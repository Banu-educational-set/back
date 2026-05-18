<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropMorphs('model');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->nullableMorphs('model');
            $table->foreignId('uploaded_by')
                ->nullable()
                ->after('disk')
                ->constrained('users')
                ->nullOnDelete();
            $table->index(['uploaded_by', 'model_id', 'created_at'], 'media_pending_idx');
        });
    }

    public function down(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex('media_pending_idx');
            $table->dropConstrainedForeignId('uploaded_by');
            $table->dropMorphs('model');
        });

        Schema::table('media', function (Blueprint $table) {
            $table->morphs('model');
        });
    }
};
