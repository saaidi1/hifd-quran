<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\InscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
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

    public function test_directeur_pages(): void
    {
        $this->actingAs($this->user('directeur@madrasa.ma'));

        foreach ([
            '/', '/etudiants', '/etudiants/create', '/groupes',
            '/professeurs', '/professeurs/create',
            '/rapport-journaliers', '/rapport-periodiques',
        ] as $url) {
            $this->get($url)->assertOk($url);
        }
    }

    public function test_directeur_est_interdit_des_zones_operationnelles(): void
    {
        $directeur = $this->user('directeur@madrasa.ma');
        $groupe    = \App\Models\Groupe::firstOrFail();

        foreach (['/groupes/create', '/logements', '/logements/create', '/tasmi', '/rapport-journaliers/create'] as $url) {
            $this->actingAs($directeur)->get($url)->assertForbidden($url);
        }
        $this->actingAs($directeur)->get("/groupes/{$groupe->id}/edit")->assertForbidden();
    }

    public function test_groupe_edit_renders(): void
    {
        $groupe = \App\Models\Groupe::firstOrFail();
        $this->actingAs($this->user('superviseur@madrasa.ma'))
            ->get("/groupes/{$groupe->id}/edit")->assertOk();
    }

    public function test_etudiant_show_renders(): void
    {
        $etudiant = \App\Models\Etudiant::firstOrFail();
        $this->actingAs($this->user('directeur@madrasa.ma'))
            ->get("/etudiants/{$etudiant->id}")->assertOk();
    }

    public function test_professeur_pages(): void
    {
        $this->actingAs($this->user('professeur@madrasa.ma'));

        foreach (['/', '/tasmi', '/rapport-journaliers', '/rapport-journaliers/create'] as $url) {
            $this->get($url)->assertOk($url);
        }
    }

    public function test_professeur_ne_peut_pas_ajouter_etudiant(): void
    {
        $user = $this->user('professeur@madrasa.ma');

        $this->actingAs($user)->get('/etudiants/create')->assertForbidden();

        $this->actingAs($user)->post('/etudiants', [
            'nom_ar'           => 'ممنوع',
            'prenom_ar'        => 'اختبار',
            'sexe'             => 'M',
            'date_naissance'   => '2010-01-01',
            'tuteur_nom'       => 'ولي',
            'tuteur_telephone' => '0611111111',
        ])->assertForbidden();
    }

    public function test_creation_etudiant_defaut_non_muqim(): void
    {
        $this->actingAs($this->user('directeur@madrasa.ma'))->post('/etudiants', [
            'nom_ar'           => 'مقبول',
            'prenom_ar'        => 'افتراضي',
            'sexe'             => 'M',
            'date_naissance'   => '2010-01-01',
            'tuteur_nom'       => 'ولي',
            'tuteur_telephone' => '0611111111',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('etudiants', [
            'nom_ar'  => 'مقبول',
            'interne' => false,
        ]);
    }

    public function test_etudiant_edit_renders(): void
    {
        $etudiant = \App\Models\Etudiant::firstOrFail();
        $this->actingAs($this->user('directeur@madrasa.ma'))
            ->get("/etudiants/{$etudiant->id}/edit")->assertOk();
    }

    public function test_tasmi_saves(): void
    {
        $user = $this->user('professeur@madrasa.ma');
        $groupe = $user->groupes()->firstOrFail();
        $etudiant = $groupe->etudiants()->firstOrFail();

        $this->actingAs($user)->post('/tasmi', [
            'groupe_id' => $groupe->id,
            'date'      => now()->toDateString(),
            'assister'  => [$etudiant->id => '1'],
            'presence'  => [$etudiant->id => 'present'],
            'lignes'    => [
                $etudiant->id => [
                    0 => [
                        'type'             => 'hifd_jadid',
                        'sourate_debut_id' => \App\Models\Sourate::first()->id,
                        'ayah_debut'       => '1',
                        'sourate_fin_id'   => \App\Models\Sourate::first()->id,
                        'ayah_fin'         => '5',
                        'note'             => 16,
                    ],
                ],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('rapports_journaliers', ['etudiant_id' => $etudiant->id]);
    }

    public function test_tasmi_lecture_requiert_les_ayahs(): void
    {
        $user      = $this->user('professeur@madrasa.ma');
        $groupe    = $user->groupes()->firstOrFail();
        $etudiant  = $groupe->etudiants()->firstOrFail();
        $sourate   = \App\Models\Sourate::firstOrFail();

        $this->actingAs($user)->post('/tasmi', [
            'groupe_id' => $groupe->id,
            'date'      => now()->toDateString(),
            'assister'  => [$etudiant->id => '1'],
            'presence'  => [$etudiant->id => 'present'],
            'lignes'    => [
                $etudiant->id => [
                    0 => [
                        'type'             => 'hifd_jadid',
                        'sourate_debut_id' => $sourate->id,
                        'ayah_debut'       => '1',
                        'sourate_fin_id'   => $sourate->id,
                    ],
                ],
            ],
        ])->assertSessionHasErrors("lignes.{$etudiant->id}.0");
    }

    public function test_tasmi_requiert_au_moins_un_etudiant(): void
    {
        $user    = $this->user('professeur@madrasa.ma');
        $groupe  = $user->groupes()->firstOrFail();
        $etudiant = $groupe->etudiants()->firstOrFail();

        $this->actingAs($user)->post('/tasmi', [
            'groupe_id' => $groupe->id,
            'date'      => now()->toDateString(),
            'presence'  => [$etudiant->id => 'present'],
        ])->assertSessionHasErrors('assister');

        $this->assertDatabaseMissing('rapports_journaliers', [
            'etudiant_id' => $etudiant->id,
            'date'        => now()->toDateString(),
        ]);
    }

    public function test_tasmi_ne_enregistre_que_les_etudiants_selectionnes(): void
    {
        $user      = $this->user('professeur@madrasa.ma');
        $groupe    = $user->groupes()->firstOrFail();
        $etudiants = $groupe->etudiants()->limit(2)->get();
        $this->assertCount(2, $etudiants);

        $date = now()->toDateString();

        $this->actingAs($user)->post('/tasmi', [
            'groupe_id' => $groupe->id,
            'date'      => $date,
            'assister'  => [$etudiants[0]->id => '1'],
            'presence'  => [
                $etudiants[0]->id => 'present',
                $etudiants[1]->id => 'absent',
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('rapports_journaliers', [
            'etudiant_id' => $etudiants[0]->id,
            'date'        => $date,
        ]);
        $this->assertDatabaseMissing('rapports_journaliers', [
            'etudiant_id' => $etudiants[1]->id,
            'date'        => $date,
        ]);
    }

    public function test_tasmi_lecture_vide_acceptee(): void
    {
        $user     = $this->user('professeur@madrasa.ma');
        $groupe   = $user->groupes()->firstOrFail();
        $etudiant = $groupe->etudiants()->firstOrFail();

        $this->actingAs($user)->post('/tasmi', [
            'groupe_id' => $groupe->id,
            'date'      => now()->toDateString(),
            'assister'  => [$etudiant->id => '1'],
            'presence'  => [$etudiant->id => 'present'],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('rapports_journaliers', [
            'etudiant_id' => $etudiant->id,
            'presence'    => 'present',
        ]);
    }

    public function test_tasmi_superviseur_peut_consulter_et_saisir_pour_toute_groupe(): void
    {
        $superviseur = $this->user('superviseur@madrasa.ma');
        $groupe      = \App\Models\Groupe::firstOrFail();
        $etudiant    = $groupe->etudiants()->firstOrFail();

        $this->actingAs($superviseur)->get('/tasmi')
            ->assertOk()
            ->assertSee($etudiant->nom_complet_ar)
            ->assertSee('تسجيل الجلسة');

        $this->actingAs($superviseur)->post('/tasmi', [
            'groupe_id' => $groupe->id,
            'date'      => now()->toDateString(),
            'assister'  => [$etudiant->id => '1'],
            'presence'  => [$etudiant->id => 'present'],
            'lignes'    => [
                $etudiant->id => [
                    0 => [
                        'type'             => 'hifd_jadid',
                        'sourate_debut_id' => \App\Models\Sourate::first()->id,
                        'ayah_debut'       => '1',
                        'sourate_fin_id'   => \App\Models\Sourate::first()->id,
                        'ayah_fin'         => '5',
                        'note'             => 15,
                    ],
                ],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('rapports_journaliers', [
            'etudiant_id'  => $etudiant->id,
            'date'         => now()->toDateString(),
            'professeur_id' => $superviseur->id,
        ]);
    }

    public function test_tasmi_professeur_ne_peut_saisir_que_pour_ses_propres_groupes(): void
    {
        $professeur      = $this->user('professeur@madrasa.ma');
        $groupeEtranger  = \App\Models\Groupe::where('professeur_id', '!=', $professeur->id)->firstOrFail();
        $etudiant        = $groupeEtranger->etudiants()->firstOrFail();

        $this->actingAs($professeur)->post('/tasmi', [
            'groupe_id' => $groupeEtranger->id,
            'date'      => now()->toDateString(),
            'assister'  => [$etudiant->id => '1'],
            'presence'  => [$etudiant->id => 'present'],
        ])->assertForbidden();
    }

    public function test_rapport_journalier_flow(): void
    {
        $user = $this->user('professeur@madrasa.ma');
        $etudiant = $user->etudiants()->firstOrFail();
        $sourate = \App\Models\Sourate::firstOrFail();

        $response = $this->actingAs($user)->post('/rapport-journaliers', [
            'etudiant_id'   => $etudiant->id,
            'date'          => now()->toDateString(),
            'presence'      => 'present',
            'lignes'        => [
                [
                    'type'             => 'hifd_jadid',
                    'sourate_debut_id' => $sourate->id,
                    'ayah_debut'       => '1',
                    'sourate_fin_id'   => $sourate->id,
                    'ayah_fin'         => '3',
                    'note'             => 15,
                ],
            ],
        ])->assertSessionHasNoErrors();

        $rapport = \App\Models\RapportJournalier::where('etudiant_id', $etudiant->id)
            ->whereDate('date', now()->toDateString())->firstOrFail();

        $this->get("/rapport-journaliers/{$rapport->id}")->assertOk();
        $this->get("/rapport-journaliers/{$rapport->id}/edit")->assertOk();
        $this->assertNotNull($rapport->note_globale);
    }

    public function test_periodique_generation(): void
    {
        $this->actingAs($this->user('directeur@madrasa.ma'))
            ->post('/rapport-periodiques/generer', [
                'type' => 'mensuel',
                'mois' => now()->format('Y-m'),
            ])->assertSessionHasNoErrors()->assertRedirect();

        $this->get('/rapport-periodiques?type=mensuel')->assertOk();

        $this->post('/rapport-periodiques/generer', [
            'type' => 'annuel',
            'mois' => now()->format('Y-m'),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->get('/rapport-periodiques?type=annuel')->assertOk();
    }

    public function test_hebergement_page(): void
    {
        $this->actingAs($this->user('garde@madrasa.ma'))
            ->get('/logements')->assertOk();
    }

    public function test_hebergement_numero_lit_equals_registration_number(): void
    {
        $garde    = $this->user('garde@madrasa.ma');
        $service  = app(InscriptionService::class);
        $etudiant = $service->preinscrire(['nom' => 'Unitaire', 'prenom' => 'Lit', 'sexe' => 'M'], $garde);

        $chambre = \App\Models\Chambre::create([
            'numero'   => 'T'.uniqid(),
            'capacite' => $etudiant->numeroInscription() + 5,
            'actif'    => true,
        ]);

        $this->actingAs($garde)
            ->post("/etudiants/{$etudiant->id}/hebergement", [
                'chambre_id'  => $chambre->id,
                'observation' => 'اختبار',
            ])->assertSessionHasNoErrors();

        $this->assertEquals($etudiant->numeroInscription(), $etudiant->hebergement->numero_lit);
        $this->assertTrue($etudiant->fresh()->interne);
    }

    public function test_place_priere_numero_equals_registration_number(): void
    {
        $garde    = $this->user('garde@madrasa.ma');
        $service  = app(InscriptionService::class);
        $etudiant = $service->preinscrire(['nom' => 'Unitaire', 'prenom' => 'Priere', 'sexe' => 'M'], $garde);
        $lieu     = \App\Models\LieuPriere::firstOrFail();

        $this->actingAs($garde)
            ->post("/etudiants/{$etudiant->id}/place-priere", [
                'lieu_priere_id' => $lieu->id,
                'rangee'         => 2,
            ])->assertSessionHasNoErrors();

        $this->assertEquals($etudiant->numeroInscription(), $etudiant->placePriere->numero_place);
    }

    public function test_note_comportement_qualitative(): void
    {
        $user      = $this->user('professeur@madrasa.ma');
        $etudiant  = $user->etudiants()->firstOrFail();
        $sourate   = \App\Models\Sourate::firstOrFail();

        $this->actingAs($user)->post('/rapport-journaliers', [
            'etudiant_id'      => $etudiant->id,
            'date'             => now()->toDateString(),
            'presence'         => 'present',
            'note_comportement' => 'excellent',
            'lignes'           => [
                ['type' => 'hifd_jadid', 'sourate_debut_id' => $sourate->id,                     'ayah_debut'       => '1',
                 'sourate_fin_id' => $sourate->id, 'ayah_fin' => '2', 'note' => 15],
            ],
        ])->assertSessionHasNoErrors()->assertRedirect();

        $rapport = \App\Models\RapportJournalier::where('etudiant_id', $etudiant->id)
            ->whereDate('date', now()->toDateString())->firstOrFail();

        $this->assertSame(\App\Enums\NiveauComportement::EXCELLENT, $rapport->note_comportement);
        $this->assertSame('سلوك ممتاز', $rapport->note_comportement->getLabel());
        $this->assertDatabaseHas('rapports_journaliers', ['id' => $rapport->id, 'note_comportement' => 'excellent']);

        $this->actingAs($user)->post('/rapport-journaliers', [
            'etudiant_id'      => $etudiant->id,
            'date'             => now()->subDay()->toDateString(),
            'presence'         => 'present',
            'note_comportement' => '20',
        ])->assertSessionHasErrors('note_comportement');
    }

    public function test_moyenne_comportement_convertit_les_niveaux(): void
    {
        $etudiant = \App\Models\Etudiant::firstOrFail();
        $groupe   = $etudiant->groupe ?: \App\Models\Groupe::firstOrFail();

        \App\Models\RapportJournalier::create([
            'etudiant_id'      => $etudiant->id,
            'professeur_id'    => $this->user('professeur@madrasa.ma')->id,
            'groupe_id'        => $groupe->id,
            'date'             => now()->toDateString(),
            'presence'         => 'present',
            'note_comportement' => 'excellent',
        ]);
        \App\Models\RapportJournalier::create([
            'etudiant_id'      => $etudiant->id,
            'professeur_id'    => $this->user('professeur@madrasa.ma')->id,
            'groupe_id'        => $groupe->id,
            'date'             => now()->subDay()->toDateString(),
            'presence'         => 'present',
            'note_comportement' => 'bon',
        ]);

        $service = app(\App\Services\RapportPeriodiqueService::class);
        $periodique = $service->generer(
            $etudiant,
            \App\Enums\TypeRapportPeriodique::MENSUEL,
            now()
        );

        $this->assertSame(\App\Enums\NiveauPeriode::EXCELLENT, $periodique->moyenne_comportement);
    }

    public function test_moyenne_generale_qualitative(): void
    {
        $this->assertSame('جيد', \App\Enums\NiveauMoyenne::depuisNote(16)?->getLabel());
        $this->assertSame('متوسط', \App\Enums\NiveauMoyenne::depuisNote(12)?->getLabel());
        $this->assertSame('ضعيف', \App\Enums\NiveauMoyenne::depuisNote(5)?->getLabel());
        $this->assertNull(\App\Enums\NiveauMoyenne::depuisNote(null));

        $user     = $this->user('professeur@madrasa.ma');
        $service  = app(InscriptionService::class);
        $etudiant = $service->preinscrire(['nom' => 'Moy', 'prenom' => 'Gen', 'sexe' => 'M'], $this->user('garde@madrasa.ma'));
        $etudiant->update(['groupe_id' => \App\Models\Groupe::firstOrFail()->id, 'statut' => 'valide', 'nom_ar' => 'متوسط', 'prenom_ar' => 'جيد']);
        $sourate = \App\Models\Sourate::firstOrFail();

        $this->actingAs($user)->post('/rapport-journaliers', [
            'etudiant_id' => $etudiant->id,
            'date'        => now()->toDateString(),
            'presence'    => 'present',
            'lignes'      => [
                ['type' => 'hifd_jadid', 'sourate_debut_id' => $sourate->id, 'ayah_debut' => '1',
                 'sourate_fin_id' => $sourate->id, 'ayah_fin' => '2', 'note' => 18],
            ],
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->user('directeur@madrasa.ma'))
            ->get('/rapport-journaliers')
            ->assertOk()
            ->assertSee('text-bg-success">جيد</span>', false)
            ->assertDontSee('text-bg-success">18</span>', false);
    }

    public function test_periodique_niveaux_qualitatifs(): void
    {
        $user     = $this->user('professeur@madrasa.ma');
        $service  = app(InscriptionService::class);
        $etudiant = $service->preinscrire(['nom' => 'Per', 'prenom' => 'Niveau', 'sexe' => 'M'], $this->user('garde@madrasa.ma'));
        $etudiant->update(['groupe_id' => \App\Models\Groupe::firstOrFail()->id, 'statut' => 'valide']);
        $sourate = \App\Models\Sourate::firstOrFail();

        $rapport = \App\Models\RapportJournalier::create([
            'etudiant_id'       => $etudiant->id,
            'professeur_id'     => $user->id,
            'groupe_id'         => $etudiant->groupe_id,
            'date'              => now()->toDateString(),
            'presence'          => 'present',
            'note_comportement' => 'excellent',
        ]);
        \App\Models\LigneRapport::create([
            'rapport_id'       => $rapport->id,
            'type'             => 'hifd_jadid',
            'sourate_debut_id' => $sourate->id,
            'ayah_debut'       => '1',
            'sourate_fin_id'   => $sourate->id,
            'ayah_fin'         => '3',
            'note'             => 18,
        ]);
        \App\Models\LigneRapport::create([
            'rapport_id'       => $rapport->id,
            'type'             => 'hifd_qadim',
            'sourate_debut_id' => $sourate->id,
            'ayah_debut'       => '4',
            'sourate_fin_id'   => $sourate->id,
            'ayah_fin'         => '6',
            'note'             => 13,
        ]);

        $periodique = app(\App\Services\RapportPeriodiqueService::class)->generer(
            $etudiant, \App\Enums\TypeRapportPeriodique::MENSUEL, now()
        );

        $this->assertSame(\App\Enums\NiveauMoyenne::BON, $periodique->moyenne_hifd);
        $this->assertSame(\App\Enums\NiveauMoyenne::MOYEN, $periodique->moyenne_murajaa);
        $this->assertSame(\App\Enums\NiveauPeriode::EXCELLENT, $periodique->moyenne_comportement);
        $this->assertSame(1, $periodique->nb_seances);
        $this->assertSame(1, $periodique->nb_presences);
        $this->assertNull($periodique->moyenne_generale);
    }

    public function test_periodique_annuel_et_sessions(): void
    {
        $user     = $this->user('professeur@madrasa.ma');
        $service  = app(InscriptionService::class);
        $etudiant = $service->preinscrire(['nom' => 'Sess', 'prenom' => 'Dates', 'sexe' => 'M'], $this->user('garde@madrasa.ma'));
        $etudiant->update(['groupe_id' => \App\Models\Groupe::firstOrFail()->id, 'statut' => 'valide']);

        $jour1 = now()->startOfYear()->addDays(2);
        $jour2 = $jour1->copy()->addDay();
        $jour3 = $jour1->copy()->addDays(2);

        \App\Models\RapportJournalier::create([
            'etudiant_id'   => $etudiant->id,
            'professeur_id' => $user->id,
            'groupe_id'     => $etudiant->groupe_id,
            'date'          => $jour1->toDateString(),
            'presence'      => 'present',
        ]);
        \App\Models\RapportJournalier::create([
            'etudiant_id'   => $etudiant->id,
            'professeur_id' => $user->id,
            'groupe_id'     => $etudiant->groupe_id,
            'date'          => $jour2->toDateString(),
            'presence'      => 'absent',
        ]);
        \App\Models\RapportJournalier::create([
            'etudiant_id'   => $etudiant->id,
            'professeur_id' => $user->id,
            'groupe_id'     => $etudiant->groupe_id,
            'date'          => $jour3->toDateString(),
            'presence'      => 'retard',
        ]);

        $periodique = app(\App\Services\RapportPeriodiqueService::class)->generer(
            $etudiant, \App\Enums\TypeRapportPeriodique::ANNUEL, now()
        );

        $this->assertSame(3, $periodique->nb_seances);
        $this->assertSame(1, $periodique->nb_presences);
        $this->assertSame(1, $periodique->nb_absences);
        $this->assertSame(1, $periodique->nb_retards);
        $this->assertSame(
            now()->startOfYear()->toDateString(),
            $periodique->date_debut->toDateString()
        );

        $this->actingAs($this->user('directeur@madrasa.ma'))
            ->get("/rapport-periodiques/{$periodique->id}")
            ->assertOk()
            ->assertSee('حاضر')
            ->assertSee('غائب')
            ->assertSee('متأخر');
    }

    public function test_rapport_journalier_recherche_simple_par_nom(): void
    {
        $directeur = $this->user('directeur@madrasa.ma');
        $groupe    = \App\Models\Groupe::firstOrFail();
        $service   = app(InscriptionService::class);
        $garde     = $this->user('garde@madrasa.ma');
        $date      = now()->toDateString();

        $etudiantA = $service->preinscrire(['nom' => 'Zerbone', 'prenom' => 'Ahmed', 'sexe' => 'M'], $garde);
        $etudiantA->update(['groupe_id' => $groupe->id, 'statut' => 'valide', 'nom_ar' => 'زربون', 'prenom_ar' => 'أحمد']);
        $etudiantB = $service->preinscrire(['nom' => 'Zerbone', 'prenom' => 'Omar', 'sexe' => 'M'], $garde);
        $etudiantB->update(['groupe_id' => $groupe->id, 'statut' => 'valide', 'nom_ar' => 'زربون', 'prenom_ar' => 'عمر']);

        \App\Models\RapportJournalier::create([
            'etudiant_id'   => $etudiantA->id,
            'professeur_id' => $this->user('professeur@madrasa.ma')->id,
            'groupe_id'     => $groupe->id,
            'date'          => $date,
            'presence'      => 'present',
        ]);
        \App\Models\RapportJournalier::create([
            'etudiant_id'   => $etudiantB->id,
            'professeur_id' => $this->user('professeur@madrasa.ma')->id,
            'groupe_id'     => $groupe->id,
            'date'          => $date,
            'presence'      => 'absent',
        ]);

        $this->actingAs($directeur)->get('/rapport-journaliers?q=أحمد')
            ->assertOk()->assertSee('أحمد زربون')->assertDontSee('عمر زربون');

        $this->actingAs($directeur)->get('/rapport-journaliers?q=عمر')
            ->assertOk()->assertSee('عمر زربون')->assertDontSee('أحمد زربون');
    }
}
