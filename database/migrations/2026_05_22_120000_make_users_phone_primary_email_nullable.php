<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone', 32)->nullable(false)->change();
            $table->dropIndex('users_phone_index');
            $table->unique('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->index('phone', 'users_phone_index');
            $table->string('phone', 255)->nullable()->change();
            $table->string('email')->nullable(false)->change();
        });
    }
};
