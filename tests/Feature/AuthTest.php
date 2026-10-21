<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_environment_is_isolated_from_container_settings(): void
    {
        $this->assertTrue(app()->environment('testing'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->assertSame('array', config('session.driver'));
    }

    public function test_guest_cannot_read_notes(): void
    {
        $this->get('/notes')->assertRedirect('/login');
    }

    public function test_registration_login_and_logout(): void
    {
        $this->post('/register', ['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'correct-horse-123', 'password_confirmation' => 'correct-horse-123'])->assertRedirect('/notes');
        $this->assertAuthenticated();
        $this->assertTrue(Hash::check('correct-horse-123', User::first()->password));
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->post('/login', ['email' => 'ada@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'ada@example.com', 'password' => 'correct-horse-123'])->assertRedirect('/notes');
        $this->assertAuthenticated();
    }

    public function test_registration_rejects_short_password_and_duplicate_email(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);
        $this->post('/register', ['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'short', 'password_confirmation' => 'short'])->assertSessionHasErrors(['email', 'password']);
    }

    public function test_control_characters_in_email_and_multibyte_password_overflow_are_rejected(): void
    {
        $this->post('/register', ['name' => 'Ada', 'email' => "ada@example.com\r\nBcc: other@example.com", 'password' => 'correct-horse-123', 'password_confirmation' => 'correct-horse-123'])->assertSessionHasErrors('email');
        $password = str_repeat('😀', 19);
        $this->post('/register', ['name' => 'Ada', 'email' => 'ada@example.com', 'password' => $password, 'password_confirmation' => $password])->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_changing_password_revokes_an_existing_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/notes')->assertOk();
        $user->update(['password' => 'a-different-password']);
        $this->get('/notes')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'missing@example.com', 'password' => 'wrong']);
        }
        $this->post('/login', ['email' => 'missing@example.com', 'password' => 'wrong'])->assertStatus(429);
    }

    public function test_password_reset_and_invalid_token(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function ($notification) use (&$token) {
            $token = $notification->token;

            return true;
        });
        $data = ['email' => $user->email, 'password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
        $this->post('/reset-password', $data + ['token' => 'bad'])->assertSessionHasErrors('email');
        $this->post('/reset-password', $data + ['token' => $token])->assertRedirect('/login');
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }
}
