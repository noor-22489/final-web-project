<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Section;
use Illuminate\Http\Request;

class TeacherAssignmentController extends Controller
{
    private function requireTeacher()
    {
        $user = auth('api')->user();
        if (!$user || $user->role !== 'teacher') {
            return [null, response()->json(['status' => false, 'message' => 'Teachers only'], 403)];
        }
        return [$user, null];
    }

    private function teacherOwnsSectionOrFail(int $sectionId)
    {
        [$user, $err] = $this->requireTeacher();
        if ($err) {
            return [null, null, $err];
        }

        $section = Section::with('course')->find($sectionId);
        if (!$section) {
            return [$user, null, response()->json(['status' => false, 'message' => 'Section not found'], 404)];
        }

        if (!$section->course || (int)$section->course->teacher_id !== (int)$user->id) {
            return [$user, $section, response()->json(['status' => false, 'message' => 'Unauthorized'], 403)];
        }

        return [$user, $section, null];
    }

    private function teacherOwnsAssignmentOrFail(int $assignmentId)
    {
        [$user, $err] = $this->requireTeacher();
        if ($err) {
            return [null, null, $err];
        }

        $assignment = Assignment::with('section.course')->find($assignmentId);
        if (!$assignment || !$assignment->section) {
            return [$user, null, response()->json(['status' => false, 'message' => 'Assignment not found'], 404)];
        }

        if (!$assignment->section->course || (int)$assignment->section->course->teacher_id !== (int)$user->id) {
            return [$user, $assignment, response()->json(['status' => false, 'message' => 'Unauthorized'], 403)];
        }

        return [$user, $assignment, null];
    }

    public function indexBySection($sectionId)
    {
        [$user, $section, $err] = $this->teacherOwnsSectionOrFail((int)$sectionId);
        if ($err) {
            return $err;
        }

        $assignments = Assignment::withCount('submissions')
            ->where('section_id', $section->id)
            ->orderByDesc('id')
            ->get();

        return response()->json(['status' => true, 'data' => $assignments]);
    }

    public function store(Request $request, $sectionId)
    {
        [$user, $section, $err] = $this->teacherOwnsSectionOrFail((int)$sectionId);
        if ($err) {
            return $err;
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_at' => ['nullable', 'date'],
            'max_marks' => ['nullable', 'integer', 'min:0'],
        ]);

        $assignment = Assignment::create([
            'section_id' => $section->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_at' => $data['due_at'] ?? null,
            'max_marks' => $data['max_marks'] ?? null,
            'created_by' => $user->id,
        ]);

        return response()->json(['status' => true, 'message' => 'Assignment created', 'data' => $assignment], 201);
    }

    public function destroy($id)
    {
        [$user, $assignment, $err] = $this->teacherOwnsAssignmentOrFail((int)$id);
        if ($err) {
            return $err;
        }

        $assignment->delete();
        return response()->json(['status' => true, 'message' => 'Assignment deleted']);
    }

    public function submissions($id)
    {
        [$user, $assignment, $err] = $this->teacherOwnsAssignmentOrFail((int)$id);
        if ($err) {
            return $err;
        }

        $submissions = AssignmentSubmission::with('user:id,name,email')
            ->where('assignment_id', $assignment->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'assignment' => $assignment,
                'submissions' => $submissions,
            ]
        ]);
    }

    public function gradeSubmission(Request $request, $id)
    {
        [$user, $err] = $this->requireTeacher();
        if ($err) {
            return $err;
        }

        $submission = AssignmentSubmission::with('assignment.section.course')->find($id);
        if (!$submission || !$submission->assignment || !$submission->assignment->section || !$submission->assignment->section->course) {
            return response()->json(['status' => false, 'message' => 'Submission not found'], 404);
        }

        if ((int)$submission->assignment->section->course->teacher_id !== (int)$user->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'marks' => ['nullable', 'numeric', 'min:0'],
            'feedback' => ['nullable', 'string'],
        ]);

        $submission->marks = array_key_exists('marks', $data) ? $data['marks'] : $submission->marks;
        $submission->feedback = array_key_exists('feedback', $data) ? $data['feedback'] : $submission->feedback;
        $submission->save();

        return response()->json(['status' => true, 'message' => 'Graded', 'data' => $submission]);
    }
}
