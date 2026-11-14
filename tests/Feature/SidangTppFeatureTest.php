<?php

namespace Tests\Feature;

use App\Models\SidangTpp;
use App\Models\TppNaskahKanwil;
use App\Models\Upt;
use App\Models\User;
use App\Services\LaporanTppGeneratorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SidangTppFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $userKanwil;

    protected User $userUpt;

    protected Upt $upt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->upt = Upt::where('nama_upt', 'like', '%Pandeglang%')->first()
            ?? Upt::firstOrCreate(['nama_upt' => 'RUTAN PANDEGLANG TEST'], ['alamat' => 'Test', 'kapasitas' => 100]);

        // Kanwil user: upt_id is null, non-admin operator wilayah
        $this->userKanwil = User::whereNull('upt_id')->where('role', 'operator_wilayah')->first()
            ?? User::factory()->create(['upt_id' => null, 'role' => 'operator_wilayah']);
        $this->userKanwil->role = 'operator_wilayah';
        $this->userKanwil->akses_sidang_tpp = true;
        $this->userKanwil->save();

        // UPT user: upt_id is set
        $this->userUpt = User::where('upt_id', $this->upt->id)->first()
            ?? User::factory()->create(['upt_id' => $this->upt->id, 'role' => 'operator']);
        $this->userUpt->akses_sidang_tpp = true;
        $this->userUpt->save();
    }

    public function test_kanwil_user_cannot_create_sidang_schedule(): void
    {
        $response = $this->actingAs($this->userKanwil)->post(route('sidang-tpp.store'), [
            'nomor_undangan' => 'UND-TEST-KANWIL-01',
            'tanggal_surat' => '2026-10-23',
            'tanggal_sidang' => '2026-10-25',
            'jam_mulai' => '09:00',
            'agenda_sidang' => 'Agenda Sidang Kanwil Test',
            'ketua_tpp' => 'Ketua Test',
            'sekretaris_tpp' => 'Sekretaris Test',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('sidang_tpp', [
            'nomor_undangan' => 'UND-TEST-KANWIL-01',
        ]);
    }

    public function test_upt_user_can_create_sidang_with_manual_agenda_and_manual_participants(): void
    {
        $response = $this->actingAs($this->userUpt)->post(route('sidang-tpp.store'), [
            'nomor_undangan' => 'UND-TEST-UPT-888',
            'tanggal_surat' => '2026-10-23',
            'tanggal_sidang' => '2026-10-25',
            'jam_mulai' => '09:00',
            'agenda_sidang' => 'Sidang Usulan Hak Integrasi PB & Register F Manual Test',
            'metode_sidang' => 'hybrid',
            'link_daring' => 'https://zoom.us/test',
            'ketua_tpp' => 'Ketua UPT Pandeglang',
            'sekretaris_tpp' => 'Sekretaris UPT',
            'wbp_json' => json_encode([
                [
                    'nama_wbp' => 'WBP Test Manual 1',
                    'nomor_register' => 'REG.991',
                    'perkara_pasal' => 'Pasal 362 KUHP',
                    'lama_pidana' => '1 Tahun',
                    'jenis_usulan' => 'Asimilasi',
                    'perlu_perhatian' => true,
                    'catatan_perhatian' => 'WBP atensi Kanwil kasus khusus',
                    'keputusan' => 'belum_putus',
                    'alasan_keterangan' => 'Syarat lengkap',
                ],
                [
                    'nama_wbp' => 'WBP Test Manual 2',
                    'nomor_register' => 'REG.992',
                    'perkara_pasal' => 'Tata Tertib',
                    'lama_pidana' => '2 Tahun',
                    'jenis_usulan' => 'Register F',
                    'perlu_perhatian' => true,
                    'catatan_perhatian' => 'Buku Register F aktif',
                    'keputusan' => 'belum_putus',
                    'alasan_keterangan' => 'Buku Register F aktif',
                ],
            ]),
        ]);

        $response->assertRedirect(route('sidang-tpp.index'));
        $this->assertDatabaseHas('sidang_tpp', [
            'nomor_undangan' => 'UND-TEST-UPT-888',
            'agenda_sidang' => 'Sidang Usulan Hak Integrasi PB & Register F Manual Test',
            'total_usulan' => 2,
        ]);
        $this->assertDatabaseHas('sidang_tpp_usulan', [
            'nama_wbp' => 'WBP Test Manual 1',
            'jenis_usulan' => 'Asimilasi',
            'perlu_perhatian' => 1,
            'catatan_perhatian' => 'WBP atensi Kanwil kasus khusus',
        ]);
    }

    public function test_upt_user_can_input_hasil_with_setuju_tidak_setuju_tunda(): void
    {
        $sidang = SidangTpp::create([
            'upt_id' => $this->upt->id,
            'nomor_undangan' => 'UND-HASIL-TEST',
            'tanggal_surat' => '2026-10-23',
            'tanggal_sidang' => '2026-10-24',
            'jam_mulai' => '09:00:00',
            'agenda_sidang' => 'Sidang Hasil Test',
            'ketua_tpp' => 'Ketua Test',
            'sekretaris_tpp' => 'Sekretaris Test',
            'status_verifikasi_kanwil' => 'menunggu_sidang',
            'status_sidang' => 'dijadwalkan',
        ]);

        $usulan = $sidang->usulan()->create([
            'nama_wbp' => 'Budi Santoso',
            'nomor_register' => 'REG.777',
            'perkara_pasal' => 'Pasal 378 KUHP',
            'lama_pidana' => '1 Tahun',
            'jenis_usulan' => 'CB',
            'keputusan' => 'belum_putus',
        ]);

        $response = $this->actingAs($this->userUpt)->post(route('sidang-tpp.hasil.update', $sidang->id), [
            'nomor_berita_acara' => 'BA.TEST.99/2026',
            'keputusan' => [
                $usulan->id => 'tunda',
            ],
            'alasan_keterangan' => [
                $usulan->id => 'Penjamin belum melengkapi KK',
            ],
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('sidang_tpp_usulan', [
            'id' => $usulan->id,
            'keputusan' => 'tunda',
        ]);

        $sidang->refresh();
        $this->assertEquals(1, $sidang->total_tunda);
        $this->assertEquals('BA.TEST.99/2026', $sidang->nomor_berita_acara);
    }

    public function test_kanwil_user_cannot_input_hasil_sidang_upt(): void
    {
        $sidang = SidangTpp::create([
            'upt_id' => $this->upt->id,
            'nomor_undangan' => 'UND-KANWIL-BLOCK',
            'tanggal_surat' => '2026-10-23',
            'tanggal_sidang' => '2026-10-24',
            'jam_mulai' => '09:00:00',
            'agenda_sidang' => 'Test Agenda',
            'ketua_tpp' => 'Ketua Test',
            'sekretaris_tpp' => 'Sekretaris Test',
            'status_verifikasi_kanwil' => 'menunggu_sidang',
            'status_sidang' => 'dijadwalkan',
        ]);

        $response = $this->actingAs($this->userKanwil)->post(route('sidang-tpp.hasil.update', $sidang->id), [
            'nomor_berita_acara' => 'BA.ILLEGAL.KANWIL',
        ]);

        $response->assertStatus(403);
    }

    public function test_upt_user_cannot_verify_kesesuaian_dokumen(): void
    {
        $sidang = SidangTpp::create([
            'upt_id' => $this->upt->id,
            'nomor_undangan' => 'UND-VERIF-TEST',
            'tanggal_surat' => '2026-10-23',
            'tanggal_sidang' => '2026-10-24',
            'jam_mulai' => '09:00:00',
            'agenda_sidang' => 'Test Verifikasi Agenda',
            'ketua_tpp' => 'Ketua Test',
            'sekretaris_tpp' => 'Sekretaris Test',
            'status_verifikasi_kanwil' => 'menunggu_verifikasi',
            'status_sidang' => 'menunggu_verifikasi_kanwil',
        ]);

        $response = $this->actingAs($this->userUpt)->post(route('sidang-tpp.kanwil.verifikasi', $sidang->id), [
            'status_verifikasi_kanwil' => 'sesuai',
        ]);

        $response->assertStatus(403);
    }

    public function test_kanwil_user_can_verify_kesesuaian_dokumen_with_sesuai_tidak_sesuai_perbaikan(): void
    {
        $sidang = SidangTpp::create([
            'upt_id' => $this->upt->id,
            'nomor_undangan' => 'UND-VERIF-KANWIL-TEST',
            'tanggal_surat' => '2026-10-23',
            'tanggal_sidang' => '2026-10-24',
            'jam_mulai' => '09:00:00',
            'agenda_sidang' => 'Test Verifikasi Agenda Kanwil',
            'ketua_tpp' => 'Ketua Test',
            'sekretaris_tpp' => 'Sekretaris Test',
            'status_verifikasi_kanwil' => 'menunggu_verifikasi',
            'status_sidang' => 'menunggu_verifikasi_kanwil',
        ]);

        $response = $this->actingAs($this->userKanwil)->post(route('sidang-tpp.kanwil.verifikasi', $sidang->id), [
            'status_verifikasi_kanwil' => 'perbaikan',
            'catatan_bintorwasdal' => 'Penjamin perlu melengkapi fotokopi KTP',
        ]);

        $response->assertRedirect(route('sidang-tpp.kanwil'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('sidang_tpp', [
            'id' => $sidang->id,
            'status_verifikasi_kanwil' => 'perbaikan',
            'catatan_bintorwasdal' => 'Penjamin perlu melengkapi fotokopi KTP',
        ]);
    }

    public function test_upt_user_can_crud_peserta_sidang(): void
    {
        $sidang = SidangTpp::create([
            'upt_id' => $this->upt->id,
            'nomor_undangan' => 'UND-PESERTA-CRUD',
            'tanggal_surat' => '2026-10-23',
            'tanggal_sidang' => '2026-10-24',
            'jam_mulai' => '09:00:00',
            'agenda_sidang' => 'Agenda Peserta CRUD',
            'ketua_tpp' => 'Ketua Test',
            'sekretaris_tpp' => 'Sekretaris Test',
            'status_verifikasi_kanwil' => 'menunggu_sidang',
            'status_sidang' => 'dijadwalkan',
        ]);

        // 1. Create Peserta
        $resStore = $this->actingAs($this->userUpt)->post(route('sidang-tpp.peserta.store', $sidang->id), [
            'nama_wbp' => 'Warga Binaan Baru',
            'nomor_register' => 'REG.NEW.001',
            'perkara_pasal' => 'Pasal 378 KUHP',
            'lama_pidana' => '1 Tahun',
            'jenis_usulan' => 'Asimilasi',
            'perlu_perhatian' => 1,
            'catatan_perhatian' => 'Atensi khusus',
        ]);

        $resStore->assertSessionHas('success');
        $this->assertDatabaseHas('sidang_tpp_usulan', [
            'sidang_tpp_id' => $sidang->id,
            'nama_wbp' => 'Warga Binaan Baru',
            'nomor_register' => 'REG.NEW.001',
            'jenis_usulan' => 'Asimilasi',
            'perlu_perhatian' => 1,
        ]);

        $peserta = $sidang->usulan()->where('nomor_register', 'REG.NEW.001')->first();
        $this->assertNotNull($peserta);

        // 2. Update Peserta
        $resUpdate = $this->actingAs($this->userUpt)->put(route('sidang-tpp.peserta.update', ['id' => $sidang->id, 'peserta_id' => $peserta->id]), [
            'nama_wbp' => 'Warga Binaan Baru Diperbarui',
            'nomor_register' => 'REG.NEW.001',
            'perkara_pasal' => 'Pasal 378 KUHP (Penipuan)',
            'lama_pidana' => '1 Tahun 6 Bulan',
            'jenis_usulan' => 'CB',
            'perlu_perhatian' => 0,
        ]);

        $resUpdate->assertSessionHas('success');
        $this->assertDatabaseHas('sidang_tpp_usulan', [
            'id' => $peserta->id,
            'nama_wbp' => 'Warga Binaan Baru Diperbarui',
            'jenis_usulan' => 'CB',
            'perlu_perhatian' => 0,
        ]);

        // 3. Destroy Peserta
        $resDestroy = $this->actingAs($this->userUpt)->delete(route('sidang-tpp.peserta.destroy', ['id' => $sidang->id, 'peserta_id' => $peserta->id]));

        $resDestroy->assertSessionHas('success');
        $this->assertDatabaseMissing('sidang_tpp_usulan', [
            'id' => $peserta->id,
        ]);
    }

    public function test_kanwil_user_cannot_crud_peserta_sidang_upt(): void
    {
        $sidang = SidangTpp::create([
            'upt_id' => $this->upt->id,
            'nomor_undangan' => 'UND-KANWIL-PESERTA-BLOCK',
            'tanggal_surat' => '2026-10-23',
            'tanggal_sidang' => '2026-10-24',
            'jam_mulai' => '09:00:00',
            'agenda_sidang' => 'Agenda Kanwil Block',
            'ketua_tpp' => 'Ketua Test',
            'sekretaris_tpp' => 'Sekretaris Test',
            'status_verifikasi_kanwil' => 'menunggu_sidang',
            'status_sidang' => 'dijadwalkan',
        ]);

        $peserta = $sidang->usulan()->create([
            'nama_wbp' => 'Existing WBP',
            'nomor_register' => 'REG.EX.001',
            'perkara_pasal' => 'Pasal 362 KUHP',
            'lama_pidana' => '1 Tahun',
            'jenis_usulan' => 'PB',
            'keputusan' => 'belum_putus',
        ]);

        // 1. Kanwil cannot Create peserta
        $resStore = $this->actingAs($this->userKanwil)->post(route('sidang-tpp.peserta.store', $sidang->id), [
            'nama_wbp' => 'Illegal Warga Binaan',
            'nomor_register' => 'REG.ILLEGAL',
            'jenis_usulan' => 'PB',
        ]);
        $resStore->assertStatus(403);

        // 2. Kanwil cannot Update peserta
        $resUpdate = $this->actingAs($this->userKanwil)->put(route('sidang-tpp.peserta.update', ['id' => $sidang->id, 'peserta_id' => $peserta->id]), [
            'nama_wbp' => 'Hacked WBP',
            'nomor_register' => 'REG.EX.001',
            'jenis_usulan' => 'PB',
        ]);
        $resUpdate->assertStatus(403);

        // 3. Kanwil cannot Delete peserta
        $resDestroy = $this->actingAs($this->userKanwil)->delete(route('sidang-tpp.peserta.destroy', ['id' => $sidang->id, 'peserta_id' => $peserta->id]));
        $resDestroy->assertStatus(403);

        // 4. Detail page for Kanwil does not show Tambah Peserta Sidang button
        $resDetail = $this->actingAs($this->userKanwil)->get(route('sidang-tpp.show', $sidang->id));
        $resDetail->assertOk();
        $resDetail->assertDontSee('Tambah Peserta Sidang');
        $resDetail->assertSee('Akses Kanwil: Pengawas &amp; Verifikator (Read Only)', false);

        // 5. Kanwil verification page has the modal pop-up and button
        $resKanwil = $this->actingAs($this->userKanwil)->get(route('sidang-tpp.kanwil'));
        $resKanwil->assertOk();
        $resKanwil->assertSee('modalRincianWbpKanwil');
        $resKanwil->assertSee('Lihat Rincian Warga Binaan');
    }

    public function test_kanwil_can_upload_signed_pdf_and_stamp_qr(): void
    {
        $sidang = SidangTpp::create([
            'upt_id' => $this->upt->id,
            'nomor_undangan' => 'UND-STAMP-TEST-01',
            'tanggal_surat' => '2026-10-23',
            'tanggal_sidang' => '2026-10-24',
            'jam_mulai' => '09:00:00',
            'agenda_sidang' => 'Test Stamping QR Sidang',
            'ketua_tpp' => 'Ketua Test',
            'sekretaris_tpp' => 'Sekretaris Test',
            'status_sidang' => 'dijadwalkan',
        ]);

        // Generate dummy valid PDF using TCPDF
        require_once app_path('Libraries/tcpdf/tcpdf.php');
        $tcpdf = new \TCPDF;
        $tcpdf->setPrintHeader(false);
        $tcpdf->setPrintFooter(false);
        $tcpdf->AddPage();
        $tcpdf->Write(0, 'Surat Scan Tanda Tangan Basah Asli');
        $tempPdf = storage_path('app/temp_upload_test.pdf');
        $tcpdf->Output($tempPdf, 'F');

        $uploadedFile = new UploadedFile(
            $tempPdf,
            'scan_nota_dinas_ttd.pdf',
            'application/pdf',
            null,
            true
        );

        $response = $this->actingAs($this->userKanwil)->post(
            route('sidang-tpp.dokumen-kanwil.upload-signed', $sidang->id),
            [
                'file_signed' => $uploadedFile,
                'jenis_dokumen' => 'nota_dinas',
                'nomor_dokumen' => 'ND-STAMP-001',
                'bubuhi_qr' => '1',
                'qr_percent_x' => 10,
                'qr_percent_y' => 85,
                'qr_target_page' => 'last',
                'qr_style' => 'badge',
            ]
        );

        $response->assertSessionHas('success');

        $naskah = TppNaskahKanwil::where('sidang_tpp_id', $sidang->id)
            ->where('jenis_dokumen', 'nota_dinas')
            ->first();

        $this->assertNotNull($naskah);
        $this->assertEquals('signed', $naskah->status);
        $this->assertEquals('digital_qr', $naskah->metode_ttd);
        $this->assertTrue($naskah->is_locked);
        $this->assertNotNull($naskah->locked_at);
        $this->assertNotEmpty($naskah->file_signed);

        $fullPath = public_path($naskah->file_signed);
        $this->assertFileExists($fullPath);
        $this->assertGreaterThan(0, filesize($fullPath));

        // Cleanup
        @unlink($tempPdf);
        @unlink($fullPath);
    }

    public function test_non_admin_cannot_replace_locked_naskah_but_admin_can(): void
    {
        $sidang = SidangTpp::create([
            'upt_id' => $this->upt->id,
            'nomor_undangan' => 'UND-LOCK-TEST-01',
            'tanggal_surat' => '2026-10-23',
            'tanggal_sidang' => '2026-10-24',
            'jam_mulai' => '09:00:00',
            'agenda_sidang' => 'Test Lock Document Protection',
            'ketua_tpp' => 'Ketua Test',
            'sekretaris_tpp' => 'Sekretaris Test',
            'status_sidang' => 'dijadwalkan',
        ]);

        $naskah = TppNaskahKanwil::create([
            'sidang_tpp_id' => $sidang->id,
            'jenis_dokumen' => 'nota_dinas',
            'nomor_dokumen' => 'ND-LOCKED-001',
            'file_signed' => 'uploads/sidang_tpp_signed/test_locked.pdf',
            'status' => 'signed',
            'metode_ttd' => 'digital_qr',
            'kode_verifikasi' => 'SB-LOCKTEST01',
            'is_locked' => true,
            'locked_at' => now(),
            'locked_by_id' => $this->userKanwil->id,
        ]);

        // Siapkan dummy file upload
        require_once app_path('Libraries/tcpdf/tcpdf.php');
        $tcpdf = new \TCPDF;
        $tcpdf->setPrintHeader(false);
        $tcpdf->setPrintFooter(false);
        $tcpdf->AddPage();
        $tcpdf->Write(0, 'Percobaan Ganti Dokumen');
        $tempPdf = storage_path('app/temp_replace_test.pdf');
        $tcpdf->Output($tempPdf, 'F');

        $uploadedFile = new UploadedFile(
            $tempPdf,
            'replace_test.pdf',
            'application/pdf',
            null,
            true
        );

        // 1. Non-admin operator wilayah mencoba upload ulang file terkunci -> HARUS 403
        $resForbidden = $this->actingAs($this->userKanwil)->post(
            route('sidang-tpp.dokumen-kanwil.upload-signed', $sidang->id),
            [
                'file_signed' => $uploadedFile,
                'jenis_dokumen' => 'nota_dinas',
                'nomor_dokumen' => 'ND-LOCKED-REPLACED',
            ]
        );
        $resForbidden->assertStatus(403);

        // 2. Admin Kanwil (role = 'admin') mencoba upload ulang -> HARUS BERHASIL
        $adminUser = User::whereNull('upt_id')->where('role', 'admin')->first()
            ?? User::factory()->create(['upt_id' => null, 'role' => 'admin']);
        $adminUser->role = 'admin';
        $adminUser->akses_sidang_tpp = true;
        $adminUser->save();

        $uploadedFileAdmin = new UploadedFile(
            $tempPdf,
            'replace_admin.pdf',
            'application/pdf',
            null,
            true
        );

        $resAdmin = $this->actingAs($adminUser)->post(
            route('sidang-tpp.dokumen-kanwil.upload-signed', $sidang->id),
            [
                'file_signed' => $uploadedFileAdmin,
                'jenis_dokumen' => 'nota_dinas',
                'nomor_dokumen' => 'ND-LOCKED-REPLACED',
                'bubuhi_qr' => '1',
            ]
        );
        $resAdmin->assertSessionHas('success');

        $naskah->refresh();
        $this->assertEquals('ND-LOCKED-REPLACED', $naskah->nomor_dokumen);
        $this->assertTrue($naskah->is_locked);

        @unlink($tempPdf);
        if ($naskah->file_signed && file_exists(public_path($naskah->file_signed))) {
            @unlink(public_path($naskah->file_signed));
        }
    }

    public function test_public_verifikasi_dokumen_page_does_not_show_lihat_hasil_scan_button(): void
    {
        $sidang = SidangTpp::create([
            'upt_id' => $this->upt->id,
            'nomor_undangan' => 'UND-VERIF-'.time(),
            'tanggal_surat' => '2026-10-23',
            'tanggal_sidang' => '2026-10-25',
            'jam_mulai' => '09:00',
            'agenda_sidang' => 'Agenda Sidang Verif Test',
            'ketua_tpp' => 'Ketua Test',
            'sekretaris_tpp' => 'Sekretaris Test',
        ]);

        $naskah = TppNaskahKanwil::create([
            'sidang_tpp_id' => $sidang->id,
            'jenis_dokumen' => 'laporan_tpp',
            'kode_verifikasi' => 'VERIF-TEST-'.time(),
            'nomor_dokumen' => 'LAP-VERIF-001',
            'tanggal_dokumen' => '2026-10-25',
            'judul_naskah' => 'Laporan TPP Kanwil',
            'materi_agenda' => 'Materi Agenda',
            'penandatangan_nama' => 'Surya Dharma',
            'penandatangan_jabatan' => 'Kepala Bidang',
            'file_signed' => 'uploads/sidang_tpp_signed/dummy.pdf',
            'status' => 'signed',
            'metode_ttd' => 'digital_qr',
        ]);

        $response = $this->get(route('verifikasi-dokumen.show', $naskah->kode_verifikasi));
        $response->assertStatus(200);
        $response->assertSee('LEMBAR OTENTIFIKASI DOKUMEN DIGITAL');
        $response->assertSee('DOKUMEN RESMI TERVERIFIKASI');
        $response->assertSee($naskah->kode_verifikasi);
        $response->assertDontSee('Lihat Hasil Scan');
        $response->assertDontSee('Lihat Hasil Scan TTD Basah');
    }

    public function test_laporan_tpp_generator_connects_paragraf_a3_with_materi_items_naturally(): void
    {
        $rawParagrafA3 = 'Kehadiran perwakilan Kantor Wilayah dalam forum sidang ini menegaskan posisi strategis struktur pengawas sebagai unsur pembina yang memastikan seluruh rangkaian proses pengambilan keputusan berjalan objektif dan bebas dari segala bentuk diskriminasi. Peran pengawasan ini difokuskan untuk menguji objektivitas Tim TPP UPT dalam menilai rekam jejak perilaku narapidana selama menjalani masa pidana di dalam lapas.';

        $materiItems = [
            'Cuti Bersyarat (CB): 4 orang narapidana',
            'Pembebasan Bersyarat (PB): 7 orang narapidana',
        ];

        // 1. Kasus tanpa kalimat penghubung (otomatis disambungkan secara alami)
        $html = LaporanTppGeneratorService::renderHtmlTemplate([
            'paragraf_a3' => $rawParagrafA3,
            'materi_sidang_items' => $materiItems,
        ]);

        $this->assertStringContainsString('Adapun pokok materi dan rincian kuota usulan narapidana yang dibahas serta ditelaah dalam forum sidang TPP ini meliputi sebagai berikut:', $html);
        $this->assertStringContainsString('Cuti Bersyarat (CB): 4 orang narapidana', $html);
        $this->assertStringContainsString('Pembebasan Bersyarat (PB): 7 orang narapidana', $html);

        // 2. Kasus jika paragraf A3 sudah memiliki klausa pengantar resmi sendiri
        $customConnectorA3 = 'Perwakilan Kanwil mengawasi jalannya sidang dengan rincian materi sebagai berikut:';
        $htmlCustom = LaporanTppGeneratorService::renderHtmlTemplate([
            'paragraf_a3' => $customConnectorA3,
            'materi_sidang_items' => $materiItems,
        ]);

        $this->assertStringContainsString('Perwakilan Kanwil mengawasi jalannya sidang dengan rincian materi sebagai berikut:', $htmlCustom);
        // Pastikan tidak terjadi pengulangan kalimat ganda
        $this->assertEquals(1, substr_count($htmlCustom, 'sebagai berikut:'));
    }

    /**
     * Memastikan user UPT pembuat data (creator) dapat mengisi hasil dan menghapus Sidang TPP UPT sebelum diverifikasi Ka Kanwil.
     */
    public function test_upt_creator_can_update_hasil_and_delete_sidang_before_kakanwil_verification(): void
    {
        $sidang = SidangTpp::create([
            'upt_id' => $this->upt->id,
            'nomor_undangan' => 'UND-CREATOR-'.time(),
            'tanggal_surat' => '2026-10-23',
            'tanggal_sidang' => '2026-10-25',
            'jam_mulai' => '09:00',
            'agenda_sidang' => 'Agenda Sidang Creator Test',
            'ketua_tpp' => 'Ketua Test',
            'sekretaris_tpp' => 'Sekretaris Test',
            'created_by' => $this->userUpt->id,
            'status_sidang' => 'dijadwalkan',
            'status_verifikasi_kanwil' => 'menunggu_sidang',
        ]);

        $this->assertTrue($sidang->canEdit($this->userUpt));
        $this->assertTrue($sidang->canDelete($this->userUpt));

        // Creator mengisi hasil sidang
        $resHasil = $this->actingAs($this->userUpt)->post(route('sidang-tpp.hasil.update', $sidang->id), [
            'nomor_berita_acara' => 'BA/UPT/TEST/999',
        ]);
        $resHasil->assertSessionHasNoErrors();
        $this->assertEquals('BA/UPT/TEST/999', $sidang->fresh()->nomor_berita_acara);

        // Creator menghapus sidang sebelum verifikasi Ka Kanwil
        $resDel = $this->actingAs($this->userUpt)->delete(route('sidang-tpp.destroy', $sidang->id));
        $resDel->assertSessionHas('success');
        $this->assertDatabaseMissing('sidang_tpp', ['id' => $sidang->id]);
    }

    /**
     * Memastikan user lain dari UPT lain atau bukan creator ditolak saat mencoba update/delete Sidang TPP.
     */
    public function test_other_user_cannot_update_or_delete_sidang_upt(): void
    {
        $otherUpt = Upt::firstOrCreate(['nama_upt' => 'LAPAS CILEGON TEST'], ['alamat' => 'Cilegon', 'kapasitas' => 500]);
        $otherUser = User::factory()->create([
            'upt_id' => $otherUpt->id,
            'role' => 'operator',
            'akses_sidang_tpp' => 'full',
        ]);

        $sidang = SidangTpp::create([
            'upt_id' => $this->upt->id,
            'nomor_undangan' => 'UND-ISOLASI-'.time(),
            'tanggal_surat' => '2026-10-23',
            'tanggal_sidang' => '2026-10-25',
            'jam_mulai' => '09:00',
            'agenda_sidang' => 'Agenda Sidang Isolasi',
            'ketua_tpp' => 'Ketua Test',
            'sekretaris_tpp' => 'Sekretaris Test',
            'created_by' => $this->userUpt->id,
            'status_sidang' => 'dijadwalkan',
            'status_verifikasi_kanwil' => 'menunggu_sidang',
        ]);

        $this->assertFalse($sidang->canEdit($otherUser));
        $this->assertFalse($sidang->canDelete($otherUser));

        $resHasil = $this->actingAs($otherUser)->post(route('sidang-tpp.hasil.update', $sidang->id), [
            'nomor_berita_acara' => 'BA/ILLEGAL/001',
        ]);
        $resHasil->assertStatus(403);

        $resDel = $this->actingAs($otherUser)->delete(route('sidang-tpp.destroy', $sidang->id));
        $resDel->assertStatus(403);
    }

    /**
     * Memastikan setelah verifikasi Ka Kanwil, data terkunci sehingga creator tidak bisa merubah/menghapus, tapi Admin tetap bisa.
     */
    public function test_after_kakanwil_verification_upt_sidang_is_locked_except_for_admin(): void
    {
        $admin = User::where('role', 'admin')->first()
            ?? User::factory()->create(['role' => 'admin', 'upt_id' => null]);

        $sidang = SidangTpp::create([
            'upt_id' => $this->upt->id,
            'nomor_undangan' => 'UND-LOCKED-'.time(),
            'tanggal_surat' => '2026-10-23',
            'tanggal_sidang' => '2026-10-25',
            'jam_mulai' => '09:00',
            'agenda_sidang' => 'Agenda Sidang Final',
            'ketua_tpp' => 'Ketua Test',
            'sekretaris_tpp' => 'Sekretaris Test',
            'created_by' => $this->userUpt->id,
            'status_sidang' => 'selesai_ttd_kakanwil',
            'status_verifikasi_kanwil' => 'sesuai',
            'tanggal_ttd_kakanwil' => '2026-10-26',
            'file_laporan_kakanwil' => 'uploads/test_laporan.pdf',
        ]);

        $this->assertTrue($sidang->isVerifiedKakanwil());
        $this->assertFalse($sidang->canEdit($this->userUpt));
        $this->assertFalse($sidang->canDelete($this->userUpt));
        $this->assertTrue($sidang->canEdit($admin));
        $this->assertTrue($sidang->canDelete($admin));

        // Creator coba edit hasil -> 403
        $resHasil = $this->actingAs($this->userUpt)->post(route('sidang-tpp.hasil.update', $sidang->id), [
            'nomor_berita_acara' => 'BA/COBA/999',
        ]);
        $resHasil->assertStatus(403);

        // Creator coba hapus -> 403
        $resDel = $this->actingAs($this->userUpt)->delete(route('sidang-tpp.destroy', $sidang->id));
        $resDel->assertStatus(403);

        // Admin menghapus sidang pasca-verifikasi -> sukses
        $resAdminDel = $this->actingAs($admin)->delete(route('sidang-tpp.destroy', $sidang->id));
        $resAdminDel->assertSessionHas('success');
        $this->assertDatabaseMissing('sidang_tpp', ['id' => $sidang->id]);
    }
}
