<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->nullable();
            $table->string('code', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('user_roles')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('ref_companies')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('ref_branches')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('ref_regions')->restrictOnUpdate()->restrictOnDelete();
            $table->string('name', 100)->nullable();
            $table->string('email', 100)->nullable();
            $table->string('nip', 50)->nullable()->unique();
            $table->string('phone', 20)->nullable();
            $table->string('password', 255)->nullable();
            $table->rememberToken()->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->nullable();
            $table->string('code', 100)->nullable();
            $table->string('description', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('user_roles')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('permission_id')->constrained('user_permissions')->restrictOnUpdate()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('user_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onUpdate('cascade')->onDelete('set null');
            $table->string('action');
            $table->string('model_type')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->text('description')->nullable();
            $table->json('data')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });        
    }

    public function down(): void
    {
        $tables = [
            'user_activity_logs',
            'user_role_permissions',
            'user_permissions',
            'password_reset_tokens',
            'users',
            'user_roles',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
