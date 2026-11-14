<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\ReadingSession;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $timezone = $user->timezone ?: 'Asia/Jakarta';
        $now = Carbon::now($timezone);
        $todayStr = $now->toDateString();
        $currentYear = $now->year;

        // 1. Streak info
        $streak = $user->streak()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'current_streak' => 0,
                'longest_streak' => 0,
                'freeze_count' => 1,
            ]
        );

        // 2. Currently reading books
        $currentlyReading = $user->userBooks()
            ->with('book')
            ->where('status', 'reading')
            ->latest('updated_at')
            ->get();

        // 3. Today's sessions
        $todaySessions = ReadingSession::where('user_id', $user->id)
            ->where('session_date', $todayStr)
            ->get();

        $todayMinutes = (int) $todaySessions->sum('duration_minutes');
        $todayPages = (int) $todaySessions->sum(function ($s) {
            return max(0, $s->end_page - $s->start_page);
        });

        // 4. Goals for today
        $dailyMinutesGoal = $user->readingGoals()
            ->where('goal_type', 'daily_minutes')
            ->where('year', $currentYear)
            ->where('is_active', true)
            ->first();

        $dailyPagesGoal = $user->readingGoals()
            ->where('goal_type', 'daily_pages')
            ->where('year', $currentYear)
            ->where('is_active', true)
            ->first();

        $yearlyBooksGoal = $user->readingGoals()
            ->where('goal_type', 'yearly_books')
            ->where('year', $currentYear)
            ->where('is_active', true)
            ->first();

        $finishedBooksThisYear = $user->userBooks()
            ->where('status', 'finished')
            ->whereYear('finished_at', $currentYear)
            ->count();

        // 5. Activity this week (7 days)
        $startOfWeek = $now->copy()->startOfWeek();
        $weeklyActivity = [];
        for ($i = 0; $i < 7; $i++) {
            $date = $startOfWeek->copy()->addDays($i)->toDateString();
            $dayName = $startOfWeek->copy()->addDays($i)->locale('id')->isoFormat('ddd');
            $hasRead = ReadingSession::where('user_id', $user->id)
                ->where('session_date', $date)
                ->exists();
            $minutes = (int) ReadingSession::where('user_id', $user->id)
                ->where('session_date', $date)
                ->sum('duration_minutes');

            $weeklyActivity[] = [
                'date' => $date,
                'day' => $dayName,
                'is_read' => $hasRead,
                'minutes' => $minutes,
                'is_today' => ($date === $todayStr),
            ];
        }

        // 6. Badges count
        $totalBadges = Badge::count();
        $earnedBadgesCount = $user->userBadges()->count();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'timezone' => $user->timezone,
            ],
            'streak' => [
                'current_streak' => $streak->current_streak,
                'longest_streak' => $streak->longest_streak,
                'freeze_count' => $streak->freeze_count,
                'last_read_date' => $streak->last_read_date,
                'is_read_today' => ($streak->last_read_date === $todayStr),
            ],
            'today' => [
                'minutes' => $todayMinutes,
                'pages' => $todayPages,
                'daily_minutes_target' => $dailyMinutesGoal?->target_value ?? 15,
                'daily_minutes_progress' => $dailyMinutesGoal && $dailyMinutesGoal->target_value > 0
                    ? round(min(($todayMinutes / $dailyMinutesGoal->target_value) * 100, 100), 1)
                    : 0,
                'daily_pages_target' => $dailyPagesGoal?->target_value ?? 20,
                'daily_pages_progress' => $dailyPagesGoal && $dailyPagesGoal->target_value > 0
                    ? round(min(($todayPages / $dailyPagesGoal->target_value) * 100, 100), 1)
                    : 0,
            ],
            'yearly' => [
                'year' => $currentYear,
                'finished_books' => $finishedBooksThisYear,
                'target_books' => $yearlyBooksGoal?->target_value ?? 12,
                'progress_percentage' => $yearlyBooksGoal && $yearlyBooksGoal->target_value > 0
                    ? round(min(($finishedBooksThisYear / $yearlyBooksGoal->target_value) * 100, 100), 1)
                    : 0,
            ],
            'weekly_activity' => $weeklyActivity,
            'currently_reading' => $currentlyReading,
            'badges' => [
                'total' => $totalBadges,
                'earned' => $earnedBadgesCount,
            ],
        ]);
    }
}

