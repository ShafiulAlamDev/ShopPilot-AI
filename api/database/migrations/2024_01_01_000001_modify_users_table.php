<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('email');
            $table->string('phone')->nullable()->after('avatar');
            $table->boolean('is_active')->default(true)->after('phone');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
            $table->string('invitation_token')->nullable()->after('last_login_at');
            $table->timestamp('invitation_accepted_at')->nullable()->after('invitation_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'avatar', 'phone', 'is_active', 'last_login_at',
                'invitation_token', 'invitation_accepted_at',
            ]);
        });
    }
};
