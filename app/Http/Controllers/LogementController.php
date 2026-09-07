<?php

namespace App\Http\Controllers;

use App\Models\Chambre;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Gestion des chambres de l'internat (الإيواء) : liste, création, édition,
 * suppression. L'attribution des lits aux étudiants se fait via
 * EtudiantController@hebergement (numéro de lit = matricule).
 */
class LogementController extends Controller
{
    /** Liste toutes les chambres avec leurs résidents actifs (hebergements.etudiant). */
    public function index(): View
    {
        $chambres = Chambre::query()
            ->with(['hebergements.etudiant'])
            ->orderBy('numero')
            ->get();

        return view('logements.index', ['chambres' => $chambres]);
    }

    /** Formulaire de création d'une chambre, avec la liste des professeurs actifs comme responsables possibles. */
    public function create(): View
    {
        return view('logements.create', ['responsables' => User::professeurs()->where('actif', true)->orderBy('nom_ar')->get()]);
    }

    /** Crée une chambre à partir des données validées. */
    public function store(Request $request): RedirectResponse
    {
        Chambre::create($this->valider($request));

        return redirect()->route('logements.index')->with('success', __('Room added.'));
    }

    /** Formulaire d'édition d'une chambre existante. */
    public function edit(Chambre $logement): View
    {
        return view('logements.edit', [
            'logement'     => $logement,
            'responsables' => User::professeurs()->where('actif', true)->orderBy('nom_ar')->get(),
        ]);
    }

    /** Met à jour une chambre existante. */
    public function update(Request $request, Chambre $logement): RedirectResponse
    {
        $logement->update($this->valider($request));

        return redirect()->route('logements.index')->with('success', __('Room updated.'));
    }

    /** Supprime une chambre. 409 (conflit) si elle héberge encore des résidents actifs. */
    public function destroy(Chambre $logement): RedirectResponse
    {
        abort_if($logement->hebergements()->exists(), 409, 'تعذر حذف غرفة تضم مقيمين.');

        $logement->delete();

        return redirect()->route('logements.index')->with('success', __('Room deleted.'));
    }

    /** Règles de validation du formulaire chambre (numéro, bâtiment, étage, capacité, responsable). */
    private function valider(Request $request): array
    {
        return $request->validate([
            'numero'         => ['required', 'string', 'max:20'],
            'batiment'       => ['nullable', 'string', 'max:50'],
            'etage'          => ['nullable', 'string', 'max:20'],
            'capacite'       => ['required', 'integer', 'min:1'],
            'responsable_id' => ['nullable', 'exists:users,id'],
            'actif'          => ['sometimes', 'boolean'],
        ]);
    }
}
