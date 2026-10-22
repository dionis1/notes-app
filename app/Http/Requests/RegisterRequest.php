<?php

namespace App\Http\Requests;

use App\Support\CredentialRules;

class RegisterRequest extends CredentialRequest
{
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100'], 'email' => [...CredentialRules::email(), 'unique:users'], 'password' => CredentialRules::password()];
    }
}
