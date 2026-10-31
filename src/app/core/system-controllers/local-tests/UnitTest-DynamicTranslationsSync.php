<?php

//Volcado de traducciones dinámicas al JSON (también en el mismo segundo) y la fila con la errata DYNAMIC_TRANSCALTIONS.
//Toca current-translations.json y tres registros de configuración: el JSON byte a byte, las filas en crudo, sin log.
//@areas src/app/classes/PiecesPHP/BuiltIn/
//@areas src/app/classes/PiecesPHP/LocalizationSystem/

use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\LocalizationSystem\Packages\JSONTranslationsPackage;
use PiecesPHP\LocalizationSystem\Util\DynamicTranslationsHelper;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/dynamic-translations-sync', function ($args) {

    echoTerminal("\e[33m[TEST:DynamicTranslationsSync] Volcado de traducciones dinámicas de la base al JSON\e[39m");
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

    $config = get_config('DYNAMIC_TRANSLATIONS');
    $dataConfigName = $config['dataConfigName'];
    $lastDateConfigName = $config['lastDateConfigName'];
    $directorio = basepath("app/lang/{$config['folderName']}");
    $jsonActual = "{$directorio}/current-translations.json";
    $logs = fn() => glob("{$directorio}/{$config['filePrefix']}*.json") ?: [];

    $jsonOriginal = is_file($jsonActual) ? file_get_contents($jsonActual) : false;
    $datosOriginales = GenericContentPseudoMapper::getContentData($dataConfigName);
    $fechaOriginal = GenericContentPseudoMapper::getContentData($lastDateConfigName);
    $logsAntes = $logs();

    //Las dos filas de lo pendiente, la buena y la de la errata, en crudo: se reponen tal cual, también si no existían.
    $database = (new \PiecesPHP\Core\BaseModel())->getDatabase();
    $tabla = (new SettingsModel())->getModel()->getTable();
    $prefijoFila = sha1(GenericContentPseudoMapper::class) . '|';
    $filaNueva = $prefijoFila . GenericContentPseudoMapper::CONTENT_DYNAMIC_TRANSLATIONS;
    $filaVieja = $prefijoFila . GenericContentPseudoMapper::LEGACY_CONTENT_DYNAMIC_TRANSLATIONS;
    $crudo = function (string $nombre) use ($database, $tabla): ?string {
        $consulta = $database->prepare("SELECT value FROM `{$tabla}` WHERE name = ?");
        $consulta->execute([$nombre]);
        $valor = $consulta->fetchColumn();
        return $valor === false ? null : (string) $valor;
    };
    $crudosAntes = [$filaNueva => $crudo($filaNueva), $filaVieja => $crudo($filaVieja)];
    $idsAntes = [];
    foreach (array_keys($crudosAntes) as $nombre) {
        $consulta = $database->prepare("SELECT id FROM `{$tabla}` WHERE name = ?");
        $consulta->execute([$nombre]);
        $idsAntes[$nombre] = $consulta->fetchColumn();
    }
    $migracion = dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/databases/actualizaciones/2026-10-26-errata-traducciones-dinamicas.sql';
    $migrar = function () use ($database, $migracion): void {
        $sentencias = preg_split('/;\s*\n/', (string) preg_replace('/^--.*$/m', '', (string) file_get_contents($migracion)));
        foreach (array_filter(array_map('trim', $sentencias ?: [])) as $sentencia) {
            $database->exec($sentencia);
        }
    };
    $ponerVieja = function (array $pendiente) use ($database, $tabla, $filaVieja): void {
        $valor = json_encode([GenericContentPseudoMapper::LEGACY_CONTENT_DYNAMIC_TRANSLATIONS => json_encode($pendiente), 'langData' => new \stdClass()]);
        $database->prepare("INSERT INTO `{$tabla}` (name, value) VALUES (?, ?)")->execute([$filaVieja, $valor]);
    };

    $sincronizar = get_config('add_dynamic_translations');
    $grupo = 'zz-prueba-sync-' . bin2hex(random_bytes(3));
    $clave = 'zz clave de prueba';

    //Prepara el JSON con fecha $json y la base con fecha $base y una traducción pendiente; devuelve [volcada, fecha de la base tras sincronizar].
    $escenario = function (\DateTime $json, \DateTime $base) use ($sincronizar, $grupo, $clave, $dataConfigName, $lastDateConfigName): array {
        $paquete = DynamicTranslationsHelper::getCurrentDynamicTranslationsJSON();
        //RETORNO-IGNORADO: si no se escribiera, «volcada» saldría false y el caso lo diría.
        DynamicTranslationsHelper::saveCurrentDynamicTranslationsJSON(new JSONTranslationsPackage($json, $paquete->getData()));
        GenericContentPseudoMapper::setContentData($dataConfigName, ['es' => [$grupo => [$clave => 'zz valor']]]);
        GenericContentPseudoMapper::setContentData($lastDateConfigName, $base);
        ($sincronizar)();
        $volcada = (DynamicTranslationsHelper::getCurrentDynamicTranslationsJSON()->getData()['es'][$grupo][$clave] ?? null) === 'zz valor';
        $fecha = GenericContentPseudoMapper::getContentData($lastDateConfigName);
        return [$volcada, $fecha instanceof \DateTime ? $fecha->format('Y-m-d H:i:s') : var_export($fecha, true)];
    };

    try {
        $check(is_callable($sincronizar) && $jsonOriginal !== false, 'banco: la lógica del include está en add_dynamic_translations y el JSON existe');

        echoTerminal('[1/3] Mismo segundo');
        $instante = new \DateTime('2026-09-17 10:00:00');
        [$volcada, $fecha] = $escenario($instante, clone $instante);
        $check($volcada && $fecha === '1990-01-01 00:00:00', 's1 la base con la MISMA fecha que el JSON se vuelca y su fecha vuelve a 1990', "volcada " . var_export($volcada, true) . ", fecha {$fecha}");
        echoTerminal(' ');

        echoTerminal('[2/3] Base anterior al JSON');
        $check(file_put_contents($jsonActual, (string) $jsonOriginal) !== false, 'banco: JSON restaurado entre escenarios');
        [$volcada, $fecha] = $escenario(new \DateTime('2026-09-17 10:00:01'), new \DateTime('2026-09-17 10:00:00'));
        $check(!$volcada && $fecha === '2026-09-17 10:00:00', 'a1 la base anterior al JSON no se vuelca y su fecha no cambia', "volcada " . var_export($volcada, true) . ", fecha {$fecha}");
        echoTerminal(' ');

        echoTerminal('[3/4] La fila con la errata DYNAMIC_TRANSCALTIONS');
        $check($tabla === 'pcsphp_app_config' && is_file($migracion), 'banco: la migración existe y nombra la tabla de esta instalación', $tabla);
        $pendiente = ['es' => [$grupo => [$clave => 'zz valor de la fila vieja']]];
        $database->prepare("DELETE FROM `{$tabla}` WHERE name IN (?, ?)")->execute([$filaNueva, $filaVieja]);
        $ponerVieja($pendiente);
        $check(GenericContentPseudoMapper::getContentData($dataConfigName) === $pendiente, 'e1 sin la fila nueva, lo pendiente se lee de la fila con la errata');
        $check($crudo($filaNueva) === null && $crudo($filaVieja) !== null, 'e2 y leer no escribe: ni crea la nueva ni toca la vieja');
        $migrar();
        $check($crudo($filaVieja) === null && GenericContentPseudoMapper::getContentData($dataConfigName) === $pendiente, 'e3 tras la migración solo queda la fila nueva, con lo pendiente');
        //El código nuevo desplegado antes de migrar: su primer guardado deja la fila nueva vacía junto a la vieja.
        $database->prepare("DELETE FROM `{$tabla}` WHERE name IN (?, ?)")->execute([$filaNueva, $filaVieja]);
        $ponerVieja($pendiente);
        GenericContentPseudoMapper::setContentData($dataConfigName, []);
        $migrar();
        $check($crudo($filaVieja) === null && GenericContentPseudoMapper::getContentData($dataConfigName) === $pendiente, 'e4 con la nueva ya creada y vacía, la migración la sustituye por la vieja');
        echoTerminal(' ');

        echoTerminal('[4/4] Si el JSON no se puede escribir, lo pendiente sigue en la base (G7-17)');
        //Una carpeta propia de solo lectura como carpeta de las traducciones: escribir en ella es imposible.
        $carpeta = 'zz-prueba-solo-lectura-' . bin2hex(random_bytes(3));
        $rutaCarpeta = basepath("app/lang/{$carpeta}");
        $configuracionOriginal = $config;
        try {
            $check(mkdir($rutaCarpeta, 0775) && chmod($rutaCarpeta, 0555) && !is_writable($rutaCarpeta), 'banco: una carpeta propia sin escritura', $rutaCarpeta);
            set_config('DYNAMIC_TRANSLATIONS', ['folderName' => $carpeta] + $config);
            $pendiente = ['es' => [$grupo => [$clave => 'zz valor que no se pudo volcar']]];
            GenericContentPseudoMapper::setContentData($dataConfigName, $pendiente);
            GenericContentPseudoMapper::setContentData($lastDateConfigName, new \DateTime());
            ($sincronizar)();
            clearstatcache();
            $check(GenericContentPseudoMapper::getContentData($dataConfigName) === $pendiente, 'w1 la escritura falla y la base NO se vacía');
            $check(glob("{$rutaCarpeta}/*") === [], 'w2 y no queda nada a medias en la carpeta', (string) json_encode(glob("{$rutaCarpeta}/*")));
        } finally {
            set_config('DYNAMIC_TRANSLATIONS', $configuracionOriginal);
            if (is_dir($rutaCarpeta)) {
                //RETORNO-IGNORADO: se abre para poder quitarla; si no se pudiera, la comprobación de abajo lo diría.
                chmod($rutaCarpeta, 0775);
                //RETORNO-IGNORADO: lo comprueba w3.
                rmdir($rutaCarpeta);
            }
            $check(!is_dir($rutaCarpeta), 'w3 la carpeta de la prueba no queda');
        }

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        if ($jsonOriginal !== false) {
            $restaurado = file_put_contents($jsonActual, $jsonOriginal);
            $check($restaurado !== false && file_get_contents($jsonActual) === $jsonOriginal, 'z1 current-translations.json restaurado byte a byte');
        }
        GenericContentPseudoMapper::setContentData($dataConfigName, $datosOriginales);
        GenericContentPseudoMapper::setContentData($lastDateConfigName, $fechaOriginal);
        foreach ($crudosAntes as $nombre => $valor) {
            $database->prepare("DELETE FROM `{$tabla}` WHERE name = ?")->execute([$nombre]);
            if ($valor !== null) {
                $database->prepare("INSERT INTO `{$tabla}` (id, name, value) VALUES (?, ?, ?)")->execute([$idsAntes[$nombre], $nombre, $valor]);
            }
        }
        $idsDespues = [];
        foreach (array_keys($crudosAntes) as $nombre) {
            $consulta = $database->prepare("SELECT id FROM `{$tabla}` WHERE name = ?");
            $consulta->execute([$nombre]);
            $idsDespues[$nombre] = $consulta->fetchColumn();
        }
        $check($crudo($filaNueva) === $crudosAntes[$filaNueva] && $crudo($filaVieja) === $crudosAntes[$filaVieja] && $idsDespues === $idsAntes, 'z3 las dos filas de lo pendiente, como estaban (valor en crudo e id)');
        foreach (array_diff($logs(), $logsAntes) as $nuevo) {
            //RETORNO-IGNORADO: log de volcado creado por la prueba; se comprueba abajo que no queda.
            unlink($nuevo);
        }
        $check(count(array_diff($logs(), $logsAntes)) === 0, 'z2 sin logs de volcado de la prueba');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El volcado de traducciones dinámicas incluye lo guardado en el mismo segundo que el último volcado.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
