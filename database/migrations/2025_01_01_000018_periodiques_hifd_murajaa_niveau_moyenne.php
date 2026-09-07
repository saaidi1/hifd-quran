<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rapports_periodiques')->where('moyenne_hifd', 'excellent')
            ->update(['moyenne_hifd' => 'bon']);

        DB::table('rapports_periodiques')->where('moyenne_murajaa', 'excellent')
            ->update(['moyenne_murajaa' => 'bon']);
    }

    public function down(): void
    {
        DB::table('rapports_periodiques')->where('moyenne_hifd', 'bon')
            ->update(['moyenne_hifd' => 'excellent']);

        DB::table('rapports_periodiques')->where('moyenne_murajaa', 'bon')
            ->update(['moyenne_murajaa' => 'excellent']);
    }
};
