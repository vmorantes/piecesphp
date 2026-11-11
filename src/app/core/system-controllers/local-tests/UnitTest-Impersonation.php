<?php

//Actuar como otro usuario (P204 a), por HTTP. Crea y borra usuarios zz-agente-fr-imp-* y una organización inactiva,
//con sus sesiones, aprobaciones y registros.
//@areas nucleo

use EventsLog\LogsRoutes;
use EventsLog\Mappers\LogsMapper;
use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\Impersonation;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/impersonation', function ($args) {

    echoTerminal("\e[33m[TEST:Impersonation] Actuar como otro usuario: desde el panel, firmado, con caducidad y sin root\e[39m");
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

    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'c0 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }
    if (!$check(LogsRoutes::ENABLE, 'c0 el registro de acciones está encendido (la huella lo necesita)')) {
        return $balance();
    }

    $ruta = function (string $nombre, array $parametros = []): string {
        $url = get_route($nombre, $parametros, true);
        if (!is_string($url) || $url === '') {
            return '';
        }
        return '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
    };
    $cookieName = CONNECT_AS_ANOTHER_USER_ID_COOKIE_NAME;
    $cabeceraToken = SessionToken::headerName();
    /**
     * @return array{status:int,body:string,location:string,json:mixed,setCookies:list<string>}
     */
    $pedir = function (string $path, string $jwt, ?array $post = null, array $cookies = [], array $query = []) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $cabeceras = [];
        if ($post !== null) {
            $cabeceras[] = 'X-Requested-With: XMLHttpRequest';
        }
        if ($jwt !== '') {
            $cabeceras[] = "{$cabeceraToken}: {$jwt}";
        }
        $setCookies = [];
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/') . (!empty($query) ? '?' . http_build_query($query) : ''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            //Sin esto una redirección al acceso se seguiría sola y un 302 se leería como 200.
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $cabeceras,
            CURLOPT_HEADERFUNCTION => function (\CurlHandle $h, string $linea) use (&$setCookies): int {
                if (stripos($linea, 'Set-Cookie:') === 0) {
                    $setCookies[] = trim(substr($linea, 11));
                }
                return strlen($linea);
            },
        ];
        if ($cookies !== []) {
            $opciones[CURLOPT_COOKIE] = implode('; ', array_map(fn($n, $v) => "{$n}={$v}", array_keys($cookies), $cookies));
        }
        if ($post !== null) {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($post);
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $location = (string) curl_getinfo($handle, CURLINFO_REDIRECT_URL);
        $body = is_string($body) ? $body : '';
        return ['status' => $status, 'body' => $body, 'location' => $location, 'json' => json_decode($body, true), 'setCookies' => $setCookies];
    };
    //La cabecera Set-Cookie de la suplantación: null si no la hay.
    $cookieDe = function (array $r) use ($cookieName): ?string {
        foreach ($r['setCookies'] as $linea) {
            if (str_starts_with($linea, "{$cookieName}=")) {
                return $linea;
            }
        }
        return null;
    };
    $valorDe = function (?string $linea) use ($cookieName): string {
        return $linea !== null ? urldecode((string) explode(';', substr($linea, strlen($cookieName) + 1))[0]) : '';
    };
    //Borrada: PHP manda `deleted` con una fecha pasada.
    $borrada = fn(array $r): bool => $cookieDe($r) !== null && in_array($valorDe($cookieDe($r)), ['', 'deleted'], true);
    //La franja con el nombre: solo el texto de su `span`, para no confundirla con otro sitio donde salga el nombre.
    $franja = function (string $html): ?string {
        return preg_match('/<div class="impersonation-band"[^>]*>\s*<span class="text">(.*?)<\/span>/s', $html, $m) === 1 ? $m[1] : null;
    };
    $aviso = function (string $html): ?string {
        return preg_match('/<div class="impersonation-band notice"[^>]*>\s*<span class="text">(.*?)<\/span>/s', $html, $m) === 1 ? $m[1] : null;
    };

    $marca = bin2hex(random_bytes(3));
    $prefijo = "zz-agente-fr-imp-{$marca}";
    $tablaUsuarios = UsersModel::TABLE;
    $tablaRegistro = LogsMapper::TABLE;
    $maxRegistro = (int) $database->query("SELECT COALESCE(MAX(id), 0) FROM `{$tablaRegistro}`")->fetchColumn();
    /** @var array<string, int> $usuarios */
    $usuarios = [];
    $organizacionInactiva = 0;

    $crear = function (string $sufijo, int $tipo, int $estado = UsersModel::STATUS_USER_ACTIVE, ?int $organizacion = null, string $nombre = 'Zz') use ($prefijo, &$usuarios): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        //Nadie entra con contraseña: los tokens los fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = $nombre;
        $u->secondname = '';
        $u->firstLastname = 'Agente';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = $estado;
        $u->failedAttempts = 0;
        $u->organization = $organizacion ?? OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[$sufijo] = (int) $u->id;
        return (int) $u->id;
    };
    $sidDe = function (string $jwt): string {
        $datos = BaseToken::getData($jwt);
        return $datos instanceof \stdClass && is_string($datos->sid ?? null) ? $datos->sid : '';
    };
    $cuenta = function (string $tipo, int $referencia) use ($database, $tablaRegistro, $maxRegistro): int {
        $consulta = $database->prepare("SELECT COUNT(*) FROM `{$tablaRegistro}` WHERE id > ? AND textMessage = ? AND referenceValue = ?");
        //RETORNO-IGNORADO: lo que cuenta es el COUNT que sigue, y se comprueba.
        $consulta->execute([$maxRegistro, LogsMapper::MESSAGES[$tipo], (string) $referencia]);
        return (int) $consulta->fetchColumn();
    };

    try {

        $empezar = $ruta('user-impersonation-start');
        $terminar = $ruta('user-impersonation-end');
        $panel = $ruta('admin');
        if (!$check($empezar !== '' && $terminar !== '' && $panel !== '', 'c0 existen las dos rutas y el inicio del panel', "{$empezar} {$terminar} {$panel}")) {
            return $balance();
        }

        $root = $crear('root', UsersModel::TYPE_USER_ROOT);
        $root2 = $crear('root2', UsersModel::TYPE_USER_ROOT);
        //El nombre lleva marcado: la franja tiene que escaparlo.
        $gral = $crear('gral', UsersModel::TYPE_USER_ADMIN_GRAL, UsersModel::STATUS_USER_ACTIVE, null, 'Zz<b>X</b>');
        $general = $crear('general', UsersModel::TYPE_USER_GENERAL);
        $inactivo = $crear('inactivo', UsersModel::TYPE_USER_GENERAL, UsersModel::STATUS_USER_INACTIVE);
        $nueva = OrganizationsController::createOrganization(['name' => "{$prefijo}-org", 'nit' => "{$prefijo}-org"]);
        if ($nueva !== null && $nueva->id !== null) {
            $o = new OrganizationMapper((int) $nueva->id);
            $o->status = OrganizationMapper::INACTIVE;
            $o->update();
            $organizacionInactiva = (int) $o->id;
        }
        $deOrgInactiva = $crear('org-inactiva', UsersModel::TYPE_USER_GENERAL, UsersModel::STATUS_USER_ACTIVE, $organizacionInactiva > 0 ? $organizacionInactiva : null);
        $jwtRoot = UserSessions::issueToken($root);
        $sidRoot = $sidDe($jwtRoot);
        $jwtGeneral = UserSessions::issueToken($general);
        $check(count($usuarios) === 6 && $organizacionInactiva > 0 && $sidRoot !== '', 'banco: seis usuarios, una organización inactiva y la sesión de root con su sid');

        //─── a · Empezar desde el panel ───────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[a] Empezar desde el panel');
        $inicio = $pedir($empezar, $jwtRoot, ['userID' => $gral]);
        $linea = $cookieDe($inicio);
        $valor = $valorDe($linea);
        $leido = Impersonation::read($valor, $sidRoot, $root);
        $check($inicio['status'] === 200 && $linea !== null && stripos($linea, 'HttpOnly') !== false && ($leido['targetID'] ?? null) === $gral && $cuenta(LogsMapper::MSG_IMPERSONATION_START, $gral) === 1,
            'a1 root inicia hacia un administrador general: 200, Set-Cookie HttpOnly firmada y UN inicio', "HTTP {$inicio['status']} · " . ($linea ?? 'sin cookie') . ' · inicios ' . $cuenta(LogsMapper::MSG_IMPERSONATION_START, $gral));
        $pagina = $pedir($panel, $jwtRoot, null, [$cookieName => rawurlencode($valor)]);
        $enFranja = $franja($pagina['body']);
        $check($pagina['status'] === 200 && $enFranja !== null && str_contains($enFranja, "{$prefijo}") === false && str_contains($enFranja, 'Agente'),
            'a2 la petición siguiente es del suplantado y la página lleva la franja con su nombre', "HTTP {$pagina['status']} · " . var_export($enFranja, true));
        $check($enFranja !== null && str_contains($enFranja, 'Zz&lt;b&gt;X&lt;/b&gt;') && !str_contains($enFranja, '<b>'), 'a3 el nombre con <b> sale escapado en la franja', var_export($enFranja, true));

        //─── b · Lo que no empieza ────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] Lo que no empieza');
        $porGet = $pedir($panel, $jwtRoot, null, [], [CONNECT_AS_ANOTHER_USER_ID_GET_PARAM_NAME => $general]);
        $check($porGet['status'] === 200 && $franja($porGet['body']) === null && $cookieDe($porGet) === null && $cuenta(LogsMapper::MSG_IMPERSONATION_START, $general) === 0,
            'b1 el GET con el parámetro, sin cookie: no suplanta, no escribe cookie ni deja inicio', "HTTP {$porGet['status']}");
        $rechazos = [
            'b2 hacia otro root' => $root2,
            'b3 hacia el usuario de sistema' => UsersModel::SYSTEM_USER_ID,
            'b4 hacia un id que no existe' => (int) $database->query("SELECT COALESCE(MAX(id), 0) + 1000 FROM `{$tablaUsuarios}`")->fetchColumn(),
            'b5 hacia un usuario inactivo' => $inactivo,
            'b6 hacia un usuario de una organización inactiva' => $deOrgInactiva,
        ];
        foreach ($rechazos as $nombre => $objetivo) {
            $r = $pedir($empezar, $jwtRoot, ['userID' => $objetivo]);
            $mensaje = is_string($r['json']['message'] ?? null) ? $r['json']['message'] : '';
            $check($r['status'] === 400 && $cookieDe($r) === null && $mensaje !== '' && $cuenta(LogsMapper::MSG_IMPERSONATION_START, $objetivo) === 0,
                "{$nombre}: 400 con su motivo, sin cookie ni inicio", "HTTP {$r['status']} · {$mensaje}");
        }
        $noRoot = $pedir($empezar, $jwtGeneral, ['userID' => $gral]);
        $check($noRoot['status'] === 403 && $cookieDe($noRoot) === null, 'b7 un no root llama a empezar: 403', (string) $noRoot['status']);
        //Suplantando, el rol de la petición es el del suplantado: la ruta, solo de root, ya responde 403.
        $yaSuplantando = $pedir($empezar, $jwtRoot, ['userID' => $general], [$cookieName => rawurlencode($valor)]);
        $check(in_array($yaSuplantando['status'], [400, 403], true) && $cookieDe($yaSuplantando) === null && $cuenta(LogsMapper::MSG_IMPERSONATION_START, $general) === 0,
            'b8 empezar estando ya suplantando: rechazado (403 por el rol del suplantado), sin cookie ni inicio', (string) $yaSuplantando['status']);

        //─── c · La cookie que no vale ────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] La cookie que no vale no corta la página ni saca a root');
        $conCookie = fn(string $v): array => $pedir($panel, $jwtRoot, null, [$cookieName => rawurlencode($v)]);
        $inexistente = $conCookie(Impersonation::cookieValue($sidRoot, $root, $rechazos['b4 hacia un id que no existe']));
        $check($inexistente['status'] === 200 && $franja($inexistente['body']) === null && $borrada($inexistente) && !str_contains($inexistente['body'], 'El id de usuario con el que intenta ingresar no existe'),
            'c1 firmada hacia un id que no existe: 200 como root, sin die, cookie borrada', "HTTP {$inexistente['status']} · " . ($cookieDe($inexistente) ?? 'sin Set-Cookie'));
        $haciaInactivo = $conCookie(Impersonation::cookieValue($sidRoot, $root, $inactivo));
        $check($haciaInactivo['status'] === 200 && $franja($haciaInactivo['body']) === null && $borrada($haciaInactivo),
            'c2 firmada hacia un inactivo: root sigue DENTRO con su sesión y la cookie se borra', "HTTP {$haciaInactivo['status']}" . ($haciaInactivo['location'] !== '' ? " → {$haciaInactivo['location']}" : '') . ' · ' . ($cookieDe($haciaInactivo) ?? 'sin Set-Cookie'));
        $caducada = $conCookie(Impersonation::cookieValue($sidRoot, $root, $gral, time() - Impersonation::lifetime() - 1));
        $check($caducada['status'] === 200 && $franja($caducada['body']) === null && $borrada($caducada) && $cuenta(LogsMapper::MSG_IMPERSONATION_EXPIRED, $gral) === 1,
            'c3 con startedAt = ahora − 3601: no suplanta, UNA caducidad y la cookie se borra', "HTTP {$caducada['status']} · caducidades " . $cuenta(LogsMapper::MSG_IMPERSONATION_EXPIRED, $gral));
        $casiCaducada = $conCookie(Impersonation::cookieValue($sidRoot, $root, $gral, time() - Impersonation::lifetime() + 30));
        $check($casiCaducada['status'] === 200 && $franja($casiCaducada['body']) !== null, 'c3 DISCRIMINANTE: con ahora − 3570, sí suplanta', "HTTP {$casiCaducada['status']}");
        $otraSesion = $sidDe(UserSessions::issueToken($root));
        $deOtraSesion = $conCookie(Impersonation::cookieValue($otraSesion, $root, $gral));
        $check($otraSesion !== '' && $deOtraSesion['status'] === 200 && $franja($deOtraSesion['body']) === null && $borrada($deOtraSesion), 'c4 firmada para OTRA sesión de root: no suplanta');
        $enClaro = $conCookie((string) $gral);
        $check($enClaro['status'] === 200 && $franja($enClaro['body']) === null && $borrada($enClaro), 'c5 la cookie vieja en claro (solo el id): no suplanta y se borra');
        //El aviso de c3 vive en la sesión PHP: se le devuelven al servidor las cookies que puso, menos la de la suplantación.
        $jarra = [];
        foreach ($caducada['setCookies'] as $l) {
            [$par] = explode(';', $l);
            [$n, $v] = array_pad(explode('=', $par, 2), 2, '');
            if ($n !== $cookieName) {
                $jarra[$n] = $v;
            }
        }
        $textoAviso = $aviso($caducada['body']);
        $otraVez = $pedir($panel, $jwtRoot, null, $jarra);
        $check($jarra !== [] && $textoAviso !== null && str_contains($textoAviso, (string) Impersonation::MINUTES) && $otraVez['status'] === 200 && $aviso($otraVez['body']) === null,
            'c6 la página de la caducidad enseña el aviso, y la siguiente de la misma sesión ya no', var_export($textoAviso, true) . ' · cookies ' . implode(',', array_keys($jarra)));

        //─── d · Terminar ─────────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[d] Terminar');
        $fin = $pedir($terminar, $jwtRoot, [], [$cookieName => rawurlencode($valor)]);
        $check($fin['status'] === 200 && $borrada($fin) && $cuenta(LogsMapper::MSG_IMPERSONATION_END, $root) === 1, 'd1 terminar suplantando: 200, cookie borrada y UN fin', "HTTP {$fin['status']} · fines " . $cuenta(LogsMapper::MSG_IMPERSONATION_END, $root));
        $sinSuplantar = $pedir($terminar, $jwtRoot, []);
        $check($sinSuplantar['status'] === 400 && $cuenta(LogsMapper::MSG_IMPERSONATION_END, $root) === 1, 'd2 terminar sin suplantar: 400 y sin otro fin', (string) $sinSuplantar['status']);
        //Un pendiente tiene el rol recortado (index.php); quien actúa como él tiene que poder volver (H60).
        $pendiente = $crear('pendiente', UsersModel::TYPE_USER_GENERAL, UsersModel::STATUS_USER_APPROVED_PENDING);
        $haciaPendiente = Impersonation::cookieValue($sidRoot, $root, $pendiente);
        $finPendiente = $pedir($terminar, $jwtRoot, [], [$cookieName => rawurlencode($haciaPendiente)]);
        $check($finPendiente['status'] === 200 && $borrada($finPendiente) && $cuenta(LogsMapper::MSG_IMPERSONATION_END, $root) === 2, 'd3 terminar suplantando a un pendiente (rol recortado): 200, cookie borrada y otro fin', "HTTP {$finPendiente['status']} · fines " . $cuenta(LogsMapper::MSG_IMPERSONATION_END, $root));

        //─── e · La revocación del suplantado no echa a root ──────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[e] La revocación del suplantado no echa a root');
        $haciaGeneral = Impersonation::cookieValue($sidRoot, $root, $general);
        //Un general con el perfil sin completar va a su perfil (302): se sigue ese salto, y no el del acceso.
        $acceso = $ruta('users-form-login');
        $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
        $comoGeneral = function () use ($pedir, $panel, $jwtRoot, $cookieName, $haciaGeneral, $acceso, $prefijoBase): array {
            $r = $pedir($panel, $jwtRoot, null, [$cookieName => rawurlencode($haciaGeneral)]);
            if ($r['status'] === 302 && $r['location'] !== '' && !str_contains($r['location'], $acceso)) {
                $destino = (string) parse_url($r['location'], \PHP_URL_PATH);
                $destino = $prefijoBase !== '' && str_starts_with($destino, $prefijoBase) ? substr($destino, strlen($prefijoBase)) : $destino;
                $r = $pedir($destino, $jwtRoot, null, [$cookieName => rawurlencode($haciaGeneral)]);
            }
            return $r;
        };
        $antesDeMarca = $comoGeneral();
        //Solo la columna, como `$soloColumna` de session-revocation: cerrar sus filas no es lo que se mide.
        $marcaPosterior = date('Y-m-d H:i:s', ((int) BaseToken::getCreated($jwtRoot)) + 2);
        $columna = UsersModel::model();
        $columna->resetAll();
        $columna->update(['sessionsValidFrom' => $marcaPosterior])->where(['id' => $general])->execute();
        $trasMarca = $comoGeneral();
        $check($antesDeMarca['status'] === 200 && $franja($antesDeMarca['body']) !== null, 'e0 CANARIO: root suplanta al general antes de moverle la marca', "HTTP {$antesDeMarca['status']}");
        $check($trasMarca['status'] === 200 && $franja($trasMarca['body']) !== null && $cookieDe($trasMarca) === null,
            'e1 con la marca del SUPLANTADO movida después del token de root, root sigue DENTRO y suplantando', "HTTP {$trasMarca['status']}" . ($trasMarca['location'] !== '' ? " → {$trasMarca['location']}" : '') . " · marca {$marcaPosterior}");

    } catch (\Throwable $e) {
        $check(false, 'sin excepciones', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $ids = implode(',', array_map('intval', $usuarios ?: [0]));
        $database->prepare("DELETE FROM `{$tablaRegistro}` WHERE id > ? AND (textMessageVariables LIKE ? OR referenceValue IN ('" . implode("','", array_map('intval', $usuarios ?: [0])) . "'))")->execute([$maxRegistro, "%{$prefijo}%"]);
        $database->exec("DELETE FROM `{$tablaRegistro}` WHERE createdBy IN ({$ids})");
        $database->exec('DELETE FROM `' . UserSessions::TABLE . "` WHERE userID IN ({$ids})");
        $database->exec("DELETE FROM `login_attempts` WHERE userID IN ({$ids})");
        $aprobaciones = SystemApprovalsMapper::model();
        $referencias = [$tablaUsuarios => array_values($usuarios), OrganizationMapper::TABLE => $organizacionInactiva > 0 ? [$organizacionInactiva] : []];
        foreach ($referencias as $tabla => $idsDeTabla) {
            foreach ($idsDeTabla as $id) {
                $aprobaciones->resetAll();
                $aprobaciones->delete(new \PiecesPHP\Core\Database\ORM\Statements\WhereSegment([
                    new \PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem('referenceTable', \PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem::EQUAL_OPERATOR, $tabla),
                    new \PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem('referenceValue', \PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem::EQUAL_OPERATOR, (string) $id, \PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem::AND_OPERATOR),
                ]))->execute();
            }
        }
        foreach ($usuarios as $id) {
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $perfiles->delete(['belongsTo' => $id])->execute();
        }
        $database->exec("DELETE FROM `{$tablaUsuarios}` WHERE id IN ({$ids})");
        if ($organizacionInactiva > 0) {
            $database->exec('DELETE FROM `' . OrganizationMapper::TABLE . "` WHERE id = {$organizacionInactiva}");
        }
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE '{$prefijo}%'")->fetchColumn();
        $quedan += (int) $database->query("SELECT COUNT(*) FROM `{$tablaRegistro}` WHERE id > {$maxRegistro} AND textMessageVariables LIKE '%{$prefijo}%'")->fetchColumn();
        $quedan += (int) $database->query('SELECT COUNT(*) FROM `' . OrganizationMapper::TABLE . "` WHERE name LIKE '{$prefijo}%'")->fetchColumn();
        $quedan += (int) $database->query('SELECT COUNT(*) FROM `' . SystemApprovalsMapper::TABLE . "` WHERE (referenceTable = '{$tablaUsuarios}' AND referenceValue IN ('" . implode("','", array_map('intval', $usuarios ?: [0])) . "')) OR (referenceTable = '" . OrganizationMapper::TABLE . "' AND referenceValue = '{$organizacionInactiva}')")->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario, organización, aprobación ni registro de la prueba', (string) $quedan);
    }

    return $balance();

})->setDescription('Actuar como otro usuario (P204 a), por HTTP: empezar y terminar solo por las dos rutas POST del panel; la cookie es HttpOnly, firmada y atada a la sesión de root; el GET ya no activa nada; nunca hacia un root, el usuario de sistema, un id que no existe, un inactivo o alguien de una organización inactiva; una cookie que no vale no corta la página ni saca a root; caduca a los 60 minutos y lo avisa una vez; y deja su huella al empezar, al terminar y al caducar. Crea y borra seis usuarios zz-agente-fr-imp-* y una organización.')->setEffects([CliActions::EFFECT_DATABASE])->register();
