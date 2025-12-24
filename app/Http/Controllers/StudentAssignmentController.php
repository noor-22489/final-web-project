<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentAssignmentController extends Controller
{
    private function requireStudent()
    {
        $user = auth('api')->user();
        if (!$user || $user->role !== 'student') {
            return [null, response()->json(['status' => false, 'message' => 'Students only'], 403)];
        }
        return [$user, null];
    }

    private function requireEnrollmentForAssignmentOrFail(int $assignmentId)
    {
        [$user, $err] = $this->requireStudent();
        if ($err) {
            return [null, null, $err];
        }

        $assignment = Assignment::with('section.course')->find($assignmentId);
        if (!$assignment || !$assignment->section || !$assignment->section->course) {
            return [$user, null, response()->json(['status' => false, 'message' => 'Assignment not found'], 404)];
        }

        $courseId = (int)$assignment->section->course->id;
        $enrolled = Enrollment::where('user_id', $user->id)->where('course_id', $courseId)->exists();
        if (!$enrolled) {
            return [$user, $assignment, response()->json(['status' => false, 'message' => 'You are not enrolled in this course'], 403)];
        }

        return [$user, $assignment, null];
    }

    public function mySubmission($id)
    {
        [$user, $assignment, $err] = $this->requireEnrollmentForAssignmentOrFail((int)$id);
        if ($err) {
            return $err;
        }

        $submission = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first();

        return response()->json(['status' => true, 'data' => $submission]);
    }

    public function submit(Request $request, $id)
    {
        [$user, $assignment, $err] = $this->requireEnrollmentForAssignmentOrFail((int)$id);
        if ($err) {
            return $err;
        }

        $request->validate([
            'file' => 'required|file|max:20480', // 20MB
        ]);

        // 1. Determine if this is a first-time submission or an update
        $existingSubmission = AssignmentSubmission::where('assignment_id', $assignment->id)
            ->where('user_id', $user->id)
            ->first();

        // 2. Handle file upload
        $file = $request->file('file');
        $filename = time() . '_' . $user->id . '_' . $file->getClientOriginalName();
        $path = $file->storeAs('assignments', $filename, 'public');
        $publicPath = '/storage/' . $path;

        // 3. Late submission logic
        $isLate = false;
        $lateMessage = "";
        if ($assignment->due_at && now()->greaterThan($assignment->due_at)) {
            $isLate = true;
            $lateMessage = " Warning: Your assignment is being uploaded after the due date.";
        }

        // 4. Update or Create logic (Allows changing existing submission)
        $submission = AssignmentSubmission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'user_id' => $user->id],
            [
                'file_path' => $publicPath,
                'submitted_at' => now(),
                'is_late' => $isLate,
            ]
        );

        // 5. Build dynamic message
        $statusMsg = $existingSubmission ? "Assignment updated successfully." : "Assignment submitted successfully.";
        
        return response()->json([
            'status' => true,
            'message' => $statusMsg . $lateMessage,
            'is_late' => $isLate,
            'is_update' => $existingSubmission ? true : false,
            'data' => $submission,
        ], 201);
    }
}
