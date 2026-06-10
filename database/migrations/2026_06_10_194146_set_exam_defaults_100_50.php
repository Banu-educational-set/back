<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Exams are scored out of 100 by convention. Bump column defaults
        // from the earlier 20/12 (Persian school-style) to 100/50 for new rows.
        DB::statement('ALTER TABLE exams ALTER COLUMN score SET DEFAULT 100');
        DB::statement('ALTER TABLE exams ALTER COLUMN minimum_score SET DEFAULT 50');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE exams ALTER COLUMN score SET DEFAULT 20');
        DB::statement('ALTER TABLE exams ALTER COLUMN minimum_score SET DEFAULT 12');
    }
};
