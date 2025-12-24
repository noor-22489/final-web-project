<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            
            // Who wrote it?
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // For which course?
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            
            $table->integer('rating'); // 1 to 5
            $table->text('comment')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};