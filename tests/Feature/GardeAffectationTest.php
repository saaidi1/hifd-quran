<?php

namespace Tests\Feature;

use App\Models\Groupe;
use App\Models\User;
use App\Services\InscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Le حارس عام (garde_general) peut affecter des étudiants à des حلقة. */
class GardeAffectationTest extends TestCase
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

    public function test_garde_peut_affecter_un_etudiant(): void
    {
        $garde   = $this->user('garde@madrasa.ma');
        $prof    = $this->user('professeur@madrasa.ma');
        $service = app(InscriptionService::class);

        $etudiant = $service->preinscrire(['nom' => 'A', 'prenom' => 'B', 'sexe' => 'M'], $garde);
        $service->evaluer($etudiant, ['decision' => 'valide', 'note_hifd' => 15, 'note_tajwid' => 14, 'note_lecture' => 16], $garde);
        $groupe = Groupe::create(['nom' => 'G', 'professeur_id' => $prof->id, 'capacite' => 10]);

        $this->actingAs($garde)
            ->post("/etudiants/{$etudiant->id}/affecter", ['groupe_id' => $groupe->id])
            ->assertSessionHasNoErrors();

        $this->assertSame($groupe->id, $etudiant->fresh()->groupe_id);
    }

    public function test_garde_peut_affecter_en_masse(): void
    {
        $garde   = $this->user('garde@madrasa.ma');
        $prof    = $this->user('professeur@madrasa.ma');
        $service = app(InscriptionService::class);

        $ids = [];
        foreach (['A', 'B', 'C'] as $nom) {
            $e = $service->preinscrire(['nom' => $nom, 'prenom' => 'X', 'sexe' => 'M'], $garde);
            $service->evaluer($e, ['decision' => 'valide', 'note_hifd' => 15, 'note_tajwid' => 14, 'note_lecture' => 16], $garde);
            $ids[] = $e->id;
        }
        $groupe = Groupe::create(['nom' => 'GM', 'professeur_id' => $prof->id, 'capacite' => 10]);

        $this->actingAs($garde)
            ->post('/etudiants/affecter-multiple', ['ids' => $ids, 'groupe_id' => $groupe->id])
            ->assertSessionHasNoErrors();

        $this->assertSame(3, \App\Models\Etudiant::where('groupe_id', $groupe->id)->count());
    }

    public function test_garde_peut_creer_et_gerer_un_groupe(): void
    {
        $garde = $this->user('garde@madrasa.ma');
        $prof  = $this->user('professeur@madrasa.ma');

        $this->actingAs($garde)->get('/groupes/create')->assertOk();

        $this->actingAs($garde)->post('/groupes', [
            'nom_ar'        => 'حلقة الحفظ الجديدة',
            'professeur_id' => $prof->id,
            'capacite'      => 15,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $groupe = \App\Models\Groupe::where('nom_ar', 'حلقة الحفظ الجديدة')->firstOrFail();
        $this->assertSame($prof->id, $groupe->professeur_id);

        $this->actingAs($garde)->get("/groupes/{$groupe->id}/edit")->assertOk();
        $this->actingAs($garde)->put("/groupes/{$groupe->id}", [
            'nom_ar'   => 'حلقة الحفظ المحدثة',
            'capacite' => 20,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('حلقة الحفظ المحدثة', $groupe->fresh()->nom_ar);
    }

    public function test_superviseur_ne_peut_pas_affecter(): void
    {
        $superviseur = $this->user('superviseur@madrasa.ma');
        $service     = app(InscriptionService::class);
        $etudiant    = $service->preinscrire(['nom' => 'S', 'prenom' => 'T', 'sexe' => 'M'], $superviseur);

        $this->actingAs($superviseur)
            ->post("/etudiants/{$etudiant->id}/affecter", ['groupe_id' => 1])
            ->assertForbidden();
    }
}
