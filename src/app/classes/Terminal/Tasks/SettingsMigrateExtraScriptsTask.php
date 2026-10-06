<?php

/**
 * SettingsMigrateExtraScriptsTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Core\Config;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\Utilities\Helpers\ExtraScripts;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * SettingsMigrateExtraScriptsTask.
 *
 * Pasa las opciones `extra_scripts*` de «Identidad y SEO» a entradas de `injected_scripts` (ADR 0032 §1) y las retira.
 * Idempotente: sin opciones que migrar no hace nada, y una entrada con el mismo código no se repite.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SettingsMigrateExtraScriptsTask extends TerminalTaskAbstract
{

    const OLD_OPTION = 'extra_scripts';

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }

        $this->description = new StringArray([
            "Pasa los «Scripts adicionales» de Identidad y SEO (extra_scripts*) a la pantalla «Scripts» y retira las opciones viejas.\r\n",
            "\tEl del idioma por omisión entra activo, en el sitio público y en la cabecera; el de otro idioma, si es distinto, inactivo.\r\n",
            "\tSe puede correr más de una vez: la segunda no hace nada.",
        ]);
        $this->route = "{$startRoute}/settings-migrate-extra-scripts[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'settings-migrate-extra-scripts';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = 'Migrar los scripts adicionales de SEO';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");

        $oldOptions = [];
        foreach (SettingsModel::getConfigurations() as $name => $value) {
            if (is_string($name) && self::isOldOption($name)) {
                $oldOptions[$name] = $value;
            }
        }

        if ($oldOptions === []) {
            echoTerminal("\e[94mINFO:\e[39m no hay opciones '" . self::OLD_OPTION . "*': nada que migrar. No se ha tocado nada.");
            echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
            exit(0);
        }

        $existing = ExtraScripts::entries();
        $plan = self::plan($oldOptions, Config::get_default_lang(), $existing);

        foreach ($plan['added'] as $entry) {
            echoTerminal("\e[34mMIGRADA:\e[39m «{$entry['label']}» · {$entry['zone']} · {$entry['position']} · " . ($entry['active'] ? 'activa' : 'INACTIVA') . ' · ' . mb_strlen($entry['code']) . ' caracteres');
        }
        foreach ($plan['skipped'] as $line) {
            echoTerminal("\e[33mSIN ENTRADA:\e[39m {$line}");
        }

        if ($plan['added'] !== [] && !SettingsModel::setConfigValue(ExtraScripts::CONFIG_NAME, array_merge($existing, $plan['added']))) {
            echoTerminal("\e[31mERROR:\e[39m no se pudo guardar '" . ExtraScripts::CONFIG_NAME . "'. No se retira ninguna opción vieja.");
            exit(1);
        }

        //Se relee antes de borrar: si lo migrado no está guardado, las viejas se quedan.
        $stored = SettingsModel::getConfigValue(ExtraScripts::CONFIG_NAME);
        $storedCodes = array_map(fn($entry) => ExtraScripts::normalizeEntry($entry)['code'] ?? null, is_array($stored) ? $stored : []);
        foreach ($plan['added'] as $entry) {
            if (!in_array($entry['code'], $storedCodes, true)) {
                echoTerminal("\e[31mERROR:\e[39m «{$entry['label']}» no está en lo guardado. No se retira ninguna opción vieja.");
                exit(1);
            }
        }

        foreach (array_keys($oldOptions) as $name) {
            $deleted = SettingsModel::model()->delete(['name' => $name])->execute();
            echoTerminal($deleted ? "\e[34mRETIRADA:\e[39m {$name}" : "\e[31mNO SE PUDO RETIRAR:\e[39m {$name}");
        }

        echoTerminal("\e[94mINFO:\e[39m " . count($plan['added']) . ' entrada(s) nueva(s) en «Scripts»; ' . count($oldOptions) . ' opción(es) vieja(s) tratadas.');
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
        exit(0);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function isOldOption(string $name): bool
    {
        return $name === self::OLD_OPTION || str_starts_with($name, self::OLD_OPTION . '_');
    }

    /**
     * Qué entradas salen de las opciones viejas. Pura: no lee ni escribe la base.
     *
     * @param array<string,mixed> $oldOptions Nombre de la opción => valor
     * @param string $defaultLang
     * @param array<int,array{label:string,zone:string,position:string,active:bool,code:string}> $existing
     * @return array{added:array<int,array{label:string,zone:string,position:string,active:bool,code:string}>,skipped:string[]}
     */
    public static function plan(array $oldOptions, string $defaultLang, array $existing): array
    {
        $langGroup = SettingsController::LANG_GROUP;
        $codes = array_map(fn(array $entry): string => trim($entry['code']), $existing);
        $defaultCode = is_string($oldOptions[self::OLD_OPTION] ?? null) ? trim($oldOptions[self::OLD_OPTION]) : '';
        $added = [];
        $skipped = [];
        ksort($oldOptions);
        foreach ($oldOptions as $name => $value) {
            $code = is_string($value) ? trim($value) : '';
            $isDefault = $name === self::OLD_OPTION || $name === self::OLD_OPTION . "_{$defaultLang}";
            if ($code === '') {
                $skipped[] = "{$name}: vacía";
                continue;
            }
            if (!$isDefault && $code === $defaultCode) {
                $skipped[] = "{$name}: igual que la del idioma por omisión";
                continue;
            }
            if (in_array($code, $codes, true)) {
                $skipped[] = "{$name}: ese código ya está en «Scripts»";
                continue;
            }
            $lang = $isDefault ? $defaultLang : mb_substr($name, mb_strlen(self::OLD_OPTION) + 1);
            $added[] = [
                'label' => $isDefault
                    ? __($langGroup, 'Scripts adicionales (migrados de SEO)')
                    : sprintf(__($langGroup, 'Scripts adicionales, idioma %s (revisar)'), $lang),
                'zone' => ExtraScripts::ZONE_PUBLIC,
                'position' => ExtraScripts::POSITION_HEAD,
                'active' => $isDefault,
                'code' => $code,
            ];
            $codes[] = $code;
        }
        return ['added' => $added, 'skipped' => $skipped];
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new SettingsMigrateExtraScriptsTask($startRoute, $namePrefix);
        return new Route(
            $instance->route,
            $instance->controller,
            $instance->name,
            $instance->method,
            $instance->requireLogin,
            null,
            $instance->rolesAllowed->getArrayCopy(),
            $instance->defaultParamsValues,
            $instance->middlewares
        );
    }

}
