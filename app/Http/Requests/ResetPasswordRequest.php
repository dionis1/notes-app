<?php

namespace App\Http\Requests;

use App\Support\CredentialRules;

class ResetPasswordRequest extends CredentialRequest
{
    public function rules(): array
    {
        return ['token' => ['required', 'string', 'max:255'], 'email' => CredentialRules::email(), 'password' => CredentialRules::password()];
    }
}
