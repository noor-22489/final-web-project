<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    // 1. ADD A REVIEW (Student)
    public function store(Request $request, $courseId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        $course = Course::find($courseId);
        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        // Check if already reviewed
        $exists = Review::where('user_id', Auth::id())
                        ->where('course_id', $courseId)
                        ->exists();

        if ($exists) {
            return response()->json(['message' => 'You have already reviewed this course.'], 409);
        }

        $review = Review::create([
            'user_id' => Auth::id(),
            'course_id' => $courseId,
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Review added successfully!',
            'data' => $review
        ], 201);
    }

    // 2. GET REVIEWS FOR A COURSE (Public)
    public function index($courseId)
    {
        $reviews = Review::where('course_id', $courseId)
                         ->with('user:id,name') // Show who wrote it
                         ->orderByDesc('created_at')
                         ->get();

        return response()->json([
            'status' => true,
            'data' => $reviews
        ]);
    }
}