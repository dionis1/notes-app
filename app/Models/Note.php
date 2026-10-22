<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Note extends Model
{
    use HasFactory;

    protected $attributes = ['version' => 1, 'is_important' => false];

    protected $fillable = ['title', 'content', 'is_important'];

    protected $casts = ['is_important' => 'boolean', 'user_id' => 'integer', 'version' => 'integer'];

    public function revisions(): HasMany
    {
        return $this->hasMany(NoteRevision::class)->orderByDesc('version');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
