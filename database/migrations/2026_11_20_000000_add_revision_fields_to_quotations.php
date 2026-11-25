<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('quotations', 'revision_no')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->integer('revision_no')->default(0);
            });
        }

        if (! Schema::hasColumn('quotations', 'revision_code')) {
            Schema::table('quotations', function (Blueprint $table) {
                $table->string('revision_code')->nullable();
            });
        }

        if (! Schema::hasColumn('temp_quotations', 'quotation_id')) {
            Schema::table('temp_quotations', function (Blueprint $table) {
                $table->unsignedBigInteger('quotation_id')->nullable()->index();
            });
        }

        if (! Schema::hasColumn('temp_quotations', 'revision_no')) {
            Schema::table('temp_quotations', function (Blueprint $table) {
                $table->integer('revision_no')->nullable();
            });
        }

        if (! Schema::hasColumn('temp_quotations', 'revision_code')) {
            Schema::table('temp_quotations', function (Blueprint $table) {
                $table->string('revision_code')->nullable();
            });
        }

        if (! Schema::hasColumn('quotation_reviews', 'revision_no')) {
            Schema::table('quotation_reviews', function (Blueprint $table) {
                $table->integer('revision_no')->default(0);
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'quotation_reviews' => ['revision_no'],
            'temp_quotations' => ['revision_code', 'revision_no', 'quotation_id'],
            'quotations' => ['revision_code', 'revision_no'],
        ] as $tableName => $columns) {
            foreach ($columns as $column) {
                if (! Schema::hasColumn($tableName, $column)) {
                    continue;
                }

                Schema::table($tableName, function (Blueprint $table) use ($column) {
                    if ($column === 'quotation_id') {
                        $table->dropIndex(['quotation_id']);
                    }

                    $table->dropColumn($column);
                });
            }
        }
    }
};
