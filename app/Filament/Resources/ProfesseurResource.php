<?php

namespace App\Filament\Resources;

use App\Enums\RoleUtilisateur;
use App\Filament\Resources\ProfesseurResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

/**
 * الأطر الإدارية والأساتذة — إضافة الأساتذة من طرف المدير.
 *
 * Gestion des comptes utilisateurs (professeurs, superviseurs, garde…)
 * strictement réservée au directeur (canViewAny) ; le compte courant
 * est exclu de la liste pour éviter l'auto-édition.
 */
class ProfesseurResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon   = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup  = 'الإدارة';
    protected static ?string $navigationLabel  = 'الأساتذة والأطر';
    protected static ?string $modelLabel       = 'أستاذ';
    protected static ?string $pluralModelLabel = 'الأساتذة والأطر';

    public static function form(Form $form): Form
    {
        return $form->schema([
            /* Compte utilisateur : identité, rôle, identifiants. */
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('nom_ar')->label('الاسم الكامل بالعربية')->required(),
                /* La « صفة » détermine les permissions (professeur, superviseur, garde…). */
                Forms\Components\Select::make('role')->label('الصفة')->options(RoleUtilisateur::class)
                    ->default(RoleUtilisateur::PROFESSEUR->value)->required(),
                Forms\Components\TextInput::make('prenom')->label('الاسم (لاتيني)')->required(),
                Forms\Components\TextInput::make('nom')->label('النسب (لاتيني)')->required(),
                Forms\Components\TextInput::make('email')->label('البريد الإلكتروني')->email()->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('telephone')->label('الهاتف')->tel(),
                Forms\Components\TextInput::make('specialite')->label('التخصص')->placeholder('رواية ورش عن نافع'),
                /* Mot de passe : obligatoire à la création, haché, modifiable seulement s'il est rempli. */
                Forms\Components\TextInput::make('password')->label('كلمة المرور')->password()
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->minLength(8),
                Forms\Components\Toggle::make('actif')->label('حساب نشيط')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom_ar')->label('الاسم')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('role')->label('الصفة')->badge(),
                Tables\Columns\TextColumn::make('email')->label('البريد')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('telephone')->label('الهاتف'),
                Tables\Columns\TextColumn::make('specialite')->label('التخصص')->toggleable(),
                Tables\Columns\TextColumn::make('groupes_count')->label('عدد المجموعات')->counts('groupes')->badge(),
                Tables\Columns\IconColumn::make('actif')->label('نشيط')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')->label('الصفة')->options(RoleUtilisateur::class),
                Tables\Filters\TernaryFilter::make('actif')->label('نشيط'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                /* Désactivation douce : le compte reste en base mais ne peut plus se connecter. */
                Tables\Actions\Action::make('desactiver')->label('تعطيل الحساب')
                    ->icon('heroicon-o-no-symbol')->color('danger')->requiresConfirmation()
                    ->visible(fn (User $record) => $record->actif && $record->id !== auth()->id())
                    ->action(fn (User $record) => $record->update(['actif' => false])),
            ])
            ->defaultSort('nom_ar');
    }

    /** Seul le directeur gère les comptes. */
    public static function canViewAny(): bool
    {
        return auth()->user()->estDirecteur();
    }

    /* Exclut le compte connecté de la liste (pas d'auto-édition du directeur). */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNot('id', auth()->id());
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProfesseurs::route('/'),
            'create' => Pages\CreateProfesseur::route('/create'),
            'edit'   => Pages\EditProfesseur::route('/{record}/edit'),
        ];
    }
}
