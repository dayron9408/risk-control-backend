<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RuleAction;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(
 *     name="RuleActions",
 *     description="Tipos de acciones para reglas"
 * )
 */
class RuleActionController extends Controller
{

    /**
     * Obtener tipos de acciones disponibles
     *
     * @OA\Get(
     *     path="/actions/types",
     *     summary="Obtener tipos de acciones",
     *     tags={"RuleActions"},
     *     security={{"apiKey": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Tipos de acciones",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="action_types",
     *                 type="object",
     *                 example={
     *                     "EMAIL": "Notificar por Email",
     *                     "SLACK": "Notificar por Slack",
     *                     "DISABLE_ACCOUNT": "Deshabilitar Cuenta",
     *                     "DISABLE_TRADING": "Deshabilitar Trading"
     *                 }
     *             ),
     *             @OA\Property(
     *                 property="descriptions",
     *                 type="object",
     *                 example={
     *                     "EMAIL": "Enviar notificación por email (mock)",
     *                     "SLACK": "Enviar notificación por Slack (mock)",
     *                     "DISABLE_ACCOUNT": "Deshabilitar completamente la cuenta",
     *                     "DISABLE_TRADING": "Deshabilitar solo el trading de la cuenta"
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function getActionTypes(): JsonResponse
    {
        return response()->json([
            'action_types' => RuleAction::getActionTypes(),
            'descriptions' => [
                'EMAIL' => 'Enviar notificación por email (mock)',
                'SLACK' => 'Enviar notificación por Slack (mock)',
                'DISABLE_ACCOUNT' => 'Deshabilitar completamente la cuenta',
                'DISABLE_TRADING' => 'Deshabilitar solo el trading de la cuenta',
            ]
        ]);
    }
}
