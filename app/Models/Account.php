<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Account",
 *     type="object",
 *     title="Cuenta de Trading",
 *     description="Representa una cuenta de trading en el sistema",
 *     required={"login", "trading_status", "status"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="Identificador único",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="login",
 *         type="integer",
 *         description="Identificador numérico de login para la cuenta de trading",
 *         example=21002025
 *     ),
 *     @OA\Property(
 *         property="trading_status",
 *         type="string",
 *         enum={"enable", "disable"},
 *         description="Estado de actividad de trading",
 *         example="enable"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"enable", "disable"},
 *         description="Estado general de la cuenta",
 *         example="enable"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Fecha y hora de creación de la cuenta"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Fecha y hora de última actualización"
 *     ),
 *     @OA\Property(
 *         property="deleted_at",
 *         type="string",
 *         format="date-time",
 *         description="Fecha y hora de eliminación suave (si aplica)"
 *     )
 * )
 */
class Account extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'login',
        'trading_status',
        'status',
    ];

    protected $casts = [
        'login' => 'integer',
        'trading_status' => 'string',
        'status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación: Una cuenta tiene muchos trades
     */
    public function trades(): HasMany
    {
        return $this->hasMany(Trade::class);
    }

    /**
     * Relación: Una cuenta tiene muchos incidentes
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    /**
     * Relación: Trades abiertos
     */
    public function openTrades(): HasMany
    {
        return $this->trades()->where('status', 'open');
    }

    /**
     * Relación: Trades cerrados
     */
    public function closedTrades(): HasMany
    {
        return $this->trades()->where('status', 'closed');
    }

    /**
     * Verificar si la cuenta está activa
     */
    public function isActive(): bool
    {
        return $this->status === 'enable';
    }

    /**
     * Verificar si el trading está activo
     */
    public function isTradingActive(): bool
    {
        return $this->trading_status === 'enable' && $this->isActive();
    }

    /**
     * Deshabilitar cuenta
     */
    public function disableAccount(): void
    {
        $this->update(['status' => 'disable']);
    }

    /**
     * Deshabilitar trading
     */
    public function disableTrading(): void
    {
        $this->update(['trading_status' => 'disable']);
    }
}
