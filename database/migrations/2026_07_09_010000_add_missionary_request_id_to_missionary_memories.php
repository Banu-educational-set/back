<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missionary_memories', function (Blueprint $table) {
            $table->foreignId('missionary_request_id')
                ->nullable()
                ->after('missionary_id')
                ->constrained('missionary_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('missionary_memories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('missionary_request_id');
        });
    }
};
