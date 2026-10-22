<?php

namespace App\Support;

final class NoteRules
{
    public static function content(bool $updating = false): array
    {
        return [
            'title' => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'content' => [$updating ? 'sometimes' : 'required', 'string', 'max:10000'],
            'is_important' => ['sometimes', 'boolean'],
            'version' => [$updating ? 'required' : 'prohibited', 'integer', 'min:1'],
        ];
    }
}
