<?php

namespace Database\Seeders;

use App\Enums\NiveauComportement;
use App\Enums\TypeComportement;
use App\Enums\TypeRapportPeriodique;
use App\Enums\TypeSeance;
use App\Models\Chambre;
use App\Models\Comportement;
use App\Models\Etudiant;
use App\Models\Groupe;
use App\Models\Hebergement;
use App\Models\LieuPriere;
use App\Models\LigneRapport;
use App\Models\PlacePriere;
use App\Models\RapportJournalier;
use App\Models\RapportPeriodique;
use App\Models\Sourate;
use App\Models\TacheMemorisation;
use App\Models\User;
use App\Services\InscriptionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Données de démonstration : groupes, chambres, lieux de prière,
 * étudiants dans tous les états du workflow, évaluations, affectations,
 * hébergements, places de prière, tâches, rapports et comportements.
 */
class DonneesDemoSeeder extends Seeder
{
    private array $docs = [
        'extrait_naissance'    => 'documents/naissances/extrait-demo.pdf',
        'attestation_scolaire' => 'documents/attestations/attest-demo.pdf',
        'autre_document'       => 'documents/autres/autre-demo.pdf',
        'photo'                => 'etudiants/avatar-demo.png',
    ];

    public function run(): void
    {
        if (Etudiant::exists()) {
            $this->command?->warn('Données de démonstration déjà présentes — annulation.');

            return;
        }

        DB::transaction(function () {
            $service = app(InscriptionService::class);
            $garde      = User::where('email', 'garde@madrasa.ma')->firstOrFail();
            $superviseur = User::where('email', 'superviseur@madrasa.ma')->firstOrFail();
            $prof = [
                1 => User::where('email', 'professeur@madrasa.ma')->firstOrFail(),
                2 => User::where('email', 'professeur2@madrasa.ma')->firstOrFail(),
                3 => User::where('email', 'professeur3@madrasa.ma')->firstOrFail(),
            ];
            $profsDeGroupe = [1 => $prof[1], 2 => $prof[2], 3 => $prof[3], 4 => $prof[1]];

            $s = fn (int $n) => Sourate::where('numero', $n)->firstOrFail();

            // ---- الحلقات ----
            $groupes = [
                1 => Groupe::create(['nom' => 'H1', 'nom_ar' => 'حلقة المبتدئين',   'professeur_id' => $prof[1]->id, 'niveau' => 'مبتدئ', 'salle' => 'القاعة 1', 'horaire_debut' => '08:30', 'horaire_fin' => '10:00', 'capacite' => 15, 'annee_scolaire' => '2025-2026', 'actif' => true]),
                2 => Groupe::create(['nom' => 'H2', 'nom_ar' => 'حلقة المتوسطين',  'professeur_id' => $prof[2]->id, 'niveau' => 'متوسط', 'salle' => 'القاعة 2', 'horaire_debut' => '10:00', 'horaire_fin' => '11:30', 'capacite' => 12, 'annee_scolaire' => '2025-2026', 'actif' => true]),
                3 => Groupe::create(['nom' => 'H3', 'nom_ar' => 'حلقة المتقدمين',  'professeur_id' => $prof[3]->id, 'niveau' => 'متقدم', 'salle' => 'المسجد',    'horaire_debut' => '16:00', 'horaire_fin' => '18:00', 'capacite' => 10, 'annee_scolaire' => '2025-2026', 'actif' => true]),
                4 => Groupe::create(['nom' => 'H4', 'nom_ar' => 'حلقة النسائية',   'professeur_id' => $prof[1]->id, 'niveau' => 'مبتدئ', 'salle' => 'القاعة 3', 'horaire_debut' => '14:00', 'horaire_fin' => '15:30', 'capacite' => 10, 'annee_scolaire' => '2025-2026', 'actif' => true]),
            ];

            // ---- الغرف ----
            $chambres = [
                1 => Chambre::create(['numero' => '1', 'batiment' => 'A', 'etage' => 'الطابق الأول',  'capacite' => 20, 'responsable_id' => $prof[1]->id, 'actif' => true]),
                2 => Chambre::create(['numero' => '2', 'batiment' => 'A', 'etage' => 'الطابق الأول',  'capacite' => 20, 'responsable_id' => $prof[2]->id, 'actif' => true]),
                3 => Chambre::create(['numero' => '3', 'batiment' => 'B', 'etage' => 'الطابق الثاني', 'capacite' => 20, 'responsable_id' => $prof[3]->id, 'actif' => true]),
                4 => Chambre::create(['numero' => '4', 'batiment' => 'B', 'etage' => 'الطابق الثاني', 'capacite' => 20, 'responsable_id' => $prof[1]->id, 'actif' => true]),
            ];

            // ---- أماكن الصلاة ----
            $mosquee = LieuPriere::create(['nom' => 'Mosquee principale', 'nom_ar' => 'المسجد الكبير', 'description' => 'مصلى الرجال', 'nb_rangees' => 10, 'places_par_rangee' => 20, 'actif' => true]);
            LieuPriere::create(['nom' => 'Salle de priere', 'nom_ar' => 'قاعة الصلاة', 'description' => 'مصلى النساء', 'nb_rangees' => 5, 'places_par_rangee' => 20, 'actif' => true]);

            // ============================================================
            // 1) Pré-inscriptions (en attente de test) — matricules 1 à 4
            // ============================================================
            $preinscrits = [];
            foreach ([
                ['Benjelloun', 'Ahmed',  'بن جلون',   'أحمد', 'M', '2011-03-14', 'فاس',    '0661111111', 'محمد بن جلون',  'père',  '0661111112', 'السنة الخامسة', 0],
                ['Tazi',       'Omar',   'الطازي',    'عمر',  'M', '2010-07-22', 'مكناس',  '0662222222', 'عبد الله الطازي', 'père', '0662222223', 'السنة السادسة', 0],
                ['Alaoui',     'Yasmine','العلوي',    'ياسمين','F', '2012-01-05', 'الرباط', '0663333333', 'أحمد العلوي',  'père',  '0663333334', 'السنة الرابعة', 0.5],
                ['Berrada',    'Hicham', 'برادة',     'هشام', 'M', '2009-11-30', 'طنجة',   '0664444444', 'عبد اللطيف برادة', 'père', '0664444445', 'السنة التاسعة', 1],
            ] as [$nom, $prenom, $nomAr, $prenomAr, $sexe, $naissance, $ville, $tel, $tuteur, $lien, $telTuteur, $niveau, $hizb]) {
                $preinscrits[] = $this->preinscrire($service, $garde, $nom, $prenom, $nomAr, $prenomAr, $sexe, $naissance, $ville, $tel, $tuteur, $lien, $telTuteur, $niveau, $hizb);
            }

            // ============================================================
            // 2) Étudiants validés et affectés — matricules 5 à 16
            // ============================================================
            $programmes = [
                1 => ['hifd' => $s(114), 'debutH' => 1, 'finH' => 6,  'mura' => $s(112), 'debutM' => 1, 'finM' => 4, 'pagesH' => 0.5, 'pagesM' => 0.5, 'note' => 15, 'notes' => [12, 13, 12], 'hizb' => 0.5, 'niveau' => 'مبتدئ'],
                2 => ['hifd' => $s(81),  'debutH' => 1, 'finH' => 29, 'mura' => $s(89), 'debutM' => 1, 'finM' => 30, 'pagesH' => 2,   'pagesM' => 1,   'note' => 16, 'notes' => [16, 15, 16], 'hizb' => 4,    'niveau' => 'متوسط'],
                3 => ['hifd' => $s(36),  'debutH' => 1, 'finH' => 83, 'mura' => $s(67), 'debutM' => 1, 'finM' => 30, 'pagesH' => 5,   'pagesM' => 2,   'note' => 17, 'notes' => [18, 17, 18], 'hizb' => 15,   'niveau' => 'متقدم'],
                4 => ['hifd' => $s(110), 'debutH' => 1, 'finH' => 3,  'mura' => $s(109), 'debutM' => 1, 'finM' => 6, 'pagesH' => 0.25, 'pagesM' => 0.5, 'note' => 14, 'notes' => [13, 14, 13], 'hizb' => 1,    'niveau' => 'مبتدئ'],
            ];

            $valides = [
                // [groupe, nom, prenom, nomAr, prenomAr, sexe, naissance, ville, tel, interne, chambre]
                [1, 'El Amrani',  'Mohammed', 'العمراني', 'محمد',   'M', '2011-05-10', 'فاس',  '0665551111', true,  1],
                [1, 'Ouazzani',   'Youssef',  'الوزاني',  'يوسف',   'M', '2010-09-18', 'صفرو', '0665552222', false, null],
                [1, 'Chraibi',    'Ibrahim',  'الشرايبي', 'إبراهيم','M', '2011-12-02', 'فاس',  '0665553333', true,  1],
                [2, 'Fassi',      'Amine',    'الفاسي',   'أمين',   'M', '2008-04-25', 'فاس',  '0665554444', true,  2],
                [2, 'Bennani',    'Karim',    'بناني',    'كريم',   'M', '2009-02-11', 'مكناس', '0665555555', false, null],
                [2, 'Khayat',     'Salim',    'خياط',     'سليم',   'M', '2008-08-30', 'تازة', '0665556666', true,  2],
                [3, 'Drissi',     'Hassan',   'الدريسي',  'حسن',    'M', '2006-06-15', 'فاس',  '0665557777', true,  3],
                [3, 'Sbihi',      'Rachid',   'السباعي',  'رشيد',   'M', '2007-01-20', 'الرباط', '0665558888', false, null],
                [3, 'Lahlou',     'Mehdi',    'الهلوي',   'مهدي',   'M', '2006-10-05', 'وجدة', '0665559999', true,  3],
                [4, 'Idrissi',    'Khadija',  'الإدريسي', 'خديجة',  'F', '2012-03-08', 'فاس',  '0665560001', false, null],
                [4, 'Berrada',    'Fatima Zahra', 'برادة', 'فاطمة الزهراء', 'F', '2011-07-19', 'طنجة', '0665560002', false, null],
                [4, 'Mernissi',   'Aya',      'المرنيسي', 'آية',    'F', '2012-11-27', 'فاس',  '0665560003', true,  4],
            ];

            $compteur = 0;
            foreach ($valides as [$g, $nom, $prenom, $nomAr, $prenomAr, $sexe, $naissance, $ville, $tel, $interne, $ch]) {
                $prog = $programmes[$g];
                $e = $this->preinscrire($service, $garde, $nom, $prenom, $nomAr, $prenomAr, $sexe, $naissance, $ville, $tel, 'ولي أمر ' . $nom, 'père', '0660000000', 'السنة الدراسية', $prog['hizb']);

                $this->evaluer($service, $e, $superviseur, $prog['notes'][0], $prog['notes'][1], $prog['notes'][2], $prog['hizb'], $prog['niveau']);
                $this->affecter($service, $e, $groupes[$g], $garde);

                $this->placePriere($e, $mosquee, $garde, 1);

                if ($interne && $ch) {
                    $this->hebergement($e, $chambres[$ch], $garde);
                }

                $this->taches($e, $profsDeGroupe[$g], $prog);
                $this->journeesDeHifd($e, $groupes[$g], $profsDeGroupe[$g], $prog);
                $this->rapportMensuel($e, $groupes[$g], $superviseur);

                $compteur++;
                if ($compteur % 3 === 0) {
                    $this->comportement($e, $profsDeGroupe[$g], TypeComportement::POSITIF, 'الانضباط', 2, 'انضباط جيد في الحلقة');
                }
                if ($compteur % 4 === 0) {
                    $this->comportement($e, $profsDeGroupe[$g], TypeComportement::NEGATIF, 'التأخير', -1, 'تأخر عن موعد الحصة');
                }
            }

            // ============================================================
            // 3) Étudiants refusés — matricules 17 à 18
            // ============================================================
            foreach ([
                ['Qadiri',   'Nabil',  'القادري',  'نبيل', 'M', '2010-03-02', 'سلا',  '0667771111'],
                ['Amrani',   'Yassine','العمري',   'ياسين','M', '2009-05-13', 'القنيطرة', '0667772222'],
            ] as [$nom, $prenom, $nomAr, $prenomAr, $sexe, $naissance, $ville, $tel]) {
                $e = $this->preinscrire($service, $garde, $nom, $prenom, $nomAr, $prenomAr, $sexe, $naissance, $ville, $tel, 'ولي أمر ' . $nom, 'père', '0660000000', 'السنة الدراسية', 0);
                $service->evaluer($e, [
                    'decision'      => 'refuse',
                    'date_test'     => now()->subDays(3)->toDateString(),
                    'hizb_maitrise' => 0,
                    'note_hifd'     => 5,
                    'note_tajwid'   => 6,
                    'note_lecture'  => 3,
                    'motif'         => 'لم يجتز اختبار القراءة',
                    'observations'  => 'قراءة ضعيفة، يحتاج سنة تحضيرية قبل الالتحاق بالحلقة.',
                ], $superviseur);
            }

            // ============================================================
            // 4) Étudiants ajournés — matricules 19 à 20
            // ============================================================
            foreach ([
                ['Bouazza', 'Said',   'بوعزة', 'سعيد', 'M', '2010-06-27', 'فاس', '0668881111'],
                ['Chami',   'Walid',  'الشامي','وليد', 'M', '2009-12-09', 'المحمدية', '0668882222'],
            ] as [$nom, $prenom, $nomAr, $prenomAr, $sexe, $naissance, $ville, $tel]) {
                $e = $this->preinscrire($service, $garde, $nom, $prenom, $nomAr, $prenomAr, $sexe, $naissance, $ville, $tel, 'ولي أمر ' . $nom, 'père', '0660000000', 'السنة الدراسية', 0.5);
                $service->evaluer($e, [
                    'decision'      => 'ajourne',
                    'date_test'     => now()->subDays(2)->toDateString(),
                    'hizb_maitrise' => 0.5,
                    'note_hifd'     => 8,
                    'note_tajwid'   => 9,
                    'note_lecture'  => 7,
                    'motif'         => 'يحتاج مزيدا من الاستعداد قبل الاختبار المقبل',
                    'observations'  => 'نتائج متوسطة، يُعاد الاختبار بعد شهر.',
                ], $superviseur);
            }
        });
    }

    /* ---------------- Helpers ---------------- */

    private function preinscrire(
        InscriptionService $service, User $garde,
        string $nom, string $prenom, string $nomAr, string $prenomAr, string $sexe,
        string $naissance, string $ville, string $tel, string $tuteur, string $lien,
        string $telTuteur, string $niveau, float $hizb
    ): Etudiant {
        return $service->preinscrire([
            'nom' => $nom, 'prenom' => $prenom, 'nom_ar' => $nomAr, 'prenom_ar' => $prenomAr,
            'sexe' => $sexe, 'date_naissance' => $naissance, 'ville' => $ville,
            'telephone' => $tel, 'tuteur_nom' => $tuteur, 'tuteur_lien' => $lien,
            'tuteur_telephone' => $telTuteur, 'niveau_scolaire' => $niveau,
            'hifd_initial_hizb' => $hizb,
        ] + $this->docs, $garde);
    }

    private function evaluer(InscriptionService $service, Etudiant $e, User $sup, float $hifd, float $tajwid, float $lecture, float $hizb, string $niveau): void
    {
        $service->evaluer($e, [
            'decision'        => 'valide',
            'date_test'       => now()->subDays(25)->toDateString(),
            'hizb_maitrise'   => $hizb,
            'note_hifd'       => $hifd,
            'note_tajwid'     => $tajwid,
            'note_lecture'    => $lecture,
            'niveau_propose'  => $niveau,
            'observations'    => 'نتيجة جيدة، الطالب أهل للالتحاق بالحلقة.',
        ], $sup);
    }

    private function affecter(InscriptionService $service, Etudiant $e, Groupe $groupe, User $garde): void
    {
        $service->affecter($e, $groupe, $garde, 'توزيع بداية السنة الدراسية');
    }

    private function hebergement(Etudiant $e, Chambre $chambre, User $garde): void
    {
        $e->update(['interne' => true]);
        Hebergement::create([
            'etudiant_id'  => $e->id,
            'chambre_id'   => $chambre->id,
            'numero_lit'   => $e->numeroInscription(),
            'date_debut'   => now()->subMonths(2)->toDateString(),
            'attribue_par' => $garde->id,
            'actif'        => true,
        ]);
    }

    private function placePriere(Etudiant $e, LieuPriere $lieu, User $garde, int $rangee): void
    {
        PlacePriere::create([
            'etudiant_id'    => $e->id,
            'lieu_priere_id' => $lieu->id,
            'rangee'         => $rangee,
            'numero_place'   => $e->numeroInscription(),
            'date_debut'     => now()->subMonths(2)->toDateString(),
            'attribue_par'   => $garde->id,
            'actif'          => true,
        ]);
    }

    private function taches(Etudiant $e, User $prof, array $p): void
    {
        TacheMemorisation::create([
            'etudiant_id'     => $e->id,
            'professeur_id'   => $prof->id,
            'type'            => TypeSeance::HIFD_JADID,
            'sourate_debut_id' => $p['hifd']->id,
            'ayah_debut'      => $p['debutH'],
            'sourate_fin_id'  => $p['hifd']->id,
            'ayah_fin'        => $p['finH'],
            'nb_pages'        => $p['pagesH'],
            'date_assignation' => now()->subDays(3)->toDateString(),
            'date_echeance'   => now()->addDays(4)->toDateString(),
            'consignes'       => 'حفظ مع ترتيل وتصحيح التجويد',
            'statut'          => 'assignee',
        ]);

        TacheMemorisation::create([
            'etudiant_id'     => $e->id,
            'professeur_id'   => $prof->id,
            'type'            => TypeSeance::HIFD_QADIM,
            'sourate_debut_id' => $p['mura']->id,
            'ayah_debut'      => $p['debutM'],
            'sourate_fin_id'  => $p['mura']->id,
            'ayah_fin'        => $p['finM'],
            'nb_pages'        => $p['pagesM'],
            'date_assignation' => now()->subDays(5)->toDateString(),
            'date_echeance'   => now()->addDays(2)->toDateString(),
            'consignes'       => 'مراجعة الحفظ القديم',
            'statut'          => 'realisee',
        ]);
    }

    private function journeesDeHifd(Etudiant $e, Groupe $groupe, User $prof, array $p, int $jours = 5): void
    {
        $etendue = $p['finH'] - $p['debutH'] + 1;
        $pas     = max(1, intdiv($etendue, $jours));
        $presences = ['present', 'present', 'retard', 'present', 'excuse'];

        for ($i = 0; $i < $jours; $i++) {
            $a1 = $p['debutH'] + ($i * $pas) % $etendue;
            $a2 = min($p['finH'], $a1 + $pas - 1);

            $rapport = RapportJournalier::create([
                'etudiant_id'      => $e->id,
                'professeur_id'    => $prof->id,
                'groupe_id'        => $groupe->id,
                'date'             => now()->subDays($i + 1)->toDateString(),
                'presence'         => $presences[$i],
                'note_comportement' => NiveauComportement::BON->value,
                'remarques'        => null,
                'verrouille'       => true,
            ]);

            LigneRapport::create([
                'rapport_id'       => $rapport->id,
                'type'             => TypeSeance::HIFD_JADID,
                'sourate_debut_id' => $p['hifd']->id,
                'ayah_debut'       => $a1,
                'sourate_fin_id'   => $p['hifd']->id,
                'ayah_fin'         => $a2,
                'nb_pages'         => $p['pagesH'],
                'note'             => $p['note'],
                'nb_erreurs'       => rand(0, 2),
                'nb_hesitations'   => rand(1, 3),
                'observation'      => null,
            ]);

            if ($i % 2 === 0) {
                LigneRapport::create([
                    'rapport_id'       => $rapport->id,
                    'type'             => TypeSeance::HIFD_QADIM,
                    'sourate_debut_id' => $p['mura']->id,
                    'ayah_debut'       => $p['debutM'],
                    'sourate_fin_id'   => $p['mura']->id,
                    'ayah_fin'         => $p['finM'],
                    'nb_pages'         => $p['pagesM'],
                    'note'             => $p['note'] + 1,
                    'nb_erreurs'       => 0,
                    'nb_hesitations'   => 1,
                    'observation'      => null,
                ]);
            }
        }
    }

    private function rapportMensuel(Etudiant $e, Groupe $groupe, User $generePar): void
    {
        RapportPeriodique::create([
            'etudiant_id'         => $e->id,
            'groupe_id'           => $groupe->id,
            'type'                => TypeRapportPeriodique::MENSUEL,
            'date_debut'          => now()->startOfMonth()->toDateString(),
            'date_fin'            => now()->toDateString(),
            'nb_seances'          => 20,
            'nb_presences'        => 18,
            'nb_absences'         => 1,
            'nb_retards'          => 1,
            'total_pages_hifd'    => 8.5,
            'total_pages_murajaa' => 12.0,
            'moyenne_hifd'        => 'bon',
            'moyenne_murajaa'     => 'bon',
            'moyenne_comportement'=> 'moyen',
            'appreciation'        => 'تقدم ملحوظ، يستمر بجد',
            'genere_par'          => $generePar->id,
        ]);
    }

    private function comportement(Etudiant $e, User $par, TypeComportement $type, string $categorie, int $points, string $description): void
    {
        Comportement::create([
            'etudiant_id' => $e->id,
            'signale_par' => $par->id,
            'date'        => now()->subDays(rand(1, 20))->toDateString(),
            'type'        => $type,
            'categorie'   => $categorie,
            'gravite'     => $type === TypeComportement::NEGATIF ? 2 : 1,
            'points'      => $points,
            'description' => $description,
        ]);
    }
}
