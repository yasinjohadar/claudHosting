<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('storage_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storage_config_id')->constrained('backup_storage_configs')->onDelete('cascade');
            $table->date('date')->comment('التاريخ');
            $table->bigInteger('bytes_stored')->default(0)->comment('البايتات المخزنة');
            $table->bigInteger('bytes_uploaded')->default(0)->comment('البايتات المرفوعة');
            $table->bigInteger('bytes_downloaded')->default(0)->comment('البايتات المنزلة');
            $table->decimal('cost', 10, 2)->default(0)->comment('التكلفة');
            $table->integer('operations_count')->default(0)->comment('عدد العمليات');
            $table->json('metadata')->nullable()->comment('بيانات إضافية');
            $table->timestamps();

            $table->unique(['storage_config_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('storage_analytics');
    }
};
