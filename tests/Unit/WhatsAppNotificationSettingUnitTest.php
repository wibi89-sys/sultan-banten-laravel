<?php

namespace Tests\Unit;

use App\Services\WhatsAppService;
use PHPUnit\Framework\TestCase;

class WhatsAppNotificationSettingUnitTest extends TestCase
{
    public function test_default_templates_contain_required_tags(): void
    {
        $tppTemplate = WhatsAppService::getDefaultSidangTppTemplate();
        $this->assertStringContainsString('{nama_upt}', $tppTemplate);
        $this->assertStringContainsString('{agenda}', $tppTemplate);
        $this->assertStringContainsString('{hari_tanggal}', $tppTemplate);

        $mutasiUsulanTemplate = WhatsAppService::getDefaultMutasiUsulanTemplate();
        $this->assertStringContainsString('{upt_asal}', $mutasiUsulanTemplate);
        $this->assertStringContainsString('{nomor_surat}', $mutasiUsulanTemplate);
        $this->assertStringContainsString('{total_usulan}', $mutasiUsulanTemplate);
        $this->assertStringContainsString('{link_detail}', $mutasiUsulanTemplate);

        $mutasiDisetujuiTemplate = WhatsAppService::getDefaultMutasiDisetujuiTemplate();
        $this->assertStringContainsString('{upt_asal}', $mutasiDisetujuiTemplate);
        $this->assertStringContainsString('{upt_tujuan}', $mutasiDisetujuiTemplate);
        $this->assertStringContainsString('{nomor_surat_kanwil}', $mutasiDisetujuiTemplate);
        $this->assertStringContainsString('{total_disetujui}', $mutasiDisetujuiTemplate);
    }
}
