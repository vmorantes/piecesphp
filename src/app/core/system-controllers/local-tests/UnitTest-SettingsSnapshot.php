<?php

//La foto de las opciones por petición web (ADR 0080), contra readFromDatabase() y el N+1 de antes; cuenta consultas.
//Escribe opciones zz-snapshot-… por PDO y por la API, y las retira siempre.
//@areas src/app/classes/PiecesPHP/Settings/
//@areas src/app/classes/PiecesPHP/SystemStatus/

use DataImportExportUtility\Controllers\DataTransferController;
use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\ConfigHelpers\ConfigOptions;
use PiecesPHP\Core\Crypto\AppKeyring;
use PiecesPHP\Core\Crypto\EncryptedData;
use PiecesPHP\Core\Crypto\KeyRotation;
use PiecesPHP\Core\Crypto\Sealed;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\SystemStatus\SystemAlert;
use PiecesPHP\SystemStatus\SystemAlertRegistry;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/settings-snapshot', function ($args) {

    echoTerminal("\e[33m[TEST:SettingsSnapshot] Las opciones se leen una vez por petición web y la foto se olvida al escribir\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
        return $condition;
    };

    $bd = (new BaseModel())->getDatabase();
    if ($bd === null) {
        $check(false, 'p0 hay conexión a la base');
        return ['success' => false, 'message' => '0/1'];
    }
    $sql = function (string $query, array $values = []) use ($bd): int {
        $statement = $bd->prepare($query);
        if (!$statement->execute($values)) {
            throw new \RuntimeException('No se ejecutó: ' . $query);
        }
        return $statement->rowCount();
    };
    $plantar = fn (string $name, ?string $value): int => $sql('INSERT INTO `pcsphp_app_config` (`name`, `value`) VALUES (?, ?)', [$name, $value]);
    $cambiar = fn (string $name, ?string $value): int => $sql('UPDATE `pcsphp_app_config` SET `value` = ? WHERE `name` = ?', [$value, $name]);
    $quedan = function () use ($bd): int {
        $statement = $bd->prepare('SELECT COUNT(*) FROM `pcsphp_app_config` WHERE `name` LIKE ?');
        $statement->execute(['zz-snapshot-%']);
        return (int) $statement->fetchColumn();
    };
    $preguntas = fn (): int => (int) $bd->query("SHOW SESSION STATUS LIKE 'Questions'")->fetch(\PDO::FETCH_NUM)[1];
    $consultas = function (callable $hacer) use ($preguntas): int {
        $antes = $preguntas();
        $hacer();
        return $preguntas() - $antes - 1;
    };
    $foto = new \ReflectionProperty(SettingsModel::class, 'snapshot');
    //El cuerpo de getConfigurations() antes del ADR 0080: el oráculo.
    $antiguo = function (): array {
        $model = (new SettingsModel())->getModel();
        $model->select()->execute();
        $rows = $model->result();
        $rows = is_array($rows) ? $rows : [];
        $data = [];
        foreach ($rows as $row) {
            //Una fila que ningún nombre alcanza (WhereItem hace trim()) hacía lanzar al N+1: la nueva la salta.
            if (rtrim((string) $row->name, ' ') !== trim((string) $row->name)) {
                continue;
            }
            $mapper = new SettingsModel($row->name);
            $data[$mapper->name] = $mapper->value;
        }
        return $data;
    };
    $src = rtrim(str_replace('\\', '/', basepath('')), '/');
    $tokensDe = function (string $file): array {
        return array_values(array_filter(\PhpToken::tokenize((string) file_get_contents($file)), fn (\PhpToken $t): bool => !$t->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])));
    };
    $cuerpo = function (array $tokens, string $function): ?array {
        $total = count($tokens);
        for ($i = 0; $i < $total - 1; $i++) {
            if ($tokens[$i]->id === T_FUNCTION && $tokens[$i + 1]->text === $function) {
                $j = $i;
                while ($j < $total && $tokens[$j]->text !== '{') {
                    $j++;
                }
                $depth = 0;
                for ($k = $j; $k < $total; $k++) {
                    $text = $tokens[$k]->text;
                    $depth += in_array($text, ['{', '${'], true) || $tokens[$k]->id === T_CURLY_OPEN || $tokens[$k]->id === T_DOLLAR_OPEN_CURLY_BRACES ? 1 : ($text === '}' ? -1 : 0);
                    if ($depth === 0) {
                        return array_slice($tokens, $j + 1, $k - $j - 1);
                    }
                }
            }
        }
        return null;
    };
    $textos = fn (?array $tokens): array => array_map(fn (\PhpToken $t): string => $t->text, $tokens ?? []);
    $modelFile = "{$src}/app/classes/PiecesPHP/Settings/ORM/SettingsModel.php";

    $p = 'zz-snapshot-';
    $sufijo = bin2hex(random_bytes(3));
    $sellado = Sealed::seal('zz-snapshot-sellado-sintetico', Sealed::PURPOSE_CONFIG_SECRET);
    $filas = [
        "{$p}int-{$sufijo}" => '5',
        "{$p}float-{$sufijo}" => '5.0',
        "{$p}grande-{$sufijo}" => '12345678901234567890',
        "{$p}true-{$sufijo}" => 'true',
        "{$p}false-{$sufijo}" => 'false',
        "{$p}null-texto-{$sufijo}" => 'null',
        "{$p}null-columna-{$sufijo}" => null,
        "{$p}vacia-{$sufijo}" => '',
        "{$p}comillas-{$sufijo}" => '""',
        "{$p}roto-{$sufijo}" => '{roto',
        "{$p}lista-{$sufijo}" => '[]',
        "{$p}objeto-{$sufijo}" => '{}',
        "{$p}anidado-{$sufijo}" => '{"a":{"b":[1,2,{"c":"ñ"}]},"d":null}',
        "{$p}unicode-{$sufijo}" => "ñandú 🦜 n\u{0303} e\u{0301}",
        "{$p}sellado-{$sufijo}" => $sellado,
        "{$p}secreto-claro-{$sufijo}" => 'zz-snapshot-secreto-sintetico-en-claro',
        "{$p}pad-{$sufijo} " => '"con espacio final"',
        "{$p}tab-{$sufijo}\t" => '"con tabulador final"',
        "{$p}Case-{$sufijo}" => '"mayúscula"',
        "{$p}case-{$sufijo}" => '"minúscula"',
        "{$p}centinela-{$sufijo}" => '"inicial"',
    ];
    $centinela = "{$p}centinela-{$sufijo}";
    $previo = SettingsModel::swapCacheForTesting(true);
    $alertas = null;
    $fijadas = null;

    try {
        echoTerminal('[p] Precondiciones');
        foreach ($filas as $name => $value) {
            $plantar($name, $value);
        }
        $collation = $bd->query("SELECT `COLLATION_NAME` FROM `information_schema`.`COLUMNS` WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'pcsphp_app_config' AND `COLUMN_NAME` = 'name'")->fetchColumn();
        $check($collation === 'utf8mb4_bin', 'p1 la columna name es utf8mb4_bin, como dice el esquema', (string) $collation);
        $check($quedan() === count($filas), 'p2 las filas zz-snapshot- están plantadas', (string) $quedan());
        echoTerminal(' ');

        echoTerminal('[t1] Cada opción, igual por la foto que por readFromDatabase() y que por el N+1 de antes');
        SettingsModel::forgetCache();
        $viejo = $antiguo();
        $distintas = [];
        foreach (array_keys($viejo) as $name) {
            $name = (string) $name;
            $porFoto = serialize(SettingsModel::getConfigValue($name));
            if ($porFoto !== serialize(SettingsModel::readFromDatabase($name)) || $porFoto !== serialize($viejo[$name])) {
                $distintas[] = $name;
            }
        }
        $zzVistas = count(array_filter(array_keys($viejo), fn ($n): bool => str_starts_with((string) $n, $p)));
        $check($zzVistas === count($filas) - 1, 't1 CANARIO: el oráculo ve todas las zz-snapshot- menos la del tabulador', "{$zzVistas} de " . count($filas));
        $check($foto->getValue() !== null, 't1 CANARIO: la foto está en uso tras leer');
        $check($distintas === [], 't1 DISCRIMINANTE: ' . count($viejo) . ' opciones, todas iguales con serialize()', 'distintas: ' . implode(', ', $distintas));
        $check(SettingsModel::getConfigValue("{$p}null-columna-{$sufijo}") === null && SettingsModel::getConfigValue("{$p}roto-{$sufijo}") === '{roto'
            && SettingsModel::getConfigValue("{$p}sellado-{$sufijo}") === $sellado && SettingsModel::getConfigValue("{$p}anidado-{$sufijo}") instanceof \stdClass,
            't1 la columna NULL da null, «{roto» la cadena, la sellada tal cual y el JSON anidado un objeto');
        echoTerminal(' ');

        echoTerminal('[t2] Se busca como la base: utf8mb4_bin ignora solo los espacios finales');
        $pad = "{$p}pad-{$sufijo}";
        $tab = "{$p}tab-{$sufijo}";
        $case = "{$p}case-{$sufijo}";
        $pares = [$pad, "{$pad} ", $tab, "{$tab}\t", " {$case}", "{$case}\t", "{$case}\n", "\t{$case} ", "{$p}Case-{$sufijo}", $case, "{$p}CASE-{$sufijo}"];
        $t2 = [];
        foreach ($pares as $name) {
            $t2[] = serialize(SettingsModel::getConfigValue($name)) === serialize(SettingsModel::readFromDatabase($name));
        }
        $check(SettingsModel::getConfigValue($pad) === 'con espacio final' && SettingsModel::getConfigValue("{$pad} ") === 'con espacio final', 't2 «pad» se encuentra con y sin el espacio final');
        $check(SettingsModel::getConfigValue($tab) === null && SettingsModel::getConfigValue("{$tab}\t") === null, 't2 la fila guardada con tabulador final no se encuentra ni con él ni sin él, como hoy');
        $check(SettingsModel::getConfigValue(" {$case}") === 'minúscula' && SettingsModel::getConfigValue("\t{$case} ") === 'minúscula', 't2 el nombre buscado se recorta entero, como WhereItem');
        $check(SettingsModel::getConfigValue("{$p}Case-{$sufijo}") === 'mayúscula' && SettingsModel::getConfigValue("{$p}case-{$sufijo}") === 'minúscula' && SettingsModel::getConfigValue("{$p}CASE-{$sufijo}") === null, 't2 las mayúsculas cuentan');
        $check(!in_array(false, $t2, true), 't2 DISCRIMINANTE: los ' . count($pares) . ' nombres, como readFromDatabase()', (string) json_encode($t2));
        echoTerminal(' ');

        echoTerminal('[t3] getConfigurations(): las mismas claves crudas, el mismo orden y los mismos valores que el N+1');
        SettingsModel::forgetCache();
        $nuevo = SettingsModel::getConfigurations();
        $check(array_keys($nuevo) === array_keys($viejo), 't3 las mismas claves en el mismo orden', count($nuevo) . ' / ' . count($viejo));
        $check(array_key_exists("{$pad} ", $nuevo) && !array_key_exists($pad, $nuevo), 't3 la clave de «pad» es la guardada, con su espacio');
        $check(serialize($nuevo) === serialize($viejo), 't3 DISCRIMINANTE: serialize() igual');
        $check(!array_key_exists("{$tab}\t", $nuevo) && !array_key_exists('', $nuevo), 't3 la fila del tabulador no aparece, y no lanza');
        echoTerminal(' ');

        echoTerminal('[t4] Cada lectura decodifica: objetos nuevos');
        $anidado = "{$p}anidado-{$sufijo}";
        $uno = SettingsModel::getConfigValue($anidado);
        $dos = SettingsModel::getConfigValue($anidado);
        if ($uno instanceof \stdClass) {
            $uno->a = 'zz-cambiado';
        }
        $tres = SettingsModel::getConfigValue($anidado);
        $check(is_object($uno) && is_object($dos) && $uno !== $dos, 't4 dos lecturas dan objetos distintos');
        $check(is_object($tres) && ($tres->a ?? null) instanceof \stdClass, 't4 DISCRIMINANTE: cambiar uno no altera la siguiente lectura');
        echoTerminal(' ');

        echoTerminal('[t5] Cada camino de escritura olvida la foto');
        $ronda = 0;
        $olvida = function (string $caso, callable $escribir) use (&$ronda, $centinela, $cambiar, $foto, $check): void {
            $ronda++;
            $fresco = "fresco-{$ronda}";
            SettingsModel::getConfigValue($centinela);
            $cambiar($centinela, (string) json_encode($fresco));
            $rancia = SettingsModel::getConfigValue($centinela) !== $fresco;
            $lanzo = '';
            try {
                $escribir();
            } catch (\Throwable $e) {
                $lanzo = get_class($e);
            }
            $check($rancia && $foto->getValue() === null && SettingsModel::getConfigValue($centinela) === $fresco, "t5 {$caso}",
                'rancia antes: ' . var_export($rancia, true) . ($lanzo !== '' ? ", lanzó {$lanzo}" : ''));
        };
        $existe = "{$p}set-existe-{$sufijo}";
        $plantar($existe, '"antes"');
        $olvida('setConfigValue() sobre una que existe (update)', fn () => SettingsModel::setConfigValue($existe, 'después'));
        $check(SettingsModel::getConfigValue($existe) === 'después', 't5 y se lee el valor nuevo');
        $nueva = "{$p}set-nueva-{$sufijo}";
        $olvida('setConfigValue() sobre una que no existe (save)', fn () => SettingsModel::setConfigValue($nueva, 'creada'));
        $check(SettingsModel::getConfigValue($nueva) === 'creada', 't5 y se lee la nueva');
        $olvida('save() de una instancia', function () use ($p, $sufijo): void {
            $instance = new SettingsModel();
            $instance->name = "{$p}save-{$sufijo}";
            $instance->value = 'guardada';
            $instance->save();
        });
        $olvida('update() de una instancia', function () use ($existe): void {
            $instance = new SettingsModel($existe);
            $instance->value = 'por update';
            $instance->update();
        });
        $check(SettingsModel::getConfigValue($existe) === 'por update', 't5 y se lee lo del update()');
        $olvida('deleteConfigValue()', fn () => SettingsModel::deleteConfigValue($nueva));
        $check(SettingsModel::getConfigValue($nueva) === null && !SettingsModel::optionExists($nueva), 't5 y la borrada ya no está');
        $olvida('model() (quien lo pide puede escribir)', fn () => SettingsModel::model());
        $olvida('forgetCache()', fn () => SettingsModel::forgetCache());

        $secreta = "{$p}secreta-{$sufijo}";
        ConfigOptions::declare($secreta, 'zz-suite-settings-snapshot', ConfigOptions::SHAPE_SECRET);
        $fijadas = ConfigOptions::swapFixedByFileForTesting([]);
        $plantar($secreta, 'zz-snapshot-clave-en-claro');
        $olvida('ConfigOptions sella lo de antes (sealLegacySecret)', fn () => ConfigOptions::applyDeclared([$secreta => 'zz-snapshot-clave-en-claro']));
        $guardada = SettingsModel::getConfigValue($secreta);
        $check(is_string($guardada) && Sealed::isSealed($guardada) && Sealed::open($guardada, Sealed::PURPOSE_CONFIG_SECRET) === 'zz-snapshot-clave-en-claro', 't5 y la foto da la sellada, no la de antes');

        $rotada = "{$p}rotada-{$sufijo}";
        $plantar($rotada, 'zz-claro:zz-snapshot-rotada');
        $llavero = new AppKeyring(AppKeyring::generateKey());
        $sitio = ['zz-snapshot' => new EncryptedData('zz-snapshot', 'zz-snapshot', EncryptedData::KIND_OPTION, $rotada, null, 'id', static fn (string $v): ?string => str_starts_with($v, 'zz-claro:') ? substr($v, 9) : null)];
        $olvida('KeyRotation::sealLegacy() sobre una opción zz', fn () => (new KeyRotation($bd, $sitio))->sealLegacy($llavero));
        $rotadaLeida = SettingsModel::getConfigValue($rotada);
        $check(is_string($rotadaLeida) && Sealed::isSealed($rotadaLeida) && Sealed::open($rotadaLeida, 'zz-snapshot', $llavero) === 'zz-snapshot-rotada', 't5 y la foto da la que selló KeyRotation');

        $olvida('un update() que lanza (clave duplicada) olvida igual', function () use ($existe, $centinela): void {
            $instance = new SettingsModel($existe);
            $instance->name = $centinela;
            $instance->update();
        });
        $check(SettingsModel::getConfigValue($existe) === 'por update', 't5 el update() que lanzó no cambió nada');
        echoTerminal(' ');

        echoTerminal('[t5b] cleanAll() (TRUNCATE, no se ejecuta aquí) olvida tras execute, en un finally');
        $tokens = $tokensDe($modelFile);
        $cleanAll = $textos($cuerpo($tokens, 'cleanAll'));
        $execute = array_search('execute', $cleanAll, true);
        $finally = array_search('finally', $cleanAll, true);
        $forget = array_search('forgetCache', $cleanAll, true);
        $check($cleanAll !== [] && $execute !== false && $finally !== false && $forget !== false && $execute < $finally && $finally < $forget,
            't5b forgetCache va en el finally, después del execute', var_export([$execute, $finally, $forget], true));
        echoTerminal(' ');

        echoTerminal('[t6] En la terminal, sin interruptor, la foto está apagada');
        SettingsModel::swapCacheForTesting(null);
        SettingsModel::getConfigValue($centinela);
        $cambiar($centinela, '"terminal"');
        $check(SettingsModel::getConfigValue($centinela) === 'terminal' && $foto->getValue() === null, 't6 DISCRIMINANTE: un UPDATE por PDO se ve en la lectura siguiente');
        SettingsModel::swapCacheForTesting(true);
        $cacheEnabled = $textos($cuerpo($tokens, 'cacheEnabled'));
        $check(in_array('PHP_SAPI', $cacheEnabled, true) && array_intersect($cacheEnabled, ['TerminalData', 'isTerminal', 'STDIN', 'defined']) === [],
            't6b cacheEnabled() mira PHP_SAPI, que ninguna suite cambia, y no TerminalData', implode(' ', $cacheEnabled));
        echoTerminal(' ');

        echoTerminal('[t7] Las consultas, en la misma conexión');
        $mismaConexion = SettingsModel::model()->getDatabase() === $bd;
        $check($mismaConexion, 't7 CANARIO: SettingsModel usa la misma conexión que la suite (Questions es de la sesión)');
        $n = count($viejo);
        $veinte = array_slice(array_map('strval', array_keys($viejo)), 0, 20);
        SettingsModel::forgetCache();
        $conFoto = $consultas(fn () => SettingsModel::getConfigurations());
        $veinteConFoto = $consultas(function () use ($veinte): void {
            foreach ($veinte as $name) {
                SettingsModel::getConfigValue($name);
            }
        });
        SettingsModel::swapCacheForTesting(false);
        $sinFoto = $consultas(fn () => SettingsModel::getConfigurations());
        $antes = $consultas($antiguo);
        $veinteSinFoto = $consultas(function () use ($veinte): void {
            foreach ($veinte as $name) {
                SettingsModel::getConfigValue($name);
            }
        });
        $alertas = SystemAlertRegistry::swapForTesting();
        for ($i = 1; $i <= 5; $i++) {
            SystemAlertRegistry::register(new SystemAlert("zz-snapshot-nag-{$i}", SystemAlert::SEVERITY_INFO, fn (): string => 'zz', fn (): bool => false, true, [UsersModel::TYPE_USER_ROOT], true));
        }
        $nagsSinFoto = $consultas(fn () => SystemAlertRegistry::nagsFor(UsersModel::TYPE_USER_ROOT));
        SettingsModel::swapCacheForTesting(true);
        SettingsModel::getConfigurations();
        $nagsConFoto = $consultas(fn () => SystemAlertRegistry::nagsFor(UsersModel::TYPE_USER_ROOT));
        SystemAlertRegistry::swapForTesting($alertas);
        $alertas = null;
        echoTerminal("   cifras: N={$n}; getConfigurations() con foto {$conFoto}, sin foto {$sinFoto}, el N+1 de antes {$antes}; 20 getConfigValue con foto {$veinteConFoto}, sin foto {$veinteSinFoto}; nagsFor() (5 avisos) con foto {$nagsConFoto}, sin foto {$nagsSinFoto}");
        $check($mismaConexion && $antes >= $n && $sinFoto === 1, 't7 CANARIO: sin foto la cuenta se ve (el N+1 de antes y la sola consulta de hoy)', "{$antes} / {$sinFoto}");
        $check($mismaConexion && $conFoto === 1, 't7 getConfigurations() con la foto vacía: 1 consulta', (string) $conFoto);
        $check($mismaConexion && $veinteConFoto === 0 && $veinteSinFoto >= 20 && $veinteSinFoto <= 40, 't7 DISCRIMINANTE: 20 getConfigValue, 0 consultas con foto y 20-40 sin ella', "{$veinteConFoto} / {$veinteSinFoto}");
        $check($mismaConexion && $nagsConFoto === 0 && $nagsSinFoto >= 1, 't7 nagsFor() con la foto llena: 0 consultas (sin foto, alguna)', "{$nagsConFoto} / {$nagsSinFoto}");
        echoTerminal(' ');

        echoTerminal('[t8] optionExists() y save() siguen yendo a la base');
        $t8 = "{$p}t8-{$sufijo}";
        $bd->beginTransaction();
        try {
            SettingsModel::getConfigurations();
            $plantar($t8, '"por PDO"');
            $instance = new SettingsModel();
            $instance->name = $t8;
            $instance->value = 'por save';
            $guardo = $instance->save();
            $check(SettingsModel::optionExists($t8) && $guardo === false, 't8 DISCRIMINANTE: optionExists ve la fila de PDO y save() devuelve false sin lanzar', var_export($guardo, true));
        } catch (\Throwable $e) {
            $check(false, 't8 sin excepción', get_class($e));
        } finally {
            $bd->rollBack();
        }
        echoTerminal(' ');

        echoTerminal('[t9] readFromDatabase() lee fresco; getConfigValue(), de la foto');
        $bd->beginTransaction();
        try {
            SettingsModel::getConfigValue($centinela);
            $cambiar($centinela, '"t9"');
            $check(SettingsModel::getConfigValue($centinela) !== 't9', 't9 CANARIO: la foto está en uso (getConfigValue da lo viejo)');
            $check(SettingsModel::readFromDatabase($centinela) === 't9', 't9 DISCRIMINANTE: readFromDatabase da lo nuevo');
        } finally {
            $bd->rollBack();
            SettingsModel::forgetCache();
        }
        echoTerminal(' ');

        echoTerminal('[t10] El respaldo de la configuración estática, en vivo');
        $ausente = "{$p}ausente-{$sufijo}";
        set_config($ausente, 'A');
        $a = SettingsModel::getConfigValue($ausente, true);
        set_config($ausente, 'B');
        $b = SettingsModel::getConfigValue($ausente, true);
        set_config($ausente, null);
        $check($a === 'A' && $b === 'B' && SettingsModel::getConfigValue($ausente) === null, 't10 DISCRIMINANTE: A y luego B; sin respaldo, null', var_export([$a, $b], true));
        echoTerminal(' ');

        echoTerminal('[t11] La foto no sale de la clase');
        $vetadas = array_values(array_intersect($textos($tokens), ['apcu_store', 'apcu_add', 'apcu_fetch', 'file_put_contents', 'fwrite', 'serialize', 'var_export', 'var_dump', 'print_r', 'error_log', 'log_soft_error', 'log_exception', 'json_encode']));
        $check($vetadas === [], 't11 SettingsModel no vuelca nada a disco, APCu ni registros', implode(', ', $vetadas));
        $check($foto->isPrivate() && $foto->isStatic(), 't11 la foto es private static');
        $getters = [];
        $total = count($tokens);
        for ($i = 0; $i < $total; $i++) {
            if ($tokens[$i]->id !== T_FUNCTION) {
                continue;
            }
            $esPublica = false;
            for ($k = $i - 1; $k >= 0 && in_array($tokens[$k]->id, [T_PUBLIC, T_PRIVATE, T_PROTECTED, T_STATIC, T_FINAL, T_ABSTRACT], true); $k--) {
                $esPublica = $esPublica || $tokens[$k]->id === T_PUBLIC;
            }
            if (!$esPublica) {
                continue;
            }
            $body = $textos($cuerpo($tokens, $tokens[$i + 1]->text));
            foreach (array_keys($body, 'return', true) as $r) {
                $fin = array_search(';', array_slice($body, $r, null, true), true);
                $expresion = implode('', array_slice($body, $r + 1, ($fin === false ? count($body) : (int) $fin) - (int) $r - 1));
                if (preg_match('/^(self::|static::)?(\$snapshot|loadSnapshot\(\)|snapshotFromRows\(.*\))$/', $expresion) === 1) {
                    $getters[] = $tokens[$i + 1]->text;
                }
            }
        }
        $check($getters === [], 't11 DISCRIMINANTE: ningún método público devuelve la foto', implode(', ', $getters));
        echoTerminal(' ');

        echoTerminal('[t12] La foto se carga construyendo un SettingsModel: el primer mapper fija lc_time_names');
        $locale = new \ReflectionProperty(BaseEntityMapper::class, 'localeSetted');
        $localePrevio = $locale->getValue();
        $locale->setValue(null, false);
        try {
            SettingsModel::forgetCache();
            SettingsModel::getConfigurations();
            $check($locale->getValue() === true, 't12 DISCRIMINANTE: tras cargar la foto, $localeSetted está puesto');
        } finally {
            $locale->setValue(null, $localePrevio);
        }
        echoTerminal(' ');

        echoTerminal('[t14] Leer, modificar y escribir parte de la base, no de la foto (avisos ocultos y exportadores apagados)');
        $alertas = SystemAlertRegistry::swapForTesting();
        foreach (['zz-snapshot-a', 'zz-snapshot-b'] as $clave) {
            SystemAlertRegistry::register(new SystemAlert($clave, SystemAlert::SEVERITY_INFO, fn (): string => 'zz', fn (): bool => true, true, [UsersModel::TYPE_USER_ROOT]));
        }
        $fijar = fn (string $name, string $json): int => $sql('INSERT INTO `pcsphp_app_config` (`name`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)', [$name, $json]);
        $bd->beginTransaction();
        try {
            SettingsModel::getConfigurations();
            $fijar(SystemAlertRegistry::HIDDEN_CONFIG, '["zz-snapshot-a"]');
            $rancia = !in_array('zz-snapshot-a', SystemAlertRegistry::hidden(), true);
            SystemAlertRegistry::hide('zz-snapshot-b');
            $trasHide = SystemAlertRegistry::hidden(true);
            $check($rancia && in_array('zz-snapshot-a', $trasHide, true) && in_array('zz-snapshot-b', $trasHide, true), 't14 DISCRIMINANTE: hide() parte de la lista fresca (no pierde lo escrito por otro)', 'rancia: ' . var_export($rancia, true) . ', ' . json_encode($trasHide));
            SettingsModel::getConfigurations();
            $fijar(SystemAlertRegistry::HIDDEN_CONFIG, '["zz-snapshot-a","zz-snapshot-b","zz-snapshot-c"]');
            SystemAlertRegistry::show('zz-snapshot-a');
            $trasShow = SystemAlertRegistry::hidden(true);
            $check($trasShow === ['zz-snapshot-b', 'zz-snapshot-c'], 't14 DISCRIMINANTE: show() parte de la lista fresca', (string) json_encode($trasShow));
            $setDisabled = new \ReflectionMethod(DataTransferController::class, 'setDisabled');
            SettingsModel::getConfigurations();
            $fijar(DataTransferController::DISABLED_CONFIG, '{"import":[],"export":["zz-snapshot-x"]}');
            $setDisabled->invoke(null, DataTransferController::KIND_EXPORT, 'zz-snapshot-y', true);
            $apagados = DataTransferController::disabledKeys(true)[DataTransferController::KIND_EXPORT];
            $check(in_array('zz-snapshot-x', $apagados, true) && in_array('zz-snapshot-y', $apagados, true), 't14 DISCRIMINANTE: setDisabled() parte de la lista fresca', (string) json_encode($apagados));
        } finally {
            $bd->rollBack();
            SettingsModel::forgetCache();
            SystemAlertRegistry::swapForTesting($alertas);
            $alertas = null;
        }
        echoTerminal(' ');

        echoTerminal('[t13] Nadie fuera de SettingsModel escribe la tabla sin olvidar la foto');
        $archivos = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator("{$src}/app", \FilesystemIterator::SKIP_DOTS));
        $escritores = [];
        $sinOlvido = [];
        foreach ($archivos as $archivo) {
            $ruta = str_replace('\\', '/', (string) $archivo);
            if (!str_ends_with($ruta, '.php') || str_contains($ruta, '/system-controllers/local-tests/') || $ruta === $modelFile) {
                continue;
            }
            $contenido = (string) file_get_contents($ruta);
            if (!str_contains($contenido, 'pcsphp_app_config') && !str_contains($contenido, 'OPTIONS_TABLE') && !str_contains($contenido, 'SettingsModel')) {
                continue;
            }
            $t = $tokensDe($ruta);
            $texto = $textos($t);
            $n = count($t);
            $escribe = false;
            $instancias = [];
            $nombraModelo = in_array('SettingsModel', $texto, true);
            for ($i = 0; $i < $n; $i++) {
                //SQL que escribe en una tabla que no es literal (sale de getTable() o de un parámetro), en un archivo que conoce SettingsModel.
                if ($nombraModelo && $t[$i]->is([T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE])
                    && preg_match('/^["\']?\s*(UPDATE|DELETE\s+FROM|INSERT\s+INTO|REPLACE\s+INTO|TRUNCATE)\s*`?["\']?$/i', $t[$i]->text) === 1
                    && ($t[$i]->id === T_ENCAPSED_AND_WHITESPACE || ($texto[$i + 1] ?? '') === '.')) {
                    $escribe = true;
                }
                if ($t[$i]->is([T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE]) && str_contains($t[$i]->text, 'pcsphp_app_config')) {
                    $escribe = true;
                }
                if ($t[$i]->text === 'OPTIONS_TABLE' && ($texto[$i - 1] ?? '') === '::') {
                    $escribe = true;
                }
                //$x = new SettingsModel / $this->x = new SettingsModel: el destino.
                if ($t[$i]->id === T_NEW && ($texto[$i + 1] ?? '') === 'SettingsModel' && ($texto[$i - 1] ?? '') === '=') {
                    $k = $i - 2;
                    $destino = $texto[$k] ?? '';
                    if (($texto[$k - 1] ?? '') === '->' && ($texto[$k - 2] ?? '') === '$this') {
                        $destino = '$this->' . $destino;
                    }
                    $instancias[] = $destino;
                }
                if ($t[$i]->id === T_NEW && ($texto[$i + 1] ?? '') === 'SettingsModel' && ($texto[$i - 1] ?? '') === '(') {
                    $j = $i;
                    $depth = 1;
                    while (++$j < $n && $depth > 0) {
                        $depth += $texto[$j] === '(' ? 1 : ($texto[$j] === ')' ? -1 : 0);
                    }
                    if (($texto[$j] ?? '') === '->' && ($texto[$j + 1] ?? '') === 'getModel') {
                        $escribe = $escribe || (($texto[$j + 4] ?? '') === '->' && ($texto[$j + 5] ?? '') !== 'getTable');
                    }
                }
            }
            foreach (array_unique($instancias) as $destino) {
                for ($i = 0; $i < $n - 3; $i++) {
                    $receptor = $texto[$i] === '$this' && ($texto[$i + 1] ?? '') === '->' ? '$this->' . ($texto[$i + 2] ?? '') : $texto[$i];
                    $salto = str_starts_with($receptor, '$this->') ? 3 : 1;
                    if ($receptor !== $destino || ($texto[$i + $salto] ?? '') !== '->' || ($texto[$i + $salto + 1] ?? '') !== 'getModel') {
                        continue;
                    }
                    //El modelo se usa en la misma expresión, o se guarda en algo que se usa después.
                    $tras = $i + $salto + 4;
                    if (($texto[$tras] ?? '') === '->' && ($texto[$tras + 1] ?? '') !== 'getTable') {
                        $escribe = true;
                    }
                    if (($texto[$i - 1] ?? '') === '=') {
                        $guardado = ($texto[$i - 3] ?? '') === '->' && ($texto[$i - 4] ?? '') === '$this' ? '$this->' . $texto[$i - 2] : ($texto[$i - 2] ?? '');
                        $usos = 0;
                        for ($k = 0; $k < $n - 2; $k++) {
                            $r = $texto[$k] === '$this' && ($texto[$k + 1] ?? '') === '->' ? '$this->' . ($texto[$k + 2] ?? '') : $texto[$k];
                            $usos += $r === $guardado ? 1 : 0;
                        }
                        $escribe = $escribe || $usos > 1;
                    }
                }
            }
            if (!$escribe) {
                continue;
            }
            $relativa = substr($ruta, strlen($src) + 1);
            $escritores[] = $relativa;
            $olvido = false;
            for ($i = 2; $i < $n; $i++) {
                if ($texto[$i] === 'forgetCache' && $texto[$i - 1] === '::' && $texto[$i - 2] === 'SettingsModel') {
                    $olvido = true;
                }
            }
            if (!$olvido) {
                $sinOlvido[] = $relativa;
            }
        }
        $check(in_array('app/core/psr4/PiecesPHP/Core/Crypto/KeyRotation.php', $escritores, true), 't13 CANARIO: el instrumento ve a KeyRotation (UPDATE sobre OPTIONS_TABLE)', implode(', ', $escritores));
        $check(in_array('app/classes/Terminal/Tasks/RepairEscapedTextTask.php', $escritores, true), 't13 CANARIO: el instrumento ve a RepairEscapedTextTask (UPDATE sobre la tabla de un mapper)', implode(', ', $escritores));
        $check($sinOlvido === [], 't13 DISCRIMINANTE: ' . count($escritores) . ' escritor(es) fuera de SettingsModel, todos llaman a SettingsModel::forgetCache()', 'sin olvido: ' . implode(', ', $sinOlvido));
    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        if ($bd->inTransaction()) {
            $bd->rollBack();
        }
        if ($alertas !== null) {
            SystemAlertRegistry::swapForTesting($alertas);
        }
        if ($fijadas !== null) {
            ConfigOptions::swapFixedByFileForTesting($fijadas);
        }
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $sql('DELETE FROM `pcsphp_app_config` WHERE `name` LIKE ?', ['zz-snapshot-%']);
            $restantes = $quedan();
        } catch (\Throwable $e) {
            $restantes = -1;
        }
        SettingsModel::swapCacheForTesting($previo);
        $check($restantes === 0, 'z1 no queda ninguna opción zz-snapshot-', "quedan {$restantes}");
    }

    $total = $passed + $failed;
    echoTerminal(' ');
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La foto de las opciones (ADR 0080): igual que readFromDatabase() y que el N+1 de antes, busca como utf8mb4_bin, decodifica en cada lectura, se olvida en cada camino de escritura, está apagada en la terminal, cuenta las consultas en la misma conexión y nadie escribe la tabla sin olvidarla. Escribe opciones zz-snapshot- y las retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
