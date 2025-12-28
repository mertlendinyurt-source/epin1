<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * RapidAPI Service - PUBG Player Lookup
 */
class RapidApiService
{
    private ?string $apiKey;
    private const API_HOST = 'id-game-checker.p.rapidapi.com';
    private const API_URL = 'https://id-game-checker.p.rapidapi.com/pubgm-global/';

    public function __construct()
    {
        $this->apiKey = env('RAPIDAPI_KEY');
    }

    /**
     * Resolve PUBG player name by ID
     */
    public function resolvePlayer(string $playerId): array
    {
        if (!$this->apiKey) {
            // Fallback if API key not configured
            return [
                'success' => true,
                'playerId' => $playerId,
                'playerName' => 'Player#' . substr($playerId, -4),
                'isBanned' => false,
            ];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'x-rapidapi-host' => self::API_HOST,
                    'x-rapidapi-key' => $this->apiKey,
                ])
                ->get(self::API_URL . $playerId);

            if (!$response->successful()) {
                // Fallback on API error
                return [
                    'success' => true,
                    'playerId' => $playerId,
                    'playerName' => 'Player#' . substr($playerId, -4),
                    'isBanned' => false,
                ];
            }

            $data = $response->json();

            // Check if player found
            if (isset($data['error']) || ($data['msg'] ?? '') !== 'id_found') {
                return [
                    'success' => false,
                    'error' => 'Oyuncu ID bulunamadı. Lütfen geçerli bir PUBG Mobile Global ID girin.',
                ];
            }

            // Check if banned
            if (($data['data']['is_ban'] ?? 0) === 1) {
                return [
                    'success' => false,
                    'error' => 'Bu hesap yasaklanmış (banned). UC yüklenemez.',
                ];
            }

            return [
                'success' => true,
                'playerId' => $playerId,
                'playerName' => $data['data']['username'] ?? 'Player#' . substr($playerId, -4),
                'isBanned' => ($data['data']['is_ban'] ?? 0) === 1,
            ];

        } catch (\Exception $e) {
            // Fallback on exception
            return [
                'success' => true,
                'playerId' => $playerId,
                'playerName' => 'Player#' . substr($playerId, -4),
                'isBanned' => false,
            ];
        }
    }
}