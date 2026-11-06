<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WaBroadcastLog extends Model
{
    use HasFactory;

    protected $table = 'wa_broadcast_logs';

    protected $fillable = [
        'broadcast_id',
        'user_id',
        'nama_penerima',
        'no_wa',
        'role',
        'nama_upt',
        'status',
        'response_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(WaBroadcast::class, 'broadcast_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
