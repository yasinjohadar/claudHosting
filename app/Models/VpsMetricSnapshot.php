<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VpsMetricSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'vps_server_id',
        'cpu_percent',
        'ram_percent',
        'disk_percent',
        'load_1',
        'net_rx_bps',
        'net_tx_bps',
        'containers_count',
        'payload',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'cpu_percent' => 'float',
            'ram_percent' => 'float',
            'disk_percent' => 'float',
            'load_1' => 'float',
            'net_rx_bps' => 'float',
            'net_tx_bps' => 'float',
            'containers_count' => 'integer',
            'payload' => 'array',
            'recorded_at' => 'datetime',
        ];
    }

    public function vpsServer(): BelongsTo
    {
        return $this->belongsTo(VpsServer::class);
    }
}
