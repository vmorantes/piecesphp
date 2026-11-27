<?php

//ADR 0061: el cifrado autenticado, el llavero, la rotación, la puerta y la primera arrancada. Nunca la clave real: un
//llavero en un temporal. Crea una tabla zz_rotacion_*, una opción zz-rotacion-* y un usuario zz-agente-cifrado-*; borra todo.
//@areas src/app/classes/Organizations/
//@areas src/app/classes/PiecesPHP/Settings/
//@areas src/app/classes/SystemApprovals/

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Crypto\AppKeyring;
use PiecesPHP\Core\Crypto\EncryptedData;
use PiecesPHP\Core\Crypto\EncryptedDataRegistry;
use PiecesPHP\Core\Crypto\KeyRotation;
use PiecesPHP\Core\Crypto\Sealed;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Core\Statics\ProtectFileMiddleware;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use Terminal\Tasks\AppKeyCommands;
use Terminal\Tasks\VerifyIntegrityTask;

CliActions::make('unit-tests:core/app-key-crypto', function ($args) {

    echoTerminal("\e[33m[TEST:AppKeyCrypto] El cifrado de verdad, el llavero y la clave que se cambia sin perder nada\e[39m");
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
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal(' ');
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];
    };

    $database = (new BaseModel())->getDatabase();
    if (!$database instanceof \PDO) {
        $check(false, 'c1 hay conexión a la base');
        return $balance();
    }
    $check(true, 'c1 hay conexión a la base');
    $directorio = sys_get_temp_dir() . '/zz-app-key-' . bin2hex(random_bytes(4));
    //RETORNO-IGNORADO: en un temporal propio: si falla, la comprobación que sigue lo dice.
    mkdir($directorio, 0775, true);
    $marca = 'zz-rotacion-' . bin2hex(random_bytes(3));
    $tabla = 'zz_rotacion_' . bin2hex(random_bytes(3));
    $tablaSello = 'zz_sello_' . bin2hex(random_bytes(3));
    $opcion = $marca . '-opcion';
    $prefijo = 'zz-agente-cifrado-' . bin2hex(random_bytes(3));
    $usuarios = [];
    $registroPrevio = null;
    $llaveroPrevio = AppKeyring::get();
    $servidorPrevio = $_SERVER;

    try {
        //─── a · Sealed ─────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] Cifrar de verdad');
        $llavero = new AppKeyring(AppKeyring::generateKey());
        $cerrado = Sealed::seal('zz texto secreto', Sealed::PURPOSE_TOTP_SECRET, $llavero);
        $otraVez = Sealed::seal('zz texto secreto', Sealed::PURPOSE_TOTP_SECRET, $llavero);
        $check(Sealed::open($cerrado, Sealed::PURPOSE_TOTP_SECRET, $llavero) === 'zz texto secreto', 'a1 ida y vuelta');
        $check(str_starts_with($cerrado, 'v2:' . $llavero->currentFingerprint() . ':') && $cerrado !== $otraVez, 'a2 formato v2:<huella>:… y un IV distinto cada vez');
        $raw = BaseHashEncryption::urlSafeB64Decode(explode(':', $cerrado, 3)[2]);
        $manipulados = [];
        foreach ([0 => 'el IV', 12 => 'la etiqueta', strlen($raw) - 1 => 'el texto cifrado'] as $posicion => $nombre) {
            $copia = $raw;
            $copia[$posicion] = chr(ord($copia[$posicion]) ^ 1);
            $manipulados[$nombre] = Sealed::open('v2:' . $llavero->currentFingerprint() . ':' . BaseHashEncryption::urlSafeB64Encode($copia), Sealed::PURPOSE_TOTP_SECRET, $llavero);
        }
        $check(array_filter($manipulados, fn($v) => $v !== null) === [], 'a3 un bit cambiado en el IV, la etiqueta o el texto cifrado: no se abre (null, nunca basura)', (string) json_encode(array_keys(array_filter($manipulados, fn($v) => $v !== null))));
        $check(Sealed::open($cerrado, Sealed::PURPOSE_MAIL_CONFIG, $llavero) === null && Sealed::open($cerrado, Sealed::PURPOSE_TOTP_SECRET, new AppKeyring(AppKeyring::generateKey())) === null, 'a4 con otro propósito u otra clave: null');
        $check(Sealed::open('v2:00000000:' . explode(':', $cerrado, 3)[2], Sealed::PURPOSE_TOTP_SECRET, $llavero) === null && Sealed::open('zz-no-es-sealed', Sealed::PURPOSE_TOTP_SECRET, $llavero) === null, 'a5 otra huella o sin formato: null');
        echoTerminal(' ');

        //─── b · El experimento ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[b] La clave sale de los tokens viejos y no de los nuevos (el experimento del ADR)');
        $clave = AppKeyring::generateKey();
        $largo = strlen($clave);
        //Lo que el usuario conoce de sus datos: todo menos el identificador de sesión, que son 64 hexadecimales.
        $prefijoDatos = '{"id":123,"sid":"';
        $permitido = function (int $i, int $byte) use ($prefijoDatos): bool {
            $finSid = strlen($prefijoDatos) + 64;
            if ($i < strlen($prefijoDatos)) {
                return $byte === ord($prefijoDatos[$i]);
            }
            if ($i < $finSid) {
                return ($byte >= 48 && $byte <= 57) || ($byte >= 97 && $byte <= 102);
            }
            return $byte === ord('"}'[$i - $finSid] ?? "\0");
        };
        $recuperar = function (array $cifrados) use ($permitido, $largo): string {
            $clave = '';
            for ($j = 0; $j < $largo; $j++) {
                $candidatas = [];
                for ($k = 0; $k < 256; $k++) {
                    $vale = true;
                    foreach ($cifrados as $c) {
                        for ($i = 0; $i < strlen($c); $i++) {
                            //El índice de la clave del Vigenère de antes: (i mod L) − 1, y −1 es la última.
                            if (((($i % $largo) - 1) + $largo) % $largo !== $j) {
                                continue;
                            }
                            if (!$permitido($i, (ord($c[$i]) - $k + 256) % 256)) {
                                $vale = false;
                                break 2;
                            }
                        }
                    }
                    if ($vale) {
                        $candidatas[] = $k;
                    }
                }
                $clave .= count($candidatas) === 1 ? chr($candidatas[0] % 256) : '?';
            }
            return $clave;
        };
        $viejos = [];
        $nuevos = [];
        //100 y no los 27-50 del experimento: con 50, a veces queda un carácter con dos candidatas y la prueba sería al azar.
        for ($n = 0; $n < 100; $n++) {
            $datos = $prefijoDatos . bin2hex(random_bytes(32)) . '"}';
            $viejos[] = BaseHashEncryption::urlSafeB64Decode(BaseHashEncryption::obfuscate($datos, $clave));
            //Lo mismo que hace ahora BaseToken::setToken() con los datos.
            $nuevos[] = BaseHashEncryption::urlSafeB64Decode(explode(':', Sealed::seal($datos, Sealed::PURPOSE_TOKEN_DATA, new AppKeyring($clave)), 3)[2]);
        }
        $deViejos = $recuperar($viejos);
        $aciertosNuevos = 0;
        $deNuevos = $recuperar($nuevos);
        for ($j = 0; $j < $largo; $j++) {
            $aciertosNuevos += $deNuevos[$j] === $clave[$j] ? 1 : 0;
        }
        $check($deViejos === $clave, 'b1 CANARIO: con 100 tokens del formato viejo, la clave entera sale (64 de 64 caracteres)', substr_count($deViejos, '?') . ' sin resolver');
        $check($aciertosNuevos === 0, 'b2 con 100 tokens del formato nuevo, ni un carácter de la clave', "{$aciertosNuevos} acertados");
        echoTerminal(' ');

        //─── c · El llavero ─────────────────────────────────────────────────────────────────────────────
        echoTerminal('[c] El llavero');
        $viejo = new AppKeyring(AppKeyring::generateKey());
        $deAntes = Sealed::seal('zz sin declarar', Sealed::PURPOSE_MAIL_CONFIG, $viejo);
        $nuevo = $viejo->rotatedTo(AppKeyring::generateKey());
        $check(Sealed::open($deAntes, Sealed::PURPOSE_MAIL_CONFIG, $nuevo) === 'zz sin declarar', 'c2 lo que nadie declaró se sigue leyendo tras rotar: el llavero guarda la anterior');
        $check(Sealed::fingerprintOf(Sealed::seal('x', Sealed::PURPOSE_MAIL_CONFIG, $nuevo)) === $nuevo->currentFingerprint() && $nuevo->previousFingerprints() === [$viejo->currentFingerprint()], 'c3 se cifra con la nueva y la vieja queda como anterior');
        $archivo = $directorio . '/' . AppKeyring::FILE_NAME;
        $check($nuevo->save($archivo) && AppKeyring::fromFile($archivo)?->keyFor($viejo->currentFingerprint()) === $viejo->current(), 'c4 se guarda y se vuelve a leer igual');
        //RETORNO-IGNORADO: el siguiente bloque empieza sin llavero.
        @unlink($archivo);
        echoTerminal(' ');

        //─── d · Rotar ──────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[d] Rotar sin perder nada, y si algo falla, no cambia nada');
        //RETORNO-IGNORADO: en la base, PDO lanza si falla (ERRMODE_EXCEPTION) y lo cuenta «la prueba corre entera».
        $database->exec("CREATE TABLE `{$tabla}` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `dato` TEXT NULL)");
        $uno = new AppKeyring(AppKeyring::generateKey());
        $proposito = Sealed::PURPOSE_TOTP_SECRET;
        $sitios = [
            'zz-columna' => new EncryptedData('zz-columna', $proposito, EncryptedData::KIND_COLUMN, $tabla, 'dato', 'id', static fn(string $v): ?string => str_starts_with($v, 'zz-claro:') ? substr($v, 9) : null),
            'zz-opcion' => new EncryptedData('zz-opcion', Sealed::PURPOSE_MAIL_CONFIG, EncryptedData::KIND_OPTION, $opcion),
            'zz-no-guardado' => new EncryptedData('zz-no-guardado', Sealed::PURPOSE_TOKEN_DATA, EncryptedData::KIND_NOT_STORED),
        ];
        $insertar = $database->prepare("INSERT INTO `{$tabla}` (`dato`) VALUES (?)");
        foreach ([Sealed::seal('zz uno', $proposito, $uno), Sealed::seal('zz dos', $proposito, $uno), 'zz-claro:zz tres', 'zz-basura-ilegible', ''] as $valor) {
            //RETORNO-IGNORADO: en la base, PDO lanza si falla (ERRMODE_EXCEPTION) y lo cuenta «la prueba corre entera».
            $insertar->execute([$valor]);
        }
        $check(SettingsModel::setConfigValue($opcion, Sealed::seal('zz opción', Sealed::PURPOSE_MAIL_CONFIG, $uno)), 'banco: una tabla zz con cinco filas (dos cifradas, una de un formato anterior, una ilegible, una vacía) y una opción zz');
        $leerTabla = fn(): array => $database->query("SELECT `id`, `dato` FROM `{$tabla}` ORDER BY `id`")->fetchAll(\PDO::FETCH_KEY_PAIR);
        $antesDeFallar = $leerTabla();
        $falla = $uno->rotatedTo(AppKeyring::generateKey());
        $lanzo = false;
        try {
            (new KeyRotation($database, $sitios))->rotate($uno, $falla, $directorio . '/no-existe/' . AppKeyring::FILE_NAME);
        } catch (\Throwable $e) {
            $lanzo = true;
        }
        $check($lanzo && $leerTabla() === $antesDeFallar && !$database->inTransaction(), 'd1 sin poder escribir el llavero, la rotación lanza y la base queda EXACTAMENTE como estaba');

        $dos = $uno->rotatedTo(AppKeyring::generateKey());
        $informe = (new KeyRotation($database, $sitios))->rotate($uno, $dos, $archivo);
        $despues = $leerTabla();
        $abiertos = array_map(fn($v) => is_string($v) && Sealed::isSealed($v) ? Sealed::open($v, $proposito, $dos) : $v, $despues);
        $huellas = array_unique(array_filter(array_map(fn($v) => is_string($v) ? Sealed::fingerprintOf($v) : null, $despues)));
        $check(array_values($abiertos) === ['zz uno', 'zz dos', 'zz tres', 'zz-basura-ilegible', ''] && array_values($huellas) === [$dos->currentFingerprint()], 'd2 todo lo legible, cifrado con la nueva (también lo del formato anterior); lo ilegible y lo vacío, como estaban', (string) json_encode($informe));
        $check($informe['zz-columna']['resealed'] === 3 && $informe['zz-columna']['unreadable'] === 1 && $informe['zz-opcion']['resealed'] === 1 && $informe['zz-no-guardado']['note'] !== '', 'd3 el recuento por sitio lo dice', (string) json_encode($informe));
        $opcionRotada = SettingsModel::getConfigValue($opcion);
        $check(is_string($opcionRotada) && Sealed::fingerprintOf($opcionRotada) === $dos->currentFingerprint() && Sealed::open($opcionRotada, Sealed::PURPOSE_MAIL_CONFIG, $dos) === 'zz opción', 'd4 la opción, también, con la huella nueva y en la forma en que se guarda');
        $escrito = AppKeyring::fromFile($archivo);
        $check($escrito !== null && $escrito->currentFingerprint() === $dos->currentFingerprint() && ($escrito->previousFingerprints()[0] ?? null) === $uno->currentFingerprint(), 'd5 el llavero escrito: la nueva y la vieja como anterior');
        echoTerminal(' ');

        //─── e · Retirar ────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[e] Retirar una clave anterior');
        $sinIlegibles = $sitios;
        //RETORNO-IGNORADO: en la base, PDO lanza si falla (ERRMODE_EXCEPTION) y lo cuenta «la prueba corre entera».
        $database->exec("DELETE FROM `{$tabla}` WHERE `dato` = 'zz-basura-ilegible'");
        $enUso = $database->prepare("INSERT INTO `{$tabla}` (`dato`) VALUES (?)");
        //RETORNO-IGNORADO: en la base, PDO lanza si falla (ERRMODE_EXCEPTION) y lo cuenta «la prueba corre entera».
        $enUso->execute([Sealed::seal('zz aún con la vieja', $proposito, $uno)]);
        $rotacion = new KeyRotation($database, $sinIlegibles);
        $check($rotacion->retire($dos, $uno->currentFingerprint(), $archivo) !== '' && AppKeyring::fromFile($archivo)?->keyFor($uno->currentFingerprint()) !== null, 'e1 una clave que aún usa un dato declarado NO se retira');
        $check($rotacion->retire($dos, $dos->currentFingerprint(), $archivo) !== '', 'e2 la actual no se retira nunca');
        //RETORNO-IGNORADO: en la base, PDO lanza si falla (ERRMODE_EXCEPTION) y lo cuenta «la prueba corre entera».
        $database->exec("DELETE FROM `{$tabla}` WHERE `dato` LIKE 'v2:" . $uno->currentFingerprint() . ":%'");
        $database->prepare("INSERT INTO `{$tabla}` (`dato`) VALUES (?)")->execute(['zz-claro:formato anterior']);
        $check($rotacion->retire($dos, $uno->currentFingerprint(), $archivo) !== '', 'e3 con datos de un formato anterior, que no dicen su clave, tampoco');
        //RETORNO-IGNORADO: en la base, PDO lanza si falla (ERRMODE_EXCEPTION) y lo cuenta «la prueba corre entera».
        $database->exec("DELETE FROM `{$tabla}` WHERE `dato` LIKE 'zz-claro:%'");
        $check($rotacion->retire($dos, $uno->currentFingerprint(), $archivo) === '' && AppKeyring::fromFile($archivo)?->previousFingerprints() === [], 'e4 CANARIO: sin nadie que la use, se retira');
        echoTerminal(' ');

        //─── f · La puerta ──────────────────────────────────────────────────────────────────────────────
        echoTerminal('[f] La puerta de verify-integrity');
        $metodo = new \ReflectionMethod(VerifyIntegrityTask::class, 'cryptoCalls');
        $llamadas = $metodo->invoke(null, "<?php\nnamespace Zz;\nuse PiecesPHP\\Core\\Crypto\\Sealed;\nuse PiecesPHP\\UserSystem\\Controllers\\UsersController;\nSealed::seal(\$x, UsersController::SIGNUP_TYPE_PURPOSE);\nSealed::open(\$y, \$proposito);\n");
        $check(is_array($llamadas) && ($llamadas[0]['purpose'] ?? null) === UsersController::SIGNUP_TYPE_PURPOSE && !in_array(UsersController::SIGNUP_TYPE_PURPOSE, EncryptedDataRegistry::purposes(), true) && array_key_exists(1, $llamadas) && $llamadas[1]['purpose'] === null, 'f1 resuelve la constante de otra clase y ve que su valor NO está declarado; y una variable no es un propósito auditable', (string) json_encode($llamadas));
        $check(in_array(Sealed::PURPOSE_TOKEN_DATA, EncryptedDataRegistry::purposes(), true) && in_array(Sealed::PURPOSE_CONFIG_SECRET, EncryptedDataRegistry::purposes(), true) && in_array(\PiecesPHP\AI\Providers\AIProviderRegistry::SEAL_PURPOSE, EncryptedDataRegistry::purposes(), true) && count(EncryptedDataRegistry::purposes()) === 8, 'f2 los ocho propósitos del framework, declarados (con las claves de API de la configuración)', implode(', ', EncryptedDataRegistry::purposes()));
        echoTerminal(' ');

        //─── g · La primera arrancada ───────────────────────────────────────────────────────────────────
        echoTerminal('[g] La primera arrancada (ADR 0061 §9), en un temporal');
        $primer = $directorio . '/primera/' . AppKeyring::FILE_NAME;
        //RETORNO-IGNORADO: en un temporal propio: si falla, la comprobación que sigue lo dice.
        mkdir(dirname($primer), 0775, true);
        $deRelleno = new AppKeyring('TODO:zz-relleno', [], AppKeyring::SOURCE_CONFIG);
        $huellaDelArranque = new \ReflectionProperty(Config::class, 'bootGeneratedKeyFingerprint');
        $huellaPorAvisar = new \ReflectionProperty(Config::class, 'generatedKeyFingerprint');
        $huellasPrevias = [$huellaDelArranque->getValue(), $huellaPorAvisar->getValue()];
        $generado = Config::first_boot_keyring($deRelleno, $primer);
        $huellaVista = Config::app_key_generated_this_boot();
        //Lo que hace announce_generated_app_key() tras avisar, sin escribir el aviso en el registro real.
        $huellaPorAvisar->setValue(null, null);
        $huellaTrasAvisar = Config::app_key_generated_this_boot();
        //Las de este proceso, como estaban: la prueba no cambia lo que vería un generate-app-key ni un aviso posterior.
        $huellaDelArranque->setValue(null, $huellasPrevias[0]);
        $huellaPorAvisar->setValue(null, $huellasPrevias[1]);
        $check(!Config::app_key_is_placeholder($generado->current()) && is_file($primer) && $generado->keyFor($deRelleno->currentFingerprint()) === 'TODO:zz-relleno', 'g1 con la de relleno y la carpeta escribible: genera, guarda, y la de relleno queda como anterior');
        $guardado = (string) file_get_contents($primer);
        $segundo = Config::first_boot_keyring($deRelleno, $primer);
        $check($segundo === $deRelleno && (string) file_get_contents($primer) === $guardado && AppKeyring::fromFile($primer)?->currentFingerprint() === $generado->currentFingerprint(), 'g2 un segundo arranque no genera otra ni toca la guardada (initAppKeyConfig() la lee antes)');
        //Una carpeta escribible y sin llavero: lo único que impide generar es que la clave sea de verdad.
        //RETORNO-IGNORADO: en un temporal propio: si falla, la comprobación que sigue lo dice.
        mkdir($directorio . '/otra', 0775, true);
        $check(Config::first_boot_keyring(new AppKeyring('zz-clave-de-verdad', [], AppKeyring::SOURCE_CONFIG), $directorio . '/otra/' . AppKeyring::FILE_NAME)->current() === 'zz-clave-de-verdad'
            && !is_file($directorio . '/otra/' . AppKeyring::FILE_NAME)
            && Config::first_boot_keyring($deRelleno, null)->current() === 'TODO:zz-relleno', 'g3 con una clave de verdad, o sin carpeta secure-keys, no genera nada');
        $check(Config::app_key_is_placeholder(Config::DEFAULT_APP_KEY) && Config::app_key_is_placeholder('TODO:x') && !Config::app_key_is_placeholder('zz-clave-de-verdad'), "g4 la de omisión ('secret') también cuenta como relleno");
        $check($huellaVista === $generado->currentFingerprint() && $huellaTrasAvisar === $huellaVista, 'g5 el arranque recuerda la huella que generó, también después de avisarla (cuando se borra la que espera aviso)', var_export([$huellaVista, $huellaTrasAvisar], true));
        try {
            $hecho = AppKeyCommands::generate($primer, $generado, $generado->currentFingerprint());
        } catch (\RuntimeException $e) {
            $hecho = ['LANZÓ: ' . $e->getMessage()];
        }
        $check(str_contains(implode(' ', $hecho), 'ya se generó en este arranque (huella ' . $generado->currentFingerprint() . ')') && (string) file_get_contents($primer) === $guardado, 'g6 generate-app-key justo tras el arranque que creó el llavero: lo da por hecho con su huella y no toca el archivo', implode(' | ', $hecho));
        $mensajes = [];
        foreach ([null, $deRelleno->currentFingerprint()] as $otraHuella) {
            try {
                AppKeyCommands::generate($primer, $generado, $otraHuella);
                $mensajes[] = 'NO LANZÓ';
            } catch (\RuntimeException $e) {
                $mensajes[] = $e->getMessage();
            }
        }
        $check(count(array_filter($mensajes, fn(string $m): bool => str_contains($m, 'rotate-app-key'))) === 2 && (string) file_get_contents($primer) === $guardado, 'g7 con un llavero de antes (o de otra clave) se niega y manda a rotate-app-key, sin tocar el archivo', implode(' | ', $mensajes));
        $sinEscribir = $directorio . '/sin-escribir';
        //RETORNO-IGNORADO: en un temporal propio: si falla, la comprobación que sigue lo dice.
        mkdir($sinEscribir, 0555, true);
        $consejos = [
            'sin carpeta' => \PiecesPHP\SystemStatus\SystemStatusRoutes::appKeyPlaceholderCause(null),
            'sin escribir' => \PiecesPHP\SystemStatus\SystemStatusRoutes::appKeyPlaceholderCause($sinEscribir),
            'con llavero' => \PiecesPHP\SystemStatus\SystemStatusRoutes::appKeyPlaceholderCause(dirname($primer)),
            'escribible' => \PiecesPHP\SystemStatus\SystemStatusRoutes::appKeyPlaceholderCause($directorio . '/otra'),
        ];
        $escribible = is_writable($sinEscribir);
        //RETORNO-IGNORADO: en un temporal propio; lo borra la limpieza del final.
        @chmod($sinEscribir, 0775);
        $check(str_contains($consejos['sin carpeta'], 'No hay carpeta de claves') && str_contains($consejos['sin carpeta'], 'permissions-and-property.sh')
            && ($escribible || (str_contains($consejos['sin escribir'], $sinEscribir) && str_contains($consejos['sin escribir'], 'no la puede escribir') && str_contains($consejos['sin escribir'], 'chmod 2770') && str_contains($consejos['sin escribir'], 'setgid')))
            && str_contains($consejos['con llavero'], 'no lo puede leer')
            && str_contains($consejos['escribible'], 'generate-app-key'), 'g8 el aviso de relleno dice la causa: sin carpeta, carpeta sin escritura (con su ruta y qué hacer), llavero ilegible' . ($escribible ? ' (este proceso escribe en una carpeta 0555: el caso sin escritura no se pudo medir)' : ''), (string) json_encode($consejos, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES));
        echoTerminal(' ');

        //La clave de relleno la avisa Avisos del sistema y no el registro de errores (decisión del PO del 25). Estructural: con
        //un llavero de verdad en secure-keys, forzar la de relleno exigiría tocar el llavero.
        $fuentes = '';
        foreach (['app/core/Config.php', 'app/core/bootstrap.php'] as $relativo) {
            $fuentes .= (string) file_get_contents(basepath($relativo));
        }
        \PiecesPHP\SystemStatus\SystemStatusRoutes::registerCoreAlerts();
        $alerta = \PiecesPHP\SystemStatus\SystemAlertRegistry::get('app-key-placeholder');
        $check(!method_exists(Config::class, 'warn_placeholder_app_key') && !str_contains($fuentes, 'warn_placeholder_app_key(') && preg_match('/log_(exception|soft_error)\([^;]*(es la de relleno|Se generó la clave|AppKeyPlaceholderCause|placeholder_app_key_advice)/is', $fuentes) !== 1
            && $alerta !== null && $alerta->audience() === [\PiecesPHP\UserSystem\ORM\UsersModel::TYPE_USER_ROOT, \PiecesPHP\UserSystem\ORM\UsersModel::TYPE_USER_ADMIN_GRAL]
            && str_contains($alerta->message(), \PiecesPHP\SystemStatus\SystemStatusRoutes::appKeyPlaceholderCause(AppKeyring::directory())),
            'g9 ni la clave de relleno ni la generada escriben en el registro de errores; el aviso de relleno lleva la causa');

        //La generación: la marca del aviso app-key-generated (fecha y huella), y ni una línea en el registro de errores.
        $registro = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\') . '/error.log.jsonl';
        $lineas = fn(): int => is_file($registro) ? count((array) file($registro)) : 0;
        $marca = $directorio . '/app-key-generated.json';
        $porAvisar = new \ReflectionProperty(Config::class, 'generatedKeyFingerprint');
        $previa = $porAvisar->getValue();
        $antes = $lineas();
        $porAvisar->setValue(null, 'zz-huella-generada');
        Config::announce_generated_app_key($marca);
        $porAvisar->setValue(null, $previa);
        $leida = Config::app_key_generated_notice($marca);
        $contenido = (string) @file_get_contents($marca);
        $check($lineas() === $antes && ($leida['fingerprint'] ?? null) === 'zz-huella-generada' && !str_contains($contenido, 'TODO') && !str_contains($contenido, 'zz-relleno'), 'g10 generar la clave deja la marca (fecha y huella) y no escribe en el registro de errores', "{$antes} → {$lineas()}; " . $contenido);
        //RETORNO-IGNORADO: en un temporal propio; si falla, la comprobación que sigue lo dice.
        file_put_contents($marca, (string) json_encode(['generatedAt' => date('c', time() - 31 * 86400), 'fingerprint' => 'zz-vieja']));
        $vieja = Config::app_key_generated_notice($marca);
        //RETORNO-IGNORADO: en un temporal propio; si falla, la comprobación que sigue lo dice.
        file_put_contents($marca, (string) json_encode(['generatedAt' => '2099-01-01T00:00:00+00:00', 'fingerprint' => 'zz-futura']));
        $check($vieja === null && Config::app_key_generated_notice($marca) === null && Config::app_key_generated_notice($directorio . '/no-existe.json') === null, 'g11 una marca de hace más de 30 días, una con fecha futura, o ninguna: el aviso no sale');
        $generada = \PiecesPHP\SystemStatus\SystemAlertRegistry::get('app-key-generated');
        $check($generada !== null && $generada->severity() === \PiecesPHP\SystemStatus\SystemAlert::SEVERITY_INFO && $generada->audience() === [\PiecesPHP\UserSystem\ORM\UsersModel::TYPE_USER_ROOT] && $generada->isDismissible() && $generada->showAsNag()
            && str_contains($generada->message(), 'secure-keys/app-key.json'), 'g12 el aviso app-key-generated: informativo, solo root, ocultable y flotante');
        echoTerminal(' ');

        //─── h · Lo publicado, migrado ──────────────────────────────────────────────────────────────────
        echoTerminal('[h] Lo de antes de la 9 se lee');
        $sitiosReales = EncryptedDataRegistry::all();
        $configVieja = (string) gzcompress((string) json_encode(['host' => 'zz-host']));
        $check($sitiosReales['mail-config']->read(BaseHashEncryption::obfuscate($configVieja, MailConfig::class), AppKeyring::get()) === $configVieja
            && $sitiosReales['mail-config']->read('zz-basura', AppKeyring::get()) === null, 'h1 la configuración de correo ofuscada se lee; la basura no');
        $antesEnBase = SettingsModel::getConfigValue(MailConfig::CONFIG_NAME);
        $correoPrevio = get_config(MailConfig::CONFIG_NAME);
        set_config(MailConfig::CONFIG_NAME, BaseHashEncryption::obfuscate($configVieja, MailConfig::class));
        $cargada = new MailConfig();
        set_config(MailConfig::CONFIG_NAME, $correoPrevio);
        $check($cargada->host() === 'zz-host' && SettingsModel::getConfigValue(MailConfig::CONFIG_NAME) === $antesEnBase, 'h2 la vieja en memoria se carga y NO se escribe en la base: solo se vuelve a guardar lo que de verdad está guardado');
        $totp = new OTPSecretsUsersMapper();
        $totp->secret = 'JBSWY3DPEHPK3PXP';
        $legado = $totp->plainSecret();
        $totp->setPlainSecret('JBSWY3DPEHPK3PXP');
        $check($legado === 'JBSWY3DPEHPK3PXP' && Sealed::isSealed((string) $totp->secret) && $totp->plainSecret() === 'JBSWY3DPEHPK3PXP', 'h3 el secreto TOTP en claro de antes se lee; el nuevo se guarda cifrado y se abre');
        $firmado = UsersController::signedType(UsersModel::TYPE_USER_GENERAL);
        $tocado = substr($firmado, 0, -1) . (substr($firmado, -1) === 'a' ? 'b' : 'a');
        $check(UsersController::typeFromSigned($firmado) === UsersModel::TYPE_USER_GENERAL && UsersController::typeFromSigned($tocado) === null && UsersController::typeFromSigned(UsersModel::TYPE_USER_ROOT . substr($firmado, (int) strpos($firmado, '-'))) === null, 'h4 el tipo del alta va firmado: cambiar la firma o el tipo no pasa');
        echoTerminal(' ');

        //─── i · Sesiones ───────────────────────────────────────────────────────────────────────────────
        echoTerminal('[i] Sesiones: un token viejo, y una sesión cerrada ante un archivo protegido');
        $u = new UsersModel();
        $u->username = "{$prefijo}-u";
        $u->email = "{$prefijo}-u@example.com";
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Agente';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_GENERAL;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[] = (int) $u->id;
        $nuevoToken = BaseToken::setToken(['id' => (int) $u->id, 'sid' => str_repeat('b', 64)]);
        $datosNuevo = BaseToken::decode($nuevoToken, BaseToken::getSecretKey(), BaseToken::$encrypt)->data ?? '';
        $check(is_string($datosNuevo) && Sealed::isSealed($datosNuevo) && (BaseToken::getData($nuevoToken)->id ?? null) === (int) $u->id, 'i0 BaseToken cifra los datos con Sealed y los vuelve a abrir');
        $clave = BaseToken::getSecretKey();
        $viejo = BaseToken::encode(['iat' => time(), 'exp' => time() + 3600, 'data' => BaseHashEncryption::obfuscate((string) json_encode(['id' => (int) $u->id, 'sid' => str_repeat('a', 64)]), $clave)], $clave);
        $check(BaseToken::check($viejo) === true && BaseToken::getData($viejo) === null && SessionToken::isActiveSession($viejo) === false, 'i1 un token firmado con los datos del formato viejo: firma buena, datos que no se abren, y NO es una sesión');
        $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
        $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        if ($base === '') {
            $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
            $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
        }
        $panel = rtrim($base, '/') . '/' . ltrim((string) parse_url((string) \PiecesPHP\AdminPanel\Controllers\AdminPanelController::routeName('', [], true), \PHP_URL_PATH), '/');
        $pedir = function (array $cabeceras) use ($panel): int {
            $handle = \Terminal\TestRun::curlInit();
            \Terminal\TestRun::curlSetoptArray($handle, [CURLOPT_URL => $panel, CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => $cabeceras]);
            curl_exec($handle);
            //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
            return (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        };
        $html = $base !== '' ? $pedir([SessionToken::headerName() . ": {$viejo}"]) : 0;
        $xhr = $base !== '' ? $pedir([SessionToken::headerName() . ": {$viejo}", 'X-Requested-With: XMLHttpRequest']) : 0;
        $check($html === 302 && $xhr === 401, 'i2 por HTTP, ese token lleva a iniciar sesión (302) o da 401 al XHR; nunca un 500', "HTML {$html}, XHR {$xhr}");

        $protegida = $directorio . '/protegida';
        //RETORNO-IGNORADO: en un temporal propio: si falla, la comprobación que sigue lo dice.
        mkdir($protegida, 0775, true);
        file_put_contents($protegida . '/zz.txt', 'zz');
        ProtectFileMiddleware::protectWithSession($protegida);
        $jwt = UserSessions::issueToken((int) $u->id);
        $_SERVER['HTTP_' . strtoupper(SessionToken::tokenName())] = $jwt;
        $peticion = new \PiecesPHP\Core\Routing\RequestRoute('GET', (new UriFactory())->createUri('http://localhost/zz'), new Headers(), [], [], (new StreamFactory())->createStream(''));
        $abierta = ProtectFileMiddleware::validateAccess($protegida . '/zz.txt', $peticion);
        UserSessions::closeAllOfUser((int) $u->id, null, UserSessions::REASON_REVOKE_ALL_USER);
        $cerrada = ProtectFileMiddleware::validateAccess($protegida . '/zz.txt', $peticion);
        $check($abierta === true && $cerrada === false, 'i3 CANARIO y guarda: con la sesión abierta el archivo protegido se sirve; con la sesión cerrada, no (la firma sigue valiendo)', var_export([$abierta, $cerrada], true));

        //─── j · seal-legacy-data ───────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[j] seal-legacy-data: lo de antes de la 9, cifrado con la clave actual y sin cambiarla');
        //RETORNO-IGNORADO: en la base, PDO lanza si falla (ERRMODE_EXCEPTION) y lo cuenta «la prueba corre entera».
        $database->exec("CREATE TABLE `{$tablaSello}` (`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY, `dato` TEXT NULL)");
        $vieja = new AppKeyring(AppKeyring::generateKey());
        $actual = $vieja->rotatedTo(AppKeyring::generateKey());
        $alDia = Sealed::seal('zz al día', $proposito, $actual);
        $plantar = $database->prepare("INSERT INTO `{$tablaSello}` (`dato`) VALUES (?)");
        foreach (['zz-claro:zz en claro', Sealed::seal('zz con la vieja', $proposito, $vieja), $alDia, 'zz-basura-ilegible'] as $valor) {
            //RETORNO-IGNORADO: en la base, PDO lanza si falla (ERRMODE_EXCEPTION) y lo cuenta «la prueba corre entera».
            $plantar->execute([$valor]);
        }
        $legible = static fn(string $v): ?string => str_starts_with($v, 'zz-claro:') ? substr($v, 9) : null;
        $sitioSello = ['zz-sello' => new EncryptedData('zz-sello', $proposito, EncryptedData::KIND_COLUMN, $tablaSello, 'dato', 'id', $legible)];
        $leerSello = fn(): array => $database->query("SELECT `id`, `dato` FROM `{$tablaSello}` ORDER BY `id`")->fetchAll(\PDO::FETCH_KEY_PAIR);

        //Un fallo a mitad: el segundo sitio lanza al leer; lo del primero no puede quedar escrito.
        $antesSello = $leerSello();
        $conFallo = $sitioSello + ['zz-falla' => new EncryptedData('zz-falla', $proposito, EncryptedData::KIND_COLUMN, $tablaSello, 'dato', 'id', static function (string $v): ?string {
            throw new \RuntimeException('zz fallo a mitad');
        })];
        $lanzoSello = false;
        try {
            (new KeyRotation($database, $conFallo))->sealLegacy($actual);
        } catch (\Throwable $e) {
            $lanzoSello = true;
        }
        $check($lanzoSello && $leerSello() === $antesSello && !$database->inTransaction(), 'j1 si algo falla a mitad, la base queda EXACTAMENTE como estaba (una transacción)');

        $primera = (new KeyRotation($database, $sitioSello))->sealLegacy($actual);
        $tras = $leerSello();
        $abiertoSello = array_map(fn($v) => is_string($v) && Sealed::isSealed($v) ? Sealed::open($v, $proposito, $actual) : $v, $tras);
        $check(array_values($abiertoSello) === ['zz en claro', 'zz con la vieja', 'zz al día', 'zz-basura-ilegible']
            && Sealed::fingerprintOf((string) array_values($tras)[0]) === $actual->currentFingerprint()
            && Sealed::fingerprintOf((string) array_values($tras)[1]) === $actual->currentFingerprint(), 'j2 lo que estaba en claro y lo de la clave vieja, cifrado con la actual; todo se sigue leyendo', (string) json_encode($primera));
        $check(array_values($tras)[2] === $alDia && $primera['zz-sello']['resealed'] === 2 && $primera['zz-sello']['unreadable'] === 1, 'j3 lo que ya estaba al día NO se toca (byte a byte) y el recuento lo dice: 2 cifrados, 1 ilegible');
        $segunda = (new KeyRotation($database, $sitioSello))->sealLegacy($actual);
        $check($segunda['zz-sello']['resealed'] === 0 && $leerSello() === $tras, 'j4 la segunda corrida no vuelve a cifrar nada', (string) json_encode($segunda));
    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        $_SERVER = $servidorPrevio;
        AppKeyring::set($llaveroPrevio);
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            //RETORNO-IGNORADO: en la base, PDO lanza si falla (ERRMODE_EXCEPTION) y lo cuenta «la prueba corre entera».
            $database->exec("DROP TABLE IF EXISTS `{$tabla}`");
            $database->exec("DROP TABLE IF EXISTS `{$tablaSello}`");
            $database->prepare('DELETE FROM `pcsphp_app_config` WHERE `name` = ?')->execute([$opcion]);
            foreach ($usuarios as $id) {
                $aprobaciones = SystemApprovalsMapper::model();
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, UsersModel::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        foreach (array_reverse(array_merge(glob($directorio . '/*/*') ?: [], glob($directorio . '/*') ?: [])) as $ruta) {
            //RETORNO-IGNORADO: un temporal propio; lo que no se borre se queda en sys_get_temp_dir().
            is_dir($ruta) ? @rmdir($ruta) : @unlink($ruta);
        }
        //RETORNO-IGNORADO: un temporal propio; lo que no se borre se queda en sys_get_temp_dir().
        @rmdir($directorio);
        $tablas = $database->prepare('SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = DATABASE() AND `table_name` = ?');
        //RETORNO-IGNORADO: en la base, PDO lanza si falla (ERRMODE_EXCEPTION) y lo cuenta «la prueba corre entera».
        $tablas->execute([$tabla]);
        $quedaSello = $database->prepare('SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = DATABASE() AND `table_name` = ?');
        //RETORNO-IGNORADO: en la base, PDO lanza si falla (ERRMODE_EXCEPTION) y lo cuenta «la prueba corre entera».
        $quedaSello->execute([$tablaSello]);
        $quedanOpciones = $database->prepare('SELECT COUNT(*) FROM `pcsphp_app_config` WHERE `name` = ?');
        //RETORNO-IGNORADO: en la base, PDO lanza si falla (ERRMODE_EXCEPTION) y lo cuenta «la prueba corre entera».
        $quedanOpciones->execute([$opcion]);
        $quedanUsuarios = $database->prepare('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE `username` LIKE ?');
        //RETORNO-IGNORADO: en la base, PDO lanza si falla (ERRMODE_EXCEPTION) y lo cuenta «la prueba corre entera».
        $quedanUsuarios->execute(["{$prefijo}%"]);
        $check((int) $tablas->fetchColumn() + (int) $quedaSello->fetchColumn() + (int) $quedanOpciones->fetchColumn() + (int) $quedanUsuarios->fetchColumn() === 0 && AppKeyring::get() === $llaveroPrevio, 'z1 no queda la tabla, la opción ni el usuario de la prueba, y el llavero de la aplicación es el de antes');
    }

    return $balance();

})->setDescription('El cifrado de la 9 (ADR 0061): AES-256-GCM detecta cualquier manipulación; la clave sale de los tokens viejos y no de los nuevos (el experimento); el llavero lee lo de antes de rotar; rotar no pierde nada y si falla no cambia nada; retirar una clave en uso se niega; la puerta resuelve los propósitos; la primera arrancada genera y guarda; lo de antes de la 9 se lee; un token viejo lleva a iniciar sesión y una sesión cerrada no abre un archivo protegido. Llavero en un temporal; crea y borra una tabla zz, una opción zz y un usuario.')->setEffects([CliActions::EFFECT_DATABASE])->register();
