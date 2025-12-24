<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (!Schema::hasColumn('quizzes', 'results_finalized_at')) {
                $table->dateTime('results_finalized_at')->nullable()->after('max_attempts');
            }
            if (!Schema::hasColumn('quizzes', 'results_finalized_by')) {
                $table->foreignId('results_finalized_by')
                    ->nullable()
                    ->after('results_finalized_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('quizzes', function (Blueprint $table) {
            if (Schema::hasColumn('quizzes', 'results_finalized_by')) {
                $table->dropConstrainedForeignId('results_finalized_by');
            }
            if (Schema::hasColumn('quizzes', 'results_finalized_at')) {
                $table->dropColumn('results_finalized_at');
            }
        });
    }
};
