<?php

namespace App\Services;

use App\Models\Gown;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Account-backed gown cart used by customer, employee, and owner booking flows.
 *
 * Each account keeps its selection in storage so it survives logout and login.
 * Legacy session selections are imported into that account's cart on first use.
 */
class ReservationCart
{
    public const SESSION_KEY = 'reservation_cart';
    public const STAFF_SESSION_KEY = 'staff_reservation_cart';
    public const OWNER_SESSION_KEY = 'owner_reservation_cart';

    /** Session key for a role so each account type has an independent cart. */
    public static function keyFor(string $role): string
    {
        return match ($role) {
            'customer' => self::SESSION_KEY,
            'employee' => self::STAFF_SESSION_KEY,
            'owner' => self::OWNER_SESSION_KEY,
            default => throw new \InvalidArgumentException('Unsupported reservation cart role.'),
        };
    }

    public function __construct(private string $key)
    {
    }

    /** Convenience factory so callers do not repeat the prefix rules. */
    public static function forRole(string $role): self
    {
        return new self(self::keyFor($role));
    }

    /** @return array<int, int> The deduplicated gown ids currently in the cart. */
    public function ids(): array
    {
        $sessionIds = array_values(array_unique(array_map('intval', (array) session($this->key, []))));
        $userId = auth()->id();

        if (!$userId) {
            return $sessionIds;
        }

        if ($sessionIds !== []) {
            $validIds = Gown::whereIn('id', $sessionIds)->pluck('id')->map(fn ($id) => (int) $id);
            DB::table('reservation_cart_items')->insertOrIgnore(
                $validIds->map(fn (int $gownId) => [
                    'user_id' => $userId,
                    'cart_key' => $this->key,
                    'gown_id' => $gownId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all()
            );
            session()->forget($this->key);
        }

        return DB::table('reservation_cart_items')
            ->where('user_id', $userId)
            ->where('cart_key', $this->key)
            ->orderBy('created_at')
            ->orderBy('id')
            ->pluck('gown_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function count(): int
    {
        return count($this->ids());
    }

    public function isEmpty(): bool
    {
        return $this->ids() === [];
    }

    /** Adds a gown once. Re-adding an already-present gown is a no-op. */
    public function add(int $gownId): void
    {
        $ids = $this->ids();
        if (!in_array($gownId, $ids, true)) {
            if ($userId = auth()->id()) {
                DB::table('reservation_cart_items')->insertOrIgnore([
                    'user_id' => $userId,
                    'cart_key' => $this->key,
                    'gown_id' => $gownId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $ids[] = $gownId;
                session([$this->key => $ids]);
            }
        }
    }

    public function remove(int $gownId): void
    {
        $ids = $this->ids();

        if ($userId = auth()->id()) {
            DB::table('reservation_cart_items')
                ->where('user_id', $userId)
                ->where('cart_key', $this->key)
                ->where('gown_id', $gownId)
                ->delete();

            return;
        }

        session([$this->key => array_values(array_diff($ids, [$gownId]))]);
    }

    public function clear(): void
    {
        if ($userId = auth()->id()) {
            DB::table('reservation_cart_items')
                ->where('user_id', $userId)
                ->where('cart_key', $this->key)
                ->delete();
        }

        session()->forget($this->key);
    }

    public function has(int $gownId): bool
    {
        return in_array($gownId, $this->ids(), true);
    }

    /**
     * The cart's gowns, in the order they were added, excluding archived or
     * retired pieces so a stale id never reaches checkout.
     */
    public function gowns(): Collection
    {
        $ids = $this->ids();
        if ($ids === []) {
            return collect();
        }

        return Gown::with('category')
            ->whereIn('id', $ids)
            ->whereNull('archived_at')
            ->where('status', '!=', 'retired')
            ->get()
            ->sortBy(fn(Gown $gown) => array_search($gown->id, $ids, true))
            ->values();
    }

    /** Combined rental price of every gown in the cart. */
    public function total(): float
    {
        return round($this->gowns()->sum(fn(Gown $gown) => (float) $gown->rental_price), 2);
    }
}
