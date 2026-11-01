<?php

// Opt in: php tests/Integration/aftersales-v3-mysql.php
// Requires the current local application schema, CREATE DATABASE and PROCESS privileges.
// Source connection is used for DDL reads only; all test writes target a generated database.
require dirname(__DIR__, 2).'/vendor/autoload.php';
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
require_once dirname(__DIR__).'/Support/AftersalesV3Mysql.php';

use App\Models\Aftersales\RefSurveyTemplate;
use App\Models\Aftersales\SatisfactionLink;
use App\Models\Aftersales\SatisfactionOtp;
use App\Models\Aftersales\Ticket;
use App\Models\Aftersales\TicketSatisfaction;
use App\Models\Aftersales\TicketServiceForm;
use App\Models\User;
use App\Services\Aftersales\InspectionTemplates;
use App\Services\Aftersales\TicketDocuments;
use App\Services\Aftersales\TicketHappyCalls;
use Database\Seeders\AftersalesV3RoleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SurveyTemplateSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\AftersalesV3Mysql;

const TEST_SIGNATURE = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a4X8AAAAASUVORK5CYII=';

$isWorker = ($argv[1] ?? '') === '--worker';
$database = $isWorker ? ($argv[2] ?? '') : 'aftersales_v3_test_'.bin2hex(random_bytes(6));
$root = $isWorker ? ($argv[3] ?? '') : sys_get_temp_dir().'/'.$database;
$suite = new AftersalesV3Mysql($database, $root);
if ($isWorker) {
    try {
        $input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
        echo json_encode($suite->worker($input, $argv[4] ?? ''), JSON_THROW_ON_ERROR);
        exit(0);
    } catch (Throwable $error) {
        fwrite(STDERR, get_class($error).': '.$error->getMessage());
        exit(1);
    }
}

$migrationNames = [
    '2026_10_29_000000_add_trader_name_to_service_customer_products',
    '2026_10_29_000100_seed_aftersales_v3_roles',
    '2026_10_30_000000_create_ticket_cycles',
    '2026_10_30_000100_add_document_publication_workflow',
    '2026_10_30_000200_add_recoming_v3_scheduling',
    '2026_10_30_000300_scope_diagnosis_to_ticket_cycles',
    '2026_10_30_000400_add_cycle_sla_setup',
    '2026_10_30_000500_add_survey_template_variants',
    '2026_10_30_000510_pin_cycle_survey_template',
    '2026_10_30_000600_add_happy_call_cycle_tracking',
    '2026_11_01_000000_add_service_form_sync_version',
];
$migrations = array_map(fn ($name) => require database_path('migrations/'.$name.'.php'), $migrationNames);
$request = fn (int $user, string $method, string $uri, array $data = []) => compact('user', 'method', 'uri', 'data');
$post = fn (int $user, string $uri, array $data = []) => $request($user, 'POST', $uri, $data);
$api = fn (?int $user, string $method, string $uri, array $data = [], int $status = 200, ?string $code = null) => $suite->expect($suite->api($user, $method, $uri, $data), $status, $code);
$ticketId = null;
$base = '';
$cycle1 = null;
$cycle2 = null;
$currentResponseId = null;
$snapshot = function () use ($api, &$base): array {
    return $api(2, 'GET', $base.'/service-form')['data'];
};
$sign = fn (string $type, string $role) => ['role' => $role, 'signature' => TEST_SIGNATURE, 'signer_name' => 'Integration Customer'];
$findings = fn (string $prefix) => ['diagnosis' => array_map(fn ($field) => ['field' => $field, 'body' => $prefix.' '.$field], ['problem', 'root_cause', 'action'])];
$answers = function (RefSurveyTemplate $template): array {
    $values = [];
    foreach ($template->questions() as $question) {
        $values[$question['question_key']] = match ($question['type']) {
            'rating' => 3, 'yes_no' => true, 'short_text', 'long_text' => 'Integration answer', 'choice' => $question['options'][0],
        };
    }

    return ['answers' => $values, 'signer_name' => 'Integration Customer', 'signer_position' => 'Manager', 'signature' => TEST_SIGNATURE, 'reason' => 'Customer answers recorded during integration test'];
};
$fingerprint = function (array $columns, array $ids): string {
    $rows = [];
    foreach ($columns as $table => $fields) {
        $rows[$table] = DB::table($table)->whereIn('id', $ids[$table])->select($fields)->orderBy('id')->get()->all();
    }

    return hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
};
$expectConstraint = function (callable $write, int $mysqlCode) use ($suite): void {
    try {
        $write();
        throw new RuntimeException('Invalid database write was accepted');
    } catch (QueryException $error) {
        $suite->check((int) $error->errorInfo[1] === $mysqlCode, 'Unexpected MySQL constraint error: '.$error->getMessage());
    }
};

$exitCode = 0;
try {
    $suite->createSchema();
    $version = DB::selectOne('SELECT VERSION() AS version')->version;
    echo 'MySQL '.$version.'; isolated database '.$database."\n";

    $suite->scenario('migration upgrade and legacy data preservation', function () use ($suite, $migrations, $fingerprint) {
        foreach (array_reverse($migrations) as $migration) {
            $migration->down();
        }
        foreach (['ticket_recomings', 'ticket_visits', 'ticket_logs', 'ticket_satisfactions', 'satisfaction_links'] as $table) {
            $suite->check(! Schema::hasIndex($table, $table.'_ticket_id_cycle_id_foreign'), 'Rollback left the old implicit cycle index');
            $suite->check(collect(Schema::getForeignKeys($table))->contains(fn ($key) => $key['columns'] === ['ticket_id'] && $key['foreign_table'] === 'tickets'), 'Rollback lost the original ticket foreign key');
        }
        (new RoleSeeder)->run();
        (new PermissionSeeder)->run();
        (new RolePermissionSeeder)->run();
        foreach ([1 => 'after_sales', 2 => 'technician', 3 => 'supervisor_after_sales', 4 => 'head_after_sales', 5 => 'technician', 6 => 'supervisor_after_sales', 7 => 'after_sales', 8 => 'head_after_sales', 9 => 'sales'] as $id => $role) {
            DB::table('users')->insert(['id' => $id, 'name' => 'Integration '.$role.' '.$id, 'email' => 'integration-'.$id.'@example.invalid', 'password' => str_repeat('x', 60), 'role_id' => DB::table('user_roles')->where('code', $role)->value('id')]);
        }
        DB::table('service_customers')->insert(['id' => 1, 'name' => 'Integration Customer', 'pic_name' => 'Customer', 'phone' => '081234567890', 'email' => 'customer@example.invalid']);
        DB::table('service_customer_products')->insert(['id' => 1, 'customer_id' => 1, 'machine_name' => 'Integration Machine', 'serial_number' => 'INTEGRATION-001', 'is_daxtro' => true] + array_fill_keys(TicketDocuments::UNIT_FIELDS, '1'));
        DB::table('ref_inspection_templates')->insert(['id' => 1, 'version' => 1, 'status' => 'active', 'title' => 'Integration Inspection', 'groups' => '[]', 'diagnosis_section_title' => 'Diagnosis', 'diagnosis_fields' => json_encode(InspectionTemplates::DIAGNOSIS)]);
        DB::table('inspection_template_state')->insert(['id' => 1, 'active_template_id' => 1]);
        DB::table('ref_inspection_groups')->insert(['id' => 1, 'ref_inspection_template_id' => 1, 'group_key' => 'basic', 'code' => 'A', 'title' => 'Basic inspection', 'sort_order' => 1]);
        DB::table('ref_inspection_items')->insert(['id' => 1, 'ref_inspection_template_id' => 1, 'ref_inspection_group_id' => 1, 'item_key' => 'pressure', 'group_code' => 'A', 'number' => 1, 'name' => 'Pressure', 'machine_types' => '[]', 'types_restricted' => false]);
        DB::table('ref_inspection_fields')->insert(['id' => 1, 'item_id' => 1, 'key' => 'pressure', 'field_key' => 'pressure', 'label' => 'Pressure', 'type' => 'number', 'unit' => 'bar', 'units' => '["bar"]']);
        DB::table('ref_agreement_templates')->insert(['id' => 1, 'version' => 1, 'title' => 'Integration Agreement', 'sections' => json_encode([['title' => 'Terms', 'clauses' => [['key' => 'A1', 'text' => 'Terms']]]]), 'is_active' => true]);
        DB::table('survey_template_state')->insert(['id' => 1]);
        (new SurveyTemplateSeeder)->run();
        foreach ([1 => null, 2 => now()] as $id => $deletedAt) {
            DB::table('tickets')->insert(['id' => $id, 'ticket_code' => 'LEGACY-'.$id, 'customer_id' => 1, 'customer_product_id' => 1, 'category' => 'mechanical', 'priority' => 'low', 'description' => 'Legacy record', 'sla_value' => 7, 'sla_unit' => 'day', 'assigned_technician_id' => 2, 'supervisor_id' => 3, 'created_by' => 1, 'service_type' => 'repair', 'deleted_at' => $deletedAt]);
            DB::table('ticket_logs')->insert(['ticket_id' => $id, 'step' => 'assigned', 'actor_user_id' => 1]);
        }
        DB::table('ticket_service_forms')->insert(['id' => 1, 'ticket_id' => 1, 'ref_inspection_template_id' => 1, 'template_version' => 1]);
        foreach (['problem', 'cause', 'action'] as $field) {
            DB::table('ticket_diagnosis_entries')->insert(['ticket_service_form_id' => 1, 'field' => $field, 'visit_no' => 1, 'entry_date' => '2026-10-29', 'visit_time' => '09:00:00', 'body' => 'Legacy '.$field]);
        }
        DB::table('ticket_satisfactions')->insert(['ticket_id' => 1, 'source' => 'link', 'review_status' => 'normal', 'filled_at' => '2026-10-29 09:00:00'] + array_fill_keys(array_keys(TicketSatisfaction::ASPECTS), 2));
        $tables = ['tickets', 'ticket_logs', 'service_customers', 'service_customer_products', 'ticket_service_forms', 'ticket_diagnosis_entries', 'ticket_satisfactions', 'ref_inspection_templates', 'ref_survey_templates'];
        $columns = array_combine($tables, array_map(fn ($table) => Schema::getColumnListing($table), $tables));
        $ids = array_combine($tables, array_map(fn ($table) => DB::table($table)->pluck('id')->all(), $tables));
        $before = $fingerprint($columns, $ids);
        foreach ($migrations as $migration) {
            $migration->up();
        }
        $suite->check($before === $fingerprint($columns, $ids), 'Upgrade changed legacy business fields');
        $suite->check(DB::table('ticket_cycles')->count() === 2, 'Active/soft-deleted legacy tickets were not backfilled');
        $suite->check(DB::table('tickets')->where('workflow_version', 'legacy')->count() === 2, 'Legacy workflow marker changed');
        $suite->check(DB::table('ticket_diagnosis_entries')->whereNotNull('cycle_id')->count() === 3, 'Legacy diagnosis not scoped to its cycle');
        $suite->check(DB::table('ticket_service_forms')->value('sync_version') === 1, 'Existing form revision not initialized');
        $suite->check(DB::table('ticket_satisfactions')->whereNotNull('cycle_id')->count() === 1, 'Legacy survey cycle was not backfilled');
        $suite->check(\App\Services\Aftersales\TicketSurveys::average(Ticket::findOrFail(1)) === 2.0, 'Upgrade changed legacy survey score');
        $suite->check(RefSurveyTemplate::active('phone_call')->questions() !== [], 'Phone-call template migration not initialized');
    });

    $suite->scenario('real role seeding is idempotent and Close belongs only to Head', function () use ($suite, $api) {
        $before = DB::table('user_role_permissions')->orderBy('role_id')->orderBy('permission_id')->get(['role_id', 'permission_id'])->toJson();
        (new AftersalesV3RoleSeeder)->run();
        (new AftersalesV3RoleSeeder)->run();
        $suite->check($before === DB::table('user_role_permissions')->orderBy('role_id')->orderBy('permission_id')->get(['role_id', 'permission_id'])->toJson(), 'Role seeder is not idempotent');
        foreach ([1 => false, 2 => false, 3 => false, 4 => true, 5 => false, 6 => false, 7 => false, 8 => true, 9 => false] as $id => $close) {
            $suite->check(User::findOrFail($id)->hasPermission('aftersales.tickets.close') === $close, 'Unexpected Close permission for user '.$id);
        }
        $api(2, 'GET', '/api/aftersales/tickets/1/service-form');
        $body = $api(2, 'GET', '/api/aftersales/tickets/1/service-form');
        $suite->check($body['data']['revision'] === null, 'Legacy snapshot unexpectedly requires a v3 revision');
        $api(2, 'PUT', '/api/aftersales/tickets/1/service-form', ['diagnosis' => [['field' => 'cause', 'body' => 'Legacy edited']]]);
        $api(2, 'POST', '/api/aftersales/tickets/1/service-form/submit', [], 409, 'legacy_document_workflow');
    });

    $suite->scenario('Admin creates ticket, assigns Technician, and schedules physical visit through API', function () use ($suite, $api, &$ticketId, &$base, &$cycle1) {
        $body = $api(1, 'POST', '/api/aftersales/tickets', ['customer_id' => 1, 'customer_product_id' => 1, 'category' => 'mechanical', 'priority' => 'medium', 'description' => 'Integration lifecycle', 'sla_value' => 7, 'sla_unit' => 'day', 'supervisor_id' => 3], 201);
        $ticketId = $body['data']['data']['id'];
        $base = '/api/aftersales/tickets/'.$ticketId;
        $suite->check(Ticket::findOrFail($ticketId)->workflow_version === 'v3', 'New ticket did not default to v3');
        $cycle1 = Ticket::findOrFail($ticketId)->currentCycle->id;
        $api(1, 'POST', $base.'/assign', ['assigned_technician_id' => 2]);
        $api(1, 'POST', $base.'/visits', ['scheduled_at' => now()->format('Y-m-d H:i:s')], 201);
        $api(1, 'POST', $base.'/on-site', [], 201);
        $suite->check(Ticket::findOrFail($ticketId)->progress === 'on_site', 'On Site transition failed');
    });

    $suite->scenario('API read and mutation authorization uses actual seeded permissions', function () use ($suite, $api, &$base, &$ticketId, $snapshot) {
        foreach ([1, 2, 3, 4, 6, 7, 8] as $user) {
            foreach (['', '/service-form', '/agreement', '/documents'] as $suffix) {
                $api($user, 'GET', $base.$suffix);
            }
        }
        foreach ([5 => 403, 9 => 403, 0 => 401] as $user => $status) {
            foreach (['', '/service-form', '/agreement', '/documents'] as $suffix) {
                $api($user ?: null, 'GET', $base.$suffix, [], $status);
            }
        }
        $token = $snapshot()['revision'];
        foreach ([1, 3, 4, 5, 6, 7, 8, 9] as $user) {
            $api($user, 'PUT', $base.'/service-form', ['revision' => $token, 'service_type' => 'repair'], 403);
            $api($user, 'POST', $base.'/service-form/submit', ['revision' => $token], 403);
        }
        foreach ([2, 3, 4, 5, 6, 8, 9] as $user) {
            $api($user, 'POST', $base.'/happy-call', [], 403);
            $api($user, 'POST', $base.'/recoming', [], 403);
            $api($user, 'POST', $base.'/recoming/reschedule', [], 403);
        }
        foreach ([1, 2, 3, 5, 6, 7, 9] as $user) {
            $api($user, 'POST', $base.'/close', [], 403);
        }
        foreach ([1, 2, 4, 5, 6, 7, 8, 9] as $user) {
            $api($user, 'POST', $base.'/documents/service_form/signatures', ['role' => 'supervisor', 'signature' => TEST_SIGNATURE], 403);
        }
        foreach (['/service-form/submit', '/happy-call', '/close', '/recoming', '/documents/service_form/signatures'] as $path) {
            $api(null, 'POST', $base.$path, [], 401);
        }
        $environment = app()->environment();
        app()->instance('env', 'local');
        try {
            $api(2, 'PUT', $base.'/service-form', ['revision' => $token], 419);
        } finally {
            app()->instance('env', $environment);
        }
        $suite->check(Ticket::query()->findOrFail($ticketId)->progress === 'on_site', 'Denied mutation changed workflow');
    });

    $suite->scenario('form edits, online photos, signatures, and stale offline rejection', function () use ($suite, $api, &$base, &$ticketId, $snapshot, $sign, $findings) {
        $old = $snapshot()['revision'];
        $data = ['revision' => $old, 'service_type' => 'repair', 'inspections' => [['item_number' => 1, 'status' => 'ok', 'values' => ['pressure' => '2']]]] + $findings('Initial');
        $body = $api(2, 'PUT', $base.'/service-form', $data);
        $suite->check($body['data']['revision'] !== $old && Ticket::findOrFail($ticketId)->progress === 'repair', 'First edit did not change revision/progress');
        $api(2, 'POST', $base.'/service-form/submit', ['revision' => $old, 'service_type' => 'preventive'], 409, 'service_form_conflict');
        $link = $api(2, 'POST', $base.'/service-form/photo-links', ['item_number' => 1, 'field_key' => 'pressure'], 201);
        $uploadPath = '/api/inspection-photo/'.$link['data']['token'];
        $suite->expect($suite->api(null, 'POST', $uploadPath, [], ['photo' => UploadedFile::fake()->image('inspection.jpg')]), 201);
        foreach (['problem', 'root_cause', 'action'] as $field) {
            $suite->expect($suite->api(2, 'POST', $base.'/service-form/diagnosis/photos', ['field' => $field], ['photo' => UploadedFile::fake()->image($field.'.jpg')]), 201);
        }
        $api(2, 'PUT', $base.'/agreement', ['accepted_clauses' => ['A1'], 'customer_name' => 'Integration Customer']);
        foreach (['service_form', 'agreement'] as $type) {
            foreach (['technician', 'customer'] as $role) {
                $api(2, 'POST', $base.'/documents/'.$type.'/signatures', $sign($type, $role), 201);
            }
        }
        $suite->check($snapshot()['submission_completeness']['complete'], 'Actual inspection/diagnosis/photos/signatures are incomplete');
    });

    $suite->scenario('offline drafts waiting on lock cannot overwrite a newer committed save', function () use ($suite, $api, &$base, &$ticketId, $snapshot, $request) {
        $token = $snapshot()['revision'];
        $draft = $request(2, 'PUT', $base.'/service-form', ['revision' => $token, 'diagnosis' => [['field' => 'problem', 'body' => 'Stale offline body']]]);
        $results = $suite->race($ticketId, [$draft, $draft], function () use ($api, &$base, $token) {
            $api(2, 'PUT', $base.'/service-form', ['revision' => $token, 'diagnosis' => [['field' => 'problem', 'body' => 'New committed server body']]]);
        });
        foreach ($results as $result) {
            $suite->expect($result, 409, 'service_form_conflict');
        }
        $formId = TicketServiceForm::where('ticket_id', $ticketId)->value('id');
        $suite->check(DB::table('ticket_diagnosis_entries')->where('ticket_service_form_id', $formId)->where('field', 'problem')->value('body') === 'New committed server body', 'Waiting draft overwrote newer content');
    });

    $suite->scenario('simultaneous submit serializes and rejects stale retry', function () use ($suite, $api, &$base, &$ticketId, $snapshot, $post) {
        $token = $snapshot()['revision'];
        $results = $suite->race($ticketId, [$post(2, $base.'/service-form/submit', ['revision' => $token]), $post(2, $base.'/service-form/submit', ['revision' => $token])]);
        $statuses = array_column($results, 'status');
        sort($statuses);
        $suite->check($statuses === [200, 409], 'Concurrent submit must produce one success and one conflict');
        foreach ($results as $result) {
            if ($result['status'] === 409) {
                $suite->expect($result, 409, 'service_form_conflict');
            }
        }
        $api(2, 'POST', $base.'/service-form/submit', ['revision' => $snapshot()['revision']]);
        $suite->check(Ticket::findOrFail($ticketId)->currentCycle->documentation_status === 'awaiting_spv_signature', 'Submit state incorrect');
        $suite->check(DB::table('ticket_document_publications')->where('ticket_id', $ticketId)->count() === 0, 'Submit published before SPV signatures');
    });

    $suite->scenario('simultaneous SPV signatures publish one log, link, and dispatch set', function () use ($suite, &$base, &$ticketId, &$cycle1, $sign, $post) {
        $results = $suite->race($ticketId, [$post(3, $base.'/documents/service_form/signatures', $sign('service_form', 'supervisor')), $post(3, $base.'/documents/agreement/signatures', $sign('agreement', 'supervisor'))]);
        foreach ($results as $result) {
            $suite->expect($result, 201);
        }
        $suite->check(DB::table('ticket_document_publications')->where('cycle_id', $cycle1)->count() === 1, 'Publication duplicated');
        $suite->check(DB::table('ticket_logs')->where('cycle_id', $cycle1)->where('step', 'documentation_published')->count() === 1, 'Publication log duplicated');
        $suite->check(DB::table('satisfaction_links')->where('cycle_id', $cycle1)->count() === 1, 'Survey link duplicated');
        $count = DB::table('ticket_document_dispatches')->whereNotNull('publication_id')->count();
        $suite->check($count === 6 && DB::table('ticket_document_dispatches')->whereNotNull('publication_id')->distinct()->count('publication_id') === 1, 'Publication must have two document types for three recipients');
        $retries = $suite->race($ticketId, [$post(3, $base.'/documents/service_form/signatures', $sign('service_form', 'supervisor')), $post(3, $base.'/documents/agreement/signatures', $sign('agreement', 'supervisor'))]);
        foreach ($retries as $result) {
            $suite->expect($result, 201);
        }
        $suite->check(DB::table('ticket_document_dispatches')->whereNotNull('publication_id')->count() === $count, 'SPV retry duplicated dispatches');
    });

    $suite->scenario('public OTP and site versus Admin survey race, review, Happy Call race, ready pagination', function () use ($suite, $api, &$base, &$ticketId, &$cycle1, &$currentResponseId, $answers, $post) {
        $link = SatisfactionLink::where('cycle_id', $cycle1)->firstOrFail();
        $payload = $answers($link->template);
        $publicBase = '/api/survey/'.basename(parse_url($link->url, PHP_URL_PATH));
        $api(null, 'GET', $publicBase.'/form', [], 401);
        // Seed a synthetic delivered OTP; provider transport is intentionally outside this test.
        SatisfactionOtp::create(['link_id' => $link->id, 'phone_masked' => '0812****7890', 'code_hash' => hash('sha256', '123456'), 'sent_at' => now(), 'expires_at' => now()->addMinutes(5)]);
        $verified = $api(null, 'POST', $publicBase.'/otp/verify', ['code' => '123456']);
        $headers = ['X-Survey-Session' => $verified['data']['session_token']];
        $suite->expect($suite->api(null, 'GET', $publicBase.'/form', [], [], $headers), 200);
        $public = ['user' => null, 'method' => 'POST', 'uri' => $publicBase.'/submit', 'data' => $payload, 'headers' => $headers];
        $results = $suite->race($ticketId, [$public, $post(1, $base.'/survey-response', $payload)]);
        $statuses = array_column($results, 'status');
        sort($statuses);
        $suite->check($statuses === [201, 422], 'Site survey single-use gate failed');
        $response = Ticket::findOrFail($ticketId)->satisfactions()->where('cycle_id', $cycle1)->sole();
        $currentResponseId = $response->id;
        $suite->check($response->review_status === 'needs_review' && Ticket::findOrFail($ticketId)->sla_stopped_at->eq($response->filled_at), 'Survey review/SLA stop incorrect');
        $suite->check(Ticket::findOrFail($ticketId)->happy_call_due_at->eq($response->filled_at->copy()->addDays(3)), 'Happy Call is not H+3');
        $api(1, 'POST', $base.'/happy-call', [], 422, 'survey_not_valid');
        foreach ([2, 3, 4, 5, 6, 8, 9] as $user) {
            $api($user, 'POST', '/api/aftersales/satisfaction-surveys/'.$response->id.'/review', ['decision' => 'approve'], 403);
        }
        $api(1, 'POST', '/api/aftersales/satisfaction-surveys/'.$response->id.'/review', ['decision' => 'approve']);
        $api(4, 'POST', $base.'/close', [], 422, 'happy_call_required');
        $context = ['cycle_id' => $cycle1, 'satisfaction_id' => $response->id];
        $results = $suite->race($ticketId, [$post(1, $base.'/happy-call', $context), $post(1, $base.'/happy-call', $context)]);
        foreach ($results as $result) {
            $suite->expect($result, 201);
        }
        $suite->check($results[0]['body']['data']['id'] === $results[1]['body']['data']['id'], 'Happy Call retry returned different logs');
        $suite->check(DB::table('ticket_logs')->where('cycle_id', $cycle1)->where('step', 'happy_call_completed')->count() === 1, 'Happy Call duplicated');
        foreach ([1, 3, 4, 6, 8] as $user) {
            $page = $api($user, 'GET', '/api/aftersales/tickets/ready-to-close?per_page=1');
            $suite->check($page['total'] === 1 && $page['data'][0]['id'] === $ticketId, 'Ready-to-close page incorrect');
            $suite->check($page['data'][0]['can_close'] === in_array($user, [4, 8], true), 'Ready-to-close capability is incorrect');
        }
        foreach ([2, 5, 9] as $user) {
            $api($user, 'GET', '/api/aftersales/tickets/ready-to-close', [], 403);
        }
        $alias = $api(4, 'GET', '/api/aftersales/tickets?ready_to_close=1&per_page=1');
        $suite->check($alias['total'] === 1 && $alias['data'][0]['id'] === $ticketId, 'Ready-to-close alias differs from dedicated endpoint');
    });

    $suite->scenario('Recoming invalidates old Happy Call, preserves SPV signatures, and publishes phone cycle once', function () use ($suite, $api, &$base, &$ticketId, &$cycle1, &$cycle2, &$currentResponseId, $snapshot, $findings, $post) {
        $old = $snapshot()['revision'];
        $signatureIds = Ticket::findOrFail($ticketId)->serviceForm->signatures()->pluck('id')->all();
        $api(1, 'POST', $base.'/documents/service_form/unlock');
        $suite->check($signatureIds === Ticket::findOrFail($ticketId)->serviceForm->signatures()->pluck('id')->all(), 'Unlock lost SPV/Customer/Technician signatures');
        $schedule = now()->addDays(2)->startOfSecond();
        $payload = ['recoming_type' => 'phone_call', 'reason' => 'unfinished_previous_work', 'planned_call_at' => $schedule->format('Y-m-d H:i:s'), 'sla_mode' => 'duration', 'sla_value' => 2, 'sla_unit' => 'day'];
        $results = $suite->race($ticketId, [$post(1, $base.'/recoming', $payload), $post(7, $base.'/recoming', $payload)]);
        $statuses = array_column($results, 'status');
        sort($statuses);
        $suite->check($statuses === [201, 422] && Ticket::findOrFail($ticketId)->cycles()->count() === 2, 'Concurrent Recoming allocated duplicate cycles');
        $cycle = Ticket::findOrFail($ticketId)->currentCycle;
        $cycle2 = $cycle->id;
        $suite->check($cycle->cycle_no === 2 && $cycle->sla_due_at->eq($schedule->copy()->addDays(2)), 'Recoming SLA/cycle incorrect');
        $beforeReschedule = $snapshot()['revision'];
        $schedule->addDay();
        $api(1, 'POST', $base.'/recoming/reschedule', ['planned_call_at' => $schedule->format('Y-m-d H:i:s')]);
        $cycle = Ticket::findOrFail($ticketId)->currentCycle;
        $suite->check($cycle->id === $cycle2 && $cycle->sla_due_at->eq($schedule->copy()->addDays(2)), 'Duration SLA was not recalculated on reschedule');
        $api(2, 'POST', $base.'/service-form/submit', ['revision' => $beforeReschedule] + $findings('Before reschedule'), 409, 'service_form_conflict');
        $suite->check(Ticket::findOrFail($ticketId)->happy_call_due_at === null && ! TicketHappyCalls::closable(Ticket::findOrFail($ticketId)), 'Old Happy Call remained current');
        $api(4, 'POST', $base.'/close', ['cycle_id' => $cycle1, 'satisfaction_id' => $currentResponseId], 409, 'ticket_cycle_changed');
        $api(2, 'POST', $base.'/service-form/submit', ['revision' => $old] + $findings('Old draft'), 409, 'service_form_conflict');
        $suite->check(Ticket::findOrFail($ticketId)->visits()->count() === 1, 'Phone call created physical visit');
        $api(2, 'PUT', $base.'/service-form', ['revision' => $snapshot()['revision']] + $findings('Phone findings'));
        $suite->check(Ticket::findOrFail($ticketId)->progress === 'repair', 'First Recoming edit did not move to Repair');
        $api(1, 'POST', $base.'/recoming/reschedule', ['planned_call_at' => $schedule->format('Y-m-d H:i:s')], 422);
        $revision = $snapshot()['revision'];
        $results = $suite->race($ticketId, [$post(2, $base.'/service-form/submit', ['revision' => $revision]), $post(2, $base.'/service-form/submit', ['revision' => $revision])]);
        $statuses = array_column($results, 'status');
        sort($statuses);
        $suite->check($statuses === [200, 409], 'Recoming submit race incorrect');
        $suite->check(DB::table('ticket_document_publications')->where('cycle_id', $cycle2)->count() === 1, 'Recoming publication duplicated');
        $suite->check(DB::table('satisfaction_links')->where('cycle_id', $cycle2)->count() === 0, 'Phone cycle issued site link');
        $suite->check(DB::table('ticket_logs')->where('cycle_id', $cycle2)->where('step', 'repair')->count() === 1, 'Repair marker duplicated');
    });

    $suite->scenario('phone survey race, reject and replace, numeric Technician score privacy', function () use ($suite, $api, &$base, &$ticketId, &$cycle2, &$currentResponseId, $answers, $post) {
        $form = $api(1, 'GET', $base.'/survey-response')['data'];
        $payload = ['cycle_id' => $cycle2, 'ref_survey_template_id' => $form['ref_survey_template_id']] + $answers(RefSurveyTemplate::findOrFail($form['ref_survey_template_id']));
        foreach ([2, 3, 4, 5, 6, 8, 9] as $user) {
            $api($user, 'GET', $base.'/survey-response', [], 403);
            $api($user, 'POST', $base.'/survey-response', $payload, 403);
        }
        $results = $suite->race($ticketId, [$post(1, $base.'/survey-response', $payload), $post(1, $base.'/survey-response', $payload)]);
        $statuses = array_column($results, 'status');
        sort($statuses);
        $suite->check($statuses === [201, 409], 'Phone survey single-use gate failed');
        $response = Ticket::findOrFail($ticketId)->satisfactions()->where('cycle_id', $cycle2)->sole();
        $rejectedId = $response->id;
        $api(1, 'POST', '/api/aftersales/satisfaction-surveys/'.$response->id.'/review', ['decision' => 'reject', 'note' => 'Please correct customer answers']);
        $suite->check(Ticket::findOrFail($ticketId)->sla_stopped_at === null && Ticket::findOrFail($ticketId)->happy_call_due_at === null, 'Rejected survey did not resume SLA/reset Happy Call');
        $api(1, 'POST', $base.'/survey-response', $payload, 201);
        $response = Ticket::findOrFail($ticketId)->satisfactions()->where('cycle_id', $cycle2)->latest('id')->firstOrFail();
        $currentResponseId = $response->id;
        $api(1, 'POST', '/api/aftersales/satisfaction-surveys/'.$response->id.'/review', ['decision' => 'approve']);
        $api(1, 'POST', $base.'/happy-call', ['cycle_id' => $cycle2, 'satisfaction_id' => $rejectedId], 409, 'survey_response_changed');
        $suite->check((float) $response->fresh()->average_score === 3.0, 'Phone score aggregation incorrect');
        $details = $api(2, 'GET', $base);
        $suite->check((float) $details['data']['average_score'] === 3.0 && $details['data']['survey_responses'] === null, 'Technician should receive numeric score without survey details');
        $suite->check(! array_key_exists('satisfaction_id', $details['data']['happy_call']), 'Technician received private survey response ID');
        $suite->check(! str_contains(json_encode($details), 'Please correct customer answers'), 'Technician received private review notes');
        $api(2, 'GET', '/api/aftersales/satisfaction-surveys/'.$response->id, [], 403);
        $suite->check(DB::table('satisfaction_links')->where('cycle_id', $cycle2)->count() === 0, 'Phone rejection created site link');
    });

    $suite->scenario('concurrent overdue escalation deduplicates recipients and notification deletion', function () use ($suite, &$ticketId, &$cycle2, &$currentResponseId) {
        // Advance every participating clock, retaining the response order and original survey time.
        \Carbon\Carbon::setTestNow(now()->addDays(4));
        try {
            $job = ['action' => 'overdue', 'ticket_id' => $ticketId, 'now' => now()->toIso8601String()];
            $results = $suite->race($ticketId, [$job, $job]);
            $counts = array_column($results, 'count');
            sort($counts);
            $suite->check($counts === [0, 3], 'Overdue race did not deduplicate Head and creating Admin: '.json_encode($counts));
            $suite->check(DB::table('notifications')->orderBy('notifiable_id')->pluck('notifiable_id')->map(fn ($id) => (int) $id)->all() === [1, 4, 8], 'Incorrect notification recipients');
            DB::table('notifications')->delete();
            $suite->check(TicketHappyCalls::notifyOverdue($ticketId) === 0 && DB::table('ticket_happy_call_escalations')->where('cycle_id', $cycle2)->count() === 3, 'Notification deletion bypassed dedup receipt');
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    });

    $suite->scenario('Happy Call then concurrent Head Close creates one closure', function () use ($suite, $api, &$base, &$ticketId, &$cycle2, &$currentResponseId, $post) {
        $context = ['cycle_id' => $cycle2, 'satisfaction_id' => $currentResponseId];
        $api(1, 'POST', $base.'/happy-call', $context, 201);
        $results = $suite->race($ticketId, [$post(4, $base.'/close', $context), $post(8, $base.'/close', $context)]);
        $statuses = array_column($results, 'status');
        sort($statuses);
        $suite->check($statuses === [201, 422], 'Close race should create one closure');
        $suite->check(DB::table('ticket_logs')->where('ticket_id', $ticketId)->where('step', 'closed')->count() === 1, 'Duplicate Closed logs');
        $suite->check(Ticket::findOrFail($ticketId)->progress === 'closed', 'Ticket did not close');
        $page = $api(4, 'GET', '/api/aftersales/tickets/ready-to-close');
        $suite->check($page['total'] === 0, 'Closed ticket remained ready to close');
        $suite->check(TicketHappyCalls::notifyOverdue($ticketId) === 0, 'Closed ticket escalated');
    });

    $suite->scenario('MySQL foreign keys, unique indexes, and protected rollback', function () use ($suite, $expectConstraint, $migrations, &$ticketId, &$cycle1, &$cycle2, &$currentResponseId) {
        $expectConstraint(fn () => DB::table('ticket_document_publications')->insert(['ticket_id' => $ticketId, 'cycle_id' => $cycle2, 'published_at' => now()]), 1062);
        $expectConstraint(fn () => DB::table('ticket_logs')->insert(['ticket_id' => 1, 'cycle_id' => $cycle2, 'step' => 'repair']), 1452);
        $expectConstraint(fn () => DB::table('ticket_logs')->insert(['ticket_id' => $ticketId, 'cycle_id' => $cycle2, 'step' => 'happy_call_completed', 'satisfaction_id' => $currentResponseId]), 1062);
        $expectConstraint(fn () => DB::table('ticket_cycles')->insert(['ticket_id' => $ticketId, 'cycle_no' => 2, 'started_at' => now()]), 1062);
        foreach ([5 => 'Cycle-specific Recoming findings exist', 7 => 'Phone-call survey history exists',
            8 => 'Pinned cycle survey templates exist', 9 => 'Happy Call survey links or escalation history exist',
            10 => 'Service Form sync revisions exist'] as $index => $message) {
            try {
                $migrations[$index]->down();
                throw new RuntimeException('Migration rollback discarded used v3 history: '.$index);
            } catch (RuntimeException $error) {
                $suite->check(str_contains($error->getMessage(), $message), 'Unexpected rollback error: '.$error->getMessage());
            }
        }
        $suite->check(DB::table('ticket_cycles')->where('ticket_id', $ticketId)->count() === 2, 'Protected rollback changed cycles');
        $suite->check(DB::table('ticket_document_publications')->where('ticket_id', $ticketId)->count() === 2, 'Protected rollback changed publications');
        $suite->check(TicketServiceForm::where('ticket_id', $ticketId)->value('sync_version') > 1, 'Protected rollback reset revision');
    });

    echo json_encode(['status' => 'passed', 'mysql' => $version, 'scenarios' => count($suite->results), 'assertions' => $suite->assertions, 'results' => $suite->results], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n";
} catch (Throwable $error) {
    $exitCode = 1;
    fwrite(STDERR, 'FAIL '.get_class($error).': '.$error->getMessage()."\n".$error->getFile().':'.$error->getLine()."\n");
} finally {
    $suite->cleanup();
    echo "Temporary database, worker markers, and files removed; application database unchanged.\n";
}
exit($exitCode);
