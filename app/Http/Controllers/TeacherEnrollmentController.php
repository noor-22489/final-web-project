<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;

class TeacherEnrollmentController extends Controller
{
    private function requireTeacher()
    {
        $user = auth('api')->user();
        if (!$user || $user->role !== 'teacher') {
            return [null, response()->json(['status' => false, 'message' => 'Teachers only'], 403)];
        }
        return [$user, null];
    }

    private function teacherOwnsCourseOrFail(int $courseId)
    {
        [$teacher, $err] = $this->requireTeacher();
        if ($err) {
            return [null, null, $err];
        }

        $course = Course::find($courseId);
        if (!$course) {
            return [$teacher, null, response()->json(['status' => false, 'message' => 'Course not found'], 404)];
        }

        if ((int)$course->teacher_id !== (int)$teacher->id) {
            return [$teacher, $course, response()->json(['status' => false, 'message' => 'Unauthorized'], 403)];
        }

        return [$teacher, $course, null];
    }

    public function index($courseId)
    {
        [$teacher, $course, $err] = $this->teacherOwnsCourseOrFail((int)$courseId);
        if ($err) {
            return $err;
        }

        $enrollments = Enrollment::with('user:id,name,email')
            ->where('course_id', $course->id)
            ->orderByDesc('id')
            ->get();

        return response()->json(['status' => true, 'data' => $enrollments]);
    }

    public function store(Request $request, $courseId)
    {
        [$teacher, $course, $err] = $this->teacherOwnsCourseOrFail((int)$courseId);
        if ($err) {
            return $err;
        }

        $data = $request->validate([
            'student_id' => ['nullable', 'integer', 'exists:users,id'],
            'student_email' => ['nullable', 'email'],
        ]);

        if (empty($data['student_id']) && empty($data['student_email'])) {
            return response()->json(['status' => false, 'message' => 'Provide student_id or student_email'], 422);
        }

        $student = null;
        if (!empty($data['student_id'])) {
            $student = User::find($data['student_id']);
        } else {
            $student = User::where('email', $data['student_email'])->first();
        }

        if (!$student || $student->role !== 'student') {
            return response()->json(['status' => false, 'message' => 'Student not found'], 404);
        }

        $exists = Enrollment::where('user_id', $student->id)
            ->where('course_id', $course->id)
            ->exists();

        if ($exists) {
            return response()->json(['status' => true, 'message' => 'Student already enrolled'], 200);
        }

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Student enrolled',
            'data' => $enrollment->load('user:id,name,email'),
        ], 201);
    }

    public function destroy($id)
    {
        [$teacher, $err] = $this->requireTeacher();
        if ($err) {
            return $err;
        }

        $enrollment = Enrollment::with('course')->find((int)$id);
        if (!$enrollment || !$enrollment->course) {
            return response()->json(['status' => false, 'message' => 'Enrollment not found'], 404);
        }

        if ((int)$enrollment->course->teacher_id !== (int)$teacher->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $enrollment->delete();
        return response()->json(['status' => true, 'message' => 'Enrollment removed']);
    }
}
