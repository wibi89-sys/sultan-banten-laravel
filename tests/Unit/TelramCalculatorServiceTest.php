<?php

namespace Tests\Unit;

use App\Services\TelramCalculatorService;
use Carbon\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class TelramCalculatorServiceTest extends TestCase
{
    private TelramCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TelramCalculatorService;
    }

    public function test_hitung_maju_1_hari(): void
    {
        // 01/02/2026 Vonis 1 hari -> ekspirasi 02/02/2026
        $result = $this->service->hitungMaju('2026-02-01', 0, 0, 1);

        $this->assertEquals('2026-02-02', $result['tgl_ekspirasi']);
        $this->assertEquals(1, $result['total_hari_vonis']);
        $this->assertEquals('1 Hari', $result['lama_pidana_teks']);
    }

    public function test_hitung_maju_1_bulan(): void
    {
        // 01/01/2026 Vonis 1 bulan -> ekspirasi 31/01/2026
        $result = $this->service->hitungMaju('2026-01-01', 0, 1, 0);

        $this->assertEquals('2026-01-31', $result['tgl_ekspirasi']);
        $this->assertEquals(30, $result['total_hari_vonis']);
        $this->assertEquals('1 Bulan', $result['lama_pidana_teks']);
    }

    public function test_hitung_maju_1_bulan_september(): void
    {
        // 20/09/2026 Vonis 1 bulan (30 hari) -> ekspirasi 20/10/2026 (September 30 hari)
        $result = $this->service->hitungMaju('2026-09-20', 0, 1, 0);

        $this->assertEquals('2026-10-20', $result['tgl_ekspirasi']);
        $this->assertEquals(30, $result['total_hari_vonis']);
        $this->assertEquals('1 Bulan', $result['lama_pidana_teks']);
    }

    public function test_hitung_maju_1_tahun(): void
    {
        // 01/01/2026 Vonis 1 tahun -> ekspirasi 01/01/2027 (di tanggal & bulan yang sama)
        $result = $this->service->hitungMaju('2026-01-01', 1, 0, 0);

        $this->assertEquals('2027-01-01', $result['tgl_ekspirasi']);
        $this->assertEquals(360, $result['total_hari_vonis']);
        $this->assertEquals('1 Tahun', $result['lama_pidana_teks']);
    }

    public function test_hitung_maju_kombinasi_tahun_dan_bulan(): void
    {
        // 01/01/2026 Vonis 1 tahun 1 bulan -> 01/01/2027 + 30 hari = 31/01/2027
        $result = $this->service->hitungMaju('2026-01-01', 1, 1, 0);

        $this->assertEquals('2027-01-31', $result['tgl_ekspirasi']);
        $this->assertEquals(390, $result['total_hari_vonis']);
        $this->assertEquals('1 Tahun, 1 Bulan', $result['lama_pidana_teks']);
    }

    public function test_hitung_maju_kombinasi_september(): void
    {
        // 20/09/2026 Vonis 1 tahun 1 bulan -> 20/09/2027 + 30 hari = 20/10/2027
        $result = $this->service->hitungMaju('2026-09-20', 1, 1, 0);

        $this->assertEquals('2027-10-20', $result['tgl_ekspirasi']);
        $this->assertEquals(390, $result['total_hari_vonis']);
        $this->assertEquals('1 Tahun, 1 Bulan', $result['lama_pidana_teks']);
    }

    public function test_hitung_maju_kombinasi_tahun_bulan_dan_hari(): void
    {
        // 01/01/2026 Vonis 1 tahun 1 bulan 5 hari -> 31/01/2027 + 5 hari = 05/02/2027
        $result = $this->service->hitungMaju('2026-01-01', 1, 1, 5);

        $this->assertEquals('2027-02-05', $result['tgl_ekspirasi']);
        $this->assertEquals(395, $result['total_hari_vonis']);
        $this->assertEquals('1 Tahun, 1 Bulan, 5 Hari', $result['lama_pidana_teks']);
    }

    public function test_kabisat_ditahan_pada_29_februari(): void
    {
        // Ditahan pada 29 Februari 2024 (kabisat)
        // Vonis 1 hari -> 01/03/2024
        $res1Hari = $this->service->hitungMaju('2024-02-29', 0, 0, 1);
        $this->assertEquals('2024-03-01', $res1Hari['tgl_ekspirasi']);

        // Vonis 1 tahun -> 2025 bukan kabisat, jatuh pada hari terakhir Februari (28/02/2025)
        $res1Thn = $this->service->hitungMaju('2024-02-29', 1, 0, 0);
        $this->assertEquals('2025-02-28', $res1Thn['tgl_ekspirasi']);

        // Vonis 4 tahun -> 2028 adalah kabisat, jatuh tepat pada 29/02/2028
        $res4Thn = $this->service->hitungMaju('2024-02-29', 4, 0, 0);
        $this->assertEquals('2028-02-29', $res4Thn['tgl_ekspirasi']);
    }

    public function test_kabisat_mode_mundur(): void
    {
        // Mundur dari 28/02/2025 ke 29/02/2024 -> 1 Tahun
        $res2 = $this->service->hitungMundur('2024-02-29', '2025-02-28');
        $this->assertEquals(1, $res2['tahun']);
        $this->assertEquals(0, $res2['bulan']);
        $this->assertEquals(0, $res2['hari']);

        // Mundur dari 29/02/2028 ke 29/02/2024 -> 4 Tahun
        $res3 = $this->service->hitungMundur('2024-02-29', '2028-02-29');
        $this->assertEquals(4, $res3['tahun']);
        $this->assertEquals(0, $res3['bulan']);
        $this->assertEquals(0, $res3['hari']);
    }

    public function test_hitung_maju_fails_when_all_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->hitungMaju('2026-01-01', 0, 0, 0);
    }

    public function test_hitung_mundur_1_hari(): void
    {
        $result = $this->service->hitungMundur('2026-02-01', '2026-02-02');

        $this->assertEquals(0, $result['tahun']);
        $this->assertEquals(0, $result['bulan']);
        $this->assertEquals(1, $result['hari']);
        $this->assertEquals('1 Hari', $result['lama_pidana_teks']);
    }

    public function test_hitung_mundur_1_bulan(): void
    {
        $result = $this->service->hitungMundur('2026-01-01', '2026-01-31');

        $this->assertEquals(0, $result['tahun']);
        $this->assertEquals(1, $result['bulan']);
        $this->assertEquals(0, $result['hari']);
        $this->assertEquals('1 Bulan', $result['lama_pidana_teks']);
    }

    public function test_hitung_mundur_1_tahun(): void
    {
        $result = $this->service->hitungMundur('2026-01-01', '2027-01-01');

        $this->assertEquals(1, $result['tahun']);
        $this->assertEquals(0, $result['bulan']);
        $this->assertEquals(0, $result['hari']);
        $this->assertEquals('1 Tahun', $result['lama_pidana_teks']);
    }

    public function test_hitung_mundur_kombinasi(): void
    {
        $result = $this->service->hitungMundur('2026-01-01', '2027-02-05');

        $this->assertEquals(1, $result['tahun']);
        $this->assertEquals(1, $result['bulan']);
        $this->assertEquals(5, $result['hari']);
        $this->assertEquals('1 Tahun, 1 Bulan, 5 Hari', $result['lama_pidana_teks']);
    }

    public function test_hitung_mundur_fails_when_ekspirasi_before_awal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->hitungMundur('2026-05-10', '2026-05-01');
    }

    public function test_hitung_sisa_pidana_normal(): void
    {
        // Dari 16 Oktober 2026 ke 20 Oktober 2027 (1 tahun 4 hari)
        $result = $this->service->hitungSisaPidana('2026-10-16', '2027-10-20');

        $this->assertEquals(1, $result['tahun']);
        $this->assertEquals(0, $result['bulan']);
        $this->assertEquals(4, $result['hari']);
        $this->assertEquals('1 Tahun, 4 Hari', $result['sisa_pidana_teks']);
    }

    public function test_hitung_sisa_pidana_kombinasi(): void
    {
        // Dari 01/01/2026 ke 05/02/2027 (1 thn 1 bln 5 hari)
        $result = $this->service->hitungSisaPidana('2026-01-01', '2027-02-05');

        $this->assertEquals(1, $result['tahun']);
        $this->assertEquals(1, $result['bulan']);
        $this->assertEquals(5, $result['hari']);
        $this->assertEquals('1 Tahun, 1 Bulan, 5 Hari', $result['sisa_pidana_teks']);
    }

    public function test_hitung_sisa_pidana_hari_yang_sama(): void
    {
        $result = $this->service->hitungSisaPidana('2026-10-16', '2026-10-16');

        $this->assertEquals(0, $result['tahun']);
        $this->assertEquals(0, $result['bulan']);
        $this->assertEquals(0, $result['hari']);
        $this->assertEquals('0 Hari (Bebas Hari Ini)', $result['sisa_pidana_teks']);
    }

    public function test_hitung_sisa_pidana_fails_when_ekspirasi_before_perhitungan(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->hitungSisaPidana('2026-10-20', '2026-10-16');
    }

    public function test_simulasi_remisi_dan_integrasi_vonis_3_tahun_pb(): void
    {
        // Narapidana ditahan 1 Januari 2024, Vonis 3 Tahun (36 Bulan), Agama Islam
        $result = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2024-01-01',
            3,
            0,
            0,
            'islam'
        );

        $this->assertEquals('PB', $result['jenis_program']);
        $this->assertEquals('Pembebasan Bersyarat (PB)', $result['nama_program']);
        $this->assertEquals('2027-01-01', $result['milestones']['ekspirasi_murni']);

        // 2/3 murni dari 3 tahun (36 bulan) = 24 bulan (2 tahun persis kalender)
        $this->assertEquals(1080, $result['total_hari_vonis']);
        $this->assertEquals('2026-01-01', $result['milestones']['tgl_2_3_murni']);
        $this->assertEquals('2025-01-01', $result['milestones']['tgl_1_3_murni']);
        $this->assertEquals('2025-07-01', $result['milestones']['tgl_1_2_murni']);
        $this->assertArrayHasKey('tgl_1_3_definitif', $result['milestones']);

        // Verifikasi ledger ada dan memiliki proyeksi remisi beserta tonggak baru (1/3, 1/2, 2/3)
        $this->assertNotEmpty($result['proyeksi_ledger']);
        $this->assertArrayHasKey('tgl_1_3_baru', $result['proyeksi_ledger'][0]);
        $this->assertArrayHasKey('tgl_1_2_baru', $result['proyeksi_ledger'][0]);
        $this->assertArrayHasKey('tgl_2_3_baru', $result['proyeksi_ledger'][0]);

        // Event pertama: Idul Fitri 10 April 2024 (kurang dari 6 bulan dari 1 Jan 2024 -> ~100 hari)
        $firstEvent = $result['proyeksi_ledger'][0];
        $this->assertEquals('RK', $firstEvent['jenis']);
        $this->assertFalse($firstEvent['eligible']);
        $this->assertEquals(0, $firstEvent['besaran_hari']);

        // Event kedua: RU 17 Agustus 2024 (telah menjalani > 6 bulan -> 229 hari)
        $secondEvent = $result['proyeksi_ledger'][1];
        $this->assertEquals('RU', $secondEvent['jenis']);
        $this->assertTrue($secondEvent['eligible']);
        $this->assertEquals(30, $secondEvent['besaran_hari']); // 1 Bulan (karena < 12 bulan)

        // Verifikasi rumus SDP: 2/3 definitif = 2/3 murni dikurangi akumulasi remisi
        $totalRemisi = $result['total_proyeksi_remisi_hari'];
        $this->assertGreaterThan(0, $totalRemisi);

        // Ekspirasi definitif = Ekspirasi murni minus total remisi
        $tglEkspirasiMurni = Carbon::parse($result['milestones']['ekspirasi_murni']);
        $expectedEkspirasiDefinitif = $tglEkspirasiMurni->copy()->subDays($totalRemisi)->toDateString();
        $this->assertEquals($expectedEkspirasiDefinitif, $result['milestones']['ekspirasi_definitif']);

        // Tanggal mulai pengusulan PB = 60 hari sebelum 2/3 definitif
        $tgl23Definitif = Carbon::parse($result['milestones']['tgl_2_3_definitif']);
        $expectedPengusulan = $tgl23Definitif->copy()->subDays(60)->toDateString();
        $this->assertEquals($expectedPengusulan, $result['milestones']['tgl_mulai_pengusulan']);
    }

    public function test_simulasi_remisi_dan_integrasi_vonis_pendek_cb(): void
    {
        // Narapidana ditahan 1 Januari 2026, Vonis 1 Tahun 2 Bulan (14 Bulan -> <= 18 Bulan = Cuti Bersyarat)
        $result = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2026-01-01',
            1,
            2,
            0,
            'kristen'
        );

        $this->assertEquals('CB', $result['jenis_program']);
        $this->assertEquals('Cuti Bersyarat (CB)', $result['nama_program']);
        $this->assertStringContainsString('Cuti Bersyarat', $result['keterangan_program']);
    }

    public function test_simulasi_remisi_dengan_remisi_awal(): void
    {
        // Narapidana pindahan yang sudah memiliki SK remisi 60 hari
        $result = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2025-01-01',
            2,
            0,
            0,
            'islam',
            60
        );

        $this->assertEquals(60, $result['remisi_awal_hari']);
        $this->assertGreaterThanOrEqual(60, $result['total_proyeksi_remisi_hari']);
    }

    public function test_simulasi_remisi_dengan_dasawarsa(): void
    {
        // Narapidana ditahan 1 Januari 2024, Vonis 3 Tahun (1080 hari)
        // Tahun 2025 adalah HUT ke-80 RI ((2025 - 1945) % 10 === 0)
        // Remisi Dasawarsa: 1/12 vonis pokok = 1080 / 12 = 90 hari (maks 90 hari)
        $result = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2024-01-01',
            3,
            0,
            0,
            'islam',
            0,
            'Ahmad Fauzi',
            'BI.123/2024',
            true, // include dasawarsa
            'none'
        );

        $this->assertEquals('Ahmad Fauzi', $result['nama_wbp']);
        $this->assertEquals('BI.123/2024', $result['no_register']);
        $this->assertTrue($result['include_dasawarsa']);

        // Cari event RD (Remisi Dasawarsa) di dalam ledger
        $dasawarsaEvent = null;
        foreach ($result['proyeksi_ledger'] as $ev) {
            if ($ev['jenis'] === 'RD' && $ev['tahun'] === 2025) {
                $dasawarsaEvent = $ev;
                break;
            }
        }

        $this->assertNotNull($dasawarsaEvent, 'Event Remisi Dasawarsa 2025 harus ditemukan di ledger.');
        $this->assertEquals(90, $dasawarsaEvent['besaran_hari']);
        $this->assertStringContainsString('HUT ke-80 RI', $dasawarsaEvent['nama_event']);
        $this->assertStringContainsString('1/12 dari vonis pokok', $dasawarsaEvent['keterangan_alasan']);
    }

    public function test_simulasi_remisi_dengan_remisi_tambahan_pemuka(): void
    {
        // Vonis 3 tahun, kategori_tambahan = pemuka (+1/3 dari RU)
        $result = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2024-01-01',
            3,
            0,
            0,
            'islam',
            0,
            'Siti Aminah',
            'BI.456/2024',
            false,
            'pemuka'
        );

        $this->assertEquals('pemuka', $result['kategori_tambahan']);

        // Pada 17 Agustus 2024, RU ke-1 (<12 bln) = 30 hari (1 bulan)
        // Pemuka mendapat 1/3 dari 30 hari = 10 hari
        $tambahanEvent = null;
        foreach ($result['proyeksi_ledger'] as $ev) {
            if ($ev['jenis'] === 'RT' && $ev['tahun'] === 2024) {
                $tambahanEvent = $ev;
                break;
            }
        }

        $this->assertNotNull($tambahanEvent, 'Event Remisi Tambahan Pemuka harus ditemukan.');
        $this->assertEquals(10, $tambahanEvent['besaran_hari']);
        $this->assertStringContainsString('Remisi Tambahan Pemuka', $tambahanEvent['nama_event']);
        $this->assertStringContainsString('1/3 dari Remisi Umum', $tambahanEvent['keterangan_alasan']);
    }

    public function test_simulasi_remisi_dengan_remisi_tambahan_jasa_negara(): void
    {
        // Vonis 3 tahun, kategori_tambahan = jasa_negara (+1/2 dari RU)
        $result = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2024-01-01',
            3,
            0,
            0,
            'islam',
            0,
            'Bambang',
            'BI.789/2024',
            false,
            'jasa_negara'
        );

        $this->assertEquals('jasa_negara', $result['kategori_tambahan']);

        // Pada 17 Agustus 2024, RU ke-1 (<12 bln) = 30 hari (1 bulan)
        // Jasa Negara mendapat 1/2 dari 30 hari = 15 hari
        $tambahanEvent = null;
        foreach ($result['proyeksi_ledger'] as $ev) {
            if ($ev['jenis'] === 'RT' && $ev['tahun'] === 2024) {
                $tambahanEvent = $ev;
                break;
            }
        }

        $this->assertNotNull($tambahanEvent, 'Event Remisi Tambahan Jasa Negara harus ditemukan.');
        $this->assertEquals(15, $tambahanEvent['besaran_hari']);
        $this->assertStringContainsString('Remisi Tambahan Jasa Negara', $tambahanEvent['nama_event']);
        $this->assertStringContainsString('1/2 dari Remisi Umum', $tambahanEvent['keterangan_alasan']);
    }

    public function test_simulasi_remisi_dengan_remisi_tambahan_mulai_tahun_kedua(): void
    {
        // Vonis 4 tahun, pemuka, mulai remisi tambahan sejak tahun ke-2 (tahunMulaiTambahan = 2)
        $result = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2024-01-01',
            4,
            0,
            0,
            'islam',
            0,
            'Ahmad Santoso',
            'BI.101/2024',
            false,
            'pemuka',
            'ekspirasi',
            2 // mulai tahun ke-2
        );

        $this->assertEquals(2, $result['tahun_mulai_tambahan']);

        // RU ke-1 (2024): TIDAK boleh ada Remisi Tambahan
        $rt2024 = null;
        // RU ke-2 (2025): HARUS ada Remisi Tambahan
        $rt2025 = null;

        foreach ($result['proyeksi_ledger'] as $ev) {
            if ($ev['jenis'] === 'RT') {
                if ($ev['tahun'] === 2024) {
                    $rt2024 = $ev;
                } elseif ($ev['tahun'] === 2025) {
                    $rt2025 = $ev;
                }
            }
        }

        $this->assertNull($rt2024, 'Remisi Tambahan pada tahun ke-1 tidak boleh diberikan bila mulai tahun ke-2.');
        $this->assertNotNull($rt2025, 'Remisi Tambahan pada tahun ke-2 harus diberikan.');
        $this->assertEquals(30, $rt2025['besaran_hari'], 'RU ke-2 adalah 90 hari, maka 1/3 dari 90 hari = 30 hari.');
    }

    public function test_generate_pdf_report_returns_valid_pdf_binary(): void
    {
        $result = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2024-01-01',
            3,
            0,
            0,
            'islam',
            0,
            'Testing WBP PDF',
            'BI.999/2024',
            true,
            'pemuka',
            '2_3',
            1,
            'Pasal 363 KUHP'
        );

        $this->assertEquals('Pasal 363 KUHP', $result['perkara_pasal']);

        $pdfBinary = $this->service->generatePdfReport($result);

        $this->assertNotEmpty($pdfBinary);
        $this->assertStringStartsWith('%PDF-', $pdfBinary);
    }

    public function test_simulasi_remisi_standar_2_3_berhenti_saat_2_3_dan_tidak_mencapai_2028(): void
    {
        // Narapidana vonis 3 tahun dari 2026-01-01
        // Dengan standar 2/3, narapidana mencapai 2/3 pada Agustus 2027 (setelah RU 2027)
        // Sehingga remisi tahun 2028 TIDAK boleh ada dalam ledger
        $result = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2026-01-01',
            3,
            0,
            0,
            'islam',
            0,
            'Joko Susanto',
            'REG.001/2026',
            true,
            'none',
            '2_3'
        );

        $this->assertEquals('2_3', $result['target_basis']);
        $this->assertStringContainsString('2/3', $result['target_basis_label']);

        $yearsInLedger = array_column($result['proyeksi_ledger'], 'tahun');
        $this->assertContains(2026, $yearsInLedger, 'Event tahun 2026 harus ada.');
        $this->assertContains(2027, $yearsInLedger, 'Event tahun 2027 harus ada.');
        $this->assertNotContains(2028, $yearsInLedger, 'Standar 2/3 PB/CB: event tahun 2028 TIDAK boleh dihitung karena WBP sudah bebas bersyarat di luar Lapas.');
    }

    public function test_simulasi_remisi_basis_ekspirasi_berlanjut_sampai_2028(): void
    {
        // Pada basis ekspirasi penuh, perhitungan dilanjutkan sampai bebas murni (2028)
        $result = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2026-01-01',
            3,
            0,
            0,
            'islam',
            0,
            'Joko Susanto',
            'REG.001/2026',
            true,
            'none',
            'ekspirasi'
        );

        $this->assertEquals('ekspirasi', $result['target_basis']);
        $yearsInLedger = array_column($result['proyeksi_ledger'], 'tahun');
        $this->assertContains(2028, $yearsInLedger, 'Pada basis ekspirasi penuh, event 2028 harus ada.');
    }
}
