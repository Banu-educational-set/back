<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedInteger('score')->default(0)->after('description');
            $table->unsignedInteger('minimum_score')->default(0)->after('score');
            $table->dropColumn('pass_score');
        });

        Schema::table('exam_questions', function (Blueprint $table) {
            $table->unsignedInteger('score')->default(1)->after('question_text');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedTinyInteger('pass_score')->nullable()->after('description');
            $table->dropColumn(['score', 'minimum_score']);
        });

        Schema::table('exam_questions', function (Blueprint $table) {
            $table->dropColumn('score');
        });
    }
};
