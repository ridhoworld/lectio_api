<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserBook;
use Carbon\Carbon;
use Illuminate\Http\Request;

class UserBookController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = $user->userBooks()->with('book');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $userBooks = $query->latest('updated_at')->get();

        return response()->json($userBooks);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'book_id' => 'required|uuid|exists:books,id',
            'status' => 'nullable|in:want_to_read,reading,finished,dnf',
            'format' => 'nullable|in:physical,ebook,audiobook',
            'current_page' => 'nullable|integer|min:0',
        ]);

        $status = $validated['status'] ?? 'want_to_read';
        $format = $validated['format'] ?? 'physical';
        $currentPage = $validated['current_page'] ?? 0;

        $userBook = UserBook::firstOrNew([
            'user_id' => $user->id,
            'book_id' => $validated['book_id'],
        ]);

        $userBook->status = $status;
        $userBook->format = $format;
        $userBook->current_page = $currentPage;

        $timezone = $user->timezone ?: 'Asia/Jakarta';
        $today = Carbon::now($timezone)->toDateString();

        if ($status === 'reading' && !$userBook->started_at) {
            $userBook->started_at = $today;
        }

        if ($status === 'finished' && !$userBook->finished_at) {
            $userBook->finished_at = $today;
            $book = $userBook->book;
            if ($book && $book->total_pages) {
                $userBook->current_page = $book->total_pages;
            }
        }

        $userBook->save();

        return response()->json([
            'message' => 'Buku berhasil dimasukkan ke rak',
            'user_book' => $userBook->load('book'),
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $userBook = $request->user()->userBooks()
            ->with(['book', 'notes', 'readingSessions'])
            ->findOrFail($id);

        return response()->json($userBook);
    }

    public function update(Request $request, $id)
    {
        $userBook = $request->user()->userBooks()->with('book')->findOrFail($id);

        $validated = $request->validate([
            'status' => 'nullable|in:want_to_read,reading,finished,dnf',
            'format' => 'nullable|in:physical,ebook,audiobook',
            'current_page' => 'nullable|integer|min:0',
            'rating' => 'nullable|numeric|between:1.0,5.0',
            'review' => 'nullable|string',
            'started_at' => 'nullable|date',
            'finished_at' => 'nullable|date',
        ]);

        $timezone = $request->user()->timezone ?: 'Asia/Jakarta';
        $today = Carbon::now($timezone)->toDateString();

        if (isset($validated['status'])) {
            $newStatus = $validated['status'];
            if ($newStatus === 'reading' && !$userBook->started_at) {
                $userBook->started_at = $today;
            } elseif ($newStatus === 'finished') {
                if (!$userBook->finished_at) {
                    $userBook->finished_at = $today;
                }
                if ($userBook->book && $userBook->book->total_pages) {
                    $userBook->current_page = $userBook->book->total_pages;
                }
            }
            $userBook->status = $newStatus;
        }

        if (isset($validated['current_page'])) {
            $maxPages = $userBook->book?->total_pages;
            $userBook->current_page = $maxPages 
                ? min($validated['current_page'], $maxPages)
                : $validated['current_page'];

            // Auto finish if reached end page
            if ($maxPages && $userBook->current_page >= $maxPages && $userBook->status !== 'finished') {
                $userBook->status = 'finished';
                $userBook->finished_at = $today;
            }
        }

        if (array_key_exists('rating', $validated)) {
            $userBook->rating = $validated['rating'];
        }

        if (array_key_exists('review', $validated)) {
            $userBook->review = $validated['review'];
        }

        if (isset($validated['format'])) {
            $userBook->format = $validated['format'];
        }

        if (isset($validated['started_at'])) {
            $userBook->started_at = $validated['started_at'];
        }

        if (isset($validated['finished_at'])) {
            $userBook->finished_at = $validated['finished_at'];
        }

        $userBook->save();

        return response()->json([
            'message' => 'Progres rak buku berhasil diperbarui',
            'user_book' => $userBook->load('book'),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $userBook = $request->user()->userBooks()->findOrFail($id);
        $userBook->delete();

        return response()->json([
            'message' => 'Buku berhasil dihapus dari rak',
        ]);
    }
}

