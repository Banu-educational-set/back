<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('marriage_status', 32)->nullable()->after('city_id');
            $table->date('birthday')->nullable()->after('marriage_status');
            $table->string('gender', 16)->nullable()->after('birthday');
            $table->text('address')->nullable()->after('gender');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['marriage_status', 'birthday', 'gender', 'address']);
        });
    }
};
