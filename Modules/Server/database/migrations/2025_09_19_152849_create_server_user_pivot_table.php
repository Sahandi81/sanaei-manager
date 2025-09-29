<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
	public function up(): void {
		Schema::create('server_user', function (Blueprint $table) {
			$table->id();
			$table->foreignId('server_id')->constrained()->cascadeOnDelete();
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();
			$table->timestamps();
			$table->unique(['server_id','user_id']);
		});

		// Backfill از servers.user_id به پیوت
		DB::statement('
            INSERT INTO server_user (server_id, user_id, created_at, updated_at)
            SELECT id as server_id, user_id, NOW(), NOW()
            FROM servers
            WHERE user_id IS NOT NULL
        ');
	}

	public function down(): void {
		Schema::dropIfExists('server_user');
	}
};
