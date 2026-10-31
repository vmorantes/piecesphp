<?php

/**
 * @pcsphp-config framework
 * Qué conviene editar aquí: nada: enseña el catálogo de opciones (ADR 0055); las tuyas, ConfigOptions::declare() en tu routes().
 */

use PiecesPHP\Core\ConfigHelpers\ConfigOptions;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;

CliActions::make('config-options', function ($args) {

    $catalog = ConfigOptions::all();
    echoTerminal("\e[33mOpciones declaradas: " . count($catalog) . "\e[39m (nombre · dueño · forma)");
    foreach ($catalog as $name => $option) {
        echoTerminal("  {$name} · {$option['owner']} · " . ($option['shape'] ?? 'sin forma'));
    }

    //Solo los NOMBRES de las filas: un valor puede ser un secreto, y aquí no se imprime nunca.
    $rows = APP_CONFIGURATION_MODULE ? array_keys(SettingsModel::getConfigurations()) : [];
    $undeclared = ConfigOptions::undeclared($rows);
    echoTerminal('');
    echoTerminal("\e[33mFilas de la base: " . count($rows) . '; sin declarar: ' . count($undeclared) . "\e[39m (no se aplican: segunda versión)");
    foreach ($undeclared as $name) {
        echoTerminal("  {$name}");
    }

    return ['success' => true, 'message' => count($undeclared) . ' sin declarar'];

})->setDescription('El catálogo de opciones que la base puede dar (ADR 0055) y las filas de la tabla de configuración que no lo cumplen. Solo nombres, nunca valores.')->setEffects([CliActions::EFFECT_NONE])->register();
