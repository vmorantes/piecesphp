<?php

//La sesión por cabecera con un nombre que lleva «_»: el cliente la manda con «-» (SessionToken::headerName()), Apache
//la deja pasar y el servidor entra; CORS anuncia las dos formas. Por HTTP, con una extensión de prueba que fija el nombre.
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\BaseModel;
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
    $pedir = function (array $cabeceras, string $metodo = 'GET') use ($base, $ruta): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($ruta, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HEADER => true,
            CURLOPT_CUSTOMREQUEST => $metodo,
            CURLOPT_HTTPHEADER => $cabeceras,
        ]);
        $respuesta = (string) curl_exec($handle);
        $corte = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'cabeceras' => substr($respuesta, 0, $corte)];
    };

    //Una extensión del proyecto que fija el nombre solo mientras dura la suite.
    $extension = basepath('app/config/extensions/zz-prueba-sesion-' . bin2hex(random_bytes(3)) . '.php');
    $prefijo = 'zz-prueba-cabecera-' . bin2hex(random_bytes(3));
    $ids = [];

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
        $check(file_put_contents($extension, "<?php\n//Prueba de UnitTest-SessionHeaderName: se retira al terminar.\nset_config('session_token_name', '{$nombrePrueba}');\n") !== false, 'banco: la extensión de prueba fija el nombre');
        $conGuion = $pedir(['zz-sesion-prueba: ' . $jwt]);
        $check($conGuion['status'] === 200, 'h1 DISCRIMINANTE: con la cabecera «zz-sesion-prueba», entra', "HTTP {$conGuion['status']}");
        $sinCabecera = $pedir([]);
        $check($sinCabecera['status'] !== 200, 'h2 sin cabecera no entra (la prueba distingue)', "HTTP {$sinCabecera['status']}");
        $conBajo = $pedir(['zz_sesion_prueba: ' . $jwt]);
        echoTerminal("      - con la cabecera «zz_sesion_prueba» (guion bajo): HTTP {$conBajo['status']}" . ($conBajo['status'] !== 200 ? ' — Apache la descarta' : ' — llega'));
        $cors = $pedir(['Origin: https://zz-otro.invalid'], 'OPTIONS');
        $permitidas = preg_match('/^Access-Control-Allow-Headers:\s*(.+)$/mi', $cors['cabeceras'], $m) === 1 ? trim($m[1]) : '';
        $check(str_contains($permitidas, 'zz-sesion-prueba') && str_contains($permitidas, $nombrePrueba), 'h3 DISCRIMINANTE: CORS anuncia las dos formas', $permitidas);
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        if (is_file($extension)) {
            //RETORNO-IGNORADO: lo comprueba z1.
            unlink($extension);
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
        $check(!is_file($extension) && $quedan === 0 && SessionToken::tokenName() === SessionToken::TOKEN_NAME || get_config(SessionToken::TOKEN_NAME_CONFIG) === $configuradoAntes, 'z1 no queda la extensión ni el usuario, y el nombre vuelve a ser el de antes');
    }

    return $balance();

})->setDescription('La sesión por cabecera con un nombre que lleva guion bajo: el cliente la manda con guion (SessionToken::headerName()) y entra; CORS anuncia las dos formas. Por HTTP, con una extensión de prueba que fija el nombre y se retira.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
