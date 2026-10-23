<?php

namespace Tests\Feature;

use App\Mail\UserPasswordResetCode;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserPasswordResetCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_a_password_reset_request_without_email_enumeration(): void
    {
        Mail::fake();
        $user = User::create([
            'name' => 'Reset User',
            'email' => 'reset-user@example.com',
            'password' => 'OldPassword123!',
        ]);

        $this->from('/forgot-password')
            ->post('/user-password/request-code', ['email' => $user->email])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
            'code_attempts' => 0,
        ]);

        $resetRow = DB::table('password_reset_tokens')->where('email', $user->email)->first();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $resetRow->code_hash);
        $this->assertTrue(now()->lessThan($resetRow->code_expires_at));
        Mail::assertSent(UserPasswordResetCode::class, function (UserPasswordResetCode $mail) use ($user, $resetRow): bool {
            return $mail->hasTo($user->email)
                && $mail->verificationCode === $resetRow->code_hash;
        });

        $this->from('/forgot-password')
            ->post('/user-password/request-code', ['email' => 'not-registered@example.com'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'not-registered@example.com',
        ]);
        Mail::assertSent(UserPasswordResetCode::class, 1);
    }

    public function test_forgot_password_page_does_not_show_user_password_reset_controls(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Administrator account')
            ->assertDontSee('BrakeSafe user account')
            ->assertDontSee('Request verification code')
            ->assertDontSee('Continue to password reset')
            ->assertDontSee('Six-digit verification code')
            ->assertDontSee('Confirm new password')
            ->assertDontSee('Use a BrakeSafeCode instead');

        $this->get(route('users.password.reset-form'))
            ->assertOk()
            ->assertSee(route('users.password.request-code'), false)
            ->assertSee('Email verification code')
            ->assertSee('Six-digit verification code')
            ->assertSee(route('users.password.reset-with-code'), false);
    }

    public function test_password_reset_requests_are_admin_only_and_raw_reset_tokens_are_not_rendered(): void
    {
        $this->get('/password-reset-requests')->assertRedirect('/login');

        $admin = Admin::factory()->create();
        $user = User::create([
            'name' => 'Reset User',
            'email' => 'reset-user@example.com',
            'password' => 'OldPassword123!',
        ]);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => 'sensitive-reset-token-value',
            'created_at' => now(),
            'code_hash' => null,
            'code_expires_at' => null,
            'code_attempts' => 0,
        ]);

        $this->actingAs($admin)
            ->get('/password-reset-requests')
            ->assertOk()
            ->assertSee($user->email)
            ->assertDontSee('sensitive-reset-token-value')
            ->assertSee('automatically')
            ->assertSee('Send verification code');
    }

    public function test_admin_can_see_automatically_emailed_password_reset_and_resend_its_code(): void
    {
        Mail::fake();
        $user = User::create([
            'name' => 'Reset User',
            'email' => 'reset-user@example.com',
            'password' => 'OldPassword123!',
        ]);

        $this->from('/forgot-password')
            ->post('/user-password/request-code', ['email' => $user->email])
            ->assertRedirect('/forgot-password');

        $resetRow = DB::table('password_reset_tokens')->where('email', $user->email)->first();
        $this->assertNotNull($resetRow);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $resetRow->code_hash);

        $this->actingAs(Admin::factory()->create())
            ->get('/password-reset-requests')
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee('Send verification code');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
            'code_hash' => $resetRow->code_hash,
        ]);
        Mail::assertSent(UserPasswordResetCode::class, 1);

        $this->actingAs(Admin::factory()->create())
            ->from('/password-reset-requests')
            ->post('/password-reset-requests/send-code', ['email' => $user->email])
            ->assertRedirect('/password-reset-requests')
            ->assertSessionHas('status');

        $updatedResetRow = DB::table('password_reset_tokens')->where('email', $user->email)->first();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $updatedResetRow->code_hash);
        $this->assertNotSame($resetRow->code_hash, $updatedResetRow->code_hash);
        $this->assertSame(0, (int) $updatedResetRow->code_attempts);
        Mail::assertSent(UserPasswordResetCode::class, 2);
    }

    public function test_registration_request_page_has_registration_and_password_reset_navigation_buttons(): void
    {
        if (!Schema::hasTable('registration_requests')) {
            Schema::create('registration_requests', function ($table): void {
                $table->id();
                $table->uuid('public_token')->unique();
                $table->string('first_name', 100);
                $table->string('last_name', 100);
                $table->date('birthday')->nullable();
                $table->string('email')->index();
                $table->string('status', 24)->default('pending_verification')->index();
                $table->string('code_hash')->nullable();
                $table->timestamp('code_expires_at')->nullable();
                $table->unsignedTinyInteger('code_attempts')->default(0);
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        $this->actingAs(Admin::factory()->create())
            ->get('/registration-requests')
            ->assertOk()
            ->assertSee('Registration Requests')
            ->assertSee('Password Resets')
            ->assertSee(route('users.password-requests.index'), false);
    }

    public function test_user_can_reset_password_once_with_the_current_email_code(): void
    {
        $user = User::create([
            'name' => 'Reset User',
            'email' => 'reset-user@example.com',
            'password' => 'OldPassword123!',
        ]);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => 'reset-token',
            'created_at' => now(),
            'code_hash' => '004209',
            'code_expires_at' => now()->addMinutes(10),
            'code_attempts' => 0,
        ]);

        $this->from('/forgot-password')
            ->post('/user-password/reset-with-code', [
                'email' => $user->email,
                'verification_code' => '004209',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertRedirect('/login')
            ->assertSessionHas('status');

        $this->assertTrue(password_verify('NewPassword123!', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);

        $this->from('/forgot-password')
            ->post('/user-password/reset-with-code', [
                'email' => $user->email,
                'verification_code' => '004209',
                'password' => 'AnotherPassword123!',
                'password_confirmation' => 'AnotherPassword123!',
            ])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('verification_code');

        $this->assertTrue(password_verify('NewPassword123!', $user->fresh()->password));
    }

    public function test_expired_and_repeatedly_incorrect_codes_are_invalidated(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        $user = User::create([
            'name' => 'Reset User',
            'email' => 'reset-user@example.com',
            'password' => 'OldPassword123!',
        ]);
        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => 'reset-token',
            'created_at' => now(),
            'code_hash' => '123456',
            'code_expires_at' => now()->subMinute(),
            'code_attempts' => 0,
        ]);

        $this->from('/forgot-password')
            ->post('/user-password/reset-with-code', [
                'email' => $user->email,
                'verification_code' => '123456',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('verification_code');
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
            'code_hash' => null,
        ]);

        DB::table('password_reset_tokens')->where('email', $user->email)->update([
            'code_hash' => '654321',
            'code_expires_at' => now()->addMinutes(10),
            'code_attempts' => 0,
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from('/forgot-password')
                ->post('/user-password/reset-with-code', [
                    'email' => $user->email,
                    'verification_code' => '000000',
                    'password' => 'NewPassword123!',
                    'password_confirmation' => 'NewPassword123!',
                ])
                ->assertRedirect('/forgot-password')
                ->assertSessionHasErrors('verification_code');
        }

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
            'code_hash' => null,
            'code_attempts' => 5,
        ]);
        $this->assertTrue(password_verify('OldPassword123!', $user->fresh()->password));
    }
}
