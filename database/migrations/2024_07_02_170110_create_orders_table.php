<?php

use App\Models\Order;
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
            $table->foreignId('user_id');
            $table->morphs('billable');
            $table->integer('subtotal');
            $table->integer('total');
            $table->tinyInteger('discount')->unsigned()->nullable();
            $table->tinyInteger('state')->default(Order::STATE_CREATED);
            $table->timestamps();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users');
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
