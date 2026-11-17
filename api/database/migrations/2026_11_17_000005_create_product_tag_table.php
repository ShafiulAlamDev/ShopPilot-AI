<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Pivot table linking products to product_tags (many-to-many).
     */
    public function up(): void
    {
        Schema::create('product_tag', function (Blueprint $table) {
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();

            $table->foreignId('tag_id')
                  ->constrained('product_tags')
                  ->cascadeOnDelete();

            $table->primary(['product_id', 'tag_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_tag');
    }
};
