<?php

use App\Models\Note;
use App\Models\NoteRevision;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('preserves personal data and edited demo records on repeated seeds', function () {
    $personal = Note::factory()->create(['title' => 'Personal note']);
    $this->seed(DemoSeeder::class);
    $writer = User::where('email', 'writer@demo.notes.test')->firstOrFail();
    expect($writer->notes()->count())->toBe(12);
    $note = $writer->notes()->firstOrFail();
    $note->update(['title' => 'My changed demo title']);
    $counts = [User::count(), Note::count(), NoteRevision::count()];
    $this->seed(DemoSeeder::class);
    expect([User::count(), Note::count(), NoteRevision::count()])->toBe($counts)
        ->and($note->fresh()->title)->toBe('My changed demo title')
        ->and($personal->fresh()->title)->toBe('Personal note');
    $this->actingAs($writer)->getJson('/api/v1/notes?per_page=50')->assertJsonCount(12, 'data')->assertJsonMissing(['title' => 'A separate private notebook']);
});

it('refuses production demo credentials', function () {
    $this->app->instance('env', 'production');
    expect(fn () => app(DemoSeeder::class)())->toThrow(RuntimeException::class)->and(User::count())->toBe(0);
});
