<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->setStatuses(['draft', 'review', 'approved_by_bm', 'pending_finance', 'published', 'rejected', 'expired']);
    }

    public function down(): void
    {
        if (DB::table('quotations')->where('status', 'approved_by_bm')->exists()) {
            throw new RuntimeException('Quotations still use approved_by_bm; rollback would discard their workflow state.');
        }

        $this->setStatuses(['draft', 'review', 'pending_finance', 'published', 'rejected', 'expired']);
    }

    private function setStatuses(array $statuses): void
    {
        if (! Schema::hasColumn('quotations', 'status')) {
            throw new RuntimeException('The quotations table must be migrated first.');
        }

        if (DB::getDriverName() !== 'sqlsrv') {
            Schema::table('quotations', function (Blueprint $table) use ($statuses) {
                $table->enum('status', $statuses)->nullable()->default('draft')->change();
            });

            return;
        }

        $connection = DB::connection();
        $grammar = $connection->getQueryGrammar();
        $tableName = $connection->getTablePrefix().'quotations';
        $constraints = DB::select(
            "SELECT name FROM sys.check_constraints WHERE parent_object_id = OBJECT_ID(?)
            AND (parent_column_id = COLUMNPROPERTY(OBJECT_ID(?), 'status', 'ColumnId')
                OR name = 'quotations_status_check')",
            [$tableName, $tableName]
        );
        $wrappedTable = $grammar->wrapTable('quotations');
        foreach ($constraints as $constraint) {
            DB::statement('ALTER TABLE '.$wrappedTable.' DROP CONSTRAINT '.$grammar->wrap($constraint->name));
        }
        DB::statement('ALTER TABLE '.$wrappedTable.' ADD CONSTRAINT '.$grammar->wrap('quotations_status_check')
            .' CHECK ('.$grammar->wrap('status').' IN ('.$grammar->quoteString($statuses).'))');
    }
};
