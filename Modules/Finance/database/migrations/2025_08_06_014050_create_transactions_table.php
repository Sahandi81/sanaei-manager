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
		Schema::create('transactions', function (Blueprint $table) {
			$table->id();
			$table->bigInteger('user_id')->index();
			$table->bigInteger('modified_by')->nullable()->comment('Admin who verified the transaction');

			$table->bigInteger('client_id')->index();
			$table->bigInteger('amount');

			$table->string('currency', 3)->default('IRR')->comment('Currency code like IRR, USD, etc.');
			$table->string('description')->nullable();

			// Payment method details
			$table->integer('card_id')->nullable()->comment('Destination card for deposit');

			// Status and verification
			$table->integer('status')->default(0)->comment('0=pending, 1=approved, 2=rejected');
			$table->timestamp('verified_at')->nullable();
			$table->text('rejection_reason')->nullable();

			// Transaction metadata
			$table->enum('type', ['panel', 'telegram'])->default('panel');

			// Foreign keys (uncomment when needed)
			// $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
			// $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
			// $table->foreign('card_id')->references('id')->on('bank_cards')->onDelete('set null');

			$table->timestamps();

			// Additional indexes
			$table->index(['client_id', 'status']);
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
