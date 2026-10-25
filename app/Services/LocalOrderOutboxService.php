<?php

namespace App\Services;

use App\Models\LocalOrderSyncOutbox;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

class LocalOrderOutboxService
{
    /**
     * @return Collection<int, LocalOrderSyncOutbox>
     */
    public function claimDue(int $limit = 50): Collection
    {
        if ($limit < 1 || $limit > 250) {
            throw new \InvalidArgumentException('The outbox claim limit must be between 1 and 250.');
        }

        return DB::transaction(function () use ($limit): Collection {
            $now = now();
            $rows = LocalOrderSyncOutbox::query()
                ->where(function ($query) use ($now): void {
                    $query->where(function ($pending) use ($now): void {
                        $pending->whereIn('status', ['pending', 'failed'])
                            ->where(function ($due) use ($now): void {
                                $due->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', $now);
                            });
                    })->orWhere(function ($stale) use ($now): void {
                        $stale->where('status', 'syncing')
                            ->where('claimed_at', '<=', $now->copy()->subMinutes(5));
                    });
                })
                ->orderBy('id')
                ->limit($limit)
                ->lockForUpdate()
                ->get();

            foreach ($rows as $row) {
                $row->forceFill([
                    'status' => 'syncing',
                    'attempts' => $row->attempts + 1,
                    'claimed_at' => $now,
                    'last_error' => null,
                ])->save();
            }

            return $rows;
        }, 3);
    }

    public function recordFailure(LocalOrderSyncOutbox $outbox, string $reason): void
    {
        if ($outbox->status !== 'syncing') {
            throw new LogicException('Only a claimed local order can be marked as a failed sync attempt.');
        }

        $delaySeconds = min(3600, 15 * (2 ** min(max(0, $outbox->attempts - 1), 8)));
        $outbox->forceFill([
            'status' => 'failed',
            'next_attempt_at' => now()->addSeconds($delaySeconds),
            'claimed_at' => null,
            'last_error' => Str::limit($reason, 2000),
        ])->save();
    }

    public function markSynchronized(LocalOrderSyncOutbox $outbox, string $cloudOrderId): void
    {
        if ($outbox->status !== 'syncing' || trim($cloudOrderId) === '') {
            throw new LogicException('A claimed local order and cloud order identifier are required.');
        }

        $outbox->forceFill([
            'status' => 'synced',
            'cloud_order_id' => $cloudOrderId,
            'synced_at' => now(),
            'claimed_at' => null,
            'next_attempt_at' => null,
            'last_error' => null,
        ])->save();
    }
}
