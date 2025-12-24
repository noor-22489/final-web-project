<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Support\Facades\DB;

class QuestionController extends Controller
{
    private function teacherOwnsQuestionOrFail(int $questionId)
    {
        $question = Question::with('quiz.section.course', 'options')->find($questionId);
        if (!$question) {
            return [null, response()->json(['status' => false, 'message' => 'Question not found'], 404)];
        }

        $user = auth('api')->user();
        $course = $question->quiz?->section?->course;
        if (!$user || $user->role !== 'teacher' || !$course || (int)$course->teacher_id !== (int)$user->id) {
            return [null, response()->json(['status' => false, 'message' => 'Unauthorized'], 403)];
        }

        return [$question, null];
    }

    // 1. ADD QUESTION (Teacher)
    public function store(Request $request, $quizId)
    {
        $request->validate([
            'question_text' => 'required|string',
            'options' => 'required|array|min:2',
            'options.*.option_text' => 'nullable|string',
            'options.*.text' => 'nullable|string',
            'options.*.is_correct' => 'required|boolean',
        ]);

        $quiz = Quiz::with('section.course')->find($quizId);
        if (!$quiz) {
            return response()->json(['message' => 'Quiz not found'], 404);
        }

        $user = auth('api')->user();
        if (!$user || $user->role !== 'teacher' || !$quiz->section || !$quiz->section->course || $quiz->section->course->teacher_id !== $user->id) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $normalizedOptions = [];
        foreach ($request->options as $optData) {
            $text = $optData['option_text'] ?? $optData['text'] ?? null;
            $text = is_string($text) ? trim($text) : null;
            if (!$text) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => ['options' => ['Each option must have option_text']]
                ], 422);
            }
            $normalizedOptions[] = [
                'option_text' => $text,
                'is_correct' => (bool)($optData['is_correct'] ?? false),
            ];
        }

        $correctCount = collect($normalizedOptions)->where('is_correct', true)->count();
        if ($correctCount !== 1) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => ['options' => ['Select exactly one correct option']]
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Create Question
            $question = Question::create([
                'quiz_id' => $quizId,
                'question_text' => $request->question_text,
            ]);

            // Create Options
            foreach ($normalizedOptions as $optData) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_text' => $optData['option_text'],
                    'is_correct' => $optData['is_correct'],
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Question added successfully!',
                'data' => $question->load('options')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error adding question: ' . $e->getMessage()], 500);
        }
    }

    // 2. UPDATE QUESTION TEXT (Teacher)
    public function update(Request $request, $id)
    {
        [$question, $err] = $this->teacherOwnsQuestionOrFail((int)$id);
        if ($err) {
            return $err;
        }

        $request->validate([
            'question_text' => 'required|string',
            'options' => 'nullable|array|min:2',
            'options.*.option_text' => 'nullable|string',
            'options.*.text' => 'nullable|string',
            'options.*.is_correct' => 'required_with:options|boolean',
        ]);

        $normalizedOptions = null;
        if ($request->has('options')) {
            $normalizedOptions = [];
            foreach ($request->options as $optData) {
                $text = $optData['option_text'] ?? $optData['text'] ?? null;
                $text = is_string($text) ? trim($text) : null;
                if (!$text) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Validation failed',
                        'errors' => ['options' => ['Each option must have option_text']]
                    ], 422);
                }
                $normalizedOptions[] = [
                    'option_text' => $text,
                    'is_correct' => (bool)($optData['is_correct'] ?? false),
                ];
            }

            if (count($normalizedOptions) > 4) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => ['options' => ['Maximum 4 options allowed']]
                ], 422);
            }

            $correctCount = collect($normalizedOptions)->where('is_correct', true)->count();
            if ($correctCount !== 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => ['options' => ['Select exactly one correct option']]
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $question->update([
                'question_text' => $request->question_text
            ]);

            if (is_array($normalizedOptions)) {
                QuestionOption::where('question_id', $question->id)->delete();
                foreach ($normalizedOptions as $optData) {
                    QuestionOption::create([
                        'question_id' => $question->id,
                        'option_text' => $optData['option_text'],
                        'is_correct' => $optData['is_correct'],
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Question updated successfully!',
                'data' => $question->fresh()->load('options')
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Error updating question: ' . $e->getMessage()], 500);
        }
    }

    // 3. DELETE QUESTION (Teacher)
    public function destroy($id)
    {
        [$question, $err] = $this->teacherOwnsQuestionOrFail((int)$id);
        if ($err) {
            return $err;
        }

        // Deleting the question automatically deletes options (if Cascade is set in DB)
        // or Laravel handles it if configured.
        $question->delete(); 

        return response()->json([
            'status' => true,
            'message' => 'Question deleted successfully!'
        ]);
    }
}