<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ticket_costs MODIFY currency ENUM('idr','usd','cny') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("DELETE FROM ticket_costs WHERE currency = 'cny'");
        DB::statement("ALTER TABLE ticket_costs MODIFY currency ENUM('idr','usd') NOT NULL");
    }
};
