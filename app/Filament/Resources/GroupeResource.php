<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GroupeResource\Pages;
use App\Models\Groupe;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * المجموعات — gestion des groupes de mémorisation du Coran.
 *
 * Périmètre par rôle :
 * - Professeur : consultation et édition limitées à SES groupes (voir getEloquentQuery) ;
 * - Directeur : gestion complète ; seul lui-même peut créer un groupe (canCreate) ;
 * - Superviseur / garde : consultation selon les droits généraux.
 */
class GroupeResource extends Resource
{
    protected static ?string $model = Groupe::class;

    protected static ?string $navigationIcon   = 'heroicon-o-rectangle-group';
    protected static ?string $navigationGroup  = 'شؤون الطلبة';
    protected static ?string $navigationLabel  = 'المجموعات';
    protected static ?string $modelLabel       = 'مجموعة';
    protected static ?string $pluralModelLabel = 'المجموعات';
    protected static ?int    $navigationSort   = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            /* Fiche du groupe : identité, encadrant, niveau et horaires. */
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\TextInput::make('nom_ar')->label('اسم المجموعة')->required(),
                Forms\Components\TextInput::make('nom')->label('الاسم (لاتيني)'),
                /* « الأستاذ المشرف » : professeurs ET superviseurs actifs (scope User::encadrants()). */
                Forms\Components\Select::make('professeur_id')->label('الأستاذ المشرف')
                    ->options(fn () => User::encadrants()->where('actif', true)
                        ->orderBy('nom_ar')
                        ->get()->mapWithKeys(fn ($p) => [$p->id => $p->nom_ar ?: $p->nom]))
                    ->searchable()->preload(),
                Forms\Components\Select::make('niveau')->label('المستوى')
                    ->options(['mubtadi' => 'مبتدئ', 'moutawassit' => 'متوسط', 'moutaqaddim' => 'متقدم', 'khatma' => 'ختمة']),
                Forms\Components\TextInput::make('salle')->label('القاعة'),
                Forms\Components\TimePicker::make('horaire_debut')->label('التوقيت (من)'),
                Forms\Components\TimePicker::make('horaire_fin')->label('التوقيت (إلى)'),
                Forms\Components\TextInput::make('capacite')->label('الطاقة الاستيعابية')->numeric()->minValue(1)->default(20)->required(),
                Forms\Components\TextInput::make('annee_scolaire')->label('السنة الدراسية')->default(date('Y') . '/' . (date('Y') + 1)),
                Forms\Components\Toggle::make('actif')->label('نشيطة')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom_ar')->label('المجموعة')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('professeur.nom_ar')->label('الأستاذ')->searchable()->placeholder('غير محدد'),
                Tables\Columns\TextColumn::make('niveau')->label('المستوى')->badge()
                    /* Libellé arabe du niveau calculé à partir de la clé stockée. */
                    ->formatStateUsing(fn ($state) => ['mubtadi' => 'مبتدئ', 'moutawassit' => 'متوسط', 'moutaqaddim' => 'متقدم', 'khatma' => 'ختمة'][$state] ?? $state),
                /* Nombre d'élèves rattachés (sous-requête counts). */
                Tables\Columns\TextColumn::make('etudiants_count')->label('عدد الطلبة')->counts('etudiants')->badge(),
                /* Places restantes calculées côté modèle (capacité - effectif). */
                Tables\Columns\TextColumn::make('places')->label('الأماكن الشاغرة')
                    ->state(fn (Groupe $g) => $g->placesRestantes())
                    ->badge()->color(fn ($state) => $state > 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('salle')->label('القاعة')->toggleable(),
                Tables\Columns\TextColumn::make('horaire_debut')->label('التوقيت')->toggleable()
                    ->formatStateUsing(fn (Groupe $g) => $g->horaire_debut && $g->horaire_fin
                        ? "{$g->horaire_debut} - {$g->horaire_fin}"
                        : ($g->horaire_debut ?: $g->horaire_fin)),
                Tables\Columns\IconColumn::make('actif')->label('نشيطة')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('professeur_id')->label('الأستاذ')->relationship('professeur', 'nom_ar'),
                Tables\Filters\TernaryFilter::make('actif')->label('نشيطة'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                /* Suppression réservée au directeur et uniquement si le groupe est vide d'élèves. */
                Tables\Actions\DeleteAction::make()->label('حذف')
                    ->visible(fn (Groupe $g) => auth()->user()->estDirecteur() && ! $g->etudiants()->exists()),
            ])
            ->defaultSort('nom_ar');
    }

    /**
     * Le professeur ne voit que ses مجموعات ;
     * les autres rôles (directeur, superviseur) voient tous les groupes.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->estProfesseur()) {
            $query->where('professeur_id', auth()->id());
        }

        return $query;
    }

    /* Création de groupe réservée au directeur. */
    public static function canCreate(): bool
    {
        return auth()->user()->estDirecteur();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListGroupes::route('/'),
            'create' => Pages\CreateGroupe::route('/create'),
            'edit'   => Pages\EditGroupe::route('/{record}/edit'),
        ];
    }
}
