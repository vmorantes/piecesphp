<?php

//Sesión por cabecera con «_» en el nombre (va con «-»); la extensión de prueba solo actúa con la marca de la corrida (H426).
//@areas nucleo
//@requires-module Organizations\OrganizationsRoutes

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\CustomErrorsHandlers\GenericHandler;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/session-header-name', function ($args) {

    echoTerminal("\e[33m[TEST:SessionHeaderName] La sesión por cabecera con un nombre con guion bajo\e[39m");
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

    $nombrePrueba = 'zz_sesion_prueba';
    //La extensión solo actúa con la marca de esta corrida: sin gates ni con-marca, la suite abre la suya.
    $corrida = GenericHandler::startTestRun();

    //─── n · El nombre de la cabecera, en proceso ──────────────────────────────────────────────
    echoTerminal('[n] headerName(): el nombre con «-» en lugar de «_»');
    $configuradoAntes = get_config(SessionToken::TOKEN_NAME_CONFIG);
    $check(SessionToken::headerName() === str_replace('_', '-', SessionToken::tokenName()), 'n1 con el nombre de hoy, el mismo con «-»', SessionToken::headerName());
    set_config(SessionToken::TOKEN_NAME_CONFIG, $nombrePrueba);
    $check(SessionToken::tokenName() === $nombrePrueba && SessionToken::headerName() === 'zz-sesion-prueba', 'n2 DISCRIMINANTE: con «zz_sesion_prueba», la cabecera es «zz-sesion-prueba»', SessionToken::headerName());
    set_config(SessionToken::TOKEN_NAME_CONFIG, $configuradoAntes);
    echoTerminal(' ');

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que backgrounds-delete.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'banco: hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $ruta = (string) parse_url(AdminPanelController::routeName('', [], true), \PHP_URL_PATH);
    $ruta = $prefijoBase !== '' && str_starts_with($ruta, $prefijoBase) ? substr($ruta, strlen($prefijoBase)) : $ruta;
    $pedir = function (array $cabeceras, string $metodo = 'GET', bool $conMarca = true) use ($base, $ruta): array {
        $handle = \Terminal\TestRun::curlInit();
        //Sin marca, como el navegador de una persona: curl_setopt_array a secas no añade la cabecera de la corrida.
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($ruta, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HEADER => true,
            CURLOPT_CUSTOMREQUEST => $metodo !== '' ? $metodo : null,
            CURLOPT_HTTPHEADER => $cabeceras,
        ];
        if ($conMarca) {
            \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        } else {
            curl_setopt_array($handle, $opciones);
        }
        $respuesta = (string) curl_exec($handle);
        $corte = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'cabeceras' => substr($respuesta, 0, $corte)];
    };

    //Una extensión del proyecto que fija el nombre solo mientras dura la suite.
    $extension = basepath('app/config/extensions/zz-prueba-sesion-' . bin2hex(random_bytes(3)) . '.php');
    //Se escribe aquí y se renombra: una petición simultánea nunca incluye la extensión a medias. Termina en .tmp: ni la
    //carga de extensiones (solo .php) ni la parada de gates (zz-prueba-sesion-*.php) lo ven.
    $extensionTemporal = $extension . '.' . bin2hex(random_bytes(3)) . '.tmp';
    $prefijo = 'zz-prueba-cabecera-' . bin2hex(random_bytes(3));
    $ids = [];
    $nombreDeSiempre = SessionToken::tokenName();
    $cabeceraMarca = 'HTTP_' . strtoupper(str_replace('-', '_', GenericHandler::TEST_RUN_HEADER));
    //Si el proceso muere a mitad (un fatal, un exit), la extensión no se queda puesta para toda la instalación.
    register_shutdown_function(static function () use ($extension, $extensionTemporal): void {
        clearstatcache();
        foreach ([$extension, $extensionTemporal] as $archivo) {
            if (is_file($archivo) && !unlink($archivo)) {
                echoTerminal("\e[31mNo se pudo retirar {$archivo}: bórrala a mano.\e[39m");
            }
        }
    });

    try {
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.invalid";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Cabecera';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ROOT;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $ids[] = (int) $u->id;
        $jwt = UserSessions::issueToken((int) $u->id);

        echoTerminal('[h] Por HTTP, con el nombre «' . $nombrePrueba . '» en el servidor');
        $contenido = "<?php\n//Prueba de UnitTest-SessionHeaderName: se retira al terminar. Solo actúa con la marca de su corrida.\n"
            . 'if (($_SERVER[' . var_export($cabeceraMarca, true) . '] ?? null) === ' . var_export($corrida['run'], true) . ") {\n"
            . '    set_config(' . var_export(SessionToken::TOKEN_NAME_CONFIG, true) . ', ' . var_export($nombrePrueba, true) . ");\n}\n";
        $check(file_put_contents($extensionTemporal, $contenido) === strlen($contenido) && rename($extensionTemporal, $extension), 'banco: la extensión de prueba fija el nombre (escrita de una vez)');
        $conGuion = $pedir(['zz-sesion-prueba: ' . $jwt]);
        $check($conGuion['status'] === 200, 'h1 DISCRIMINANTE: con la cabecera «zz-sesion-prueba», entra', "HTTP {$conGuion['status']}");
        $sinCabecera = $pedir([]);
        $check($sinCabecera['status'] !== 200, 'h2 sin cabecera no entra (la prueba distingue)', "HTTP {$sinCabecera['status']}");
        $conBajo = $pedir(['zz_sesion_prueba: ' . $jwt]);
        echoTerminal("      - con la cabecera «zz_sesion_prueba» (guion bajo): HTTP {$conBajo['status']}" . ($conBajo['status'] !== 200 ? ' — Apache la descarta' : ' — llega'));
        $cors = $pedir(['Origin: https://zz-otro.invalid'], 'OPTIONS');
        $permitidas = preg_match('/^Access-Control-Allow-Headers:\s*(.+)$/mi', $cors['cabeceras'], $m) === 1 ? trim($m[1]) : '';
        $check(str_contains($permitidas, 'zz-sesion-prueba') && str_contains($permitidas, $nombrePrueba), 'h3 DISCRIMINANTE: CORS anuncia las dos formas', $permitidas);
        $cookieDeSiempre = 'Cookie: ' . $nombreDeSiempre . '=' . $jwt;
        $marcadaDeSiempre = $pedir([$cookieDeSiempre]);
        $check($marcadaDeSiempre['status'] !== 200, "h4 con la marca, la cookie «{$nombreDeSiempre}» no entra: la extensión actúa", "HTTP {$marcadaDeSiempre['status']}");
        $sinMarca = $pedir([$cookieDeSiempre], 'GET', false);
        $check($sinMarca['status'] === 200, "h5 DISCRIMINANTE: SIN la marca, la cookie «{$nombreDeSiempre}» entra: el navegador de una persona no ve el nombre cambiado", "HTTP {$sinMarca['status']}");
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        foreach ([$extension, $extensionTemporal] as $archivo) {
            if (is_file($archivo)) {
                //RETORNO-IGNORADO: lo comprueba z1.
                unlink($archivo);
            }
        }
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($ids as $id) {
            foreach ($referencias as [$tablaReferencia, $columna]) {
                $database->prepare("DELETE FROM `{$tablaReferencia}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        clearstatcache();
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check(!is_file($extension) && !is_file($extensionTemporal) && $quedan === 0 && SessionToken::tokenName() === $nombreDeSiempre && get_config(SessionToken::TOKEN_NAME_CONFIG) === $configuradoAntes, 'z1 no queda la extensión ni el usuario, y el nombre vuelve a ser el de antes', 'extensión ' . (is_file($extension) ? 'queda' : 'no queda') . ", usuarios {$quedan}, nombre " . SessionToken::tokenName());
        if ($corrida['created']) {
            $podados = GenericHandler::pruneTestRun($corrida['run']);
            echoTerminal('      - errores de prueba podados de la corrida propia: ' . $podados['entries'] . ($podados['error'] !== null ? " — {$podados['error']}" : ''));
        }
    }

    return $balance();

})->setDescription('La sesión por cabecera con un nombre que lleva guion bajo: el cliente la manda con guion (SessionToken::headerName()) y entra; CORS anuncia las dos formas. Por HTTP, con una extensión de prueba que fija el nombre solo con la marca de la corrida y se retira.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
