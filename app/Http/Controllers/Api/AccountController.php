<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountRequest;
use App\Http\Requests\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Accounts",
 *     description="Gestión de cuentas de trading"
 * )
 */
class AccountController extends Controller
{
    /**
     * Obtener lista de cuentas
     *
     * @OA\Get(
     *     path="/accounts",
     *     summary="Listar cuentas",
     *     tags={"Accounts"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por estado de cuenta",
     *         @OA\Schema(type="string", enum={"enable", "disable"})
     *     ),
     *     @OA\Parameter(
     *         name="trading_status",
     *         in="query",
     *         description="Filtrar por estado de trading",
     *         @OA\Schema(type="string", enum={"enable", "disable"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de cuentas",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/Account")
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
        $query = Account::query();

        // Filtrar por status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrar por trading_status
        if ($request->has('trading_status')) {
            $query->where('trading_status', $request->trading_status);
        }

        // Paginación
        $accounts = $query->paginate($request->get('per_page', 15));

        return response()->json($accounts);
    }

    /**
     * Crear nueva cuenta
     *
     * @OA\Post(
     *     path="/accounts",
     *     summary="Crear cuenta",
     *     tags={"Accounts"},
     *     security={{"apiKey": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"login", "trading_status", "status"},
     *             @OA\Property(property="login", type="integer", example=21002025),
     *             @OA\Property(
     *                 property="trading_status",
     *                 type="string",
     *                 enum={"enable", "disable"},
     *                 example="enable"
     *             ),
     *             @OA\Property(
     *                 property="status",
     *                 type="string",
     *                 enum={"enable", "disable"},
     *                 example="enable"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cuenta creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Account created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Account")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = Account::create($request->validated());

        return response()->json([
            'message' => 'Account created successfully',
            'data' => $account
        ], 201);
    }

    /**
     * Obtener cuenta por ID
     *
     * @OA\Get(
     *     path="/accounts/{id}",
     *     summary="Obtener cuenta por ID",
     *     tags={"Accounts"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la cuenta",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cuenta obtenida",
     *         @OA\JsonContent(ref="#/components/schemas/Account")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Account $account): JsonResponse
    {
        $account->load(['trades' => function ($query) {
            $query->latest();
        }, 'incidents' => function ($query) {
            $query->latest();
        }]);

        return response()->json($account);
    }

    /**
     * Actualizar cuenta
     *
     * @OA\Put(
     *     path="/accounts/{id}",
     *     summary="Actualizar cuenta",
     *     tags={"Accounts"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la cuenta",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="login", type="integer", example=21002025),
     *             @OA\Property(property="trading_status", type="string", enum={"enable", "disable"}),
     *             @OA\Property(property="status", type="string", enum={"enable", "disable"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cuenta actualizada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Account updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Account")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        $account->update($request->validated());

        return response()->json([
            'message' => 'Account updated successfully',
            'data' => $account
        ]);
    }

   /**
     * Eliminar cuenta
     *
     * @OA\Delete(
     *     path="/accounts/{id}",
     *     summary="Eliminar cuenta",
     *     tags={"Accounts"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la cuenta",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cuenta eliminada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Account deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(Account $account): JsonResponse
    {
        $account->delete();

        return response()->json([
            'message' => 'Account deleted successfully'
        ]);
    }

    /**
     * Obtener estado de riesgo de cuenta
     *
     * @OA\Get(
     *     path="/accounts/{id}/risk-status",
     *     summary="Obtener estado de riesgo",
     *     tags={"Accounts"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la cuenta",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estado de riesgo",
     *         @OA\JsonContent(
     *             @OA\Property(property="account_id", type="integer", example=1),
     *             @OA\Property(property="login", type="integer", example=21002025),
     *             @OA\Property(property="status", type="string", example="enable"),
     *             @OA\Property(property="trading_status", type="string", example="enable"),
     *             @OA\Property(
     *                 property="incidents_by_severity",
     *                 type="object",
     *                 @OA\Property(property="HARD", type="integer", example=2),
     *                 @OA\Property(property="SOFT", type="integer", example=5)
     *             ),
     *             @OA\Property(property="open_trades_count", type="integer", example=3),
     *             @OA\Property(property="closed_trades_count", type="integer", example=15),
     *             @OA\Property(
     *                 property="risk_level",
     *                 type="string",
     *                 enum={"LOW", "MEDIUM", "HIGH", "CRITICAL"},
     *                 example="HIGH"
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function riskStatus(Account $account): JsonResponse
    {
        $incidents = $account->incidents()
            ->with('rule')
            ->get();

        $incidentsBySeverity = [
            'HARD' => $incidents->where('severity', 'HARD')->count(),
            'SOFT' => $incidents->where('severity', 'SOFT')->count(),
        ];


        return response()->json([
            'account_id' => $account->id,
            'login' => $account->login,
            'status' => $account->status,
            'trading_status' => $account->trading_status,
            'incidents_by_severity' => $incidentsBySeverity,
            'open_trades_count' => $account->openTrades()->count(),
            'closed_trades_count' => $account->closedTrades()->count(),
            'risk_level' => $this->calculateRiskLevel($incidentsBySeverity),
        ]);
    }

    /**
     * Calculate risk level based on incidents.
     */
    private function calculateRiskLevel(array $incidentsBySeverity): string
    {
        $hardCount = $incidentsBySeverity['HARD'] ?? 0;
        $softCount = $incidentsBySeverity['SOFT'] ?? 0;

        if ($hardCount > 0) {
            return 'CRITICAL';
        } elseif ($softCount >= 3) {
            return 'HIGH';
        } elseif ($softCount >= 1) {
            return 'MEDIUM';
        }

        return 'LOW';
    }

     /**
     * Deshabilitar trading de cuenta
     *
     * @OA\Post(
     *     path="/accounts/{id}/disable-trading",
     *     summary="Deshabilitar trading",
     *     tags={"Accounts"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la cuenta",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Trading deshabilitado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Trading disabled for account"),
     *             @OA\Property(property="data", ref="#/components/schemas/Account")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function disableTrading(Account $account): JsonResponse
    {
        $account->disableTrading();

        return response()->json([
            'message' => 'Trading disabled for account',
            'data' => $account
        ]);
    }

   /**
     * Habilitar trading de cuenta
     *
     * @OA\Post(
     *     path="/accounts/{id}/enable-trading",
     *     summary="Habilitar trading",
     *     tags={"Accounts"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la cuenta",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Trading habilitado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Trading enabled for account"),
     *             @OA\Property(property="data", ref="#/components/schemas/Account")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function enableTrading(Account $account): JsonResponse
    {
        $account->update(['trading_status' => 'enable']);

        return response()->json([
            'message' => 'Trading enabled for account',
            'data' => $account
        ]);
    }

    /**
     * Obtener trades de cuenta
     *
     * @OA\Get(
     *     path="/accounts/{id}/trades",
     *     summary="Obtener trades por cuenta",
     *     tags={"Accounts"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la cuenta",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por estado de trade",
     *         @OA\Schema(type="string", enum={"open", "closed"})
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrar por tipo de trade",
     *         @OA\Schema(type="string", enum={"BUY", "SELL"})
     *     ),
     *     @OA\Parameter(
     *         name="date_from",
     *         in="query",
     *         description="Fecha desde (formato YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
     *     ),
     *     @OA\Parameter(
     *         name="date_to",
     *         in="query",
     *         description="Fecha hasta (formato YYYY-MM-DD)",
     *         @OA\Schema(type="string", format="date")
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
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function trades(Account $account, Request $request): JsonResponse
    {
        $query = $account->trades();

        // Filtrar por status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrar por tipo
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filtrar por fecha
        if ($request->has('date_from')) {
            $query->where('open_time', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('open_time', '<=', $request->date_to);
        }

        $trades = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json($trades);
    }
}
