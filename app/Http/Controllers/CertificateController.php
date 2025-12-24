<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuizAttempt;
use App\Models\Quiz;
use App\Models\QuizCertificate;
use Barryvdh\DomPDF\Facade\Pdf; // The library we just installed

class CertificateController extends Controller
{
    public function myCertificates()
    {
        $user = auth('api')->user();
        if (!$user || $user->role !== 'student') {
            return response()->json(['message' => 'Students only'], 403);
        }

        $certs = QuizCertificate::with(['quiz:id,title'])
            ->where('user_id', (int)$user->id)
            ->orderByDesc('issued_at')
            ->get()
            ->map(function ($cert) {
                return [
                    'id' => (int)$cert->id,
                    'quiz_id' => (int)$cert->quiz_id,
                    'quiz_title' => $cert->quiz?->title,
                    'rank' => (int)$cert->rank,
                    'issued_at' => optional($cert->issued_at)->toISOString(),
                ];
            });

        return response()->json([
            'status' => true,
            'data' => $certs,
        ]);
    }

    public function download($attemptId)
    {
        // 1. Find the Quiz Attempt
        $attempt = QuizAttempt::with(['user', 'quiz'])->find($attemptId);

        if (!$attempt) {
            return response()->json(['message' => 'Attempt not found'], 404);
        }

        // 2. Check Ownership (Only the student who took it can download)
        if ($attempt->user_id !== auth('api')->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // 3. Ensure results are finalized and certificate is actually issued
        if (!$attempt->quiz || !$attempt->quiz->results_finalized_at) {
            return response()->json(['message' => 'Certificates are available after results are finalized'], 422);
        }

        $hasCert = QuizCertificate::where('quiz_id', (int)$attempt->quiz_id)
            ->where('user_id', (int)$attempt->user_id)
            ->exists();

        if (!$hasCert) {
            return response()->json(['message' => 'Certificate not available'], 404);
        }

        // 4. Prepare Data for the View
        $data = [
            'user_name' => $attempt->user->name,
            'studentName' => $attempt->user->name,
            'quiz_title' => $attempt->quiz->title,
            'score' => $attempt->score,
            'total' => $attempt->total_questions,
            'date' => $attempt->created_at->format('d M Y'),
        ];

        // 5. Generate PDF
        $pdf = Pdf::loadView('certificate', $data);

        // 6. Download it
        return $pdf->download('certificate.pdf');
    }

    public function downloadForQuiz($quizId)
    {
        $user = auth('api')->user();
        if (!$user || $user->role !== 'student') {
            return response()->json(['message' => 'Students only'], 403);
        }

        $quiz = Quiz::find($quizId);
        if (!$quiz) {
            return response()->json(['message' => 'Quiz not found'], 404);
        }

        if (!$quiz->results_finalized_at) {
            return response()->json(['message' => 'Certificates are available after results are finalized'], 422);
        }

        $cert = QuizCertificate::with(['attempt.user', 'attempt.quiz'])
            ->where('quiz_id', (int)$quizId)
            ->where('user_id', (int)$user->id)
            ->first();

        if (!$cert || !$cert->attempt) {
            return response()->json(['message' => 'Certificate not available'], 404);
        }

        $attempt = $cert->attempt;

        $data = [
            'user_name' => $attempt->user?->name ?? $user->name,
            'studentName' => $attempt->user?->name ?? $user->name,
            'quiz_title' => $attempt->quiz?->title ?? $quiz->title,
            'score' => $attempt->score,
            'total' => $attempt->total_questions,
            'date' => ($cert->issued_at ?? $quiz->results_finalized_at ?? $attempt->created_at)->format('d M Y'),
        ];

        $pdf = Pdf::loadView('certificate', $data);

        return $pdf->download('certificate.pdf');
    }
}