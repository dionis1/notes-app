<?php

use App\Actions\CreateNoteAction;
use App\Actions\DeleteNoteAction;
use App\Actions\RestoreNoteRevisionAction;
use App\Actions\UpdateNoteAction;
use App\Models\Note;
use App\Models\NoteRevision;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

uses(RefreshDatabase::class);

it('authorizes direct actions and rejects invalid content', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $note = app(CreateNoteAction::class)($owner, ['title' => 'Private', 'content' => 'Original']);
    expect(fn () => app(UpdateNoteAction::class)($other, $note, ['title' => 'Stolen', 'version' => 1]))->toThrow(AuthorizationException::class);
    expect(fn () => app(DeleteNoteAction::class)($other, $note, 1))->toThrow(AuthorizationException::class);
    expect(fn () => app(RestoreNoteRevisionAction::class)($other, $note, $note->revisions()->first(), 1))->toThrow(AuthorizationException::class);
    expect(fn () => app(CreateNoteAction::class)($owner, ['title' => '', 'content' => '']))->toThrow(ValidationException::class);
    expect(Note::count())->toBe(1)->and(NoteRevision::count())->toBe(1);
});

it('restores a private snapshot as a new version without rewriting history', function () {
    $owner = User::factory()->create();
    $note = app(CreateNoteAction::class)($owner, ['title' => 'First', 'content' => 'Original', 'is_important' => true]);
    $original = $note->revisions()->firstOrFail();
    $note = app(UpdateNoteAction::class)($owner, $note, ['title' => 'Second', 'content' => 'Changed', 'is_important' => false, 'version' => 1]);
    $restored = app(RestoreNoteRevisionAction::class)($owner, $note, $original, 2);
    expect($restored->version)->toBe(3)->and($restored->title)->toBe('First')
        ->and($restored->is_important)->toBeTrue()->and($original->fresh()->content)->toBe('Original')
        ->and($restored->revisions()->first()->restored_from_version)->toBe(1);
    expect(fn () => app(UpdateNoteAction::class)($owner, $note, ['title' => 'Stale', 'version' => 2]))->toThrow(ConflictHttpException::class);
    expect(fn () => app(DeleteNoteAction::class)($owner, $note, 2))->toThrow(ConflictHttpException::class);
    expect(fn () => app(RestoreNoteRevisionAction::class)($owner, $note, $original, 2))->toThrow(ConflictHttpException::class);
    expect(NoteRevision::count())->toBe(3);
});

it('rolls back note changes if snapshot persistence fails', function () {
    $owner = User::factory()->create();
    $note = app(CreateNoteAction::class)($owner, ['title' => 'Original', 'content' => 'Content']);
    NoteRevision::creating(fn () => throw new RuntimeException('Snapshot unavailable'));
    try {
        expect(fn () => app(UpdateNoteAction::class)($owner, $note, ['title' => 'Unsaved', 'version' => 1]))->toThrow(RuntimeException::class);
        expect(fn () => app(CreateNoteAction::class)($owner, ['title' => 'Unsaved', 'content' => 'Content']))->toThrow(RuntimeException::class);
    } finally {
        NoteRevision::flushEventListeners();
    }
    expect($note->fresh()->title)->toBe('Original')->and($note->fresh()->version)->toBe(1)
        ->and(Note::count())->toBe(1)->and(NoteRevision::count())->toBe(1);
});

it('snapshots legacy owned notes on edit and removes history with permanent deletion', function () {
    $legacy = Note::factory()->create(['title' => 'Before history']);
    $owner = User::findOrFail($legacy->user_id);
    $current = app(UpdateNoteAction::class)($owner, $legacy, ['title' => 'After history', 'version' => 1]);
    expect(NoteRevision::count())->toBe(2)->and(NoteRevision::where('version', 1)->first()->title)->toBe('Before history');
    app(DeleteNoteAction::class)($owner, $current, 2);
    expect(Note::count())->toBe(0)->and(NoteRevision::count())->toBe(0);
});
