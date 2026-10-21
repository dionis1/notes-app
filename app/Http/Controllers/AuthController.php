<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private function emailRules(): array
    {
        return ['required', 'string', 'email', 'max:255', 'not_regex:/[\\x00-\\x20\\x7f]/'];
    }

    private function passwordRules(): array
    {
        return ['required', 'string', 'confirmed', PasswordRule::min(12), 'max:72', function (string $attribute, mixed $value, \Closure $fail) {
            if (is_string($value) && (strlen($value) > 72 || str_contains($value, "\0"))) {
                $fail('The password must be at most 72 bytes and cannot contain null characters.');
            }
        }];
    }

    public function register(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email', '')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => [...$this->emailRules(), 'unique:users'],
            'password' => $this->passwordRules(),
        ]);
        $user = User::create($data);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('notes');
    }

    public function login(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email', '')))]);
        $credentials = $request->validate(['email' => $this->emailRules(), 'password' => ['required', 'string']]);
        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'The credentials do not match our records.']);
        }
        $request->session()->regenerate();

        return redirect()->intended(route('notes'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function forgot(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email', '')))]);
        $request->validate(['email' => $this->emailRules()]);
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'If that account exists, a password reset link has been sent.');
    }

    public function reset(Request $request)
    {
        $request->merge(['email' => strtolower(trim((string) $request->input('email', '')))]);
        $request->validate([
            'token' => ['required'], 'email' => $this->emailRules(),
            'password' => $this->passwordRules(),
        ]);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return redirect()->route('login')->with('status', 'Password reset. Please sign in.');
    }
}
