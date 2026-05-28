<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->unsignedInteger('score')->default(0)->after('description');
            $table->unsignedInteger('minimum_score')->default(0)->after('score');
        });
    }

    public function down(): void
    {
        Schema::table('terms', function (Blueprint $table) {
            $table->dropColumn(['score', 'minimum_score']);
        });
    }
};
