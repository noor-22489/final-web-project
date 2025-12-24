<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'duration_minutes')) {
                $table->unsignedInteger('duration_minutes')->nullable()->after('title');
            }
            if (!Schema::hasColumn('quizzes', 'deadline_at')) {
                $table->dateTime('deadline_at')->nullable()->after('duration_minutes');
            }
            if (!Schema::hasColumn('quizzes', 'negative_mark_per_wrong')) {
                $table->decimal('negative_mark_per_wrong', 6, 2)->default(0)->after('deadline_at');
            }
            if (!Schema::hasColumn('quizzes', 'max_attempts')) {
                $table->unsignedInteger('max_attempts')->nullable()->after('negative_mark_per_wrong');
            }
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            foreach (['max_attempts', 'negative_mark_per_wrong', 'deadline_at', 'duration_minutes'] as $col) {
                if (Schema::hasColumn('quizzes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
