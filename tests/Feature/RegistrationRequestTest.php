<?php

namespace Tests\Feature;

use App\Mail\RegistrationRequestVerificationCode;
use App\Models\Admin;
use App\Models\RegistrationRequest as UserRegistrationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegistrationRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('registration_requests')) {
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
    }

    public function test_request_access_form_is_public_but_not_linked_from_login(): void
    {
        $this->get('/request-access')
            ->assertOk()
            ->assertViewIs('auth.request-access')
            ->assertSee('Request user access');

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Request user access');
    }

    public function test_user_can_submit_a_sanitized_adult_access_request_and_verify_email(): void
    {
        Mail::fake();

        $this->from('/request-access')
            ->post('/request-access', [
                'first_name' => '<b>Ada</b>',
                'last_name' => ' Lovelace ',
                'birthday' => '2000-12-10',
                'email' => ' <em>ADA@example.com</em> ',
            ])
            ->assertRedirect();

        $registrationRequest = UserRegistrationRequest::firstOrFail();
        $this->assertSame('Ada', $registrationRequest->first_name);
        $this->assertSame('Lovelace', $registrationRequest->last_name);
        $this->assertSame('ada@example.com', $registrationRequest->email);
        $this->assertSame('pending_verification', $registrationRequest->status);
        $this->assertSame(0, $registrationRequest->code_attempts);
        $this->assertTrue($registrationRequest->code_expires_at->isFuture());

        $verificationCode = null;
        Mail::assertSent(RegistrationRequestVerificationCode::class, function (RegistrationRequestVerificationCode $mail) use (&$verificationCode, $registrationRequest): bool {
            $verificationCode = $mail->verificationCode;

            return $mail->hasTo('ada@example.com')
                && str_contains($mail->verificationUrl, $registrationRequest->public_token);
        });
        $this->assertSame($verificationCode, $registrationRequest->code_hash);
        $renderedEmail = (new RegistrationRequestVerificationCode(
            $registrationRequest->email,
            $verificationCode,
            route('registration-requests.verify', ['token' => $registrationRequest->public_token]),
        ))->render();
        $this->assertStringContainsString('Dear ada@example.com,', $renderedEmail);
        $this->assertStringContainsString('data:image/', $renderedEmail);
        $this->assertStringContainsString('alt="BrakeSafe"', $renderedEmail);

        $this->get(route('registration-requests.verify', ['token' => $registrationRequest->public_token]))
            ->assertOk()
            ->assertSee('Verify your email');

        $this->post(route('registration-requests.verify.submit', ['token' => $registrationRequest->public_token]), [
            'verification_code' => $verificationCode,
        ])->assertRedirect(route('registration-requests.verify', ['token' => $registrationRequest->public_token]))
            ->assertSessionHas('status', 'Your email is verified. Your access request is waiting for admin approval.');

        $this->assertDatabaseHas('registration_requests', [
            'id' => $registrationRequest->id,
            'status' => 'pending_review',
            'code_hash' => null,
            'code_expires_at' => null,
        ]);

        $this->get(route('registration-requests.verify', ['token' => $registrationRequest->public_token]))
            ->assertOk()
            ->assertSee('Your email is verified. Your request is waiting for admin approval.');
    }

    public function test_admin_can_see_automatically_emailed_requests_while_they_await_verification(): void
    {
        Mail::fake();

        $this->post('/request-access', [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'birthday' => '2000-12-10',
            'email' => 'ada@example.com',
        ])->assertRedirect();

        $registrationRequest = UserRegistrationRequest::where('email', 'ada@example.com')->firstOrFail();

        $this->actingAs(Admin::factory()->create())
            ->get('/registration-requests')
            ->assertOk()
            ->assertSee('ada@example.com')
            ->assertDontSee('data-request-action="send-verification-code"', false);

        $this->assertDatabaseHas('registration_requests', [
            'id' => $registrationRequest->id,
            'status' => 'pending_verification',
            'code_hash' => $registrationRequest->code_hash,
        ]);
        Mail::assertSent(RegistrationRequestVerificationCode::class);
    }

    public function test_admin_can_resend_a_registration_verification_code(): void
    {
        Mail::fake();
        $admin = Admin::factory()->create();
        $registrationRequest = $this->makePendingVerificationRequest();
        $previousCode = $registrationRequest->code_hash;

        $this->actingAs($admin)
            ->postJson("/registration-requests/{$registrationRequest->id}/send-verification-code")
            ->assertOk()
            ->assertJsonPath('message', 'Verification code sent to ada@example.com. The code expires in 10 minutes.');

        $freshRequest = $registrationRequest->fresh();
        $this->assertNotSame($previousCode, $freshRequest->code_hash);
        $this->assertSame(0, $freshRequest->code_attempts);
        $this->assertTrue($freshRequest->code_expires_at->isFuture());
        Mail::assertSent(RegistrationRequestVerificationCode::class, function (RegistrationRequestVerificationCode $mail) use ($freshRequest): bool {
            return $mail->hasTo($freshRequest->email)
                && $mail->verificationCode === $freshRequest->code_hash;
        });
    }

    public function test_admin_can_resend_a_registration_verification_code_after_it_expires(): void
    {
        $admin = Admin::factory()->create();
        $registrationRequest = $this->makePendingVerificationRequest();
        $registrationRequest->update(['code_expires_at' => now()->subSecond()]);

        $this->actingAs($admin)
            ->get('/registration-requests')
            ->assertOk()
            ->assertSee('data-request-action="send-verification-code"', false);
    }

    public function test_request_is_rejected_if_birthday_is_under_18(): void
    {
        Mail::fake();

        $this->post('/request-access', [
            'first_name' => 'Young',
            'last_name' => 'User',
            'birthday' => now()->subYears(18)->addDay()->toDateString(),
            'email' => 'young@example.com',
        ])->assertSessionHasErrors('birthday');

        $this->assertSame(0, UserRegistrationRequest::count());
        Mail::assertNothingSent();
    }

    public function test_incorrect_email_code_increments_attempts_and_locks_after_five_tries(): void
    {
        $registrationRequest = $this->makePendingVerificationRequest();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $response = $this->from(route('registration-requests.verify', ['token' => $registrationRequest->public_token]))
                ->post(route('registration-requests.verify.submit', ['token' => $registrationRequest->public_token]), [
                    'verification_code' => '000000',
                ])
                ->assertRedirect();
        }

        $this->assertSame(5, $registrationRequest->fresh()->code_attempts);
        $this->assertSame('expired', $registrationRequest->fresh()->status);
        $this->assertNull($registrationRequest->fresh()->code_hash);
        $response->assertSessionHasErrors('verification_code');
    }

    public function test_admin_approval_creates_a_user_and_returns_the_one_time_code(): void
    {
        $admin = Admin::factory()->create();
        $registrationRequest = $this->makePendingReviewRequest();

        $response = $this->actingAs($admin)
            ->postJson("/registration-requests/{$registrationRequest->id}/approve")
            ->assertOk()
            ->assertJsonPath('message', 'Access request approved and user account created.')
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.brakesafe_code');

        $code = $response->json('brakesafe_code');
        $user = User::where('email', $registrationRequest->email)->firstOrFail();
        $this->assertSame('Ada Lovelace', $user->name);
        $this->assertTrue(Hash::check(str_replace('-', '', $code), $user->brakesafe_code));
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('registration_requests', [
            'id' => $registrationRequest->id,
            'status' => 'completed',
        ]);

        $this->postJson("/registration-requests/{$registrationRequest->id}/approve")
            ->assertUnprocessable();
    }

    public function test_admin_can_reject_a_verified_request(): void
    {
        $admin = Admin::factory()->create();
        $registrationRequest = $this->makePendingReviewRequest();

        $this->actingAs($admin)
            ->postJson("/registration-requests/{$registrationRequest->id}/reject")
            ->assertOk()
            ->assertJsonPath('message', 'Access request rejected.');

        $this->assertDatabaseHas('registration_requests', [
            'id' => $registrationRequest->id,
            'status' => 'rejected',
        ]);
        $this->assertDatabaseMissing('users', ['email' => $registrationRequest->email]);
    }

    public function test_admin_request_review_page_requires_admin_authentication(): void
    {
        $this->get('/registration-requests')->assertRedirect('/login');
    }

    public function test_admin_request_review_page_displays_pending_requests_without_review_actions(): void
    {
        $admin = Admin::factory()->create();
        $registrationRequest = $this->makePendingReviewRequest();
        $registrationRequest->update(['status' => 'pending']);

        $this->actingAs($admin)
            ->get('/registration-requests')
            ->assertOk()
            ->assertSee('Ada Lovelace')
            ->assertSee('pending')
            ->assertSee('User Requests')
            ->assertDontSee('Header navigation')
            ->assertSee('id="admin-user-badge"', false)
            ->assertSee('data-request-action="send-verification-code"', false)
            ->assertSee('Resend becomes available when a code expires or delivery fails.')
            ->assertSee('data-request-action="delete"', false)
            ->assertDontSee('data-request-action="approve"')
            ->assertDontSee('data-request-action="reject"');
    }

    public function test_header_shows_admin_name_and_notification_icon_without_profile_picture(): void
    {
        Storage::fake('public');
        $photoPath = 'profile-photos/admin-avatar.png';
        Storage::disk('public')->put($photoPath, 'test image content');
        $admin = Admin::factory()->create([
            'full_name' => 'BrakeSafe Admin',
            'profile_photo_path' => $photoPath,
        ]);

        $response = $this->actingAs($admin)
            ->get('/registration-requests')
            ->assertOk();

        $response
            ->assertSee('id="admin-user-badge"', false)
            ->assertSee('aria-label="Signed in as BrakeSafe Admin"', false)
            ->assertSee('BrakeSafe Admin')
            ->assertSee('<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>', false)
            ->assertSee(Storage::disk('public')->url($photoPath), false)
            ->assertSee('profile picture', false);

        $this->assertSame(1, preg_match('/<div id="admin-user-badge"[^>]*>(.*?)<\/div>/s', $response->getContent(), $headerBadge));
        $this->assertStringNotContainsString('data-admin-avatar', $headerBadge[1]);
    }

    public function test_admin_request_review_page_handles_missing_birthday(): void
    {
        $admin = Admin::factory()->create();
        $registrationRequest = $this->makePendingReviewRequest();
        $registrationRequest->update(['birthday' => null]);

        $this->actingAs($admin)
            ->get('/registration-requests')
            ->assertOk()
            ->assertSee('ada@example.com')
            ->assertSee('Public token:')
            ->assertSee($registrationRequest->public_token)
            ->assertDontSee('Birthday:');
    }

    public function test_admin_can_delete_an_access_request_without_deleting_an_existing_user(): void
    {
        $admin = Admin::factory()->create();
        $registrationRequest = $this->makePendingReviewRequest();
        $registrationRequest->update(['status' => 'completed']);
        User::create([
            'name' => 'Ada Lovelace',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'email' => $registrationRequest->email,
            'birthday' => '2000-12-10',
            'password' => Hash::make('ExamplePass123!'),
        ]);

        $this->actingAs($admin)
            ->deleteJson("/registration-requests/{$registrationRequest->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Access request deleted.');

        $this->assertDatabaseMissing('registration_requests', ['id' => $registrationRequest->id]);
        $this->assertDatabaseHas('users', ['email' => $registrationRequest->email]);
    }

    public function test_deleting_an_access_request_requires_admin_authentication(): void
    {
        $registrationRequest = $this->makePendingReviewRequest();

        $this->deleteJson("/registration-requests/{$registrationRequest->id}")
            ->assertUnauthorized();

        $this->assertDatabaseHas('registration_requests', ['id' => $registrationRequest->id]);
    }

    private function makePendingVerificationRequest(): UserRegistrationRequest
    {
        return UserRegistrationRequest::create([
            'public_token' => (string) Str::uuid(),
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'birthday' => '2000-12-10',
            'email' => 'ada@example.com',
            'status' => 'pending_verification',
            'code_hash' => '123456',
            'code_expires_at' => now()->addMinutes(10),
            'code_attempts' => 0,
        ]);
    }

    private function makePendingReviewRequest(): UserRegistrationRequest
    {
        return UserRegistrationRequest::create([
            'public_token' => (string) Str::uuid(),
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'birthday' => '2000-12-10',
            'email' => 'ada@example.com',
            'status' => 'pending_review',
            'code_attempts' => 0,
        ]);
    }
}
