<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            $table->boolean('is_priority')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('homeworks', function (Blueprint $table) {
            $table->dropColumn('is_priority');
        });
    }
};
