<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::query();

        if ($request->filled('q')) {
            $q = $request->input('q');
            $query->where(function ($w) use ($q) {
                $w->where('title', 'like', "%{$q}%")
                  ->orWhere('author', 'like', "%{$q}%")
                  ->orWhere('isbn', 'like', "%{$q}%")
                  ->orWhere('genre', 'like', "%{$q}%");
            });
        }

        if ($request->filled('genre')) {
            $query->where('genre', $request->input('genre'));
        }

        $books = $query->latest()->paginate(20);

        return response()->json($books);
    }

    public function show($id)
    {
        $book = Book::findOrFail($id);
        return response()->json($book);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|max:20|unique:books,isbn',
            'publisher' => 'nullable|string|max:150',
            'total_pages' => 'nullable|integer|min:1',
            'genre' => 'nullable|string|max:100',
            'cover_url' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'synopsis' => 'nullable|string',
        ]);

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('covers', 'public');
            $validated['cover_url'] = url(Storage::url($path));
        }

        unset($validated['cover_image']);
        $book = Book::create($validated);

        return response()->json([
            'message' => 'Buku berhasil ditambahkan ke katalog',
            'book' => $book,
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $book = Book::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'author' => 'sometimes|required|string|max:255',
            'isbn' => 'nullable|string|max:20|unique:books,isbn,' . $book->id,
            'publisher' => 'nullable|string|max:150',
            'total_pages' => 'nullable|integer|min:1',
            'genre' => 'nullable|string|max:100',
            'cover_url' => 'nullable|string',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096',
            'synopsis' => 'nullable|string',
        ]);

        if ($request->hasFile('cover_image')) {
            $path = $request->file('cover_image')->store('covers', 'public');
            $validated['cover_url'] = url(Storage::url($path));
        }

        unset($validated['cover_image']);
        $book->update($validated);

        return response()->json([
            'message' => 'Data buku berhasil diperbarui',
            'book' => $book->fresh(),
        ]);
    }

    /**
     * Lookup book metadata by ISBN from local DB, Google Books, or Open Library.
     */
    public function lookupIsbn(string $isbn)
    {
        $cleanIsbn = preg_replace('/[^0-9Xx]/', '', $isbn);
        if (empty($cleanIsbn)) {
            return response()->json(['message' => 'Format ISBN tidak valid'], 422);
        }

        $existing = Book::where('isbn', $cleanIsbn)->first();
        if ($existing) {
            return response()->json([
                'source' => 'local',
                'exists' => true,
                'book' => $existing,
            ]);
        }

        // 1. Try Google Books API
        try {
            $gbResponse = Http::timeout(8)->get('https://www.googleapis.com/books/v1/volumes', [
                'q' => "isbn:{$cleanIsbn}",
            ]);

            if ($gbResponse->successful()) {
                $items = $gbResponse->json('items', []);
                if (!empty($items)) {
                    $info = $items[0]['volumeInfo'] ?? [];
                    $cover = $info['imageLinks']['thumbnail'] ?? $info['imageLinks']['smallThumbnail'] ?? null;
                    if ($cover) {
                        $cover = str_replace('http://', 'https://', $cover);
                    }

                    return response()->json([
                        'source' => 'google_books',
                        'exists' => false,
                        'book' => [
                            'isbn' => $cleanIsbn,
                            'title' => $info['title'] ?? '',
                            'author' => isset($info['authors']) ? implode(', ', $info['authors']) : '',
                            'publisher' => $info['publisher'] ?? null,
                            'total_pages' => $info['pageCount'] ?? null,
                            'genre' => isset($info['categories'][0]) ? $info['categories'][0] : null,
                            'cover_url' => $cover,
                            'synopsis' => $info['description'] ?? null,
                        ],
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Fallback to Open Library
        }

        // 2. Fallback: Open Library API
        try {
            $olResponse = Http::timeout(8)->get('https://openlibrary.org/api/books', [
                'bibkeys' => "ISBN:{$cleanIsbn}",
                'format' => 'json',
                'jscmd' => 'data',
            ]);

            if ($olResponse->successful()) {
                $data = $olResponse->json("ISBN:{$cleanIsbn}");
                if (!empty($data)) {
                    $authors = collect($data['authors'] ?? [])->pluck('name')->implode(', ');
                    $publishers = collect($data['publishers'] ?? [])->pluck('name')->implode(', ');
                    $subjects = collect($data['subjects'] ?? [])->pluck('name')->first();

                    return response()->json([
                        'source' => 'open_library',
                        'exists' => false,
                        'book' => [
                            'isbn' => $cleanIsbn,
                            'title' => $data['title'] ?? '',
                            'author' => $authors,
                            'publisher' => $publishers ?: null,
                            'total_pages' => $data['number_of_pages'] ?? null,
                            'genre' => $subjects,
                            'cover_url' => $data['cover']['large'] ?? $data['cover']['medium'] ?? null,
                            'synopsis' => null,
                        ],
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Ignore and return 404
        }

        return response()->json([
            'message' => "Buku dengan ISBN {$cleanIsbn} tidak ditemukan. Silakan isi manual.",
        ], 404);
    }
}
