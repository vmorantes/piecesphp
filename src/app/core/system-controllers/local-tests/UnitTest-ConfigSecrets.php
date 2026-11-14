<?php

//Escribe opciones zz-config-secreta-… en la tabla de configuración y las retira; su aviso de [d] lleva la marca de la
//corrida y lo poda quien la abrió (ADR 0063).
//@areas nucleo

use PiecesPHP\Core\ConfigHelpers\ConfigOptions;
use PiecesPHP\Core\Crypto\AppKeyring;
use PiecesPHP\Core\Crypto\EncryptedData;
use PiecesPHP\Core\Crypto\EncryptedDataRegistry;
use PiecesPHP\Core\Crypto\Sealed;
use PiecesPHP\Core\CustomErrorsHandlers\GenericHandler;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/config-secrets', function ($args) {

    echoTerminal("\e[33m[TEST:ConfigSecrets] Las claves de API de la configuración se guardan selladas y se leen en claro\e[39m");
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

    $sufijo = bin2hex(random_bytes(4));
    $nombres = [];
    $nombre = function (string $caso) use ($sufijo, &$nombres): string {
        $nombre = "zz-config-secreta-{$caso}-{$sufijo}";
        $nombres[] = $nombre;
        ConfigOptions::declare($nombre, 'zz-suite-config-secrets', ConfigOptions::SHAPE_SECRET);
        return $nombre;
    };
    $enBase = fn (string $nombre): mixed => SettingsModel::getConfigValue($nombre);
    $cargar = function (string $nombre) use ($enBase): void {
        ConfigOptions::applyDeclared([$nombre => $enBase($nombre)]);
    };
    $abrir = fn (mixed $valor): ?string => is_string($valor) && Sealed::isSealed($valor) ? Sealed::open($valor, Sealed::PURPOSE_CONFIG_SECRET) : null;
    $logDir = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\');
    $registro = fn (): string => is_file("{$logDir}/" . GenericHandler::FILE_ENTRIES) ? (string) file_get_contents("{$logDir}/" . GenericHandler::FILE_ENTRIES) : '';

    try {
        //──── [p] La decisión, pura ──────────────────────────────────────────────────────────
        echoTerminal('[p] Qué se hace con lo guardado');
        $sellada = Sealed::seal('zz-clave-p', Sealed::PURPOSE_CONFIG_SECRET);
        $check(ConfigOptions::decideSecret('', null) === ['', ConfigOptions::SECRET_EMPTY] && ConfigOptions::decideSecret(null, null) === [null, ConfigOptions::SECRET_EMPTY], 'p1 vacía o sin valor: tal cual');
        $check(ConfigOptions::decideSecret($sellada, 'zz-clave-p') === ['zz-clave-p', ConfigOptions::SECRET_OPENED], 'p2 sellada que abre: lo abierto');
        $check(ConfigOptions::decideSecret($sellada, null) === ['', ConfigOptions::SECRET_UNREADABLE], 'p3 sellada que no abre: vacía');
        $check(ConfigOptions::decideSecret('zz-clave-en-claro', null) === ['zz-clave-en-claro', ConfigOptions::SECRET_LEGACY], 'p4 en claro (lo de antes): tal cual, para sellar');
        echoTerminal(' ');

        //──── [a] Lo de antes ────────────────────────────────────────────────────────────────
        echoTerminal('[a] Una clave en claro (clon anterior) se aplica igual y queda sellada tras la primera carga');
        $a = $nombre('a');
        SettingsModel::setConfigValue($a, 'zz-clave-de-antes');
        $cargar($a);
        $check(get_config($a) === 'zz-clave-de-antes', 'a1 get_config() da la clave en claro');
        $check(Sealed::isSealed((string) $enBase($a)) && $abrir($enBase($a)) === 'zz-clave-de-antes', 'a2 DISCRIMINANTE: en la base queda sellada, y abre a la misma clave');
        $selladaA = (string) $enBase($a);
        $cargar($a);
        $check($enBase($a) === $selladaA && get_config($a) === 'zz-clave-de-antes', 'a3 la segunda carga no la vuelve a sellar y sigue dando la clave');
        echoTerminal(' ');

        //──── [b] Sellada ────────────────────────────────────────────────────────────────────
        echoTerminal('[b] Una sellada se abre al cargar');
        $b = $nombre('b');
        SettingsModel::setConfigValue($b, Sealed::seal('zz-clave-sellada', Sealed::PURPOSE_CONFIG_SECRET));
        $cargar($b);
        $check(get_config($b) === 'zz-clave-sellada', 'b1 DISCRIMINANTE: get_config() da la clave abierta');
        echoTerminal(' ');

        //──── [c] Guardar ────────────────────────────────────────────────────────────────────
        echoTerminal('[c] Guardar sella; vacía no sella');
        $c = $nombre('c');
        $guardar = ConfigOptions::valueToStore($c, 'zz-clave-nueva');
        $check(is_string($guardar) && $abrir($guardar) === 'zz-clave-nueva', 'c1 DISCRIMINANTE: lo que se guarda va sellado y abre a la clave');
        $check(ConfigOptions::valueToStore($c, '') === '', 'c2 vacía sigue vacía (no se sella)');
        $check(ConfigOptions::valueToStore($c, $guardar) === $guardar, 'c3 lo ya sellado no se vuelve a sellar');
        $check(ConfigOptions::valueToStore('modelOpenAI', 'zz-no-secreta') === 'zz-no-secreta', 'c4 una opción que no es SHAPE_SECRET se guarda tal cual');
        $check(ConfigOptions::sealSecret('') === '' && $abrir(ConfigOptions::sealSecret('zz-instalacion')) === 'zz-instalacion', 'c5 sealSecret(), el sitio único (también el de la instalación inicial): vacía, vacía; con valor, sellada');
        echoTerminal(' ');

        //──── [d] Otra clave de la aplicación ────────────────────────────────────────────────
        echoTerminal('[d] Con otra clave de la aplicación no abre: vacía, y un aviso con el nombre y sin el valor');
        $d = $nombre('d');
        $otra = new AppKeyring(AppKeyring::generateKey());
        SettingsModel::setConfigValue($d, Sealed::seal('zz-clave-de-otra-instalacion', Sealed::PURPOSE_CONFIG_SECRET, $otra));
        $antes = strlen($registro());
        $cargar($d);
        $nuevo = substr($registro(), $antes);
        $check(get_config($d) === '', 'd1 DISCRIMINANTE: get_config() da vacía');
        $check(str_contains($nuevo, $d), 'd2 el aviso nombra la opción', mb_substr($nuevo, 0, 200));
        $check(!str_contains($nuevo, 'zz-clave-de-otra-instalacion') && !str_contains($nuevo, (string) $enBase($d)), 'd3 y no lleva el valor ni lo sellado');
        $antes = strlen($registro());
        $cargar($d);
        $check(!str_contains(substr($registro(), $antes), $d), 'd4 la segunda carga no vuelve a avisar');
        echoTerminal(' ');

        //──── [e] La rotación ────────────────────────────────────────────────────────────────
        echoTerminal('[e] Las tres claves están registradas: KeyRotation las re-cifra (el caso genérico, en core/app-key-crypto)');
        $sitios = EncryptedDataRegistry::all();
        foreach (['config-secret-openai' => 'OpenAIApiKey', 'config-secret-mistral' => 'MistralAIApiKey', 'config-secret-osticket' => SettingsController::OS_TICKET_KEY_CONFIG] as $clave => $opcion) {
            $sitio = $sitios[$clave] ?? null;
            $check($sitio instanceof EncryptedData && $sitio->kind() === EncryptedData::KIND_OPTION && $sitio->option() === $opcion && $sitio->purpose() === Sealed::PURPOSE_CONFIG_SECRET,
                "e1 {$opcion}: registrada como opción con el propósito config-secret");
            $check($sitio instanceof EncryptedData && $sitio->read('zz-en-claro', AppKeyring::get()) === 'zz-en-claro', "e2 {$opcion}: lo de antes se lee como la clave en claro");
        }
        $check(ConfigOptions::all()['OpenAIApiKey']['shape'] === ConfigOptions::SHAPE_SECRET && ConfigOptions::all()['MistralAIApiKey']['shape'] === ConfigOptions::SHAPE_SECRET && ConfigOptions::all()[SettingsController::OS_TICKET_KEY_CONFIG]['shape'] === ConfigOptions::SHAPE_SECRET,
            'e3 las tres, declaradas SHAPE_SECRET');
        echoTerminal(' ');

        //──── [f] El camino de fallo ─────────────────────────────────────────────────────────
        echoTerminal('[f] Si guardar la versión sellada falla, la clave en claro sigue en la base intacta');
        $f = $nombre('f');
        SettingsModel::setConfigValue($f, 'zz-clave-que-no-se-sella');
        ConfigOptions::failSealLegacyForTesting(true);
        try {
            $cargar($f);
        } finally {
            ConfigOptions::failSealLegacyForTesting(false);
        }
        $check($enBase($f) === 'zz-clave-que-no-se-sella', 'f1 DISCRIMINANTE: en la base sigue en claro, intacta');
        $check(get_config($f) === 'zz-clave-que-no-se-sella', 'f2 y get_config() la da igual');
        echoTerminal(' ');

        //──── [s] La fila de la base manda sobre secure-keys (P318, abierta) ─────────────────
        echoTerminal('[s] La fila de la base pisa lo que puso secure-keys antes de la carga (como hoy; P318 del PO)');
        $s = $nombre('s');
        set_config($s, 'zz-de-secure-keys');
        SettingsModel::setConfigValue($s, Sealed::seal('zz-de-la-base', Sealed::PURPOSE_CONFIG_SECRET));
        $cargar($s);
        $check(get_config($s) === 'zz-de-la-base', 's1 con fila sellada, gana la fila');
        set_config($s, 'zz-de-secure-keys');
        SettingsModel::setConfigValue($s, '');
        $cargar($s);
        $check(get_config($s) === '', 's2 con la fila vacía, también gana la fila (vacía)');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        ConfigOptions::failSealLegacyForTesting(false);
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        //Por el patrón de TODAS las pasadas: una caída a mitad deja las suyas.
        $database = (new \PiecesPHP\Core\BaseModel())->getDatabase();
        $tabla = SettingsModel::model()->getTable();
        foreach ($nombres as $n) {
            ConfigOptions::forgetWarned("secret-unreadable:{$n}");
        }
        $quedan = -1;
        if ($database !== null) {
            $database->prepare("DELETE FROM `{$tabla}` WHERE name LIKE ?")->execute(['zz-config-secreta-%']);
            $consulta = $database->prepare("SELECT COUNT(*) FROM `{$tabla}` WHERE name LIKE ?");
            $consulta->execute(['zz-config-secreta-%']);
            $quedan = (int) $consulta->fetchColumn();
        }
        $check($quedan === 0, 'z1 no queda ninguna opción de la suite', "quedan {$quedan}");
    }

    $total = $passed + $failed;
    echoTerminal(' ');
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Las claves de API de la configuración (ConfigOptions::SHAPE_SECRET): se guardan selladas, se abren al cargar, lo de antes se sella solo una vez (y si falla queda intacto), con otra clave de la aplicación quedan vacías con un aviso sin el valor, están registradas para la rotación y la fila de la base manda sobre secure-keys. Escribe opciones zz- y las retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
