<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Trade",
 *     type="object",
 *     title="Operación de Trading",
 *     description="Representa una operación de trading realizada por un usuario",
 *     required={"account_id", "type", "volume", "open_time", "status"},
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="Identificador único",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="account_id",
 *         type="integer",
 *         description="ID de la cuenta asociada",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="type",
 *         type="string",
 *         enum={"BUY", "SELL"},
 *         description="Tipo de operación",
 *         example="BUY"
 *     ),
 *     @OA\Property(
 *         property="volume",
 *         type="number",
 *         format="float",
 *         description="Volumen de la operación",
 *         example=1.50
 *     ),
 *     @OA\Property(
 *         property="open_time",
 *         type="string",
 *         format="date-time",
 *         description="Fecha y hora de apertura de la operación"
 *     ),
 *     @OA\Property(
 *         property="close_time",
 *         type="string",
 *         format="date-time",
 *         description="Fecha y hora de cierre de la operación",
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="open_price",
 *         type="number",
 *         format="float",
 *         description="Precio de apertura",
 *         example=1.23456
 *     ),
 *     @OA\Property(
 *         property="close_price",
 *         type="number",
 *         format="float",
 *         description="Precio de cierre",
 *         example=1.24567,
 *         nullable=true
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"open", "closed"},
 *         description="Estado de la operación",
 *         example="open"
 *     ),
 *     @OA\Property(
 *         property="metadata",
 *         type="object",
 *         description="Metadatos adicionales de la operación",
 *         additionalProperties=true,
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
class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id',
        'type',
        'volume',
        'open_time',
        'close_time',
        'open_price',
        'close_price',
        'status',
        'metadata',
    ];

    protected $casts = [
        'account_id' => 'integer',
        'type' => 'string',
        'volume' => 'decimal:2',
        'open_time' => 'datetime',
        'close_time' => 'datetime',
        'open_price' => 'decimal:5',
        'close_price' => 'decimal:5',
        'status' => 'string',
        'metadata' => 'array',
    ];

    /**
     * Relación: Un trade pertenece a una cuenta
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Relación: Un trade puede tener incidentes
     */
    public function incidents(): HasMany
    {
        return $this->hasMany(Incident::class);
    }

    /**
     * Calcular duración del trade en segundos
     */
    public function getDurationInSeconds(): ?int
    {
        if ($this->close_time && $this->open_time) {
            return $this->close_time->diffInSeconds($this->open_time);
        }
        return null;
    }

    /**
     * Calcular P&L (Profit & Loss)
     */
    public function getProfitLoss(): ?float
    {
        if ($this->close_price && $this->open_price) {
            $difference = $this->close_price - $this->open_price;
            return $difference * $this->volume;
        }
        return null;
    }

    /**
     * Cerrar trade
     */
    public function closeTrade(float $closePrice): void
    {
        $this->update([
            'close_time' => now(),
            'close_price' => $closePrice,
            'status' => 'closed',
        ]);
    }

    /**
     * Verificar si el trade está abierto
     */
    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    /**
     * Verificar si el trade está cerrado
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }
}
