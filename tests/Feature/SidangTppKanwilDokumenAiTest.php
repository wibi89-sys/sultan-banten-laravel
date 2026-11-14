<?php

namespace Tests\Feature;

use App\Models\SidangTppKanwil;
use App\Models\SidangTppKanwilAnggota;
use App\Models\TppKanwilSetting;
use App\Models\User;
use App\Services\GeminiAiService;
use App\Services\TppKanwilDokumenService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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
     * Memastikan user non-admin (operator wilayah biasa) DITOLAK (403) saat mencoba menghapus Sidang TPP Kanwil.
     */
    public function test_non_admin_cannot_delete_sidang_tpp_kanwil(): void
    {
        // $this->userKanwil memiliki role 'operator_wilayah' (bukan 'admin')
        $response = $this->actingAs($this->userKanwil)
            ->delete(route('sidang-tpp-kanwil.destroy', $this->sidang->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('sidang_tpp_kanwil', ['id' => $this->sidang->id]);
    }

    /**
     * Memastikan hanya Admin Kanwil (role: admin) yang diizinkan menghapus Sidang TPP Kanwil.
     */
    public function test_admin_can_delete_sidang_tpp_kanwil(): void
    {
        $admin = User::where('role', 'admin')->first()
            ?? User::factory()->create(['role' => 'admin', 'upt_id' => null]);

        $response = $this->actingAs($admin)
            ->delete(route('sidang-tpp-kanwil.destroy', $this->sidang->id));

        $response->assertRedirect(route('sidang-tpp-kanwil.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('sidang_tpp_kanwil', ['id' => $this->sidang->id]);
    }
}
