<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_embeddings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                  ->unique()
                  ->constrained('products')
                  ->cascadeOnDelete();

            // JSON-encoded float array (1536 dims for text-embedding-3-small)
            $table->longText('embedding');

            $table->string('embedding_model')->default('text-embedding-3-small');

            // The text that was embedded (used for cache invalidation)
            $table->text('embedded_text');

            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_embeddings');
    }
};
