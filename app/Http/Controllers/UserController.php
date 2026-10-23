<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\BrakeSafeCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('registration-requests.index');
    }

    public function store(Request $request): JsonResponse
    {
        $this->sanitizeUserInput($request);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:127'],
            'last_name' => ['required', 'string', 'max:127'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'birthday' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers()->symbols()->rules(['regex:/[^\p{L}\p{N}\s]/u']), 'confirmed'],
        ]);

        $brakeSafeCode = BrakeSafeCode::generate();
        $user = new User([
            ...$validated,
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'password' => Hash::make($validated['password']),
        ]);
        $user->forceFill(['brakesafe_code' => Hash::make(str_replace('-', '', $brakeSafeCode))])->save();

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user,
            'brakesafe_code' => $brakeSafeCode,
        ], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->sanitizeUserInput($request);

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:127'],
            'last_name' => ['required', 'string', 'max:127'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'birthday' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'password' => ['nullable', 'string', Password::min(8)->mixedCase()->numbers()->symbols()->rules(['regex:/[^\p{L}\p{N}\s]/u']), 'confirmed'],
            'regenerate_brakesafe_code' => ['sometimes', 'boolean'],
        ]);

        $attributes = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'name' => trim($validated['first_name'].' '.$validated['last_name']),
            'email' => $validated['email'],
            'birthday' => $validated['birthday'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $attributes['password'] = Hash::make($validated['password']);
        }

        $brakeSafeCode = null;
        if (!empty($validated['regenerate_brakesafe_code'])) {
            $brakeSafeCode = BrakeSafeCode::generate();
            $attributes['brakesafe_code'] = Hash::make(str_replace('-', '', $brakeSafeCode));
        }

        $user->forceFill($attributes)->save();

        return response()->json(array_merge([
            'message' => 'User updated successfully.',
            'user' => $user->fresh(),
        ], $brakeSafeCode === null ? [] : ['brakesafe_code' => $brakeSafeCode]));
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json(['message' => 'User deleted successfully.']);
    }

    private function sanitizeUserInput(Request $request): void
    {
        $sanitized = [];

        foreach (['first_name', 'last_name', 'email'] as $field) {
            $value = $request->input($field);
            if (is_string($value)) {
                $sanitized[$field] = $this->sanitizeText($value);
            }
        }

        $request->merge($sanitized);
    }

    private function sanitizeText(string $value): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

}
