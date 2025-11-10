<?php

namespace App\Http\Controllers;

use App\Models\Marchand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MarchandController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/marchands",
     *     summary="Liste des marchands",
     *     tags={"Marchands"},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des marchands récupérée avec succès",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Marchand")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $marchands = Marchand::all();
        return response()->json($marchands);
    }

    /**
     * @OA\Post(
     *     path="/api/marchands",
     *     summary="Créer un nouveau marchand",
     *     tags={"Marchands"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Marchand")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Marchand créé avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Marchand")
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
            'telephone' => 'required|string|unique:marchands,telephone',
            'code_marchand' => 'required|string|unique:marchands,code_marchand|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $marchand = Marchand::create($request->all());
        return response()->json($marchand, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/marchands/{id}",
     *     summary="Afficher un marchand spécifique",
     *     tags={"Marchands"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du marchand"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Marchand trouvé",
     *         @OA\JsonContent(ref="#/components/schemas/Marchand")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Marchand non trouvé"
     *     )
     * )
     */
    public function show($id)
    {
        $marchand = Marchand::findOrFail($id);
        return response()->json($marchand);
    }

    /**
     * @OA\Put(
     *     path="/api/marchands/{id}",
     *     summary="Mettre à jour un marchand",
     *     tags={"Marchands"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du marchand"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/Marchand")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Marchand mis à jour avec succès",
     *         @OA\JsonContent(ref="#/components/schemas/Marchand")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Marchand non trouvé"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        $marchand = Marchand::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|required|string|max:255',
            'telephone' => 'sometimes|required|string|unique:marchands,telephone,' . $marchand->id,
            'code_marchand' => 'sometimes|required|string|unique:marchands,code_marchand,' . $marchand->id . '|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $marchand->update($request->all());
        return response()->json($marchand);
    }

    /**
     * @OA\Delete(
     *     path="/api/marchands/{id}",
     *     summary="Supprimer un marchand",
     *     tags={"Marchands"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID du marchand"
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Marchand supprimé avec succès"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Marchand non trouvé"
     *     )
     * )
     */
    public function destroy($id)
    {
        $marchand = Marchand::findOrFail($id);
        $marchand->delete();
        return response()->json(null, 204);
    }
}
