<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTradeRequest;
use App\Models\Trade;
use App\Models\Account;
use App\Services\RiskRules\RuleEvaluatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Trades",
 *     description="Gestión de operaciones de trading"
 * )
 */
class TradeController extends Controller
{
    public function __construct(
        private RuleEvaluatorService $ruleEvaluator
    ) {
    }

    /**
     * Obtener lista de trades
     *
     * @OA\Get(
     *     path="/trades",
     *     summary="Listar trades",
     *     tags={"Trades"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="account_id",
     *         in="query",
     *         description="Filtrar por ID de cuenta",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por estado",
     *         @OA\Schema(type="string", enum={"open", "closed"})
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrar por tipo",
     *         @OA\Schema(type="string", enum={"BUY", "SELL"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de trades",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/Trade")
     *                     )
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Trade::with('account');

        if ($request->has('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $trades = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json($trades);
    }

    /**
     * Crear nuevo trade
     *
     * @OA\Post(
     *     path="/trades",
     *     summary="Crear trade",
     *     tags={"Trades"},
     *     security={{"apiKey": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"account_id", "type", "volume", "open_time", "status"},
     *             @OA\Property(property="account_id", type="integer", example=1),
     *             @OA\Property(property="type", type="string", enum={"BUY", "SELL"}, example="BUY"),
     *             @OA\Property(property="volume", type="number", format="float", example=1.50),
     *             @OA\Property(property="open_time", type="string", format="date-time"),
     *             @OA\Property(property="close_time", type="string", format="date-time", nullable=true),
     *             @OA\Property(property="open_price", type="number", format="float", example=1.23456),
     *             @OA\Property(property="close_price", type="number", format="float", nullable=true),
     *             @OA\Property(property="status", type="string", enum={"open", "closed"}, example="open"),
     *             @OA\Property(property="metadata", type="object", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Trade creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Trade created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Trade")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreTradeRequest $request): JsonResponse
    {
        $trade = Trade::create($request->validated());

        // Si el trade se crea cerrado, evaluar reglas
        if ($trade->status === 'closed') {
            $this->ruleEvaluator->evaluateTrade($trade);
        }

        return response()->json([
            'message' => 'Trade created successfully',
            'data' => $trade
        ], 201);
    }

    /**
     * Obtener trade por ID
     *
     * @OA\Get(
     *     path="/trades/{id}",
     *     summary="Obtener trade por ID",
     *     tags={"Trades"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del trade",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Trade obtenido",
     *         @OA\JsonContent(ref="#/components/schemas/Trade")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Trade $trade): JsonResponse
    {
        $trade->load(['account', 'incidents']);

        return response()->json($trade);
    }

    /**
     * Actualizar trade
     *
     * @OA\Put(
     *     path="/trades/{id}",
     *     summary="Actualizar trade",
     *     tags={"Trades"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del trade",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="close_time", type="string", format="date-time", nullable=true),
     *             @OA\Property(property="close_price", type="number", format="float", nullable=true, example=1.24567),
     *             @OA\Property(property="status", type="string", enum={"open", "closed"}, nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Trade actualizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Trade updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Trade")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function update(Request $request, Trade $trade): JsonResponse
    {
        $validated = $request->validate([
            'close_time' => 'nullable|date',
            'close_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:open,closed',
        ]);

        $oldStatus = $trade->status;
        $trade->update($validated);

        // Si se cerró el trade, evaluar reglas
        if ($oldStatus === 'open' && $trade->status === 'closed') {
            $this->ruleEvaluator->evaluateTrade($trade);
        }

        return response()->json([
            'message' => 'Trade updated successfully',
            'data' => $trade
        ]);
    }

    /**
     * Eliminar trade
     *
     * @OA\Delete(
     *     path="/trades/{id}",
     *     summary="Eliminar trade",
     *     tags={"Trades"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del trade",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Trade eliminado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Trade deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Trade $trade): JsonResponse
    {
        $trade->delete();

        return response()->json([
            'message' => 'Trade deleted successfully'
        ]);
    }

    /**
     * Cerrar trade
     *
     * @OA\Post(
     *     path="/trades/{id}/close",
     *     summary="Cerrar trade",
     *     tags={"Trades"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del trade",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"close_price"},
     *             @OA\Property(property="close_price", type="number", format="float", example=1.24567)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Trade cerrado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Trade closed successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Trade")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function closeTrade(Trade $trade, Request $request): JsonResponse
    {
        $request->validate([
            'close_price' => 'required|numeric|min:0',
        ]);

        $oldStatus = $trade->status;

        $trade->update([
            'close_time' => now(),
            'close_price' => $request->close_price,
            'status' => 'closed',
        ]);

        // Evaluar reglas después de cerrar
        if ($oldStatus === 'open') {
            $this->ruleEvaluator->evaluateTrade($trade);
        }

        return response()->json([
            'message' => 'Trade closed successfully',
            'data' => $trade
        ]);
    }

    /**
     * Get trades by account.
     */
    public function byAccount(Account $account): JsonResponse
    {
        $trades = $account->trades()
            ->with('incidents')
            ->latest()
            ->paginate(20);

        return response()->json($trades);
    }
}
