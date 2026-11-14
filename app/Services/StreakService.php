<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserStreak;
use Carbon\Carbon;

class StreakService
{
    /**
     * Update user streak after a valid reading session.
     */
    public function recordSession(User $user, ?string $sessionDate = null): array
    {
        $timezone = $user->timezone ?: 'Asia/Jakarta';
        $today = $sessionDate 
            ? Carbon::parse($sessionDate, $timezone)->startOfDay() 
            : Carbon::now($timezone)->startOfDay();

        $streak = $user->streak()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_read_date' => null,
                'freeze_count' => 1,
            ]
        );

        $streakChanged = false;
        $freezeUsed = false;
        $freezeEarned = false;

        if (!$streak->last_read_date) {
            // First time reading
            $streak->current_streak = 1;
            $streak->longest_streak = 1;
            $streak->last_read_date = $today->format('Y-m-d');
            $streakChanged = true;
        } else {
            $lastRead = Carbon::parse($streak->last_read_date, $timezone)->startOfDay();
            $diffDays = $lastRead->diffInDays($today, false);

            if ($diffDays == 0) {
                // Already read today, maintain streak
            } elseif ($diffDays == 1) {
                // Read consecutive day
                $streak->current_streak += 1;
                $streak->last_read_date = $today->format('Y-m-d');
                $streakChanged = true;

                // Milestone: every 7 days streak, award 1 freeze (max 2)
                if ($streak->current_streak % 7 === 0 && $streak->freeze_count < 2) {
                    $streak->freeze_count += 1;
                    $freezeEarned = true;
                }
            } elseif ($diffDays == 2 && $streak->freeze_count > 0) {
                // Missed exactly 1 day, consume 1 freeze
                $streak->freeze_count -= 1;
                $freezeUsed = true;
                $streak->current_streak += 1;
                $streak->last_read_date = $today->format('Y-m-d');
                $streakChanged = true;
            } else {
                // Broken streak
                $streak->current_streak = 1;
                $streak->last_read_date = $today->format('Y-m-d');
                $streakChanged = true;
            }

            if ($streak->current_streak > $streak->longest_streak) {
                $streak->longest_streak = $streak->current_streak;
            }
        }

        $streak->save();

        // Check & unlock badges
        $newBadges = $this->evaluateBadges($user, $streak);

        return [
            'current_streak' => $streak->current_streak,
            'longest_streak' => $streak->longest_streak,
            'freeze_count' => $streak->freeze_count,
            'freeze_used' => $freezeUsed,
            'freeze_earned' => $freezeEarned,
            'new_badges' => $newBadges,
        ];
    }

    /**
     * Evaluate and award badges based on achievements.
     */
    public function evaluateBadges(User $user, UserStreak $streak): array
    {
        $awarded = [];
        $earnedBadgeIds = $user->userBadges()->pluck('badge_id')->toArray();

        $allBadges = Badge::whereNotIn('id', $earnedBadgeIds)->get();

        $booksFinished = $user->userBooks()->where('status', 'finished')->count();
        $totalPagesRead = (int) $user->readingSessions()->sum('end_page') - (int) $user->readingSessions()->sum('start_page');
        $totalPagesRead = max(0, $totalPagesRead);
        $totalNotes = $user->userBooks()->withCount('notes')->get()->sum('notes_count');

        foreach ($allBadges as $badge) {
            $isEarned = false;

            switch ($badge->requirement_type) {
                case 'streak_days':
                    if ($streak->current_streak >= $badge->requirement_value) {
                        $isEarned = true;
                    }
                    break;
                case 'books_finished':
                    if ($booksFinished >= $badge->requirement_value) {
                        $isEarned = true;
                    }
                    break;
                case 'pages_read':
                    if ($totalPagesRead >= $badge->requirement_value) {
                        $isEarned = true;
                    }
                    break;
                case 'notes_count':
                    if ($totalNotes >= $badge->requirement_value) {
                        $isEarned = true;
                    }
                    break;
            }

            if ($isEarned) {
                UserBadge::create([
                    'user_id' => $user->id,
                    'badge_id' => $badge->id,
                    'earned_at' => Carbon::now(),
                ]);
                $awarded[] = $badge;
            }
        }

        return $awarded;
    }
}

