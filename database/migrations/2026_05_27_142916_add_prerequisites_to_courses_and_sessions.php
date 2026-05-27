<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->json('prerequisite_course_ids')->nullable()->after('is_active');
        });

        Schema::table('course_sessions', function (Blueprint $table) {
            $table->json('prerequisite_session_ids')->nullable()->after('link');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('prerequisite_course_ids');
        });

        Schema::table('course_sessions', function (Blueprint $table) {
            $table->dropColumn('prerequisite_session_ids');
        });
    }
};
