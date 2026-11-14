<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NoteAndHighlight;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = NoteAndHighlight::whereHas('userBook', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with('userBook.book');

        if ($request->filled('user_book_id')) {
            $query->where('user_book_id', $request->input('user_book_id'));
        }

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($w) use ($q) {
                $w->where('quote_text', 'like', "%{$q}%")
                  ->orWhere('personal_note', 'like', "%{$q}%");
            });
        }

        $notes = $query->latest()->paginate(20);

        return response()->json($notes);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'user_book_id' => 'required|uuid|exists:user_books,id',
            'quote_text' => 'required|string',
            'page_number' => 'nullable|integer|min:1',
            'personal_note' => 'nullable|string',
        ]);

        // Ensure user owns this user_book
        $userBook = $user->userBooks()->findOrFail($validated['user_book_id']);

        $note = NoteAndHighlight::create([
            'user_book_id' => $userBook->id,
            'quote_text' => $validated['quote_text'],
            'page_number' => $validated['page_number'] ?? null,
            'personal_note' => $validated['personal_note'] ?? null,
        ]);

        return response()->json([
            'message' => 'Kutipan/catatan berhasil disimpan',
            'note' => $note->load('userBook.book'),
        ], 201);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $note = NoteAndHighlight::whereHas('userBook', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($id);

        $note->delete();

        return response()->json([
            'message' => 'Catatan berhasil dihapus',
        ]);
    }
}

