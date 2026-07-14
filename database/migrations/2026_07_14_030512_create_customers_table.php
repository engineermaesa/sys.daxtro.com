<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('location_link')->nullable();
            $table->float('electricity')->nullable();
            $table->float('building_area')->nullable();
            $table->float('access_road_width')->nullable();
            $table->json('file_cad')->nullable();
            $table->foreignId('leads_id')->unique()->constrained('leads');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
