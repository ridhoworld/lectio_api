<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use Illuminate\Http\Request;

class BadgeController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $earnedBadgeIds = $user->userBadges()->pluck('badge_id')->toArray();

        $badges = Badge::all()->map(function ($badge) use ($earnedBadgeIds, $user) {
            $isEarned = in_array($badge->id, $earnedBadgeIds);
            $earnedAt = null;
            if ($isEarned) {
                $userBadge = $user->userBadges()->where('badge_id', $badge->id)->first();
                $earnedAt = $userBadge?->earned_at;
            }

            return [
                'id' => $badge->id,
                'name' => $badge->name,
                'description' => $badge->description,
                'icon_url' => $badge->icon_url,
                'requirement_type' => $badge->requirement_type,
                'requirement_value' => $badge->requirement_value,
                'is_earned' => $isEarned,
                'earned_at' => $earnedAt,
            ];
        });

        return response()->json($badges);
    }
}

