<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_certificates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quiz_id')->constrained('quizzes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('quiz_attempt_id')->constrained('quiz_attempts')->onDelete('cascade');

            $table->unsignedTinyInteger('rank'); // 1,2,3
            $table->dateTime('issued_at');

            $table->timestamps();

            $table->unique(['quiz_id', 'user_id']);
            $table->unique(['quiz_id', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_certificates');
    }
};
