<?php

namespace App\Repositories;

use App\Models\Note;
use App\Repositories\Interfaces\NoteRepositoryInterface;
use Illuminate\Support\Collection;

class NoteRepository implements NoteRepositoryInterface
{
    public function all(bool $importantOnly = false): Collection
    {
        $query = Note::query();
        if ($importantOnly) {
            $query->where('is_important', true);
        }
        return $query->latest()->get();
    }

    public function find(int $id): ?Note
    {
        return Note::find($id);
    }

    public function create(array $data): Note
    {
        return Note::create($data);
    }

    public function update(Note $note, array $data): bool
    {
        return $note->update($data);
    }

    public function delete(Note $note): bool
    {
        return $note->delete();
    }
}
