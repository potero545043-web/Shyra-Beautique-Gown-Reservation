<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DamageReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'gown_return_id',
        'reservation_id',
        'gown_id',
        'damage_type',
        'description',
        'repair_cost',
        'discovered_at',
        'estimated_repair_cost',
        'final_repair_cost',
        'severity',
        'photos',
        'archived_at',
    ];

    protected $casts = [
        'repair_cost' => 'decimal:2',
        'discovered_at' => 'date',
        'estimated_repair_cost' => 'decimal:2',
        'final_repair_cost' => 'decimal:2',
        'archived_at' => 'datetime',
    ];

    public static function ensureForDamagedGown(Gown $gown): ?self
    {
        if ($gown->status !== 'damaged') {
            return null;
        }

        return self::where('gown_id', $gown->id)
            ->whereNull('archived_at')
            ->first() ?? self::create([
                'gown_id' => $gown->id,
                'damage_type' => 'Gown marked damaged',
                'description' => 'Gown status was marked damaged. Add inspection notes and repair costs as needed.',
                'severity' => 'moderate',
                'discovered_at' => Carbon::today(),
                'repair_cost' => 0,
            ]);
    }

    public function gownReturn()
    {
        return $this->belongsTo(GownReturn::class);
    }

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }

    public function gown()
    {
        return $this->belongsTo(Gown::class);
    }
}
