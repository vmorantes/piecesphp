<?php

//El prefijo y el gentilicio de cada país (P196 b): la forma del prefijo, las dos columnas, las opciones (solo activos,
//escapadas) y qué se acepta al guardar. Crea países zz- y los retira.
//@areas nucleo

use PiecesPHP\App\Locations\Mappers\CountryMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/country-phone-code', function ($args) {

    echoTerminal("\e[33m[TEST:CountryPhoneCode] El prefijo telefónico y el gentilicio de cada país\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };

    echoTerminal('[1] Prefijos válidos');
    foreach (['+57', '+1', '+1234'] as $valor) {
        $check(CountryMapper::isValidPhoneCode($valor), "1 «{$valor}» es válido");
    }
    echoTerminal(' ');

    echoTerminal('[2] Prefijos inválidos');
    foreach (['57', '+', '+0', '+057', '+12345', '+5a', ' +57', '+57 ', '', '+57' . "\n"] as $valor) {
        $check(!CountryMapper::isValidPhoneCode($valor), '2 «' . addcslashes($valor, "\n") . '» se rechaza');
    }
    echoTerminal(' ');

    echoTerminal('[3] Ida y vuelta por el mapper');
    $table = CountryMapper::PREFIX_TABLE . CountryMapper::TABLE;
    $nombre = 'zz-prueba-pais-' . bin2hex(random_bytes(4));
    $database = (new BaseModel())->getDatabase();
    $id = null;
    try {
        $pais = new CountryMapper();
        $pais->name = $nombre;
        $pais->phoneCode = '+999';
        $pais->demonym = 'zz-prueba-gentilicio';
        $pais->active = CountryMapper::ACTIVE;
        $guardado = $pais->save();
        $id = $pais->id !== null ? (int) $pais->id : null;
        $check($guardado && $id !== null, '3a el país se guarda');
        $releido = $id !== null ? new CountryMapper($id) : null;
        $check(
            $releido !== null && $releido->phoneCode === '+999' && $releido->demonym === 'zz-prueba-gentilicio',
            '3b releído por id, con el prefijo y el gentilicio guardados',
            $releido !== null ? var_export([$releido->phoneCode, $releido->demonym], true) : 'sin fila'
        );
    } finally {
        if ($id !== null) {
            $database->prepare("DELETE FROM `{$table}` WHERE id = ?")->execute([$id]);
        }
        $quedan = $database->prepare("SELECT COUNT(*) FROM `{$table}` WHERE name = ?");
        $quedan->execute([$nombre]);
        $check($id !== null && (int) $quedan->fetchColumn() === 0, 'z1 no queda el país de la prueba');
    }
    echoTerminal(' ');

    echoTerminal('[4] Las opciones de los desplegables y [5] qué se acepta al guardar');
    $base = 'zz-prueba-pais-' . bin2hex(random_bytes(4));
    $ids = [];
    try {
        foreach ([['a', '+998', "{$base}-gentilicio-a", CountryMapper::ACTIVE], ['b', '+998', "{$base}-gentilicio-b", CountryMapper::ACTIVE], ['c', '+997', "{$base}-gentilicio-c", CountryMapper::INACTIVE]] as [$sufijo, $prefijo, $gentilicio, $activo]) {
            $pais = new CountryMapper();
            $pais->name = "{$base}-{$sufijo}";
            $pais->phoneCode = $prefijo;
            $pais->demonym = $gentilicio;
            $pais->active = $activo;
            $pais->save();
            if ($pais->id !== null) {
                $ids[] = (int) $pais->id;
            }
        }
        $check(count($ids) === 3, '4a los tres países de la prueba se guardan');

        $prefijos = CountryMapper::phoneCodeOptions();
        $check(($prefijos['+998'] ?? null) === "+998 · {$base}-a, {$base}-b", '4b «+998» sale una vez, con los dos países activos en orden', var_export($prefijos['+998'] ?? null, true));
        $check(!array_key_exists('+997', $prefijos), '4c «+997», de un país inactivo, no sale');
        $gentilicios = CountryMapper::demonymOptions();
        $check(array_key_exists("{$base}-gentilicio-a", $gentilicios) && array_key_exists("{$base}-gentilicio-b", $gentilicios), '4d salen los dos gentilicios activos');
        $check(!array_key_exists("{$base}-gentilicio-c", $gentilicios), '4e el gentilicio del inactivo no sale');
        $conActual = CountryMapper::phoneCodeOptions('+996', true);
        $check(array_key_first($conActual) === '' && array_key_last($conActual) === '+996' && $conActual['+996'] === '+996', '4f con la opción vacía primero, y un valor guardado que no ofrece nadie, al final y tal cual');
        $conActual = CountryMapper::demonymOptions('zz-no-existe', true);
        $check(array_key_first($conActual) === '' && array_key_last($conActual) === 'zz-no-existe', '4g lo mismo en nacionalidad');
        $malicioso = "<b>'x";
        $conActual = CountryMapper::phoneCodeOptions($malicioso);
        $check(array_key_last($conActual) === escape_html($malicioso) && $conActual[escape_html($malicioso)] === escape_html($malicioso) && !array_key_exists($malicioso, $conActual), '4h el valor guardado sale escapado en clave y texto (la selección se compara contra la clave escapada)', var_export(array_slice($conActual, -1, 1, true), true));

        $casos = [
            ['acceptsPhoneCode', '', null, true, 'vacío'],
            ['acceptsPhoneCode', null, null, true, 'null'],
            ['acceptsPhoneCode', '+998', null, true, 'ofrecido'],
            ['acceptsPhoneCode', '+997', null, false, 'de un país inactivo'],
            ['acceptsPhoneCode', '+996', '+996', true, 'el guardado que no cambia'],
            ['acceptsPhoneCode', '+995', '+996', false, 'otro'],
            ['acceptsDemonym', '', null, true, 'vacío'],
            ['acceptsDemonym', "{$base}-gentilicio-a", null, true, 'ofrecido'],
            ['acceptsDemonym', "{$base}-gentilicio-c", null, false, 'de un país inactivo'],
            ['acceptsDemonym', 'Otras nacionalidad', 'Otras nacionalidad', true, 'el guardado que no cambia'],
            ['acceptsDemonym', 'zz-otra', 'Otras nacionalidad', false, 'otro'],
        ];
        foreach ($casos as [$metodo, $valor, $guardado, $esperado, $nombre]) {
            $obtenido = CountryMapper::$metodo($valor, $guardado);
            $check($obtenido === $esperado, "5 {$metodo}: {$nombre} → " . ($esperado ? 'true' : 'false'));
        }
    } finally {
        foreach ($ids as $idPais) {
            $database->prepare("DELETE FROM `{$table}` WHERE id = ?")->execute([$idPais]);
        }
        $quedan = $database->prepare("SELECT COUNT(*) FROM `{$table}` WHERE name LIKE ?");
        $quedan->execute(["{$base}%"]);
        $check((int) $quedan->fetchColumn() === 0, 'z2 no quedan los países de [4] y [5]');
    }
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El prefijo telefónico y el gentilicio de cada país: la forma del prefijo, la ida y vuelta de las dos columnas, las opciones de los desplegables (solo activos, escapadas) y qué se acepta al guardar. Crea países zz- y los retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
