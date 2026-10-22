<?php

use App\Services\Aftersales\CustomerMatching;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('aftersales_mutation_locks', function (Blueprint $t) {
            $t->string('lock_key', 64)->primary();
        });
        $mysql = DB::getDriverName() === 'mysql';
        Schema::table('service_customer_products', function (Blueprint $t) use ($mysql) {
            $column = $t->string('normalized_serial')->nullable()->index();
            if ($mysql) {
                $column->collation('utf8mb4_bin');
            }
        });
        
        DB::table('service_customer_products')->select('id', 'serial_number')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('service_customer_products')->where('id', $row->id)->update(['normalized_serial' => CustomerMatching::serial($row->serial_number)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('service_customer_products', function (Blueprint $t) {
            $t->dropIndex(['normalized_serial']);
            $t->dropColumn('normalized_serial');
        });
        Schema::dropIfExists('aftersales_mutation_locks');
    }
};
