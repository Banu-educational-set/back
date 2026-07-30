<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Update column defaults to the Persian-academic norm: score=20, minimum_score=12.
        Schema::table('terms', function (Blueprint $table) {
            $table->unsignedInteger('score')->default(20)->change();
            $table->unsignedInteger('minimum_score')->default(12)->change();
        });
        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedInteger('score')->default(20)->change();
            $table->unsignedInteger('minimum_score')->default(12)->change();
        });

        // Backfill existing rows that are still on the previous zero default.
        DB::table('terms')->where('score', 0)->update(['score' => 20]);
        DB::table('terms')->where('minimum_score', 0)->update(['minimum_score' => 12]);
        DB::table('exams')->where('score', 0)->update(['score' => 20]);
        DB::table('exams')->where('minimum_score', 0)->update(['minimum_score' => 12]);
    }

    public function down(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->unsignedInteger('score')->default(0)->change();
            $table->unsignedInteger('minimum_score')->default(0)->change();
        });
        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedInteger('score')->default(0)->change();
            $table->unsignedInteger('minimum_score')->default(0)->change();
        });
    }
};
