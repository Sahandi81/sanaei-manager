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
        Schema::create('order_configs', function (Blueprint $table) {
			$table->id();

			// Foreign keys
			$table->foreignId('server_id')->constrained('servers')->cascadeOnDelete();
			$table->foreignId('inbound_id')->constrained('inbounds')->cascadeOnDelete();
			$table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
			$table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();

			// Configuration data
			$table->json('config');
			$table->decimal('used_traffic_gb', 10, 3)->default(0.0);
			// Timestamps
			$table->timestamps();

			// Indexes
			$table->index(['server_id', 'inbound_id']);
			$table->index(['order_id']);
			$table->index(['client_id']);
			$table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_configs');
    }
};
