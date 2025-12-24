<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // 1. DASHBOARD STATS
    public function stats()
    {
        return response()->json([
            'status' => true,
            'data' => [
                'total_students' => User::where('role', 'student')->count(),
                'total_teachers' => User::where('role', 'teacher')->count(),
                'total_courses' => Course::count(),
                'total_quiz_attempts' => QuizAttempt::count(),
            ]
        ]);
    }

    // 2. DELETE COURSE (Moderation)
    public function deleteCourse($id)
    {
        $course = Course::find($id);

        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $course->delete();

        return response()->json([
            'status' => true,
            'message' => 'Course deleted by Admin.'
        ]);
    }

    // 3. TEACHERS
    public function listTeachers()
    {
        $teachers = User::query()
            ->where('role', 'teacher')
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'email', 'role', 'is_active', 'qualification', 'department', 'created_at']);

        return response()->json(['status' => true, 'data' => $teachers]);
    }

    public function createTeacher(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
        ]);

        $teacher = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'teacher',
            'is_active' => true,
            'qualification' => $data['qualification'] ?? null,
            'department' => $data['department'] ?? null,
        ]);

        return response()->json(['status' => true, 'message' => 'Teacher created', 'data' => $teacher], 201);
    }

    public function updateTeacher(Request $request, $id)
    {
        $teacher = User::where('role', 'teacher')->find($id);
        if (!$teacher) {
            return response()->json(['status' => false, 'message' => 'Teacher not found'], 404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $teacher->id],
            'password' => ['nullable', 'string', 'min:6'],
            'qualification' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('password', $data) && $data['password']) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $teacher->update($data);

        return response()->json(['status' => true, 'message' => 'Teacher updated', 'data' => $teacher]);
    }

    public function deactivateTeacher($id)
    {
        $teacher = User::where('role', 'teacher')->find($id);
        if (!$teacher) {
            return response()->json(['status' => false, 'message' => 'Teacher not found'], 404);
        }

        $teacher->is_active = false;
        $teacher->save();

        return response()->json(['status' => true, 'message' => 'Teacher deactivated', 'data' => $teacher]);
    }

    public function deleteTeacher($id)
    {
        $teacher = User::where('role', 'teacher')->find($id);
        if (!$teacher) {
            return response()->json(['status' => false, 'message' => 'Teacher not found'], 404);
        }

        $teacher->delete();
        return response()->json(['status' => true, 'message' => 'Teacher deleted']);
    }

    // 4. STUDENTS
    public function listStudents()
    {
        $students = User::query()
            ->where('role', 'student')
            ->orderBy('id', 'desc')
            ->get(['id', 'name', 'email', 'role', 'is_active', 'roll_number', 'program', 'batch', 'created_at']);

        return response()->json(['status' => true, 'data' => $students]);
    }

    public function createStudent(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'roll_number' => ['nullable', 'string', 'max:255'],
            'program' => ['nullable', 'string', 'max:255'],
            'batch' => ['nullable', 'string', 'max:255'],
        ]);

        $student = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'student',
            'is_active' => true,
            'roll_number' => $data['roll_number'] ?? null,
            'program' => $data['program'] ?? null,
            'batch' => $data['batch'] ?? null,
        ]);

        return response()->json(['status' => true, 'message' => 'Student created', 'data' => $student], 201);
    }

    public function updateStudent(Request $request, $id)
    {
        $student = User::where('role', 'student')->find($id);
        if (!$student) {
            return response()->json(['status' => false, 'message' => 'Student not found'], 404);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $student->id],
            'password' => ['nullable', 'string', 'min:6'],
            'roll_number' => ['nullable', 'string', 'max:255'],
            'program' => ['nullable', 'string', 'max:255'],
            'batch' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('password', $data) && $data['password']) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $student->update($data);

        return response()->json(['status' => true, 'message' => 'Student updated', 'data' => $student]);
    }

    public function deactivateStudent($id)
    {
        $student = User::where('role', 'student')->find($id);
        if (!$student) {
            return response()->json(['status' => false, 'message' => 'Student not found'], 404);
        }

        $student->is_active = false;
        $student->save();

        return response()->json(['status' => true, 'message' => 'Student blocked', 'data' => $student]);
    }

    public function deleteStudent($id)
    {
        $student = User::where('role', 'student')->find($id);
        if (!$student) {
            return response()->json(['status' => false, 'message' => 'Student not found'], 404);
        }

        $student->delete();
        return response()->json(['status' => true, 'message' => 'Student deleted']);
    }

    // 5. COURSES
    public function listCourses()
    {
        $courses = Course::with(['teacher:id,name,email'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json(['status' => true, 'data' => $courses]);
    }

    public function createCourse(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'credit_hours' => ['nullable', 'integer', 'min:0', 'max:30'],
            'semester' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'string', 'max:255'],
            'teacher_id' => ['nullable', 'exists:users,id'],
        ]);

        // If teacher_id provided, ensure it's a teacher
        if (!empty($data['teacher_id'])) {
            $teacher = User::where('role', 'teacher')->find($data['teacher_id']);
            if (!$teacher) {
                return response()->json(['status' => false, 'message' => 'Selected teacher not found'], 422);
            }
        } else {
            // courses migration originally required teacher_id, so require it if still non-nullable
            if (!\Schema::hasColumn('courses', 'teacher_id')) {
                return response()->json(['status' => false, 'message' => 'teacher_id is required'], 422);
            }
        }

        // If teacher_id is missing but DB requires it, we'll set it to the first teacher to avoid DB error.
        if (empty($data['teacher_id'])) {
            $fallbackTeacherId = User::where('role', 'teacher')->value('id');
            if (!$fallbackTeacherId) {
                return response()->json(['status' => false, 'message' => 'Create a teacher first before creating a course'], 422);
            }
            $data['teacher_id'] = $fallbackTeacherId;
        }

        $course = Course::create($data);

        return response()->json(['status' => true, 'message' => 'Course created', 'data' => $course], 201);
    }

    public function updateCourse(Request $request, $id)
    {
        $course = Course::find($id);
        if (!$course) {
            return response()->json(['status' => false, 'message' => 'Course not found'], 404);
        }

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'credit_hours' => ['nullable', 'integer', 'min:0', 'max:30'],
            'semester' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'string', 'max:255'],
            'teacher_id' => ['nullable', 'exists:users,id'],
        ]);

        if (array_key_exists('teacher_id', $data) && !empty($data['teacher_id'])) {
            $teacher = User::where('role', 'teacher')->find($data['teacher_id']);
            if (!$teacher) {
                return response()->json(['status' => false, 'message' => 'Selected teacher not found'], 422);
            }
        }

        $course->update($data);

        return response()->json(['status' => true, 'message' => 'Course updated', 'data' => $course]);
    }

    public function assignTeacherToCourse(Request $request, $id)
    {
        $course = Course::find($id);
        if (!$course) {
            return response()->json(['status' => false, 'message' => 'Course not found'], 404);
        }

        $data = $request->validate([
            'teacher_id' => ['required', 'exists:users,id'],
        ]);

        $teacher = User::where('role', 'teacher')->find($data['teacher_id']);
        if (!$teacher) {
            return response()->json(['status' => false, 'message' => 'Selected teacher not found'], 422);
        }

        $course->teacher_id = $teacher->id;
        $course->save();

        return response()->json(['status' => true, 'message' => 'Teacher assigned', 'data' => $course]);
    }

    // 6. ENROLLMENTS (Admin-managed)
    public function enrollStudent(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'exists:users,id'],
            'course_id' => ['required', 'exists:courses,id'],
        ]);

        $student = User::where('role', 'student')->find($data['student_id']);
        if (!$student) {
            return response()->json(['status' => false, 'message' => 'Selected user is not a student'], 422);
        }

        $exists = Enrollment::where('user_id', $student->id)
            ->where('course_id', $data['course_id'])
            ->exists();

        if ($exists) {
            return response()->json(['status' => true, 'message' => 'Student already enrolled'], 200);
        }

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $data['course_id'],
        ]);

        return response()->json(['status' => true, 'message' => 'Student enrolled', 'data' => $enrollment], 201);
    }

    public function removeEnrollment($id)
    {
        $enrollment = Enrollment::find($id);
        if (!$enrollment) {
            return response()->json(['status' => false, 'message' => 'Enrollment not found'], 404);
        }

        $enrollment->delete();
        return response()->json(['status' => true, 'message' => 'Enrollment removed']);
    }
}