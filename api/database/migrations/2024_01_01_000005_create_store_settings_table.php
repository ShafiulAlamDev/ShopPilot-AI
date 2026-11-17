<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->longText('value')->nullable();
            $table->string('type')->default('string')
                  ->comment('string, integer, boolean, json, encrypted');
            $table->timestamps();

            $table->unique(['store_id', 'key']);
            $table->index('store_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
