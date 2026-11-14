<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReadingGoal;
use App\Models\User;
use App\Models\UserStreak;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'email' => 'required|email|max:100|unique:users,email',
            'password' => 'required|string|min:6',
            'name' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|max:50',
            'reminder_time' => 'nullable|string|max:10',
            'daily_minutes_goal' => 'nullable|integer|min:1',
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'name' => $validated['name'] ?? $validated['username'],
            'timezone' => $validated['timezone'] ?? 'Asia/Jakarta',
            'reminder_time' => $validated['reminder_time'] ?? '20:00',
        ]);

        // Initialize default streak
        UserStreak::create([
            'user_id' => $user->id,
            'current_streak' => 0,
            'longest_streak' => 0,
            'freeze_count' => 1,
        ]);

        // Setup default reading goal
        $currentYear = Carbon::now()->year;
        ReadingGoal::create([
            'user_id' => $user->id,
            'goal_type' => 'daily_minutes',
            'target_value' => $validated['daily_minutes_goal'] ?? 15,
            'year' => $currentYear,
            'is_active' => true,
        ]);

        ReadingGoal::create([
            'user_id' => $user->id,
            'goal_type' => 'yearly_books',
            'target_value' => 12,
            'year' => $currentYear,
            'is_active' => true,
        ]);

        $token = $user->createToken('lectio_token')->plainTextToken;

        return response()->json([
            'message' => 'Registrasi berhasil',
            'token' => $token,
            'user' => $user->load('streak', 'readingGoals'),
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string', // username or email
            'password' => 'required|string',
        ]);

        $login = $request->input('login');
        $user = User::where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Kombinasi email/username dan kata sandi tidak cocok.'],
            ]);
        }

        $token = $user->createToken('lectio_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'token' => $token,
            'user' => $user->load('streak', 'readingGoals'),
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user()->load('streak', 'readingGoals');
        return response()->json([
            'user' => $user,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|max:50',
            'reminder_time' => 'nullable|string|max:10',
            'avatar_url' => 'nullable|string',
        ]);

        $user->update(array_filter($validated, fn($val) => !is_null($val)));

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user' => $user->fresh(['streak', 'readingGoals']),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil',
        ]);
    }
}

