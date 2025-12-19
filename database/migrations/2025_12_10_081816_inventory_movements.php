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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('type', ['IN', 'OUT', 'ADJUST']);
            $table->string('reference_type'); // 'purchase','sale','adjustment'
            $table->unsignedBigInteger('reference_id')->nullable();

            // quantity bisa plus (IN / ADJUST UP) atau minus (OUT / ADJUST DOWN)
            $table->integer('quantity');

            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
