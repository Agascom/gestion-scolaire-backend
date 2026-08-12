<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Controller de base de l'API v1 : format de réponse JSON homogène.
 *
 * Format : { success: bool, data: mixed, message: string }
 */
abstract class ApiController extends Controller
{
    /**
     * Réponse de succès.
     */
    protected function success(mixed $data = null, string $message = '', int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    /**
     * Réponse d'erreur.
     */
    protected function error(string $message, int $status = 422, mixed $data = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => $data,
            'message' => $message,
        ], $status);
    }
}