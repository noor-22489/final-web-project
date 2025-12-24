<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'title',
        'duration_minutes',
        'deadline_at',
        'negative_mark_per_wrong',
        'max_attempts',
        'results_finalized_at',
        'results_finalized_by',
    ];

    protected $casts = [
        'deadline_at' => 'datetime',
        'negative_mark_per_wrong' => 'decimal:2',
        'duration_minutes' => 'integer',
        'max_attempts' => 'integer',
        'results_finalized_at' => 'datetime',
        'results_finalized_by' => 'integer',
    ];

    // Relationship: A Quiz belongs to a Section
    public function section()
    {
        return $this->belongsTo(Section::class);
    }
    
    // Relationship: A Quiz has many Questions (We will build this next)
    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function certificates()
    {
        return $this->hasMany(QuizCertificate::class);
    }
}