<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class OverstayingValidationUnitTest extends TestCase
{
    private array $baseRules = [
        'upt_id' => 'required',
        'tanggal' => 'required|date',
        'tahanan_ai' => 'required|integer|min:0',
        'tahanan_aii' => 'required|integer|min:0',
        'tahanan_aiii' => 'required|integer|min:0',
        'tahanan_aiv' => 'required|integer|min:0',
        'tahanan_av' => 'required|integer|min:0',
        'overstaying_ai' => 'required|integer|min:0|lte:tahanan_ai',
        'overstaying_aii' => 'required|integer|min:0|lte:tahanan_aii',
        'overstaying_aiii' => 'required|integer|min:0|lte:tahanan_aiii',
        'overstaying_aiv' => 'required|integer|min:0|lte:tahanan_aiv',
        'overstaying_av' => 'required|integer|min:0|lte:tahanan_av',
    ];

    private array $messages = [
        'overstaying_ai.lte' => 'Jumlah Overstaying AI (:input) tidak boleh melebihi jumlah Tahanan AI.',
        'overstaying_aii.lte' => 'Jumlah Overstaying AII (:input) tidak boleh melebihi jumlah Tahanan AII.',
        'overstaying_aiii.lte' => 'Jumlah Overstaying AIII (:input) tidak boleh melebihi jumlah Tahanan AIII.',
        'overstaying_aiv.lte' => 'Jumlah Overstaying AIV (:input) tidak boleh melebihi jumlah Tahanan AIV.',
        'overstaying_av.lte' => 'Jumlah Overstaying AV (:input) tidak boleh melebihi jumlah Tahanan AV.',
    ];

    public function test_validation_fails_when_overstaying_exceeds_tahanan(): void
    {
        $payload = [
            'upt_id' => 1,
            'tanggal' => '2026-10-16',
            'tahanan_ai' => 1,
            'overstaying_ai' => 2, // 2 > 1 (Invalid)
            'tahanan_aii' => 0,
            'overstaying_aii' => 0,
            'tahanan_aiii' => 0,
            'overstaying_aiii' => 0,
            'tahanan_aiv' => 0,
            'overstaying_aiv' => 0,
            'tahanan_av' => 0,
            'overstaying_av' => 0,
        ];

        $validator = Validator::make($payload, $this->baseRules, $this->messages);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('overstaying_ai'));
        $this->assertEquals(
            'Jumlah Overstaying AI (2) tidak boleh melebihi jumlah Tahanan AI.',
            $validator->errors()->first('overstaying_ai')
        );
    }

    public function test_validation_fails_when_tahanan_zero_and_overstaying_positive(): void
    {
        $payload = [
            'upt_id' => 1,
            'tanggal' => '2026-10-16',
            'tahanan_ai' => 0,
            'overstaying_ai' => 1, // 1 > 0 (Invalid)
            'tahanan_aii' => 0,
            'overstaying_aii' => 0,
            'tahanan_aiii' => 0,
            'overstaying_aiii' => 0,
            'tahanan_aiv' => 0,
            'overstaying_aiv' => 0,
            'tahanan_av' => 0,
            'overstaying_av' => 0,
        ];

        $validator = Validator::make($payload, $this->baseRules, $this->messages);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('overstaying_ai'));
    }

    public function test_validation_passes_when_all_are_zero(): void
    {
        $payload = [
            'upt_id' => 1,
            'tanggal' => '2026-10-16',
            'tahanan_ai' => 0,
            'overstaying_ai' => 0,
            'tahanan_aii' => 0,
            'overstaying_aii' => 0,
            'tahanan_aiii' => 0,
            'overstaying_aiii' => 0,
            'tahanan_aiv' => 0,
            'overstaying_aiv' => 0,
            'tahanan_av' => 0,
            'overstaying_av' => 0,
        ];

        $validator = Validator::make($payload, $this->baseRules, $this->messages);

        $this->assertFalse($validator->fails());
    }

    public function test_validation_passes_when_overstaying_equals_tahanan(): void
    {
        $payload = [
            'upt_id' => 1,
            'tanggal' => '2026-10-16',
            'tahanan_ai' => 5,
            'overstaying_ai' => 5,
            'tahanan_aii' => 2,
            'overstaying_aii' => 2,
            'tahanan_aiii' => 0,
            'overstaying_aiii' => 0,
            'tahanan_aiv' => 1,
            'overstaying_aiv' => 1,
            'tahanan_av' => 3,
            'overstaying_av' => 3,
        ];

        $validator = Validator::make($payload, $this->baseRules, $this->messages);

        $this->assertFalse($validator->fails());
    }

    public function test_validation_passes_when_overstaying_less_than_tahanan(): void
    {
        $payload = [
            'upt_id' => 1,
            'tanggal' => '2026-10-16',
            'tahanan_ai' => 10,
            'overstaying_ai' => 2,
            'tahanan_aii' => 4,
            'overstaying_aii' => 1,
            'tahanan_aiii' => 8,
            'overstaying_aiii' => 0,
            'tahanan_aiv' => 3,
            'overstaying_aiv' => 1,
            'tahanan_av' => 6,
            'overstaying_av' => 2,
        ];

        $validator = Validator::make($payload, $this->baseRules, $this->messages);

        $this->assertFalse($validator->fails());
    }
}
