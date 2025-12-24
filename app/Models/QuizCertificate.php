<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'user_id',
        'quiz_attempt_id',
        'rank',
        'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'rank' => 'integer',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attempt()
    {
        return $this->belongsTo(QuizAttempt::class, 'quiz_attempt_id');
    }
}
