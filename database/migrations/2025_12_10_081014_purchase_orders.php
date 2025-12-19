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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('code')->unique();
            $table->enum('status', ['draft', 'ordered', 'received', 'cancelled'])
                ->default('draft');

            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('received_at')->nullable();

            $table->decimal('total_amount', 15, 2)->default(0);

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->index(['supplier_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
