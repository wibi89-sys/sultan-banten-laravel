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

        // Pentahapan murni sesuai kaidah vonis: 1/3 = 1 thn (01/01/2025), 1/2 = 1 thn 6 bln (30/06/2025), 2/3 = 2 thn (01/01/2026)
        $this->assertEquals(1080, $result['total_hari_vonis']);
        $this->assertEquals('2026-01-01', $result['milestones']['tgl_2_3_murni']);
        $this->assertEquals('2025-01-01', $result['milestones']['tgl_1_3_murni']);
        $this->assertEquals('2025-06-30', $result['milestones']['tgl_1_2_murni']);
        $this->assertArrayHasKey('tgl_1_3_definitif', $result['milestones']);

        // Verifikasi ledger ada dan memiliki proyeksi remisi beserta pentahapan baru (1/3, 1/2, 2/3)
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

        // Ekspirasi definitif = Ekspirasi murni minus total remisi (240 hari dari 1 Jan 2027 -> 6 Mei 2026)
        $this->assertEquals('2026-05-06', $result['milestones']['ekspirasi_definitif']);

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

    public function test_simulasi_remisi_kasus_khusus_10_tahun_katolik_donor_darah(): void
    {
        // Kasus uji pengguna: Tahan 30/09/2022, Pidana 10 Tahun, Katolik, Donor mulai tahun ke-2
        // Sebelum Remisi Natal 2026 (yaitu pada event RT Jasa Negara 17 Agustus 2026):
        // 1/3 = 30/12/2023, 1/2 = 27/08/2025, 2/3 = 29/04/2027
        $result = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2022-09-30',
            10,
            0,
            0,
            'katolik',
            0,
            'Narapidana',
            '-',
            true,
            'jasa_negara',
            '2_3',
            2,
            '-'
        );

        $ledger = $result['proyeksi_ledger'];
        $this->assertNotEmpty($ledger);

        // Cari baris tepat sebelum Remisi Khusus Natal 2026 (yaitu baris ke-12 atau RT 2026)
        $eventRt2026 = null;
        foreach ($ledger as $idx => $row) {
            if ($row['jenis'] === 'RT' && $row['tahun'] === 2026) {
                $eventRt2026 = $row;
                break;
            }
        }

        $this->assertNotNull($eventRt2026, 'Event RT Jasa Negara 2026 harus ada.');
        $this->assertEquals('2023-12-29', $eventRt2026['tgl_1_3_baru'], '1/3 sebelum Natal 2026 (remisi 25 bulan -> 40-25 = 15 bln = 1 thn 3 bln) harus 2023-12-29.');
        $this->assertEquals('2025-08-26', $eventRt2026['tgl_1_2_baru'], '1/2 sebelum Natal 2026 (remisi 25 bulan -> 60-25 = 35 bln = 2 thn 11 bln) harus 2025-08-26.');
        $this->assertEquals('2027-04-28', $eventRt2026['tgl_2_3_baru'], '2/3 sebelum Natal 2026 (remisi 25 bulan -> 80-25 = 55 bln = 4 thn 7 bln) harus 2027-04-28.');
        $this->assertEquals('2030-09-11', $eventRt2026['perkiraan_ekspirasi_baru'], 'Ekspirasi sebelum Natal 2026 harus 2030-09-11.');
        $this->assertEquals('26 Bulan, 15 Hari', $result['total_proyeksi_remisi_teks'], 'Total remisi harus dinyatakan dalam Bulan dan Hari (tanpa akumulasi tahun).');

        // Hasil definitif akhir setelah RK Natal 2026 (potongan 26 bulan 15 hari = 795 hari):
        $this->assertEquals('2025-07-12', $result['milestones']['tgl_1_2_definitif'], '1/2 Definitif (60 bln - 26 bln 15 hr = 33 bln 15 hr = 2 thn 9 bln 15 hr) harus 12 Juli 2025.');
        $this->assertEquals('2027-03-14', $result['milestones']['tgl_2_3_definitif'], '2/3 Definitif (80 bln - 26 bln 15 hr = 53 bln 15 hr = 4 thn 5 bln 15 hr) harus 14 Maret 2027.');
        $this->assertEquals('2027-01-13', $result['milestones']['tgl_mulai_pengusulan'], 'Mulai usul SDP harus 13 Januari 2027 (H-60 hari dari 14 Maret 2027).');
        $this->assertEquals('2030-07-28', $result['milestones']['ekspirasi_definitif'], 'Ekspirasi Akhir harus 28 Juli 2030.');
    }

    public function test_simulasi_remisi_dengan_tanggal_putusan_dan_remisi_susulan(): void
    {
        // Ditahan 01/01/2024, Vonis 3 Tahun, Putusan inkrah 01/09/2024 (8 bulan setelah ditahan)
        // Pada event RU 17/08/2024: hari dijalani = 229 hari (memenuhi syarat > 180 hari / 6 bulan),
        // tetapi karena event terjadi sebelum tgl putusan, remisi berstatus Remisi Susulan.
        $result = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2024-01-01',
            3,
            0,
            0,
            'islam',
            0,
            'Ahmad Fauzi',
            'BI.012/2024',
            true,
            'none',
            '2_3',
            1,
            'Pasal 378 KUHP',
            '2024-09-01'
        );

        $this->assertEquals('2024-09-01', $result['tgl_putusan']);
        $this->assertEquals(244, $result['masa_tahanan_hari'], 'Masa tahanan 01/01/2024 s.d. 01/09/2024 adalah 244 hari.');
        $this->assertNotEmpty($result['masa_tahanan_teks']);
        $this->assertEquals(836, $result['sisa_hari_pasca_putusan'], 'Sisa pidana pasca-putusan (1080 - 244) = 836 hari.');
        $this->assertNotEmpty($result['sisa_pidana_pasca_putusan_teks']);

        // Periksa event RU 17/08/2024 di dalam ledger
        $eventRu2024 = null;
        foreach ($result['proyeksi_ledger'] as $row) {
            if ($row['jenis'] === 'RU' && $row['tahun'] === 2024) {
                $eventRu2024 = $row;
                break;
            }
        }

        $this->assertNotNull($eventRu2024, 'Event RU 17 Agustus 2024 harus tercatat.');
        $this->assertTrue($eventRu2024['eligible']);
        $this->assertEquals('Memenuhi Syarat (Remisi Susulan)', $eventRu2024['keterangan_status']);
        $this->assertStringContainsString('Remisi Susulan', $eventRu2024['keterangan_alasan']);

        // Pastikan tanggal putusan juga masuk ke array formatted
        $this->assertNotNull($result['formatted']['tgl_putusan']);
    }

    public function test_simulasi_remisi_tanggal_putusan_lebih_awal_dari_tgl_awal_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2024-01-01',
            3,
            0,
            0,
            'islam',
            0,
            'Ahmad Fauzi',
            'BI.012/2024',
            true,
            'none',
            '2_3',
            1,
            'Pasal 378 KUHP',
            '2023-12-01' // Lebih awal dari tgl_awal
        );
    }

    public function test_hitungan_manual_teks_dan_html_dihasilkan_dengan_lengkap_dan_fleksibel(): void
    {
        $result = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2023-01-01',
            3,
            6,
            0,
            'islam',
            0,
            'Budi Santoso',
            'REG.099/2023',
            true,
            'none',
            '2_3',
            1,
            'Pasal 363 KUHP',
            '2023-06-15'
        );

        $this->assertArrayHasKey('hitungan_manual_teks', $result);
        $this->assertArrayHasKey('hitungan_manual_html', $result);

        $teks = $result['hitungan_manual_teks'];
        $html = $result['hitungan_manual_html'];

        // Verifikasi rincian perhitungan matematis terstruktur (A, B, C, D) pada format teks
        $this->assertStringContainsString('Tgl. Putusan', $teks);
        $this->assertStringContainsString('Nama                : Budi Santoso', $teks);
        $this->assertStringContainsString('A. POTONGAN TAHANAN (POTAH)', $teks);
        $this->assertStringContainsString('B. EKSPIRASI SEBELUM REMISI', $teks);
        $this->assertStringContainsString('C. PENTAHAPAN MASA PIDANA (SETELAH REMISI)', $teks);
        $this->assertStringContainsString('D. USUL SDP (H-2 BULAN)', $teks);
        $this->assertStringNotContainsString('tonggak', strtolower($teks));

        // Verifikasi baris perantara konversi bulan dan kembali ke tahun pada 1/2 dan 2/3 (3 thn 6 bln = 42 Bulan -> 21 Bulan & 28 Bulan)
        $this->assertStringContainsString('42 Bulan', $teks);
        $this->assertStringContainsString('21 Bulan', $teks);
        $this->assertStringContainsString('28 Bulan', $teks);
        $this->assertStringContainsString('1 Tahun, 4 Bulan', $teks);
        $this->assertStringContainsString('1 Tahun, 11 Bulan', $teks);
        $this->assertStringContainsString('42 Bulan', $html);
        $this->assertStringContainsString('21 Bulan', $html);
        $this->assertStringContainsString('28 Bulan', $html);

        // Verifikasi penghapusan kurung hari redundan pada potah & remisi
        $this->assertStringNotContainsString('(165 Hari)', $teks);
        $this->assertStringNotContainsString('(165 Hari)', $html);

        // Kasus Vonis 10 Tahun (Memverifikasi konversi 120 Bulan -> 1/2 = 60 Bulan dan 2/3 = 80 Bulan)
        $res10 = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2020-01-01',
            10,
            0,
            0,
            'islam',
            0,
            'Ahmad 10 Thn',
            'REG.10/2020'
        );
        $this->assertStringContainsString('120 Bulan', $res10['hitungan_manual_teks']);
        $this->assertStringContainsString('60 Bulan', $res10['hitungan_manual_teks']);
        $this->assertStringContainsString('80 Bulan', $res10['hitungan_manual_teks']);
        $this->assertStringContainsString('120 Bulan', $res10['hitungan_manual_html']);
        $this->assertStringContainsString('60 Bulan', $res10['hitungan_manual_html']);
        $this->assertStringContainsString('80 Bulan', $res10['hitungan_manual_html']);

        // Verifikasi format visual html
        $this->assertStringContainsString('Nama', $html);
        $this->assertStringContainsString('Budi Santoso', $html);
        $this->assertStringContainsString('A. POTONGAN TAHANAN (POTAH)', $html);
        $this->assertStringContainsString('B. EKSPIRASI SEBELUM REMISI', $html);
        $this->assertStringContainsString('C. PENTAHAPAN MASA PIDANA (SETELAH REMISI)', $html);
        $this->assertStringContainsString('D. USUL SDP (H-2 BULAN)', $html);
        $this->assertStringNotContainsString('tonggak', strtolower($html));

        // Verifikasi PDF Report
        $pdfOutput = TelramCalculatorService::generatePdfReport($result);
        $this->assertNotEmpty($pdfOutput);
        $this->assertStringStartsWith('%PDF-', $pdfOutput);
    }

    public function test_kalkulasi_dengan_pidana_subsider(): void
    {
        // Uji coba hitung maju dengan vonis pokok 2 tahun + subsider 3 bulan
        $maju = $this->service->hitungMaju('2024-01-01', 2, 0, 0, 0, 3, 0);
        $this->assertTrue($maju['has_subsider']);
        $this->assertEquals('2026-01-01', $maju['tgl_ekspirasi']);
        // 2026-01-01 + 3 bulan (90 hari) = 2026-04-01
        $this->assertEquals('2026-04-01', $maju['tgl_ekspirasi_subsider']);
        $this->assertEquals('3 Bulan', $maju['subsider_teks']);

        // Uji coba simulasi remisi dengan subsider 6 bulan
        $result = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2024-01-01',
            3,
            0,
            0,
            'islam',
            0,
            'WBP Uji Subsider',
            'BI.777/2024',
            true,
            'none',
            '2_3',
            1,
            'Pasal 114 UU Narkotika',
            '2024-06-01',
            0,
            6,
            0 // Subsider 6 bulan
        );

        $this->assertTrue($result['has_subsider']);
        $this->assertEquals('6 Bulan', $result['subsider_teks']);
        $this->assertNotNull($result['milestones']['ekspirasi_subsider_murni']);
        $this->assertNotNull($result['milestones']['ekspirasi_subsider_definitif']);
        $this->assertStringContainsString('Pidana Subsider', $result['hitungan_manual_teks']);
        $this->assertStringContainsString('Pidana Subsider', $result['hitungan_manual_html']);

        // Pastikan ekspirasi subsider definitif lebih lama 180 hari (6 bulan) dari ekspirasi pokok definitif
        $tglPokokDef = Carbon::parse($result['milestones']['ekspirasi_definitif']);
        $tglSubDef = Carbon::parse($result['milestones']['ekspirasi_subsider_definitif']);
        $this->assertEquals(180, (int) $tglPokokDef->diffInDays($tglSubDef));

        // Verifikasi PDF juga dapat digenerate dengan subsider dan tepat 2 halaman
        $pdf = TelramCalculatorService::generatePdfReport($result);
        $this->assertNotEmpty($pdf);
        $pageCount = preg_match_all('/\/Type\s*\/Page\b/', $pdf);
        $this->assertEquals(2, $pageCount, "PDF harus tepat 2 halaman, ditemukan: {$pageCount} halaman.");
    }

    public function test_judul_program_dinamis_pb_vs_cb(): void
    {
        // 1. Kasus CB: Vonis <= 1 tahun 6 bulan (1 tahun 0 bulan = 360 hari)
        $resCb = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2024-01-01',
            1,
            0,
            0,
            'islam',
            0,
            'WBP CB'
        );

        $this->assertEquals('CB', $resCb['kode_program']);
        $this->assertEquals('PERHITUNGAN CUTI BERSYARAT (CB)', $resCb['judul_program']);
        $this->assertStringContainsString('PERHITUNGAN CUTI BERSYARAT (CB)', $resCb['hitungan_manual_teks']);
        $this->assertStringContainsString('PERHITUNGAN CUTI BERSYARAT (CB)', $resCb['hitungan_manual_html']);

        // 2. Kasus Batas Persis CB: 1 tahun 6 bulan (540 hari)
        $resBatasCb = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2024-01-01',
            1,
            6,
            0,
            'islam',
            0,
            'WBP Batas CB'
        );

        $this->assertEquals('CB', $resBatasCb['kode_program']);
        $this->assertEquals('PERHITUNGAN CUTI BERSYARAT (CB)', $resBatasCb['judul_program']);

        // 3. Kasus PB: Vonis > 1 tahun 6 bulan (e.g. 1 tahun 6 bulan 1 hari atau 2 tahun)
        $resPb = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2024-01-01',
            2,
            0,
            0,
            'islam',
            0,
            'WBP PB'
        );

        $this->assertEquals('PB', $resPb['kode_program']);
        $this->assertEquals('PERHITUNGAN PEMBEBASAN BERSYARAT (PB)', $resPb['judul_program']);
        $this->assertStringContainsString('PERHITUNGAN PEMBEBASAN BERSYARAT (PB)', $resPb['hitungan_manual_teks']);
        $this->assertStringContainsString('PERHITUNGAN PEMBEBASAN BERSYARAT (PB)', $resPb['hitungan_manual_html']);
    }

    public function test_subsider_bb_vs_sb_dan_nominal_denda(): void
    {
        // 1. Kasus BB (Belum Bayar): Denda Rp 1.000.000.000, Subsider 3 Bulan
        $resBb = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2024-01-01',
            3,
            0,
            0,
            'islam',
            0,
            'WBP Denda BB',
            'BI.01',
            true,
            'none',
            '2_3',
            1,
            'Pasal 114 UU Narkotika',
            null,
            0,
            3,
            0,
            '1000000000',
            'bb'
        );

        $this->assertTrue($resBb['has_subsider']);
        $this->assertEquals('bb', $resBb['subsider_status']);
        $this->assertEquals(1000000000.0, $resBb['denda_nominal']);
        $this->assertEquals('Rp 1.000.000.000,-', $resBb['denda_nominal_formatted']);
        $this->assertStringContainsString('Rp 1.000.000.000,-', $resBb['subsider_identitas_teks']);
        $this->assertStringContainsString('(Belum Bayar / BB)', $resBb['subsider_identitas_teks']);

        // Pada BB, kurungan 3 bulan (90 hari) harus menambah 2/3 dan ekspirasi
        $tgl23Pokok = Carbon::parse($resBb['milestones']['tgl_2_3_definitif']);
        $tgl23Sub = Carbon::parse($resBb['milestones']['tgl_2_3_subsider_definitif']);
        $this->assertEquals(90, (int) $tgl23Pokok->diffInDays($tgl23Sub));

        $tglEksPokok = Carbon::parse($resBb['milestones']['ekspirasi_definitif']);
        $tglEksSub = Carbon::parse($resBb['milestones']['ekspirasi_subsider_definitif']);
        $this->assertEquals(90, (int) $tglEksPokok->diffInDays($tglEksSub));

        // Hitungan manual memuat rincian 2/3 (+Subsider BB)
        $this->assertStringContainsString('2/3 Masa Pidana (+Subsider BB)', $resBb['hitungan_manual_teks']);
        $this->assertStringContainsString('2/3 (+Subsider BB)', $resBb['hitungan_manual_html']);

        // 2. Kasus SB (Sudah Bayar): Denda Rp 1.000.000.000, Subsider 3 Bulan (SB)
        $resSb = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2024-01-01',
            3,
            0,
            0,
            'islam',
            0,
            'WBP Denda SB',
            'BI.02',
            true,
            'none',
            '2_3',
            1,
            'Pasal 114 UU Narkotika',
            null,
            0,
            3,
            0,
            '1000000000',
            'sb'
        );

        $this->assertTrue($resSb['has_subsider']);
        $this->assertEquals('sb', $resSb['subsider_status']);
        $this->assertStringContainsString('Rp 1.000.000.000,-', $resSb['subsider_identitas_teks']);
        $this->assertStringContainsString('(Sudah Bayar / SB)', $resSb['subsider_identitas_teks']);

        // Pada SB, kurungan subsider TIDAK dijalani (null)
        $this->assertNull($resSb['milestones']['ekspirasi_subsider_definitif']);
        $this->assertNull($resSb['milestones']['tgl_2_3_subsider_definitif']);

        // Hitungan manual tidak menambahkan baris 2/3 (+Subsider BB)
        $this->assertStringNotContainsString('2/3 (+Subsider BB)', $resSb['hitungan_manual_teks']);

        // 3. Kasus Tanpa Subsider dan Tanpa Denda
        $resNol = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2024-01-01',
            2,
            0,
            0,
            'islam'
        );
        $this->assertEquals('-', $resNol['subsider_identitas_teks']);
        $this->assertStringContainsString('Pidana Subsider     : -', $resNol['hitungan_manual_teks']);
    }

    public function test_format_tanggal_numeric_dd_mm_yyyy(): void
    {
        $res = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2024-01-05',
            2,
            0,
            0,
            'islam'
        );

        $this->assertArrayHasKey('numeric', $res);
        $this->assertEquals('05/01/2024', $res['numeric']['tgl_awal']);
        $this->assertEquals('05/01/2026', $res['numeric']['ekspirasi_sebelum_remisi']);
        // Pastikan tanggal matematika di hitungan manual mencakup format dd/mm/yyyy
        $this->assertStringContainsString('05/01/2024', $res['hitungan_manual_teks']);
    }

    public function test_pdf_report_vonis_panjang_dengan_potah_dan_subsider_bb(): void
    {
        // Narapidana 10 tahun, ada potah (ditahan 2020-01-01, putusan 2020-06-01), subsider 3 bulan BB, denda 1 milyar
        $res = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2020-01-01',
            10,
            0,
            0,
            'islam',
            0,
            'Narapidana Kasus Berat',
            'BI.888/2020',
            true,
            'pemuka',
            '2_3',
            1,
            'Pasal 114 ayat (2) UU 35/2009',
            '2020-06-01',
            0,
            3,
            0,
            '1000000000',
            'bb'
        );

        $pdf = TelramCalculatorService::generatePdfReport($res);
        $this->assertNotEmpty($pdf);
        $pageCount = preg_match_all('/\/Type\s*\/Page\b/', $pdf);
        // Pada vonis 10 tahun (15 baris event remisi), tabel ledger remisi terbagi rapi
        // dan Lampiran Hitungan Manual berada di halaman lampiran tersendiri (3 halaman total)
        $this->assertEquals(3, $pageCount, "PDF narapidana 10 tahun terbagi rapi 3 halaman, ditemukan: {$pageCount} halaman.");
    }

    public function test_riwayat_penahanan_multi_periode_dengan_penangguhan_dan_pembantaran(): void
    {
        // Skenario ilustrasi sesuai kesepakatan:
        // 1. Rutan: 01/01/2024 s.d. 31/01/2024 (31 hari) -> faktor 1/1 = 31 hari
        // 2. Kota: 01/02/2024 s.d. 29/02/2024 (29 hari) -> faktor 1/5 = ceil(29/5) = 6 hari (favor reo)
        // 3. Pembantaran RS: 01/03/2024 s.d. 15/03/2024 (15 hari) -> faktor 0 = 0 hari (jeda)
        // 4. Masuk Kembali Rutan: 16/03/2024 s.d. 31/03/2024 (16 hari) -> faktor 1/1 = 16 hari
        // Total Kalender = 91 hari. Total Potah = 31 + 6 + 0 + 16 = 53 hari. Total Jeda = 15 hari.
        // Tgl Putusan = 31/03/2024.
        // Tgl Ditahan Efektif = 31/03/2024 - 53 hari = 07/02/2024.

        $riwayat = [
            ['jenis' => 'rutan', 'tgl_mulai' => '2024-01-01', 'tgl_selesai' => '2024-01-31', 'keterangan' => 'Rutan Kelas I Jakarta Pusat'],
            ['jenis' => 'kota', 'tgl_mulai' => '2024-02-01', 'tgl_selesai' => '2024-02-29', 'keterangan' => 'Tahanan Kota Jakarta'],
            ['jenis' => 'bantar', 'tgl_mulai' => '2024-03-01', 'tgl_selesai' => '2024-03-15', 'keterangan' => 'Rawat Inap RS Pengayoman'],
            ['jenis' => 'masuk_kembali', 'tgl_mulai' => '2024-03-16', 'tgl_selesai' => '2024-03-31', 'keterangan' => 'Masuk Kembali ke Rutan'],
        ];

        $res = $this->service->hitungProyeksiRemisiDanIntegrasi(
            '2024-01-01',
            10,
            0,
            0,
            'islam',
            0,
            'Putri Candrawathi',
            'BI/78/2023',
            true,
            'none',
            '2_3',
            1,
            'Pasal 340 KUHP',
            '2024-03-31',
            0,
            0,
            0,
            0,
            'bb',
            $riwayat
        );

        $this->assertTrue($res['has_riwayat_penahanan']);
        $this->assertCount(4, $res['riwayat_penahanan']);
        $this->assertEquals(91, $res['total_hari_kalender_tahanan']);
        $this->assertEquals(53, $res['masa_tahanan_hari']);
        $this->assertEquals(15, $res['total_hari_jeda_tahanan']);
        $this->assertEquals('2024-01-01', $res['tgl_awal_fisik']);
        $this->assertEquals('2024-02-07', $res['tgl_mulai_ditahan_efektif']);
        $this->assertEquals('07/02/2024', $res['numeric']['tgl_mulai_ditahan_efektif']);

        // Verifikasi teks hitungan manual mencakup tabel riwayat dan tgl efektif
        $this->assertStringContainsString('Rincian Riwayat Penahanan', $res['hitungan_manual_teks']);
        $this->assertStringContainsString('Pembantaran', $res['hitungan_manual_teks']);
        $this->assertStringContainsString('Tgl. Mulai Ditahan (Efektif)', $res['hitungan_manual_teks']);
        $this->assertStringContainsString('07/02/2024', $res['hitungan_manual_teks']);

        // Verifikasi hitungan manual HTML juga mencakup tabel riwayat
        $this->assertStringContainsString('A. POTONGAN TAHANAN (POTAH) &amp; RIWAYAT PENAHANAN', $res['hitungan_manual_html']);
        $this->assertStringContainsString('Pasal 22 &amp; 31 KUHAP', $res['hitungan_manual_html']);

        // Verifikasi PDF dapat digenerate dengan sukses
        $pdf = TelramCalculatorService::generatePdfReport($res);
        $this->assertNotEmpty($pdf);
    }

    public function test_riwayat_penahanan_tahanan_rumah_dengan_asas_favor_reo(): void
    {
        // Tahanan Rumah 10 hari: 10/3 = 3.33 -> ceil = 4 hari potah
        $riwayat = [
            ['jenis' => 'rumah', 'tgl_mulai' => '2024-05-01', 'tgl_selesai' => '2024-05-10', 'keterangan' => 'Penahanan Rumah'],
        ];

        $calc = $this->service->hitungRiwayatPenahanan($riwayat, Carbon::parse('2024-05-10')->startOfDay());

        $this->assertEquals(10, $calc['total_hari_kalender']);
        $this->assertEquals(4, $calc['total_hari_potah'], '10 hari tahanan rumah dibagi 3 dengan asas favor reo (ceil) menghasilkan 4 hari potah.');
    }
}
