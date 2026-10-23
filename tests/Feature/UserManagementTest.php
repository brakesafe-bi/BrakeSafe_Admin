<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_page_redirects_to_requests_and_users_sidebar_link_is_removed(): void
    {
        $admin = Admin::factory()->create();

        $this->actingAs($admin)
            ->get('/users')
            ->assertRedirect(route('registration-requests.index'));

        $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));
        $this->assertIsString($layout);
        $this->assertStringNotContainsString("route('users.index')", $layout);
    }

    public function test_sidebar_open_uses_staggered_navigation_item_animation_on_desktop_and_mobile(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));
        $this->assertIsString($layout);
        $this->assertStringContainsString('@keyframes sidebar-nav-item-pop', $layout);
        $this->assertStringContainsString('#sidebar.sidebar-items-entering .nav-item:nth-child(7)', $layout);
        $this->assertStringContainsString('#sidebar.sidebar-items-entering .sidebar-footer-collapse', $layout);
        $this->assertStringContainsString('id="profile-sidebar" class="sidebar-footer-item sidebar-footer-profile', $layout);
        $this->assertStringContainsString('class="sidebar-footer-item sidebar-footer-logout mb-3"', $layout);
        $this->assertStringContainsString('class="sidebar-footer-item sidebar-footer-collapse hidden lg:flex', $layout);
        $this->assertStringContainsString('wasCollapsed && !isCollapsed && window.innerWidth >= 1024', $layout);
        $this->assertStringContainsString('window.BrakeSafeAnimateSidebarItems = animateSidebarItems', $layout);
        $this->assertStringContainsString('if (isVisible) window.BrakeSafeAnimateSidebarItems(sidebar)', $layout);
        $this->assertStringContainsString('window.BrakeSafeAnimateSidebarItems?.(sidebar)', file_get_contents(resource_path('views/analytics/index.blade.php')));
        $this->assertStringContainsString('window.BrakeSafeAnimateSidebarItems?.(sidebar)', file_get_contents(resource_path('views/incidents/index.blade.php')));
        $this->assertStringContainsString('prefers-reduced-motion: reduce', $layout);
    }

    public function test_sidebar_shows_live_updates_above_the_signed_in_account(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));
        $this->assertIsString($layout);
        $liveUpdatePosition = strpos($layout, 'id="live-update-indicator"');
        $signedInPosition = strpos($layout, 'id="profile-sidebar"');

        $this->assertNotFalse($liveUpdatePosition);
        $this->assertNotFalse($signedInPosition);
        $this->assertLessThan($signedInPosition, $liveUpdatePosition);

        $this->actingAs(Admin::factory()->create())
            ->get('/')
            ->assertOk()
            ->assertSee('Live updates ·', false)
            ->assertSee('id="profile-sidebar"', false);

        $this->assertStringContainsString('html[data-admin-theme="light"] #live-update-indicator', $layout);
        $this->assertStringContainsString('color: #334155 !important;', $layout);
    }

    public function test_web_server_page_uses_a_fullscreen_browser_layout(): void
    {
        $this->actingAs(Admin::factory()->create())
            ->get('/web-server')
            ->assertOk()
            ->assertDontSee('id="menu-toggle"', false)
            ->assertDontSee('Barangay maps, alerts & brake events')
            ->assertSee('id="web-server-app"', false)
            ->assertSee('id="server-address-form"', false)
            ->assertSee('web-server-page', false)
            ->assertSee('body.web-server-page #sidebar', false)
            ->assertSee('aria-label="Return to Dashboard"', false)
            ->assertSee('height: 100dvh;', false);
    }

    public function test_user_can_be_created_with_sanitized_details_and_a_hashed_password(): void
    {
        $response = $this->actingAs(Admin::factory()->create())
            ->postJson('/users', [
                'first_name' => " <b>Ada</b>\n",
                'last_name' => ' Lovelace ',
                'email' => ' <em>ada@example.com</em> ',
                'birthday' => '2000-12-10',
                'password' => 'ExamplePass123!',
                'password_confirmation' => 'ExamplePass123!',
            ])
            ->assertCreated()
            ->assertJsonPath('user.name', 'Ada Lovelace')
            ->assertJsonPath('user.first_name', 'Ada')
            ->assertJsonPath('user.last_name', 'Lovelace')
            ->assertJsonPath('user.email', 'ada@example.com')
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.brakesafe_code')
            ->assertJsonPath('brakesafe_code', fn ($code) => preg_match('/^BS-[A-HJ-NP-Z2-9]{4}(?:-[A-HJ-NP-Z2-9]{4}){3}$/', $code) === 1);

        $brakeSafeCode = $response->json('brakesafe_code');
        $user = User::where('email', 'ada@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('ExamplePass123!', $user->password));
        $this->assertTrue(Hash::check(str_replace('-', '', $brakeSafeCode), $user->brakesafe_code));
        $this->assertNotSame($brakeSafeCode, $user->brakesafe_code);
        $this->assertSame('2000-12-10', $user->birthday->format('Y-m-d'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Ada Lovelace',
        ]);
    }

    public function test_user_creation_rejects_a_birthday_that_makes_the_user_under_18(): void
    {
        $this->actingAs(Admin::factory()->create())
            ->postJson('/users', [
                'first_name' => 'Young',
                'last_name' => 'User',
                'email' => 'young@example.com',
                'birthday' => now()->subYears(18)->addDay()->toDateString(),
                'password' => 'ExamplePass123!',
                'password_confirmation' => 'ExamplePass123!',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('birthday');

        $this->assertDatabaseMissing('users', ['email' => 'young@example.com']);
    }

    public function test_user_creation_allows_a_birthday_on_the_minimum_age_boundary(): void
    {
        $birthday = now()->subYears(18)->toDateString();

        $this->actingAs(Admin::factory()->create())
            ->postJson('/users', [
                'first_name' => 'Adult',
                'last_name' => 'User',
                'email' => 'adult@example.com',
                'birthday' => $birthday,
                'password' => 'ExamplePass123!',
                'password_confirmation' => 'ExamplePass123!',
            ])
            ->assertCreated();

        $this->assertSame($birthday, User::where('email', 'adult@example.com')->firstOrFail()->birthday->format('Y-m-d'));
    }

    public function test_user_creation_rejects_a_weak_password(): void
    {
        $this->actingAs(Admin::factory()->create())
            ->postJson('/users', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'weak@example.com',
                'birthday' => '1990-01-01',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_password_must_include_a_non_space_symbol(): void
    {
        $this->actingAs(Admin::factory()->create())
            ->postJson('/users', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'nosymbol@example.com',
                'birthday' => '1990-01-01',
                'password' => 'Abcdef12 ',
                'password_confirmation' => 'Abcdef12 ',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_user_creation_requires_a_matching_password_confirmation(): void
    {
        $this->actingAs(Admin::factory()->create())
            ->postJson('/users', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'mismatch@example.com',
                'birthday' => '1990-01-01',
                'password' => 'ExamplePass123!',
                'password_confirmation' => 'DifferentPass123!',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_user_details_can_be_updated_without_changing_password(): void
    {
        $user = User::create([
            'name' => 'Grace Hopper',
            'first_name' => 'Grace',
            'last_name' => 'Hopper',
            'email' => 'grace@example.com',
            'birthday' => '1980-12-09',
            'password' => 'OriginalPass123!',
        ]);
        $originalPassword = $user->password;

        $this->actingAs(Admin::factory()->create())
            ->putJson("/users/{$user->id}", [
                'first_name' => '<strong>Grace</strong>',
                'last_name' => 'Brewster Hopper',
                'email' => 'grace@example.com',
                'birthday' => '1980-12-09',
                'password' => null,
                'password_confirmation' => null,
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Grace Brewster Hopper')
            ->assertJsonPath('user.first_name', 'Grace');

        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_user_can_be_deleted(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test-user@example.com',
            'birthday' => '1990-01-01',
            'password' => 'ExamplePass123!',
        ]);

        $this->actingAs(Admin::factory()->create())
            ->deleteJson("/users/{$user->id}")
            ->assertOk()
            ->assertJsonPath('message', 'User deleted successfully.');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_users_page_requires_admin_authentication(): void
    {
        $this->get('/users')->assertRedirect('/login');
    }

    public function test_brakesafe_code_resets_a_user_password_once_and_is_invalidated(): void
    {
        $user = User::create([
            'name' => 'Recovery User',
            'first_name' => 'Recovery',
            'last_name' => 'User',
            'email' => 'recovery@example.com',
            'birthday' => '1990-01-01',
            'password' => 'OldPassword123!',
        ]);
        $code = 'BS-ABCD-EFGH-JKLM-NPQR';
        $user->forceFill(['brakesafe_code' => Hash::make(str_replace('-', '', $code))])->save();

        $this->from('/forgot-password')
            ->post('/user-password/reset', [
                'email' => $user->email,
                'brakesafe_code' => strtolower($code),
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertRedirect('/login')
            ->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $user->password));
        $this->assertNull($user->brakesafe_code);

        $this->from('/forgot-password')
            ->post('/user-password/reset', [
                'email' => $user->email,
                'brakesafe_code' => $code,
                'password' => 'AnotherPassword123!',
                'password_confirmation' => 'AnotherPassword123!',
            ])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('brakesafe_code')
            ->assertSessionHas('_old_input', fn (array $input) => ! array_key_exists('brakesafe_code', $input));
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }

    public function test_invalid_brakesafe_code_does_not_change_the_password(): void
    {
        $user = User::create([
            'name' => 'Recovery User',
            'first_name' => 'Recovery',
            'last_name' => 'User',
            'email' => 'recovery@example.com',
            'birthday' => '1990-01-01',
            'password' => 'OldPassword123!',
        ]);
        $user->forceFill(['brakesafe_code' => Hash::make('BSABCDEFGHIJKLMNOPQR')])->save();
        $originalPassword = $user->password;

        $this->from('/forgot-password')
            ->post('/user-password/reset', [
                'email' => $user->email,
                'brakesafe_code' => 'BS-AAAA-AAAA-AAAA-AAAA',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors('brakesafe_code');

        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_admin_can_regenerate_a_users_brakesafe_code(): void
    {
        $user = User::create([
            'name' => 'Recovery User',
            'first_name' => 'Recovery',
            'last_name' => 'User',
            'email' => 'recovery@example.com',
            'password' => 'OldPassword123!',
        ]);
        $oldCode = 'BS-ABCD-EFGH-JKLM-NPQR';
        $user->forceFill(['brakesafe_code' => Hash::make(str_replace('-', '', $oldCode))])->save();

        $response = $this->actingAs(Admin::factory()->create())
            ->putJson("/users/{$user->id}", [
                'first_name' => 'Recovery',
                'last_name' => 'User',
                'email' => $user->email,
                'birthday' => '1990-01-01',
                'password' => null,
                'password_confirmation' => null,
                'regenerate_brakesafe_code' => true,
            ])
            ->assertOk()
            ->assertJsonMissingPath('user.brakesafe_code');

        $newCode = $response->json('brakesafe_code');
        $this->assertIsString($newCode);
        $this->assertNotSame($oldCode, $newCode);
        $this->assertTrue(Hash::check(str_replace('-', '', $newCode), $user->fresh()->brakesafe_code));
    }

    public function test_forgot_password_page_shows_only_administrator_recovery(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Reset your password')
            ->assertSee('Administrator account')
            ->assertSee('Email password reset link')
            ->assertSee('Administrator email')
            ->assertDontSee('BrakeSafe user account')
            ->assertDontSee('Request verification code')
            ->assertDontSee('Already have a code? Continue to password reset')
            ->assertDontSee('BrakeSafeCode')
            ->assertDontSee('Six-digit verification code')
            ->assertDontSee('Confirm new password')
            ->assertDontSee('Reset password with code');
    }
}
