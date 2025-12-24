<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            if (!Schema::hasColumn('materials', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (!Schema::hasColumn('materials', 'view_count')) {
                $table->unsignedInteger('view_count')->default(0)->after('content');
            }
            if (!Schema::hasColumn('materials', 'download_count')) {
                $table->unsignedInteger('download_count')->default(0)->after('view_count');
            }
        });

        // Expand ENUM types for uploads/links. Raw SQL avoids requiring doctrine/dbal.
        try {
            DB::statement("ALTER TABLE materials MODIFY COLUMN type ENUM('pdf','video','text','ppt','document','link') NOT NULL");
        } catch (\Throwable $e) {
            // If DB isn't MySQL or already modified, ignore.
        }
    }

    public function down(): void
    {
        // Best-effort rollback of enum.
        try {
            DB::statement("ALTER TABLE materials MODIFY COLUMN type ENUM('pdf','video','text') NOT NULL");
        } catch (\Throwable $e) {
            // ignore
        }

        Schema::table('materials', function (Blueprint $table) {
            foreach (['download_count', 'view_count', 'description'] as $col) {
                if (Schema::hasColumn('materials', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
