<?php

namespace App\Actions;

use App\Models\Note;
use App\Models\User;
use App\Support\NoteRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

final class CreateNoteAction
{
    public function __invoke(User $actor, array $input): Note
    {
        Gate::forUser($actor)->authorize('create', Note::class);
        $data = Validator::make($input, NoteRules::content())->validate();

        return DB::transaction(function () use ($actor, $data): Note {
            $note = $actor->notes()->create($data);
            app(RecordNoteRevision::class)($note);

            return $note;
        }, 3);
    }
}
