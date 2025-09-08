<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('lead_meetings', function (Blueprint $table) {
            $table->foreignId('meeting_type_id')->nullable()->after('lead_id')->constrained('meeting_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lead_meetings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('meeting_type_id');
        });
    }
};
