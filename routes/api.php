<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\QuestionController; // Import
use App\Http\Controllers\StudentCourseController;
use App\Http\Controllers\StudentAssignmentController;
use App\Http\Controllers\TeacherAssignmentController;
use App\Http\Controllers\TeacherAnnouncementController;
use App\Http\Controllers\TeacherEnrollmentController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\AdminRulesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// 1. PUBLIC AUTHENTICATION
Route::group(['prefix' => 'auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// 2. PROTECTED ROUTES (Token Required)
Route::middleware(['auth:api'])->group(function () {
    
    // Auth Utilities
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('me', [AuthController::class, 'me']);
    });

    // TEACHER ROUTES
    Route::prefix('teacher')->group(function () {
        Route::get('/my-courses', [CourseController::class, 'myCourses']);
        Route::post('/courses', [CourseController::class, 'store']);

        // Teacher-managed enrollments
        Route::get('/courses/{id}/enrollments', [TeacherEnrollmentController::class, 'index']);
        Route::post('/courses/{id}/enrollments', [TeacherEnrollmentController::class, 'store']);
        Route::delete('/enrollments/{id}', [TeacherEnrollmentController::class, 'destroy']);
        
        // Sections
        Route::post('/courses/{id}/sections', [SectionController::class, 'store']);
        Route::get('/courses/{id}/sections', [SectionController::class, 'index']);
        
        // Materials
        Route::post('/materials', [MaterialController::class, 'store']);
        Route::get('/sections/{sectionId}/materials', [MaterialController::class, 'indexBySection']);
        Route::put('/materials/{id}', [MaterialController::class, 'update']);
        Route::delete('/materials/{id}', [MaterialController::class, 'destroy']);
        
        // Quizzes
        Route::post('/sections/{id}/quizzes', [QuizController::class, 'store']);
        Route::get('/sections/{id}/quizzes', [QuizController::class, 'indexBySection']);
        Route::get('/quizzes/{id}/preview', [QuizController::class, 'preview']);
        Route::post('/quizzes/{id}/duplicate', [QuizController::class, 'duplicate']);
        Route::get('/quizzes/{id}/results', [QuizController::class, 'results']);
        Route::get('/quizzes/{id}/results.csv', [QuizController::class, 'resultsCsv']);
        Route::post('/quizzes/{id}/finalize', [QuizController::class, 'finalizeResults']);
        Route::put('/quizzes/{id}/deadline', [QuizController::class, 'updateDeadline']);
        Route::delete('/quizzes/{id}', [QuizController::class, 'destroy']);
        
        // Questions Management
        Route::post('/quizzes/{id}/questions', [QuestionController::class, 'store']); // Create
        Route::put('/questions/{id}', [QuestionController::class, 'update']);         // Update
        Route::delete('/questions/{id}', [QuestionController::class, 'destroy']);     // Delete

        // Assignments
        Route::post('/sections/{id}/assignments', [TeacherAssignmentController::class, 'store']);
        Route::get('/sections/{id}/assignments', [TeacherAssignmentController::class, 'indexBySection']);
        Route::delete('/assignments/{id}', [TeacherAssignmentController::class, 'destroy']);
        Route::get('/assignments/{id}/submissions', [TeacherAssignmentController::class, 'submissions']);
        Route::put('/assignment-submissions/{id}/grade', [TeacherAssignmentController::class, 'gradeSubmission']);

        // Announcements
        Route::post('/courses/{id}/announcements', [TeacherAnnouncementController::class, 'store']);
        Route::get('/courses/{id}/announcements', [TeacherAnnouncementController::class, 'index']);
        Route::delete('/announcements/{id}', [TeacherAnnouncementController::class, 'destroy']);
    });

    // STUDENT ROUTES
    Route::prefix('student')->group(function () {
        // Enrollment is admin-managed
        Route::post('/enroll/{id}', [EnrollmentController::class, 'store']);
        Route::get('/my-courses', [EnrollmentController::class, 'index']);
        Route::get('/courses/{id}/overview', [StudentCourseController::class, 'overview']);

        // Quizzes (secure student access)
        Route::get('/quizzes/{id}', [QuizController::class, 'studentShow']);
        Route::post('/quizzes/{id}/submit', [QuizController::class, 'submit']);
        Route::get('/quiz-attempts/{id}', [QuizController::class, 'attemptReview']);

        // Assignments
        Route::post('/assignments/{id}/submit', [StudentAssignmentController::class, 'submit']);
        Route::get('/assignments/{id}/my-submission', [StudentAssignmentController::class, 'mySubmission']);

        Route::get('/certificates', [CertificateController::class, 'myCertificates']);
        Route::get('/certificates/{id}/download', [CertificateController::class, 'download']);
        Route::get('/quizzes/{id}/certificate/download', [CertificateController::class, 'downloadForQuiz']);
        Route::post('/courses/{id}/reviews', [ReviewController::class, 'store']);
        Route::get('/courses/{id}/sections', [SectionController::class, 'index']); 
    });

    // Leaderboard (per-quiz)
    Route::get('/quizzes/{id}/leaderboard', [LeaderboardController::class, 'quiz']);

    // ADMIN ROUTES
    Route::group(['prefix' => 'admin', 'middleware' => ['is_admin']], function () {
        Route::get('/stats', [AdminController::class, 'stats']);

        // Leaderboard/Certificate rules
        Route::get('/rules', [AdminRulesController::class, 'getLeaderboardRules']);
        Route::put('/rules', [AdminRulesController::class, 'updateLeaderboardRules']);

        // Teachers
        Route::get('/teachers', [AdminController::class, 'listTeachers']);
        Route::post('/teachers', [AdminController::class, 'createTeacher']);
        Route::put('/teachers/{id}', [AdminController::class, 'updateTeacher']);
        Route::post('/teachers/{id}/deactivate', [AdminController::class, 'deactivateTeacher']);
        Route::delete('/teachers/{id}', [AdminController::class, 'deleteTeacher']);

        // Students
        Route::get('/students', [AdminController::class, 'listStudents']);
        Route::post('/students', [AdminController::class, 'createStudent']);
        Route::put('/students/{id}', [AdminController::class, 'updateStudent']);
        Route::post('/students/{id}/deactivate', [AdminController::class, 'deactivateStudent']);
        Route::delete('/students/{id}', [AdminController::class, 'deleteStudent']);

        // Courses
        Route::get('/courses', [AdminController::class, 'listCourses']);
        Route::post('/courses', [AdminController::class, 'createCourse']);
        Route::put('/courses/{id}', [AdminController::class, 'updateCourse']);
        Route::post('/courses/{id}/assign-teacher', [AdminController::class, 'assignTeacherToCourse']);
        Route::delete('/courses/{id}', [AdminController::class, 'deleteCourse']);

        // Enrollments (Admin-managed)
        Route::post('/enrollments', [AdminController::class, 'enrollStudent']);
        Route::delete('/enrollments/{id}', [AdminController::class, 'removeEnrollment']);
    });
});

// 3. PUBLIC ROUTES
Route::get('/courses', [CourseController::class, 'index']);
Route::get('/quizzes/{id}', [QuizController::class, 'show']);
Route::get('/leaderboard', [LeaderboardController::class, 'index']);
Route::get('/courses/{id}/reviews', [ReviewController::class, 'index']);