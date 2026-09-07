<?php

namespace Tests\Feature;

use App\Enums\StatutInscription;
use App\Models\Etudiant;
use App\Models\Groupe;
use App\Models\User;
use App\Services\InscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_workflow_preinscription_validation_affectation(): void
    {
        $garde      = User::where('email', 'garde@madrasa.ma')->firstOrFail();
        $superviseur = User::where('email', 'superviseur@madrasa.ma')->firstOrFail();
        $professeur = User::where('email', 'professeur@madrasa.ma')->firstOrFail();
        $service    = app(InscriptionService::class);

        $groupe = Groupe::create([
            'nom' => 'G1', 'nom_ar' => 'الحلقة 1',
            'professeur_id' => $professeur->id, 'capacite' => 10,
        ]);

        // Étape 1 : pré-inscription par le garde.
        $etudiant = $service->preinscrire([
            'nom' => 'Alami', 'prenom' => 'Mohammed',
            'nom_ar' => 'العلوي', 'prenom_ar' => 'محمد',
            'sexe' => 'M', 'telephone' => '0612345678',
        ], $garde);

        $this->assertSame(StatutInscription::PREINSCRIT, $etudiant->statut);
        $this->assertSame($garde->id, $etudiant->preinscrit_par);
        $this->assertNotNull($etudiant->matricule);

        // Affectation avant validation → refusée.
        $this->expectException(ValidationException::class);
        try {
            $service->affecter($etudiant, $groupe, $garde);
        } catch (ValidationException $e) {
            $this->assertStringContainsString('validée', $e->getMessage());
            throw $e;
        }
    }

    public function test_affectation_impossible_avant_validation(): void
    {
        $garde       = User::where('email', 'garde@madrasa.ma')->firstOrFail();
        $professeur  = User::where('email', 'professeur@madrasa.ma')->firstOrFail();
        $service     = app(InscriptionService::class);

        $groupe = Groupe::create([
            'nom' => 'G2', 'professeur_id' => $professeur->id, 'capacite' => 5,
        ]);

        $etudiant = $service->preinscrire([
            'nom' => 'Test', 'prenom' => 'A', 'sexe' => 'M',
        ], $garde);

        try {
            $service->affecter($etudiant, $groupe, $garde);
            $this->fail('Affectation devrait être refusée avant validation.');
        } catch (ValidationException $e) {
            $this->assertSame(StatutInscription::PREINSCRIT, $etudiant->fresh()->statut);
            $this->assertNull($etudiant->fresh()->groupe_id);
        }
    }

    public function test_workflow_complet_apres_validation(): void
    {
        $garde       = User::where('email', 'garde@madrasa.ma')->firstOrFail();
        $superviseur = User::where('email', 'superviseur@madrasa.ma')->firstOrFail();
        $professeur  = User::where('email', 'professeur@madrasa.ma')->firstOrFail();
        $service     = app(InscriptionService::class);

        $groupe = Groupe::create([
            'nom' => 'G3', 'professeur_id' => $professeur->id, 'capacite' => 5,
        ]);

        $etudiant = $service->preinscrire([
            'nom' => 'Benali', 'prenom' => 'Youssef', 'sexe' => 'M',
        ], $garde);

        $evaluation = $service->evaluer($etudiant, [
            'decision'   => 'valide',
            'note_hifd'  => 15,
            'note_tajwid' => 14,
            'note_lecture' => 16,
        ], $superviseur);

        $this->assertSame(StatutInscription::VALIDE, $etudiant->fresh()->statut);
        $this->assertSame($superviseur->id, $etudiant->fresh()->valide_par);
        $this->assertNotNull($evaluation->id);

        $affectation = $service->affecter($etudiant, $groupe, $garde);

        $this->assertSame($groupe->id, $etudiant->fresh()->groupe_id);
        $this->assertTrue($affectation->actif);
    }

    public function test_preinscription_enregistre_documents(): void
    {
        $garde   = User::where('email', 'garde@madrasa.ma')->firstOrFail();
        $service = app(InscriptionService::class);

        $etudiant = $service->preinscrire([
            'nom'                 => 'Fatimi',
            'prenom'              => 'Amine',
            'nom_ar'              => 'الفاطمي',
            'prenom_ar'           => 'أمين',
            'sexe'                => 'M',
            'extrait_naissance'   => 'documents/naissances/extrait-1.pdf',
            'attestation_scolaire' => 'documents/attestations/attest-1.pdf',
            'photo'               => 'etudiants/photo-1.jpg',
        ], $garde);

        $this->assertSame(StatutInscription::PREINSCRIT, $etudiant->statut);
        $this->assertNotNull($etudiant->matricule);
        $this->assertGreaterThan(0, $etudiant->numeroInscription());
        $this->assertSame('documents/naissances/extrait-1.pdf', $etudiant->extrait_naissance);
        $this->assertSame('documents/attestations/attest-1.pdf', $etudiant->attestation_scolaire);
        $this->assertCount(3, $etudiant->documents());
    }

    public function test_numero_inscription_unique_et_croissant(): void
    {
        $garde   = User::where('email', 'garde@madrasa.ma')->firstOrFail();
        $service = app(InscriptionService::class);

        $e1 = $service->preinscrire(['nom' => 'A', 'prenom' => 'A', 'sexe' => 'M'], $garde);
        $e2 = $service->preinscrire(['nom' => 'B', 'prenom' => 'B', 'sexe' => 'M'], $garde);

        $this->assertNotEquals($e1->matricule, $e2->matricule);
        $this->assertLessThan($e2->numeroInscription(), $e1->numeroInscription());
        $this->assertNotEquals($e1->numeroInscription(), $e2->numeroInscription());
    }

    public function test_refus_avec_observation_bloque_affectation(): void
    {
        $garde       = User::where('email', 'garde@madrasa.ma')->firstOrFail();
        $superviseur = User::where('email', 'superviseur@madrasa.ma')->firstOrFail();
        $professeur  = User::where('email', 'professeur@madrasa.ma')->firstOrFail();
        $service     = app(InscriptionService::class);

        $groupe = Groupe::create([
            'nom' => 'G5', 'professeur_id' => $professeur->id, 'capacite' => 5,
        ]);

        $etudiant = $service->preinscrire([
            'nom' => 'Rifi', 'prenom' => 'Hassan', 'sexe' => 'M',
        ], $garde);

        $evaluation = $service->evaluer($etudiant, [
            'decision'    => 'refuse',
            'motif'       => 'لم يجتز اختبار القراءة',
            'observations' => 'الطالب لا يقرأ بشكل سليم، يحتاج سنة تحضيرية',
            'note_hifd'   => 5,
            'note_tajwid' => 6,
            'note_lecture' => 3,
        ], $superviseur);

        // La décision et l'observation sont enregistrées.
        $this->assertSame('refuse', $evaluation->decision);
        $this->assertSame('لم يجتز اختبار القراءة', $evaluation->motif);
        $this->assertSame('الطالب لا يقرأ بشكل سليم، يحتاج سنة تحضيرية', $evaluation->observations);

        // L'étudiant est bien « مرفوض » et le motif est conservé.
        $fresh = $etudiant->fresh();
        $this->assertSame(StatutInscription::REFUSE, $fresh->statut);
        $this->assertSame('لم يجتز اختبار القراءة', $fresh->motif_refus);

        // Un étudiant refusé ne peut jamais être affecté à une solution.
        try {
            $service->affecter($fresh, $groupe, $garde);
            $this->fail('L\'affectation d\'un étudiant refusé doit être impossible.');
        } catch (ValidationException $e) {
            $this->assertNull($fresh->fresh()->groupe_id);
        }
    }
}
