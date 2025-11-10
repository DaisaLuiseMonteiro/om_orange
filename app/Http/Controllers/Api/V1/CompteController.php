<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Compte;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @OA\Tag(
 *     name="Comptes",
 *     description="Gestion des comptes bancaires"
 * )
 */
class CompteController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/comptes",
     *     summary="Lister tous les comptes",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Compte")
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $comptes = Compte::with(['client', 'transactions'])
            ->where('user_id', auth()->id())
            ->get();

        return response()->json([
            'success' => true,
            'data' => $comptes
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/comptes",
     *     summary="Créer un nouveau compte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"client_id", "type_compte", "devise"},
     *             @OA\Property(property="client_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="type_compte", type="string", example="courant", enum={"courant", "epargne"}),
     *             @OA\Property(property="devise", type="string", example="XOF", maxLength=3),
     *             @OA\Property(property="solde_initial", type="number", format="float", example=0)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Compte créé avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|uuid|exists:clients,id',
            'type_compte' => 'required|in:courant,epargne',
            'devise' => 'required|string|size:3',
            'solde_initial' => 'sometimes|numeric|min:0'
        ]);

        $compte = DB::transaction(function () use ($validated) {
            $compte = Compte::create([
                'numero_compte' => $this->genererNumeroCompte(),
                'client_id' => $validated['client_id'],
                'type_compte' => $validated['type_compte'],
                'solde_initial' => $validated['solde_initial'] ?? 0,
                'devise' => $validated['devise'],
                'statut' => 'actif',
                'user_id' => auth()->id()
            ]);

            // Si un solde initial est fourni, créer une transaction de dépôt
            if (isset($validated['solde_initial']) && $validated['solde_initial'] > 0) {
                Transaction::create([
                    'reference' => 'DEP-' . strtoupper(Str::random(10)),
                    'type' => 'depot',
                    'montant' => $validated['solde_initial'],
                    'devise' => $validated['devise'],
                    'compte_destinataire_id' => $compte->id,
                    'statut' => 'termine',
                    'date_execution' => now(),
                    'motif' => 'Dépôt initial',
                    'user_id' => auth()->id()
                ]);
            }

            return $compte->load('transactions');
        });

        return response()->json([
            'success' => true,
            'message' => 'Compte créé avec succès',
            'data' => $compte
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/{compte}",
     *     summary="Afficher les détails d'un compte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         required=true,
     *         description="ID du compte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du compte récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
     *         )
     *     )
     * )
     */
    public function show(Compte $compte): JsonResponse
    {
        $this->authorize('view', $compte);

        return response()->json([
            'success' => true,
            'data' => $compte->load(['client', 'transactions' => function ($query) {
                $query->latest()->take(10);
            }])
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/{compte}/solde",
     *     summary="Obtenir le solde d'un compte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         required=true,
     *         description="ID du compte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Solde récupéré avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="solde", type="number", example=1000.50),
     *                 @OA\Property(property="devise", type="string", example="XOF")
     *             )
     *         )
     *     )
     * )
     */
    public function solde(Compte $compte): JsonResponse
    {
        $this->authorize('view', $compte);

        return response()->json([
            'success' => true,
            'data' => [
                'solde' => $compte->solde,
                'devise' => $compte->devise
            ]
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/comptes/{compte}/bloquer",
     *     summary="Bloquer un compte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         required=true,
     *         description="ID du compte à bloquer",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte bloqué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte bloqué avec succès")
     *         )
     *     )
     * )
     */
    public function bloquer(Compte $compte): JsonResponse
    {
        $this->authorize('update', $compte);

        $compte->update(['statut' => 'bloque']);

        return response()->json([
            'success' => true,
            'message' => 'Compte bloqué avec succès'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/comptes/{compte}/debloquer",
     *     summary="Débloquer un compte",
     *     tags={"Comptes"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="compte",
     *         in="path",
     *         required=true,
     *         description="ID du compte à débloquer",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Compte débloqué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte débloqué avec succès")
     *         )
     *     )
     * )
     */
    public function debloquer(Compte $compte): JsonResponse
    {
        $this->authorize('update', $compte);

        $compte->update(['statut' => 'actif']);

        return response()->json([
            'success' => true,
            'message' => 'Compte débloqué avec succès'
        ]);
    }

    /**
     * Génère un numéro de compte unique
     */
    protected function genererNumeroCompte(): string
    {
        do {
            $numero = 'CMPT-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Compte::where('numero_compte', $numero)->exists());

        return $numero;
    }
}
