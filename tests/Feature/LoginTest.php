<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_page_renders_in_arabic(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('تسجيل الدخول', false);
    }

    public function test_user_can_login(): void
    {
        $this->post('/login', [
            'email'    => 'directeur@madrasa.ma',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->post('/login', [
            'email'    => 'directeur@madrasa.ma',
            'password' => 'mauvais-mot-de-passe',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        User::where('email', 'garde@madrasa.ma')->update(['actif' => false]);

        $this->post('/login', [
            'email'    => 'garde@madrasa.ma',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_cannot_access_login_page(): void
    {
        $this->actingAs(User::where('email', 'directeur@madrasa.ma')->first())
            ->get('/login')
            ->assertRedirect(route('dashboard'));
    }

    public function test_logout(): void
    {
        $user = User::where('email', 'directeur@madrasa.ma')->first();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
