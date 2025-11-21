<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();

            // Relasi ke order & user
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Info dari payment gateway
            $table->string('gateway_transaction_id')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_channel', 50)->nullable();

            // Nominal
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('IDR');

            // Status transaksi
            $table->enum('status', ['pending', 'success', 'failed', 'expired', 'refunded'])
                ->default('pending');

            // Tokenisasi & signature
            $table->string('payment_token')->nullable();
            $table->string('signature')->nullable();

            // Response dari gateway
            $table->json('callback_response')->nullable();

            // Keamanan & tracking
            $table->string('ip_address', 50)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();
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
