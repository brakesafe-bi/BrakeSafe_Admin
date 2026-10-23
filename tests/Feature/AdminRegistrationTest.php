<?php

namespace Tests\Feature;

use App\Mail\OtpVerification;
use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_an_admin_and_sends_otp(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'full_name' => 'BrakeSafe Admin',
            'email' => 'admin@example.com',
            'password' => 'SafePassword123!',
            'password_confirmation' => 'SafePassword123!',
            'role' => 'user',
        ]);

        $response->assertOk()->assertViewIs('auth.verify-otp');
        $this->assertTrue(Schema::hasTable('admins'));
        $this->assertDatabaseHas('admins', [
            'email' => 'admin@example.com',
            'is_verified' => false,
        ]);
        Mail::assertSent(OtpVerification::class, fn (OtpVerification $mail) => $mail->hasTo('admin@example.com'));

        $admin = Admin::where('email', 'admin@example.com')->firstOrFail();
        $this->post('/register/verify-otp', ['otp' => session('otp')])
            ->assertRedirect('/')
            ->assertSessionHas('login_success', true);

        $this->assertDatabaseHas('admins', [
            'id' => $admin->id,
            'is_verified' => true,
        ]);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_registration_is_unavailable_once_the_single_admin_account_exists(): void
    {
        $existingAdmin = Admin::factory()->create();
        Mail::fake();

        $this->get('/register')
            ->assertRedirect('/login');

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('<a href="/register" class="small"', false);

        $this->post('/register', [
            'full_name' => 'Second Admin',
            'email' => 'second-admin@example.com',
            'password' => 'SafePassword123!',
            'password_confirmation' => 'SafePassword123!',
        ])->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertSame(1, Admin::count());
        $this->assertDatabaseHas('admins', ['id' => $existingAdmin->id]);
        $this->assertDatabaseMissing('admins', ['email' => 'second-admin@example.com']);
        Mail::assertNothingSent();
    }

    public function test_successful_login_shows_welcome_alert_before_dashboard_loading_animation(): void
    {
        $admin = Admin::factory()->create();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/')
            ->assertSessionHas('login_success', true);

        $this->assertAuthenticatedAs($admin);

        $this->get('/')
            ->assertOk()
            ->assertSee('Welcome to BrakeSafe')
            ->assertSee('Welcome back, ' . $admin->full_name . '!', false)
            ->assertSee('Loading your dashboard...', false)
            ->assertSee('startTransition(true, true)', false);
    }

    public function test_invalid_login_credentials_show_a_sweet_alert(): void
    {
        $admin = Admin::factory()->create();

        $this->from('/login')
            ->post('/login', [
                'email' => $admin->email,
                'password' => 'incorrect-password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->get('/login')
            ->assertOk()
            ->assertSee('title: \'Login failed\'', false)
            ->assertSee('The email or password you entered is incorrect. Please try again.')
            ->assertSee('confirmButtonText: \'Try again\'', false);
    }

    public function test_opening_logout_url_with_get_redirects_without_logging_out(): void
    {
        $admin = Admin::factory()->create();
        $this->actingAs($admin);

        $this->get('/logout')
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($admin);

        $this->post('/logout')
            ->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_expired_logout_request_redirects_without_bypassing_csrf_protection(): void
    {
        $admin = Admin::factory()->create();
        Route::post('/logout', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        })->middleware('auth');

        $this->actingAs($admin)
            ->post('/logout')
            ->assertRedirect('/')
            ->assertSessionHasErrors('logout');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_admin_url_redirects_to_the_dashboard(): void
    {
        $this->get('/admin')->assertRedirect('/login');

        $this->actingAs(Admin::factory()->create())
            ->get('/admin')
            ->assertRedirect('/');
    }

    public function test_admin_pages_redirect_guests_to_login_when_opened_directly(): void
    {
        foreach ([
            '/',
            '/admin',
            '/analytics',
            '/apa',
            '/brake-events',
            '/password-reset-requests',
            '/profile',
            '/registration-requests',
            '/users',
            '/web-server',
        ] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_apa_map_requires_a_maptiler_key(): void
    {
        config(['services.maptiler.key' => null]);
        $this->actingAs(Admin::factory()->create());

        $this->get('/apa')
            ->assertOk()
            ->assertSee('MapTiler API key is missing.');
    }

    public function test_dashboard_works_without_device_or_road_segment_data(): void
    {
        $this->assertFalse(Schema::hasTable('vehicles'));
        $this->assertFalse(Schema::hasTable('devices'));
        $this->assertFalse(Schema::hasTable('road_segments'));
        $this->assertFalse(Schema::hasColumn('brake_events', 'device_id'));
        $this->assertFalse(Schema::hasColumn('brake_events', 'road_segment'));

        $this->actingAs(Admin::factory()->create())
            ->get('/')
            ->assertOk();
    }

    public function test_live_heatmap_route_is_removed(): void
    {
        $this->actingAs(Admin::factory()->create())
            ->get('/live-heatmap')
            ->assertNotFound();
    }

    public function test_apa_map_uses_configured_maptiler_key(): void
    {
        config(['services.maptiler.key' => 'test-map-key']);
        $this->actingAs(Admin::factory()->create());

        $this->get('/apa')
            ->assertOk()
            ->assertSee('api.maptiler.com/maps/streets-v2-dark', false)
            ->assertSee('test-map-key', false)
            ->assertSee('MapTiler could not load map tiles.');
    }
}
