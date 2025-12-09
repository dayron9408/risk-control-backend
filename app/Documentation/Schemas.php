<?php

namespace App\Documentation;

/**
 * @OA\Schema(
 *     schema="PaginatedResponse",
 *     type="object",
 *     title="Respuesta Paginada",
 *     @OA\Property(property="data", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="links", type="object"),
 *     @OA\Property(property="meta", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     title="Respuesta de Error",
 *     @OA\Property(property="error", type="string", example="Unauthorized"),
 *     @OA\Property(property="message", type="string", example="Clave API inválida o faltante")
 * )
 *
 * @OA\Schema(
 *     schema="ValidationErrorResponse",
 *     type="object",
 *     title="Respuesta de Error de Validación",
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Los datos proporcionados son inválidos."
 *     ),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         additionalProperties={
 *             "type": "array",
 *             "items": {"type": "string"}
 *         },
 *         example={
 *             "email": {"El campo email es obligatorio."},
 *             "password": {"El campo password debe tener al menos 6 caracteres."}
 *         }
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     type="object",
 *     title="Respuesta Exitosa",
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="Operación completada exitosamente"
 *     ),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         nullable=true
 *     )
 * )
 */
class Schemas
{
    // Solo anotaciones
}
