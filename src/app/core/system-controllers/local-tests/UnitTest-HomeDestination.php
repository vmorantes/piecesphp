<?php

//Adónde entra cada tipo de usuario al abrir el inicio del panel (HomeDestinationRegistry): los repartos de siempre,
//idénticos, y uno que registra la suite. Por HTTP con los usuarios zz-agente- y en proceso; repone el registro.
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\AdminPanel\HomeDestinationRegistry;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use SystemApprovals\Util\SystemApprovalManager;

CliActions::make('unit-tests:core/home-destination', function ($args) {

    echoTerminal("\e[33m[TEST:HomeDestination] Adónde entra cada tipo de usuario al abrir el inicio del panel\e[39m");
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

    //─── d · Los repartos de siempre, registrados por sus módulos ─────────────────────────────
    echoTerminal('[d] Los destinos por omisión, registrados al arrancar');
    $conPerfil = UsersModel::TYPES_USER_SHOULD_HAVE_PROFILE;
    $check(HomeDestinationRegistry::has(UsersModel::TYPE_USER_COMUNICACIONES) && array_filter($conPerfil, fn (int $tipo): bool => !HomeDestinationRegistry::has($tipo)) === [], 'd1 comunicaciones y los tipos con perfil tienen destino');
    $check(!HomeDestinationRegistry::has(UsersModel::TYPE_USER_ROOT) && !HomeDestinationRegistry::has(UsersModel::TYPE_USER_ADMIN_GRAL), 'd2 root y el administrador general, no: ven las tarjetas');
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
    $camino = function (string $url) use ($prefijoBase): string {
        $path = (string) parse_url($url, \PHP_URL_PATH);
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $rutaInicio = $camino(AdminPanelController::routeName('', [], true));
    $rutaPerfil = $camino(\MySpace\Controllers\MyProfileController::routeName('my-profile', [], true));
    $pedir = function (int $userID) use ($base, $rutaInicio, $prefijoBase): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($rutaInicio, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => [SessionToken::headerName() . ': ' . UserSessions::issueToken($userID)],
        ]);
        $respuesta = (string) curl_exec($handle);
        $location = preg_match('/^Location:\s*(\S+)/mi', $respuesta, $m) === 1 ? (string) parse_url($m[1], \PHP_URL_PATH) : '';
        $location = $prefijoBase !== '' && str_starts_with($location, $prefijoBase) ? substr($location, strlen($prefijoBase)) : $location;
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'location' => $location, 'body' => $respuesta];
    };

    //─── p · Idénticos a como eran: los usuarios zz-agente- de cada tipo ──────────────────────
    echoTerminal('[p] Por HTTP, cada usuario zz-agente- entra donde entraba');
    $agentes = $database->query("SELECT `id`, `type` FROM `" . UsersModel::TABLE . "` WHERE `username` LIKE 'zz-agente-%' ORDER BY `type`, `id`")->fetchAll(\PDO::FETCH_ASSOC);
    $check(count($agentes) > 0, 'banco: hay usuarios zz-agente-', (string) count($agentes));
    $vistos = [];
    foreach ($agentes as $agente) {
        $tipo = (int) $agente['type'];
        if (isset($vistos[$tipo])) {
            continue;
        }
        $vistos[$tipo] = true;
        $aprobado = SystemApprovalManager::getInstance()->isApproved(UsersModel::class, (int) $agente['id']);
        $r = $pedir((int) $agente['id']);
        if ($aprobado && in_array($tipo, $conPerfil, true)) {
            $check($r['status'] === 302 && $r['location'] === $rutaPerfil, "p tipo {$tipo} (con perfil, aprobado): a Mi perfil", "HTTP {$r['status']} · {$r['location']}");
        } elseif ($aprobado && $tipo === UsersModel::TYPE_USER_COMUNICACIONES) {
            $check($r['status'] === 200 && !str_contains($r['body'], 'data-dashboard ') && str_contains($r['body'], 'dataTables'), "p tipo {$tipo} (comunicaciones, aprobado): su listado de publicaciones", "HTTP {$r['status']}");
        } else {
            $check($r['status'] === 200 && str_contains($r['body'], 'data-dashboard '), "p tipo {$tipo}: las tarjetas del inicio", "HTTP {$r['status']}");
        }
    }
    echoTerminal(' ');

    //─── r · El registro, en proceso ─────────────────────────────────────────────────────────
    echoTerminal('[r] Un destino registrado por la suite');
    $originales = HomeDestinationRegistry::swapForTesting();
    $prefijo = 'zz-prueba-inicio-' . bin2hex(random_bytes(3));
    $ids = [];
    try {
        $u = new UsersModel();
        $u->username = "{$prefijo}-general";
        $u->email = "{$prefijo}-general@example.invalid";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Inicio';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_INSTITUCIONAL;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $ids[] = (int) $u->id;
        $usuario = new UserDataPackage((int) $u->id);
        $peticion = new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/zz-inicio'), new Headers(), [], [], (new StreamFactory())->createStream(''));
        $destino = fn (RequestRoute $request, ResponseRoute $response, UserDataPackage $user): ResponseRoute => $response->withHeader('X-Zz-Destino', 'uno-' . $user->id);

        $check(HomeDestinationRegistry::resolve($usuario, $peticion, new ResponseRoute()) === null, 'r1 sin destino para su tipo: null, ve las tarjetas');
        HomeDestinationRegistry::register(UsersModel::TYPE_USER_INSTITUCIONAL, $destino, false);
        $respuesta = HomeDestinationRegistry::resolve($usuario, $peticion, new ResponseRoute());
        $check($respuesta !== null && $respuesta->getHeaderLine('X-Zz-Destino') === 'uno-' . $u->id, 'r2 DISCRIMINANTE: con destino registrado, su respuesta, con el usuario');
        $doble = null;
        try {
            HomeDestinationRegistry::register(UsersModel::TYPE_USER_INSTITUCIONAL, $destino);
        } catch (\InvalidArgumentException $e) {
            $doble = $e->getMessage();
        }
        $check($doble !== null, 'r3 DISCRIMINANTE: dos destinos para el mismo tipo: lanza', (string) $doble);
        HomeDestinationRegistry::replace(UsersModel::TYPE_USER_INSTITUCIONAL, fn (RequestRoute $request, ResponseRoute $response): ResponseRoute => $response->withHeader('X-Zz-Destino', 'dos'), false);
        $respuesta = HomeDestinationRegistry::resolve($usuario, $peticion, new ResponseRoute());
        $check($respuesta !== null && $respuesta->getHeaderLine('X-Zz-Destino') === 'dos', 'r4 replace() lo cambia');
        HomeDestinationRegistry::replace(UsersModel::TYPE_USER_INSTITUCIONAL, $destino, true);
        $check(HomeDestinationRegistry::resolve($usuario, $peticion, new ResponseRoute()) === null, 'r5 DISCRIMINANTE: solo para aprobados y él no lo está: null');
        $check(HomeDestinationRegistry::remove(UsersModel::TYPE_USER_INSTITUCIONAL) && !HomeDestinationRegistry::has(UsersModel::TYPE_USER_INSTITUCIONAL), 'r6 remove() lo quita');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        HomeDestinationRegistry::swapForTesting($originales);
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($ids as $id) {
            foreach ($referencias as [$tablaReferencia, $columna]) {
                $database->prepare("DELETE FROM `{$tablaReferencia}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0 && HomeDestinationRegistry::has(UsersModel::TYPE_USER_COMUNICACIONES), 'z1 no queda el usuario y el registro vuelve a ser el de siempre');
    }

    return $balance();

})->setDescription('Adónde entra cada tipo de usuario al abrir el inicio del panel (HomeDestinationRegistry): los repartos de siempre, idénticos por HTTP con los usuarios zz-agente-, y uno registrado por la suite (dos para el mismo tipo lanza; replace, remove y solo aprobados). Repone el registro.')->setEffects([CliActions::EFFECT_DATABASE])->register();
