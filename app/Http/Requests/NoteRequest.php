<?php

namespace App\Http\Requests;

use App\Support\NoteRules;
use Illuminate\Foundation\Http\FormRequest;

class NoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return NoteRules::content(! $this->isMethod('post'));
    }
}
