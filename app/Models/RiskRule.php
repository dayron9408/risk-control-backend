<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @OA\Schema(
 *     schema="RiskRule",
 *     type="object",
 *     title="Regla de Riesgo",
 *     description="Regla configurable para el control de riesgo",
 *     required={"name", "type", "severity", "is_active"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="Identificador único",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nombre de la regla",
 *         example="Duración mínima de trade"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="Descripción de la regla",
 *         example="Regla que verifica si un trade dura menos del tiempo mínimo configurado",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         enum={"DURATION", "VOLUME", "OPEN_TRADES"},
 *         description="Tipo de regla",
 *         example="DURATION"
 *     ),
 *     @OA\Property(
 *         property="severity",
 *         type="string",
 *         enum={"HARD", "SOFT"},
 *         description="Severidad de la regla",
 *         example="HARD"
 *     ),
 *     @OA\Property(
 *         property="min_duration_seconds",
 *         type="integer",
 *         description="Duración mínima en segundos (para reglas tipo DURATION)",
 *         example=60,
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="min_factor",
 *         type="number",
 *         format="float",
 *         description="Factor mínimo para volumen (para reglas tipo VOLUME)",
 *         example=0.5,
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="max_factor",
 *         type="number",
 *         format="float",
 *         description="Factor máximo para volumen (para reglas tipo VOLUME)",
 *         example=2.0,
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="lookback_trades",
 *         type="integer",
 *         description="Cantidad de trades históricos a considerar (para reglas tipo VOLUME)",
 *         example=10,
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="time_window_minutes",
 *         type="integer",
 *         description="Ventana de tiempo en minutos (para reglas tipo OPEN_TRADES)",
 *         example=60,
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="min_open_trades",
 *         type="integer",
 *         description="Mínimo de trades abiertos permitidos",
 *         example=1,
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="max_open_trades",
 *         type="integer",
 *         description="Máximo de trades abiertos permitidos",
 *         example=5,
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="incidents_before_action",
 *         type="integer",
 *         description="Incidentes necesarios antes de ejecutar acción (para reglas SOFT)",
 *         example=3,
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="is_active",
 *         type="boolean",
 *         description="Indica si la regla está activa",
 *         example=true
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
 *     ),
 *     @OA\Property(
 *         property="deleted_at",
 *         type="string",
 *         format="date-time",
 *         nullable=true
 *     )
 * )
 */
class RiskRule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'severity',
        'min_duration_seconds',
        'min_factor',
        'max_factor',
        'lookback_trades',
        'time_window_minutes',
        'min_open_trades',
        'max_open_trades',
        'incidents_before_action',
        'is_active',
    ];

    protected $casts = [
        'min_duration_seconds' => 'integer',
        'min_factor' => 'decimal:2',
        'max_factor' => 'decimal:2',
        'lookback_trades' => 'integer',
        'time_window_minutes' => 'integer',
        'min_open_trades' => 'integer',
        'max_open_trades' => 'integer',
        'incidents_before_action' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Tipos de reglas disponibles
     */
    public const TYPE_DURATION = 'DURATION';
    public const TYPE_VOLUME = 'VOLUME';
    public const TYPE_OPEN_TRADES = 'OPEN_TRADES';

    /**
     * Severidades
     */
    public const SEVERITY_HARD = 'HARD';
    public const SEVERITY_SOFT = 'SOFT';

    /**
     * Relación: Una regla tiene muchas acciones
     */
    public function actions(): HasMany
    {
        return $this->hasMany(RuleAction::class, 'rule_id', 'id');
    }

    /**
     * Relación: Una regla tiene muchos incidentes
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class, 'rule_id', 'id');
    }

    /**
     * Obtener tipos de reglas disponibles
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_DURATION => 'Duración de Trade',
            self::TYPE_VOLUME => 'Consistencia de Volumen',
            self::TYPE_OPEN_TRADES => 'Trades Abiertos',
        ];
    }

    /**
     * Obtener severidades disponibles
     */
    public static function getSeverities(): array
    {
        return [
            self::SEVERITY_HARD => 'Regla Dura',
            self::SEVERITY_SOFT => 'Regla Suave',
        ];
    }

    /**
     * Verificar si la regla está activa
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Verificar si es regla dura
     */
    public function isHardRule(): bool
    {
        return $this->severity === self::SEVERITY_HARD;
    }

    /**
     * Verificar si es regla suave
     */
    public function isSoftRule(): bool
    {
        return $this->severity === self::SEVERITY_SOFT;
    }

    /**
     * Obtener acciones por tipo
     */
    public function getActionsByType(string $actionType): HasMany
    {
        return $this->actions()->where('action_type', $actionType);
    }
}
