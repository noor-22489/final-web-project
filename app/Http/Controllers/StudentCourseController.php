<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\AssignmentSubmission;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;

class StudentCourseController extends Controller
{
    private function requireStudent()
    {
        $user = auth('api')->user();
        if (!$user || $user->role !== 'student') {
            return [null, response()->json(['status' => false, 'message' => 'Students only'], 403)];
        }
        return [$user, null];
    }

    private function requireEnrollmentOrFail(int $courseId)
    {
        [$user, $err] = $this->requireStudent();
        if ($err) {
            return [null, null, $err];
        }

        $exists = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->exists();

        if (!$exists) {
            return [$user, null, response()->json(['status' => false, 'message' => 'You are not enrolled in this course'], 403)];
        }

        $course = Course::with([
            'teacher:id,name,email',
            'announcements.creator:id,name',
            'sections' => function ($q) {
                $q->orderBy('sort_order')->orderBy('id');
            },
            'sections.materials' => function ($q) {
                $q->orderByDesc('id');
            },
            'sections.quizzes' => function ($q) {
                $q->orderByDesc('id');
            },
            'sections.assignments' => function ($q) {
                $q->orderByDesc('id');
            },
        ])->find($courseId);

        if (!$course) {
            return [$user, null, response()->json(['status' => false, 'message' => 'Course not found'], 404)];
        }

        return [$user, $course, null];
    }

    // Student course overview: teacher, credits, sections => materials/quizzes/assignments, announcements
    public function overview($courseId)
    {
        [$user, $course, $err] = $this->requireEnrollmentOrFail((int)$courseId);
        if ($err) {
            return $err;
        }

        // Attach my submission status to assignments (avoid leaking other students)
        $assignmentIds = collect($course->sections)
            ->flatMap(fn($s) => $s->assignments)
            ->pluck('id')
            ->values();

        $mySubmissions = $assignmentIds->isEmpty()
            ? collect()
            : AssignmentSubmission::where('user_id', $user->id)
                ->whereIn('assignment_id', $assignmentIds)
                ->get()
                ->keyBy('assignment_id');

        foreach ($course->sections as $section) {
            foreach ($section->assignments as $assignment) {
                $assignment->setAttribute('my_submission', $mySubmissions->get($assignment->id));
            }
        }

        // Quiz attempts summary
        $quizIds = collect($course->sections)
            ->flatMap(fn($s) => $s->quizzes)
            ->pluck('id')
            ->values();

        $attempts = $quizIds->isEmpty()
            ? collect()
            : QuizAttempt::where('user_id', $user->id)
                ->whereIn('quiz_id', $quizIds)
                ->orderByDesc('id')
                ->get();

        $attemptsByQuiz = $attempts->groupBy('quiz_id');
        foreach ($course->sections as $section) {
            foreach ($section->quizzes as $quiz) {
                $quizAttempts = $attemptsByQuiz->get($quiz->id, collect());
                $quiz->setAttribute('my_attempts_count', $quizAttempts->count());
                $quiz->setAttribute('my_last_attempt', $quizAttempts->first());
            }
        }

        return response()->json([
            'status' => true,
            'data' => [
                'course' => $course,
            ]
        ]);
    }
}
