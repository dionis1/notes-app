<?php

namespace App\Repositories\Interfaces;

use App\Models\Note;
use Illuminate\Support\Collection;

interface NoteRepositoryInterface
{
    public function all(bool $importantOnly = false): Collection;
    public function find(int $id): ?Note;
    public function create(array $data): Note;
    public function update(Note $note, array $data): bool;
    public function delete(Note $note): bool;
}
