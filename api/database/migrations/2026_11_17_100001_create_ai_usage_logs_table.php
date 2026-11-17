<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                  ->constrained('organizations')
                  ->cascadeOnDelete();

            $table->foreignId('store_id')
                  ->nullable()
                  ->constrained('stores')
                  ->nullOnDelete();

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->string('feature');  // e.g. product_description, semantic_search, shopping_assistant, comparison, support
            $table->string('model');    // e.g. gpt-4o, text-embedding-3-small

            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->integer('total_tokens')->default(0);

            $table->decimal('cost_usd', 10, 6)->nullable();
            $table->integer('duration_ms')->nullable();

            $table->enum('status', ['success', 'error'])->default('success');
            $table->text('error_message')->nullable();

            $table->timestamps();

            // Indexes for common query patterns
            $table->index(['organization_id', 'feature']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
