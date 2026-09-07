<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $email): User
    {
        return User::where('email', $email)->firstOrFail();
    }

    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_unauthenticated_is_redirected(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_directeur_can_access_dashboard_and_resources(): void
    {
        $this->actingAs($this->user('directeur@madrasa.ma'))
            ->get('/')
            ->assertOk();
    }

    /**
     * @dataProvider roleProvider
     */
    public function test_each_role_can_log_into_panel(string $slug): void
    {
        $this->actingAs($this->user($slug.'@madrasa.ma'))
            ->get('/')
            ->assertOk();
    }

    /**
     * @dataProvider fournisseurComptesDeTest
     */
    public function test_compte_de_test_du_seeder_est_fonctionnel(string $email): void
    {
        $user = $this->user($email);
        $this->assertTrue($user->actif, $email.' actif');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', $user->password), $email);
        $this->actingAs($user)->get('/')->assertOk();
    }

    public static function fournisseurComptesDeTest(): array
    {
        return array_map(
            fn (string $email) => [$email],
            [
                'directeur@madrasa.ma', 'directeur2@madrasa.ma', 'directeur3@madrasa.ma',
                'superviseur@madrasa.ma', 'superviseur2@madrasa.ma', 'superviseur3@madrasa.ma',
                'garde@madrasa.ma', 'garde2@madrasa.ma', 'garde3@madrasa.ma',
                'professeur@madrasa.ma', 'professeur2@madrasa.ma', 'professeur3@madrasa.ma',
            ]
        );
    }

    public static function roleProvider(): array
    {
        return [
            'directeur'   => ['directeur'],
            'superviseur' => ['superviseur'],
            'garde'       => ['garde'],
            'professeur'  => ['professeur'],
        ];
    }

    public function test_guest_cannot_access_sante_route(): void
    {
        $this->get('/sante')->assertOk()->assertJson(['statut' => 'ok']);
    }

    public function test_all_resources_respond_200_for_directeur(): void
    {
        $this->actingAs($this->user('directeur@madrasa.ma'));
        foreach (['etudiants', 'groupes', 'professeurs', 'rapport-journaliers', 'rapport-periodiques'] as $resource) {
            $this->get('/'.$resource)->assertOk();
        }
    }

    public function test_directeur_est_consommateur_de_rapports_seulement(): void
    {
        $directeur = $this->user('directeur@madrasa.ma');
        $groupe    = \App\Models\Groupe::firstOrFail();

        // Le directeur reçoit tous les rapports : journalier + périodique.
        $this->actingAs($directeur)->get('/rapport-journaliers')->assertOk();
        $this->actingAs($directeur)->get('/rapport-periodiques')->assertOk();

        // Mais il ne gère ni les حلقات, ni تسميع الحلقة, ni الغرف والإيواء.
        $this->actingAs($directeur)->get('/groupes/create')->assertForbidden();
        $this->actingAs($directeur)->get("/groupes/{$groupe->id}/edit")->assertForbidden();
        $this->actingAs($directeur)->get('/tasmi')->assertForbidden();
        $this->actingAs($directeur)->get('/logements')->assertForbidden();
        $this->actingAs($directeur)->get('/rapport-journaliers/create')->assertForbidden();
    }

    public function test_inactive_user_cannot_access_panel(): void
    {
        $user = $this->user('directeur@madrasa.ma');
        $user->update(['actif' => false]);
        $this->actingAs($user)->get('/')->assertForbidden();
    }

    public function test_formulaire_creation_etudiant_repond_200(): void
    {
        $this->actingAs($this->user('garde@madrasa.ma'))
            ->get('/etudiants/create')
            ->assertOk();
    }
}
