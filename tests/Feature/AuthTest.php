<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    private array $createdUsernames = [];

    protected function tearDown(): void
    {
        if (! empty($this->createdUsernames)) {
            $users = User::whereIn('username', $this->createdUsernames)->get();
            $userIds = $users->pluck('id')->filter()->all();

            ActivityLog::whereIn('user_id', $userIds)
                ->orWhereIn('user_name', $this->createdUsernames)
                ->delete();

            User::whereIn('username', $this->createdUsernames)->delete();
        }

        parent::tearDown();
    }

    public function test_login_fails_with_wrong_password_and_logs_activity(): void
    {
        $username = 'test_fail_'.uniqid();
        $this->createdUsernames[] = $username;

        $user = User::create([
            'username' => $username,
            'password' => 'password123',
            'nama_lengkap' => 'Test Auth User',
            'role' => 'operator',
        ]);

        $response = $this->from('/login')->post('/login', [
            'username' => $username,
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('username');
        $this->assertGuest();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'aksi' => 'error',
            'modul' => 'auth',
        ]);
    }

    public function test_login_fails_with_nonexistent_username_and_logs_activity_with_null_user_id(): void
    {
        $nonExistentUsername = 'unknown_tester_'.uniqid();
        $this->createdUsernames[] = $nonExistentUsername;

        $response = $this->from('/login')->post('/login', [
            'username' => $nonExistentUsername,
            'password' => 'any_password',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('username');
        $this->assertGuest();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => null,
            'user_name' => $nonExistentUsername,
            'aksi' => 'error',
            'modul' => 'auth',
        ]);
    }

    public function test_login_succeeds_with_correct_credentials(): void
    {
        $username = 'test_ok_'.uniqid();
        $this->createdUsernames[] = $username;

        $user = User::create([
            'username' => $username,
            'password' => 'correctpassword',
            'nama_lengkap' => 'Test Success User',
            'role' => 'operator',
        ]);

        $response = $this->post('/login', [
            'username' => $username,
            'password' => 'correctpassword',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'aksi' => 'login',
            'modul' => 'auth',
        ]);
    }
}
