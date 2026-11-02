<?php

namespace Tests\Support;

use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use RuntimeException;

class AftersalesV3Mysql
{
    public int $assertions = 0;

    public array $results = [];

    private $admin;

    private $source;

    private array $workers = [];

    private bool $created = false;

    public function __construct(public string $database, public string $root)
    {
        $this->check((bool) preg_match('/^aftersales_v3_test_[a-f0-9]{12}$/D', $database), 'Generated test database required');
        $this->check(basename($root) === $database && dirname($root) === sys_get_temp_dir(), 'Generated temporary directory required');
        $config = config('database.connections.mysql');
        $this->check(in_array($config['host'], ['localhost', '127.0.0.1'], true) && empty($config['url']), 'Only an explicitly configured local MySQL connection is supported');
        $this->check($config['database'] && ! str_starts_with($config['database'], 'aftersales_v3_test_'), 'Application source database must differ from the test database');
        $this->source = DB::build($config);
        $config['database'] = null;
        $this->admin = DB::build($config);
        $config['database'] = $database;
        config([
            'database.connections.aftersales_v3_test' => $config, 'database.default' => 'aftersales_v3_test',
            'app.env' => 'testing', 'app.locked' => false, 'app.debug' => false,
            'app.key' => 'base64:'.base64_encode(str_repeat('t', 32)),
            'session.driver' => 'array', 'cache.default' => 'array', 'mail.default' => 'array',
            'filesystems.disks.local.root' => $root.'/files', 'filesystems.disks.public.root' => $root.'/public',
            'logging.default' => 'single', 'logging.channels.single.path' => $root.'/runtime.log',
        ]);
        Queue::fake();
        Mail::fake();
    }

    public function check(bool $condition, string $message): void
    {
        $this->assertions++;
        if (! $condition) {
            throw new RuntimeException($message);
        }
    }

    public function scenario(string $name, callable $test): void
    {
        $before = $this->assertions;
        $started = microtime(true);
        $test();
        $this->results[] = ['scenario' => $name, 'assertions' => $this->assertions - $before, 'seconds' => round(microtime(true) - $started, 3)];
        echo 'PASS '.$name."\n";
        flush();
    }

    public function createSchema(): void
    {
        File::makeDirectory($this->root, 0755, true, true);
        $this->admin->statement('CREATE DATABASE `'.$this->database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $this->created = true;
        $this->check(DB::connection()->getDatabaseName() === $this->database, 'Unexpected write database');
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        try {
            $sourceDatabase = $this->source->getDatabaseName();
            // DDL only. Never select customer, user, permission, or other source business rows.
            foreach ($this->source->table('information_schema.TABLES')->where('TABLE_SCHEMA', $sourceDatabase)->where('TABLE_TYPE', 'BASE TABLE')->pluck('TABLE_NAME') as $table) {
                $this->check((bool) preg_match('/^[A-Za-z0-9_]+$/D', $table), 'Unexpected table identifier');
                $ddl = (array) $this->source->selectOne('SHOW CREATE TABLE `'.$table.'`');
                $this->check(! str_contains($ddl['Create Table'], '`'.$sourceDatabase.'`.'), 'Cross-database reference refused');
                DB::unprepared($ddl['Create Table']);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function api(?int $userId, string $method, string $uri, array $data = [], array $files = [], array $headers = []): array
    {
        Auth::forgetGuards();
        if ($userId !== null) {
            Auth::guard('web')->setUser(User::findOrFail($userId));
        }
        $server = ['HTTP_ACCEPT' => 'application/json', 'REMOTE_ADDR' => '198.51.100.12', 'HTTP_USER_AGENT' => 'AftersalesV3MysqlIntegration'];
        if ($files) {
            $request = Request::create($uri, $method, $data, [], $files, $server);
        } else {
            $request = Request::create($uri, $method, [], [], [], $server + ['CONTENT_TYPE' => 'application/json'], json_encode($data, JSON_THROW_ON_ERROR));
        }
        foreach ($headers as $name => $value) {
            $request->headers->set($name, $value);
        }
        $kernel = app(Kernel::class);
        $response = $kernel->handle($request);
        $result = ['status' => $response->getStatusCode(), 'body' => json_decode($response->getContent(), true), 'request' => ['user' => $userId, 'method' => $method, 'uri' => $uri]];
        $kernel->terminate($request, $response);

        return $result;
    }

    public function expect(array $response, int $status, ?string $code = null): array
    {
        $this->check($response['status'] === $status, 'HTTP '.$status.' expected; received '.json_encode($response, JSON_UNESCAPED_SLASHES));
        if ($code !== null) {
            $this->check(($response['body']['code'] ?? null) === $code, 'Unexpected error code: '.json_encode($response));
        }

        return $response['body'] ?? [];
    }

    /** Each worker executes an actual HTTP request while the parent holds the ticket row. */
    public function race(int $ticketId, array $requests, ?callable $whileLocked = null): array
    {
        $this->check(count($requests) >= 2, 'A race requires at least two requests');
        DB::beginTransaction();
        try {
            DB::table('tickets')->where('id', $ticketId)->lockForUpdate()->firstOrFail();
            foreach ($requests as $request) {
                $ready = $this->root.'/worker-'.bin2hex(random_bytes(6)).'.ready';
                $process = proc_open([PHP_BINARY, base_path('tests/Integration/aftersales-v3-mysql.php'), '--worker', $this->database, $this->root, $ready],
                    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
                $this->check(is_resource($process), 'Worker could not start');
                $this->workers[] = ['process' => $process, 'pipes' => $pipes, 'ready' => $ready, 'out' => '', 'err' => ''];
                fwrite($pipes[0], json_encode($request, JSON_THROW_ON_ERROR));
                fclose($pipes[0]);
                stream_set_blocking($pipes[1], false);
                stream_set_blocking($pipes[2], false);
            }
            $deadline = microtime(true) + 20;
            do {
                $blocked = 0;
                foreach ($this->workers as $worker) {
                    if (is_file($worker['ready'])) {
                        $thread = (int) file_get_contents($worker['ready']);
                        $transaction = $this->admin->selectOne('SELECT trx_state AS state FROM information_schema.innodb_trx WHERE trx_mysql_thread_id = ?', [$thread]);
                        $blocked += ($transaction->state ?? null) === 'LOCK WAIT' ? 1 : 0;
                    }
                }
                if ($blocked === count($requests)) {
                    break;
                }
                usleep(100000);
            } while (microtime(true) < $deadline);
            $this->check($blocked === count($requests), 'Not all workers reached a real InnoDB LOCK WAIT');
            if ($whileLocked) {
                $whileLocked();
            }
            DB::commit();
            $results = [];
            $deadline = microtime(true) + 20;
            foreach ($this->workers as &$worker) {
                do {
                    $worker['out'] .= stream_get_contents($worker['pipes'][1]);
                    $worker['err'] .= stream_get_contents($worker['pipes'][2]);
                    $state = proc_get_status($worker['process']);
                    if (! $state['running']) {
                        break;
                    }
                    usleep(100000);
                } while (microtime(true) < $deadline);
                $this->check(! $state['running'], 'Worker timed out');
                $worker['out'] .= stream_get_contents($worker['pipes'][1]);
                $worker['err'] .= stream_get_contents($worker['pipes'][2]);
                $this->check($state['exitcode'] === 0, 'Worker failed: '.$worker['err'].$worker['out']);
                $results[] = json_decode($worker['out'], true, 512, JSON_THROW_ON_ERROR);
            }
            unset($worker);

            return $results;
        } finally {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->closeWorkers();
        }
    }

    public function worker(array $request, string $ready): array
    {
        $this->check(dirname($ready) === $this->root, 'Worker marker must belong to this run');
        if (isset($request['now'])) {
            \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse($request['now']));
        }
        file_put_contents($ready, (string) DB::selectOne('SELECT CONNECTION_ID() AS id')->id);
        if (($request['action'] ?? null) === 'overdue') {
            return ['count' => \App\Services\Aftersales\TicketHappyCalls::notifyOverdue($request['ticket_id'])];
        }

        return $this->api($request['user'], $request['method'], $request['uri'], $request['data'] ?? [], [], $request['headers'] ?? []);
    }

    private function closeWorkers(): void
    {
        foreach ($this->workers as $worker) {
            $state = proc_get_status($worker['process']);
            if ($state['running']) {
                proc_terminate($worker['process']);
            }
            foreach ($worker['pipes'] as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            proc_close($worker['process']);
            if (is_file($worker['ready'])) {
                unlink($worker['ready']);
            }
        }
        $this->workers = [];
    }

    public function cleanup(): void
    {
        $this->closeWorkers();
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        DB::purge('aftersales_v3_test');
        if ($this->created) {
            $this->admin->statement('DROP DATABASE `'.$this->database.'`');
        }
        // Only the verified, randomly generated directory for this run is eligible.
        $resolved = realpath($this->root);
        if ($resolved !== false && basename($resolved) === $this->database && realpath(dirname($resolved)) === realpath(sys_get_temp_dir())) {
            File::deleteDirectory($resolved);
        }
    }
}
