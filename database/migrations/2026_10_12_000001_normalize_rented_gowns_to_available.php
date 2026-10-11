<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('gowns')
            ->where('status', 'rented')
            ->update(['status' => 'available']);
    }

    public function down(): void
    {
    }
};
