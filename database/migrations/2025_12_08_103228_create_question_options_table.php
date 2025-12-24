<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            
            // Link to the Question
            $table->foreignId('question_id')->constrained('questions')->onDelete('cascade');
            
            $table->string('option_text'); // e.g. "Paris"
            $table->boolean('is_correct')->default(false); // True if this is the right answer
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_options');
    }
};