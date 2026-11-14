<?php

namespace Tests\Feature;

use App\Models\KanwilPegawai;
use App\Models\MutasiNarapidana;
use App\Models\MutasiNarapidanaTujuan;
use App\Models\SidangTpp;
use App\Models\SidangTppKanwil;
use App\Models\SidangTppKanwilAnggota;
use App\Models\SidangTppKanwilMateri;
use App\Models\SidangTppKanwilMateriPendapat;
use App\Models\SidangTppUsulan;
use App\Models\TppKanwilSetting;
use App\Models\Upt;
use App\Models\User;
use App\Services\GeminiAiService;
use App\Services\TppKanwilDokumenService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SidangTppKanwilDokumenAiTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userKanwil;

    protected SidangTppKanwil $sidang;

    protected function setUp(): void
    {
        parent::setUp();

        // Siapkan user kantor wilayah dengan hak akses penuh
        $this->userKanwil = User::whereNull('upt_id')->where('role', 'operator_wilayah')->first()
            ?? User::factory()->create(['upt_id' => null, 'role' => 'operator_wilayah']);
        $this->userKanwil->role = 'operator_wilayah';
        $this->userKanwil->akses_sidang_tpp = true;
        $this->userKanwil->akses_sidang_tpp_kanwil = true;
        $this->userKanwil->save();

        // Siapkan data sidang TPP Kanwil
        $this->sidang = SidangTppKanwil::create([
            'nomor_sidang' => 'TPP-KW/TEST/'.date('Ymd_His'),
            'nomor_nota_dinas' => 'W.12-UM.01.01-TEST',
            'nomor_berita_acara' => 'BA-TPP-KW/TEST-001',
            'nomor_risalah' => 'WP.12.PK.06.05-TEST',
            'nomor_laporan' => 'WP.12.PK.06.06-TEST',
            'tanggal_sidang' => Carbon::now()->addDays(2),
            'jam_mulai' => '09:00:00',
            'jam_selesai' => '11:30:00',
            'tempat' => 'Ruang Rapat Divisi Pemasyarakatan Kanwil Banten',
            'metode' => 'luring',
            'agenda_kategori' => 'Izin Luar Negeri Klien Bapas',
            'agenda_judul' => 'Pembahasan Izin Umroh Klien PB Bapas Serang',
            'agenda_deskripsi' => 'Pengusulan izin ke luar negeri dalam rangka ibadah umroh bagi klien pemasyarakatan.',
            'status_sidang' => 'berlangsung',
            'ketua_nama' => 'Junaedi, S.H.',
            'ketua_nip' => '197501011999031001',
            'ketua_jabatan' => 'Ketua Sidang TPP',
            'sekretaris_nama' => 'Budi Santoso, S.Sos.',
            'sekretaris_nip' => '198002022005011002',
            'sekretaris_jabatan' => 'Sekretaris Sidang TPP',
            'total_anggota' => 2,
            'total_hadir' => 2,
            'total_setuju' => 2,
            'is_klien_bapas' => true,
            'klien_data' => [
                'nama_klien' => 'Ahmad Fulan',
                'bin' => 'Fulan bin Fulan',
                'keperluan_ln' => 'Ibadah Umroh',
                'negara_tujuan' => 'Kerajaan Arab Saudi',
                'bapas_nama' => 'Bapas Kelas I Serang',
            ],
            'created_by' => $this->userKanwil->id,
        ]);

        SidangTppKanwilAnggota::create([
            'sidang_tpp_kanwil_id' => $this->sidang->id,
            'nama' => 'H. Junaedi, S.H.',
            'nip' => '197501011999031001',
            'jabatan' => 'Ketua Sidang TPP',
            'peran_sidang' => 'Ketua',
            'status_kehadiran' => 'hadir_luring',
            'status_pendapat' => 'setuju',
        ]);
    }

    /**
     * Memastikan seluruh naskah dinas menggunakan huruf Arial / Helvetica dan tidak lagi menggunakan Times New Roman.
     */
    public function test_dokumen_tpp_kanwil_uses_arial_font_and_standard_ditjenpas_kop(): void
    {
        $baHtml = TppKanwilDokumenService::renderBeritaAcaraHtml($this->sidang);
        $risalahHtml = TppKanwilDokumenService::renderRisalahHtml($this->sidang);
        $laporanHtml = TppKanwilDokumenService::renderLaporanHtml($this->sidang);
        $notaHtml = TppKanwilDokumenService::renderNotaDinasHtml($this->sidang);

        // Pastikan font standar sans-serif (Arial / Helvetica) digunakan
        $this->assertStringContainsString('Arial, Helvetica, sans-serif', $baHtml);
        $this->assertStringContainsString('Arial, Helvetica, sans-serif', $risalahHtml);
        $this->assertStringContainsString('Arial, Helvetica, sans-serif', $laporanHtml);
        $this->assertStringContainsString('Arial, Helvetica, sans-serif', $notaHtml);

        // Pastikan tidak ada lagi Times New Roman
        $this->assertStringNotContainsString('Times New Roman', $baHtml);
        $this->assertStringNotContainsString('Times New Roman', $risalahHtml);
        $this->assertStringNotContainsString('Times New Roman', $laporanHtml);
        $this->assertStringNotContainsString('Times New Roman', $notaHtml);

        // Pastikan Kop surat standar Kementerian Imigrasi dan Pemasyarakatan Ditjenpas
        $this->assertStringContainsString('KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA', $baHtml);
        $this->assertStringContainsString('KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA', $risalahHtml);
        $this->assertStringContainsString('KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA', $laporanHtml);
        $this->assertStringContainsString('KEMENTERIAN IMIGRASI DAN PEMASYARAKATAN REPUBLIK INDONESIA', $notaHtml);

        // Border tunggal kop resmi 2px
        $this->assertStringContainsString('border-bottom:2px solid #000;', $baHtml);
        $this->assertStringContainsString('border-bottom:2px solid #000;', $risalahHtml);
        $this->assertStringContainsString('border-bottom:2px solid #000;', $laporanHtml);
        $this->assertStringContainsString('border-bottom:2px solid #000;', $notaHtml);
    }

    /**
     * Memastikan AI Service naskah dinas dapat men-generate struktur naskah Laporan, Risalah, dan BA.
     */
    public function test_ai_generate_naskah_service_returns_structured_content(): void
    {
        $lap = GeminiAiService::generateNaskahSidangTppKanwil([
            'jenis_dokumen' => 'laporan',
            'sidang' => $this->sidang,
            'instruksi_khusus' => 'Fokuskan pada asesmen risiko rendah.',
        ]);
        $this->assertTrue($lap['success']);
        $this->assertNotEmpty($lap['data']['kegiatan_dilaksanakan']);
        $this->assertNotEmpty($lap['data']['hasil_dicapai']);

        $ris = GeminiAiService::generateNaskahSidangTppKanwil([
            'jenis_dokumen' => 'risalah',
            'sidang' => $this->sidang,
        ]);
        $this->assertTrue($ris['success']);
        $this->assertNotEmpty($ris['data']['materi_sidang']);
        $this->assertNotEmpty($ris['data']['hasil_sidang']);

        $ba = GeminiAiService::generateNaskahSidangTppKanwil([
            'jenis_dokumen' => 'berita_acara',
            'sidang' => $this->sidang,
        ]);
        $this->assertTrue($ba['success']);
        $this->assertNotEmpty($ba['data']['uraian_sidang']);
        $this->assertNotEmpty($ba['data']['kesimpulan_rekomendasi']);
    }

    /**
     * Memastikan kustomisasi teks (dokumen_custom) langsung diterapkan pada output HTML dokumen.
     */
    public function test_dokumen_custom_overrides_rendered_in_laporan_and_risalah(): void
    {
        $customTextKegiatan = 'Narasi kustom kegiatan sidang TPP Kanwil diselaraskan khusus untuk klien khusus.';
        $customHasilItem = 'Poin khusus hasil sidang yang disetujui pimpinan tingkat tinggi.';

        $this->sidang->dokumen_custom = [
            'laporan' => [
                'kegiatan_dilaksanakan' => $customTextKegiatan,
                'hasil_dicapai' => [$customHasilItem],
                'simpulan' => ['Simpulan kustom A'],
                'saran' => ['Saran kustom B'],
            ],
            'risalah' => [
                'materi_sidang' => ['Materi kustom persidangan TPP Kanwil.'],
                'hasil_sidang' => ['Keputusan kustom persidangan TPP.'],
            ],
            'berita_acara' => [
                'uraian_sidang' => 'Uraian BA kustom dari AI asisten.',
                'kesimpulan_rekomendasi' => 'Kesimpulan mufakat kustom BA.',
            ],
        ];
        $this->sidang->save();

        $laporanHtml = TppKanwilDokumenService::renderLaporanHtml($this->sidang);
        $risalahHtml = TppKanwilDokumenService::renderRisalahHtml($this->sidang);
        $baHtml = TppKanwilDokumenService::renderBeritaAcaraHtml($this->sidang);

        $this->assertStringContainsString($customTextKegiatan, $laporanHtml);
        $this->assertStringContainsString($customHasilItem, $laporanHtml);
        $this->assertStringContainsString('Materi kustom persidangan TPP Kanwil.', $risalahHtml);
        $this->assertStringContainsString('Keputusan kustom persidangan TPP.', $risalahHtml);
        $this->assertStringContainsString('Uraian BA kustom dari AI asisten.', $baHtml);
        $this->assertStringContainsString('Kesimpulan mufakat kustom BA.', $baHtml);
    }

    /**
     * Memastikan endpoint AI (generate, save, reset) di controller berfungsi dengan otentikasi Kanwil.
     */
    public function test_controller_ai_generate_save_and_reset_endpoints(): void
    {
        // 1. Test Generate Endpoint
        $resGen = $this->actingAs($this->userKanwil)
            ->postJson(route('sidang-tpp-kanwil.ai-generate-naskah', $this->sidang->id), [
                'jenis_dokumen' => 'laporan',
                'instruksi_khusus' => 'Uji coba endpoint generate',
            ]);
        $resGen->assertOk();
        $resGen->assertJsonPath('success', true);

        // 2. Test Save Endpoint
        $resSave = $this->actingAs($this->userKanwil)
            ->postJson(route('sidang-tpp-kanwil.ai-save-naskah', $this->sidang->id), [
                'jenis_dokumen' => 'laporan',
                'data' => [
                    'kegiatan_dilaksanakan' => 'Teks tersimpan melalui API AJAX',
                    'hasil_dicapai' => ['Poin hasil 1', 'Poin hasil 2'],
                    'simpulan' => ['Simpulan 1'],
                    'saran' => ['Saran 1'],
                ],
            ]);
        $resSave->assertOk();
        $resSave->assertJsonPath('success', true);

        $this->sidang->refresh();
        $this->assertEquals('Teks tersimpan melalui API AJAX', $this->sidang->dokumen_custom['laporan']['kegiatan_dilaksanakan']);

        // 3. Test Reset Endpoint
        $resReset = $this->actingAs($this->userKanwil)
            ->postJson(route('sidang-tpp-kanwil.ai-reset-naskah', $this->sidang->id), [
                'jenis_dokumen' => 'laporan',
            ]);
        $resReset->assertOk();
        $resReset->assertJsonPath('success', true);

        $this->sidang->refresh();
        $this->assertNull($this->sidang->dokumen_custom);
    }

    /**
     * Memastikan Form Tab 3 (Format Naskah & Dasar Hukum) dapat disimpan tanpa error validasi kakanwil_nama/kakanwil_jabatan.
     */
    public function test_setting_naskah_tab_can_be_saved_without_kakanwil_fields(): void
    {
        $response = $this->actingAs($this->userKanwil)
            ->post(route('sidang-tpp-kanwil.setting.update'), [
                'setting_section' => 'naskah',
                'nomor_nota_prefix' => 'WP.12.TEST.NOTA-',
                'nomor_risalah_prefix' => 'WP.12.TEST.RIS-',
                'nomor_laporan_prefix' => 'WP.12.TEST.LAP-',
                'tempat_sidang_default' => 'Ruang Sidang Baru',
                'dasar_hukum' => ['UU Baru No. 1', 'Permen Baru No. 2'],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('sidang-tpp-kanwil.setting').'#tab-naskah');

        $setting = TppKanwilSetting::getFirst();
        $this->assertEquals('WP.12.TEST.NOTA-', $setting->nomor_nota_prefix);
        $this->assertEquals('Ruang Sidang Baru', $setting->tempat_sidang_default);
    }

    /**
     * Memastikan Form Tab 2 (Profil & Spesimen Kakanwil) dapat disimpan dengan sukses.
     */
    public function test_setting_kakanwil_tab_can_be_saved_successfully(): void
    {
        $response = $this->actingAs($this->userKanwil)
            ->post(route('sidang-tpp-kanwil.setting.update'), [
                'setting_section' => 'kakanwil',
                'kakanwil_nama' => 'Dody Fahrudin, Bc.IP., S.H., M.H.',
                'kakanwil_nip' => '197205141994031001',
                'kakanwil_pangkat' => 'Pembina Utama Madya (IV/d)',
                'kakanwil_jabatan' => 'Kepala Kantor Wilayah',
                'auto_embed_ttd' => '1',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('sidang-tpp-kanwil.setting').'#tab-kakanwil');

        $setting = TppKanwilSetting::getFirst();
        $this->assertEquals('Dody Fahrudin, Bc.IP., S.H., M.H.', $setting->kakanwil_nama);
        $this->assertTrue((bool) $setting->auto_embed_ttd);
    }

    /**
     * Memastikan user yang menginput data (creator) dapat mengubah dan menghapus sidang sebelum verifikasi Ka Kanwil.
     */
    public function test_creator_can_update_and_delete_sidang_before_kakanwil_verification(): void
    {
        // 1. Creator dapat mengubah data sidang sebelum verifikasi
        $responseUpdate = $this->actingAs($this->userKanwil)
            ->put(route('sidang-tpp-kanwil.update', $this->sidang->id), [
                'nomor_sidang' => $this->sidang->nomor_sidang,
                'agenda_kategori' => 'Izin Luar Negeri Klien Bapas (Revisi)',
                'agenda_judul' => 'Judul Sidang Diperbarui Creator',
                'tanggal_sidang' => '2026-11-20',
                'jam_mulai' => '09:30',
                'jam_selesai' => '12:00',
                'tempat' => 'Ruang Sidang TPP Kanwil Lantai 2',
                'metode' => 'luring',
            ]);

        $responseUpdate->assertSessionHasNoErrors();
        $this->assertEquals('Judul Sidang Diperbarui Creator', $this->sidang->fresh()->agenda_judul);

        // 2. Creator dapat menghapus sidang sebelum verifikasi
        $responseDelete = $this->actingAs($this->userKanwil)
            ->delete(route('sidang-tpp-kanwil.destroy', $this->sidang->id));

        $responseDelete->assertRedirect(route('sidang-tpp-kanwil.index'));
        $this->assertDatabaseMissing('sidang_tpp_kanwil', ['id' => $this->sidang->id]);
    }

    /**
     * Memastikan user lain (bukan creator dan bukan admin) ditolak (403) saat mencoba merubah atau menghapus sidang.
     */
    public function test_other_user_cannot_update_or_delete_sidang(): void
    {
        $otherUser = User::factory()->create([
            'role' => 'operator_wilayah',
            'upt_id' => null,
            'akses_sidang_tpp_kanwil' => true,
        ]);

        // Coba update -> 403
        $responseUpdate = $this->actingAs($otherUser)
            ->put(route('sidang-tpp-kanwil.update', $this->sidang->id), [
                'nomor_sidang' => $this->sidang->nomor_sidang,
                'agenda_kategori' => $this->sidang->agenda_kategori,
                'agenda_judul' => 'Coba bajak sidang',
                'tanggal_sidang' => '2026-11-20',
                'jam_mulai' => '09:00',
                'tempat' => 'Ruang Sidang',
                'metode' => 'luring',
            ]);
        $responseUpdate->assertStatus(403);

        // Coba delete -> 403
        $responseDelete = $this->actingAs($otherUser)
            ->delete(route('sidang-tpp-kanwil.destroy', $this->sidang->id));
        $responseDelete->assertStatus(403);

        $this->assertDatabaseHas('sidang_tpp_kanwil', ['id' => $this->sidang->id]);
    }

    /**
     * Memastikan setelah verifikasi terakhir Ka Kanwil, user penginput TIDAK BISA merubah maupun menghapus.
     */
    public function test_after_kakanwil_verification_creator_cannot_update_or_delete(): void
    {
        // Simulasikan sidang telah selesai dan diverifikasi Ka Kanwil
        $this->sidang->update([
            'status_sidang' => 'selesai',
            'tanggapan_kakanwil_status' => 'disetujui',
            'tanggapan_kakanwil_tanggal' => '2026-11-15',
            'kakanwil_nama' => 'Dody Fahrudin, Bc.IP., S.H., M.H.',
            'file_disposisi_kakanwil' => 'uploads/test_disposisi.pdf',
        ]);

        $this->assertTrue($this->sidang->isVerifiedKakanwil());
        $this->assertFalse($this->sidang->canEdit($this->userKanwil));
        $this->assertFalse($this->sidang->canDelete($this->userKanwil));

        // Coba update oleh creator -> ditolak 403
        $responseUpdate = $this->actingAs($this->userKanwil)
            ->put(route('sidang-tpp-kanwil.update', $this->sidang->id), [
                'nomor_sidang' => $this->sidang->nomor_sidang,
                'agenda_kategori' => $this->sidang->agenda_kategori,
                'agenda_judul' => 'Coba ubah pasca verifikasi',
                'tanggal_sidang' => '2026-11-20',
                'jam_mulai' => '09:00',
                'tempat' => 'Ruang Sidang',
                'metode' => 'luring',
            ]);
        $responseUpdate->assertStatus(403);

        // Coba delete oleh creator -> ditolak 403
        $responseDelete = $this->actingAs($this->userKanwil)
            ->delete(route('sidang-tpp-kanwil.destroy', $this->sidang->id));
        $responseDelete->assertStatus(403);

        $this->assertDatabaseHas('sidang_tpp_kanwil', ['id' => $this->sidang->id]);
    }

    /**
     * Memastikan Admin TETAP BISA merubah dan menghapus sidang sekalipun telah diverifikasi terakhir Ka Kanwil.
     */
    public function test_admin_can_update_and_delete_even_after_kakanwil_verification(): void
    {
        $admin = User::where('role', 'admin')->first()
            ?? User::factory()->create(['role' => 'admin', 'upt_id' => null]);

        $this->sidang->update([
            'status_sidang' => 'selesai',
            'tanggapan_kakanwil_status' => 'disetujui',
            'tanggapan_kakanwil_tanggal' => '2026-11-15',
            'kakanwil_nama' => 'Dody Fahrudin, Bc.IP., S.H., M.H.',
            'file_disposisi_kakanwil' => 'uploads/test_disposisi.pdf',
        ]);

        $this->assertTrue($this->sidang->canEdit($admin));
        $this->assertTrue($this->sidang->canDelete($admin));

        // Admin melakukan update pasca-verifikasi
        $responseUpdate = $this->actingAs($admin)
            ->put(route('sidang-tpp-kanwil.update', $this->sidang->id), [
                'nomor_sidang' => $this->sidang->nomor_sidang,
                'agenda_kategori' => 'Koreksi Kebijakan Pimpinan',
                'agenda_judul' => 'Judul Sidang Dikoreksi Admin',
                'tanggal_sidang' => '2026-11-20',
                'jam_mulai' => '10:00',
                'tempat' => 'Ruang Rapat Utama Kanwil',
                'metode' => 'hybrid',
            ]);
        $responseUpdate->assertSessionHasNoErrors();
        $this->assertEquals('Judul Sidang Dikoreksi Admin', $this->sidang->fresh()->agenda_judul);

        // Admin melakukan hapus pasca-verifikasi (force delete resmi pimpinan)
        $responseDelete = $this->actingAs($admin)
            ->delete(route('sidang-tpp-kanwil.destroy', $this->sidang->id));

        $responseDelete->assertRedirect(route('sidang-tpp-kanwil.index'));
        $this->assertDatabaseMissing('sidang_tpp_kanwil', ['id' => $this->sidang->id]);
    }

    /**
     * Menguji pendaftaran Sidang TPP Kanwil kategori Mutasi Narapidana
     * beserta sinkronisasi otomatis dua arah ke modul Mutasi UPT saat Ka Kanwil mendisposisi.
     */
    public function test_sidang_kanwil_mutasi_narapidana_two_way_sync(): void
    {
        Storage::fake('public');

        $uptAsal = Upt::first() ?? Upt::factory()->create();
        $uptTujuan = Upt::where('id', '!=', $uptAsal->id)->first() ?? Upt::factory()->create();

        $mutasi = MutasiNarapidana::create([
            'nomor_surat' => 'W12.PAS.TEST-MUTASI-'.date('His'),
            'tanggal_surat' => Carbon::now(),
            'upt_asal_id' => $uptAsal->id,
            'kategori_mutasi' => 'Keinginan Sendiri Mendekati Keluarga',
            'keterangan' => 'Usulan pemindahan WBP an. Budi Santoso bin Suwandi',
            'total_usulan' => 1,
            'status' => 'diajukan',
            'created_by' => $this->userKanwil->id,
        ]);

        $pegawaiKetua = KanwilPegawai::first() ?? KanwilPegawai::create([
            'nama' => 'Junaedi, S.H.',
            'nip' => '197501011999031001',
            'jabatan' => 'Ketua Sidang TPP',
            'is_active' => true,
        ]);
        $pegawaiSekretaris = KanwilPegawai::where('id', '!=', $pegawaiKetua->id)->first() ?? KanwilPegawai::create([
            'nama' => 'Budi Santoso, S.Sos.',
            'nip' => '198002022005011002',
            'jabatan' => 'Sekretaris Sidang TPP',
            'is_active' => true,
        ]);

        $nomorSidang = 'STPP-KW/MUTASI/'.date('Ymd_His');
        $dokumenTambahanFile = UploadedFile::fake()->create('surat_keterangan_desa.pdf', 100, 'application/pdf');

        $responseStore = $this->actingAs($this->userKanwil)
            ->post(route('sidang-tpp-kanwil.store'), [
                'nomor_sidang' => $nomorSidang,
                'tanggal_sidang' => date('Y-m-d'),
                'jam_mulai' => '09:00',
                'tempat' => 'Ruang Sidang TPP Kanwil',
                'metode' => 'luring',
                'agenda_kategori' => 'Usulan Mutasi Narapidana Antar-UPT',
                'agenda_judul' => 'Pemindahan WBP an. Budi Santoso',
                'subjek_tipe' => 'mutasi_narapidana',
                'mutasi_id' => $mutasi->id,
                'upt_tujuan_rekomendasi_id' => $uptTujuan->id,
                'mutasi_wbp_nama' => 'Budi Santoso bin Suwandi',
                'mutasi_nomor_register' => 'BI.124/2025',
                'mutasi_perkara' => 'Pencurian Ps 363 KUHP',
                'mutasi_lama_pidana' => '2 Tahun Penjara',
                'mutasi_upt_asal_id' => $uptAsal->id,
                'mutasi_alasan' => 'Keinginan sendiri mendekati keluarga',
                'mutasi_penjamin_nama' => 'Siti Aminah',
                'mutasi_penjamin_hubungan' => 'Istri Kandung',
                'mutasi_penjamin_alamat' => 'Jl. Pandeglang No. 12',
                'mutasi_penjamin_kontak' => '081234567890',
                'dokumen_tambahan_nama' => 'Surat Keterangan Domisili Desa',
                'dokumen_tambahan_file' => $dokumenTambahanFile,
                'ketua_id' => $pegawaiKetua->id,
                'sekretaris_id' => $pegawaiSekretaris->id,
                'anggota_ids' => [$pegawaiKetua->id, $pegawaiSekretaris->id],
            ]);

        $responseStore->assertSessionHasNoErrors();
        $responseStore->assertRedirect();
        $sidangCreated = SidangTppKanwil::where('nomor_sidang', $nomorSidang)->first();
        $this->assertNotNull($sidangCreated);
        $this->assertEquals('mutasi_narapidana', $sidangCreated->subjek_tipe);
        $this->assertEquals($mutasi->id, $sidangCreated->mutasi_id);
        $this->assertEquals($uptTujuan->id, $sidangCreated->upt_tujuan_rekomendasi_id);
        $this->assertEquals('Surat Keterangan Domisili Desa', $sidangCreated->narapidana_data['berkas']['dokumen_tambahan']['nama'] ?? null);

        // Pastikan relasi awal pada mutasi telah terhubung ke sidang
        $this->assertEquals($sidangCreated->id, $mutasi->fresh()->sidang_tpp_kanwil_id);

        // Simulasi Disposisi Kepala Kantor Wilayah: Setujui Rekomendasi Sidang
        $disposisiFile = UploadedFile::fake()->create('disposisi_kakanwil.pdf', 100, 'application/pdf');
        $responseDisposisi = $this->actingAs($this->userKanwil)
            ->post(route('sidang-tpp-kanwil.upload-disposisi-kakanwil', $sidangCreated->id), [
                'tanggapan_kakanwil_status' => 'disetujui',
                'tanggapan_kakanwil_catatan' => 'Disetujui untuk dipindahkan sesuai rekomendasi TPP ke '.$uptTujuan->nama_upt,
                'tanggapan_kakanwil_tanggal' => date('Y-m-d'),
                'kakanwil_nama' => 'Dody Fahrudin, Bc.IP., S.H., M.H.',
                'file_disposisi_kakanwil' => $disposisiFile,
            ]);

        $responseDisposisi->assertSessionHasNoErrors();

        // Verifikasi Two-Way Sync ke Mutasi Narapidana
        $mutasiUpdated = $mutasi->fresh();
        $this->assertEquals('disetujui', $mutasiUpdated->status);
        $this->assertEquals($uptTujuan->id, $mutasiUpdated->upt_tujuan_id);
        $this->assertStringContainsString('DISETUJUI', $mutasiUpdated->catatan_kanwil);
        $this->assertNotNull($mutasiUpdated->file_persetujuan_kanwil);
    }

    /**
     * Menguji pendaftaran Sidang TPP Kanwil agenda Izin Berobat Keluar Kota bagi Narapidana
     * dengan upload dokumen rekam medis dan berkas tambahan lainnya.
     */
    public function test_sidang_kanwil_izin_berobat_narapidana(): void
    {
        Storage::fake('public');

        $uptAsal = Upt::first() ?? Upt::factory()->create();
        $pegawaiKetua = KanwilPegawai::first() ?? KanwilPegawai::create([
            'nama' => 'Junaedi, S.H.',
            'nip' => '197501011999031001',
            'jabatan' => 'Ketua Sidang TPP',
            'is_active' => true,
        ]);
        $pegawaiSekretaris = KanwilPegawai::where('id', '!=', $pegawaiKetua->id)->first() ?? KanwilPegawai::create([
            'nama' => 'Budi Santoso, S.Sos.',
            'nip' => '198002022005011002',
            'jabatan' => 'Sekretaris Sidang TPP',
            'is_active' => true,
        ]);

        $nomorSidang = 'STPP-KW/MEDIS/'.date('Ymd_His');
        $fileRekam = UploadedFile::fake()->create('rekam_medis.pdf', 100, 'application/pdf');
        $fileTambahan = UploadedFile::fake()->create('hasil_rontgen.pdf', 100, 'application/pdf');

        $responseStore = $this->actingAs($this->userKanwil)
            ->post(route('sidang-tpp-kanwil.store'), [
                'nomor_sidang' => $nomorSidang,
                'tanggal_sidang' => date('Y-m-d'),
                'jam_mulai' => '09:00',
                'tempat' => 'Ruang Sidang TPP Kanwil',
                'metode' => 'hybrid',
                'agenda_kategori' => 'Permintaan Izin Berobat Keluar Kota',
                'agenda_judul' => 'Rujukan Medis Narapidana an. Kusnadi ke RSCM Jakarta',
                'subjek_tipe' => 'izin_berobat',
                'berobat_wbp_nama' => 'Kusnadi bin Darman',
                'berobat_nomor_register' => 'BI.88/2024',
                'berobat_perkara' => 'Narkotika Ps 112 UU 35/2009',
                'berobat_upt_asal_id' => $uptAsal->id,
                'berobat_diagnosa' => 'Kanker Hati Stadium III B Perlu Bedah Onkologi',
                'berobat_dokter' => 'dr. Anton, Sp.B(K)Onk',
                'berobat_rs_nama' => 'RSUP Dr. Cipto Mangunkusumo (RSCM)',
                'berobat_rs_kota' => 'Jakarta Pusat',
                'berobat_penjamin_nama' => 'Hj. Rohanah',
                'berobat_penjamin_hubungan' => 'Ibu Kandung',
                'berobat_penjamin_kontak' => '087812345678',
                'file_medis_rekam' => $fileRekam,
                'dokumen_tambahan_nama' => 'Hasil Scan MRI dan Rontgen Toraks',
                'dokumen_tambahan_file' => $fileTambahan,
                'ketua_id' => $pegawaiKetua->id,
                'sekretaris_id' => $pegawaiSekretaris->id,
                'anggota_ids' => [$pegawaiKetua->id, $pegawaiSekretaris->id],
            ]);

        $responseStore->assertSessionHasNoErrors();
        $responseStore->assertRedirect();
        $sidangCreated = SidangTppKanwil::where('nomor_sidang', $nomorSidang)->first();
        $this->assertNotNull($sidangCreated);
        $this->assertEquals('izin_berobat', $sidangCreated->subjek_tipe);
        $this->assertEquals('Kanker Hati Stadium III B Perlu Bedah Onkologi', $sidangCreated->narapidana_data['diagnosa_medis'] ?? null);
        $this->assertEquals('Hasil Scan MRI dan Rontgen Toraks', $sidangCreated->narapidana_data['berkas']['dokumen_tambahan']['nama'] ?? null);

        // Verifikasi output dokumen BA dan Risalah memuat diagnosa dan RS rujukan
        $risalahHtml = TppKanwilDokumenService::renderRisalahHtml($sidangCreated);
        $this->assertStringContainsString('RSUP Dr. Cipto Mangunkusumo (RSCM)', $risalahHtml);
        $this->assertStringContainsString('Kusnadi bin Darman', $risalahHtml);
    }

    /**
     * Menguji penambahan peserta Sidang TPP Satker UPT untuk kategori Mutasi dan Izin Berobat
     * beserta upload dokumen kustom tambahan.
     */
    public function test_peserta_sidang_tpp_upt_mutasi_dan_izin_berobat(): void
    {
        Storage::fake('public');

        $upt = Upt::first() ?? Upt::factory()->create();
        $uptTujuan = Upt::where('id', '!=', $upt->id)->first() ?? Upt::factory()->create();

        $userUpt = User::factory()->create([
            'upt_id' => $upt->id,
            'role' => 'operator',
            'akses_sidang_tpp' => 'full',
        ]);

        $sidangUpt = SidangTpp::create([
            'upt_id' => $upt->id,
            'nomor_undangan' => 'UND-TPP/UPT/TEST/'.date('His'),
            'tanggal_surat' => Carbon::now()->toDateString(),
            'tanggal_sidang' => Carbon::now()->toDateString(),
            'jam_mulai' => '09:00:00',
            'agenda_sidang' => 'Sidang Usulan TPP UPT',
            'ketua_tpp' => 'Ketua TPP Lapas',
            'sekretaris_tpp' => 'Sekretaris TPP Lapas',
            'status_verifikasi_kanwil' => 'menunggu_sidang',
            'total_usulan' => 0,
            'status_sidang' => 'dijadwalkan',
            'created_by' => $userUpt->id,
        ]);

        $fileTambahan = UploadedFile::fake()->create('surat_kades.pdf', 100, 'application/pdf');

        // Tambah peserta kategori Mutasi (Pindah UPT)
        $responsePesertaMutasi = $this->actingAs($userUpt)
            ->post(route('sidang-tpp.peserta.store', $sidangUpt->id), [
                'nama_wbp' => 'Suhendra bin Mamat',
                'nomor_register' => 'BI.201/2024',
                'perkara_pasal' => 'Penipuan Ps 378 KUHP',
                'lama_pidana' => '2 Tahun Penjara',
                'jenis_usulan' => 'Penempatan/Mutasi',
                'subjek_tipe' => 'mutasi_narapidana',
                'upt_tujuan_id' => $uptTujuan->id,
                'alasan_pemindahan' => 'Mendekatkan dengan keluarga penjamin lansia',
                'penjamin_nama' => 'Mamat Suhendar',
                'penjamin_hubungan' => 'Ayah Kandung',
                'penjamin_alamat' => 'Kampung Cibadak Lebak',
                'dokumen_tambahan_nama' => 'Surat Keterangan Miskin dan Lansia Kelurahan',
                'dokumen_tambahan_file' => $fileTambahan,
            ]);

        $responsePesertaMutasi->assertSessionHasNoErrors();
        $responsePesertaMutasi->assertRedirect();
        $usulanMutasi = SidangTppUsulan::where('sidang_tpp_id', $sidangUpt->id)
            ->where('nomor_register', 'BI.201/2024')
            ->first();

        $this->assertNotNull($usulanMutasi);
        $this->assertEquals('Penempatan/Mutasi', $usulanMutasi->jenis_usulan);
        $this->assertEquals('mutasi_narapidana', $usulanMutasi->subjek_tipe);
        $this->assertEquals($uptTujuan->id, $usulanMutasi->upt_tujuan_id);
        $this->assertEquals('Surat Keterangan Miskin dan Lansia Kelurahan', $usulanMutasi->berkas_khusus['dokumen_tambahan']['nama'] ?? null);

        // Tambah peserta kategori Izin Berobat Keluar Kota
        $fileTambahanMedis = UploadedFile::fake()->create('surat_tindakan.pdf', 100, 'application/pdf');
        $responsePesertaBerobat = $this->actingAs($userUpt)
            ->post(route('sidang-tpp.peserta.store', $sidangUpt->id), [
                'nama_wbp' => 'Rahmat Hidayat',
                'nomor_register' => 'BI.305/2024',
                'perkara_pasal' => 'Penggelapan Ps 372 KUHP',
                'lama_pidana' => '1 Tahun 6 Bulan',
                'jenis_usulan' => 'Izin Berobat',
                'subjek_tipe' => 'izin_berobat',
                'diagnosa_medis' => 'Gagal Ginjal Kronis Perlu Hemodialisa Khusus',
                'dokter_penanggung_jawab' => 'dr. Hendra Sp.PD',
                'rs_rujukan_nama' => 'RSUD Banten Serang',
                'rs_rujukan_kota' => 'Serang',
                'penjamin_nama' => 'Dewi Sartika',
                'penjamin_hubungan' => 'Istri',
                'dokumen_tambahan_nama' => 'Surat Persetujuan Tindakan Medis',
                'dokumen_tambahan_file' => $fileTambahanMedis,
            ]);

        $responsePesertaBerobat->assertSessionHasNoErrors();
        $usulanBerobat = SidangTppUsulan::where('sidang_tpp_id', $sidangUpt->id)
            ->where('nomor_register', 'BI.305/2024')
            ->first();

        $this->assertNotNull($usulanBerobat);
        $this->assertEquals('Izin Berobat', $usulanBerobat->jenis_usulan);
        $this->assertEquals('izin_berobat', $usulanBerobat->subjek_tipe);
        $this->assertEquals('Gagal Ginjal Kronis Perlu Hemodialisa Khusus', $usulanBerobat->diagnosa_medis);
    }

    /**
     * Memastikan pembuatan Sidang TPP Kanwil mendukung multi-materi (array materis).
     */
    public function test_create_sidang_kanwil_with_multiple_materis(): void
    {
        $pegawaiKetua = KanwilPegawai::first() ?? KanwilPegawai::create([
            'nama' => 'Ketua Test',
            'nip' => '198001012000011001',
            'jabatan' => 'Kadivpas',
            'peran_tpp' => 'Ketua',
        ]);
        $pegawaiSekretaris = KanwilPegawai::where('id', '!=', $pegawaiKetua->id)->first() ?? KanwilPegawai::create([
            'nama' => 'Sekretaris Test',
            'nip' => '198201012000011002',
            'jabatan' => 'Kasubbid',
            'peran_tpp' => 'Sekretaris',
        ]);
        $pegawaiAnggota = KanwilPegawai::create([
            'nama' => 'Anggota Test 1',
            'nip' => '198501012000011003',
            'jabatan' => 'Pelaksana',
            'peran_tpp' => 'Anggota',
        ]);

        $upt1 = Upt::first() ?? Upt::create(['nama_upt' => 'Lapas Kelas IIA Serang']);
        $upt2 = Upt::where('id', '!=', $upt1->id)->first() ?? Upt::create(['nama_upt' => 'Lapas Kelas IIA Cilegon']);

        $response = $this->actingAs($this->userKanwil)
            ->post(route('sidang-tpp-kanwil.store'), [
                'nomor_sidang' => 'STPP-KW/MULTI/'.uniqid(),
                'tanggal_sidang' => '2026-11-20',
                'jam_mulai' => '09:00',
                'tempat' => 'Ruang Sidang Utama',
                'metode' => 'luring',
                'ketua_id' => $pegawaiKetua->id,
                'sekretaris_id' => $pegawaiSekretaris->id,
                'anggota_ids' => [$pegawaiAnggota->id],
                'materis' => [
                    [
                        'kategori_materi' => 'mutasi_narapidana',
                        'judul_materi' => 'Usulan Pemindahan Narapidana an. Anton Sujarwo',
                        'mutasi_wbp_nama' => 'Anton Sujarwo',
                        'mutasi_nomor_register' => 'BI.111/2026',
                        'mutasi_perkara' => 'Narkotika Ps 114',
                        'mutasi_lama_pidana' => '5 Tahun',
                        'mutasi_upt_asal_id' => $upt1->id,
                        'upt_tujuan_rekomendasi_id' => $upt2->id,
                        'mutasi_alasan' => 'Mendekati keluarga',
                        'mutasi_penjamin_nama' => 'Siti Aminah',
                        'dokumen_tambahan_nama' => 'Surat Kades',
                    ],
                    [
                        'kategori_materi' => 'izin_berobat',
                        'judul_materi' => 'Izin Berobat Rujukan RS Luar Kota an. Bambang Tri',
                        'berobat_wbp_nama' => 'Bambang Tri',
                        'berobat_nomor_register' => 'BI.222/2026',
                        'berobat_perkara' => 'Pencurian',
                        'berobat_upt_asal_id' => $upt1->id,
                        'berobat_diagnosa' => 'Tumor Otak',
                        'berobat_rs_nama' => 'RSUP RSCM Jakarta',
                        'berobat_rs_kota' => 'Jakarta Pusat',
                    ],
                    [
                        'kategori_materi' => 'lainnya',
                        'judul_materi' => 'Evaluasi Keamanan Akhir Tahun Lapas',
                        'deskripsi_materi' => 'Pembahasan antisipasi Nataru Lapas se-Banten',
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $newSidang = SidangTppKanwil::where('nomor_sidang', 'like', 'STPP-KW/MULTI/%')->latest()->first();
        $this->assertNotNull($newSidang);
        $this->assertEquals(3, $newSidang->materis()->count());

        $m1 = $newSidang->materis()->where('urutan', 1)->first();
        $this->assertEquals('mutasi_narapidana', $m1->kategori_materi);
        $this->assertEquals('Anton Sujarwo', $m1->narapidana_data['nama']);
        $this->assertEquals($upt2->id, $m1->upt_tujuan_rekomendasi_id);

        $m2 = $newSidang->materis()->where('urutan', 2)->first();
        $this->assertEquals('izin_berobat', $m2->kategori_materi);
        $this->assertEquals('RSUP RSCM Jakarta', $m2->narapidana_data['rs_rujukan_nama']);

        $m3 = $newSidang->materis()->where('urutan', 3)->first();
        $this->assertEquals('lainnya', $m3->kategori_materi);
    }

    /**
     * Memastikan tambah, update, dan hapus materi individual via modal endpoint berfungsi.
     */
    public function test_tambah_edit_dan_hapus_materi_via_controller(): void
    {
        $upt = Upt::first() ?? Upt::create(['nama_upt' => 'Lapas Serang']);

        // 1. Tambah materi baru via AJAX/Form POST
        $responseStore = $this->actingAs($this->userKanwil)
            ->post(route('sidang-tpp-kanwil.materi.store', $this->sidang->id), [
                'kategori_materi' => 'izin_berobat',
                'judul_materi' => 'Materi Baru Berobat Darurat',
                'berobat_wbp_nama' => 'Darurat Pasien',
                'berobat_diagnosa' => 'Apendisitis Akut',
                'berobat_rs_nama' => 'RSUD Banten',
                'berobat_rs_kota' => 'Serang',
            ]);

        $responseStore->assertSessionHasNoErrors();
        $newMateri = SidangTppKanwilMateri::where('sidang_tpp_kanwil_id', $this->sidang->id)
            ->where('judul_materi', 'Materi Baru Berobat Darurat')
            ->first();

        $this->assertNotNull($newMateri);
        $this->assertEquals('izin_berobat', $newMateri->kategori_materi);
        $this->assertEquals('Darurat Pasien', $newMateri->narapidana_data['nama']);

        // 2. Update materi via PUT
        $responseUpdate = $this->actingAs($this->userKanwil)
            ->put(route('sidang-tpp-kanwil.materi.update', [$this->sidang->id, $newMateri->id]), [
                'kategori_materi' => 'izin_berobat',
                'judul_materi' => 'Materi Baru Berobat Diperbarui',
                'berobat_wbp_nama' => 'Darurat Pasien Revisi',
                'berobat_diagnosa' => 'Apendisitis Akut Terkonfirmasi',
                'rekomendasi_sidang' => 'setuju',
                'catatan_rekomendasi' => 'Disetujui untuk operasi segera',
            ]);

        $responseUpdate->assertSessionHasNoErrors();
        $newMateri->refresh();
        $this->assertEquals('Materi Baru Berobat Diperbarui', $newMateri->judul_materi);
        $this->assertEquals('setuju', $newMateri->rekomendasi_sidang);
        $this->assertEquals('Disetujui untuk operasi segera', $newMateri->catatan_rekomendasi);

        // 3. Hapus materi via DELETE
        $responseDestroy = $this->actingAs($this->userKanwil)
            ->delete(route('sidang-tpp-kanwil.materi.destroy', [$this->sidang->id, $newMateri->id]));

        $responseDestroy->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('sidang_tpp_kanwil_materi', ['id' => $newMateri->id]);
    }

    /**
     * Memastikan pada portal WA anggota sidang, anggota memberikan pendapat dan catatan khusus per materi sidang.
     */
    public function test_anggota_portal_submit_pendapat_dan_catatan_khusus_per_materi(): void
    {
        // Siapkan materi 1 dan materi 2
        $materi1 = SidangTppKanwilMateri::create([
            'sidang_tpp_kanwil_id' => $this->sidang->id,
            'urutan' => 1,
            'kategori_materi' => 'mutasi_narapidana',
            'judul_materi' => 'Pemindahan Narapidana Ahmad',
        ]);
        $materi2 = SidangTppKanwilMateri::create([
            'sidang_tpp_kanwil_id' => $this->sidang->id,
            'urutan' => 2,
            'kategori_materi' => 'izin_berobat',
            'judul_materi' => 'Izin Berobat Bambang',
        ]);

        $anggota = SidangTppKanwilAnggota::where('sidang_tpp_kanwil_id', $this->sidang->id)->first();
        $this->assertNotNull($anggota);

        // Submit via portal anggota
        $responseSubmit = $this->post(route('sidang-tpp-kanwil.portal-anggota.submit', $anggota->token_akses), [
            'status_kehadiran' => 'hadir_luring',
            'status_pendapat' => 'setuju',
            'catatan_pendapat' => 'Rangkuman umum saya setuju.',
            'materi_pendapat' => [
                $materi1->id => [
                    'status' => 'setuju',
                    'catatan' => 'Catatan khusus materi 1: Litmas memenuhi syarat dan penjamin jelas.',
                ],
                $materi2->id => [
                    'status' => 'setuju_catatan',
                    'catatan' => 'Catatan khusus materi 2: Wajib pengawalan ketat petugas dan RS rujukan terkonfirmasi.',
                ],
            ],
        ]);

        $responseSubmit->assertSessionHasNoErrors();
        $responseSubmit->assertRedirect();

        // Verifikasi tanggapan per materi tersimpan di tabel sidang_tpp_kanwil_materi_pendapat
        $p1 = SidangTppKanwilMateriPendapat::where('sidang_tpp_kanwil_materi_id', $materi1->id)
            ->where('sidang_tpp_kanwil_anggota_id', $anggota->id)
            ->first();
        $this->assertNotNull($p1);
        $this->assertEquals('setuju', $p1->status_pendapat);
        $this->assertEquals('Catatan khusus materi 1: Litmas memenuhi syarat dan penjamin jelas.', $p1->catatan_pendapat);

        $p2 = SidangTppKanwilMateriPendapat::where('sidang_tpp_kanwil_materi_id', $materi2->id)
            ->where('sidang_tpp_kanwil_anggota_id', $anggota->id)
            ->first();
        $this->assertNotNull($p2);
        $this->assertEquals('setuju_catatan', $p2->status_pendapat);
        $this->assertEquals('Catatan khusus materi 2: Wajib pengawalan ketat petugas dan RS rujukan terkonfirmasi.', $p2->catatan_pendapat);
    }

    /**
     * Memastikan two-way sync multi usulan mutasi narapidana saat Kakanwil upload disposisi.
     */
    public function test_two_way_sync_multi_mutasi_narapidana(): void
    {
        $uptAsal = Upt::first() ?? Upt::create(['nama_upt' => 'Lapas Cilegon']);
        $uptTujuan1 = Upt::where('id', '!=', $uptAsal->id)->first() ?? Upt::create(['nama_upt' => 'Lapas Tangerang']);

        $mutasi1 = MutasiNarapidana::create([
            'nomor_surat' => 'W12.PAS-MUT.001',
            'tanggal_surat' => now(),
            'upt_asal_id' => $uptAsal->id,
            'upt_tujuan_id' => null,
            'total_usulan' => 1,
            'keterangan' => 'Narapidana Kasus A',
            'status' => 'diajukan',
        ]);

        $mutasi2 = MutasiNarapidana::create([
            'nomor_surat' => 'W12.PAS-MUT.002',
            'tanggal_surat' => now(),
            'upt_asal_id' => $uptAsal->id,
            'upt_tujuan_id' => null,
            'total_usulan' => 1,
            'keterangan' => 'Narapidana Kasus B',
            'status' => 'diajukan',
        ]);

        $materi1 = SidangTppKanwilMateri::create([
            'sidang_tpp_kanwil_id' => $this->sidang->id,
            'urutan' => 1,
            'kategori_materi' => 'mutasi_narapidana',
            'judul_materi' => 'Pemindahan Mutasi 1',
            'mutasi_id' => $mutasi1->id,
            'rekomendasi_sidang' => 'setuju',
            'upt_tujuan_rekomendasi_id' => $uptTujuan1->id,
            'catatan_rekomendasi' => 'Setuju dipindahkan ke Lapas Tangerang',
        ]);

        $materi2 = SidangTppKanwilMateri::create([
            'sidang_tpp_kanwil_id' => $this->sidang->id,
            'urutan' => 2,
            'kategori_materi' => 'mutasi_narapidana',
            'judul_materi' => 'Pemindahan Mutasi 2',
            'mutasi_id' => $mutasi2->id,
            'rekomendasi_sidang' => 'tolak',
            'catatan_rekomendasi' => 'Ditolak karena belum memenuhi syarat subtantif',
        ]);

        // Upload disposisi Ka Kanwil
        $fileDisposisi = UploadedFile::fake()->create('disposisi_multi.pdf', 100, 'application/pdf');
        $response = $this->actingAs($this->userKanwil)
            ->post(route('sidang-tpp-kanwil.upload-disposisi-kakanwil', $this->sidang->id), [
                'kakanwil_nama' => 'Dody Fahrudin, Bc.IP., S.H., M.H.',
                'kakanwil_jabatan' => 'Kepala Kantor Wilayah',
                'tanggapan_kakanwil_status' => 'disetujui',
                'tanggapan_kakanwil_tanggal' => '2026-11-20',
                'tanggapan_kakanwil_catatan' => 'Lanjutkan proses dan terbitkan surat keputusan.',
                'file_disposisi_kakanwil' => $fileDisposisi,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Mutasi 1 disetujui -> status disetujui dan upt_tujuan_id diset ke Lapas Tangerang
        $mutasi1->refresh();
        $this->assertEquals('disetujui', $mutasi1->status);
        $this->assertEquals($uptTujuan1->id, $mutasi1->upt_tujuan_id);
        $this->assertStringContainsString('Status: DISETUJUI', $mutasi1->catatan_kanwil);

        // Mutasi 2 juga tersinkronisasi dengan status disetujui sesuai disposisi Kakanwil
        $mutasi2->refresh();
        $this->assertEquals('disetujui', $mutasi2->status);
        $this->assertStringContainsString('Status: DISETUJUI', $mutasi2->catatan_kanwil);
    }

    public function test_create_and_manage_materi_mutasi_keinginan_sendiri_with_pk_bapas_and_ba_upt(): void
    {
        $uptAsal = Upt::first() ?? Upt::create(['nama_upt' => 'Lapas Kelas IIA Serang']);
        $uptTujuan = Upt::where('id', '!=', $uptAsal->id)->first() ?? Upt::create(['nama_upt' => 'Lapas Kelas I Tangerang']);

        // 1. Create materi mutasi_keinginan_sendiri via materiStore
        $filePernyataan = UploadedFile::fake()->create('pernyataan_pribadi_mandiri.pdf', 150, 'application/pdf');
        $fileBaUpt = UploadedFile::fake()->create('ba_sidang_tpp_upt.pdf', 200, 'application/pdf');
        $fileLitmas = UploadedFile::fake()->create('litmas_bapas_serang.pdf', 250, 'application/pdf');
        $fileJaminan = UploadedFile::fake()->create('jaminan_keluarga.pdf', 100, 'application/pdf');
        $fileKtpPenjamin = UploadedFile::fake()->create('ktp_kk_penjamin.pdf', 80, 'application/pdf');
        $fileRegF = UploadedFile::fake()->create('register_f.pdf', 50, 'application/pdf');
        $fileKesehatan = UploadedFile::fake()->create('surat_dokter_sehat.pdf', 90, 'application/pdf');

        $response = $this->actingAs($this->userKanwil)
            ->post(route('sidang-tpp-kanwil.materi.store', $this->sidang->id), [
                'kategori_materi' => 'mutasi_keinginan_sendiri',
                'judul_materi' => 'Permohonan Pemindahan Sendiri an. Ahmad Sobari',
                'deskripsi_materi' => 'Permohonan mendekati keluarga penjamin di Tangerang dengan biaya mandiri.',
                'mutasi_wbp_nama' => 'Ahmad Sobari bin Hasan',
                'mutasi_nomor_register' => 'BI.445/2024',
                'mutasi_perkara' => 'Pencurian Ps 363 KUHP',
                'mutasi_lama_pidana' => '3 Tahun 6 Bulan',
                'mutasi_upt_asal_id' => $uptAsal->id,
                'upt_tujuan_rekomendasi_id' => $uptTujuan->id,
                'mutasi_alasan' => 'Mendekati keluarga orang tua yang sedang sakit keras.',
                // PK Bapas & Litmas
                'nama_pk_bapas' => 'Bambang Pamungkas, S.Sos.',
                'nip_pk_bapas' => '198205122006041002',
                'bapas_nama' => 'Balai Pemasyarakatan Kelas I Serang',
                'rekomendasi_litmas' => 'Memenuhi syarat substantif dan administratif, layak dipindahkan mendekati penjamin.',
                // Hasil Sidang TPP UPT
                'nomor_ba_tpp_upt' => 'W12.PAS.1.PK.01-098/2026',
                'tanggal_ba_tpp_upt' => '2026-11-10',
                'rekomendasi_tpp_upt' => 'Disetujui TPP Lapas Serang untuk diteruskan ke Kantor Wilayah.',
                // Penjamin
                'mutasi_penjamin_nama' => 'Siti Aminah',
                'mutasi_penjamin_nik' => '3671025501850001',
                'mutasi_penjamin_hubungan' => 'Istri Kandung',
                'mutasi_penjamin_kontak' => '081298765432',
                'mutasi_penjamin_alamat' => 'Jl. Veteran No. 12 Kota Tangerang',
                // Berkas
                'file_mutasi_pernyataan_pribadi' => $filePernyataan,
                'file_mutasi_ba_upt' => $fileBaUpt,
                'file_mutasi_litmas' => $fileLitmas,
                'file_mutasi_jaminan' => $fileJaminan,
                'file_mutasi_ktp_penjamin' => $fileKtpPenjamin,
                'file_mutasi_reg_f' => $fileRegF,
                'file_mutasi_kesehatan' => $fileKesehatan,
                'rekomendasi_sidang' => 'setuju',
                'catatan_rekomendasi' => 'Disetujui dengan pengawalan dan biaya mandiri',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $materi = SidangTppKanwilMateri::where('sidang_tpp_kanwil_id', $this->sidang->id)
            ->where('kategori_materi', 'mutasi_keinginan_sendiri')
            ->first();

        $this->assertNotNull($materi);
        $this->assertTrue($materi->isMutasiKeinginanSendiri());
        $this->assertTrue($materi->isMutasiNarapidana());
        $this->assertStringContainsString('Permintaan Sendiri', $materi->kategori_badge);

        $ndm = $materi->narapidana_data;
        $this->assertEquals('Ahmad Sobari bin Hasan', $ndm['nama']);
        $this->assertEquals('Bambang Pamungkas, S.Sos.', $ndm['pk_bapas']['nama']);
        $this->assertEquals('Balai Pemasyarakatan Kelas I Serang', $ndm['pk_bapas']['bapas']);
        $this->assertEquals('W12.PAS.1.PK.01-098/2026', $ndm['tpp_upt']['nomor_ba']);
        $this->assertEquals('3671025501850001', $ndm['penjamin']['nik']);
        $this->assertNotEmpty($ndm['berkas']['pernyataan_pribadi']);
        $this->assertNotEmpty($ndm['berkas']['ba_upt']);

        // 2. Verifikasi update materi
        $responseUpdate = $this->actingAs($this->userKanwil)
            ->put(route('sidang-tpp-kanwil.materi.update', [$this->sidang->id, $materi->id]), [
                'kategori_materi' => 'mutasi_keinginan_sendiri',
                'judul_materi' => 'Permohonan Pemindahan Sendiri an. Ahmad Sobari (Revisi)',
                'mutasi_wbp_nama' => 'Ahmad Sobari bin Hasan',
                'nama_pk_bapas' => 'Bambang Pamungkas, S.Sos., M.Si.',
                'nomor_ba_tpp_upt' => 'W12.PAS.1.PK.01-098.REV/2026',
                'mutasi_penjamin_nama' => 'Siti Aminah',
                'mutasi_penjamin_nik' => '3671025501850001',
                'rekomendasi_sidang' => 'setuju',
                'catatan_rekomendasi' => 'Disetujui penuh',
            ]);

        $responseUpdate->assertSessionHasNoErrors();
        $materi->refresh();
        $this->assertEquals('Bambang Pamungkas, S.Sos., M.Si.', $materi->narapidana_data['pk_bapas']['nama']);
        $this->assertEquals('W12.PAS.1.PK.01-098.REV/2026', $materi->narapidana_data['tpp_upt']['nomor_ba']);

        // 3. Verifikasi naskah dinas merender informasi mutasi keinginan sendiri
        $this->sidang->refresh();
        $risalahHtml = TppKanwilDokumenService::renderRisalahHtml($this->sidang);
        $this->assertStringContainsString('Mutasi Permintaan Sendiri', $risalahHtml);
        $this->assertStringContainsString('Ahmad Sobari bin Hasan', $risalahHtml);
        $this->assertStringContainsString('kesanggupan pembiayaan mandiri', $risalahHtml);

        $laporanHtml = TppKanwilDokumenService::renderLaporanHtml($this->sidang);
        $this->assertStringContainsString('Ahmad Sobari bin Hasan', $laporanHtml);
    }

    /**
     * Test pengisian UPT Tujuan secara manual untuk pemindahan narapidana ke luar wilayah Banten.
     */
    public function test_mutasi_narapidana_manual_upt_tujuan_luar_wilayah(): void
    {
        $nomorSidang = 'TPP-MANUAL-UPT-'.uniqid();
        $uptAsal = Upt::firstOrCreate(
            ['nama_upt' => 'Lapas Kelas IIA Cilegon'],
            ['jenis_upt' => 'Lapas', 'wilayah' => 'Banten']
        );

        $pegawaiKetua = KanwilPegawai::firstOrCreate(
            ['nip' => '197001012000011099'],
            ['nama' => 'Drs. Supriyadi, M.H.', 'jabatan' => 'Kepala Divisi Pemasyarakatan']
        );
        $pegawaiSekretaris = KanwilPegawai::firstOrCreate(
            ['nip' => '198002022005011099'],
            ['nama' => 'Hendra Setiawan, S.H.', 'jabatan' => 'Kepala Bidang Pelayanan Tahanan']
        );

        // 1. Buat Sidang dengan UPT Tujuan luar wilayah (Lapas Kelas I Semarang)
        $response = $this->actingAs($this->userKanwil)
            ->post(route('sidang-tpp-kanwil.store'), [
                'nomor_sidang' => $nomorSidang,
                'tanggal_sidang' => date('Y-m-d'),
                'jam_mulai' => '09:00',
                'tempat' => 'Ruang Rapat Divpas',
                'metode' => 'luring',
                'agenda_kategori' => 'Usulan Mutasi Narapidana Antar-Wilayah',
                'agenda_judul' => 'Pemindahan Narapidana ke Luar Wilayah',
                'subjek_tipe' => 'mutasi_narapidana',
                'mutasi_upt_asal_id' => $uptAsal->id,
                'mutasi_upt_tujuan' => 'Lapas Kelas I Semarang',
                'mutasi_wbp_nama' => 'Joko Widodo bin Sudarto',
                'mutasi_nomor_register' => 'BI.999/2025',
                'mutasi_perkara' => 'Narkotika Ps 114',
                'mutasi_lama_pidana' => '8 Tahun Penjara',
                'mutasi_alasan' => 'Mendekati keluarga di Jawa Tengah',
                'ketua_id' => $pegawaiKetua->id,
                'sekretaris_id' => $pegawaiSekretaris->id,
                'anggota_ids' => [$pegawaiKetua->id, $pegawaiSekretaris->id],
            ]);

        $response->assertSessionHasNoErrors();
        $sidang = SidangTppKanwil::where('nomor_sidang', $nomorSidang)->first();
        $this->assertNotNull($sidang);

        // UPT Tujuan di luar Banten sehingga ID null namun nama tersimpan rapi di narapidana_data
        $this->assertNull($sidang->upt_tujuan_rekomendasi_id);
        $this->assertEquals('Lapas Kelas I Semarang', $sidang->narapidana_data['upt_tujuan_nama']);

        $materi1 = $sidang->materis()->first();
        $this->assertNotNull($materi1);
        $this->assertEquals('Lapas Kelas I Semarang', $materi1->narapidana_data['upt_tujuan_nama']);

        // 2. Tambah materi kedua dengan UPT Tujuan ke Lapas Besi Nusakambangan
        $responseMateri = $this->actingAs($this->userKanwil)
            ->post(route('sidang-tpp-kanwil.materi.store', $sidang->id), [
                'kategori_materi' => 'mutasi_keinginan_sendiri',
                'judul_materi' => 'Permohonan Mutasi Mandiri an. Rudyanto',
                'mutasi_wbp_nama' => 'Rudyanto bin Karno',
                'mutasi_nomor_register' => 'BI.888/2025',
                'mutasi_upt_asal_id' => $uptAsal->id,
                'mutasi_upt_tujuan' => 'Lapas Kelas IIA Besi Nusakambangan',
                'mutasi_alasan' => 'Permohonan pribadi mendekati domisili Cilacap',
                'nama_pk_bapas' => 'Sudirman, S.H.',
                'bapas_nama' => 'Bapas Kelas I Serang',
                'rekomendasi_litmas' => 'Memenuhi syarat substantif',
            ]);

        $responseMateri->assertSessionHasNoErrors();
        $materi2 = $sidang->materis()->where('kategori_materi', 'mutasi_keinginan_sendiri')->first();
        $this->assertNotNull($materi2);
        $this->assertNull($materi2->upt_tujuan_rekomendasi_id);
        $this->assertEquals('Lapas Kelas IIA Besi Nusakambangan', $materi2->narapidana_data['upt_tujuan_nama']);

        // 3. Update materi kedua menjadi UPT Tujuan Lapas Kelas I Malang
        $responseUpdate = $this->actingAs($this->userKanwil)
            ->put(route('sidang-tpp-kanwil.materi.update', [$sidang->id, $materi2->id]), [
                'kategori_materi' => 'mutasi_keinginan_sendiri',
                'judul_materi' => 'Permohonan Mutasi Mandiri an. Rudyanto (Revisi Tujuan)',
                'mutasi_wbp_nama' => 'Rudyanto bin Karno',
                'mutasi_upt_tujuan' => 'Lapas Kelas I Malang',
                'rekomendasi_sidang' => 'setuju',
            ]);

        $responseUpdate->assertSessionHasNoErrors();
        $materi2->refresh();
        $this->assertEquals('Lapas Kelas I Malang', $materi2->narapidana_data['upt_tujuan_nama']);

        // 4. Verifikasi dokumen dinas merender nama UPT manual luar wilayah
        $sidang->refresh();
        $risalahHtml = TppKanwilDokumenService::renderRisalahHtml($sidang);
        $this->assertStringContainsString('Lapas Kelas I Semarang', $risalahHtml);
        $this->assertStringContainsString('Lapas Kelas I Malang', $risalahHtml);

        $laporanHtml = TppKanwilDokumenService::renderLaporanHtml($sidang);
        $this->assertStringContainsString('Lapas Kelas I Semarang', $laporanHtml);
        $this->assertStringContainsString('Lapas Kelas I Malang', $laporanHtml);
    }

    public function test_integrasi_penuh_mutasi_narapidana_alokasi_multi_tujuan_dan_auto_sync_kakanwil(): void
    {
        Storage::fake('public');

        $uptAsal = Upt::where('nama_upt', 'like', '%Cilegon%')->first() ?? Upt::first();
        $uptTujuan1 = Upt::where('nama_upt', 'like', '%Serang%')->first() ?? Upt::skip(1)->first();
        $uptTujuan2 = Upt::where('nama_upt', 'like', '%Rangkasbitung%')->first() ?? Upt::skip(2)->first();

        // 1. Buat usulan mutasi kolektif dari UPT
        $mutasi = MutasiNarapidana::create([
            'upt_asal_id' => $uptAsal->id,
            'nomor_surat' => 'W12.PAS.1-PK.01.04-TEST-'.rand(100, 999),
            'tanggal_surat' => '2026-11-10',
            'kategori_mutasi' => 'Overkapasitas / Redistribusi',
            'jumlah_narkotika' => 15,
            'jumlah_pidum' => 7,
            'jumlah_korupsi' => 3,
            'total_usulan' => 25,
            'keterangan' => 'Usulan pemindahan narapidana overkapasitas ke Lapas se-Banten',
            'status' => 'diajukan',
        ]);

        $ketua = KanwilPegawai::where('peran_tpp', 'Ketua')->first() ?? KanwilPegawai::first();
        $sekretaris = KanwilPegawai::where('peran_tpp', 'Sekretaris')->first() ?? KanwilPegawai::skip(1)->first();
        $nomorSidang = 'TPP-KANWIL-FULL-'.rand(1000, 9999);

        // 2. Register Sidang TPP Kanwil menarik mutasi_id dengan tabel alokasi multi-tujuan
        $response = $this->actingAs($this->userKanwil)
            ->post(route('sidang-tpp-kanwil.store'), [
                'nomor_sidang' => $nomorSidang,
                'tanggal_sidang' => '2026-11-15',
                'jam_mulai' => '09:00',
                'tempat' => 'Ruang Rapat Utama Kanwil Kemenkumham Banten',
                'metode' => 'luring',
                'ketua_id' => $ketua->id,
                'sekretaris_id' => $sekretaris->id,
                'anggota_ids' => [$ketua->id, $sekretaris->id],
                'materis' => [
                    [
                        'kategori_materi' => 'mutasi_narapidana',
                        'judul_materi' => 'Usulan Pemindahan Narapidana Overkapasitas '.$uptAsal->nama_upt,
                        'mutasi_id' => $mutasi->id,
                        'mutasi_upt_asal_id' => $uptAsal->id,
                        'alokasi' => [
                            [
                                'upt_tujuan_id' => $uptTujuan1->id,
                                'jumlah_narkotika' => 10,
                                'jumlah_pidum' => 5,
                                'jumlah_korupsi' => 0,
                            ],
                            [
                                'upt_tujuan_id' => $uptTujuan2->id,
                                'jumlah_narkotika' => 5,
                                'jumlah_pidum' => 2,
                                'jumlah_korupsi' => 3,
                            ],
                        ],
                    ],
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $sidang = SidangTppKanwil::where('nomor_sidang', $nomorSidang)->first();
        $this->assertNotNull($sidang);

        $materi = $sidang->materis()->first();
        $this->assertNotNull($materi);
        $this->assertEquals($mutasi->id, $materi->mutasi_id);
        $this->assertEquals(25, $materi->narapidana_data['total_usulan']);
        $this->assertCount(2, $materi->narapidana_data['alokasi']);
        $this->assertEquals(25, $materi->narapidana_data['total_disetujui']);

        // 3. Verifikasi naskah dinas Risalah & Laporan mencantumkan distribusi alokasi multi-tujuan
        $risalah = TppKanwilDokumenService::renderRisalahHtml($sidang);
        $this->assertStringContainsString($uptTujuan1->nama_upt, $risalah);
        $this->assertStringContainsString($uptTujuan2->nama_upt, $risalah);
        $this->assertStringContainsString('25', $risalah);

        $laporan = TppKanwilDokumenService::renderLaporanHtml($sidang);
        $this->assertStringContainsString('25 Orang', $laporan);

        // 4. Kakanwil memverifikasi sidang dengan status 'disetujui' dan unggah berkas fisik SK Kanwil
        $fakePdf = UploadedFile::fake()->create('sk_persetujuan_kanwil.pdf', 500, 'application/pdf');
        $responseKakanwil = $this->actingAs($this->userKanwil)
            ->post(route('sidang-tpp-kanwil.upload-disposisi-kakanwil', $sidang->id), [
                'tanggapan_kakanwil_status' => 'disetujui',
                'tanggapan_kakanwil_tanggal' => '2026-11-15',
                'tanggapan_kakanwil_catatan' => 'Disetujui penuh sesuai rekomendasi sidang TPP Kanwil',
                'kakanwil_nama' => 'Dr. H. Bambang Irawan, Bc.I.P., S.H., M.H.',
                'kakanwil_nip' => '196805121990031001',
                'nomor_surat_kanwil' => 'W12.PK.01.04-SK-999',
                'file_disposisi_kakanwil' => $fakePdf,
            ]);

        $responseKakanwil->assertSessionHasNoErrors();
        $sidang->refresh();
        $this->assertEquals('disetujui', $sidang->tanggapan_kakanwil_status);
        $this->assertEquals('selesai', $sidang->status_sidang);

        // 5. Cek MutasiNarapidana ter-update secara otomatis
        $mutasi->refresh();
        $this->assertEquals('disetujui', $mutasi->status);
        $this->assertEquals('W12.PK.01.04-SK-999', $mutasi->nomor_surat_kanwil);
        $this->assertEquals('2026-11-15', $mutasi->tanggal_surat_kanwil?->format('Y-m-d'));
        $this->assertNotNull($mutasi->file_persetujuan_kanwil);
        $this->assertEquals(25, $mutasi->total_disetujui);
        $this->assertEquals(15, $mutasi->jumlah_disetujui_narkotika);
        $this->assertEquals(7, $mutasi->jumlah_disetujui_pidum);
        $this->assertEquals(3, $mutasi->jumlah_disetujui_korupsi);

        // 6. Cek MutasiNarapidanaTujuan otomatis ter-generate sesuai alokasi sidang
        $tujuans = MutasiNarapidanaTujuan::where('mutasi_id', $mutasi->id)->get();
        $this->assertCount(2, $tujuans);

        $tujuan1 = $tujuans->where('upt_tujuan_id', $uptTujuan1->id)->first();
        $this->assertNotNull($tujuan1);
        $this->assertEquals(10, $tujuan1->jumlah_disetujui_narkotika);
        $this->assertEquals(5, $tujuan1->jumlah_disetujui_pidum);
        $this->assertEquals(0, $tujuan1->jumlah_disetujui_korupsi);
        $this->assertEquals(15, $tujuan1->total_disetujui);

        $tujuan2 = $tujuans->where('upt_tujuan_id', $uptTujuan2->id)->first();
        $this->assertNotNull($tujuan2);
        $this->assertEquals(5, $tujuan2->jumlah_disetujui_narkotika);
        $this->assertEquals(2, $tujuan2->jumlah_disetujui_pidum);
        $this->assertEquals(3, $tujuan2->jumlah_disetujui_korupsi);
        $this->assertEquals(10, $tujuan2->total_disetujui);
    }

    public function test_materi_update_mismatched_quota_without_catatan_fails(): void
    {
        $uptAsal = Upt::firstOrCreate(['nama_upt' => 'Lapas Asal Test'], ['kapasitas' => 500]);
        $uptTujuan = Upt::firstOrCreate(['nama_upt' => 'Lapas Tujuan Test'], ['kapasitas' => 600]);

        $mutasi = MutasiNarapidana::create([
            'nomor_surat' => 'USUL/TEST/001',
            'tanggal_surat' => '2026-11-10',
            'upt_asal_id' => $uptAsal->id,
            'kategori_mutasi' => 'keamanan',
            'jumlah_narkotika' => 15,
            'jumlah_pidum' => 5,
            'jumlah_korupsi' => 0,
            'total_usulan' => 20,
            'status' => 'diajukan',
        ]);

        $materi = SidangTppKanwilMateri::create([
            'sidang_tpp_kanwil_id' => $this->sidang->id,
            'urutan' => 1,
            'kategori_materi' => 'mutasi_narapidana',
            'judul_materi' => 'Usulan Mutasi 20 WBP Lapas Asal Test',
            'mutasi_id' => $mutasi->id,
            'narapidana_data' => [
                'total_usulan' => 20,
                'jumlah_usulan_narkotika' => 15,
                'jumlah_usulan_pidum' => 5,
                'jumlah_usulan_korupsi' => 0,
            ],
        ]);

        // Coba simpan persetujuan sebagian (15 orang alokasi dari 20 usulan) tanpa catatan pertimbangan
        $resp = $this->actingAs($this->userKanwil)
            ->put(route('sidang-tpp-kanwil.materi.update', [$this->sidang->id, $materi->id]), [
                'kategori_materi' => 'mutasi_narapidana',
                'judul_materi' => 'Usulan Mutasi 20 WBP Lapas Asal Test',
                'rekomendasi_sidang' => 'setuju_catatan',
                'catatan_rekomendasi' => '', // kosong!
                'alokasi' => [
                    [
                        'upt_tujuan_id' => $uptTujuan->id,
                        'jumlah_narkotika' => 10,
                        'jumlah_pidum' => 5,
                        'jumlah_korupsi' => 0,
                    ],
                ],
            ]);

        $resp->assertSessionHas('error');
    }

    public function test_materi_update_partial_quota_with_catatan_succeeds_and_syncs_tujuans(): void
    {
        $uptAsal = Upt::firstOrCreate(['nama_upt' => 'Lapas Asal Test 2'], ['kapasitas' => 500]);
        $uptTujuan = Upt::firstOrCreate(['nama_upt' => 'Lapas Tujuan Test 2'], ['kapasitas' => 600]);

        $mutasi = MutasiNarapidana::create([
            'nomor_surat' => 'USUL/TEST/002',
            'tanggal_surat' => '2026-11-10',
            'upt_asal_id' => $uptAsal->id,
            'kategori_mutasi' => 'keamanan',
            'jumlah_narkotika' => 20,
            'jumlah_pidum' => 0,
            'jumlah_korupsi' => 0,
            'total_usulan' => 20,
            'status' => 'diajukan',
        ]);

        $materi = SidangTppKanwilMateri::create([
            'sidang_tpp_kanwil_id' => $this->sidang->id,
            'urutan' => 2,
            'kategori_materi' => 'mutasi_narapidana',
            'judul_materi' => 'Usulan Mutasi 20 WBP',
            'mutasi_id' => $mutasi->id,
            'narapidana_data' => [
                'total_usulan' => 20,
                'jumlah_usulan_narkotika' => 20,
                'jumlah_usulan_pidum' => 0,
                'jumlah_usulan_korupsi' => 0,
            ],
        ]);

        // Simpan persetujuan sebagian (12 orang alokasi dari 20 usulan) DENGAN catatan pertimbangan
        $resp = $this->actingAs($this->userKanwil)
            ->put(route('sidang-tpp-kanwil.materi.update', [$this->sidang->id, $materi->id]), [
                'kategori_materi' => 'mutasi_narapidana',
                'judul_materi' => 'Usulan Mutasi 20 WBP',
                'rekomendasi_sidang' => 'setuju_catatan',
                'catatan_rekomendasi' => 'Kapasitas Lapas Tujuan terbatas, hanya disetujui 12 orang. Sisanya 8 orang ditunda.',
                'alokasi' => [
                    [
                        'upt_tujuan_id' => $uptTujuan->id,
                        'jumlah_narkotika' => 12,
                        'jumlah_pidum' => 0,
                        'jumlah_korupsi' => 0,
                    ],
                ],
            ]);

        $resp->assertSessionHasNoErrors();
        $resp->assertSessionHas('success');

        // Pastikan sinkronisasi ke tabel mutasi_narapidana_tujuan berhasil
        $tujuans = MutasiNarapidanaTujuan::where('mutasi_id', $mutasi->id)->get();
        $this->assertCount(1, $tujuans);
        $this->assertEquals($uptTujuan->id, $tujuans->first()->upt_tujuan_id);
        $this->assertEquals(12, $tujuans->first()->total_disetujui);

        // Pastikan mutasi_narapidana juga tersinkronisasi
        $mutasi->refresh();
        $this->assertEquals(12, $mutasi->total_disetujui);
        $this->assertEquals($uptTujuan->id, $mutasi->upt_tujuan_id);
        $this->assertStringContainsString('Kapasitas Lapas Tujuan terbatas', $mutasi->catatan_kanwil);
    }
}
