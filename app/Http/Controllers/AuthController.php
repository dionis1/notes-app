<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $data = $request->validated();
        $user = User::create($data);
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return $request->expectsJson()
            ? response()->json(['data' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email]], 201)
            : redirect()->route('notes');
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();
        if (! Auth::guard('web')->attempt($credentials)) {
            throw ValidationException::withMessages(['email' => 'The credentials do not match our records.']);
        }
        $request->session()->regenerate();

        return $request->expectsJson()
            ? response()->json(['message' => 'Signed in.'])
            : redirect()->intended(route('notes'));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson()
            ? response()->json(['message' => 'Signed out.'])
            : redirect()->route('login');
    }

    public function forgot(ForgotPasswordRequest $request)
    {
        Password::sendResetLink($request->only('email'));

        $message = 'If that account exists, a password reset link has been sent.';

        return $request->expectsJson()
            ? response()->json(['message' => $message])
            : back()->with('status', $message);
    }

    public function reset(ResetPasswordRequest $request)
    {
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Password reset. Please sign in.'])
            : redirect()->route('login')->with('status', 'Password reset. Please sign in.');
    }
}
