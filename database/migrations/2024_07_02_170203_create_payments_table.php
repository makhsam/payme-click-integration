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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders');

            $table->tinyInteger('method'); // click, payme

            $table->string('transaction_id', 25)->unique();
            $table->bigInteger('click_paydoc_id')->nullable();
            $table->unsignedInteger('amount');
            $table->tinyInteger('state');
            $table->smallInteger('reason')->nullable();

            $table->timestamp('payment_time');
            $table->timestamp('create_time');
            $table->timestamp('perform_time')->nullable();
            $table->timestamp('cancel_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
