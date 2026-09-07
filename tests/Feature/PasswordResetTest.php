<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** La page « mot de passe oublié » affiche le formulaire. */
    public function test_page_mot_de_passe_oublie_s_affiche(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('email', false);
    }

    /** Un e-mail connu reçoit le lien : la notification est envoyée et un token enregistré. */
    public function test_envoi_lien_reinitialisation_pour_email_connu(): void
    {
        Notification::fake();

        $user = User::where('email', 'directeur@madrasa.ma')->firstOrFail();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        Notification::assertSentTo($user, ResetPassword::class);
        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    }

    /** Un e-mail inconnu n'est jamais révélé (anti-énumération) et rien n'est envoyé. */
    public function test_email_inconnu_ne_revele_pas_l_existence(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'personne@inconnue.ma'])
            ->assertSessionHasErrors('email');

        Notification::assertNothingSent();
    }

    /** La page de réinitialisation s'affiche avec le token et l'e-mail pré-rempli. */
    public function test_page_reset_s_affiche_avec_le_token(): void
    {
        $user = User::where('email', 'directeur@madrasa.ma')->firstOrFail();
        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', ['token' => $token]).'?email='.$user->email)
            ->assertOk();
    }

    /** Le flux complet : token valide → nouveau mot de passe → reconnexion avec le nouveau mot de passe. */
    public function test_reinitialisation_mot_de_passe_avec_token_valide(): void
    {
        $user = User::where('email', 'directeur@madrasa.ma')->firstOrFail();
        $token = Password::broker()->createToken($user);

        $this->post(route('password.store'), [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'NouveauMotDePasse123',
            'password_confirmation' => 'NouveauMotDePasse123',
        ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

        $this->post(route('login.submit'), [
            'email'    => $user->email,
            'password' => 'NouveauMotDePasse123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    /** Un token invalide ou expiré est refusé. */
    public function test_token_invalide_est_refuse(): void
    {
        $user = User::where('email', 'directeur@madrasa.ma')->firstOrFail();

        $this->post(route('password.store'), [
            'token'                 => 'token-invalide',
            'email'                 => $user->email,
            'password'              => 'NouveauMotDePasse123',
            'password_confirmation' => 'NouveauMotDePasse123',
        ])->assertSessionHasErrors('email');

        $this->assertFalse(
            \Illuminate\Support\Facades\Hash::check('NouveauMotDePasse123', $user->fresh()->password)
        );
    }
}