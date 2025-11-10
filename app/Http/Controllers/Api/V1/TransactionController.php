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
 *     name="Transactions",
 *     description="Gestion des opérations financières"
 * )
 */
class TransactionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/transactions/transfert",
     *     summary="Effectuer un transfert entre comptes",
     *     tags={"Transactions"},
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"compte_emetteur_id", "compte_destinataire_id", "montant", "devise"},
     *             @OA\Property(property="compte_emetteur_id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
     *             @OA\Property(property="compte_destinataire_id", type="string", format="uuid", example="660e8400-e29b-41d4-a716-446655440001"),
     *             @OA\Property(property="montant", type="number", format="float", example=50000),
     *             @OA\Property(property="devise", type="string", example="XOF"),
     *             @OA\Property(property="motif", type="string", example="Paiement de facture")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Transfert effectué avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/Transaction")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation ou solde insuffisant",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="error", type="object", ref="#/components/schemas/ErrorResponse")
     *         )
     *     )
     * )
     */
    public function transfert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'compte_emetteur_id' => 'required|uuid|exists:comptes,id',
            'compte_destinataire_id' => 'required|uuid|exists:comptes,id',
            'montant' => 'required|numeric|min:0.01',
            'devise' => 'required|string|size:3',
            'motif' => 'nullable|string|max:255'
        ]);

        return DB::transaction(function () use ($validated) {
            // Vérifier que les comptes sont différents
            if ($validated['compte_emetteur_id'] === $validated['compte_destinataire_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les comptes émetteur et destinataire doivent être différents'
                ], 422);
            }

            // Récupérer les comptes avec verrouillage pour éviter les accès concurrents
            $compteEmetteur = Compte::lockForUpdate()->findOrFail($validated['compte_emetteur_id']);
            $compteDestinataire = Compte::lockForUpdate()->findOrFail($validated['compte_destinataire_id']);
            
            // Calculer le solde actuel
            $soldeEmetteur = $compteEmetteur->solde;
            
            // Vérifier que le solde est suffisant
            if ($soldeEmetteur < $validated['montant']) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INSUFFICIENT_FUNDS',
                        'message' => 'Solde insuffisant pour effectuer cette opération',
                        'details' => [
                            'solde_disponible' => $soldeEmetteur,
                            'montant_demande' => $validated['montant']
                        ]
                    ]
                ], 422);
            }

            // Créer la transaction
            $transaction = Transaction::create([
                'reference' => 'TXN-' . strtoupper(Str::random(10)),
                'type' => 'transfert',
                'montant' => $validated['montant'],
                'devise' => $validated['devise'],
                'compte_emetteur_id' => $validated['compte_emetteur_id'],
                'compte_destinataire_id' => $validated['compte_destinataire_id'],
                'statut' => 'termine',
                'motif' => $validated['motif'] ?? null,
                'date_execution' => now()
            ]);

            // Mettre à jour les soldes initiaux (pour le calcul du solde)
            $compteEmetteur->decrement('solde_initial', $validated['montant']);
            $compteDestinataire->increment('solde_initial', $validated['montant']);

            // Recharger les modèles pour obtenir les soldes à jour
            $compteEmetteur->refresh();
            $compteDestinataire->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Transfert effectué avec succès',
                'data' => [
                    'transaction' => $transaction->load(['compteEmetteur', 'compteDestinataire']),
                    'nouveau_solde_emetteur' => $compteEmetteur->solde,
                    'nouveau_solde_destinataire' => $compteDestinataire->solde
                ]
            ]);
        });

        // Calculer les frais de transaction (exemple: 1% du montant, minimum 100)
        $frais = max($request->montant * 0.01, 100);
        $montantTotal = $request->montant + $frais;

        // Démarrer une transaction de base de données
        return DB::transaction(function () use ($request, $compteEmetteur, $compteDestinataire, $frais, $montantTotal) {
            // Mettre à jour les soldes
            $compteEmetteur->decrement('solde', $montantTotal);
            $compteDestinataire->increment('solde', $request->montant);

            // Créer la transaction
            $transaction = Transaction::create([
                'type' => 'transfert',
                'montant' => $request->montant,
                'devise' => $request->devise,
                'compte_emetteur_id' => $compteEmetteur->id,
                'compte_destinataire_id' => $compteDestinataire->id,
                'frais' => $frais,
                'statut' => 'reussie',
                'motif' => $request->motif,
                'date_execution' => now(),
                'metadata' => [
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ]
            ]);

            return response()->json([
                'success' => true,
                'data' => $transaction->load(['emetteur', 'destinataire'])
            ]);
        });
    }

    /**
     * @OA\Get(
     *     path="/api/v1/transactions/historique/{compteId}",
     *     summary="Historique des transactions d'un compte",
     *     tags={"Transactions"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         required=true,
     *         description="ID du compte",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des transactions",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Transaction")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             )
     *         )
     *     )
     * )
     */
    public function historique(string $compteId, Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $perPage = $request->input('per_page', 10);

        // Vérifier que l'utilisateur a accès à ce compte
        $compte = Compte::findOrFail($compteId);
        if ($compte->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Accès non autorisé à ce compte',
                    'details' => []
                ]
            ], 403);
        }

        // Récupérer les transactions où le compte est émetteur ou destinataire
        $transactions = Transaction::where('compte_emetteur_id', $compteId)
            ->orWhere('compte_destinataire_id', $compteId)
            ->with(['emetteur', 'destinataire'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions->items(),
            'meta' => [
                'current_page' => $transactions->currentPage(),
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
            ]
        ]);
    }
}

/**
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="reference", type="string", example="TR20231110ABC123"),
 *     @OA\Property(property="type", type="string", example="transfert"),
 *     @OA\Property(property="montant", type="number", format="float", example=50000.00),
 *     @OA\Property(property="devise", type="string", example="XOF"),
 *     @OA\Property(property="frais", type="number", format="float", example=500.00),
 *     @OA\Property(property="statut", type="string", example="reussie"),
 *     @OA\Property(property="motif", type="string", nullable=true, example="Paiement de facture"),
 *     @OA\Property(property="date_execution", type="string", format="date-time"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(
 *         property="emetteur",
 *         type="object",
 *         @OA\Property(property="id", type="string", format="uuid"),
 *         @OA\Property(property="numero_compte", type="string")
 *     ),
 *     @OA\Property(
 *         property="destinataire",
 *         type="object",
 *         @OA\Property(property="id", type="string", format="uuid"),
 *         @OA\Property(property="numero_compte", type="string")
 *     )
 * )
 */
