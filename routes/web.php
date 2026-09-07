<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EtudiantController;
use App\Http\Controllers\GroupeController;
use App\Http\Controllers\LogementController;
use App\Http\Controllers\ProfesseurController;
use App\Http\Controllers\RapportJournalierController;
use App\Http\Controllers\RapportPeriodiqueController;
use App\Http\Controllers\TasmiController;
use Illuminate\Support\Facades\Route;

/* ---------------- Santé ---------------- */

Route::get('/sante', fn () => response()->json(['statut' => 'ok']));

/* ---------------- Authentification ---------------- */

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.submit');

    /* ---- Mot de passe oublié / réinitialisation ---- */
    Route::get('/mot-de-passe-oublie', [ForgotPasswordController::class, 'showRequestForm'])->name('password.request');
    Route::post('/mot-de-passe-oublie', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reinitialiser-mot-de-passe/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reinitialiser-mot-de-passe', [ResetPasswordController::class, 'reset'])->name('password.store');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

/* ---------------- Application (comptes actifs uniquement) ---------------- */

Route::middleware(['auth', 'role:directeur,superviseur,garde_general,professeur'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /* ---- Profil et changement de mot de passe ---- */
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profil/mot-de-passe', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/profil/mot-de-passe', [PasswordController::class, 'update'])->name('password.update');

    /* ---- شؤون الطلبة : الطلبة ---- */
    Route::get('/etudiants', [EtudiantController::class, 'index'])->name('etudiants.index');
    Route::get('/etudiants/create', [EtudiantController::class, 'create'])->name('etudiants.create')->middleware('role:directeur,superviseur,garde_general');
    Route::post('/etudiants', [EtudiantController::class, 'store'])->name('etudiants.store')->middleware('role:directeur,superviseur,garde_general');
    Route::post('/etudiants/affecter-multiple', [EtudiantController::class, 'affecterMultiple'])->name('etudiants.affecter-multiple');
    Route::get('/etudiants/{etudiant}', [EtudiantController::class, 'show'])->name('etudiants.show');
    Route::get('/etudiants/{etudiant}/edit', [EtudiantController::class, 'edit'])->name('etudiants.edit');
    Route::put('/etudiants/{etudiant}', [EtudiantController::class, 'update'])->name('etudiants.update');

    Route::post('/etudiants/{etudiant}/evaluer', [EtudiantController::class, 'evaluer'])->name('etudiants.evaluer');
    Route::post('/etudiants/{etudiant}/affecter', [EtudiantController::class, 'affecter'])->name('etudiants.affecter');
    Route::post('/etudiants/{etudiant}/hebergement', [EtudiantController::class, 'hebergement'])->name('etudiants.hebergement')->middleware('role:garde_general');
    Route::post('/etudiants/{etudiant}/place-priere', [EtudiantController::class, 'placePriere'])->name('etudiants.place-priere')->middleware('role:garde_general');

    /* ---- شؤون الطلبة : الحلقات (consultation pour tous, gestion prof+superviseur+garde) ---- */
    Route::get('/groupes', [GroupeController::class, 'index'])->name('groupes.index');
    Route::middleware('role:professeur,superviseur,garde_general')->group(function () {
        Route::get('/groupes/create', [GroupeController::class, 'create'])->name('groupes.create');
        Route::post('/groupes', [GroupeController::class, 'store'])->name('groupes.store');
        Route::get('/groupes/{groupe}/edit', [GroupeController::class, 'edit'])->name('groupes.edit');
        Route::put('/groupes/{groupe}', [GroupeController::class, 'update'])->name('groupes.update');
        Route::delete('/groupes/{groupe}', [GroupeController::class, 'destroy'])->name('groupes.destroy');
    });

    /* ---- الإدارة : الأساتذة والأطر ---- */
    Route::resource('professeurs', ProfesseurController::class)->except(['show'])->middleware('role:directeur');
    Route::post('/professeurs/{professeur}/toggle', [ProfesseurController::class, 'toggle'])->name('professeurs.toggle')->middleware('role:directeur');

    /* ---- الإدارة : الغرف والإيواء (garde général uniquement) ---- */
    Route::resource('logements', LogementController::class)->except(['show'])->middleware('role:garde_general');

    /* ---- الحفظ والمتابعة : التقارير اليومية (saisie professeur, lecture pour tous) ---- */
    Route::middleware('role:professeur')->group(function () {
        Route::get('/rapport-journaliers/create', [RapportJournalierController::class, 'create'])->name('rapport-journaliers.create');
        Route::post('/rapport-journaliers', [RapportJournalierController::class, 'store'])->name('rapport-journaliers.store');
        Route::get('/rapport-journaliers/{rapport}/edit', [RapportJournalierController::class, 'edit'])->name('rapport-journaliers.edit');
        Route::put('/rapport-journaliers/{rapport}', [RapportJournalierController::class, 'update'])->name('rapport-journaliers.update');
        Route::delete('/rapport-journaliers/{rapport}', [RapportJournalierController::class, 'destroy'])->name('rapport-journaliers.destroy');
    });
    Route::get('/rapport-journaliers', [RapportJournalierController::class, 'index'])->name('rapport-journaliers.index');
    Route::get('/rapport-journaliers/exporter', [RapportJournalierController::class, 'exporterExcel'])->name('rapport-journaliers.excel');
    Route::get('/rapport-journaliers/{rapport}/pdf', [RapportJournalierController::class, 'telechargerPdf'])->name('rapport-journaliers.pdf');
    Route::get('/rapport-journaliers/{rapport}', [RapportJournalierController::class, 'show'])->name('rapport-journaliers.show');

    /* ---- الحفظ والمتابعة : حصة التسميع (visible hors directeur, saisie professeur + superviseur) ---- */
    Route::get('/tasmi', [TasmiController::class, 'index'])->name('tasmi.index')->middleware('role:professeur,superviseur,garde_general');
    Route::post('/tasmi', [TasmiController::class, 'store'])->name('tasmi.store')->middleware('role:professeur,superviseur');

    /* ---- التقارير : أسبوعية وشهرية ---- */
    Route::get('/rapport-periodiques', [RapportPeriodiqueController::class, 'index'])->name('rapports-periodiques.index')
        ->middleware('role:directeur,superviseur');
    Route::get('/rapport-periodiques/exporter', [RapportPeriodiqueController::class, 'exporterExcel'])->name('rapports-periodiques.excel')
        ->middleware('role:directeur,superviseur');
    Route::get('/rapport-periodiques/{rapport}/pdf', [RapportPeriodiqueController::class, 'telechargerPdf'])->name('rapports-periodiques.pdf')
        ->middleware('role:directeur,superviseur');
    Route::get('/rapport-periodiques/{rapport}', [RapportPeriodiqueController::class, 'show'])->name('rapports-periodiques.show')
        ->middleware('role:directeur,superviseur');
    Route::post('/rapport-periodiques/generer', [RapportPeriodiqueController::class, 'generer'])->name('rapports-periodiques.generer')
        ->middleware('role:directeur,superviseur');
});
