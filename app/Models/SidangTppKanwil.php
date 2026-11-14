<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SidangTppKanwil extends Model
{
    use HasFactory;

    protected $table = 'sidang_tpp_kanwil';

    protected $fillable = [
        'nomor_sidang',
        'tanggal_sidang',
        'jam_mulai',
        'jam_selesai',
        'tempat',
        'metode',
        'link_daring',
        'meeting_id',
        'passcode',
        'nomor_nota_dinas',
        'tanggal_nota_dinas',
        'file_nota_dinas',
        'agenda_kategori',
        'agenda_judul',
        'agenda_deskripsi',
        'file_bahan_sidang',
        'ketua_id',
        'ketua_nama',
        'ketua_nip',
        'ketua_jabatan',
        'sekretaris_id',
        'sekretaris_nama',
        'sekretaris_nip',
        'sekretaris_jabatan',
        'total_anggota',
        'total_hadir',
        'total_setuju',
        'total_setuju_catatan',
        'total_tolak',
        'total_tunda',
        'kesimpulan_sidang',
        'nomor_berita_acara',
        'file_ba_draft',
        'file_ba_signed',
        'tanggapan_kakanwil_status',
        'tanggapan_kakanwil_catatan',
        'tanggapan_kakanwil_tanggal',
        'kakanwil_nama',
        'kakanwil_nip',
        'file_disposisi_kakanwil',
        'status_sidang',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_sidang' => 'date',
            'tanggal_nota_dinas' => 'date',
            'tanggapan_kakanwil_tanggal' => 'date',
            'total_anggota' => 'integer',
            'total_hadir' => 'integer',
            'total_setuju' => 'integer',
            'total_setuju_catatan' => 'integer',
            'total_tolak' => 'integer',
            'total_tunda' => 'integer',
        ];
    }

    public function anggota(): HasMany
    {
        return $this->hasMany(SidangTppKanwilAnggota::class, 'sidang_tpp_kanwil_id')->orderBy('id');
    }

    public function ketuaPegawai(): BelongsTo
    {
        return $this->belongsTo(KanwilPegawai::class, 'ketua_id');
    }

    public function sekretarisPegawai(): BelongsTo
    {
        return $this->belongsTo(KanwilPegawai::class, 'sekretaris_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Hitung ulang agregasi kehadiran dan pendapat dari tabel anggota.
     */
    public function recalculateStats(): void
    {
        $this->total_anggota = $this->anggota()->count();
        $this->total_hadir = $this->anggota()->whereIn('status_kehadiran', ['hadir_luring', 'hadir_daring'])->count();
        $this->total_setuju = $this->anggota()->where('status_pendapat', 'setuju')->count();
        $this->total_setuju_catatan = $this->anggota()->where('status_pendapat', 'setuju_catatan')->count();
        $this->total_tolak = $this->anggota()->where('status_pendapat', 'tolak')->count();
        $this->total_tunda = $this->anggota()->where('status_pendapat', 'tunda')->count();
        $this->save();
    }

    /**
     * Badge status pelaksanaan sidang.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status_sidang) {
            'draft' => '<span class="badge bg-secondary"><i class="fas fa-edit me-1"></i>Draf</span>',
            'undangan_terbit' => '<span class="badge bg-info text-dark"><i class="fas fa-envelope-open-text me-1"></i>Undangan Terbit</span>',
            'berlangsung' => '<span class="badge bg-primary text-white"><i class="fas fa-comments me-1"></i>Berlangsung</span>',
            'menunggu_kakanwil' => '<span class="badge bg-warning text-dark"><i class="fas fa-user-clock me-1"></i>Menunggu Kakanwil</span>',
            'selesai' => '<span class="badge bg-success text-white"><i class="fas fa-check-circle me-1"></i>Selesai</span>',
            default => '<span class="badge bg-secondary">-</span>',
        };
    }

    /**
     * Badge status tanggapan Kakanwil.
     */
    public function getTanggapanBadgeAttribute(): string
    {
        return match ($this->tanggapan_kakanwil_status) {
            'disetujui' => '<span class="badge bg-success text-white"><i class="fas fa-check-double me-1"></i>Disetujui</span>',
            'disetujui_bersyarat' => '<span class="badge bg-info text-white"><i class="fas fa-clipboard-check me-1"></i>Disetujui dg Catatan</span>',
            'ditolak' => '<span class="badge bg-danger text-white"><i class="fas fa-times-circle me-1"></i>Ditolak</span>',
            'perlu_pendalaman' => '<span class="badge bg-warning text-dark"><i class="fas fa-search me-1"></i>Perlu Pendalaman</span>',
            default => '<span class="badge bg-light text-muted border"><i class="fas fa-clock me-1"></i>Menunggu Disposisi</span>',
        };
    }
}
