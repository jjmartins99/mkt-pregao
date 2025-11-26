<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('reserved_quantity', 12, 3)->default(0);
            $table->decimal('available_quantity', 12, 3)->default(0);
            $table->decimal('min_stock', 12, 3)->default(0);
            $table->decimal('max_stock', 12, 3)->nullable();
            $table->timestamps();
            
            $table->unique(['product_id', 'warehouse_id']);
        });

        // Tabela para lotes
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->string('batch_number');
            $table->date('expiry_date')->nullable();
            $table->decimal('quantity', 12, 3)->default(0);
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->timestamp('production_date')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->unique(['product_id', 'warehouse_id', 'batch_number']);
        });

        // Tabela para movimentos de stock
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('batch_id')->nullable()->constrained('stock_batches')->onDelete('set null');
            $table->enum('type', ['IN', 'OUT', 'ADJUSTMENT', 'TRANSFER_IN', 'TRANSFER_OUT']);
            $table->decimal('quantity', 12, 3);
            $table->decimal('balance', 12, 3);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('stock_batches');
        Schema::dropIfExists('stocks');
    }
};