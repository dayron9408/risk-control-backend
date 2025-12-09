<?php

namespace App\Documentation;

/**
 * @OA\Response(
 *     response="ValidationError",
 *     description="Error de validación",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(
 *             property="message",
 *             type="string",
 *             example="Los datos proporcionados son inválidos."
 *         ),
 *         @OA\Property(
 *             property="errors",
 *             type="object",
 *             additionalProperties={
 *                 "type": "array",
 *                 "items": {"type": "string"}
 *             }
 *         )
 *     )
 * )
 *
 * @OA\Response(
 *     response="Unauthorized",
 *     description="No autorizado",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="error", type="string", example="Unauthorized"),
 *         @OA\Property(property="message", type="string", example="Clave API inválida")
 *     )
 * )
 *
 * @OA\Response(
 *     response="NotFound",
 *     description="Recurso no encontrado",
 *     @OA\JsonContent(
 *         type="object",
 *         @OA\Property(property="error", type="string", example="Not Found"),
 *         @OA\Property(property="message", type="string", example="Recurso no encontrado")
 *     )
 * )
 */
class Responses
{
    // Solo anotaciones
}
