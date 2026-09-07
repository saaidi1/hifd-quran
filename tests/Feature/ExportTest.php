<?php

namespace Tests\Feature;

use App\Models\RapportJournalier;
use App\Models\RapportPeriodique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function user(string $email): \App\Models\User
    {
        return \App\Models\User::where('email', $email)->firstOrFail();
    }

    /** Le rapport journalier se télécharge en PDF avec un en-tête d'attachement. */
    public function test_pdf_rapport_journalier(): void
    {
        $rapport = RapportJournalier::firstOrFail();

        $response = $this->actingAs($this->user('directeur@madrasa.ma'))
            ->get("/rapport-journaliers/{$rapport->id}/pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
    }

    /** Le rapport périodique se télécharge en PDF. */
    public function test_pdf_rapport_periodique(): void
    {
        $this->actingAs($this->user('directeur@madrasa.ma'))
            ->post('/rapport-periodiques/generer', [
                'type' => 'mensuel',
                'mois' => now()->format('Y-m'),
            ])->assertSessionHasNoErrors();

        $rapport = RapportPeriodique::firstOrFail();

        $response = $this->actingAs($this->user('directeur@madrasa.ma'))
            ->get("/rapport-periodiques/{$rapport->id}/pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition'));
    }

    /** La liste des rapports journaliers s'exporte en Excel (filtres conservés). */
    public function test_excel_rapports_journaliers(): void
    {
        $response = $this->actingAs($this->user('directeur@madrasa.ma'))
            ->get('/rapport-journaliers/exporter');

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type')
        );
    }

    /** La liste des rapports périodiques s'exporte en Excel. */
    public function test_excel_rapports_periodiques(): void
    {
        $response = $this->actingAs($this->user('directeur@madrasa.ma'))
            ->get('/rapport-periodiques/exporter');

        $response->assertOk();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $response->headers->get('content-type')
        );
    }

    /** Un professeur peut télécharger le PDF d'un rapport de son périmètre. */
    public function test_pdf_rapport_journalier_professeur_perimetre(): void
    {
        $professeur = $this->user('professeur@madrasa.ma');
        $groupeId   = $professeur->groupes()->firstOrFail()->id;
        $rapport    = RapportJournalier::where('groupe_id', $groupeId)->firstOrFail();

        $this->actingAs($professeur)
            ->get("/rapport-journaliers/{$rapport->id}/pdf")
            ->assertOk();
    }
}