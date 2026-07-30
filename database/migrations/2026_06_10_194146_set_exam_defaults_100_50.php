<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Exams are scored out of 100 by convention. Bump column defaults
        // from the earlier 20/12 (Persian school-style) to 100/50 for new rows.
        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedInteger('score')->default(100)->change();
            $table->unsignedInteger('minimum_score')->default(50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->unsignedInteger('score')->default(20)->change();
            $table->unsignedInteger('minimum_score')->default(12)->change();
        });
    }
};
