<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('satisfaction_otps')) {
            return;
        }

        Schema::create('satisfaction_otps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('link_id')->constrained('satisfaction_links')->cascadeOnDelete();
            $table->string('phone_masked', 32);
            $table->string('code_hash', 64);
            $table->timestamp('sent_at');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('locked_until')->nullable();
            $table->string('session_hash', 64)->nullable();
            $table->timestamp('session_expires_at')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE ticket_satisfactions MODIFY filled_by_user_id BIGINT UNSIGNED NULL');

        Schema::table('ticket_satisfactions', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('signer_position');
        });
    }

    public function down(): void
    {
        Schema::table('ticket_satisfactions', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });
        Schema::dropIfExists('satisfaction_otps');
    }
};
