<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'title',
        'description',
        'type',      // 'pdf', 'video', 'text'
        'file_path', // Stores the location (e.g., "materials/abc.pdf")
        'content',   // Stores text
        'view_count',
        'download_count',
    ];

    // Relationship: A Material belongs to a Section
    public function section()
    {
        return $this->belongsTo(Section::class);
    }
}