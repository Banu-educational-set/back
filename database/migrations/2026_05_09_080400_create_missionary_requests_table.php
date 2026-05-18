<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missionary_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('missionary_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('external_source')->nullable();
            $table->string('external_reference_id')->nullable()->index();
            $table->string('requester_name');
            $table->string('requester_phone')->nullable();
            $table->string('requester_email')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->date('requested_date')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamps();

            $table->index('missionary_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missionary_requests');
    }
};
