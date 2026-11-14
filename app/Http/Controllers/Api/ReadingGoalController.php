<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReadingGoal;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReadingGoalController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $year = $request->input('year', Carbon::now()->year);

        $goals = $user->readingGoals()->where('year', $year)->get();

        return response()->json($goals);
    }

    public function storeOrUpdate(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'goal_type' => 'required|in:daily_pages,daily_minutes,yearly_books',
            'target_value' => 'required|integer|min:1',
            'year' => 'nullable|integer',
        ]);

        $year = $validated['year'] ?? Carbon::now()->year;

        $goal = ReadingGoal::updateOrCreate(
            [
                'user_id' => $user->id,
                'goal_type' => $validated['goal_type'],
                'year' => $year,
            ],
            [
                'target_value' => $validated['target_value'],
                'is_active' => true,
            ]
        );

        return response()->json([
            'message' => 'Target membaca berhasil diperbarui',
            'goal' => $goal,
        ]);
    }
}

