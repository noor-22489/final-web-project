<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'question_text',
    ];

    // Relationship: A Question belongs to a Quiz
    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    // Relationship: A Question has many Options (A, B, C, D) - We build this next
    public function options()
    {
        return $this->hasMany(QuestionOption::class);
    }
}