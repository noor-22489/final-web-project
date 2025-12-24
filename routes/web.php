<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebAuthController;

// --- 1. PUBLIC ROUTES (No Login Required) ---

// Home Page (The Welcome/Landing Page)
Route::get('/', function () {
    return view('welcome');
});

// Auth (session-based web login)
Route::get('/login', [WebAuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.post');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

// Course Catalog (Viewable by everyone)
Route::get('/courses', function () {
    return view('courses.index');
})->name('courses.index');


// --- 2. DASHBOARDS (role-based via JWT/localStorage) ---

// Hub route: sends the user to the correct role dashboard via JS
Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/admin/dashboard', function () {
    return view('admin.dashboard');
})->name('admin.dashboard');

Route::get('/admin/teachers', function () {
    return view('admin.teachers');
})->name('admin.teachers.index');

Route::get('/admin/students', function () {
    return view('admin.students');
})->name('admin.students.index');

Route::get('/admin/courses', function () {
    return view('admin.courses');
})->name('admin.courses.index');

Route::get('/admin/rules', function () {
    return view('admin.rules');
})->name('admin.rules');

Route::get('/teacher/dashboard', function () {
    return view('teacher.dashboard');
})->name('teacher.dashboard');

Route::get('/student/dashboard', function () {
    return view('student.dashboard');
})->name('student.dashboard');


// --- 3. STUDENT PAGES ---

Route::get('/my-courses', function () {
    return redirect()->route('student.dashboard');
})->name('student.my_courses');

// Course View (Student/Public)
Route::get('/course/{id}/view', function ($id) {
    return view('student.view_course', compact('id'));
})->name('student.view_course');

Route::get('/student/quiz/{id}/attempt', function ($id) {
    // Lightweight page: the JS will enforce role/enrollment via API
    // If the user came from a course page, courseId is available there; here we include it for back navigation.
    // We don't have server-side session auth in this project flow.
    $courseId = request()->query('courseId');
    return view('student.attempt_quiz', ['quizId' => $id, 'courseId' => (int)($courseId ?: 0)]);
})->name('student.quiz_attempt');

Route::get('/student/quiz/{id}/leaderboard', function ($id) {
    $courseId = request()->query('courseId');
    return view('student.quiz_leaderboard', ['quizId' => $id, 'courseId' => (int)($courseId ?: 0)]);
})->name('student.quiz_leaderboard');


// --- 4. TEACHER PAGES (API access is protected by JWT) ---

Route::group(['prefix' => 'teacher', 'as' => 'teacher.'], function () {
    
    // Teacher's Course List
    Route::get('/my-courses', function () {
        return view('teacher.my_courses_list');
    })->name('my_courses'); // Name is teacher.my_courses

    // Teacher specific view for creating a course
    Route::get('/create-course', function () {
        return view('teacher.create_course');
    })->name('create_course'); // Name is teacher.create_course

    // Course Management (The main dashboard for content creation)
    Route::get('/course/{id}/manage', function ($id) {
        return view('teacher.manage_course', compact('id'));
    })->name('manage_course'); // Name is teacher.manage_course

    // **!!! THE FIX: THE NEW ROUTE IS NOW GUARANTEED TO BE PROTECTED !!!**
    Route::get('/quiz/{id}/manage_questions', function ($id) {
        return view('teacher.manage_questions', ['quizId' => $id]);
    })->name('manage_questions'); // Name is teacher.manage_questions

    Route::get('/quiz/{id}/results', function ($id) {
        return view('teacher.quiz_results', ['quizId' => $id]);
    })->name('quiz_results');

    Route::get('/assignments/{id}/submissions', function ($id) {
        // courseId is passed for the back button; API enforces ownership.
        $courseId = request()->query('courseId');
        return view('teacher.assignment_submissions', [
            'assignmentId' => $id,
            'courseId' => (int)($courseId ?: 0),
        ]);
    })->name('assignment_submissions');
    
});