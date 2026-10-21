<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SpaAuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_json_registration_and_session_authentication(): void
    {
        $this->postJson('/register', [
            'name' => 'New writer', 'email' => 'writer@example.test',
            'password' => 'safe-example-password-123', 'password_confirmation' => 'safe-example-password-123',
        ])->assertCreated()->assertJsonPath('data.name', 'New writer');

        $this->assertAuthenticated();
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.email', 'writer@example.test');
        $this->postJson('/logout')->assertOk();
        $this->assertGuest('web');
        // Requests share the test container, unlike separate browser requests.
        Auth::forgetGuards();
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_invalid_json_credentials_are_rejected(): void
    {
        User::factory()->create(['email' => 'known@example.test']);
        $this->postJson('/login', ['email' => 'known@example.test', 'password' => 'wrong'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
    }
}
