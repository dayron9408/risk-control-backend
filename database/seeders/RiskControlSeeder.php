<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RiskControlSeeder extends Seeder
{
    public function run(): void
    {
        echo "=== Creando datos de prueba para Risk Control ===\n";

        // Limpiar tablas
        DB::table('notifications')->delete();
        DB::table('incidents')->delete();
        DB::table('rule_actions')->delete();
        DB::table('risk_rules')->delete();
        DB::table('trades')->delete();
        DB::table('accounts')->delete();

        // 1. Crear cuentas con diferentes estados
        $accountIds = [];

        for ($i = 1; $i <= 8; $i++) {
            $login = 21002000 + $i;
            $accountId = DB::table('accounts')->insertGetId([
                'login' => $login,
                'trading_status' => $i <= 6 ? 'enable' : 'disable',
                'status' => $i <= 7 ? 'enable' : 'disable',
                'created_at' => now()->subDays(rand(1, 30)),
                'updated_at' => now(),
            ]);

            $accountIds[] = $accountId;
            echo "✅ Cuenta creada: {$login} (ID: {$accountId})\n";
        }

        // 2. Crear trades variados
        $tradeIds = [];
        $tradeCount = 0;

        foreach ($accountIds as $accountId) {
            // Trades normales cerrados (para promedio)
            for ($j = 1; $j <= 7; $j++) {
                $openTime = Carbon::now()->subDays(rand(1, 5))->subHours(rand(1, 23));
                $closeTime = (clone $openTime)->addMinutes(rand(5, 180)); // Duración normal

                $tradeId = DB::table('trades')->insertGetId([
                    'account_id' => $accountId,
                    'type' => rand(0, 1) ? 'BUY' : 'SELL',
                    'volume' => 1.0, // Volumen base para promedio
                    'open_time' => $openTime,
                    'close_time' => $closeTime,
                    'open_price' => rand(10000, 20000) / 100,
                    'close_price' => rand(10000, 20000) / 100,
                    'status' => 'closed',
                    'created_at' => $openTime,
                    'updated_at' => $closeTime,
                ]);
                $tradeIds[] = $tradeId;
                $tradeCount++;
            }

            // Trade RÁPIDO para regla DURATION (menos de 60s)
            $fastOpen = Carbon::now()->subMinutes(10);
            $fastClose = (clone $fastOpen)->addSeconds(rand(10, 45)); // Menos de 60s

            $fastTradeId = DB::table('trades')->insertGetId([
                'account_id' => $accountId,
                'type' => 'BUY',
                'volume' => 2.5,
                'open_time' => $fastOpen,
                'close_time' => $fastClose,
                'open_price' => 150.50,
                'close_price' => 150.55,
                'status' => 'closed',
                'created_at' => $fastOpen,
                'updated_at' => $fastClose,
            ]);
            $tradeIds[] = $fastTradeId;
            $tradeCount++;

            // Trade con VOLUMEN EXTREMO para regla VOLUME
            $volumeTradeId = DB::table('trades')->insertGetId([
                'account_id' => $accountId,
                'type' => 'SELL',
                'volume' => rand(0, 1) ? 0.1 : 5.0, // Muy bajo o muy alto
                'open_time' => Carbon::now()->subMinutes(15),
                'close_time' => Carbon::now()->subMinutes(10),
                'open_price' => 155.75,
                'close_price' => 155.50,
                'status' => 'closed',
                'created_at' => now()->subMinutes(15),
                'updated_at' => now()->subMinutes(10),
            ]);
            $tradeIds[] = $volumeTradeId;
            $tradeCount++;

            // Trades ABIERTOS para regla OPEN_TRADES
            for ($k = 1; $k <= rand(2, 5); $k++) {
                $openTradeId = DB::table('trades')->insertGetId([
                    'account_id' => $accountId,
                    'type' => rand(0, 1) ? 'BUY' : 'SELL',
                    'volume' => rand(5, 30) / 10,
                    'open_time' => Carbon::now()->subMinutes(rand(5, 25)),
                    'close_time' => null,
                    'open_price' => rand(10000, 20000) / 100,
                    'close_price' => null,
                    'status' => 'open',
                    'created_at' => now()->subMinutes(rand(5, 25)),
                    'updated_at' => now()->subMinutes(rand(5, 25)),
                ]);
                $tradeIds[] = $openTradeId;
                $tradeCount++;
            }
        }

        // 3. Crear reglas de riesgo (3 tipos)
        $rules = [
            [
                'name' => 'Trade Muy Rápido',
                'description' => 'Alerta si un trade dura menos de 60 segundos',
                'type' => 'DURATION',
                'severity' => 'HARD',
                'min_duration_seconds' => 60,
                'min_factor' => null,
                'max_factor' => null,
                'lookback_trades' => null,
                'time_window_minutes' => null,
                'min_open_trades' => null,
                'max_open_trades' => null,
                'incidents_before_action' => null,
                'is_active' => true,
                'created_at' => now()->subDays(10),
                'updated_at' => now(),
            ],
            [
                'name' => 'Control de Volumen',
                'description' => 'Volumen fuera del rango normal (0.5x - 2.0x del promedio histórico)',
                'type' => 'VOLUME',
                'severity' => 'SOFT',
                'min_duration_seconds' => null,
                'min_factor' => 0.5,
                'max_factor' => 2.0,
                'lookback_trades' => 5,
                'time_window_minutes' => null,
                'min_open_trades' => null,
                'max_open_trades' => null,
                'incidents_before_action' => 3,
                'is_active' => true,
                'created_at' => now()->subDays(8),
                'updated_at' => now(),
            ],
            [
                'name' => 'Muchos Trades Abiertos',
                'description' => 'Más de 3 trades abiertos en 30 minutos',
                'type' => 'OPEN_TRADES',
                'severity' => 'SOFT',
                'min_duration_seconds' => null,
                'min_factor' => null,
                'max_factor' => null,
                'lookback_trades' => null,
                'time_window_minutes' => 30,
                'min_open_trades' => null,
                'max_open_trades' => 3,
                'incidents_before_action' => 2,
                'is_active' => true,
                'created_at' => now()->subDays(5),
                'updated_at' => now(),
            ],
        ];

        $ruleIds = [];
        foreach ($rules as $ruleData) {
            $ruleId = DB::table('risk_rules')->insertGetId($ruleData);
            $ruleIds[] = $ruleId;
            echo "✅ Regla creada: {$ruleData['name']} (ID: {$ruleId})\n";
        }

        // 4. Crear acciones para cada regla (manteniendo ORDER)
        $ruleActions = [
            // Regla DURATION (HARD)
            ['rule_id' => $ruleIds[0], 'action_type' => 'EMAIL', 'order' => 1, 'config' => json_encode(['email_to' => 'risk@example.com'])],
            ['rule_id' => $ruleIds[0], 'action_type' => 'DISABLE_TRADING', 'order' => 2, 'config' => null],

            // Regla VOLUME (SOFT)
            ['rule_id' => $ruleIds[1], 'action_type' => 'EMAIL', 'order' => 1, 'config' => json_encode(['email_to' => 'alerts@example.com'])],
            ['rule_id' => $ruleIds[1], 'action_type' => 'SLACK', 'order' => 2, 'config' => json_encode(['channel' => '#risk-alerts'])],

            // Regla OPEN_TRADES (SOFT)
            ['rule_id' => $ruleIds[2], 'action_type' => 'EMAIL', 'order' => 1, 'config' => json_encode(['email_to' => 'manager@example.com'])],
            ['rule_id' => $ruleIds[2], 'action_type' => 'SLACK', 'order' => 2, 'config' => json_encode(['channel' => '#trading-monitor'])],
        ];

        foreach ($ruleActions as $action) {
            DB::table('rule_actions')->insert(array_merge($action, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        // 5. Crear incidentes reales para probar
        $incidentCount = 0;

        // Incidentes de DURATION (trades rápidos)
        foreach ($accountIds as $accountId) {
            $incidentId = DB::table('incidents')->insertGetId([
                'rule_id' => $ruleIds[0], // Regla DURATION
                'account_id' => $accountId,
                'trade_id' => $fastTradeId, // Trade rápido
                'severity' => 'HARD',
                'description' => "Trade cerrado en menos de 60 segundos (duración: 45s)",
                'created_at' => Carbon::now()->subHours(rand(1, 24)),
                'updated_at' => Carbon::now()->subHours(rand(1, 24)),
            ]);
            $incidentCount++;

            // Notificaciones para este incidente
            DB::table('notifications')->insert([
                'incident_id' => $incidentId,
                'action_type' => 'EMAIL',
                'status' => 'EXECUTED',
                'details' => 'Mock email sent to risk@example.com',
                'metadata' => json_encode(['recipient' => 'risk@example.com']),
                'executed_at' => now()->subHours(rand(1, 12)),
                'created_at' => now()->subHours(rand(1, 24)),
                'updated_at' => now()->subHours(rand(1, 12)),
            ]);

            DB::table('notifications')->insert([
                'incident_id' => $incidentId,
                'action_type' => 'DISABLE_TRADING',
                'status' => 'EXECUTED',
                'details' => 'Trading disabled for account ' . (21002000 + array_search($accountId, $accountIds) + 1),
                'metadata' => null,
                'executed_at' => now()->subHours(rand(1, 10)),
                'created_at' => now()->subHours(rand(1, 23)),
                'updated_at' => now()->subHours(rand(1, 10)),
            ]);
        }

        // Incidentes de VOLUME (para algunas cuentas)
        for ($i = 0; $i < 5; $i++) {
            $incidentId = DB::table('incidents')->insertGetId([
                'rule_id' => $ruleIds[1], // Regla VOLUME
                'account_id' => $accountIds[$i],
                'trade_id' => $volumeTradeId,
                'severity' => 'SOFT',
                'description' => "Volumen del trade (5.0) fuera del rango permitido [0.5, 2.0]",
                'created_at' => Carbon::now()->subHours(rand(1, 48)),
                'updated_at' => Carbon::now()->subHours(rand(1, 48)),
            ]);
            $incidentCount++;

        }

        // Incidentes de OPEN_TRADES (para algunas cuentas)
        for ($i = 2; $i < 7; $i++) {
            $incidentId = DB::table('incidents')->insertGetId([
                'rule_id' => $ruleIds[2], // Regla OPEN_TRADES
                'account_id' => $accountIds[$i],
                'trade_id' => null,
                'severity' => 'SOFT',
                'description' => "Cuenta tiene 4 trades abiertos en los últimos 30 minutos (máximo: 3)",
                'created_at' => Carbon::now()->subHours(rand(1, 72)),
                'updated_at' => Carbon::now()->subHours(rand(1, 72)),
            ]);
            $incidentCount++;

            DB::table('notifications')->insert([
                'incident_id' => $incidentId,
                'action_type' => 'EMAIL',
                'status' => 'EXECUTED',
                'details' => 'Mock email sent to manager@example.com',
                'executed_at' => now()->subHours(rand(1, 36)),
                'created_at' => now()->subHours(rand(1, 72)),
                'updated_at' => now()->subHours(rand(1, 36)),
            ]);
        }

        echo "\n ¡Datos creados exitosamente!\n";
        echo "    Resumen:\n";
        echo "   - Cuentas: " . count($accountIds) . "\n";
        echo "   - Trades: {$tradeCount}\n";
        echo "   - Reglas: " . count($rules) . "\n";
        echo "   - Acciones: " . count($ruleActions) . "\n";
        echo "   - Incidentes: {$incidentCount}\n";
        echo "   - Notificaciones: " . ($incidentCount * 2) . " (aproximadamente)\n";

        echo "\n   Datos específicos creados:\n";
        echo "   • Trades rápidos (<60s) para probar regla DURATION\n";
        echo "   • Trades con volumen extremo para probar regla VOLUME\n";
        echo "   • Múltiples trades abiertos para probar regla OPEN_TRADES\n";
        echo "   • Incidentes HARD y SOFT\n";
    }
}
