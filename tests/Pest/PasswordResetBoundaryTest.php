<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('accepts a reset token once and rejects its replay', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);
    $input = ['token' => $token, 'email' => $user->email, 'password' => 'ChangedNotes!2026', 'password_confirmation' => 'ChangedNotes!2026'];
    $this->postJson('/reset-password', $input)->assertOk();
    $this->postJson('/reset-password', $input)->assertUnprocessable();
});

it('rejects an expired reset token', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);
    $this->travel(61)->minutes();
    $this->postJson('/reset-password', ['token' => $token, 'email' => $user->email, 'password' => 'ChangedNotes!2026', 'password_confirmation' => 'ChangedNotes!2026'])->assertUnprocessable();
});
