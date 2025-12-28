<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Services\RapidApiService;

class PlayerController extends Controller
{
    private RapidApiService $rapidApi;

    public function __construct(RapidApiService $rapidApi)
    {
        $this->rapidApi = $rapidApi;
    }

    /**
     * Resolve player name by ID
     * GET /api/player/resolve?id=xxx
     */
    public function resolve(Request $request): JsonResponse
    {
        $playerId = $request->query('id');

        if (!$playerId || strlen($playerId) < 6) {
            return response()->json([
                'success' => false,
                'error' => 'Geçersiz Oyuncu ID',
            ], 400);
        }

        $result = $this->rapidApi->resolvePlayer($playerId);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'error' => $result['error'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'playerId' => $result['playerId'],
                'playerName' => $result['playerName'],
                'isBanned' => $result['isBanned'] ?? false,
            ],
        ]);
    }
}