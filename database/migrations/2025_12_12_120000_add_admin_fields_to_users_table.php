<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role');
            }

            // Teacher profile fields
            if (!Schema::hasColumn('users', 'qualification')) {
                $table->string('qualification')->nullable()->after('is_active');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('qualification');
            }

            // Student profile fields
            if (!Schema::hasColumn('users', 'roll_number')) {
                $table->string('roll_number')->nullable()->after('department');
            }
            if (!Schema::hasColumn('users', 'program')) {
                $table->string('program')->nullable()->after('roll_number');
            }
            if (!Schema::hasColumn('users', 'batch')) {
                $table->string('batch')->nullable()->after('program');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['batch', 'program', 'roll_number', 'department', 'qualification', 'is_active'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
