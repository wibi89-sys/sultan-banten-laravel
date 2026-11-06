<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WaBroadcast extends Model
{
    use HasFactory;

    protected $table = 'wa_broadcasts';

    protected $fillable = [
        'pengumuman_id',
        'judul',
        'kategori',
        'pesan',
        'target_type',
        'target_roles',
        'target_upt_ids',
        'kirim_ke_grup',
        'total_target',
        'total_sent',
        'total_failed',
        'status',
        'created_by',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'target_roles' => 'array',
            'target_upt_ids' => 'array',
            'kirim_ke_grup' => 'boolean',
            'sent_at' => 'datetime',
            'total_target' => 'integer',
            'total_sent' => 'integer',
            'total_failed' => 'integer',
        ];
    }

    public function pengumuman(): BelongsTo
    {
        return $this->belongsTo(Pengumuman::class, 'pengumuman_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WaBroadcastLog::class, 'broadcast_id');
    }
}
