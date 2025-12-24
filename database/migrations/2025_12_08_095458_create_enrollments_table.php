<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            
            // Who is enrolling?
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Which course?
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            
            // Prevent duplicate enrollments (User 1 cannot enroll in Course 5 twice)
            $table->unique(['user_id', 'course_id']);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};