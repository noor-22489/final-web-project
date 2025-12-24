<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\Section;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\Enrollment;
use App\Models\QuizCertificate;
use App\Models\LmsSetting;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    private function computeBestAttemptsForQuiz(int $quizId): array
    {
        $attempts = QuizAttempt::with('user:id,name,email')
            ->where('quiz_id', $quizId)
            ->get();

        $bestByUser = [];

        foreach ($attempts as $attempt) {
            $userId = (int)$attempt->user_id;

            $marks = $attempt->marks;
            $marksVal = $marks === null ? (float)($attempt->score ?? 0) : (float)$marks;
            $timeVal = $attempt->time_taken_seconds === null ? null : (int)$attempt->time_taken_seconds;

            if (!isset($bestByUser[$userId])) {
                $bestByUser[$userId] = $attempt;
                continue;
            }

            $currentBest = $bestByUser[$userId];
            $bestMarks = $currentBest->marks === null ? (float)($currentBest->score ?? 0) : (float)$currentBest->marks;
            $bestTime = $currentBest->time_taken_seconds === null ? null : (int)$currentBest->time_taken_seconds;

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
                return $aMarks < $bMarks ? 1 : -1; // desc
            }

            $aTime = $a->time_taken_seconds === null ? PHP_INT_MAX : (int)$a->time_taken_seconds;
            $bTime = $b->time_taken_seconds === null ? PHP_INT_MAX : (int)$b->time_taken_seconds;

            if ($aTime !== $bTime) {
                return $aTime < $bTime ? -1 : 1; // asc
            }

            // Stable tie-breaker
            return (int)$a->id < (int)$b->id ? -1 : 1;
        });

        return $bestAttempts;
    }

    private function teacherOwnsSectionOrFail(int $sectionId)
    {
        $section = Section::with('course')->find($sectionId);
        if (!$section) {
            return [null, response()->json(['status' => false, 'message' => 'Section not found'], 404)];
        }

        $user = auth('api')->user();
        if (!$user || $user->role !== 'teacher' || !$section->course || $section->course->teacher_id !== $user->id) {
            return [null, response()->json(['status' => false, 'message' => 'Unauthorized'], 403)];
        }

        return [$section, null];
    }

    private function teacherOwnsQuizOrFail(int $quizId)
    {
        $quiz = Quiz::with('section.course')->find($quizId);
        if (!$quiz) {
            return [null, response()->json(['status' => false, 'message' => 'Quiz not found'], 404)];
        }

        $user = auth('api')->user();
        if (!$user || $user->role !== 'teacher' || !$quiz->section || !$quiz->section->course || $quiz->section->course->teacher_id !== $user->id) {
            return [null, response()->json(['status' => false, 'message' => 'Unauthorized'], 403)];
        }

        return [$quiz, null];
    }

    // 1. CREATE QUIZ (Flexible: Questions are OPTIONAL)
    public function store(Request $request, $sectionId = null)
    {
        // A. Handle Section ID (From URL or Body)
        if (!$sectionId) {
            $request->validate(['section_id' => 'required|exists:sections,id']);
            $sectionId = $request->section_id;
        }

        // B. Validate (Questions are now NULLABLE)
        $request->validate([
            'title' => 'required|string',
            'duration_minutes' => 'nullable|integer|min:1',
            'duration' => 'nullable|integer|min:1',
            'deadline_at' => 'nullable|date',
            'negative_mark_per_wrong' => 'nullable|numeric|min:0',
            'max_attempts' => 'nullable|integer|min:1',
            'questions' => 'nullable|array',  // <--- FIXED: Optional
            'questions.*.question_text' => 'required_with:questions|string',
            'questions.*.options' => 'required_with:questions|array|min:2',
            'questions.*.options.*.option_text' => 'required_with:questions|string',
            'questions.*.options.*.is_correct' => 'required_with:questions|boolean',
        ]);

        $durationMinutes = $request->duration_minutes ?? $request->duration;

        // Teacher ownership check
        [$section, $err] = $this->teacherOwnsSectionOrFail((int)$sectionId);
        if ($err) {
            return $err;
        }

        DB::beginTransaction();

        try {
            // C. Create the Quiz Container
            $quiz = Quiz::create([
                'section_id' => $sectionId,
                'title' => $request->title,
                'duration_minutes' => $durationMinutes,
                'deadline_at' => $request->deadline_at,
                'negative_mark_per_wrong' => $request->negative_mark_per_wrong ?? 0,
                'max_attempts' => $request->max_attempts,
            ]);

            // D. Only add questions IF they are sent in this request
            if ($request->has('questions') && !empty($request->questions)) {
                foreach ($request->questions as $qData) {
                    $question = Question::create([
                        'quiz_id' => $quiz->id,
                        'question_text' => $qData['question_text'],
                    ]);

                    foreach ($qData['options'] as $optData) {
                        QuestionOption::create([
                            'question_id' => $question->id,
                            'option_text' => $optData['option_text'],
                            'is_correct' => $optData['is_correct'],
                        ]);
                    }
                }
            }

            DB::commit();
            
            return response()->json([
                'status' => true,
                'message' => 'Quiz created successfully!',
                'data' => $quiz->load('questions.options')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating quiz: ' . $e->getMessage()], 500);
        }
    }

    // 1b. LIST QUIZZES FOR A SECTION (Teacher Only)
    public function indexBySection($sectionId)
    {
        [$section, $err] = $this->teacherOwnsSectionOrFail((int)$sectionId);
        if ($err) {
            return $err;
        }

        $quizzes = Quiz::where('section_id', $sectionId)
            ->orderByDesc('id')
            ->get();

        return response()->json(['status' => true, 'data' => $quizzes]);
    }

    // 2. GET QUIZ DETAILS (Secure: Hides answers from Students)
    public function show($id)
    {
        // Load Quiz with Questions and Options
        $quiz = Quiz::with(['questions.options'])->find($id);

        if (!$quiz) {
            return response()->json(['message' => 'Quiz not found'], 404);
        }

        // SECURITY CHECK: Who is asking?
        $user = auth('api')->user(); 

        // If it is a Student (or not logged in), HIDE the correct answer flag!
        if (!$user || $user->role === 'student') {
            $quiz->questions->each(function ($question) {
                $question->options->makeHidden(['is_correct']);
            });
        }

        return response()->json([
            'status' => true,
            'data' => $quiz
        ]);
    }

    // 2a. STUDENT SHOW (Requires enrollment)
    public function studentShow($id)
    {
        $user = auth('api')->user();
        if (!$user || $user->role !== 'student') {
            return response()->json(['status' => false, 'message' => 'Students only'], 403);
        }

        $quiz = Quiz::with(['section.course', 'questions.options'])->find($id);
        if (!$quiz) {
            return response()->json(['status' => false, 'message' => 'Quiz not found'], 404);
        }

        $courseId = $quiz->section?->course?->id;
        if (!$courseId) {
            return response()->json(['status' => false, 'message' => 'Course not found'], 404);
        }

        $enrolled = Enrollment::where('user_id', $user->id)->where('course_id', $courseId)->exists();
        if (!$enrolled) {
            return response()->json(['status' => false, 'message' => 'You are not enrolled in this course'], 403);
        }

        // Hide correct flags before attempt
        $quiz->questions->each(function ($question) {
            $question->options->makeHidden(['is_correct']);
        });

        return response()->json(['status' => true, 'data' => $quiz]);
    }

    // 2b. PREVIEW QUIZ (Teacher Only, includes correct flags)
    public function preview($id)
    {
        [$quiz, $err] = $this->teacherOwnsQuizOrFail((int)$id);
        if ($err) {
            return $err;
        }

        $quiz = Quiz::with(['questions.options'])->find($id);

        return response()->json(['status' => true, 'data' => $quiz]);
    }

    // 2c. DUPLICATE QUIZ (Teacher Only)
    public function duplicate($id)
    {
        [$quiz, $err] = $this->teacherOwnsQuizOrFail((int)$id);
        if ($err) {
            return $err;
        }

        DB::beginTransaction();
        try {
            $quiz->load('questions.options');

            $newQuiz = Quiz::create([
                'section_id' => $quiz->section_id,
                'title' => 'Copy of ' . $quiz->title,
                'duration_minutes' => $quiz->duration_minutes,
                'deadline_at' => $quiz->deadline_at,
                'negative_mark_per_wrong' => $quiz->negative_mark_per_wrong,
                'max_attempts' => $quiz->max_attempts,
            ]);

            foreach ($quiz->questions as $q) {
                $newQuestion = Question::create([
                    'quiz_id' => $newQuiz->id,
                    'question_text' => $q->question_text,
                ]);

                foreach ($q->options as $opt) {
                    QuestionOption::create([
                        'question_id' => $newQuestion->id,
                        'option_text' => $opt->option_text,
                        'is_correct' => (bool)$opt->is_correct,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Quiz duplicated', 'data' => $newQuiz], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Error duplicating quiz: ' . $e->getMessage()], 500);
        }
    }

    // 2d. RESULTS (Teacher Only)
    public function results($id)
    {
        [$quiz, $err] = $this->teacherOwnsQuizOrFail((int)$id);
        if ($err) {
            return $err;
        }

        $attempts = QuizAttempt::with('user')
            ->where('quiz_id', $id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'quiz' => $quiz,
                'attempts' => $attempts,
            ]
        ]);
    }

    // 2g. UPDATE QUIZ DEADLINE (Teacher Only)
    public function updateDeadline(Request $request, $id)
    {
        [$quiz, $err] = $this->teacherOwnsQuizOrFail((int)$id);
        if ($err) {
            return $err;
        }

        $data = $request->validate([
            'deadline_at' => ['nullable', 'date'],
        ]);

        $quiz->deadline_at = $data['deadline_at'] ?? null;
        $quiz->save();

        return response()->json([
            'status' => true,
            'message' => 'Quiz deadline updated',
            'data' => $quiz,
        ]);
    }

    // 2h. DELETE QUIZ (Teacher Only)
    public function destroy($id)
    {
        [$quiz, $err] = $this->teacherOwnsQuizOrFail((int)$id);
        if ($err) {
            return $err;
        }

        $quiz->delete();

        return response()->json([
            'status' => true,
            'message' => 'Quiz deleted',
        ]);
    }

    // 2e. RESULTS CSV (Teacher Only)
    public function resultsCsv($id)
    {
        [$quiz, $err] = $this->teacherOwnsQuizOrFail((int)$id);
        if ($err) {
            return $err;
        }

        $attempts = QuizAttempt::with('user')
            ->where('quiz_id', $id)
            ->orderByDesc('id')
            ->get();

        $lines = [];
        $lines[] = 'Attempt ID,Student ID,Student Name,Student Email,Correct,Wrong,Total Questions,Marks,Created At';

        foreach ($attempts as $a) {
            $name = $a->user ? str_replace('"', '""', $a->user->name ?? '') : '';
            $email = $a->user ? str_replace('"', '""', $a->user->email ?? '') : '';
            $lines[] = implode(',', [
                $a->id,
                $a->user_id,
                '"' . $name . '"',
                '"' . $email . '"',
                $a->correct_answers ?? 0,
                $a->wrong_answers ?? 0,
                $a->total_questions,
                $a->marks ?? '',
                $a->created_at,
            ]);
        }

        $csv = implode("\n", $lines) . "\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="quiz_' . $quiz->id . '_results.csv"',
        ]);
    }

    // 2f. FINALIZE RESULTS + ISSUE CERTIFICATES (Teacher Only)
    public function finalizeResults($id)
    {
        [$quiz, $err] = $this->teacherOwnsQuizOrFail((int)$id);
        if ($err) {
            return $err;
        }

        if ($quiz->results_finalized_at) {
            return response()->json(['status' => false, 'message' => 'Results already finalized'], 422);
        }

        $bestAttempts = $this->computeBestAttemptsForQuiz((int)$id);
        $certificateTopN = LmsSetting::getInt('certificate_top_n', 3);
        $certificateTopN = max(1, min(10, $certificateTopN));

        $winners = array_slice($bestAttempts, 0, min($certificateTopN, count($bestAttempts)));

        DB::beginTransaction();
        try {
            $quiz->results_finalized_at = now();
            $quiz->results_finalized_by = auth('api')->id();
            $quiz->save();

            // Rebuild certificates deterministically
            QuizCertificate::where('quiz_id', (int)$id)->delete();

            $issuedAt = now();
            foreach ($winners as $idx => $attempt) {
                QuizCertificate::create([
                    'quiz_id' => (int)$id,
                    'user_id' => (int)$attempt->user_id,
                    'quiz_attempt_id' => (int)$attempt->id,
                    'rank' => (int)($idx + 1),
                    'issued_at' => $issuedAt,
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Results finalized and certificates issued',
                'data' => [
                    'quiz_id' => (int)$id,
                    'certificates_issued' => count($winners),
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Finalize failed: ' . $e->getMessage()], 500);
        }
    }

    // 3. SUBMIT QUIZ & GET SCORE (Student Only)
    public function submit(Request $request, $id)
    {
        $user = auth('api')->user();
        if (!$user || $user->role !== 'student') {
            return response()->json(['status' => false, 'message' => 'Students only'], 403);
        }

        $request->validate([
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.option_id' => 'required|exists:question_options,id',
            'time_taken_seconds' => 'nullable|integer|min:0|max:86400',
        ]);

        $quiz = Quiz::with('section.course', 'questions.options')->find($id);
        if (!$quiz) {
            return response()->json(['message' => 'Quiz not found'], 404);
        }

        $courseId = $quiz->section?->course?->id;
        if (!$courseId) {
            return response()->json(['status' => false, 'message' => 'Course not found'], 404);
        }

        $enrolled = Enrollment::where('user_id', $user->id)->where('course_id', (int)$courseId)->exists();
        if (!$enrolled) {
            return response()->json(['status' => false, 'message' => 'You are not enrolled in this course'], 403);
        }

        if ($quiz->deadline_at && now()->greaterThan($quiz->deadline_at)) {
            return response()->json(['status' => false, 'message' => 'Quiz deadline has passed'], 422);
        }

        if ($quiz->results_finalized_at) {
            return response()->json(['status' => false, 'message' => 'Quiz results are finalized. Submissions are closed.'], 422);
        }

        if ($quiz->max_attempts) {
            $attemptCount = QuizAttempt::where('quiz_id', $id)->where('user_id', $user->id)->count();
            if ($attemptCount >= (int)$quiz->max_attempts) {
                return response()->json(['status' => false, 'message' => 'Max attempts reached'], 422);
            }
        }

        // If client submits duplicate entries for the same question, last one wins
        $answersByQuestion = collect($request->answers)->keyBy('question_id');

        $correctCount = 0;
        $wrongCount = 0;
        $totalQuestions = $quiz->questions->count();

        $review = [];

        foreach ($quiz->questions as $question) {
            $submitted = $answersByQuestion->get($question->id);
            if (!$submitted) {
                continue;
            }

            $optionId = $submitted['option_id'] ?? null;
            if (!$optionId) {
                continue;
            }

            $selectedOption = $question->options->firstWhere('id', (int)$optionId);
            $correctOption = $question->options->firstWhere('is_correct', true);
            $isCorrect = (bool)($selectedOption && $selectedOption->is_correct);

            if ($isCorrect) {
                $correctCount++;
            } else {
                $wrongCount++;
            }

            $review[] = [
                'question_id' => $question->id,
                'question_text' => $question->question_text,
                'selected_option_id' => $selectedOption?->id,
                'selected_option_text' => $selectedOption?->option_text,
                'correct_option_id' => $correctOption?->id,
                'correct_option_text' => $correctOption?->option_text,
                'is_correct' => $isCorrect,
            ];
        }

        $negative = (float)($quiz->negative_mark_per_wrong ?? 0);
        $marks = $correctCount - ($wrongCount * $negative);

        DB::beginTransaction();
        try {
            // Keep legacy 'score' as correct answers count
            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $id,
                'score' => $correctCount,
                'total_questions' => $totalQuestions,
                'correct_answers' => $correctCount,
                'wrong_answers' => $wrongCount,
                'marks' => $marks,
                'time_taken_seconds' => $request->input('time_taken_seconds'),
            ]);

            foreach ($review as $r) {
                QuizAttemptAnswer::create([
                    'quiz_attempt_id' => $attempt->id,
                    'question_id' => $r['question_id'],
                    'selected_option_id' => $r['selected_option_id'],
                    'is_correct' => (bool)$r['is_correct'],
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Quiz submitted successfully!',
                'attempt_id' => $attempt->id,
                'results' => [
                    'correct' => $correctCount,
                    'wrong' => $wrongCount,
                    'total' => $totalQuestions,
                    'negative_mark_per_wrong' => $negative,
                    'marks' => $marks,
                    'percentage_correct' => ($totalQuestions > 0) ? round(($correctCount / $totalQuestions) * 100, 2) . '%' : '0%'
                ],
                'review' => $review,
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Error submitting quiz: ' . $e->getMessage()], 500);
        }
    }

    // 3a. ATTEMPT REVIEW (Student only, shows stored answers + score)
    public function attemptReview($id)
    {
        $user = auth('api')->user();
        if (!$user || $user->role !== 'student') {
            return response()->json(['status' => false, 'message' => 'Students only'], 403);
        }

        $attempt = QuizAttempt::with(['quiz.section.course', 'quiz.questions.options', 'answers'])->find($id);
        if (!$attempt) {
            return response()->json(['status' => false, 'message' => 'Attempt not found'], 404);
        }

        if ((int)$attempt->user_id !== (int)$user->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $quiz = $attempt->quiz;
        $answersByQuestion = $attempt->answers->keyBy('question_id');

        $review = [];
        foreach ($quiz->questions as $q) {
            $a = $answersByQuestion->get($q->id);
            $selected = $a ? $q->options->firstWhere('id', (int)$a->selected_option_id) : null;
            $correct = $q->options->firstWhere('is_correct', true);
            $review[] = [
                'question_id' => $q->id,
                'question_text' => $q->question_text,
                'selected_option_id' => $selected?->id,
                'selected_option_text' => $selected?->option_text,
                'correct_option_id' => $correct?->id,
                'correct_option_text' => $correct?->option_text,
                'is_correct' => (bool)($a?->is_correct),
            ];
        }

        return response()->json([
            'status' => true,
            'data' => [
                'attempt' => $attempt,
                'review' => $review,
            ]
        ]);
    }
}