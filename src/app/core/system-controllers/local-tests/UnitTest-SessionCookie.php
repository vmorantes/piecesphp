<?php

//La cookie de sesión la pone y la borra el servidor (H188), por HTTP, y ningún JS propio guarda el token.
//Crea un usuario zz-agente-cookie-* con sus sesiones y sus intentos de acceso; borra todo.
//@areas nucleo

use EventsLog\Mappers\LogsMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\BaseToken;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\SessionCookie;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/session-cookie', function ($args) {

    echoTerminal("\e[33m[TEST:SessionCookie] La cookie de sesión la pone el servidor, HttpOnly y con la caducidad del token\e[39m");
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

    //─── e · Estático: ningún JS propio guarda el token ─────────────────────────────────────────
    echoTerminal('[e] Ningún JS propio guarda el token');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    $scripts = [];
    if (is_dir("{$proyecto}/.git")) {
        $salida = (string) shell_exec('git -C ' . escapeshellarg($proyecto) . " -c core.quotePath=false ls-files -z -- 'src/**/*.js' 2>/dev/null");
        $scripts = array_values(array_filter(explode("\0", $salida), fn(string $f): bool => $f !== '' && !str_contains($f, '/vendor/') && !str_contains($f, '/plugins/') && !str_ends_with($f, '.min.js')));
    }
    //Un setItem con la clave del token, o un document.cookie con su nombre y valor (`=;` es borrar la vieja). Las sesiones
    //aisladas (`this.tokenName`, otro nombre) siguen guardando el suyo: eso lo mira e2.
    $guarda = '/localStorage\.setItem\(\s*PiecesPHPSystemUserHelper\.localJWTAuthName\s*,|document\.cookie\s*=\s*`\$\{(?:PiecesPHPSystemUserHelper\.localJWTAuthName|name)\}=(?!;)/';
    $sembrado = "localStorage.setItem(PiecesPHPSystemUserHelper.localJWTAuthName, x)\ndocument.cookie = `\${PiecesPHPSystemUserHelper.localJWTAuthName}=\${encodeURI(JWT)};path=/`\ndocument.cookie = `\${name}=;expires=\${expired};path=/`\nlocalStorage.setItem('user_name_storage', u)\n";
    $cazados = preg_match_all($guarda, $sembrado);
    $check($cazados === 2, 'e0 CANARIO: el patrón caza el setItem y el document.cookie con valor, no el borrado ni otra clave', (string) $cazados);
    $hallados = [];
    foreach ($scripts as $relativo) {
        $texto = (string) @file_get_contents("{$proyecto}/{$relativo}");
        foreach (preg_split('/\R/', $texto) ?: [] as $indice => $linea) {
            if (preg_match($guarda, $linea) === 1) {
                $hallados[] = $relativo . ':' . ($indice + 1);
            }
        }
    }
    $check($scripts !== [] && $hallados === [], 'e1 ningún JS propio escribe el token en localStorage ni en document.cookie (' . count($scripts) . ' JS)', implode(', ', $hallados));
    //El manejador genérico guarda el de una sesión aislada, pero con el nombre de la de usuarios sale antes de guardar.
    $generico = (string) @file_get_contents(basepath('statics/core/js/user-system/PiecesPHPGenericHandlerSession.js'));
    $inicio = strpos($generico, 'setJWT(JWT) {');
    $cuerpo = $inicio === false ? '' : substr($generico, $inicio, 200);
    $check(preg_match('/^setJWT\(JWT\) \{\s*if \(this\.isUserSession\) \{\s*this\.forgetLegacyToken\(\)\s*return\s*\}/', $cuerpo) === 1, 'e2 PiecesPHPGenericHandlerSession no guarda el token de la sesión de usuarios (sale antes; las aisladas, como antes)', mb_substr($cuerpo, 0, 120));
    echoTerminal(' ');

    //─── El resto, por HTTP ──────────────────────────────────────────────────────────────────────
    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la suite de las sesiones.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'c1 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }
    $ruta = function (string $nombre, array $parametros = []): string {
        $url = get_route($nombre, $parametros, true);
        if (!is_string($url) || $url === '') {
            return '';
        }
        return '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
    };
    $panel = $ruta('admin');
    $nombre = SessionToken::tokenName();

    //Una petición con la cabecera del token (null: sin ella) y la cookie (null: sin ella); devuelve el estado, el JSON y
    //las cabeceras Set-Cookie de la cookie de sesión.
    $pedir = function (string $path, ?string $cabecera, ?string $cookie, ?array $post = null, bool $xhr = true) use ($base, $nombre): array {
        $handle = \Terminal\TestRun::curlInit();
        $cabeceras = $xhr ? ['X-Requested-With: XMLHttpRequest'] : [];
        if ($cabecera !== null) {
            //curl quita una cabecera «Nombre:» sin valor; «Nombre;» la manda vacía, que es lo que se prueba en b2.
            $cabeceras[] = $cabecera === '' ? "{$nombre};" : "{$nombre}: {$cabecera}";
        }
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $cabeceras,
        ];
        if ($cookie !== null) {
            $opciones[CURLOPT_COOKIE] = "{$nombre}={$cookie}";
        }
        if ($post !== null) {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($post);
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $respuesta = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $tamCabecera = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $respuesta = is_string($respuesta) ? $respuesta : '';
        $cabecerasTexto = substr($respuesta, 0, $tamCabecera);
        $body = substr($respuesta, $tamCabecera);
        $setCookies = [];
        foreach (preg_split('/\R/', $cabecerasTexto) ?: [] as $linea) {
            if (stripos($linea, 'set-cookie:') === 0 && str_starts_with(ltrim(substr($linea, 11)), "{$nombre}=")) {
                $setCookies[] = trim(substr($linea, 11));
            }
        }
        return ['status' => $status, 'json' => json_decode($body, true), 'cookies' => $setCookies];
    };
    //Por XHR, una sesión rechazada es 401; una aceptada, 200 (o 302 a completar el perfil, que no es el acceso).
    $entra = fn(?string $cabecera, ?string $cookie): bool => in_array($pedir($panel, $cabecera, $cookie)['status'], [200, 302], true);
    //La Set-Cookie que pone un valor (no la que borra).
    $puesta = function (array $cookies) use ($nombre): ?string {
        foreach ($cookies as $c) {
            if (!str_starts_with($c, "{$nombre}=;") && !str_starts_with($c, "{$nombre}=deleted")) {
                return $c;
            }
        }
        return null;
    };
    $valor = fn(string $setCookie): string => urldecode((string) explode(';', substr($setCookie, strlen($nombre) + 1), 2)[0]);
    //Lo que se imprime, sin el valor del token.
    $sinValor = fn(string $setCookie): string => preg_replace('/^([^=]+)=[^;]*/', '$1=<token>', $setCookie) ?? '';

    $prefijo = 'zz-agente-cookie-' . bin2hex(random_bytes(3));
    $clave = bin2hex(random_bytes(12)) . 'Aa1!';
    $usuarios = [];
    $tablaUsuarios = UsersModel::TABLE;
    $tablaIntentos = LoginAttemptsModel::TABLE;
    $maxIntento = (int) $database->query("SELECT COALESCE(MAX(id), 0) FROM `{$tablaIntentos}`")->fetchColumn();
    $maxLog = (int) $database->query('SELECT COALESCE(MAX(id), 0) FROM `' . LogsMapper::TABLE . '`')->fetchColumn();

    try {
        $u = new UsersModel();
        $u->username = $prefijo;
        $u->email = "{$prefijo}@example.com";
        $u->password = password_hash($clave, \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Agente';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ADMIN_GRAL;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[] = (int) $u->id;
        $entrar = fn(): array => $pedir($ruta('users-login-request'), null, null, ['username' => $prefijo, 'password' => $clave, 'twoFactor' => '']);

        //─── a · El acceso pone la cookie ──────────────────────────────────────────────────────────
        echoTerminal('[a] El acceso pone la cookie');
        $acceso = $entrar();
        $token = is_string($acceso['json']['token'] ?? null) ? $acceso['json']['token'] : '';
        $check($acceso['status'] === 200 && ($acceso['json']['auth'] ?? null) === true && $token !== '', 'a1 CANARIO: el usuario de la prueba entra, y el cuerpo sigue llevando el token (contrato de la API)', "HTTP {$acceso['status']} " . json_encode($acceso['json']['error'] ?? null));
        $cookie = $puesta($acceso['cookies']);
        echoTerminal('      Set-Cookie: ' . ($cookie !== null ? $sinValor($cookie) : '(ninguna)'));
        $check($cookie !== null && $valor($cookie) === $token, 'a2 responde Set-Cookie con el mismo token', $cookie !== null ? $sinValor($cookie) : 'sin Set-Cookie');
        $atributos = $cookie !== null ? strtolower($cookie) : '';
        $check(str_contains($atributos, '; httponly'), 'a3 HttpOnly', $cookie !== null ? $sinValor($cookie) : '');
        $check(str_contains($atributos, '; samesite=lax'), 'a4 SameSite=Lax', $cookie !== null ? $sinValor($cookie) : '');
        $check(!str_starts_with($base, 'https://') || str_contains($atributos, '; secure'), 'a5 Secure en https', $cookie !== null ? $sinValor($cookie) : '');
        $caduca = $cookie !== null && preg_match('/;\s*expires=([^;]+)/i', $cookie, $m) === 1 ? strtotime($m[1]) : false;
        $expToken = $token !== '' ? BaseToken::getExpired($token) : null;
        $check(is_int($caduca) && is_int($expToken) && abs($caduca - $expToken) <= 5, 'a6 Expires = la caducidad del token (±5 s): la sesión sobrevive al cierre del navegador', var_export([$caduca, $expToken], true));
        //Un formulario de otro sitio no puede mandar X-Requested-With: sin ella, el acceso no pone la cookie (no puede
        //meter su sesión en este navegador), aunque el cuerpo siga llevando el token para la API.
        $sinXhr = $pedir($ruta('users-login-request'), null, null, ['username' => $prefijo, 'password' => $clave, 'twoFactor' => ''], false);
        $check(is_string($sinXhr['json']['token'] ?? null) && $puesta($sinXhr['cookies']) === null, 'a7 un acceso SIN X-Requested-With da el token en el cuerpo pero NO pone la cookie', "HTTP {$sinXhr['status']} · " . implode(' | ', array_map($sinValor, $sinXhr['cookies'])));
        $check($cookie !== null && !str_contains($atributos, '; domain='), 'a8 sin Domain: solo para el host que la pone, no para sus subdominios', $cookie !== null ? $sinValor($cookie) : '');
        //La que lleva valor va la ÚLTIMA: en un host IP el navegador guarda Domain=host como de solo host y un borrado
        //posterior se la llevaría. Y antes, los borrados de las formas con Domain.
        $ultima = end($acceso['cookies']);
        $check($ultima !== false && $ultima === $cookie && count($acceso['cookies']) >= 3, 'a11 el acceso borra antes las formas viejas y pone la buena al final', implode(' | ', array_map($sinValor, $acceso['cookies'])));
        $conNone = SessionCookie::options(time() + 60, ['path' => '/', 'domain' => 'zz.example', 'secure' => true, 'samesite' => 'None']);
        $conStrict = SessionCookie::options(time() + 60, ['path' => '/', 'domain' => 'zz.example', 'secure' => true, 'samesite' => 'Strict']);
        $check($conNone['samesite'] === 'Lax' && $conStrict['samesite'] === 'Strict' && $conNone['domain'] === '', 'a9 con samesite None en la configuración sale Lax (Strict se respeta), y el domain de la configuración no llega', json_encode([$conNone['samesite'], $conStrict['samesite'], $conNone['domain']]) ?: '');
        echoTerminal(' ');

        //─── b · Con la cookie se entra; la cabecera, si trae valor, manda ─────────────────────────
        echoTerminal('[b] La cookie y la cabecera');
        $check($entra(null, $token), 'b1 con SOLO la cookie, el panel entra');
        $check($entra('', $token), 'b2 con la cabecera VACÍA y la cookie, entra (la cabecera vacía cuenta como ausente)');
        $cerrada = UserSessions::issueToken((int) $u->id);
        $datosCerrada = BaseToken::getData($cerrada);
        $cuenta = $database->prepare('SELECT id FROM `' . UserSessions::TABLE . '` WHERE sessionHash = ?');
        $cuenta->execute([UserSessions::hash((string) ($datosCerrada->sid ?? ''))]);
        UserSessions::close((int) $cuenta->fetchColumn(), null, UserSessions::REASON_CLOSED_BY_ADMIN);
        $check(!$entra($cerrada, $token), 'b3 con una cabecera CON VALOR (la de una sesión cerrada) y la cookie buena, manda la cabecera: no entra');
        $check($entra($token, null), 'b4 la API por cabecera sigue igual: con solo la cabecera, entra');
        $verificaCabecera = $pedir($ruta('users-verify-login-request'), $token, null, []);
        $check(($verificaCabecera['json']['isAuth'] ?? null) === true && $verificaCabecera['cookies'] === [], 'b5 verify con el token en la CABECERA no toca la cookie (ni la pone ni la borra)', implode(' | ', array_map($sinValor, $verificaCabecera['cookies'])));
        echoTerminal(' ');

        //─── c · Cerrar sesión la borra y la deja sin valor ────────────────────────────────────────
        echoTerminal('[c] Cerrar sesión');
        $salida = $pedir($ruta('user-sessions-logout'), null, $token, []);
        $borrada = array_values(array_filter($salida['cookies'], fn(string $c): bool => preg_match('/^' . preg_quote($nombre, '/') . '=(?:;|deleted)/', $c) === 1 && str_contains(strtolower($c), 'httponly')));
        echoTerminal('      Set-Cookie: ' . ($borrada !== [] ? $sinValor($borrada[0]) : '(ninguna que borre la HttpOnly)'));
        $check($salida['status'] === 200 && $borrada !== [], 'c1 el logout responde Set-Cookie que borra la cookie HttpOnly', "HTTP {$salida['status']} · " . implode(' | ', array_map($sinValor, $salida['cookies'])));
        $check(!$entra(null, $token), 'c2 y con esa cookie ya no se entra');
        $host = (string) parse_url($base, \PHP_URL_HOST);
        $conDominio = array_values(array_filter($salida['cookies'], fn(string $c): bool => preg_match('/^' . preg_quote($nombre, '/') . '=(?:;|deleted)/', $c) === 1 && preg_match('/;\s*domain=\.?' . preg_quote($host, '/') . '(?:;|$)/i', $c) === 1));
        $dominios = array_map(fn(string $c): string => preg_match('/;\s*domain=(\.?[^;]+)/i', $c, $m) === 1 ? strtolower($m[1]) : '', $conDominio);
        $check($host !== '' && in_array(strtolower($host), $dominios, true) && in_array('.' . strtolower($host), $dominios, true), 'c3 y caduca también las formas con Domain=host y Domain=.host (la que dejó la 10.6 previa y las del JS viejo)', implode(' | ', array_map($sinValor, $salida['cookies'])));
        echoTerminal(' ');

        //─── d · Invalidar las sesiones del usuario ────────────────────────────────────────────────
        echoTerminal('[d] Invalidar las sesiones del usuario');
        $otra = $entrar();
        $tokenOtra = is_string($otra['json']['token'] ?? null) ? $otra['json']['token'] : '';
        $check($tokenOtra !== '' && $entra(null, $tokenOtra), 'd1 CANARIO: un acceso nuevo, y con su cookie se entra');
        (new UsersModel())->revokeSessions((int) $u->id);
        $check(!$entra(null, $tokenOtra), 'd2 cerradas sus sesiones (revokeSessions), la cookie ya no entra');
        $verificar = $pedir($ruta('users-verify-login-request'), null, $tokenOtra, []);
        $check(($verificar['json']['isAuth'] ?? null) === false && $puesta($verificar['cookies']) === null && $verificar['cookies'] !== [], 'd3 y verify, con esa cookie, dice que no y la borra', implode(' | ', array_map($sinValor, $verificar['cookies'])));

    } catch (\Throwable $e) {
        $check(false, 'x1 la suite terminó sin excepciones', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ')');
    } finally {
        try {
            if ($usuarios !== []) {
                $marcas = implode(',', array_fill(0, count($usuarios), '?'));
                $database->prepare('DELETE FROM `' . UserSessions::TABLE . "` WHERE userID IN ({$marcas})")->execute($usuarios);
                $database->prepare("DELETE FROM `{$tablaIntentos}` WHERE id > ? AND (userID IN ({$marcas}) OR usernameAttempt = ?)")->execute(array_merge([$maxIntento], $usuarios, [$prefijo]));
                $database->prepare('DELETE FROM `' . LogsMapper::TABLE . "` WHERE id > ? AND createdBy IN ({$marcas})")->execute(array_merge([$maxLog], $usuarios));
                //Crear el usuario abre su aprobación: sin esto quedaba una fila huérfana por corrida.
                $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . "` WHERE referenceTable = ? AND referenceValue IN ({$marcas})")->execute(array_merge([$tablaUsuarios], array_map('strval', $usuarios)));
                $database->prepare("DELETE FROM `{$tablaUsuarios}` WHERE id IN ({$marcas})")->execute($usuarios);
            }
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $consulta = $database->prepare("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username = ?");
        $consulta->execute([$prefijo]);
        $quedan = (int) $consulta->fetchColumn();
        $consulta = $database->prepare("SELECT COUNT(*) FROM `{$tablaIntentos}` WHERE id > ? AND usernameAttempt = ?");
        $consulta->execute([$maxIntento, $prefijo]);
        $quedan += (int) $consulta->fetchColumn();
        if ($usuarios !== []) {
            $consulta = $database->prepare('SELECT COUNT(*) FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ? AND referenceValue IN (' . implode(',', array_fill(0, count($usuarios), '?')) . ')');
            $consulta->execute(array_merge([$tablaUsuarios], array_map('strval', $usuarios)));
            $quedan += (int) $consulta->fetchColumn();
        }
        $check($quedan === 0, 'z1 no queda el usuario, ni sus intentos de acceso, ni su aprobación', (string) $quedan);
    }

    return $balance();

})->setDescription('La cookie de sesión por HTTP: el acceso la pone HttpOnly, SameSite=Lax y con la caducidad del token; con ella sola se entra; la cabecera vacía cae a ella y una con valor manda; el logout la borra; cerradas las sesiones no entra y verify la borra; ningún JS propio guarda el token. Crea y borra un usuario, sus sesiones y sus intentos de acceso.')->setEffects([CliActions::EFFECT_DATABASE])->register();
