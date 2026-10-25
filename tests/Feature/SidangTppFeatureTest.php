<?php

namespace Tests\Feature;

use App\Models\SidangTpp;
use App\Models\Upt;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
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

        // Kanwil user: upt_id is null
        $this->userKanwil = User::whereNull('upt_id')->first()
            ?? User::factory()->create(['upt_id' => null, 'role' => 'admin']);

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
}
