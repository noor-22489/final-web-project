<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Enrollment;
use App\Models\Course;

class EnrollmentController extends Controller
{
    // 1. ENROLL IN A COURSE
    public function store(Request $request, $courseId)
    {
        return response()->json([
            'status' => false,
            'message' => 'Enrollment is managed by Teacher'
        ], 403);
    }

    // 2. MY COURSES
    public function index()
    {
        $user = auth('api')->user();
        if (!$user || $user->role !== 'student') {
            return response()->json(['status' => false, 'message' => 'Students only'], 403);
        }

        $enrollments = Enrollment::with('course.teacher:id,name')
                                 ->where('user_id', $user->id)
                                 ->get();

        return response()->json(['status' => true, 'data' => $enrollments]);
    }
}