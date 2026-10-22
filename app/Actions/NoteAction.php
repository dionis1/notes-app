<?php

namespace App\Actions;

use App\Models\Note;
use App\Models\User;
use App\Support\NoteRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

abstract class NoteAction
{
    protected function persist(User $actor, Note $note, array $input, ?int $restoredFromVersion = null): Note
    {
        Gate::forUser($actor)->authorize('update', $note);
        $data = Validator::make($input, NoteRules::content(true))->validate();
        $version = $data['version'];
        unset($data['version']);

        return DB::transaction(function () use ($actor, $note, $data, $version, $restoredFromVersion): Note {
            $current = $actor->notes()->findOrFail($note->id);
            if ($current->version !== $version) {
                throw new ConflictHttpException('This note changed elsewhere. Your draft is preserved. Reload the note before saving again.');
            }
            // Preserve the current state of notes created before revision history existed.
            app(RecordNoteRevision::class)($current);
            $changed = $actor->notes()->whereKey($note->id)->where('version', $version)
                ->update($data + ['version' => $version + 1]);
            if ($changed !== 1) {
                throw new ConflictHttpException('This note changed elsewhere. Your draft is preserved. Reload the note before saving again.');
            }
            $current->refresh();
            app(RecordNoteRevision::class)($current, $restoredFromVersion);

            return $current;
        }, 3);
    }
}
