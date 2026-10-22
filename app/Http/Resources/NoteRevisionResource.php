<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NoteRevisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id, 'version' => $this->version, 'title' => $this->title,
            'content' => $this->content, 'is_important' => $this->is_important,
            'restored_from_version' => $this->restored_from_version,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
