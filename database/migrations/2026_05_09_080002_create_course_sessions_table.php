<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type')->default('online');
            $table->timestamp('starts_at')->nullable();
            $table->string('location')->nullable();
            $table->string('link', 500)->nullable();
            $table->timestamps();

            $table->index(['course_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_sessions');
    }
};
