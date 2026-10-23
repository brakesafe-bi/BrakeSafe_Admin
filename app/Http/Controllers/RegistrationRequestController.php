<?php

namespace App\Http\Controllers;

use App\Mail\RegistrationRequestVerificationCode;
use App\Models\RegistrationRequest as UserRegistrationRequest;
use App\Models\User;
use App\Support\BrakeSafeCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RegistrationRequestController extends Controller
{
    private const MAX_CODE_ATTEMPTS = 5;

    private function verificationCodeMatches(string $submittedCode, string $storedCode): bool
    {
        if (preg_match('/^\$(?:2[aby]|argon2(?:i|id))\$/', $storedCode) === 1) {
            return Hash::check($submittedCode, $storedCode);
        }

        return hash_equals($storedCode, $submittedCode);
    }

    public function create(): View
    {
        return view('auth.request-access');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->sanitizeRequestInput($request);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birthday' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'email' => ['required', 'email', 'max:255'],
        ]);

        if (User::where('email', $validated['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'An account with this email already exists.',
            ]);
        }

        if (UserRegistrationRequest::where('email', $validated['email'])
            ->whereIn('status', ['pending_verification', 'pending_review'])
            ->exists()) {
            throw ValidationException::withMessages([
                'email' => 'There is already an active access request for this email.',
            ]);
        }

        $verificationCode = (string) random_int(100000, 999999);
        $userRequest = UserRegistrationRequest::create([
            ...$validated,
            'public_token' => (string) Str::uuid(),
            'status' => 'pending_verification',
            'code_hash' => $verificationCode,
            'code_expires_at' => now()->addMinutes(10),
            'code_attempts' => 0,
        ]);

        $verificationUrl = route('registration-requests.verify', ['token' => $userRequest->public_token]);

        try {
            Mail::to($userRequest->email)->send(new RegistrationRequestVerificationCode(
                $userRequest->email,
                $verificationCode,
                $verificationUrl,
            ));
        } catch (Throwable $exception) {
            $userRequest->delete();
            Log::error('Registration request verification email failed.', [
                'registration_request_id' => $userRequest->id,
                'error' => $exception->getMessage(),
            ]);

            return back()
                ->withInput($request->only(['first_name', 'last_name', 'birthday', 'email']))
                ->withErrors(['email' => 'Unable to send the verification email. Please try again later.']);
        }

        return redirect()
            ->route('registration-requests.verify', ['token' => $userRequest->public_token])
            ->with('status', 'A verification code has been sent to your email.');
    }

    public function verificationForm(string $token): View
    {
        $userRequest = UserRegistrationRequest::where('public_token', $token)->firstOrFail();

        return view('auth.verify-registration-request', [
            'registrationRequest' => $userRequest,
        ]);
    }

    public function verify(Request $request, string $token): RedirectResponse
    {
        $request->merge([
            'verification_code' => is_string($request->input('verification_code'))
                ? trim(strip_tags($request->input('verification_code')))
                : $request->input('verification_code'),
        ]);

        $validated = $request->validate([
            'verification_code' => ['required', 'digits:6'],
        ]);

        $result = DB::transaction(function () use ($token, $validated): string {
            $userRequest = UserRegistrationRequest::where('public_token', $token)->lockForUpdate()->firstOrFail();

            if ($userRequest->status !== 'pending_verification') {
                return 'already_processed';
            }

            if (!$userRequest->code_expires_at || $userRequest->code_expires_at->isPast()) {
                $userRequest->update([
                    'status' => 'expired',
                    'code_hash' => null,
                    'code_expires_at' => null,
                    'completed_at' => now(),
                ]);

                return 'expired';
            }

            if ($userRequest->code_attempts >= self::MAX_CODE_ATTEMPTS) {
                $userRequest->update([
                    'status' => 'expired',
                    'code_hash' => null,
                    'code_expires_at' => null,
                    'completed_at' => now(),
                ]);

                return 'locked';
            }

            if (!$userRequest->code_hash || !$this->verificationCodeMatches(
                $validated['verification_code'],
                (string) $userRequest->code_hash,
            )) {
                $attempts = $userRequest->code_attempts + 1;
                $userRequest->update([
                    'code_attempts' => $attempts,
                    ...($attempts >= self::MAX_CODE_ATTEMPTS ? [
                        'status' => 'expired',
                        'code_hash' => null,
                        'code_expires_at' => null,
                        'completed_at' => now(),
                    ] : []),
                ]);

                return $attempts >= self::MAX_CODE_ATTEMPTS ? 'locked' : 'invalid';
            }

            $userRequest->update([
                'status' => 'pending_review',
                'code_hash' => null,
                'code_expires_at' => null,
                'code_attempts' => 0,
            ]);

            return 'verified';
        });

        if ($result !== 'verified') {
            $message = match ($result) {
                'expired' => 'The verification code has expired. Submit a new access request.',
                'locked' => 'Too many incorrect codes. Submit a new access request.',
                'already_processed' => 'This verification request is no longer active.',
                default => 'The verification code is incorrect.',
            };

            return back()
                ->withErrors(['verification_code' => $message])
                ->withInput();
        }

        return redirect()
            ->route('registration-requests.verify', ['token' => $token])
            ->with('status', 'Your email is verified. Your access request is waiting for admin approval.');
    }

    public function index(): View
    {
        return view('registration-requests.index', [
            'registrationRequests' => UserRegistrationRequest::query()
                ->whereIn('status', ['pending', 'pending_verification', 'pending_review', 'completed', 'rejected'])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function sendVerificationCode(UserRegistrationRequest $registrationRequest): JsonResponse
    {
        $verificationCode = (string) random_int(100000, 999999);

        $userRequest = DB::transaction(function () use ($registrationRequest, $verificationCode): UserRegistrationRequest {
            $lockedRequest = UserRegistrationRequest::whereKey($registrationRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($lockedRequest->status, ['pending', 'pending_verification'], true)) {
                throw ValidationException::withMessages([
                    'request' => 'A verification code can only be sent while a request is awaiting email verification.',
                ]);
            }

            if (User::where('email', $lockedRequest->email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'A user account with this email already exists.',
                ]);
            }

            $lockedRequest->update([
                'status' => 'pending_verification',
                'code_hash' => $verificationCode,
                'code_expires_at' => now()->addMinutes(10),
                'code_attempts' => 0,
                'completed_at' => null,
            ]);

            return $lockedRequest->refresh();
        });

        $verificationUrl = route('registration-requests.verify', ['token' => $userRequest->public_token]);

        try {
            Mail::to($userRequest->email)->send(new RegistrationRequestVerificationCode(
                $userRequest->email,
                $verificationCode,
                $verificationUrl,
            ));
        } catch (Throwable $exception) {
            UserRegistrationRequest::query()
                ->whereKey($userRequest->id)
                ->where('status', 'pending_verification')
                ->where('code_hash', $verificationCode)
                ->update([
                    'status' => 'pending',
                    'code_hash' => null,
                    'code_expires_at' => null,
                    'code_attempts' => 0,
                ]);

            Log::error('Admin-sent registration verification email failed.', [
                'registration_request_id' => $userRequest->id,
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to send the verification email. Please try again later.',
            ], 502);
        }

        return response()->json([
            'message' => 'Verification code sent to '.$userRequest->email.'. The code expires in 10 minutes.',
        ]);
    }

    public function approve(UserRegistrationRequest $registrationRequest): JsonResponse
    {
        $result = DB::transaction(function () use ($registrationRequest): array {
            $lockedRequest = UserRegistrationRequest::whereKey($registrationRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== 'pending_review') {
                throw ValidationException::withMessages([
                    'request' => 'Only verified pending requests can be approved.',
                ]);
            }

            if (User::where('email', $lockedRequest->email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'A user account with this email already exists.',
                ]);
            }

            $code = BrakeSafeCode::generate();
            $name = trim($lockedRequest->first_name.' '.$lockedRequest->last_name);
            $user = new User([
                'name' => $name,
                'first_name' => $lockedRequest->first_name,
                'last_name' => $lockedRequest->last_name,
                'email' => $lockedRequest->email,
                'birthday' => $lockedRequest->birthday,
            ]);
            $user->forceFill([
                'password' => Hash::make(Str::random(64)),
                'email_verified_at' => now(),
                'brakesafe_code' => Hash::make(str_replace('-', '', $code)),
            ])->save();

            $lockedRequest->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            return ['user' => $user, 'code' => $code];
        });

        return response()->json([
            'message' => 'Access request approved and user account created.',
            'user' => $result['user'],
            'brakesafe_code' => $result['code'],
        ]);
    }

    public function reject(UserRegistrationRequest $registrationRequest): JsonResponse
    {
        DB::transaction(function () use ($registrationRequest): void {
            $lockedRequest = UserRegistrationRequest::whereKey($registrationRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== 'pending_review') {
                throw ValidationException::withMessages([
                    'request' => 'Only verified pending requests can be rejected.',
                ]);
            }

            $lockedRequest->update([
                'status' => 'rejected',
                'completed_at' => now(),
                'code_hash' => null,
                'code_expires_at' => null,
            ]);
        });

        return response()->json(['message' => 'Access request rejected.']);
    }

    public function destroy(UserRegistrationRequest $registrationRequest): JsonResponse
    {
        $registrationRequest->delete();

        return response()->json(['message' => 'Access request deleted.']);
    }

    private function sanitizeRequestInput(Request $request): void
    {
        $sanitized = [];

        foreach (['first_name', 'last_name', 'email'] as $field) {
            $value = $request->input($field);
            if (is_string($value)) {
                $value = strip_tags($value);
                $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
                $sanitized[$field] = trim((string) preg_replace('/\s+/u', ' ', $value));
            }
        }

        if (isset($sanitized['email'])) {
            $sanitized['email'] = strtolower($sanitized['email']);
        }

        $request->merge($sanitized);
    }
}
