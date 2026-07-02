<?php

namespace App\Actions\Badges;

use App\Models\Badge;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class BuildBadgePageDataAction
{
    /**
     * @return array<string, mixed>
     */
    public function index(User $user): array
    {
        $currentDate = now(config('app.timezone'));
        $badges = $this->badgesForUser($user);

        return [
            'badgeUser' => $user,
            'badges' => $badges,
            'badgeHistoryRows' => $this->historyRows($badges, $user, $currentDate),
            'hasActiveBadge' => $this->hasActiveBadge($user, $currentDate),
            'view_path' => 'frontend.badge.badge',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function confirmation(User $user): array
    {
        return [
            'badgePrice' => get_settings('badge_price'),
            'badgeUser' => $user,
            'view_path' => 'frontend.badge.badge_info',
        ];
    }

    /**
     * @return Collection<int, Badge>
     */
    private function badgesForUser(User $user): Collection
    {
        return Badge::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  Collection<int, Badge>  $badges
     * @return Collection<int, array{number: int, user_name: string, start_date: string, end_date: string, is_active: bool}>
     */
    private function historyRows(Collection $badges, User $user, CarbonInterface $currentDate): Collection
    {
        return $badges
            ->values()
            ->map(fn (Badge $badge, int $index): array => [
                'number' => $index + 1,
                'user_name' => $user->name,
                'start_date' => $badge->start_date?->format('d M Y') ?? '',
                'end_date' => $badge->end_date?->format('d M Y') ?? '',
                'is_active' => $this->badgeCoversDate($badge, $currentDate),
            ]);
    }

    private function hasActiveBadge(User $user, CarbonInterface $currentDate): bool
    {
        $badge = Badge::query()
            ->where('user_id', $user->id)
            ->whereDate('start_date', '<=', $currentDate)
            ->whereDate('end_date', '>=', $currentDate)
            ->first();

        return $badge instanceof Badge
            && (int) $badge->status === 1
            && $this->badgeCoversDate($badge, $currentDate);
    }

    private function badgeCoversDate(Badge $badge, CarbonInterface $currentDate): bool
    {
        return $badge->start_date instanceof CarbonInterface
            && $badge->end_date instanceof CarbonInterface
            && $currentDate->greaterThanOrEqualTo($badge->start_date)
            && $currentDate->lessThanOrEqualTo($badge->end_date);
    }
}
