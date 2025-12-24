<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            
            // Who took it?
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Which quiz?
            $table->foreignId('quiz_id')->constrained('quizzes')->onDelete('cascade');
            
            // The Result
            $table->integer('score'); // e.g., 8 (correct answers)
            $table->integer('total_questions'); // e.g., 10 (total questions)
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
    }
};