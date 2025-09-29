<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('bot_name')->nullable()->after('telegram_id');
            $table->string('bot_id')->nullable()->after('telegram_id');
            $table->string('support_id')->nullable()->after('telegram_id');
            $table->string('tut_url')->nullable()->after('telegram_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('bot_name');
            $table->dropColumn('bot_id');
            $table->dropColumn('support_id');
            $table->dropColumn('tut_url');
        });
    }
};
