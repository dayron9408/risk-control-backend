<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Account;
use App\Models\RiskRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Incidents",
 *     description="Gestión de incidentes de riesgo"
 * )
 */
class IncidentController extends Controller
{
    /**
     * Obtener lista de incidentes
     *
     * @OA\Get(
     *     path="/incidents",
     *     summary="Listar incidentes",
     *     tags={"Incidents"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="account_id",
     *         in="query",
     *         description="Filtrar por ID de cuenta",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="rule_id",
     *         in="query",
     *         description="Filtrar por ID de regla",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="severity",
     *         in="query",
     *         description="Filtrar por severidad",
     *         @OA\Schema(type="string", enum={"HARD", "SOFT"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Buscar por login de cuenta",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página",
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de incidentes",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Incident")
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 @OA\Property(property="total", type="integer", example=50),
     *                 @OA\Property(property="per_page", type="integer", example=20),
     *                 @OA\Property(property="current_page", type="integer", example=1),
     *                 @OA\Property(property="last_page", type="integer", example=3),
     *                 @OA\Property(property="from", type="integer", example=1),
     *                 @OA\Property(property="to", type="integer", example=20)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $query = Incident::with(['account', 'rule', 'trade']);

        // Filtros
        if ($request->has('account_id')) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->has('rule_id')) {
            $query->where('rule_id', $request->rule_id);
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->has('search') && !empty(trim($request->search))) {
            $searchTerm = '%' . trim($request->search) . '%';
            $query->whereHas('account', function ($q) use ($searchTerm) {
                $q->where('login', 'LIKE', $searchTerm);
            });
        }

        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);

        $incidents = $query->latest()->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $incidents->items(),
            'meta' => [
                'total' => $incidents->total(),
                'per_page' => $incidents->perPage(),
                'current_page' => $incidents->currentPage(),
                'last_page' => $incidents->lastPage(),
                'from' => $incidents->firstItem(),
                'to' => $incidents->lastItem(),
            ]
        ]);
    }

    /**
     * Obtener incidentes por cuenta
     *
     * @OA\Get(
     *     path="/accounts/{account_id}/incidents",
     *     summary="Obtener incidentes por cuenta",
     *     tags={"Incidents"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="account_id",
     *         in="path",
     *         required=true,
     *         description="ID de la cuenta",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Incidentes de la cuenta",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="account",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="login", type="integer", example=21002025)
     *             ),
     *             @OA\Property(
     *                 property="incidents",
     *                 type="object",
     *                 allOf={
     *                     @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *                     @OA\Schema(
     *                         @OA\Property(
     *                             property="data",
     *                             type="array",
     *                             @OA\Items(ref="#/components/schemas/Incident")
     *                         )
     *                     )
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function byAccount(Account $account, Request $request): JsonResponse
    {
        $query = $account->incidents()->with(['rule', 'trade']);

        $incidents = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'account' => [
                'id' => $account->id,
                'login' => $account->login,
            ],
            'incidents' => $incidents
        ]);
    }

    /**
     * Obtener incidentes por regla
     *
     * @OA\Get(
     *     path="/rules/{rule_id}/incidents",
     *     summary="Obtener incidentes por regla",
     *     tags={"Incidents"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="rule_id",
     *         in="path",
     *         required=true,
     *         description="ID de la regla",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         @OA\Schema(type="integer", default=20)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Incidentes de la regla",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="rule",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Duración mínima"),
     *                 @OA\Property(property="type", type="string", example="DURATION")
     *             ),
     *             @OA\Property(
     *                 property="incidents",
     *                 type="object",
     *                 allOf={
     *                     @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *                     @OA\Schema(
     *                         @OA\Property(
     *                             property="data",
     *                             type="array",
     *                             @OA\Items(ref="#/components/schemas/Incident")
     *                         )
     *                     )
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function byRule(RiskRule $riskRule, Request $request): JsonResponse
    {
        $query = $riskRule->incidents()->with(['account', 'trade']);

        $incidents = $query->latest()->paginate($request->get('per_page', 20));

        return response()->json([
            'rule' => [
                'id' => $riskRule->id,
                'name' => $riskRule->name,
                'type' => $riskRule->type,
            ],
            'incidents' => $incidents
        ]);
    }

    /**
     * Obtener estadísticas de incidentes
     *
     * @OA\Get(
     *     path="/incidents/statistics",
     *     summary="Estadísticas de incidentes",
     *     tags={"Incidents"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="time_range",
     *         in="query",
     *         description="Rango de tiempo",
     *         @OA\Schema(type="string", enum={"today", "week", "month", "year"}, default="today")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estadísticas",
     *         @OA\JsonContent(
     *             @OA\Property(property="time_range", type="string", example="today"),
     *             @OA\Property(property="total", type="integer", example=25),
     *             @OA\Property(
     *                 property="by_severity",
     *                 type="object",
     *                 @OA\Property(property="HARD", type="integer", example=10),
     *                 @OA\Property(property="SOFT", type="integer", example=15)
     *             ),
     *             @OA\Property(
     *                 property="top_rules",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="Duración mínima"),
     *                     @OA\Property(property="type", type="string", example="DURATION"),
     *                     @OA\Property(property="count", type="integer", example=8)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function statistics(Request $request): JsonResponse
    {
        $timeRange = $request->get('time_range', 'today');

        $query = Incident::query();

        switch ($timeRange) {
            case 'today':
                $query->whereDate('incidents.created_at', today());
                break;
            case 'week':
                $query->where('incidents.created_at', '>=', now()->subWeek());
                break;
            case 'month':
                $query->where('incidents.created_at', '>=', now()->subMonth());
                break;
            case 'year':
                $query->where('incidents.created_at', '>=', now()->subYear());
                break;
        }

        $total = $query->count();

        $bySeverity = $query->clone()
            ->selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        $byRule = $query->clone()
            ->join('risk_rules', 'incidents.rule_id', '=', 'risk_rules.id')
            ->selectRaw('risk_rules.name, risk_rules.type, COUNT(*) as count')
            ->groupBy('risk_rules.id', 'risk_rules.name', 'risk_rules.type')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return response()->json([
            'time_range' => $timeRange,
            'total' => $total,
            'by_severity' => $bySeverity,
            'top_rules' => $byRule,
        ]);
    }
}
