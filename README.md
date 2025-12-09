Excelente. Ahora tengo toda la información necesaria. Aquí tienes el README completo para el backend Laravel:

# Backend API - Sistema de Control de Riesgo

## Descripción

API REST para el sistema de control de riesgo desarrollado con **Laravel 12**. Proporciona endpoints para gestionar cuentas de trading, operaciones, reglas de riesgo, incidencias y ejecución de acciones automatizadas.

## Tecnologías principales

-   **Laravel 12** - Framework PHP
-   **PHP 8.2+** - Lenguaje de programación
-   **MySQL** - Base de datos
-   **Swagger/OpenAPI** - Documentación API
-   **Composer** - Gestor de dependencias

## Estructura del proyecto

```
app/
├── Console/
│   └── Kernel.php          # Configuración de comandos programados
├── Http/
│   ├── Controllers/Api/    # Controladores de la API
│   │   ├── AccountController.php
│   │   ├── TradeController.php
│   │   ├── RiskRuleController.php
│   │   ├── IncidentController.php
│   │   ├── RuleActionController.php
│   │   └── RiskEvaluationController.php
│   ├── Middleware/
│   │   └── ApiKeyMiddleware.php
│   └── Services/RiskRules/ # Servicios de evaluación de reglas
│       ├── RuleEvaluatorService.php
│       └── Rules/          # Implementaciones de reglas
│           ├── DurationRule.php
│           ├── VolumeRule.php
│           └── OpenTradesRule.php
database/
├── migrations/             # Migraciones de base de datos
│   ├── 2025_12_05_050950_create_accounts_table.php
│   ├── 2025_12_05_051104_create_trades_table.php
│   ├── 2025_12_05_051127_create_risk_rules_table.php
│   ├── 2025_12_05_051206_create_rule_actions_table.php
│   ├── 2025_12_05_051305_create_incidents_table.php
│   └── 2025_12_05_051355_create_incident_logs_table.php
└── seeders/
    └── RiskControlSeeder.php
routes/
├── api.php                # Rutas de la API
└── console.php            # Comandos programados
```

## Configuración

### Prerrequisitos

-   **PHP 8.2+** (requerido por Laravel 12)
-   **Composer** 2.5+
-   **MySQL** 8.0+

### Instalación

1. Clonar el repositorio
2. Instalar dependencias:

```bash
composer install
```

3. Configurar variables de entorno:
4. Configurar base de datos en `.env`
5. Ejecutar migraciones y seeders:

```bash
php artisan migrate
php artisan db:seed --class=RiskControlSeeder
```

6. Iniciar servidor de desarrollo:

```bash
php artisan serve
```

La API estará disponible en `http://localhost:8000`

## Autenticación

La API usa autenticación mediante API Key en header:

```
X-API-KEY: mW60I7w1FxgUSH2QaGQYroiQouIks5QFa2R4FMi6bTZDFDTjjTp81c2i0neLfn9M
```

## Migraciones y base de datos

### Esquema de base de datos

```sql
accounts
├── id
├── login (unique)
├── trading_status (enable/disable)
├── status (enable/disable)
└── timestamps

trades
├── id
├── account_id (FK)
├── type (BUY/SELL)
├── volume
├── open_time
├── close_time
├── open_price
├── close_price
├── status (open/closed)
└── timestamps

risk_rules
├── id
├── name
├── type (DURATION, VOLUME, OPEN_TRADES)
├── severity (HARD, SOFT)
├── parameters (min_duration_seconds, min_factor, max_factor, etc.)
├── incidents_before_action (para reglas SOFT)
├── is_active
└── timestamps

incidents
├── id
├── rule_id (FK)
├── account_id (FK)
├── trade_id (FK, nullable)
├── severity (HARD, SOFT)
├── description
└── timestamps

rule_actions
├── id
├── rule_id (FK)
├── action_type (EMAIL, SLACK, DISABLE_ACCOUNT, DISABLE_TRADING)
├── config (JSON)
├── order
└── timestamps

notifications
├── id
├── incident_id (FK)
├── action_type
├── status (PENDING, EXECUTED, FAILED)
├── details
├── executed_at
└── timestamps
```

### Datos de prueba

El seeder `RiskControlSeeder` crea:

-   8 cuentas con diferentes estados
-   Trades variados para probar todas las reglas
-   3 reglas de riesgo preconfiguradas
-   Incidentes y notificaciones de ejemplo

Ejecutar seeder:

```bash
php artisan db:seed --class=RiskControlSeeder
```

## Comandos Artisan

### Desarrollo

| Comando                     | Descripción                   |
| --------------------------- | ----------------------------- |
| `php artisan serve`         | Inicia servidor de desarrollo |
| `php artisan migrate`       | Ejecuta migraciones           |
| `php artisan migrate:fresh` | Recrea base de datos          |
| `php artisan db:seed`       | Ejecuta seeders               |

### Sistema de riesgo

| Comando                     | Descripción                                    |
| --------------------------- | ---------------------------------------------- |
| `php artisan risk:evaluate` | Evaluación manual de todas las cuentas activas |
| `php artisan schedule:run`  | Ejecuta comandos programados                   |

### Mantenimiento

| Comando                    | Descripción                   |
| -------------------------- | ----------------------------- |
| `php artisan optimize`     | Optimiza la aplicación        |
| `php artisan route:clear`  | Limpia caché de rutas         |
| `php artisan config:clear` | Limpia caché de configuración |

### Tareas programadas

```php
// Evaluación periódica de riesgo cada día a las 3:00 AM
Schedule::call(function () {
    // Evaluar todas las cuentas activas
})->dailyAt('03:00')->name('risk-evaluation')->withoutOverlapping();
```

### Evaluación manual

```bash
php artisan risk:evaluate
```

Este comando evalúa todas las cuentas activas y muestra resultados en consola.

## Documentación API (Swagger)

### Acceso a la documentación

1. Instalar paquetes de documentación (si no están instalados):

```bash
composer require darkaonline/l5-swagger
```

2. Publicar configuración:

```bash
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

3. Generar documentación:

```bash
php artisan l5-swagger:generate
```

4. Acceder a la documentación:
    - **URL:** `http://localhost:8000/api/documentation`
    - **JSON:** `http://localhost:8000/api/docs`

### Endpoints principales

#### Cuentas (Accounts)

-   `GET /api/v1/accounts` - Listar cuentas
-   `POST /api/v1/accounts` - Crear cuenta
-   `GET /api/v1/accounts/{id}/risk-status` - Estado de riesgo
-   `POST /api/v1/accounts/{id}/disable-trading` - Deshabilitar trading

#### Operaciones (Trades)

-   `GET /api/v1/trades` - Listar trades
-   `POST /api/v1/trades` - Crear trade
-   `POST /api/v1/trades/{id}/close` - Cerrar trade

#### Reglas de Riesgo (Risk Rules)

-   `GET /api/v1/rules` - Listar reglas
-   `POST /api/v1/rules` - Crear regla
-   `PUT /api/v1/rules/{id}` - Actualizar regla
-   `POST /api/v1/rules/{id}/toggle-active` - Alternar estado
-   `POST /api/v1/rules/{id}/actions` - Asignar acciones

#### Tipos de reglas implementadas

1. **DURATION** - Operaciones con duración menor a X tiempo

    - Parámetro: `min_duration_seconds`

2. **VOLUME** - Consistencia de volumen de trade

    - Parámetros: `min_factor`, `max_factor`, `lookback_trades`

3. **OPEN_TRADES** - Cantidad de operaciones abiertas en ventana de tiempo
    - Parámetros: `time_window_minutes`, `min_open_trades`, `max_open_trades`

#### Incidencias (Incidents)

-   `GET /api/v1/incidents` - Listar incidencias
-   `GET /api/v1/incidents/statistics` - Estadísticas
-   `GET /api/v1/accounts/{id}/incidents` - Incidencias por cuenta

#### Evaluación (Evaluation)

-   `POST /api/v1/evaluate/account/{id}` - Evaluar cuenta específica
-   `POST /api/v1/evaluate/all-active` - Evaluar todas las cuentas activas

## Tipos de reglas

### 1. Regla DURATION

```json
{
    "type": "DURATION",
    "severity": "HARD",
    "min_duration_seconds": 60,
    "incidents_before_action": null
}
```

### 2. Regla VOLUME

```json
{
    "type": "VOLUME",
    "severity": "SOFT",
    "min_factor": 0.5,
    "max_factor": 2.0,
    "lookback_trades": 5,
    "incidents_before_action": 3
}
```

### 3. Regla OPEN_TRADES

```json
{
    "type": "OPEN_TRADES",
    "severity": "SOFT",
    "time_window_minutes": 30,
    "max_open_trades": 3,
    "incidents_before_action": 2
}
```

## Acciones de riesgo

### Tipos de acciones

-   **EMAIL** - Notificar por email (mock)
-   **SLACK** - Notificar por Slack (mock)
-   **DISABLE_ACCOUNT** - Deshabilitar completamente la cuenta
-   **DISABLE_TRADING** - Deshabilitar solo el trading

### Configuración de acciones

Las acciones se pueden asignar a reglas con orden de ejecución:

```json
{
    "actions": [
        {
            "action_type": "EMAIL",
            "order": 1,
            "config": { "email_to": "admin@example.com" }
        },
        {
            "action_type": "DISABLE_TRADING",
            "order": 2,
            "config": null
        }
    ]
}
```

## Middleware de API Key

### Configuración

La API Key se configura en `.env`:

```env
API_KEY=mW60I7w1FxgUSH2QaGQYroiQouIks5QFa2R4FMi6bTZDFDTjjTp81c2i0neLfn9M
```

### Uso en peticiones

```bash
curl -X GET http://localhost:8000/api/v1/accounts \
  -H "X-API-KEY: mW60I7w1FxgUSH2QaGQYroiQouIks5QFa2R4FMi6bTZDFDTjjTp81c2i0neLfn9M"
```

### Cambiar API Key

1. Actualizar en `.env`
2. Limpiar caché:

```bash
php artisan config:clear
```

##  Evaluación de reglas

### Mecanismos de evaluación

1. **Por evento** - Cuando se cierra un trade
2. **Periódica** - Programada vía scheduler
3. **Manual** - Comando `risk:evaluate`

### Prevención de duplicados

El sistema evita crear incidentes duplicados:

-   **Reglas DURATION/VOLUME**: 10 minutos de prevención
-   **Regla OPEN_TRADES**: 30 minutos de prevención

## Notas de implementación

### Características implementadas

-    API REST completa con documentación Swagger
-    3 tipos de reglas de riesgo configurables
-    4 tipos de acciones automatizadas
-    Evaluación por evento y periódica
-    Prevención de incidentes duplicados
-    Sistema de logging y notificaciones
-    Datos de prueba completos
-    Middleware de autenticación API Key
-    Comandos Artisan para gestión

### Consideraciones de diseño

-   **Separación de responsabilidades**: Controladores, servicios y reglas separados
-   **Extensibilidad**: Fácil agregar nuevos tipos de reglas
-   **Seguridad**: Autenticación por API Key, rate limiting
-   **Rendimiento**: Caching, prevención de evaluaciones duplicadas
-   **Mantenibilidad**: Código documentado, estructura clara

---

**Desarrollado para MMTECH-SOLUTIONS - Sistema de Control de Riesgo**
