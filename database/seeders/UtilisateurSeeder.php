<?php

namespace Database\Seeders;

use App\Enums\RoleUtilisateur;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UtilisateurSeeder extends Seeder
{
    public function run(): void
    {
        $comptes = [
            // المدير
            ['directeur@madrasa.ma',   'المدير',          RoleUtilisateur::DIRECTEUR],
            ['directeur2@madrasa.ma',  'المدير (2)',      RoleUtilisateur::DIRECTEUR],
            ['directeur3@madrasa.ma',  'المدير (3)',      RoleUtilisateur::DIRECTEUR],
            // المشرف على الأساتذة
            ['superviseur@madrasa.ma', 'المشرف على الأساتذة', RoleUtilisateur::SUPERVISEUR],
            ['superviseur2@madrasa.ma', 'المشرف (2)',     RoleUtilisateur::SUPERVISEUR],
            ['superviseur3@madrasa.ma', 'المشرف (3)',     RoleUtilisateur::SUPERVISEUR],
            // الحارس العام
            ['garde@madrasa.ma',       'الحارس العام',    RoleUtilisateur::GARDE],
            ['garde2@madrasa.ma',      'الحارس (2)',      RoleUtilisateur::GARDE],
            ['garde3@madrasa.ma',      'الحارس (3)',      RoleUtilisateur::GARDE],
            // الأستاذ
            ['professeur@madrasa.ma',  'الأستاذ',         RoleUtilisateur::PROFESSEUR],
            ['professeur2@madrasa.ma', 'الأستاذ (2)',     RoleUtilisateur::PROFESSEUR],
            ['professeur3@madrasa.ma', 'الأستاذ (3)',     RoleUtilisateur::PROFESSEUR],
        ];

        foreach ($comptes as [$email, $nomAr, $role]) {
            User::updateOrCreate(['email' => $email], [
                'name'     => $role->libelle(),
                'prenom'   => $role->libelle(),
                'nom'      => 'Demo',
                'nom_ar'   => $nomAr,
                'role'     => $role,
                'password' => Hash::make('password'),
                'actif'    => true,
            ]);
        }
    }
}
