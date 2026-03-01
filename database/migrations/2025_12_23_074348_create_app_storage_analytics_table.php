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
        Schema::create('app_storage_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('storage_config_id')->constrained('app_storage_configs')->onDelete('cascade');
            $table->date('date');
            $table->bigInteger('bytes_stored')->default(0);
            $table->bigInteger('bytes_uploaded')->default(0);
            $table->bigInteger('bytes_downloaded')->default(0);
            $table->decimal('cost', 10, 2)->default(0);
            $table->integer('operations_count')->default(0);
            $table->string('file_type')->nullable()->comment('نوع الملف');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['storage_config_id', 'date', 'file_type']);
            $table->index('date');
            $table->index('file_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_storage_analytics');
    }
};
