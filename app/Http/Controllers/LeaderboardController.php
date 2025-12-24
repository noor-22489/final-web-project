<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QuizAttempt;
use App\Models\Quiz;
use App\Models\QuizCertificate;
use App\Models\LmsSetting;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    // GET TOP STUDENTS
    public function index()
    {
        // 1. Group by User and Sum their Scores
        $leaderboard = QuizAttempt::select('user_id', DB::raw('SUM(score) as total_score'))
            ->with('user:id,name,email') // Get user name
            ->groupBy('user_id')
            ->orderByDesc('total_score') // Highest score first
            ->take(10) // Top 10 only
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Top 10 Students',
            'data' => $leaderboard
        ]);
    }

    // GET QUIZ LEADERBOARD (rank by marks desc, time asc)
    public function quiz($quizId)
    {
        $quiz = Quiz::find($quizId);
        if (!$quiz) {
            return response()->json(['status' => false, 'message' => 'Quiz not found'], 404);
        }

        $topN = LmsSetting::getInt('leaderboard_top_n', 10);
        $topN = max(1, min(100, $topN));

        $attempts = QuizAttempt::with('user:id,name,email')
            ->where('quiz_id', (int)$quizId)
            ->get();

        $bestByUser = [];

        foreach ($attempts as $attempt) {
            $userId = (int)$attempt->user_id;

            $marksVal = $attempt->marks === null ? (float)($attempt->score ?? 0) : (float)$attempt->marks;
            $timeVal = $attempt->time_taken_seconds === null ? null : (int)$attempt->time_taken_seconds;

            if (!isset($bestByUser[$userId])) {
                $bestByUser[$userId] = $attempt;
                continue;
            }

            $current = $bestByUser[$userId];
            $bestMarks = $current->marks === null ? (float)($current->score ?? 0) : (float)$current->marks;
            $bestTime = $current->time_taken_seconds === null ? null : (int)$current->time_taken_seconds;

            if ($marksVal > $bestMarks) {
                $bestByUser[$userId] = $attempt;
                continue;
            }

            if ($marksVal === $bestMarks) {
                // Lower time wins; null time is always worse
                if ($bestTime === null && $timeVal !== null) {
                    $bestByUser[$userId] = $attempt;
                    continue;
                }
                if ($bestTime !== null && $timeVal !== null && $timeVal < $bestTime) {
                    $bestByUser[$userId] = $attempt;
                    continue;
                }
            }
        }

        $bestAttempts = array_values($bestByUser);

        usort($bestAttempts, function ($a, $b) {
            $aMarks = $a->marks === null ? (float)($a->score ?? 0) : (float)$a->marks;
            $bMarks = $b->marks === null ? (float)($b->score ?? 0) : (float)$b->marks;

            if ($aMarks !== $bMarks) {
                return $aMarks < $bMarks ? 1 : -1;
            }

            $aTime = $a->time_taken_seconds === null ? PHP_INT_MAX : (int)$a->time_taken_seconds;
            $bTime = $b->time_taken_seconds === null ? PHP_INT_MAX : (int)$b->time_taken_seconds;
            if ($aTime !== $bTime) {
                return $aTime < $bTime ? -1 : 1;
            }

            return (int)$a->id < (int)$b->id ? -1 : 1;
        });

        $entries = [];
        $myRank = null;
        $user = auth('api')->user();

        foreach ($bestAttempts as $idx => $attempt) {
            $rank = $idx + 1;
            if ($user && (int)$attempt->user_id === (int)$user->id) {
                $myRank = $rank;
            }

            if ($rank > $topN) {
                continue;
            }

            $entries[] = [
                'rank' => $rank,
                'user' => $attempt->user,
                'attempt_id' => (int)$attempt->id,
                'marks' => $attempt->marks === null ? (float)($attempt->score ?? 0) : (float)$attempt->marks,
                'time_taken_seconds' => $attempt->time_taken_seconds,
                'taken_at' => $attempt->created_at,
            ];
        }

        $myCertificate = null;
        if ($user) {
            $cert = QuizCertificate::where('quiz_id', (int)$quizId)->where('user_id', (int)$user->id)->first();
            if ($cert) {
                $myCertificate = [
                    'rank' => (int)$cert->rank,
                    'issued_at' => $cert->issued_at,
                ];
            }
        }

        return response()->json([
            'status' => true,
            'data' => [
                'quiz_id' => (int)$quizId,
                'results_finalized_at' => $quiz->results_finalized_at,
                'leaderboard_top_n' => $topN,
                'entries' => $entries,
                'my_rank' => $myRank,
                'my_certificate' => $myCertificate,
            ]
        ]);
    }
}