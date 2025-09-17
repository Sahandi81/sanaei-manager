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
        Schema::table('clients', function (Blueprint $table) {
			Schema::table('clients', function (Blueprint $table) {
				$table->unsignedBigInteger('referrer_id')->nullable()->index()->after('status');
				$table->string('referral_code')->nullable()->unique()->after('referrer_id');
				$table->timestamp('referred_at')->nullable()->after('referral_code');

				$table->foreign('referrer_id')
					->references('id')->on('clients')
					->nullOnDelete();
			});
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
			Schema::table('clients', function (Blueprint $table) {
				$table->dropForeign(['referrer_id']);
				$table->dropColumn(['referrer_id', 'referral_code', 'referred_at']);
			});
        });
    }
};
