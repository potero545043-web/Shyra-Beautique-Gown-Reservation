<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $damagedGowns = DB::table('gowns')
            ->where('status', 'damaged')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('damage_reports')
                    ->whereColumn('damage_reports.gown_id', 'gowns.id')
                    ->whereNull('damage_reports.archived_at');
            })
            ->get(['id']);

        foreach ($damagedGowns as $gown) {
            DB::table('damage_reports')->insert([
                'gown_id' => $gown->id,
                'damage_type' => 'Gown marked damaged',
                'description' => 'Gown status was marked damaged. Add inspection notes and repair costs as needed.',
                'severity' => 'moderate',
                'discovered_at' => now()->toDateString(),
                'repair_cost' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
    }
};
