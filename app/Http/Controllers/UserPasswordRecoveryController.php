<?php

namespace App\Http\Controllers;

use App\Mail\UserPasswordResetCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class UserPasswordRecoveryController extends Controller
{
    private const MAX_CODE_ATTEMPTS = 5;

    public function index(): View
    {
        $passwordResetRequests = DB::table('password_reset_tokens')
            ->join('users', 'users.email', '=', 'password_reset_tokens.email')
            ->select([
                'password_reset_tokens.email',
                'password_reset_tokens.created_at',
                'password_reset_tokens.code_expires_at',
            ])
            ->orderByDesc('password_reset_tokens.created_at')
            ->paginate(20);

        return view('password-reset-requests.index', compact('passwordResetRequests'));
    }

    public function requestCode(Request $request): RedirectResponse
    {
        $email = $request->input('email');
        $request->merge([
            'email' => is_string($email) ? trim(strip_tags($email)) : $email,
        ]);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $userExists = User::where('email', $validated['email'])->exists();
        if ($userExists) {
            $verificationCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $tokenHash = Hash::make(Str::random(64));

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $validated['email']],
                [
                    'token' => $tokenHash,
                    'created_at' => now(),
                    'code_hash' => $verificationCode,
                    'code_expires_at' => now()->addMinutes(10),
                    'code_attempts' => 0,
                ]
            );

            try {
                Mail::to($validated['email'])->send(new UserPasswordResetCode(
                    $validated['email'],
                    $verificationCode,
                ));
            } catch (Throwable $exception) {
                DB::table('password_reset_tokens')
                    ->where('email', $validated['email'])
                    ->where('token', $tokenHash)
                    ->delete();

                Log::error('User password reset verification email failed.', [
                    'email' => $validated['email'],
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return back()->with(
            'status',
            'If a BrakeSafe account exists for that email, a password reset verification code has been sent.'
        );
    }

    public function resetForm(): View
    {
        return view('auth.user-password-reset');
    }

    public function sendCode(Request $request): RedirectResponse
    {
        $email = $request->input('email');
        $request->merge([
            'email' => is_string($email) ? trim(strip_tags($email)) : $email,
        ]);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);
        $verificationCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $resetRequest = DB::transaction(function () use ($validated, $verificationCode): ?object {
            $userExists = User::where('email', $validated['email'])->exists();
            $lockedRequest = DB::table('password_reset_tokens')
                ->where('email', $validated['email'])
                ->lockForUpdate()
                ->first();

            if (!$userExists || !$lockedRequest) {
                return null;
            }

            DB::table('password_reset_tokens')
                ->where('email', $validated['email'])
                ->update([
                    'token' => Hash::make(Str::random(64)),
                    'code_hash' => $verificationCode,
                    'code_expires_at' => now()->addMinutes(10),
                    'code_attempts' => 0,
                    'created_at' => now(),
                ]);

            return (object) ['email' => $validated['email']];
        });

        if ($resetRequest === null) {
            return redirect()->route('users.password-requests.index')->withErrors([
                'password_reset_request' => 'The password reset request or user account could not be found.',
            ]);
        }

        try {
            Mail::to($resetRequest->email)->send(new UserPasswordResetCode(
                $resetRequest->email,
                $verificationCode,
            ));
        } catch (Throwable $exception) {
            DB::table('password_reset_tokens')
                ->where('email', $resetRequest->email)
                ->where('code_hash', $verificationCode)
                ->update([
                    'token' => Hash::make(Str::random(64)),
                    'code_hash' => null,
                    'code_expires_at' => null,
                    'code_attempts' => 0,
                ]);

            Log::error('User password reset verification email failed.', [
                'email' => $resetRequest->email,
                'error' => $exception->getMessage(),
            ]);

            return redirect()->route('users.password-requests.index')->withErrors([
                'password_reset_request' => 'Unable to send the verification code. Please check mail configuration and try again.',
            ]);
        }

        return redirect()
            ->route('users.password-requests.index')
            ->with('status', 'Password reset verification code sent to '.$resetRequest->email.'.');
    }

    public function resetWithCode(Request $request): RedirectResponse
    {
        $email = $request->input('email');
        $code = $request->input('verification_code');
        $request->merge([
            'email' => is_string($email) ? trim(strip_tags($email)) : $email,
            'verification_code' => is_string($code) ? trim(strip_tags($code)) : $code,
        ]);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'verification_code' => ['required', 'digits:6'],
            'password' => [
                'required',
                'string',
                Password::min(8)->mixedCase()->numbers()->symbols()->rules(['regex:/[^\p{L}\p{N}\s]/u']),
                'confirmed',
            ],
        ]);

        $result = DB::transaction(function () use ($validated): string {
            $resetRequest = DB::table('password_reset_tokens')
                ->where('email', $validated['email'])
                ->lockForUpdate()
                ->first();

            if (!$resetRequest || !$resetRequest->code_hash) {
                return 'missing';
            }

            if (!$resetRequest->code_expires_at || now()->greaterThan($resetRequest->code_expires_at)) {
                DB::table('password_reset_tokens')
                    ->where('email', $validated['email'])
                    ->update([
                        'code_hash' => null,
                        'code_expires_at' => null,
                        'code_attempts' => 0,
                    ]);

                return 'expired';
            }

            if ((int) $resetRequest->code_attempts >= self::MAX_CODE_ATTEMPTS) {
                return 'attempts';
            }

            if (!hash_equals((string) $resetRequest->code_hash, $validated['verification_code'])) {
                $attempts = (int) $resetRequest->code_attempts + 1;
                DB::table('password_reset_tokens')
                    ->where('email', $validated['email'])
                    ->update([
                        'code_attempts' => $attempts,
                        ...($attempts >= self::MAX_CODE_ATTEMPTS
                            ? ['code_hash' => null, 'code_expires_at' => null]
                            : []),
                    ]);

                return $attempts >= self::MAX_CODE_ATTEMPTS ? 'attempts' : 'invalid';
            }

            $user = User::where('email', $validated['email'])->lockForUpdate()->first();
            if (!$user) {
                DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();
                return 'missing';
            }

            $user->forceFill([
                'password' => Hash::make($validated['password']),
                'brakesafe_code' => null,
            ])->save();
            DB::table('password_reset_tokens')->where('email', $validated['email'])->delete();

            return 'success';
        });

        if ($result !== 'success') {
            $message = match ($result) {
                'expired' => 'The verification code expired. Request a new password reset code.',
                'attempts' => 'Too many incorrect attempts. Ask an administrator to send a new code.',
                'invalid' => 'The email or verification code is incorrect.',
                default => 'No active password reset code was found for that email.',
            };

            return back()
                ->withErrors(['verification_code' => $message])
                ->withInput($request->only('email'));
        }

        return redirect()
            ->route('login')
            ->with('status', 'Password reset successfully. Your verification code can only be used once.');
    }

    public function reset(Request $request): RedirectResponse
    {
        $email = $request->input('email');
        $code = $request->input('brakesafe_code');

        $request->merge([
            'email' => is_string($email) ? trim(strip_tags($email)) : $email,
            'brakesafe_code' => is_string($code)
                ? strtoupper((string) preg_replace('/[\s-]+/u', '', preg_replace('/[\x00-\x1F\x7F]/u', '', strip_tags($code))))
                : $code,
        ]);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'brakesafe_code' => ['required', 'string', 'max:40', 'regex:/^BS[A-HJ-NP-Z2-9]{16}$/'],
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()->symbols()->rules(['regex:/[^\p{L}\p{N}\s]/u']), 'confirmed'],
        ]);

        $user = User::where('email', $validated['email'])->first();
        if (!$user || !$user->brakesafe_code || !Hash::check($validated['brakesafe_code'], $user->brakesafe_code)) {
            return back()
                ->withErrors(['brakesafe_code' => 'The email or BrakeSafeCode is incorrect or has already been used.'])
                ->withInput($request->only('email'));
        }

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'brakesafe_code' => null,
        ])->save();

        return redirect()
            ->route('login')
            ->with('status', 'Password reset successfully. Your BrakeSafeCode can only be used once.');
    }
}
