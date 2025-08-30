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
		Schema::create('wallet_transactions', function (Blueprint $table) {
			$table->bigIncrements('id');

			$table->unsignedBigInteger('wallet_id');
			$table->string('type', 32); // deposit | withdraw | transfer_out | transfer_in | adjustment

			// مبلغ همیشه مثبت؛ نشانه خروج/ورود با type مشخص می‌شود
			$table->unsignedBigInteger('amount_minor');

			// موجودی پس از اعمال این تراکنش (برای audit سریع)
			$table->unsignedBigInteger('running_balance_minor');

			// جلوگیری از دوباره‌کاری (idempotency)
			$table->string('idempotency_key', 128)->nullable()->unique();

			// مرجع اختیاری به هر مدل دامین (مثلاً سفارش/پرداخت/درگاه)
			$table->nullableMorphs('ref'); // ref_type, ref_id

			$table->json('meta')->nullable();

			$table->timestamps();

			// روابط و ایندکس‌ها
			$table->foreign('wallet_id')
				->references('id')->on('wallets')
				->onUpdate('cascade')->onDelete('cascade');

			$table->index(['wallet_id', 'created_at'], 'wtx_wallet_created_idx');
			$table->index('type', 'wtx_type_idx');
			$table->index(['ref_type', 'ref_id'], 'wtx_ref_idx');
		});
	}
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
