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
        'subjek_tipe',
        'mutasi_id',
        'narapidana_data',
        'upt_tujuan_rekomendasi_id',
        'is_klien_bapas',
        'klien_data',
        'dokumen_custom',
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
        'nomor_risalah',
        'nomor_laporan',
        'file_ba_draft',
        'file_ba_signed',
        'foto_dokumentasi_1',
        'foto_dokumentasi_2',
        'file_risalah_signed',
        'file_laporan_signed',
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
            'is_klien_bapas' => 'boolean',
            'klien_data' => 'array',
            'narapidana_data' => 'array',
            'dokumen_custom' => 'array',
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

    public function materis(): HasMany
    {
        return $this->hasMany(SidangTppKanwilMateri::class, 'sidang_tpp_kanwil_id')->orderBy('urutan');
    }

    public function getTotalMateriAttribute(): int
    {
        return $this->materis->count();
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

    public function mutasi(): BelongsTo
    {
        return $this->belongsTo(MutasiNarapidana::class, 'mutasi_id');
    }

    public function uptTujuanRekomendasi(): BelongsTo
    {
        return $this->belongsTo(Upt::class, 'upt_tujuan_rekomendasi_id');
    }

    public function isMutasiKeinginanSendiri(): bool
    {
        return $this->subjek_tipe === 'mutasi_keinginan_sendiri'
            || $this->agenda_kategori === 'mutasi_keinginan_sendiri'
            || $this->materis->contains(fn ($m) => $m->isMutasiKeinginanSendiri());
    }

    public function isMutasiNarapidana(): bool
    {
        return in_array($this->subjek_tipe, ['mutasi_narapidana', 'mutasi_keinginan_sendiri'])
            || in_array($this->agenda_kategori, ['mutasi_narapidana', 'mutasi_keinginan_sendiri'])
            || ! empty($this->mutasi_id)
            || $this->materis->contains(fn ($m) => $m->isMutasiNarapidana());
    }

    public function isIzinBerobat(): bool
    {
        return $this->subjek_tipe === 'izin_berobat'
            || $this->materis->contains(fn ($m) => $m->isIzinBerobat());
    }

    public function isKlienBapas(): bool
    {
        return $this->is_klien_bapas
            || $this->subjek_tipe === 'klien_umroh'
            || $this->materis->contains(fn ($m) => $m->isKlienBapas());
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
            'draft' => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="fas fa-edit me-1"></i>Draf</span>',
            'undangan_terbit' => '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="fas fa-envelope-open-text me-1"></i>Undangan Terbit</span>',
            'berlangsung' => '<span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="fas fa-comments me-1"></i>Berlangsung</span>',
            'menunggu_kakanwil' => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="fas fa-user-clock me-1"></i>Menunggu Kakanwil</span>',
            'selesai' => '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-check-circle me-1"></i>Selesai</span>',
            default => '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">-</span>',
        };
    }

    /**
     * Badge status tanggapan Kakanwil.
     */
    public function getTanggapanBadgeAttribute(): string
    {
        return match ($this->tanggapan_kakanwil_status) {
            'disetujui' => '<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fas fa-check-double me-1"></i>Disetujui</span>',
            'disetujui_bersyarat' => '<span class="badge bg-info-subtle text-info border border-info-subtle"><i class="fas fa-clipboard-check me-1"></i>Disetujui dg Catatan</span>',
            'ditolak' => '<span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fas fa-times-circle me-1"></i>Ditolak</span>',
            'perlu_pendalaman' => '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="fas fa-search me-1"></i>Perlu Pendalaman</span>',
            default => '<span class="badge bg-light text-muted border"><i class="fas fa-clock me-1"></i>Menunggu Disposisi</span>',
        };
    }

    /**
     * Cek apakah Sidang TPP Kanwil sudah diverifikasi terakhir / mendapat tanggapan final dari Ka Kanwil.
     */
    public function isVerifiedKakanwil(): bool
    {
        return $this->status_sidang === 'selesai'
            || in_array($this->tanggapan_kakanwil_status, ['disetujui', 'disetujui_bersyarat', 'ditolak', 'perlu_pendalaman'], true)
            || ! empty($this->file_disposisi_kakanwil);
    }

    /**
     * Cek apakah user berwenang merubah data sidang ini.
     * User yang menginput masih bisa merubah selama belum diverifikasi terakhir Ka Kanwil.
     * Jika sudah diverifikasi terakhir Ka Kanwil, data terkunci kecuali untuk Administrator.
     */
    public function canEdit(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $hasKanwilAccess = $user->hasAccess('sidang_tpp_kanwil', 'full')
            || ($user->hasAccess('sidang_tpp', 'full') && $user->isKanwil());

        if (! $user->isKanwil() || ! $hasKanwilAccess) {
            return false;
        }

        // Terkunci pasca-verifikasi Ka Kanwil untuk non-admin
        if ($this->isVerifiedKakanwil()) {
            return false;
        }

        // User pembuat data dapat merubah
        return empty($this->created_by) || (int) $this->created_by === (int) $user->id;
    }

    /**
     * Cek apakah user berwenang menghapus sidang ini.
     * Sebelum verifikasi Ka Kanwil: Admin dan User yang menginput dapat menghapus.
     * Setelah verifikasi Ka Kanwil: Tidak bisa dihapus, KECUALI Administrator.
     */
    public function canDelete(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        // Administrator selalu berwenang menghapus (termasuk override pasca-verifikasi Ka Kanwil)
        if ($user->isAdmin()) {
            return true;
        }

        $hasKanwilAccess = $user->hasAccess('sidang_tpp_kanwil', 'full')
            || ($user->hasAccess('sidang_tpp', 'full') && $user->isKanwil());

        if (! $user->isKanwil() || ! $hasKanwilAccess) {
            return false;
        }

        // Jika sudah diverifikasi terakhir Ka Kanwil, tidak bisa dihapus kecuali Admin
        if ($this->isVerifiedKakanwil()) {
            return false;
        }

        // Sebelum verifikasi Ka Kanwil, user yang menginput dapat menghapus
        return ! empty($this->created_by) && (int) $this->created_by === (int) $user->id;
    }
}
