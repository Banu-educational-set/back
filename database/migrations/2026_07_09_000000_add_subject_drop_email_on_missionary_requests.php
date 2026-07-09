<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('missionary_requests', function (Blueprint $table) {
            $table->string('subject')->nullable()->after('title');
        });

        if (Schema::hasColumn('missionary_requests', 'requester_email')) {
            Schema::table('missionary_requests', function (Blueprint $table) {
                $table->dropColumn('requester_email');
            });
        }
    }

    public function down(): void
    {
        Schema::table('missionary_requests', function (Blueprint $table) {
            $table->string('requester_email')->nullable()->after('requester_phone');
            $table->dropColumn('subject');
        });
    }
};
