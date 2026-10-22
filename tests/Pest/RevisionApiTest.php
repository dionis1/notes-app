<?php

use App\Actions\CreateNoteAction;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps history private and refuses snapshots from another note', function () {
    $owner = User::factory()->create();
    $note = app(CreateNoteAction::class)($owner, ['title' => 'Private', 'content' => 'Private history']);
    $another = app(CreateNoteAction::class)($owner, ['title' => 'Other', 'content' => 'Different history']);
    $revision = $another->revisions()->firstOrFail()->id;
    $this->actingAs($owner);
    $this->getJson("/api/v1/notes/{$note->id}/revisions")->assertOk()->assertJsonPath('data.0.version', 1);
    $this->postJson("/api/v1/notes/{$note->id}/revisions/{$revision}/restore", ['version' => 1])->assertNotFound();
    $this->actingAs(User::factory()->create());
    $this->getJson("/api/v1/notes/{$note->id}/revisions")->assertNotFound();
});

it('validates pages and searches for the literal zero string', function () {
    $owner = User::factory()->create();
    Note::factory()->create(['user_id' => $owner->id, 'title' => 'Item 0', 'content' => 'Zero']);
    Note::factory()->create(['user_id' => $owner->id, 'title' => 'Other', 'content' => 'No number']);
    $this->actingAs($owner);
    $this->getJson('/api/v1/notes?q=0')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/notes?page=-1')->assertUnprocessable();
    $this->getJson('/api/v1/notes?important=bad')->assertUnprocessable();
});

it('returns authentication errors for unknown tokens and non-JSON clients', function () {
    $this->get('/api/v1/notes', ['Accept' => 'text/html'])->assertUnauthorized();
    $this->withToken('missing-token')->getJson('/api/v1/notes')->assertUnauthorized();
});
