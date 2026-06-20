<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vps_metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vps_server_id')->constrained('vps_servers')->cascadeOnDelete();
            $table->decimal('cpu_percent', 5, 1)->default(0);
            $table->decimal('ram_percent', 5, 1)->default(0);
            $table->decimal('disk_percent', 5, 1)->default(0);
            $table->decimal('load_1', 8, 2)->nullable();
            $table->decimal('net_rx_bps', 16, 0)->nullable();
            $table->decimal('net_tx_bps', 16, 0)->nullable();
            $table->unsignedSmallInteger('containers_count')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('recorded_at');
            $table->index(['vps_server_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vps_metric_snapshots');
    }
};
