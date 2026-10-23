<?php

namespace Tests\Feature\Auth;

use App\Mail\AdminPasswordResetCode;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminPasswordResetCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_asks_for_a_code_instead_of_a_reset_link(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Send Code')
            ->assertDontSee('>Email verification code</button>', false)
            ->assertSee('six-digit verification code')
            ->assertDontSee('password reset link');

        $this->get('/reset-password')
            ->assertOk()
            ->assertSee('Six-digit verification code')
            ->assertSee('Request another verification code')
            ->assertSee('action="/reset-password"', false)
            ->assertDontSee('name="password"', false)
            ->assertDontSee('name="password_confirmation"', false);

        $this->get('/reset-password/new')
            ->assertRedirect('/forgot-password');

        $admin = Admin::factory()->create();
        Mail::fake();
        $code = $this->requestCode($admin);
        $this->post('/reset-password', ['code' => $code])
            ->assertRedirect('/reset-password/new');

        $this->get('/reset-password/new')
            ->assertOk()
            ->assertSee('action="/reset-password/new"', false)
            ->assertSeeInOrder(['name="password"', 'name="password_confirmation"'], false)
            ->assertDontSee('name="code"', false)
            ->assertSee('aria-controls="new-password"', false)
            ->assertSee('aria-controls="confirm-password"', false)
            ->assertSee('data-password-visibility-toggle', false)
            ->assertSee('Password security')
            ->assertSee('Passwords do not match.')
            ->assertSee('At least 8 characters')
            ->assertSee('data-password-rule="uppercase"', false)
            ->assertSee('password-strength-bar', false);
    }

    public function test_forgot_password_sends_a_verification_code_without_a_reset_link(): void
    {
        Mail::fake();
        $admin = Admin::factory()->create();
        $code = null;

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => $admin->email])
            ->assertRedirect('/reset-password')
            ->assertSessionHas('status')
            ->assertSessionHas('reset_email', $admin->email)
            ->assertSessionHas('reset_code_hash')
            ->assertSessionHas('reset_code_expires_at')
            ->assertSessionHas('reset_code_attempts', 0)
            ->assertSessionMissing('reset_token');

        Mail::assertSent(AdminPasswordResetCode::class, function (AdminPasswordResetCode $mail) use (&$code, $admin): bool {
            $code = $mail->code;
            $renderedEmail = $mail->render();

            return $mail->hasTo($admin->email)
                && preg_match('/^\d{6}$/', $mail->code) === 1
                && str_contains($renderedEmail, $mail->code)
                && !str_contains($renderedEmail, '/reset-password?token=');
        });
        $this->assertNotNull($code);
        $this->assertTrue(Hash::check($code, session('reset_code_hash')));
    }

    public function test_unknown_admin_email_is_rejected_without_sending_mail(): void
    {
        Mail::fake();

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'missing@example.com'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email')
            ->assertSessionHasInput('email', 'missing@example.com')
            ->assertSessionMissing('reset_email')
            ->assertSessionMissing('reset_code_hash');

        Mail::assertNothingSent();
    }

    public function test_three_unknown_admin_emails_disable_code_requests_for_thirty_seconds(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
        $admin = Admin::factory()->create();
        $ip = '192.0.2.10';
        $attemptsKey = 'admin_password_reset_attempts|'.$ip;
        $lockKey = 'admin_password_reset_lock|'.$ip;
        Cache::forget($attemptsKey);
        Cache::forget($lockKey);

        foreach ([1, 2, 3] as $attempt) {
            $response = $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->from('/forgot-password')
                ->post('/forgot-password', ['email' => "missing{$attempt}@example.com"])
                ->assertRedirect('/forgot-password')
                ->assertSessionHasErrors('email');

            if ($attempt < 3) {
                $response->assertSessionHas('errors', function ($errors) use ($attempt): bool {
                    return str_contains($errors->first('email'), "Attempt {$attempt} of 3.");
                });
            } else {
                $response->assertSessionHas('errors', function ($errors): bool {
                    return str_contains($errors->first('email'), 'Try again in 30 seconds.');
                });
            }
        }

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get('/forgot-password')
            ->assertOk()
            ->assertSee('id="admin-reset-code-button" type="submit" data-lockout-seconds="30" disabled', false)
            ->assertSee('Send Code');

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'blocked@example.com'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email');

        $this->travel(31)->seconds();

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->get('/forgot-password')
            ->assertOk()
            ->assertSee('data-lockout-seconds="0"', false)
            ->assertDontSee('id="admin-reset-code-button" type="submit" data-lockout-seconds="0" disabled', false);

        $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->post('/forgot-password', ['email' => $admin->email])
            ->assertRedirect('/reset-password')
            ->assertSessionHas('reset_email', $admin->email);

        Mail::assertSent(AdminPasswordResetCode::class, fn (AdminPasswordResetCode $mail): bool => $mail->hasTo($admin->email));
        Cache::forget($attemptsKey);
        Cache::forget($lockKey);
    }

    public function test_admin_must_verify_code_before_resetting_password_once(): void
    {
        Mail::fake();
        $admin = Admin::factory()->create();

        $this->from('/reset-password/new')
            ->post('/reset-password/new', [
                'password' => 'New-password-123!',
                'password_confirmation' => 'New-password-123!',
            ])->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email');

        $code = $this->requestCode($admin);
        $this->post('/reset-password', ['code' => $code])
            ->assertRedirect('/reset-password/new')
            ->assertSessionHas('status', 'Your email has been verified. You can now choose a new password.')
            ->assertSessionMissing('reset_code_hash');

        $this->post('/reset-password/new', [
            'password' => 'New-password-123!',
            'password_confirmation' => 'New-password-123!',
        ])->assertRedirect('/login')
            ->assertSessionHas('status', 'Password reset successfully.')
            ->assertSessionMissing('reset_verified_until');

        $this->assertTrue(Hash::check('New-password-123!', $admin->fresh()->password));
        $this->get('/login')
            ->assertOk()
            ->assertSee('Password reset successfully')
            ->assertSee('Password reset successfully.')
            ->assertSee('window.Swal.fire', false)
            ->assertSee('timer: 5000', false)
            ->assertSee('#ef4444')
            ->assertSee("status?.remove(), 5000", false);

        $this->from('/reset-password/new')
            ->post('/reset-password/new', [
                'password' => 'Another-password-123!',
                'password_confirmation' => 'Another-password-123!',
            ])->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email');
    }

    public function test_admin_password_reset_requires_a_strong_password_and_matching_confirmation(): void
    {
        Mail::fake();
        $admin = Admin::factory()->create();
        $code = $this->requestCode($admin);
        $this->post('/reset-password', ['code' => $code])
            ->assertRedirect('/reset-password/new');

        $this->from('/reset-password/new')
            ->post('/reset-password/new', [
                'password' => 'Password123',
                'password_confirmation' => 'Password123',
            ])
            ->assertRedirect('/reset-password/new')
            ->assertSessionHasErrors('password');

        $this->from('/reset-password/new')
            ->post('/reset-password/new', [
                'password' => 'Strong-pass-123!',
                'password_confirmation' => 'Different-pass-123!',
            ])
            ->assertRedirect('/reset-password/new')
            ->assertSessionHasErrors('password');

        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
    }

    public function test_incorrect_codes_are_limited_and_expired_codes_cannot_be_used(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
        $admin = Admin::factory()->create();
        $code = $this->requestCode($admin);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->from('/reset-password')->post('/reset-password', [
                'code' => '000000',
            ]);
            if ($attempt < 5) {
                $response->assertRedirect('/reset-password')
                    ->assertSessionHasErrors('code');
            } else {
                $response->assertRedirect('/forgot-password')
                    ->assertSessionHasErrors('email');
            }
        }

        $response->assertSessionMissing('reset_code_hash');

        $code = $this->requestCode($admin);
        $this->travel(11)->minutes();

        $this->from('/reset-password')
            ->post('/reset-password', [
                'code' => $code,
            ])->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('email')
            ->assertSessionMissing('reset_email')
            ->assertSessionMissing('reset_verified_until');

        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
    }

    private function requestCode(Admin $admin): string
    {
        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => $admin->email])
            ->assertRedirect('/reset-password');

        $code = null;
        Mail::assertSent(AdminPasswordResetCode::class, function (AdminPasswordResetCode $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $this->assertNotNull($code);

        return $code;
    }

}
