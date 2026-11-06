<?php

//Con sesión, el formulario de acceso vuelve a la página pedida (`next`, si es de este sitio) o al panel, nunca a la portada
//(P291 a). Por HTTP, con la sesión de un usuario zz- abierta por la prueba. Siembra zz- y lo retira.
//@areas nucleo

use EventsLog\Mappers\LogsMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/login-redirect-with-session', function ($args) {

    echoTerminal("\e[33m[TEST:LoginRedirectWithSession] Con sesión, el acceso vuelve a la página pedida o al panel\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que core/user-sessions.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'p0 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }

    $ruta = function (string $nombre): string {
        $url = get_route($nombre, [], true);
        return is_string($url) && $url !== '' ? '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/') : '';
    };
    $acceso = $ruta('users-form-login');
    $panel = $ruta('admin');
    $listado = $ruta('users-list');
    $basePath = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::tokenName();
    //La redirección del acceso: su estado y adónde manda.
    $ir = function (?string $next, string $jwt) use ($base, $acceso, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($acceso, '/') . ($next !== null ? '?next=' . rawurlencode($next) : ''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ]);
        $respuesta = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $location = is_string($respuesta) && preg_match('/^Location:\s*(\S+)/mi', $respuesta, $m) === 1 ? trim($m[1]) : '';
        return ['status' => $status, 'location' => $location, 'path' => rtrim((string) parse_url($location, \PHP_URL_PATH), '/')];
    };
    //Las rutas de get_route() en el terminal no llevan la ruta base de la instalación: el panel servido es $basePath . $panel.
    $panelServido = $basePath . rtrim($panel, '/');
    $check($acceso !== '' && $panel !== '' && $listado !== '' && $panelServido !== $basePath, 'p1 existen el acceso, el panel y el listado de usuarios, y el panel no es la portada', "{$acceso} · {$panel} · {$listado}");

    $tablaUsuarios = UsersModel::TABLE;
    $tablaSesiones = UserSessions::TABLE;
    $prefijo = 'zz-prueba-acceso-' . bin2hex(random_bytes(3));
    $usuarios = [];
    $maxLogPrevio = (int) $database->query('SELECT COALESCE(MAX(id), 0) FROM `' . LogsMapper::TABLE . '`')->fetchColumn();

    try {
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.com";
        //Nadie entra con contraseña: la sesión la abre la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Acceso';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ROOT;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[] = (int) $u->id;
        $sid = UserSessions::open((int) $u->id);
        $jwt = SessionToken::generateToken(['id' => (int) $u->id, 'sid' => $sid], null, SessionToken::DURATION_MAX, false);

        $haciaListado = $base . $listado;
        $r = $ir($haciaListado, $jwt);
        $check($r['status'] === 302 && $r['location'] === $haciaListado, 'a1 con next de una página del panel, vuelve a ella', "HTTP {$r['status']} → {$r['location']}");
        $r = $ir(null, $jwt);
        $check($r['status'] === 302 && $r['path'] === $panelServido, 'a2 sin next, al panel', "HTTP {$r['status']} → {$r['location']}");
        $sinNext = $r;
        $r = $ir('https://example.com' . $listado, $jwt);
        $check($r['status'] === 302 && $r['path'] === $panelServido, 'a3 con next de otro host, al panel', "HTTP {$r['status']} → {$r['location']}");
        $r = $ir($base . $panel . '../../fuera', $jwt);
        $check($r['status'] === 302 && $r['path'] === $panelServido, 'a4 con «..» en next, al panel', "HTTP {$r['status']} → {$r['location']}");
        $check($sinNext['path'] !== $basePath && $sinNext['path'] !== '', 'a5 en ningún caso a la portada', "{$sinNext['path']} frente a {$basePath}");
        $r = $ir($haciaListado, 'zz-sin-sesion');
        $check($r['status'] === 200, 'a6 CANARIO: sin sesión, el acceso se pinta y no redirige', "HTTP {$r['status']} → {$r['location']}");

    } catch (\Throwable $e) {
        $check(false, 'z0 la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        try {
            if ($usuarios !== []) {
                $marcas = implode(',', array_fill(0, count($usuarios), '?'));
                $database->prepare("DELETE FROM `{$tablaSesiones}` WHERE userID IN ({$marcas})")->execute($usuarios);
                $database->prepare('DELETE FROM `' . LogsMapper::TABLE . "` WHERE id > ? AND createdBy IN ({$marcas})")->execute(array_merge([$maxLogPrevio], $usuarios));
            }
            foreach ($usuarios as $id) {
                $aprobaciones = SystemApprovalsMapper::model();
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tablaUsuarios),
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
        $consulta = $database->prepare("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE ?");
        $consulta->execute(["{$prefijo}%"]);
        $quedan = (int) $consulta->fetchColumn();
        if ($usuarios !== []) {
            $sesiones = $database->prepare("SELECT COUNT(*) FROM `{$tablaSesiones}` WHERE userID IN (" . implode(',', array_fill(0, count($usuarios), '?')) . ')');
            $sesiones->execute($usuarios);
            $quedan += (int) $sesiones->fetchColumn();
        }
        $check($quedan === 0, 'z1 no queda ningún usuario ni sesión de la prueba', (string) $quedan);
    }

    return $balance();

})->setDescription('Con sesión, el formulario de acceso redirige a `next` si es de este sitio y, si no o sin él, al panel; nunca a la portada. Crea y borra un usuario zz- y su sesión.')->setEffects([CliActions::EFFECT_DATABASE])->register();
