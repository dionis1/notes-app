<?php

namespace App\Actions;

use App\Models\Note;
use App\Models\NoteRevision;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class RestoreNoteRevisionAction extends NoteAction
{
    public function __invoke(User $actor, Note $note, NoteRevision $revision, int $version): Note
    {
        Gate::forUser($actor)->authorize('restore', $note);
        abort_unless($revision->note_id === $note->id, 404);

        return $this->persist($actor, $note, [
            'title' => $revision->title, 'content' => $revision->content,
            'is_important' => $revision->is_important, 'version' => $version,
        ], $revision->version);
    }
}
