<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->setCounterTypes(['ticket', 'work_order', 'quotation']);

        $highest = 0;
        DB::table('quotations')->whereNotNull('quotation_no')
            ->select(['id', 'quotation_no'])
            ->chunkById(500, function ($quotations) use (&$highest) {
                foreach ($quotations as $quotation) {
                    if (preg_match('/^QT_DEAL_(\d+)$/D', $quotation->quotation_no, $matches)) {
                        $highest = max($highest, (int) $matches[1]);
                    }
                }
            });

        $now = Carbon::now('Asia/Jakarta');
        DB::transaction(function () use ($highest, $now) {
            $counter = DB::table('document_counters')
                ->where('type', 'quotation')->where('date', '1970-01-01');
            $existing = (clone $counter)->lockForUpdate()->first();

            if (! $existing) {
                DB::table('document_counters')->insert([
                    'type' => 'quotation', 'date' => '1970-01-01',
                    'last_number' => $highest, 'created_at' => $now, 'updated_at' => $now,
                ]);
            } elseif ((int) $existing->last_number < $highest) {
                (clone $counter)->update(['last_number' => $highest, 'updated_at' => $now]);
            }
        });
    }

    public function down(): void
    {
        if (DB::table('document_counters')->where('type', 'quotation')->where('last_number', '>', 0)->exists()) {
            throw new RuntimeException('Quotation numbers are reserved; rollback would discard the numbering history.');
        }

        DB::table('document_counters')->where('type', 'quotation')->delete();
        $this->setCounterTypes(['ticket', 'work_order']);
    }

    private function setCounterTypes(array $types): void
    {
        if (! Schema::hasColumn('document_counters', 'type')) {
            throw new RuntimeException('The existing document_counters table must be migrated first.');
        }

        if (DB::getDriverName() !== 'sqlsrv') {
            Schema::table('document_counters', function (Blueprint $table) use ($types) {
                $table->enum('type', $types)->change();
            });

            return;
        }

        // SQL Server represents enum columns as NVARCHAR + a CHECK constraint.
        $connection = DB::connection();
        $grammar = $connection->getQueryGrammar();
        $tableName = $connection->getTablePrefix().'document_counters';
        $constraints = DB::select(
            "SELECT name FROM sys.check_constraints WHERE parent_object_id = OBJECT_ID(?)
            AND (parent_column_id = COLUMNPROPERTY(OBJECT_ID(?), 'type', 'ColumnId')
                OR name = 'document_counters_type_check')",
            [$tableName, $tableName]
        );
        $wrappedTable = $grammar->wrapTable('document_counters');
        foreach ($constraints as $constraint) {
            DB::statement('ALTER TABLE '.$wrappedTable.' DROP CONSTRAINT '.$grammar->wrap($constraint->name));
        }
        $values = $grammar->quoteString($types);
        DB::statement('ALTER TABLE '.$wrappedTable.' ADD CONSTRAINT '
            .$grammar->wrap('document_counters_type_check').' CHECK ('.$grammar->wrap('type').' IN ('.$values.'))');
    }
};
