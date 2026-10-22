<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['note_id', 'version', 'title', 'content', 'is_important', 'restored_from_version'];

    protected function casts(): array
    {
        return ['note_id' => 'integer', 'version' => 'integer', 'is_important' => 'boolean', 'restored_from_version' => 'integer'];
    }

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }
}
