<?php

namespace App\Actions;

use App\Models\Note;
use App\Models\User;

final class UpdateNoteAction extends NoteAction
{
    public function __invoke(User $actor, Note $note, array $input): Note
    {
        return $this->persist($actor, $note, $input);
    }
}
