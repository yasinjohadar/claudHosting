<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whm_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('username', 64)->unique();
            $table->string('domain', 253);
            $table->string('package', 128)->nullable();
            $table->string('status', 32)->default('active');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index('domain');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whm_accounts');
    }
};
