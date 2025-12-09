<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Services\RiskRules\RuleEvaluatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Evaluation",
 *     description="Evaluación de reglas de riesgo"
 * )
 */
class RiskEvaluationController extends Controller
{
    public function __construct(
        private RuleEvaluatorService $ruleEvaluator
    ) {
    }

    /**
     * Evaluar reglas para una cuenta
     *
     * @OA\Post(
     *     path="/evaluate/account/{account_id}",
     *     summary="Evaluar reglas para cuenta",
     *     tags={"Evaluation"},
     *     security={{"apiKey": {}}},
     *     @OA\Parameter(
     *         name="account_id",
     *         in="path",
     *         required=true,
     *         description="ID de la cuenta",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="trigger",
     *                 type="string",
     *                 enum={"manual", "event", "periodic"},
     *                 example="manual"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Evaluación completada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Evaluation completed"),
     *             @OA\Property(property="account_id", type="integer", example=1),
     *             @OA\Property(property="login", type="integer", example=21002025),
     *             @OA\Property(property="total_rules_evaluated", type="integer", example=3),
     *             @OA\Property(property="violations_found", type="integer", example=1),
     *             @OA\Property(
     *                 property="results",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="rule_id", type="integer", example=1),
     *                     @OA\Property(property="rule_name", type="string", example="Duración mínima"),
     *                     @OA\Property(property="violated", type="boolean", example=true),
     *                     @OA\Property(property="description", type="string", example="Trade cerrado en menos de 60 segundos"),
     *                     @OA\Property(property="actions_executed", type="integer", example=1)
     *                 )
     *             ),
     *             @OA\Property(property="trigger", type="string", example="manual"),
     *             @OA\Property(property="evaluated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function evaluateAccount(Account $account, Request $request): JsonResponse
    {
        $request->validate([
            'trigger' => 'nullable|in:manual,event,periodic',
        ]);

        $results = $this->ruleEvaluator->evaluateAccount($account);

        return response()->json([
            'message' => 'Evaluation completed',
            'account_id' => $account->id,
            'login' => $account->login,
            'total_rules_evaluated' => count($results),
            'violations_found' => count(array_filter($results, fn($r) => $r['violated'])),
            'results' => $results,
            'trigger' => $request->get('trigger', 'manual'),
            'evaluated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Evaluar todas las cuentas activas
     *
     * @OA\Post(
     *     path="/evaluate/all-active",
     *     summary="Evaluar todas las cuentas activas",
     *     tags={"Evaluation"},
     *     security={{"apiKey": {}}},
     *     @OA\RequestBody(
     *         required=false,
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="trigger",
     *                 type="string",
     *                 enum={"manual", "periodic"},
     *                 example="manual"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Evaluación masiva completada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Bulk evaluation completed"),
     *             @OA\Property(property="total_accounts_evaluated", type="integer", example=5),
     *             @OA\Property(property="total_violations_found", type="integer", example=3),
     *             @OA\Property(
     *                 property="results",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="account_id", type="integer", example=1),
     *                     @OA\Property(property="login", type="integer", example=21002025),
     *                     @OA\Property(property="rule_id", type="integer", example=1),
     *                     @OA\Property(property="violated", type="boolean", example=true),
     *                     @OA\Property(property="description", type="string")
     *                 )
     *             ),
     *             @OA\Property(property="trigger", type="string", example="manual"),
     *             @OA\Property(property="evaluated_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function evaluateAllActive(Request $request): JsonResponse
    {
        $request->validate([
            'trigger' => 'nullable|in:manual,periodic',
        ]);

        $results = $this->ruleEvaluator->evaluateAllActiveAccounts();

        return response()->json([
            'message' => 'Bulk evaluation completed',
            'total_accounts_evaluated' => count(array_unique(array_column($results, 'account_id'))),
            'total_violations_found' => count(array_filter($results, fn($r) => $r['violated'])),
            'results' => $results,
            'trigger' => $request->get('trigger', 'manual'),
            'evaluated_at' => now()->toISOString(),
        ]);
    }
}
