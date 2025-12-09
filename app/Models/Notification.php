<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="Notification",
 *     type="object",
 *     title="Notificación",
 *     description="Registro de ejecución de acciones de notificación",
 *     required={"incident_id", "action_type", "status"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="Identificador único",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="incident_id",
 *         type="integer",
 *         description="ID del incidente relacionado",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="action_type",
 *         type="string",
 *         enum={"EMAIL", "SLACK", "DISABLE_ACCOUNT", "DISABLE_TRADING"},
 *         description="Tipo de acción ejecutada",
 *         example="EMAIL"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"PENDING", "EXECUTED", "FAILED"},
 *         description="Estado de la ejecución",
 *         example="EXECUTED"
 *     ),
 *     @OA\Property(
 *         property="details",
 *         type="string",
 *         description="Detalles de la ejecución",
 *         example="Email enviado exitosamente a admin@example.com",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="metadata",
 *         type="object",
 *         description="Metadatos adicionales",
 *         additionalProperties=true,
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="executed_at",
 *         type="string",
 *         format="date-time",
 *         description="Fecha y hora de ejecución",
 *         nullable=true
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
class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_id',
        'action_type',
        'status',
        'details',
        'metadata',
        'executed_at',
    ];

    protected $casts = [
        'incident_id' => 'integer',
        'action_type' => 'string',
        'status' => 'string',
        'metadata' => 'array',
        'executed_at' => 'datetime',
    ];

    /**
     * Estados de ejecución
     */
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_EXECUTED = 'EXECUTED';
    public const STATUS_FAILED = 'FAILED';

    /**
     * Relación: Un log pertenece a un incidente
     */
    public function incident(): BelongsTo
    {
        return $this->belongsTo(Incident::class);
    }

    /**
     * Marcar como ejecutado
     */
    public function markAsExecuted(string $details = null): void
    {
        $this->update([
            'status' => self::STATUS_EXECUTED,
            'executed_at' => now(),
            'details' => $details,
        ]);
    }

    /**
     * Marcar como fallido
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'details' => $error,
        ]);
    }

    /**
     * Verificar si está pendiente
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Verificar si fue ejecutado
     */
    public function isExecuted(): bool
    {
        return $this->status === self::STATUS_EXECUTED;
    }
}
