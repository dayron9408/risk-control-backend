<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRiskRuleRequest;
use App\Models\RiskRule;
use App\Models\RuleAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="RiskRules",
 *     description="Gestión de reglas de riesgo"
 * )
 */
class RiskRuleController extends Controller
{
    /**
     * Obtener lista de reglas
     *
     * @OA\Get(
     *     path="/rules",
     *     summary="Listar reglas de riesgo",
     *     tags={"RiskRules"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrar por tipo de regla",
     *         @OA\Schema(type="string", enum={"DURATION", "VOLUME", "OPEN_TRADES"})
     *     ),
     *     @OA\Parameter(
     *         name="severity",
     *         in="query",
     *         description="Filtrar por severidad",
     *         @OA\Schema(type="string", enum={"HARD", "SOFT"})
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filtrar por estado activo",
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de reglas",
     *         @OA\JsonContent(
     *             allOf={
     *                 @OA\Schema(ref="#/components/schemas/PaginatedResponse"),
     *                 @OA\Schema(
     *                     @OA\Property(
     *                         property="data",
     *                         type="array",
     *                         @OA\Items(ref="#/components/schemas/RiskRule")
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
        $query = RiskRule::with('actions');

        // Filtros
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $rules = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($rules);
    }

    /**
     * Crear nueva regla
     *
     * @OA\Post(
     *     path="/rules",
     *     summary="Crear regla de riesgo",
     *     tags={"RiskRules"},
     *     security={{"apiKey": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RiskRule")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Regla creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Risk rule created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/RiskRule")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreRiskRuleRequest $request): JsonResponse
    {
        $rule = RiskRule::create($request->validated());

        return response()->json([
            'message' => 'Risk rule created successfully',
            'data' => $rule->load('actions')
        ], 201);
    }

    /**
     * Obtener regla por ID
     *
     * @OA\Get(
     *     path="/rules/{id}",
     *     summary="Obtener regla por ID",
     *     tags={"RiskRules"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la regla",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Regla obtenida",
     *         @OA\JsonContent(ref="#/components/schemas/RiskRule")
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(string $id): JsonResponse
    {
        $rule = RiskRule::with(['actions', 'incidents'])->findOrFail($id);

        return response()->json($rule);
    }



    /**
     * Actualizar regla
     *
     * @OA\Put(
     *     path="/rules/{id}",
     *     summary="Actualizar regla",
     *     tags={"RiskRules"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la regla",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/RiskRule")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Regla actualizada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Risk rule updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/RiskRule")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function update(StoreRiskRuleRequest $request, RiskRule $rule): JsonResponse
    {
        $rule->update($request->validated());

        return response()->json([
            'message' => 'Risk rule updated successfully',
            'data' => $rule->load('actions')
        ]);
    }

    /**
     * Eliminar regla
     *
     * @OA\Delete(
     *     path="/rules/{id}",
     *     summary="Eliminar regla",
     *     tags={"RiskRules"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la regla",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Regla eliminada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Risk rule deleted successfully")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function destroy(RiskRule $rule): JsonResponse
    {
        $rule->delete();

        return response()->json([
            'message' => 'Risk rule deleted successfully'
        ]);
    }

    /**
     * Alternar estado activo de regla
     *
     * @OA\Post(
     *     path="/rules/{id}/toggle-active",
     *     summary="Alternar estado activo",
     *     tags={"RiskRules"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la regla",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Estado actualizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Risk rule status updated"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="is_active", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function toggleActive(RiskRule $rule): JsonResponse
    {
        $rule->update(['is_active' => !$rule->is_active]);

        return response()->json([
            'message' => 'Risk rule status updated',
            'data' => [
                'id' => $rule->id,
                'is_active' => $rule->is_active
            ]
        ]);
    }

    /**
     * Asignar acciones a regla
     *
     * @OA\Post(
     *     path="/rules/{id}/actions",
     *     summary="Asignar acciones a regla",
     *     tags={"RiskRules"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la regla",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"actions"},
     *             @OA\Property(
     *                 property="actions",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     required={"action_type"},
     *                     @OA\Property(
     *                         property="action_type",
     *                         type="string",
     *                         enum={"EMAIL", "SLACK", "DISABLE_ACCOUNT", "DISABLE_TRADING"},
     *                         example="EMAIL"
     *                     ),
     *                     @OA\Property(property="order", type="integer", example=1, nullable=true),
     *                     @OA\Property(
     *                         property="config",
     *                         type="object",
     *                         nullable=true,
     *                         example={"email_to": "admin@example.com"}
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Acciones asignadas",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Actions assigned successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/RiskRule")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function assignActions(RiskRule $rule, Request $request): JsonResponse
    {
        $request->validate([
            'actions' => 'required|array',
            'actions.*.action_type' => 'required|in:EMAIL,SLACK,DISABLE_ACCOUNT,DISABLE_TRADING',
            'actions.*.order' => 'nullable|integer|min:0',
        ]);

        // Eliminar acciones existentes
        $rule->actions()->delete();

        // Crear nuevas acciones
        foreach ($request->actions as $actionData) {
            $rule->actions()->create($actionData);
        }

        return response()->json([
            'message' => 'Actions assigned successfully',
            'data' => $rule->load('actions')
        ]);
    }

    /**
     * Remove an action from a rule.
     */
    public function removeAction(RiskRule $riskRule, RuleAction $action): JsonResponse
    {
        if ($action->rule_id !== $riskRule->id) {
            return response()->json([
                'message' => 'Action does not belong to this rule'
            ], 400);
        }

        $action->delete();

        return response()->json([
            'message' => 'Action removed successfully'
        ]);
    }

    /**
     * Obtener información de tipos de reglas
     *
     * @OA\Get(
     *     path="/rules/types/info",
     *     summary="Obtener información de tipos de reglas",
     *     tags={"RiskRules"},
     *     security={{"apiKey": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Información de tipos",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="types",
     *                 type="object",
     *                 example={
     *                     "DURATION": "Duración de Trade",
     *                     "VOLUME": "Consistencia de Volumen",
     *                     "OPEN_TRADES": "Trades Abiertos"
     *                 }
     *             ),
     *             @OA\Property(
     *                 property="types_info",
     *                 type="object",
     *                 description="Información detallada por tipo"
     *             ),
     *             @OA\Property(
     *                 property="severities",
     *                 type="object",
     *                 example={
     *                     "HARD": "Regla Dura",
     *                     "SOFT": "Regla Suave"
     *                 }
     *             ),
     *             @OA\Property(
     *                 property="action_types",
     *                 type="array",
     *                 description="Tipos de acciones disponibles",
     *                 @OA\Items(type="string", example="EMAIL")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function getTypesInfo(): JsonResponse
    {
        $typesInfo = [
            'DURATION' => [
                'description' => 'Operaciones con duración menor a X tiempo',
                'parameters' => [
                    'min_duration_seconds' => [
                        'type' => 'integer',
                        'required' => true,
                        'description' => 'Duración mínima en segundos'
                    ]
                ]
            ],
            'VOLUME' => [
                'description' => 'Consistencia de volumen de trade',
                'parameters' => [
                    'min_factor' => [
                        'type' => 'decimal',
                        'required' => true,
                        'description' => 'Factor mínimo (ej: 0.5)'
                    ],
                    'max_factor' => [
                        'type' => 'decimal',
                        'required' => true,
                        'description' => 'Factor máximo (ej: 2.0)'
                    ],
                    'lookback_trades' => [
                        'type' => 'integer',
                        'required' => true,
                        'description' => 'Cantidad de trades históricos a considerar'
                    ]
                ]
            ],
            'OPEN_TRADES' => [
                'description' => 'Cantidad de operaciones abiertas en ventana de tiempo',
                'parameters' => [
                    'time_window_minutes' => [
                        'type' => 'integer',
                        'required' => true,
                        'description' => 'Ventana de tiempo en minutos'
                    ],
                    'min_open_trades' => [
                        'type' => 'integer',
                        'required' => false,
                        'description' => 'Mínimo de trades abiertos (opcional)'
                    ],
                    'max_open_trades' => [
                        'type' => 'integer',
                        'required' => false,
                        'description' => 'Máximo de trades abiertos (opcional)'
                    ]
                ]
            ]
        ];

        return response()->json([
            'types' => RiskRule::getTypes(),
            'types_info' => $typesInfo,
            'severities' => RiskRule::getSeverities(),
            'action_types' => RuleAction::getActionTypes()
        ]);
    }
}
