<?php

namespace App\Filament\Pages;

use App\Enums\StatutPresence;
use App\Enums\TypeSeance;
use App\Models\Etudiant;
use App\Models\Groupe;
use App\Models\RapportJournalier;
use App\Models\Sourate;
use App\Models\TacheMemorisation;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

/**
 * حصة : saisie de tous les étudiants d'une مجموعة en une seule page.
 *
 * La portion est pré-remplie depuis le واجب مقرر assigné la veille :
 * le professeur n'a plus qu'à saisir la note et les erreurs.
 */
class TasmiHalaqa extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-microphone';
    protected static ?string $navigationGroup = 'الحفظ والمتابعة';
    protected static ?string $navigationLabel = 'حصة';
    protected static ?string $title           = 'حصة';
    protected static ?int    $navigationSort  = 0;

    protected static string $view = 'filament.pages.tasmi-halaqa';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()->estProfesseur() || auth()->user()->estSuperviseur();
    }

    /** Groupes saisissables : les siens pour le professeur, toutes pour le superviseur. */
    private function groupesAutorises()
    {
        return auth()->user()->estProfesseur()
            ? auth()->user()->groupes()->where('actif', true)
            : Groupe::query()->where('actif', true);
    }

    /* Pré-remplissage : premier groupe autorisé, date du jour, séance 1. */
    public function mount(): void
    {
        $premiereHalaqa = $this->groupesAutorises()->first();

        $this->form->fill([
            'groupe_id' => $premiereHalaqa?->id,
            'date'      => today()->toDateString(),
            'seance'    => 1,
        ]);

        $this->chargerEtudiants();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()->columns(4)->schema([
                    Forms\Components\Select::make('groupe_id')
                        ->label('المجموعة')
                        ->options(fn () => $this->groupesAutorises()->pluck('nom_ar', 'id'))
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn () => $this->chargerEtudiants()),

                    Forms\Components\DatePicker::make('date')
                        ->label('التاريخ')
                        ->default(today())
                        ->maxDate(today())
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn () => $this->chargerEtudiants()),

                    Forms\Components\Select::make('seance')
                        ->label('الجلسة')
                        /* Séances déjà saisies pour ce groupe/date + une entrée « max + 1 — جديدة » pour la nouvelle séance. */
                        ->options(function (Forms\Get $get) {
                            $groupeId = $get('groupe_id');
                            $date     = $get('date') ?: today()->toDateString();

                            if (! $groupeId) {
                                return [1 => 'الجلسة 1'];
                            }

                            $existantes = RapportJournalier::where('groupe_id', $groupeId)
                                ->whereDate('date', $date)
                                ->distinct()
                                ->orderBy('seance')
                                ->pluck('seance');

                            $options = $existantes
                                ->mapWithKeys(fn ($n) => [$n => "الجلسة {$n}"])
                                ->all();

                            $suivante = (int) $existantes->max() + 1;
                            /* Nouvelle séance numérotée max + 1 (ex. « الجلسة 2 — جديدة »). */
                            $options[$suivante] = "الجلسة {$suivante} — جديدة";

                            return $options;
                        })
                        ->default(1)
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn () => $this->chargerEtudiants()),

                    /* Compteur « X / Y élèves récités » mis à jour en direct. */
                    Forms\Components\Placeholder::make('resume')
                        ->label('الإنجاز')
                        ->content(fn () => $this->resume()),
                ]),

                Forms\Components\Repeater::make('lignes')
                    ->label('')
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->collapsible()
                    ->itemLabel(fn (array $state): string => $this->libelleEtudiant($state))
                    ->schema([
                        Forms\Components\Hidden::make('etudiant_id'),
                        Forms\Components\Hidden::make('nom'),
                        Forms\Components\Hidden::make('tache_id'),

                        Forms\Components\Grid::make(4)->schema([
                            Forms\Components\Select::make('presence')
                                ->label('الحضور')
                                ->options(StatutPresence::class)
                                ->default(StatutPresence::PRESENT->value)
                                ->required()
                                ->live(),

                            Forms\Components\Select::make('type')
                                ->label('نوع التسميع')
                                ->options(TypeSeance::class)
                                ->default(TypeSeance::HIFD_JADID->value)
                                ->visible(fn (Forms\Get $get) => $this->estPresent($get)),

                            Forms\Components\TextInput::make('note')
                                ->label('النقطة / 20')
                                ->numeric()->minValue(0)->maxValue(20)
                                ->required(fn (Forms\Get $get) => $this->estPresent($get))
                                ->visible(fn (Forms\Get $get) => $this->estPresent($get)),

                            Forms\Components\TextInput::make('nb_erreurs')
                                ->label('الأخطاء')
                                ->numeric()->minValue(0)->default(0)
                                ->visible(fn (Forms\Get $get) => $this->estPresent($get)),
                        ]),

                        Forms\Components\Grid::make(5)
                            ->visible(fn (Forms\Get $get) => $this->estPresent($get))
                            ->schema([
                                Forms\Components\Select::make('sourate_debut_id')->label('من سورة')
                                    ->options(fn () => $this->sourates())->searchable()->required(),
                                Forms\Components\TextInput::make('ayah_debut')->label('من الآية')
                                    ->numeric()->minValue(1)->required(),
                                Forms\Components\Select::make('sourate_fin_id')->label('إلى سورة')
                                    ->options(fn () => $this->sourates())->searchable()->required(),
                                Forms\Components\TextInput::make('ayah_fin')->label('إلى الآية')
                                    ->numeric()->minValue(1)->required(),
                                Forms\Components\TextInput::make('nb_pages')->label('الأوجه')
                                    ->numeric()->step(0.25)->minValue(0),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->visible(fn (Forms\Get $get) => $this->estPresent($get))
                            ->schema([
                                Forms\Components\TextInput::make('note_comportement')
                                    ->label('نقطة السلوك / 20')->numeric()->minValue(0)->maxValue(20),
                                Forms\Components\TextInput::make('remarques')->label('ملاحظة'),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * Recharge la liste des étudiants et pré-remplit depuis le مقرر / le rapport existant :
     * priorité à la saisie existante de cette séance, sinon au devoir échu, sinon vide.
     */
    public function chargerEtudiants(): void
    {
        $groupeId = $this->data['groupe_id'] ?? null;
        $date     = $this->data['date'] ?? today()->toDateString();
        $seance   = (int) ($this->data['seance'] ?? 1);

        if (! $groupeId) {
            $this->data['lignes'] = [];

            return;
        }

        $etudiants = Etudiant::valides()
            ->where('groupe_id', $groupeId)
            ->orderBy('nom_ar')
            ->get();

        $rapports = RapportJournalier::whereDate('date', $date)
            ->where('seance', $seance)
            ->whereIn('etudiant_id', $etudiants->pluck('id'))
            ->with('lignes')
            ->get()->keyBy('etudiant_id');

        $taches = TacheMemorisation::whereIn('etudiant_id', $etudiants->pluck('id'))
            ->where('statut', 'assignee')
            ->whereDate('date_echeance', '<=', $date)
            ->orderByDesc('date_echeance')
            ->get()->groupBy('etudiant_id');

        $this->data['lignes'] = $etudiants->map(function (Etudiant $etudiant) use ($rapports, $taches) {
            $rapport = $rapports->get($etudiant->id);
            $ligne   = $rapport?->lignes->first();
            $tache   = $taches->get($etudiant->id)?->first();

            // priorité : ce qui est déjà saisi > le واجب مقرر > vide
            $source = $ligne ?? $tache;

            return [
                'etudiant_id'       => $etudiant->id,
                'nom'               => $etudiant->nom_complet_ar,
                'tache_id'          => $tache?->id,
                'presence'          => $rapport?->presence?->value ?? StatutPresence::PRESENT->value,
                'type'              => $source?->type?->value ?? TypeSeance::HIFD_JADID->value,
                'sourate_debut_id'  => $source?->sourate_debut_id,
                'ayah_debut'        => $source?->ayah_debut,
                'sourate_fin_id'    => $source?->sourate_fin_id,
                'ayah_fin'          => $source?->ayah_fin,
                'nb_pages'          => $source?->nb_pages,
                'note'              => $ligne?->note,
                'nb_erreurs'        => $ligne?->nb_erreurs ?? 0,
                'note_comportement' => $rapport?->note_comportement,
                'remarques'         => $rapport?->remarques,
            ];
        })->all();
    }

    /** Enregistre tous les rapports de la مجموعة en une transaction. */
    /**
     * Sauvegarde « assistée » : un rapport par élève (updateOrCreate sur
     * etudiant + date + séance), lignes recréées, devoirs marqués réalisés,
     * note globale recalculée — le tout dans une transaction unique.
     */
    public function enregistrer(): void
    {
        $donnees = $this->form->getState();
        $groupe  = Groupe::findOrFail($donnees['groupe_id']);

        // Le professeur ne saisit que pour ses propres groupes ;
        // le superviseur peut saisir pour n'importe quelle مجموعة.
        if (auth()->user()->estProfesseur()) {
            abort_unless($groupe->professeur_id === auth()->id(), 403);
        }

        $sourates = Sourate::all()->keyBy('id');
        $erreurs  = [];

        // Contrôle des plages avant toute écriture
        foreach ($donnees['lignes'] as $ligne) {
            if (! $this->ligneEstPresente($ligne) || blank($ligne['sourate_debut_id'])) {
                continue;
            }

            $debut = $sourates[$ligne['sourate_debut_id']];
            $fin   = $sourates[$ligne['sourate_fin_id']];

            if ($ligne['ayah_debut'] > $debut->nb_ayat || $ligne['ayah_fin'] > $fin->nb_ayat) {
                $erreurs[] = $ligne['nom'] . ' : رقم الآية يتجاوز عدد آيات السورة.';
            } elseif ($fin->positionGlobale((int) $ligne['ayah_fin']) < $debut->positionGlobale((int) $ligne['ayah_debut'])) {
                $erreurs[] = $ligne['nom'] . ' : نهاية المقطع تسبق بدايته.';
            }
        }

        if ($erreurs) {
            Notification::make()->danger()
                ->title('تعذر الحفظ')
                ->body(implode(' ', array_slice($erreurs, 0, 3)))
                ->persistent()
                ->send();

            return;
        }

        $compte = 0;

        DB::transaction(function () use ($donnees, $groupe, &$compte) {
            foreach ($donnees['lignes'] as $ligne) {
                $rapport = RapportJournalier::updateOrCreate(
                    [
                        'etudiant_id' => $ligne['etudiant_id'],
                        'date'        => $donnees['date'],
                        'seance'      => (int) ($donnees['seance'] ?? 1),
                    ],
                    [
                        'professeur_id'     => auth()->id(),
                        'groupe_id'         => $groupe->id,
                        'presence'          => $ligne['presence'],
                        'note_comportement' => $ligne['note_comportement'] ?? null,
                        'remarques'         => $ligne['remarques'] ?? null,
                    ]
                );

                $rapport->lignes()->delete();

                if ($this->ligneEstPresente($ligne) && filled($ligne['sourate_debut_id'])) {
                    $rapport->lignes()->create([
                        'tache_id'         => $ligne['tache_id'] ?? null,
                        'type'             => $ligne['type'],
                        'sourate_debut_id' => $ligne['sourate_debut_id'],
                        'ayah_debut'       => $ligne['ayah_debut'],
                        'sourate_fin_id'   => $ligne['sourate_fin_id'],
                        'ayah_fin'         => $ligne['ayah_fin'],
                        'nb_pages'         => $ligne['nb_pages'] ?? null,
                        'note'             => $ligne['note'],
                        'nb_erreurs'       => $ligne['nb_erreurs'] ?? 0,
                    ]);

                    if (! empty($ligne['tache_id'])) {
                        TacheMemorisation::where('id', $ligne['tache_id'])->update(['statut' => 'realisee']);
                    }
                }

                $rapport->recalculerNoteGlobale();
                $compte++;
            }
        });

        Notification::make()->success()
            ->title("تم تسجيل $compte تقرير")
            ->body('مجموعة ' . $groupe->nom_ar . ' — ' . $donnees['date'] . ' — الجلسة ' . ($donnees['seance'] ?? 1))
            ->send();

        $this->chargerEtudiants();
    }

    /* ---------------- Aides ---------------- */

    private function sourates(): array
    {
        return once(fn () => Sourate::orderBy('numero')->pluck('nom_ar', 'id')->all());
    }

    private function estPresent(Forms\Get $get): bool
    {
        return in_array($get('presence'), [StatutPresence::PRESENT->value, StatutPresence::RETARD->value], true);
    }

    private function ligneEstPresente(array $ligne): bool
    {
        return in_array($ligne['presence'] ?? null, [StatutPresence::PRESENT->value, StatutPresence::RETARD->value], true);
    }

    private function libelleEtudiant(array $state): string
    {
        $nom  = $state['nom'] ?? 'طالب';
        $note = $state['note'] ?? null;

        if (! $this->ligneEstPresente($state)) {
            return $nom . ' — ' . (StatutPresence::tryFrom($state['presence'] ?? '')?->getLabel() ?? '');
        }

        return $note !== null && $note !== '' ? "$nom — $note / 20" : $nom . ' — لم يسمَّع بعد';
    }

    /* Compte les élèves notés sur le total de la ligne d'élèves chargée. */
    private function resume(): string
    {
        $lignes = $this->data['lignes'] ?? [];
        $total  = count($lignes);
        $notes  = collect($lignes)->filter(fn ($l) => filled($l['note'] ?? null))->count();

        return $total ? "$notes / $total طالب تم تسميعهم" : 'لا يوجد طلبة في هذه المجموعة';
    }
}
