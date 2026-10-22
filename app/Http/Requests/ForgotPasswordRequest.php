<?php

namespace App\Http\Requests;

use App\Support\CredentialRules;

class ForgotPasswordRequest extends CredentialRequest
{
    public function rules(): array
    {
        return ['email' => CredentialRules::email()];
    }
}
