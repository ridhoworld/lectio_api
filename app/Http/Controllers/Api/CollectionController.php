<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\CollectionItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $collections = $request->user()
            ->collections()
            ->with(['items.userBook.book'])
            ->withCount('items')
            ->latest()
            ->get();

        return response()->json($collections);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
        ]);

        $collection = Collection::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Koleksi berhasil dibuat',
            'collection' => $collection->load(['items.userBook.book'])->loadCount('items'),
        ], 201);
    }

    public function show(Request $request, string $id)
    {
        $collection = $request->user()
            ->collections()
            ->with(['items.userBook.book'])
            ->withCount('items')
            ->findOrFail($id);

        return response()->json($collection);
    }

    public function update(Request $request, string $id)
    {
        $collection = $request->user()->collections()->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
        ]);

        $collection->update($validated);

        return response()->json([
            'message' => 'Koleksi berhasil diperbarui',
            'collection' => $collection->load(['items.userBook.book'])->loadCount('items'),
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $collection = $request->user()->collections()->findOrFail($id);
        $collection->delete();

        return response()->json([
            'message' => 'Koleksi berhasil dihapus',
        ]);
    }

    public function addItem(Request $request, string $id)
    {
        $user = $request->user();
        $collection = $user->collections()->findOrFail($id);

        $validated = $request->validate([
            'user_book_id' => 'required|uuid|exists:user_books,id',
        ]);

        $userBook = $user->userBooks()->findOrFail($validated['user_book_id']);

        $item = CollectionItem::firstOrCreate(
            [
                'collection_id' => $collection->id,
                'user_book_id' => $userBook->id,
            ],
            [
                'added_at' => Carbon::now(),
            ]
        );

        return response()->json([
            'message' => 'Buku ditambahkan ke koleksi',
            'item' => $item->load('userBook.book'),
        ], 201);
    }

    public function removeItem(Request $request, string $id, string $userBookId)
    {
        $collection = $request->user()->collections()->findOrFail($id);

        CollectionItem::where('collection_id', $collection->id)
            ->where('user_book_id', $userBookId)
            ->delete();

        return response()->json([
            'message' => 'Buku dihapus dari koleksi',
        ]);
    }
}

