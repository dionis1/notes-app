<?php

namespace App\Services;

use App\Models\Note;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class NoteService
{
    public function find(User $user, int $id): Note
    {
        return $user->notes()->findOrFail($id);
    }

    public function save(User $user, ?int $id, ?int $version, array $data): Note
    {
        if ($id === null) {
            return $user->notes()->create($data);
        }
        $note = $this->find($user, $id);
        Gate::forUser($user)->authorize('update', $note);
        // Compare and update in one SQL statement: timestamps alone can miss same-second edits.
        $changed = $user->notes()->whereKey($id)->where('version', $version)
            ->update($data + ['version' => $note->version + 1]);
        if ($changed !== 1) {
            throw ValidationException::withMessages(['conflict' => 'This note changed in another session. Your draft is preserved. Reload the note before saving again.']);
        }

        return $this->find($user, $id);
    }

    public function delete(User $user, int $id, int $version): void
    {
        $note = $this->find($user, $id);
        Gate::forUser($user)->authorize('delete', $note);
        if ($user->notes()->whereKey($id)->where('version', $version)->delete() !== 1) {
            throw ValidationException::withMessages(['conflict' => 'This note changed. Reload it before deleting.']);
        }
    }
}
