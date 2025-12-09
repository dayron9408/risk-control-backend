<?php

namespace App\Documentation;

/**
 * @OA\Info(
 *     title="Risk Control API",
 *     version="1.0.0",
 *     description="API para sistema de control de riesgo en operaciones de trading",
 *     @OA\Contact(
 *         email="soporte@riskcontrol.com"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Servidor local"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="apiKey",
 *     type="apiKey",
 *     in="header",
 *     name="X-API-KEY"
 * )
 */
class ApiInfo
{
    // Solo anotaciones
}
