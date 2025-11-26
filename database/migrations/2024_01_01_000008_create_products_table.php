<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->unique();
            $table->string('barcode')->nullable();
            $table->enum('kind', ['good', 'service']);
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('brand_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('unit_id')->constrained()->onDelete('cascade');
            $table->decimal('weight', 8, 2)->nullable();
            $table->json('dimensions')->nullable();
            $table->boolean('track_stock')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_expiry')->default(false);
            $table->boolean('requires_batch')->default(false);
            $table->enum('picking_policy', ['fifo', 'lifo', 'fefo'])->default('fifo');
            $table->decimal('min_stock', 8, 2)->default(0);
            $table->decimal('max_stock', 8, 2)->nullable();
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->integer('total_sold')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabela para imagens dos produtos
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('image_path');
            $table->boolean('is_primary')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Tabela para preços dos produtos
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('branch_id')->nullable()->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->decimal('compare_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_to')->nullable();
            $table->timestamps();
            
            $table->unique(['product_id', 'branch_id']);
        });

        // Tabela para embalagens dos produtos
        Schema::create('product_packaging', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('barcode')->nullable();
            $table->decimal('conversion_factor', 8, 3);
            $table->decimal('price', 10, 2)->nullable();
            $table->decimal('weight', 8, 2)->nullable();
            $table->integer('min_quantity')->default(1);
            $table->integer('max_quantity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_packaging');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
    }
};