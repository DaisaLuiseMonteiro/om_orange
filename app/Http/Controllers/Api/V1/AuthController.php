<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Authentification",
 *     description="Endpoints pour l'authentification et la gestion des comptes"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/auth/code-secret",
     *     summary="Générer un code secret",
     *     tags={"Authentification"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Code secret généré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="message", type="string", example="Code secret généré avec succès")
     *             )
     *         )
     *     )
     * )
     */
    public function generateSecretCode(): JsonResponse
    {
        $user = Auth::user();
        $code = strtoupper(Str::random(6));
        
        $user->update([
            'code_secret' => Hash::make($code)
        ]);

        // Dans un environnement de production, vous enverriez ce code par SMS/Email
        // Pour le développement, nous le retournons dans la réponse
        return response()->json([
            'success' => true,
            'data' => [
                'message' => 'Code secret généré avec succès',
                'code' => $code // À supprimer en production
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/verify-code",
     *     summary="Vérifier le code secret",
     *     tags={"Authentification"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code"},
     *             @OA\Property(property="code", type="string", example="A1B2C3")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code vérifié avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/TokenResponse")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Code invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object", ref="#/components/schemas/ErrorResponse")
     *         )
     *     )
     * )
     */
    public function verifyCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6'
        ]);

        $user = Auth::user();

        if (!$user->code_secret || !Hash::check($request->code, $user->code_secret)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_CODE',
                    'message' => 'Code secret invalide',
                    'details' => []
                ]
            ], 422);
        }

        // Créer un token d'authentification pour le code secret
        $token = $user->createToken('code-secret-token', ['view-balance'])->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => config('sanctum.expiration', 60 * 24 * 30) // 30 jours par défaut
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/solde",
     *     summary="Lister les soldes des comptes",
     *     tags={"Comptes"},
     *     security={{"codeAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des soldes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="numero_compte", type="string"),
     *                     @OA\Property(property="type", type="string"),
     *                     @OA\Property(property="solde", type="number", format="float"),
     *                     @OA\Property(property="devise", type="string")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function listSoldes(): JsonResponse
    {
        $user = Auth::user();
        
        $soldes = $user->comptes()->get(['id', 'numero_compte', 'type', 'solde', 'devise']);

        return response()->json([
            'success' => true,
            'data' => $soldes
        ]);
    }
}

/**
 * @OA\Schema(
 *     schema="TokenResponse",
 *     type="object",
 *     @OA\Property(property="token", type="string", example="1|abcdef123456"),
 *     @OA\Property(property="token_type", type="string", example="Bearer"),
 *     @OA\Property(property="expires_in", type="integer", example=43200)
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(property="code", type="string", example="ERROR_CODE"),
 *     @OA\Property(property="message", type="string", example="Message d'erreur détaillé"),
 *     @OA\Property(property="details", type="object", example={})
 * )
 */
