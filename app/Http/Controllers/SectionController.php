<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Section;
use App\Models\Course;

class SectionController extends Controller
{
    // 1. CREATE SECTION (Teacher)
    public function store(Request $request, $courseId)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $course = Course::find($courseId);

        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        // Check ownership (Optional: Ensure teacher owns course)
        if ($course->teacher_id !== auth()->id()) {
             return response()->json(['message' => 'Unauthorized'], 403);
        }

        $section = Section::create([
            'course_id' => $courseId,
            'title' => $request->title,
            'sort_order' => $request->sort_order ?? 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Section created successfully!',
            'data' => $section
        ], 201);
    }

    // 2. GET SECTIONS LIST (Teacher/Student/Public) <--- NEW FUNCTION
    public function index($courseId)
    {
        // Check if course exists
        $course = Course::find($courseId);
        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        // Get sections
        $sections = Section::where('course_id', $courseId)->get();

        return response()->json([
            'status' => true,
            'message' => 'Sections retrieved successfully',
            'data' => $sections
        ]);
    }
}