<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->nullable();
        });

        Schema::create('lead_segments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->nullable();
        });

        Schema::create('lead_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->nullable();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained('lead_sources')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('segment_id')->constrained('lead_segments')->restrictOnUpdate()->restrictOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('ref_regions')->restrictOnUpdate()->restrictOnDelete();
            $table->string('province', 100)->nullable();
            $table->foreignId('status_id')->constrained('lead_statuses')->restrictOnUpdate()->restrictOnDelete();
            $table->string('name', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->foreignId('product_id')->nullable()->constrained('ref_products')->restrictOnUpdate()->restrictOnDelete();
            $table->string('needs')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->restrictOnUpdate()->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users')->restrictOnUpdate()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lead_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->restrictOnUpdate()->restrictOnDelete();            
            $table->foreignId('status_id')->constrained('lead_statuses')->restrictOnUpdate()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lead_meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->restrictOnUpdate()->restrictOnDelete();            
            $table->boolean('is_online')->nullable();
            $table->string('online_url')->nullable();
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->string('city', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->enum('result', ['yes', 'no', 'expired'])->nullable();
            $table->text('summary')->nullable();
            $table->foreignId('attachment_id')->nullable()->constrained('attachments')->restrictOnUpdate()->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lead_meeting_reschedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('lead_meetings')->restrictOnUpdate()->restrictOnDelete();                        
            $table->timestamp('old_scheduled_start_at')->nullable();
            $table->timestamp('old_scheduled_end_at')->nullable();
            $table->string('old_online_url')->nullable();
            $table->string('new_online_url')->nullable();
            $table->timestamp('new_scheduled_start_at')->nullable();
            $table->timestamp('new_scheduled_end_at')->nullable();
            $table->string('old_location', 255)->nullable();
            $table->string('new_location', 255)->nullable();
            $table->string('reason', 255)->nullable();
            $table->foreignId('rescheduled_by')->constrained('users')->restrictOnUpdate()->restrictOnDelete();                        
            $table->timestamp('rescheduled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lead_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->restrictOnUpdate()->restrictOnDelete();                        
            $table->foreignId('sales_id')->constrained('users')->restrictOnUpdate()->restrictOnDelete();                                    
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        $tables = [
            'lead_claims',
            'lead_meeting_reschedules',
            'lead_meetings',
            'lead_status_logs',
            'lead_notes',
            'leads',
            'lead_statuses',
            'lead_segments',
            'lead_sources',            
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
