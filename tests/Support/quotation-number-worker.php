<?php

use App\Services\Orders\QuotationNumberGenerator;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$container = new Container;
$database = new Manager($container);
$database->addConnection([
    'driver' => 'sqlite', 'database' => $argv[1], 'prefix' => '', 'busy_timeout' => 10000,
]);
$container->instance('db', $database->getDatabaseManager());
Facade::setFacadeApplication($container);

echo "ready\n";
fflush(STDOUT);
DB::transaction(function () use ($argv) {
    $number = (new QuotationNumberGenerator)->next();
    echo 'reserved:'.$number."\n";
    fflush(STDOUT);

    if (($argv[2] ?? null) === 'hold' && trim((string) fgets(STDIN)) !== 'commit') {
        throw new RuntimeException('The test worker did not receive a commit signal.');
    }

    DB::table('quotations')->insert(['quotation_no' => $number]);
});
echo "committed\n";
