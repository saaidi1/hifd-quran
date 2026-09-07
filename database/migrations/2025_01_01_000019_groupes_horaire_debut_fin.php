<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groupes', function (Blueprint $table) {
            $table->string('horaire_debut')->nullable()->after('salle');
            $table->string('horaire_fin')->nullable()->after('horaire_debut');
        });

        $groupes = DB::table('groupes')->whereNotNull('horaire')->get();

        foreach ($groupes as $groupe) {
            [$debut, $fin] = self::decouper($groupe->horaire);

            DB::table('groupes')->where('id', $groupe->id)->update([
                'horaire_debut' => $debut,
                'horaire_fin'   => $fin,
            ]);
        }

        Schema::table('groupes', function (Blueprint $table) {
            $table->dropColumn('horaire');
        });
    }

    public function down(): void
    {
        Schema::table('groupes', function (Blueprint $table) {
            $table->string('horaire')->nullable()->after('salle');
        });

        $groupes = DB::table('groupes')->whereNotNull('horaire_debut')->orWhereNotNull('horaire_fin')->get();

        foreach ($groupes as $groupe) {
            $morceaux = array_filter([$groupe->horaire_debut, $groupe->horaire_fin]);

            DB::table('groupes')->where('id', $groupe->id)->update([
                'horaire' => $morceaux ? implode(' - ', $morceaux) : null,
            ]);
        }

        Schema::table('groupes', function (Blueprint $table) {
            $table->dropColumn(['horaire_debut', 'horaire_fin']);
        });
    }

    private static function decouper(?string $horaire): array
    {
        if (! $horaire) {
            return [null, null];
        }

        $parties = preg_split('/\s*[-–—]\s*/', $horaire);

        return [self::normaliser($parties[0] ?? null), self::normaliser($parties[1] ?? null)];
    }

    private static function normaliser(?string $heure): ?string
    {
        if ($heure === null || trim($heure) === '') {
            return null;
        }

        $heure = trim($heure);

        if (preg_match('/^(\d{1,2})\s*h\s*(\d{0,2})$/i', $heure, $m)) {
            return str_pad($m[1], 2, '0', STR_PAD_LEFT).':'.str_pad($m[2] ?: '00', 2, '0', STR_PAD_LEFT);
        }

        if (str_contains($heure, ':')) {
            return substr($heure, 0, 5);
        }

        return $heure;
    }
};
