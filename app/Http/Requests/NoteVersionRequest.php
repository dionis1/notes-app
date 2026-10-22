<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NoteVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return ['version' => ['required', 'integer', 'min:1']];
    }
}
