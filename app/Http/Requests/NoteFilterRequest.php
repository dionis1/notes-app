<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NoteFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['q' => ['nullable', 'string', 'max:100'], 'important' => ['nullable', 'boolean'], 'per_page' => ['nullable', 'integer', 'between:1,50'], 'page' => ['nullable', 'integer', 'min:1']];
    }
}
