<?php

/**
 * AIUsageReport.php
 */

namespace PiecesPHP\AI;

use PiecesPHP\AI\Mappers\AIUsageMapper;
use PiecesPHP\AI\Providers\AIProviderRegistry;
use PiecesPHP\Core\BaseModel;

/**
 * AIUsageReport - El «Registro» de Configuración › IA (ADR 0077 §8): el consumo anotado en AIUsageMapper::TABLE, agrupado
 * por función, por registro de proveedor o por modelo y por el comienzo de su periodo, con su coste aproximado. Cuenta y
 * muestra: no limita nada (las cuotas siguen siendo de tokens).
 *
 * El coste de una fila: el que dio el proveedor (reportedCostUsd) si es mayor que 0; si no, sus tokens de entrada y de
 * salida por el precio de ese modelo en ese registro (USD por millón); sin precio, o sin entrada ni salida, null («—»).
 *
 * @package     PiecesPHP\AI
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class AIUsageReport
{
    const GROUP_CAPABILITY = 'capability';
    const GROUP_PROVIDER = 'provider';
    const GROUP_MODEL = 'model';
    const GROUPS = [self::GROUP_CAPABILITY, self::GROUP_PROVIDER, self::GROUP_MODEL];

    /**
     * «Desde»: los últimos N meses (por la última vez que se anotó cada fila, updatedAt) o todo (0).
     */
    const SINCE_ALL = 0;
    const SINCE_MONTHS = [3, 6, 12, self::SINCE_ALL];

    /**
     * Los precios de cada registro guardado (ai_center.providers[].prices): por id de registro, por modelo.
     *
     * @return array<string,array<string,array{input:float,output:float}>>
     */
    public static function pricesByRecord(): array
    {
        $center = AIGateway::normalizeCenter(get_config(AIGateway::CONFIG_NAME));
        $prices = [];
        foreach (is_array($center) && is_array($center['providers'] ?? null) ? $center['providers'] : [] as $entry) {
            if (!is_array($entry) || !is_string($entry['id'] ?? null) || !is_array($entry['prices'] ?? null)) {
                continue;
            }
            foreach ($entry['prices'] as $model => $price) {
                if (is_array($price) && is_numeric($price['input'] ?? null) && is_numeric($price['output'] ?? null)) {
                    $prices[$entry['id']][(string) $model] = ['input' => (float) $price['input'], 'output' => (float) $price['output']];
                }
            }
        }
        return $prices;
    }

    /**
     * El registro de una fila: los convertidos de OpenAI y Mistral se anotan con su nombre de antes
     * (AIGateway::usageProviderName()).
     *
     * @param string $provider
     * @return string
     */
    public static function recordIdOf(string $provider): string
    {
        return match ($provider) {
            AI_OPENAI => AIProviderRegistry::CONVERTED_OPENAI,
            AI_MISTRAL => AIProviderRegistry::CONVERTED_MISTRAL,
            default => $provider,
        };
    }

    /**
     * El coste aproximado de una fila, en USD, o null si no se puede estimar.
     *
     * @param array<string,mixed> $row provider, model, inputTokens, outputTokens y reportedCostUsd
     * @param array<string,array<string,array{input:float,output:float}>> $prices pricesByRecord()
     * @return float|null
     */
    public static function rowCostUsd(array $row, array $prices): ?float
    {
        $reported = (float) ($row['reportedCostUsd'] ?? 0);
        if ($reported > 0) {
            return $reported;
        }
        $price = $prices[self::recordIdOf((string) ($row['provider'] ?? ''))][(string) ($row['model'] ?? '')] ?? null;
        $input = (int) ($row['inputTokens'] ?? 0);
        $output = (int) ($row['outputTokens'] ?? 0);
        if ($price === null || ($input === 0 && $output === 0)) {
            return null;
        }
        return ($input * $price['input'] + $output * $price['output']) / 1000000;
    }

    /**
     * El informe: una fila por grupo y comienzo de periodo (sin las filas AIGateway::PROVIDER_USAGE_ROW, que repiten el
     * consumo por registro), y los totales. cost es la suma de lo que se pudo estimar; partial, si alguna fila no se pudo.
     * providerCostMissing: a la tabla le falta la actualización 2026-11-16-coste-de-la-ia (sin el coste del proveedor).
     *
     * @param string $group Uno de GROUPS; otro, GROUP_CAPABILITY
     * @param int $sinceMonths Uno de SINCE_MONTHS; otro, SINCE_ALL
     * @param int|null $now Por omisión, ahora
     * @return array{rows:list<array{group:string,label:string,periodStart:string,requests:int,input:int,output:int,total:int,cost:?float,partial:bool}>,totals:array{requests:int,input:int,output:int,total:int,cost:?float,partial:bool},providerCostMissing:bool}
     */
    public static function report(string $group, int $sinceMonths, ?int $now = null): array
    {
        $group = in_array($group, self::GROUPS, true) ? $group : self::GROUP_CAPABILITY;
        $sinceMonths = in_array($sinceMonths, self::SINCE_MONTHS, true) ? $sinceMonths : self::SINCE_ALL;
        $database = (new BaseModel())->getDatabase() ?? throw new \RuntimeException('Sin conexión a la base para el registro de la IA.');
        $where = ' FROM `' . AIGateway::usageTable() . '` WHERE capability <> ?';
        $parameters = [AIGateway::PROVIDER_USAGE_ROW];
        if ($sinceMonths !== self::SINCE_ALL) {
            $where .= ' AND updatedAt >= ?';
            $parameters[] = date('Y-m-d H:i:s', (int) strtotime("-{$sinceMonths} months", $now ?? time()));
        }
        //Sin la actualización 2026-11-16-coste-de-la-ia, sin el coste del proveedor (y se avisa); sin columnas más viejas, lanza.
        $providerCostMissing = false;
        try {
            $statement = $database->prepare('SELECT capability, provider, model, periodStart, tokens, inputTokens, outputTokens, reportedCostUsd, requests' . $where);
            //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o lee o lanza; lo que cuenta es el fetchAll().
            $statement->execute($parameters);
        } catch (\PDOException $e) {
            if ($e->getCode() !== '42S22' && (int) ($e->errorInfo[1] ?? 0) !== 1054) {
                throw $e;
            }
            $providerCostMissing = true;
            $statement = $database->prepare('SELECT capability, provider, model, periodStart, tokens, inputTokens, outputTokens, 0 AS reportedCostUsd, requests' . $where);
            //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o lee o lanza; lo que cuenta es el fetchAll().
            $statement->execute($parameters);
        }
        $prices = self::pricesByRecord();
        $empty = fn(): array => ['requests' => 0, 'input' => 0, 'output' => 0, 'total' => 0, 'cost' => null, 'partial' => false];
        $add = function (array $sum, array $row, ?float $cost): array {
            $sum['requests'] += (int) $row['requests'];
            $sum['input'] += (int) $row['inputTokens'];
            $sum['output'] += (int) $row['outputTokens'];
            $sum['total'] += (int) $row['tokens'];
            if ($cost === null) {
                $sum['partial'] = true;
            } else {
                $sum['cost'] = ($sum['cost'] ?? 0.0) + $cost;
            }
            return $sum;
        };
        $groups = [];
        $totals = $empty();
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $value = (string) $row[$group];
            $key = $value . "\0" . (string) $row['periodStart'];
            $cost = self::rowCostUsd($row, $prices);
            $groups[$key] ??= ['group' => $value, 'label' => self::labelOf($group, $value), 'periodStart' => (string) $row['periodStart']] + $empty();
            $groups[$key] = $add($groups[$key], $row, $cost);
            $totals = $add($totals, $row, $cost);
        }
        //Si ninguna fila se pudo estimar, no hay coste parcial: no hay coste.
        $sums = function (array $sum): array {
            $cost = is_float($sum['cost']) ? $sum['cost'] : null;
            return ['requests' => (int) $sum['requests'], 'input' => (int) $sum['input'], 'output' => (int) $sum['output'], 'total' => (int) $sum['total'], 'cost' => $cost, 'partial' => $cost !== null && $sum['partial'] === true];
        };
        $rows = [];
        foreach ($groups as $sum) {
            $rows[] = ['group' => (string) $sum['group'], 'label' => (string) $sum['label'], 'periodStart' => (string) $sum['periodStart']] + $sums($sum);
        }
        usort($rows, fn(array $a, array $b): int => [mb_strtolower($a['label']), $b['periodStart']] <=> [mb_strtolower($b['label']), $a['periodStart']]);
        return ['rows' => $rows, 'totals' => $sums($totals), 'providerCostMissing' => $providerCostMissing];
    }

    /**
     * El nombre de un grupo: la función por su nombre; el registro por su sobrenombre, o el texto guardado si ya no existe;
     * el modelo tal cual.
     *
     * @param string $group
     * @param string $value
     * @return string
     */
    public static function labelOf(string $group, string $value): string
    {
        return match ($group) {
            self::GROUP_CAPABILITY => AICapabilityRegistry::get($value)?->name() ?? $value,
            self::GROUP_PROVIDER => AIProviderRegistry::get(self::recordIdOf($value))?->alias() ?? $value,
            default => $value,
        };
    }
}
