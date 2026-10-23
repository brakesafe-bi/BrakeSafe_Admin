<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_shows_existing_account_values_and_empty_password_fields(): void
    {
        $admin = Admin::factory()->create([
            'name' => 'WinverCalawagan',
            'full_name' => 'WinverCalawagan',
            'email' => 'brakesafebi@gmail.com',
        ]);

        $this->actingAs($admin)
            ->get('/profile')
            ->assertOk()
            ->assertViewIs('profile.index')
            ->assertSee('name="first_name"', false)
            ->assertSee('value="Winver"', false)
            ->assertSee('name="last_name"', false)
            ->assertSee('value="Calawagan"', false)
            ->assertSeeInOrder(['Signed in', 'brakesafebi@gmail.com'], false)
            ->assertSee('value="brakesafebi@gmail.com"', false)
            ->assertSee('Leave new password fields empty to keep your current password.')
            ->assertSee('Save profile')
            ->assertSee('name="current_password" type="password"', false)
            ->assertSee('name="profile_photo"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('data-admin-avatar-fallback', false)
            ->assertSee("photoInput.addEventListener('change'", false)
            ->assertSee("document.querySelectorAll('[data-admin-avatar]')", false)
            ->assertDontSee('Header navigation')
            ->assertSee('profile-notifications-toggle', false);
    }

    public function test_profile_details_can_be_updated_without_changing_password(): void
    {
        $admin = Admin::factory()->create();
        $originalPassword = $admin->password;

        $this->actingAs($admin)
            ->put('/profile', [
                'first_name' => 'Updated',
                'last_name' => 'Admin',
                'email' => 'updated-admin@example.com',
                'current_password' => '',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect('/profile');

        $admin->refresh();
        $this->assertSame('Updated Admin', $admin->full_name);
        $this->assertSame('Updated Admin', $admin->name);
        $this->assertSame('updated-admin@example.com', $admin->email);
        $this->assertSame($originalPassword, $admin->password);
    }

    public function test_profile_picture_upload_is_saved_to_public_storage_and_database(): void
    {
        Storage::fake('public');
        $admin = Admin::factory()->create();

        $this->actingAs($admin)
            ->put('/profile', [
                'first_name' => 'Test',
                'last_name' => 'Admin',
                'email' => $admin->email,
                'current_password' => '',
                'password' => '',
                'password_confirmation' => '',
                'profile_photo' => UploadedFile::fake()->createWithContent(
                    'profile.png',
                    file_get_contents(public_path('administrator/logo.png'))
                ),
            ])
            ->assertRedirect('/profile');

        $photoPath = $admin->fresh()->profile_photo_path;
        $this->assertIsString($photoPath);
        $this->assertStringStartsWith('profile-photos/', $photoPath);
        Storage::disk('public')->assertExists($photoPath);

        $this->get('/profile')
            ->assertOk()
            ->assertSee(Storage::disk('public')->url($photoPath), false);
    }

    public function test_profile_picture_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $admin = Admin::factory()->create();

        $this->actingAs($admin)
            ->from('/profile')
            ->put('/profile', [
                'first_name' => 'Test',
                'last_name' => 'Admin',
                'email' => $admin->email,
                'current_password' => '',
                'password' => '',
                'password_confirmation' => '',
                'profile_photo' => UploadedFile::fake()->create('document.txt', 10, 'text/plain'),
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrors('profile_photo');

        $this->assertNull($admin->fresh()->profile_photo_path);
        Storage::disk('public')->assertDirectoryEmpty('profile-photos');
    }

    public function test_profile_password_can_be_changed_after_confirming_current_password(): void
    {
        $admin = Admin::factory()->create(['password' => 'OldPassword123!']);

        $this->actingAs($admin)
            ->put('/profile', [
                'first_name' => 'Test',
                'last_name' => 'Admin',
                'email' => $admin->email,
                'current_password' => 'OldPassword123!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertRedirect('/profile');

        $this->assertTrue(Hash::check('NewPassword123!', $admin->fresh()->password));
    }

    public function test_profile_password_change_requires_valid_current_password(): void
    {
        $admin = Admin::factory()->create();
        $originalPassword = $admin->password;

        $this->actingAs($admin)
            ->from('/profile')
            ->put('/profile', [
                'first_name' => 'Test',
                'last_name' => 'Admin',
                'email' => $admin->email,
                'current_password' => 'incorrect',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ])
            ->assertRedirect('/profile')
            ->assertSessionHasErrors('current_password');

        $this->assertSame($originalPassword, $admin->fresh()->password);
    }
}
