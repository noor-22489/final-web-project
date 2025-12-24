<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Course;
use Illuminate\Http\Request;

class TeacherAnnouncementController extends Controller
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
        [$user, $err] = $this->requireTeacher();
        if ($err) {
            return [null, null, $err];
        }

        $course = Course::find($courseId);
        if (!$course) {
            return [$user, null, response()->json(['status' => false, 'message' => 'Course not found'], 404)];
        }

        if ((int)$course->teacher_id !== (int)$user->id) {
            return [$user, $course, response()->json(['status' => false, 'message' => 'Unauthorized'], 403)];
        }

        return [$user, $course, null];
    }

    public function index($courseId)
    {
        [$user, $course, $err] = $this->teacherOwnsCourseOrFail((int)$courseId);
        if ($err) {
            return $err;
        }

        $announcements = Announcement::with('creator:id,name')
            ->where('course_id', $course->id)
            ->orderByDesc('id')
            ->get();

        return response()->json(['status' => true, 'data' => $announcements]);
    }

    public function store(Request $request, $courseId)
    {
        [$user, $course, $err] = $this->teacherOwnsCourseOrFail((int)$courseId);
        if ($err) {
            return $err;
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $a = Announcement::create([
            'course_id' => $course->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'created_by' => $user->id,
        ]);

        return response()->json(['status' => true, 'message' => 'Announcement posted', 'data' => $a->load('creator:id,name')], 201);
    }

    public function destroy($id)
    {
        [$user, $err] = $this->requireTeacher();
        if ($err) {
            return $err;
        }

        $a = Announcement::with('course')->find($id);
        if (!$a || !$a->course) {
            return response()->json(['status' => false, 'message' => 'Announcement not found'], 404);
        }

        if ((int)$a->course->teacher_id !== (int)$user->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $a->delete();
        return response()->json(['status' => true, 'message' => 'Announcement deleted']);
    }
}
