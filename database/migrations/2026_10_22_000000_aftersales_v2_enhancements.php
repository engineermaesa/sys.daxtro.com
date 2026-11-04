<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ticket_satisfactions MODIFY review_status ENUM('normal','needs_review','reviewed','rejected') NOT NULL DEFAULT 'normal'");
        DB::statement("ALTER TABLE part_stock_movements MODIFY type ENUM('in','out','adjustment','ticket_out','ticket_return') NOT NULL");

        if (! Schema::hasColumn('ticket_satisfactions', 'review_note')) {
            Schema::table('ticket_satisfactions', function (Blueprint $table) {
                $table->text('review_note')->nullable()->after('reviewed_at');
            });
        }

        if (! Schema::hasColumn('ticket_parts', 'planned_quantity')) {
            Schema::table('ticket_parts', function (Blueprint $table) {
                $table->unsignedInteger('planned_quantity')->nullable()->after('qty');
               
                $table->unsignedInteger('untracked_quantity')->default(0)->after('planned_quantity');
            });
            DB::statement("UPDATE ticket_parts SET planned_quantity = ROUND(qty), untracked_quantity = ROUND(qty) WHERE type = 'estimated'");
        }

        if (! Schema::hasColumn('part_stock_movements', 'ticket_part_id')) {
            Schema::table('part_stock_movements', function (Blueprint $table) {
                $table->foreignId('ticket_part_id')->nullable()->after('ticket_id')->constrained('ticket_parts')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('part_stock_movements', 'ticket_part_id')) {
            Schema::table('part_stock_movements', function (Blueprint $table) {
                $table->dropConstrainedForeignId('ticket_part_id');
            });
        }
        if (Schema::hasColumn('ticket_parts', 'planned_quantity')) {
            Schema::table('ticket_parts', fn (Blueprint $table) => $table->dropColumn(['planned_quantity', 'untracked_quantity']));
        }
        if (Schema::hasColumn('ticket_satisfactions', 'review_note')) {
            Schema::table('ticket_satisfactions', fn (Blueprint $table) => $table->dropColumn('review_note'));
        }
        DB::statement("DELETE FROM part_stock_movements WHERE type IN ('ticket_out','ticket_return')");
        DB::statement("ALTER TABLE part_stock_movements MODIFY type ENUM('in','out','adjustment') NOT NULL");
        DB::statement("UPDATE ticket_satisfactions SET review_status = 'needs_review' WHERE review_status = 'rejected'");
        DB::statement("ALTER TABLE ticket_satisfactions MODIFY review_status ENUM('normal','needs_review','reviewed') NOT NULL DEFAULT 'normal'");
    }
};
