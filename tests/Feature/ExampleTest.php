<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Sultan Banten');
    }

    public function test_public_user_can_access_dashboard(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Statistik Utama');
    }

    public function test_unauthenticated_user_accessing_kroscek_is_redirected_to_login(): void
    {
        $response = $this->get('/kroscek');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::first();
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Statistik Utama');
        $response->assertSee('Pidum dan Pidsus');
        $response->assertSee('Peta Lokasi UPT di Banten');
    }

    public function test_authenticated_user_can_access_kroscek(): void
    {
        $user = User::where('role', 'admin')->first() ?? User::first();
        $response = $this->actingAs($user)->get('/kroscek');
        $response->assertStatus(200);
        $response->assertSee('Kroscek Data Tahanan');
        $response->assertSee('Bebas Harian Input');
        $response->assertSee('Status & Jam Input 4 Modul Laporan', false);
        $response->assertSee('kroscek-table-grid');
    }

    public function test_operator_user_can_access_kroscek_mandiri(): void
    {
        $operator = User::where('role', 'operator')->whereNotNull('upt_id')->first();
        if ($operator) {
            $response = $this->actingAs($operator)->get('/kroscek');
            $response->assertStatus(200);
            $response->assertSee('Verifikasi Mandiri');
            $response->assertSee('Status & Jam Input 4 Modul Laporan Harian', false);
        }
    }

    public function test_authenticated_user_can_access_bebas_harian(): void
    {
        $user = User::first();
        $response = $this->actingAs($user)->get('/bebas-harian');
        $response->assertStatus(200);
        $response->assertSee('Pencatatan Bebas Harian');
        $response->assertSee('Input Bebas Hari Ini');
    }
}
