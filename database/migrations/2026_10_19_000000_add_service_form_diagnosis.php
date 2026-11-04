<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_service_forms', function (Blueprint $t) {
            $t->boolean('legacy_without_diagnosis')->default(false);
        });
        DB::table('ticket_service_forms')->where('status', 'saved')->update(['legacy_without_diagnosis' => true]);
        Schema::create('ticket_diagnosis_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ticket_service_form_id')->constrained('ticket_service_forms');
            $t->string('field', 16);
            $t->unsignedInteger('visit_no');
            $t->date('entry_date');
            $t->time('visit_time');
            $t->string('name')->nullable();
            $t->longText('body')->nullable();
            $t->longText('visit_notes')->nullable();
            $t->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('locked_at')->nullable();
            $t->timestamps();
            $t->unique(['ticket_service_form_id', 'field', 'visit_no'], 'diagnosis_form_field_visit');
        });
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE ticket_photos MODIFY category ENUM('problem','analysis','repair','inspection','diagnosis') NOT NULL");
        }
        Schema::table('ticket_photos', function (Blueprint $t) {
            $t->foreignId('ticket_service_form_id')->nullable()->constrained('ticket_service_forms');
            $t->string('diagnosis_field', 16)->nullable();
            $t->string('caption', 500)->nullable();
            $t->timestamp('locked_at')->nullable();
            $t->index(['ticket_service_form_id', 'diagnosis_field'], 'diagnosis_photo_form_field');
        });
    }

    public function down(): void
    {
        if (DB::table('ticket_diagnosis_entries')->exists()) {
            throw new RuntimeException('Diagnosis history exists; rollback would discard service records.');
        }
        Schema::table('ticket_photos', function (Blueprint $t) {
            $t->dropIndex('diagnosis_photo_form_field');
            $t->dropConstrainedForeignId('ticket_service_form_id');
            $t->dropColumn(['diagnosis_field', 'caption', 'locked_at']);
        });
        Schema::dropIfExists('ticket_diagnosis_entries');
        Schema::table('ticket_service_forms', fn (Blueprint $t) => $t->dropColumn('legacy_without_diagnosis'));
    }
};
