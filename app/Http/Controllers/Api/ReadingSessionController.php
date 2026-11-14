<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReadingSession;
use App\Services\StreakService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReadingSessionController extends Controller
{
    protected StreakService $streakService;

    public function __construct(StreakService $streakService)
    {
        $this->streakService = $streakService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $sessions = $user->readingSessions()
            ->with('userBook.book')
            ->latest('session_date')
            ->latest('created_at')
            ->paginate(20);

        return response()->json($sessions);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $timezone = $user->timezone ?: 'Asia/Jakarta';
        $today = Carbon::now($timezone)->toDateString();

        $validated = $request->validate([
            'user_book_id' => 'required|uuid|exists:user_books,id',
            'start_page' => 'required|integer|min:0',
            'end_page' => 'required|integer|gte:start_page',
            'duration_minutes' => 'required|integer|min:1',
            'session_date' => 'nullable|date',
        ]);

        $sessionDate = $validated['session_date'] ?? $today;

        // Verify user owns this user_book
        $userBook = $user->userBooks()->with('book')->findOrFail($validated['user_book_id']);

        return DB::transaction(function () use ($user, $userBook, $validated, $sessionDate, $today) {
            // 1. Create reading session
            $session = ReadingSession::create([
                'user_id' => $user->id,
                'user_book_id' => $userBook->id,
                'start_page' => $validated['start_page'],
                'end_page' => $validated['end_page'],
                'duration_minutes' => $validated['duration_minutes'],
                'session_date' => $sessionDate,
            ]);

            // 2. Update user_book current_page and status
            $userBook->current_page = max($userBook->current_page, $validated['end_page']);
            if ($userBook->status === 'want_to_read') {
                $userBook->status = 'reading';
                $userBook->started_at = $sessionDate;
            }

            // Check if book finished
            if ($userBook->book && $userBook->book->total_pages && $userBook->current_page >= $userBook->book->total_pages) {
                $userBook->status = 'finished';
                if (!$userBook->finished_at) {
                    $userBook->finished_at = $sessionDate;
                }
            }

            $userBook->save();

            // 3. Process Streak and Gamification Badges
            $streakResult = $this->streakService->recordSession($user, $sessionDate);

            return response()->json([
                'message' => 'Sesi membaca berhasil disimpan!',
                'session' => $session->load('userBook.book'),
                'streak' => $streakResult,
            ], 201);
        });
    }
}

