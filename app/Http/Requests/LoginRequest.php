<?php

namespace App\Http\Requests;

use App\Support\CredentialRules;

class LoginRequest extends CredentialRequest
{
    public function rules(): array
    {
        return ['email' => CredentialRules::email(), 'password' => ['required', 'string']];
    }
}
