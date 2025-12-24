<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Enrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
    ];

    // Relationship: An enrollment belongs to a User (Student)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relationship: An enrollment belongs to a Course
    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}