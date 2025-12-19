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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();

            $table->foreignId('cashier_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_discount', 15, 2)->default(0);

            $table->enum('payment_method', ['cash', 'transfer', 'e_wallet'])
                ->default('cash');

            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('change_amount', 15, 2)->default(0);

            $table->timestamps();

            $table->index(['cashier_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::dropIfExists('sales');
    }
};
