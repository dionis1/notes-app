<?php

namespace App\Actions;

use App\Models\Note;
use App\Models\NoteRevision;

// Internal snapshot writer; invoke inside a note action's transaction.
final class RecordNoteRevision
{
    public function __invoke(Note $note, ?int $restoredFromVersion = null): NoteRevision
    {
        return $note->revisions()->firstOrCreate(['version' => $note->version], [
            'title' => $note->title, 'content' => $note->content,
            'is_important' => $note->is_important, 'restored_from_version' => $restoredFromVersion,
        ]);
    }
}
