<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            if (!Schema::hasColumn('quiz_attempts', 'correct_answers')) {
                $table->unsignedInteger('correct_answers')->default(0)->after('total_questions');
            }
            if (!Schema::hasColumn('quiz_attempts', 'wrong_answers')) {
                $table->unsignedInteger('wrong_answers')->default(0)->after('correct_answers');
            }
            if (!Schema::hasColumn('quiz_attempts', 'marks')) {
                $table->decimal('marks', 8, 2)->nullable()->after('wrong_answers');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            foreach (['marks', 'wrong_answers', 'correct_answers'] as $col) {
                if (Schema::hasColumn('quiz_attempts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
