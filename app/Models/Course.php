<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'title',
        'code',
        'credit_hours',
        'semester',
        'description',
        'price',
        'thumbnail',
    ];

    // Relationship: A Course belongs to a Teacher (User)
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }
}