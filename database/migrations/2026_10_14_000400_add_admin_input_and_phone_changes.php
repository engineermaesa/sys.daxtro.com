<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('ticket_satisfactions', 'admin_input_reason')) {
            Schema::table('ticket_satisfactions', function (Blueprint $table) {
                $table->string('admin_input_reason')->nullable()->after('review_reasons');
            });
        }

        if (! Schema::hasTable('service_customer_phone_changes')) {
            Schema::create('service_customer_phone_changes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('service_customers')->cascadeOnDelete();
                $table->string('old_phone', 50)->nullable();
                $table->string('new_phone', 50)->nullable();
                $table->foreignId('changed_by_user_id')->constrained('users');
                $table->timestamp('changed_at');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_customer_phone_changes');
        Schema::table('ticket_satisfactions', function (Blueprint $table) {
            $table->dropColumn('admin_input_reason');
        });
    }
};
