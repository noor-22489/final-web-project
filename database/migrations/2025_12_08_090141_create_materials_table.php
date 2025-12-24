<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            
            // Link to the Section
            $table->foreignId('section_id')->constrained('sections')->onDelete('cascade');
            
            $table->string('title');
            $table->enum('type', ['pdf', 'video', 'text']); // What kind is it?
            
            // "nullable" because 'text' type doesn't need a file
            $table->string('file_path')->nullable(); 
            
            // "nullable" because 'video' type doesn't need text content
            $table->text('content')->nullable(); 
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materials');
    }
};