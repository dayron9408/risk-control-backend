<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="RuleAction",
 *     type="object",
 *     title="Acción de Regla",
 *     description="Acción a ejecutar cuando se viola una regla",
 *     required={"rule_id", "action_type"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="Identificador único",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="rule_id",
 *         type="integer",
 *         description="ID de la regla asociada",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="action_type",
 *         type="string",
 *         enum={"EMAIL", "SLACK", "DISABLE_ACCOUNT", "DISABLE_TRADING"},
 *         description="Tipo de acción a ejecutar",
 *         example="EMAIL"
 *     ),
 *     @OA\Property(
 *         property="config",
 *         type="object",
 *         description="Configuración específica de la acción",
 *         additionalProperties=true,
 *         example={"email_to": "admin@example.com", "subject": "Alerta de riesgo"},
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="order",
 *         type="integer",
 *         description="Orden de ejecución de la acción",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time"
 *     )
 * )
 */
class RuleAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_id',
        'action_type',
        'config',
        'order',
    ];

    protected $casts = [
        'rule_id' => 'integer',
        'action_type' => 'string',
        'config' => 'array',
        'order' => 'integer',
    ];

    /**
     * Tipos de acciones disponibles
     */
    public const ACTION_EMAIL = 'EMAIL';
    public const ACTION_SLACK = 'SLACK';
    public const ACTION_DISABLE_ACCOUNT = 'DISABLE_ACCOUNT';
    public const ACTION_DISABLE_TRADING = 'DISABLE_TRADING';

    /**
     * Relación: Una acción pertenece a una regla
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(RiskRule::class, 'rule_id', 'id');
    }

    /**
     * Obtener tipos de acciones disponibles
     */
    public static function getActionTypes(): array
    {
        return [
            self::ACTION_EMAIL => 'Notificar por Email',
            self::ACTION_SLACK => 'Notificar por Slack',
            self::ACTION_DISABLE_ACCOUNT => 'Deshabilitar Cuenta',
            self::ACTION_DISABLE_TRADING => 'Deshabilitar Trading',
        ];
    }

    /**
     * Ejecutar acción
     */
    public function execute(Incident $incident): bool
    {
        // Este método será implementado en el servicio de acciones
        return true;
    }
}
