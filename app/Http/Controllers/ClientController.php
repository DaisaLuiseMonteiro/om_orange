<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Marchand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Schema(
 *     schema="Marchand",
 *     type="object",
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="nom", type="string"),
 *     @OA\Property(property="telephone", type="string"),
 *     @OA\Property(property="code_marchand", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Client",
 *     type="object",
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="nom", type="string"),
 *     @OA\Property(property="prenom", type="string"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="telephone", type="string"),
 *     @OA\Property(property="marchand_id", type="integer", format="int64"),
 *     @OA\Property(
 *         property="marchand",
 *         type="object",
 *         ref="#/components/schemas/Marchand"
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ClientController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/clients",
     *     summary="Liste des clients",
     *     tags={"Clients"},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des clients récupérée avec succès",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Client")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $clients = Client::with('marchand')->get();
        return response()->json($clients);
    }

    /**
     * @OA\Post(
     *     path="/api/clients",
     *     summary="Créer un nouveau client",
     *     tags={"Clients"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Client")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Client créé avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Client")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'telephone' => 'required|string|unique:clients,telephone',
            'marchand_id' => 'required|exists:marchands,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $client = Client::create($request->all());
        return response()->json($client->load('marchand'), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/clients/{id}",
     *     summary="Afficher un client spécifique",
     *     tags={"Clients"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du client"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/Client")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client non trouvé"
     *     )
     * )
     */
    public function show($id)
    {
        $client = Client::with('marchand')->findOrFail($id);
        return response()->json($client);
    }

    /**
     * @OA\Put(
     *     path="/api/clients/{id}",
     *     summary="Mettre à jour un client",
     *     tags={"Clients"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du client"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Client")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Client mis à jour avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Client")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client non trouvé"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $client = Client::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string|max:255',
            'prenom' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:clients,email,' . $client->id,
            'telephone' => 'sometimes|required|string|unique:clients,telephone,' . $client->id,
            'marchand_id' => 'sometimes|required|exists:marchands,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $client->update($request->all());
        return response()->json($client->load('marchand'));
    }

    /**
     * @OA\Delete(
     *     path="/api/clients/{id}",
     *     summary="Supprimer un client",
     *     tags={"Clients"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du client"
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Client supprimé avec succès"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Client non trouvé"
     *     )
     * )
     */
    public function destroy($id)
    {
        $client = Client::findOrFail($id);
        $client->delete();
        return response()->json(null, 204);
    }

    /**
     * @OA\Get(
     *     path="/api/marchands/{marchandId}/clients",
     *     summary="Lister les clients d'un marchand",
     *     tags={"Marchands"},
     *     @OA\Parameter(
     *         name="marchandId",
     *         in="path",
     *         required=true,
     *         description="ID du marchand"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des clients du marchand",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Client")
     *         )
     *     )
     * )
     */
    public function getByMarchand($marchandId)
    {
        $marchand = Marchand::findOrFail($marchandId);
        $clients = $marchand->clients;
        return response()->json($clients);
    }
}
