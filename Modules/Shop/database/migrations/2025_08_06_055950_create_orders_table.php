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
		Schema::create('orders', function (Blueprint $table) {
			$table->id();
			$table->foreignId('user_id')->constrained()->cascadeOnDelete();
			$table->foreignId('client_id')->constrained()->cascadeOnDelete();
			$table->foreignId('product_id')->constrained()->cascadeOnDelete();
			$table->decimal('price', 20, 2);
			$table->integer('traffic_gb');
			$table->integer('duration_days');
			$table->timestamp('expires_at');
			$table->string('subs')->unique();
			$table->integer('status')->default(0); // 0:pending, 1:active, 2:expired, 3:canceled
			$table->timestamps();

			$table->index('subs');
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
