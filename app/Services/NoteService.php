<?php

namespace App\Services;

use App\Models\Note;
use App\Repositories\Interfaces\NoteRepositoryInterface;
use Illuminate\Support\Collection;

class NoteService
{
    protected NoteRepositoryInterface $repo;

    public function __construct(NoteRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Get all notes, optionally only important ones.
     */
    public function getAllNotes(bool $importantOnly = false): Collection
    {
        return $this->repo->all($importantOnly);
    }

    /**
     * Find a specific note by its ID.
     */
    public function findNote(int $id): ?Note
    {
        return $this->repo->find($id);
    }

    /**
     * Create or update a note.
     */
    public function saveNote(?int $id, array $data): string
    {
        if ($id) {
            $note = $this->repo->find($id);
            if ($note) {
                $this->repo->update($note, $data);
                return 'Note updated';
            }
        }

        $this->repo->create($data);
        return 'Note created';
    }

    /**
     * Delete a note by ID.
     */
    public function deleteNote(int $id): string
    {
        $note = $this->repo->find($id);
        if ($note) {
            $this->repo->delete($note);
            return 'Note deleted';
        }

        return 'Note not found';
    }
}
