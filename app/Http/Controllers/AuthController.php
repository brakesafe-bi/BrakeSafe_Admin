<?php

namespace App\Http\Controllers;

use App\Mail\AdminPasswordResetCode;
use App\Mail\OtpVerification;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginForm(Request $request)
    {
        $ip = $request->ip();
        $lockKey = 'login_lock|' . $ip;
        $lockedAt = Cache::get($lockKey);
        $seconds = 0;
        if ($lockedAt) {
            $elapsed = time() - (int) $lockedAt;
            $seconds = max(0, 30 - $elapsed);
            if ($seconds === 0) {
                Cache::forget($lockKey);
            }
        }

        $showRegister = Admin::doesntExist();

        return view('auth.login', ['lockoutSeconds' => $seconds, 'showRegister' => $showRegister]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:30'],
            'password' => ['required', 'max:30'],
        ]);

        $ip = $request->ip();
        $attemptsKey = 'login_attempts|' . $ip;
        $lockKey = 'login_lock|' . $ip;

        $lockedAt = Cache::get($lockKey);
        if ($lockedAt) {
            return back()->withInput();
        }

        if (!Auth::attempt($credentials)) {
            // increment attempts counter with 30s decay
            if (!Cache::has($attemptsKey)) {
                Cache::put($attemptsKey, 1, now()->addSeconds(30));
                $attempts = 1;
            } else {
                $attempts = Cache::increment($attemptsKey);
            }

            if ($attempts >= 3) {
                Cache::put($lockKey, time(), now()->addSeconds(30));
                Cache::forget($attemptsKey);
                return back()->withInput();
            }

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are invalid.'],
            ]);
        }

        if (!Auth::user()->is_verified) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => ['Your account is not yet verified. Please complete the OTP verification sent to your email.'],
            ]);
        }

        Cache::forget('login_attempts|' . $request->ip());
        Cache::forget('login_lock|' . $request->ip());
        $request->session()->regenerate();
        return redirect('/')->with('login_success', true);
    }

    public function registerForm()
    {
        if (Admin::exists()) {
            return redirect()->route('login');
        }

        return view('auth.register', ['adminCount' => 0]);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'unique:admins', 'max:50'],
            'password' => ['required', 'confirmed', 'min:8'],
            'full_name' => ['required', 'string'],
        ]);

        $admin = DB::transaction(function () use ($validated): ?Admin {
            $existingAdmins = Admin::query()->lockForUpdate()->get(['id']);

            if ($existingAdmins->isNotEmpty()) {
                return null;
            }

            return Admin::create([
                'name' => $validated['full_name'],
                'full_name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_verified' => false,
            ]);
        });

        if (!$admin) {
            return redirect()->route('login')->withErrors([
                'email' => 'The administrator account is already set up.',
            ]);
        }

        $otp = rand(100000, 999999);
        session(['otp' => $otp, 'pending_admin_id' => $admin->id]);

        try {
            Mail::to($admin->email)->send(new OtpVerification($otp, $admin->full_name));
        } catch (\Exception $e) {
            Log::error('OTP email send failed', [
                'email' => $admin->email,
                'error' => $e->getMessage(),
            ]);

            $admin->delete();
            session()->forget(['otp', 'pending_admin_id']);

            return back()->withErrors(['email' => 'Unable to send OTP email. Please check your mail configuration and try again.']);
        }

        return view('auth.verify-otp', ['email' => $admin->email]);
    }

    public function verifyOtp(Request $request)
    {
        $validated = $request->validate(['otp' => ['required', 'numeric']]);
        $sessionOtp = session('otp');
        $pendingAdminId = session('pending_admin_id');

        if ($validated['otp'] != $sessionOtp) {
            return back()->withErrors(['otp' => 'Invalid OTP.']);
        }

        $admin = Admin::find($pendingAdminId);
        if (!$admin) {
            return back()->withErrors(['otp' => 'Admin account not found.']);
        }

        $admin->update(['is_verified' => true]);
        session()->forget(['otp', 'pending_admin_id']);

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect('/')->with('login_success', true);
    }

    public function forgotForm()
    {
        return view('auth.forgot-password', [
            'lockoutSeconds' => $this->adminPasswordResetLockoutSeconds(request()),
        ]);
    }

    public function sendResetLink(Request $request)
    {
        $lockoutSeconds = $this->adminPasswordResetLockoutSeconds($request);
        if ($lockoutSeconds > 0) {
            return back()->withInput()->withErrors([
                'email' => "Too many incorrect attempts. Try again in {$lockoutSeconds} seconds.",
            ]);
        }

        $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $request->session()->forget([
            'reset_code_hash',
            'reset_code_expires_at',
            'reset_code_attempts',
            'reset_email',
            'reset_verified_until',
        ]);

        $admin = Admin::where('email', $validated['email'])->first();
        if (! $admin) {
            $attemptsKey = $this->adminPasswordResetAttemptsKey($request);
            $attempts = Cache::has($attemptsKey)
                ? Cache::increment($attemptsKey)
                : tap(1, fn (int $attempts) => Cache::put($attemptsKey, $attempts, now()->addSeconds(30)));

            if ($attempts >= 3) {
                Cache::put($this->adminPasswordResetLockoutKey($request), now()->timestamp, now()->addSeconds(30));
                Cache::forget($attemptsKey);

                return back()->withInput()->withErrors([
                    'email' => 'Too many incorrect attempts. Try again in 30 seconds.',
                ]);
            }

            return back()->withInput()->withErrors([
                'email' => "This email does not match an administrator account. Attempt {$attempts} of 3.",
            ]);
        }

        Cache::forget($this->adminPasswordResetAttemptsKey($request));
        Cache::forget($this->adminPasswordResetLockoutKey($request));

        $code = (string) random_int(100000, 999999);
        $request->session()->put([
            'reset_code_hash' => Hash::make($code),
            'reset_code_expires_at' => now()->addMinutes(10)->timestamp,
            'reset_code_attempts' => 0,
            'reset_email' => $admin->email,
        ]);

        try {
            Mail::to($admin->email)->send(new AdminPasswordResetCode($code, $admin->full_name ?? $admin->name));
        } catch (\Throwable $exception) {
            $request->session()->forget([
                'reset_code_hash',
                'reset_code_expires_at',
                'reset_code_attempts',
                'reset_email',
            ]);
            Log::error('Administrator password reset code email failed.', [
                'admin_id' => $admin->id,
                'error' => $exception->getMessage(),
            ]);

            return back()->withInput()->withErrors([
                'email' => 'Unable to send the verification code. Please try again later.',
            ]);
        }

        return redirect('/reset-password')->with(
            'status',
            'A verification code has been sent to your administrator email.',
        );
    }

    private function adminPasswordResetLockoutSeconds(Request $request): int
    {
        $lockoutKey = $this->adminPasswordResetLockoutKey($request);
        $lockedAt = Cache::get($lockoutKey);
        if (! $lockedAt) {
            return 0;
        }

        $seconds = max(0, 30 - (now()->timestamp - (int) $lockedAt));
        if ($seconds === 0) {
            Cache::forget($lockoutKey);
        }

        return $seconds;
    }

    private function adminPasswordResetAttemptsKey(Request $request): string
    {
        return 'admin_password_reset_attempts|'.$request->ip();
    }

    private function adminPasswordResetLockoutKey(Request $request): string
    {
        return 'admin_password_reset_lock|'.$request->ip();
    }

    public function resetForm()
    {
        return view('auth.reset-password-code');
    }

    public function verifyResetCode(Request $request)
    {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);

        $expiresAt = (int) $request->session()->get('reset_code_expires_at', 0);
        $codeHash = $request->session()->get('reset_code_hash');
        $email = $request->session()->get('reset_email');
        if (!$expiresAt || $expiresAt <= now()->timestamp || !is_string($codeHash) || !$email) {
            $this->clearAdminPasswordResetChallenge($request);

            return redirect('/forgot-password')->withErrors(['email' => 'Your verification code expired. Request a new code.']);
        }

        if (!Hash::check($validated['code'], $codeHash)) {
            $attempts = (int) $request->session()->get('reset_code_attempts', 0) + 1;
            if ($attempts >= 5) {
                $this->clearAdminPasswordResetChallenge($request);

                return redirect('/forgot-password')->withErrors(['email' => 'Too many incorrect codes. Request a new code.']);
            }

            $request->session()->put('reset_code_attempts', $attempts);

            return back()->withErrors(['code' => 'The verification code is incorrect.']);
        }

        $admin = Admin::where('email', $email)->first();
        if (!$admin) {
            $this->clearAdminPasswordResetChallenge($request);

            return redirect('/forgot-password')->withErrors(['email' => 'This password reset request is no longer valid. Request a new code.']);
        }

        $request->session()->forget([
            'reset_code_hash',
            'reset_code_expires_at',
            'reset_code_attempts',
        ]);
        $request->session()->put('reset_verified_until', now()->addMinutes(5)->timestamp);

        return redirect('/reset-password/new')->with(
            'status',
            'Your email has been verified. You can now choose a new password.',
        );
    }

    public function resetPasswordForm(Request $request)
    {
        if (!$this->hasVerifiedAdminPasswordReset($request)) {
            $this->clearAdminPasswordResetChallenge($request);

            return redirect('/forgot-password')->withErrors([
                'email' => 'Verify a valid code before choosing a new password.',
            ]);
        }

        return view('auth.reset-password');
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'password' => [
                'required',
                'string',
                Password::min(8)->mixedCase()->numbers()->symbols()->rules(['regex:/[^\p{L}\p{N}\s]/u']),
                'confirmed',
            ],
        ]);

        if (!$this->hasVerifiedAdminPasswordReset($request)) {
            $this->clearAdminPasswordResetChallenge($request);

            return redirect('/forgot-password')->withErrors([
                'email' => 'Verify a valid code before choosing a new password.',
            ]);
        }

        $email = $request->session()->get('reset_email');
        $admin = Admin::where('email', $email)->first();
        if (!$admin) {
            $this->clearAdminPasswordResetChallenge($request);

            return redirect('/forgot-password')->withErrors([
                'email' => 'This password reset request is no longer valid. Request a new code.',
            ]);
        }

        $admin->update(['password' => Hash::make($validated['password'])]);
        $this->clearAdminPasswordResetChallenge($request);

        return redirect('/login')->with('status', 'Password reset successfully.');
    }

    private function hasVerifiedAdminPasswordReset(Request $request): bool
    {
        return is_string($request->session()->get('reset_email'))
            && $request->session()->get('reset_email') !== ''
            && (int) $request->session()->get('reset_verified_until', 0) > now()->timestamp;
    }

    private function clearAdminPasswordResetChallenge(Request $request): void
    {
        $request->session()->forget([
            'reset_code_hash',
            'reset_code_expires_at',
            'reset_code_attempts',
            'reset_email',
            'reset_verified_until',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
