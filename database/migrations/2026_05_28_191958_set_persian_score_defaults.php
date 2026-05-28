<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update column defaults to the Persian-academic norm: score=20, minimum_score=12.
        DB::statement('ALTER TABLE terms ALTER COLUMN score SET DEFAULT 20');
        DB::statement('ALTER TABLE terms ALTER COLUMN minimum_score SET DEFAULT 12');
        DB::statement('ALTER TABLE exams ALTER COLUMN score SET DEFAULT 20');
        DB::statement('ALTER TABLE exams ALTER COLUMN minimum_score SET DEFAULT 12');

        // Backfill existing rows that are still on the previous zero default.
        DB::table('terms')->where('score', 0)->update(['score' => 20]);
        DB::table('terms')->where('minimum_score', 0)->update(['minimum_score' => 12]);
        DB::table('exams')->where('score', 0)->update(['score' => 20]);
        DB::table('exams')->where('minimum_score', 0)->update(['minimum_score' => 12]);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE terms ALTER COLUMN score SET DEFAULT 0');
        DB::statement('ALTER TABLE terms ALTER COLUMN minimum_score SET DEFAULT 0');
        DB::statement('ALTER TABLE exams ALTER COLUMN score SET DEFAULT 0');
        DB::statement('ALTER TABLE exams ALTER COLUMN minimum_score SET DEFAULT 0');
    }
};
