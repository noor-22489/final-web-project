<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'code')) {
                $table->string('code')->nullable()->after('title');
            }
            if (!Schema::hasColumn('courses', 'credit_hours')) {
                $table->unsignedTinyInteger('credit_hours')->nullable()->after('code');
            }
            if (!Schema::hasColumn('courses', 'semester')) {
                $table->string('semester')->nullable()->after('credit_hours');
            }
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            foreach (['semester', 'credit_hours', 'code'] as $col) {
                if (Schema::hasColumn('courses', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
