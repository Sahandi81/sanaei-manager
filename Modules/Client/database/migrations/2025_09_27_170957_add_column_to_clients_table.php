<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table('clients', function (Blueprint $table) {
			$table->dropUnique('unique_tel_id');

			$table->unique(['user_id', 'telegram_id'], 'clients_user_id_telegram_id_unique');
		});
	}

	public function down(): void
	{
		Schema::table('clients', function (Blueprint $table) {
			$table->dropUnique('clients_user_id_telegram_id_unique');
			$table->unique('telegram_id', 'unique_tel_id');
		});
	}
};
