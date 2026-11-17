<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
            $table->string('email');
            $table->string('role')->default('store_manager');
            $table->string('token')->unique();
            $table->string('status')->default('pending')
                  ->comment('pending, accepted, expired, cancelled');
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['token', 'status']);
            $table->index(['email', 'organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_invitations');
    }
};
