<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * NOTE: product_categories table is created first (000002 runs after 000001 alphabetically,
     * so the FK is deferred — but to be safe this migration adds category_id WITHOUT a strict FK
     * and the FK constraint is added after product_categories exists via the boot order).
     * Since Laravel sorts migrations by filename, 000001 < 000002, so we cannot reference
     * product_categories here. The FK on category_id is therefore added as a plain nullable column
     * and constrained in a later alter migration (000006).
     *
     * Products table schema.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                  ->constrained('stores')
                  ->cascadeOnDelete();

            // category_id FK added after product_categories table exists (migration 000006)
            $table->unsignedBigInteger('category_id')->nullable()->index();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('short_description', 500)->nullable();

            // Pricing
            $table->decimal('price', 10, 2);
            $table->decimal('compare_price', 10, 2)->nullable();  // original/crossed-out price
            $table->decimal('cost_price', 10, 2)->nullable();     // for margin calculation

            // Inventory
            $table->integer('stock_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->boolean('track_stock')->default(true);

            // Status
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);

            // Shipping
            $table->decimal('weight', 8, 2)->nullable();
            $table->string('weight_unit', 10)->nullable()->default('kg');

            // SEO
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();

            // AI
            $table->boolean('ai_description_generated')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Composite indexes for common query patterns
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'is_featured']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
