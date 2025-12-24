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
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            
            // Link to the Course (If course is deleted, section is deleted)
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            
            $table->string('title');
            $table->integer('sort_order')->default(0); // To arrange sections like 1, 2, 3
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};