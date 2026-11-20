<?php

use App\Http\Controllers\Leads\WarmLeadController;
use App\Http\Controllers\Orders\QuotationController;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Notification};
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
config(['database.connections.quotation_mutation_worker' => [
    'driver' => 'sqlite', 'database' => $argv[1], 'prefix' => '', 'busy_timeout' => 100,
    'foreign_key_constraints' => true,
]]);
DB::setDefaultConnection('quotation_mutation_worker');
Notification::fake();
$actor = User::findOrFail($argv[2]);
Auth::guard()->setUser($actor);
$action = $argv[3];
$id = $argv[4];
$request = Request::create('/quotation-mutation-test', 'POST', json_decode($argv[5], true));
$request->headers->set('Accept', 'application/json');
$request->setUserResolver(fn () => $actor);
$held = false;
if (($argv[6] ?? '') === 'hold') {
    DB::listen(function ($event) use (&$held, $action) {
        if (!$held && (preg_match('/^update "(?:temp_quotations|quotations)"/i', $event->sql)
            || ($action === 'create' && str_starts_with($event->sql, 'insert into "temp_quotations"')))) {
            $held = true;
            echo "locked\n";
            fflush(STDOUT);
            if (trim((string) fgets(STDIN)) !== 'commit') throw new RuntimeException('Worker was not released.');
        }
    });
}
echo "ready\n";
fflush(STDOUT);
try {
    $controller = app(in_array($action, ['create', 'save', 'submit'], true) ? WarmLeadController::class : QuotationController::class);
    $response = match ($action) {
        'create' => $controller->createQuotation($request, $id),
        'save' => $controller->storeQuotation($request, $id),
        'submit' => $controller->finalSubmitQuotation($request, $id),
        'cancel' => $controller->cancelDraft($request, $id),
        'approve' => $controller->approve($request, $id),
        'reject' => $controller->reject($request, $id),
    };
    echo 'status:'.$response->getStatusCode()."\n";
} catch (HttpExceptionInterface $exception) {
    echo 'status:'.$exception->getStatusCode()."\n";
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage());
    exit(1);
}
