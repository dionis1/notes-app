<?php

namespace App\Actions;

use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class DeleteNoteAction
{
    public function __invoke(User $actor, Note $note, int $version): void
    {
        Gate::forUser($actor)->authorize('delete', $note);
        if ($actor->notes()->whereKey($note->id)->where('version', $version)->delete() !== 1) {
            throw new ConflictHttpException('This note changed. Reload it before deleting.');
        }
    }
}
