<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_technicians', function (Blueprint $table) {
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position');
            $table->timestamps();
            $table->primary(['ticket_id', 'user_id']);
            $table->index(['user_id', 'ticket_id']);
        });

        DB::table('ticket_technicians')->insertUsing(
            ['ticket_id', 'user_id', 'position', 'created_at', 'updated_at'],
            DB::table('tickets')->whereNotNull('assigned_technician_id')
                ->selectRaw('id, assigned_technician_id, 0, created_at, updated_at')
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_technicians');
    }
};
