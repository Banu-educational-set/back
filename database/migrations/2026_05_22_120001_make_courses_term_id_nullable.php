<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['term_id']);
            $table->unsignedBigInteger('term_id')->nullable()->change();
            $table->foreign('term_id')->references('id')->on('terms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign(['term_id']);
            $table->unsignedBigInteger('term_id')->nullable(false)->change();
            $table->foreign('term_id')->references('id')->on('terms')->cascadeOnDelete();
        });
    }
};
