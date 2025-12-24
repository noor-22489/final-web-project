<?php

namespace App\Http\Controllers;

use App\Models\LmsSetting;
use Illuminate\Http\Request;

class AdminRulesController extends Controller
{
    public function getLeaderboardRules()
    {
        return response()->json([
            'status' => true,
            'data' => [
                'leaderboard_top_n' => LmsSetting::getInt('leaderboard_top_n', 10),
                'certificate_top_n' => LmsSetting::getInt('certificate_top_n', 3),
            ]
        ]);
    }

    public function updateLeaderboardRules(Request $request)
    {
        $data = $request->validate([
            'leaderboard_top_n' => ['required', 'integer', 'min:1', 'max:100'],
            'certificate_top_n' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        LmsSetting::set('leaderboard_top_n', $data['leaderboard_top_n']);
        LmsSetting::set('certificate_top_n', $data['certificate_top_n']);

        return response()->json([
            'status' => true,
            'message' => 'Rules updated',
            'data' => $data,
        ]);
    }
}
