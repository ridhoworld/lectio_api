<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $timezone = $user->timezone ?: 'Asia/Jakarta';
        $now = Carbon::now($timezone);
        $currentYear = $now->year;

        // 1. 84-day (12 weeks) activity heatmap
        $startDate = $now->copy()->subDays(83)->startOfDay();
        $sessions = $user->readingSessions()
            ->where('session_date', '>=', $startDate->toDateString())
            ->get();

        $dailyMap = [];
        foreach ($sessions as $s) {
            $dateStr = Carbon::parse($s->session_date)->toDateString();
            if (!isset($dailyMap[$dateStr])) {
                $dailyMap[$dateStr] = ['minutes' => 0, 'pages' => 0];
            }
            $dailyMap[$dateStr]['minutes'] += (int) $s->duration_minutes;
            $dailyMap[$dateStr]['pages'] += max(0, (int) $s->end_page - (int) $s->start_page);
        }

        $heatmap = [];
        for ($i = 0; $i < 84; $i++) {
            $d = $startDate->copy()->addDays($i)->toDateString();
            $heatmap[] = [
                'date' => $d,
                'minutes' => $dailyMap[$d]['minutes'] ?? 0,
                'pages' => $dailyMap[$d]['pages'] ?? 0,
            ];
        }

        // 2. 6-month trend
        $monthlyTrend = [];
        for ($m = 5; $m >= 0; $m--) {
            $monthDate = $now->copy()->subMonths($m);
            $monthSessions = $user->readingSessions()
                ->whereYear('session_date', $monthDate->year)
                ->whereMonth('session_date', $monthDate->month)
                ->get();

            $monthlyTrend[] = [
                'month' => $monthDate->locale('id')->isoFormat('MMM'),
                'year' => $monthDate->year,
                'minutes' => (int) $monthSessions->sum('duration_minutes'),
                'pages' => (int) $monthSessions->sum(fn($s) => max(0, $s->end_page - $s->start_page)),
            ];
        }

        // 3. All-time / yearly summary
        $allSessions = $user->readingSessions()->get();
        $totalMinutes = (int) $allSessions->sum('duration_minutes');
        $totalPages = (int) $allSessions->sum(fn($s) => max(0, $s->end_page - $s->start_page));
        $pagesPerHour = $totalMinutes > 0 ? round(($totalPages / $totalMinutes) * 60, 1) : 0;

        $finishedBooks = $user->userBooks()->with('book')->where('status', 'finished')->get();
        $finishedThisYear = $finishedBooks->filter(function ($ub) use ($currentYear) {
            return $ub->finished_at && Carbon::parse($ub->finished_at)->year === $currentYear;
        })->count();

        $ratedBooks = $user->userBooks()->whereNotNull('rating')->get();
        $averageRating = $ratedBooks->count() > 0 ? round($ratedBooks->avg('rating'), 1) : 0;

        // Top genre
        $genreCounts = $user->userBooks()
            ->with('book')
            ->get()
            ->pluck('book.genre')
            ->filter()
            ->countBy()
            ->sortDesc();

        $topGenre = $genreCounts->keys()->first() ?? '-';

        return response()->json([
            'summary' => [
                'total_books_finished' => $finishedBooks->count(),
                'finished_this_year' => $finishedThisYear,
                'total_pages_read' => $totalPages,
                'total_minutes_read' => $totalMinutes,
                'total_sessions' => $allSessions->count(),
                'pages_per_hour' => $pagesPerHour,
                'average_rating' => $averageRating,
                'top_genre' => $topGenre,
            ],
            'heatmap' => $heatmap,
            'monthly_trend' => $monthlyTrend,
        ]);
    }
}

