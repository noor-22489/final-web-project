<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;

class CourseController extends Controller
{
    // 1. CREATE COURSE (Teacher Only)
    public function store(Request $request)
    {
        // Validate the input
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Create the course in the database
        $course = Course::create([
            'teacher_id' => auth()->id(), // Auto-assign the logged-in Teacher
            'title' => $request->title,
            'description' => $request->description,
            'price' => 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Course created successfully!',
            'data' => $course
        ], 201);
    }

    // 2. LIST ALL COURSES (Public)
    public function index()
    {
        // Get courses with the teacher's name included
        $courses = Course::with('teacher:id,name')
            ->withCount('enrollments')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $courses
        ]);
    }

    // 3. LIST MY COURSES (Teacher)
    public function myCourses()
    {
        $teacher = auth('api')->user();
        if (!$teacher || $teacher->role !== 'teacher') {
            return response()->json(['status' => false, 'message' => 'Teachers only'], 403);
        }

        $courses = Course::query()
            ->where('teacher_id', $teacher->id)
            ->withCount('enrollments')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['status' => true, 'data' => $courses]);
    }
}