<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $updating = $this->isMethod('put') || $this->isMethod('patch');

        return [
            'title' => [$updating ? 'sometimes' : 'required', 'string', 'max:255'],
            'content' => [$updating ? 'sometimes' : 'required', 'string', 'max:10000'],
            'is_important' => ['sometimes', 'boolean'],
            'version' => [$updating ? 'required' : 'prohibited', 'integer', 'min:1'],
        ];
    }
}
