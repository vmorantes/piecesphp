<?php

//Las pantallas del acceso, por HTTP y sin JavaScript: los enlaces de la ayuda, el formulario con el error dentro, el ojo,
//el código de seis cifras hasta el ingreso, el pie con el propietario y los botones del 404. Repone lo que toca.
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Mailer;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UserProblemsController;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\Controllers\UserSystemFeaturesController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;
use PiecesPHP\UserSystem\ORM\UserSessionsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/access-screens', function ($args) {

    echoTerminal("\e[33m[TEST:AccessScreens] Las pantallas del acceso, la ayuda y el 404\e[39m");
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

    //La guarda de la red va PRIMERO: pedir el código envía un correo por el Mailer de la instalación.
    $usado = new Mailer(false);
    $destinoLocal = in_array(gethostbyname((string) $usado->Host), ['127.0.0.1', '::1'], true);
    if (!$check(MailDelivery::goesToSink() && $destinoLocal, 'banco: el correo está retenido y el Mailer va a 127.0.0.1 (ninguna prueba sale a la red)', $usado->Host . ':' . $usado->Port)) {
        return $balance();
    }

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que mail-settings-tests.
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
        $query = (string) parse_url($url, \PHP_URL_QUERY);
        $path = $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
        return $path . ($query !== '' ? "?{$query}" : '');
    };
    $cabeceraToken = SessionToken::headerName();
    $pedir = function (string $ruta, ?string $jwt = null, ?array $post = null) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $cabeceras = $jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [];
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($ruta, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $cabeceras,
        ] + ($post !== null ? [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($post)] : []));
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $body = is_string($body) ? $body : '';
        $datos = json_decode($body, true);
        return ['status' => $status, 'body' => $body, 'data' => is_array($datos) ? $datos : []];
    };
    $dom = function (string $html): \DOMXPath {
        $documento = new \DOMDocument();
        @$documento->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        return new \DOMXPath($documento);
    };
    $texto = fn(mixed $nodo): string => $nodo instanceof \DOMNode ? trim((string) preg_replace('/\s+/u', ' ', $nodo->textContent)) : '';

    $rutaAcceso = $camino(UsersController::routeName('form-login', [], true));
    $rutaAyuda = $camino(UserProblemsController::routeName('problems-list', [], true));
    $rutaIngresar = $camino(UsersController::routeName('login-request', [], true));
    $rutaCodigo = $camino(UserSystemFeaturesController::routeName('generate-otp', [], true));
    $ruta404 = 'zz-prueba-no-existe-' . bin2hex(random_bytes(3)) . '/';

    $tabla = (new SettingsModel())->getModel()->getTable();
    $crudo = function (string $opcion) use ($database, $tabla): ?string {
        $consulta = $database->prepare("SELECT value FROM `{$tabla}` WHERE BINARY name = ?");
        $consulta->execute([$opcion]);
        $valor = $consulta->fetchColumn();
        return $valor === false ? null : (string) $valor;
    };
    $poner = function (string $opcion, ?string $valor) use ($database, $tabla, $crudo): void {
        if ($valor === null) {
            $database->prepare("DELETE FROM `{$tabla}` WHERE BINARY name = ?")->execute([$opcion]);
        } elseif ($crudo($opcion) === null) {
            $database->prepare("INSERT INTO `{$tabla}` (name, value) VALUES (?, ?)")->execute([$opcion, $valor]);
        } else {
            $database->prepare("UPDATE `{$tabla}` SET value = ? WHERE BINARY name = ?")->execute([$valor, $opcion]);
        }
    };
    $propietarioAntes = $crudo('owner');
    $invertirAntes = $crudo(\PiecesPHP\Settings\Controllers\SettingsController::BRAND_IMAGES_INVERT_ON_DARK['logo']);
    $opcionModo = \PiecesPHP\Settings\Controllers\SettingsController::BRAND_IMAGES_DARK_MODE['logo']['mode'];
    $opcionOscura = \PiecesPHP\Settings\Controllers\SettingsController::BRAND_IMAGES_DARK_MODE['logo']['image'];
    $modoAntes = $crudo($opcionModo);

    $prefijo = 'zz-prueba-acceso-' . bin2hex(random_bytes(3));
    $correo = "{$prefijo}@example.invalid";
    $clave = 'Zz-' . bin2hex(random_bytes(8));
    $ids = [];

    try {
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = $correo;
        $u->password = password_hash($clave, \PASSWORD_DEFAULT);
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
        $ids[] = (int) $u->id;

        //─── a · La ayuda para entrar: cada botón lleva a donde dice ───────────────────────────────────
        echoTerminal('[a] La ayuda para entrar: «Solicitar soporte» al formulario y «Iniciar sesión» al acceso');
        $ayuda = $pedir($rutaAyuda)['body'];
        $constante = fn(string $nombre): string => preg_match('/const ' . $nombre . ' = "([^"]*)"/', $ayuda, $m) === 1 ? $m[1] : '(no está)';
        $check($camino($constante('OTHER_PROBLEMS_FORM')) === $camino(UserProblemsController::routeName('other-problems-form', [], true)), 'a1 DISCRIMINANTE: OTHER_PROBLEMS_FORM (Solicitar soporte) es el formulario de otros problemas', $constante('OTHER_PROBLEMS_FORM'));
        $check($camino($constante('USER_FORM_LOGIN')) === $rutaAcceso, 'a2 DISCRIMINANTE: USER_FORM_LOGIN (Iniciar sesión) es el acceso', $constante('USER_FORM_LOGIN'));
        foreach (['es', 'en'] as $idioma) {
            $plantilla = (string) file_get_contents(basepath("app/lang/files/usersProblems/PROBLEMS_LIST-{$idioma}.html"));
            $check(str_contains($plantilla, 'const OTHER_PROBLEMS_FORM = "${otherProblemsFormLink}"') && str_contains($plantilla, 'const USER_FORM_LOGIN = "${loginLink}"'), "a3 la plantilla en {$idioma}, con las dos constantes en su sitio");
        }
        echoTerminal(' ');

        //─── b · El formulario de acceso ────────────────────────────────────────────────────────────────
        echoTerminal('[b] El formulario: el error dentro, «Usuario o correo», el ojo y los dos métodos');
        $acceso = $pedir($rutaAcceso);
        $x = $dom($acceso['body']);
        $formulario = $x->query('//form[@login-form-js]')?->item(0);
        $check($acceso['status'] === 200 && $formulario instanceof \DOMElement, 'b0 CANARIO: el acceso responde y trae su formulario', "HTTP {$acceso['status']}");
        $error = $x->query('//form[@login-form-js]//*[@data-login-error]')?->item(0);
        $check($error instanceof \DOMElement && $error->getAttribute('role') === 'alert' && str_contains($error->getAttribute('class'), 'error message'), 'b1 DISCRIMINANTE: el error es un mensaje del tema DENTRO del formulario, con role=alert');
        $check($x->query('//*[@show-error]')?->length === 0 && $x->query('//*[@try-again]')?->length === 0, 'b2 y ya no hay un bloque aparte con «Intentar nuevamente»');
        $js = (string) file_get_contents(basepath('app/classes/PiecesPHP/UserSystem/Statics/js/login.js'));
        $check(!str_contains($js, 'defaultView.hide') && !str_contains($js, "\$('[defauld-show]')"), 'b3 el JavaScript no oculta el formulario al fallar');
        $etiqueta = $x->query('//label[@for="login-username"]')?->item(0);
        $check($texto($etiqueta) === __(USER_LOGIN_LANG_GROUP, 'Usuario o correo'), 'b4 la etiqueta dice «Usuario o correo»', $texto($etiqueta));
        $ojo = $x->query('//*[@data-login-eye]')?->item(0);
        $check($ojo instanceof \DOMElement && $ojo->nodeName === 'button' && $ojo->getAttribute('type') === 'button' && $ojo->getAttribute('aria-pressed') === 'false' && $ojo->getAttribute('aria-controls') === 'login-password' && $ojo->getAttribute('aria-label') === __(USER_LOGIN_LANG_GROUP, 'Mostrar contraseña'), 'b5 DISCRIMINANTE: el ojo es un <button type="button"> con aria-pressed, aria-controls y aria-label', $ojo instanceof \DOMElement ? $ojo->nodeName : 'no está');
        $pestanas = $x->query('//*[@role="tablist"]//*[@role="tab"]');
        $check($pestanas?->length === 2 && $x->query('//*[@role="tabpanel"]')?->length === 2, 'b6 el selector Contraseña / Código de acceso: dos pestañas y dos paneles');
        $check($x->query('//*[@twofa-field]')?->length === 1 && $x->query('//*[contains(@class,"remember-me")]')?->length === 1, 'b7 se mantienen el segundo factor y «Recordarme» (P256)');
        echoTerminal(' ');

        //─── c · El código de acceso, hasta el ingreso ──────────────────────────────────────────────────
        echoTerminal('[c] El código: seis casillas, la caducidad real, el envío uniforme y el ingreso con las seis cifras');
        $casillas = $x->query('//*[@data-login-code-boxes]//input[@maxlength="1"][@inputmode="numeric"]');
        $check($casillas?->length === 6, 'c1 DISCRIMINANTE: seis casillas de una cifra', (string) $casillas?->length);
        $minutos = OTPRateLimiter::config()['oneUseCodeMinutes'];
        $check($formulario instanceof \DOMElement && $formulario->getAttribute('data-code-minutes') === (string) $minutos && str_contains($formulario->getAttribute('data-text-expires'), (string) $minutos), "c2 la caducidad es la configurada ({$minutos} minutos), no un número fijo");
        $check($formulario instanceof \DOMElement && $camino($formulario->getAttribute('data-otp-url')) === $rutaCodigo, 'c3 «Enviar código» llama a la ruta que ya existe (generate-otp)');
        $inexistente = $pedir($rutaCodigo . '?username=' . rawurlencode("{$prefijo}-no-existe@example.invalid"));
        $existente = $pedir($rutaCodigo . '?username=' . rawurlencode($correo));
        $check($existente['status'] === 200 && ($existente['data']['success'] ?? null) === true, 'c4 pedir el código con el CORREO responde con éxito', "HTTP {$existente['status']}, " . mb_substr((string) ($existente['data']['message'] ?? ''), 0, 120));
        if (OTPRateLimiter::config()['uniformResponse']) {
            $check(($inexistente['data']['success'] ?? null) === ($existente['data']['success'] ?? null) && ($inexistente['data']['message'] ?? '') === ($existente['data']['message'] ?? ''), 'c5 y la respuesta es la misma con un usuario que no existe (P201)');
        }
        //Con la respuesta uniforme el código se genera DESPUÉS de responder (RespondFirst): se espera, con tope.
        $limite = microtime(true) + 10;
        do {
            $otp = OTPSecretsUsersMapper::getOTPData((int) $u->id, OTPSecretsUsersMapper::METHOD_ONE_USE_CODE);
            $codigo = $otp !== null ? (string) $otp->oneUseCode : '';
            if (preg_match('/^[0-9]{6}$/', $codigo) === 1) {
                break;
            }
            usleep(200000);
        } while (microtime(true) < $limite);
        $check(preg_match('/^[0-9]{6}$/', $codigo) === 1, 'c6 el código guardado son seis cifras');
        $mal = $pedir($rutaIngresar, null, ['username' => $correo, 'password' => '000000' === $codigo ? '111111' : '000000', 'twoFactor' => '']);
        $check(($mal['data']['auth'] ?? null) === false && ($mal['data']['error'] ?? '') === UsersController::INCORRECT_PASSWORD, 'c7 un código equivocado no entra (INCORRECT_PASSWORD, el error del código en la pantalla)', (string) ($mal['data']['error'] ?? ''));
        $bien = $pedir($rutaIngresar, null, ['username' => $correo, 'password' => $codigo, 'twoFactor' => '']);
        $check(($bien['data']['auth'] ?? null) === true, 'c8 CANARIO: el correo y las seis cifras, enviadas como hoy en el campo de la contraseña, entran', "HTTP {$bien['status']}, " . (string) ($bien['data']['error'] ?? ''));
        echoTerminal(' ');

        //─── d · El acceso con contraseña acepta el correo (B2) ─────────────────────────────────────────
        echoTerminal('[d] El acceso con contraseña acepta el correo, no solo el usuario');
        $conCorreo = $pedir($rutaIngresar, null, ['username' => $correo, 'password' => $clave, 'twoFactor' => '']);
        $check(($conCorreo['data']['auth'] ?? null) === true, 'd1 el correo y la contraseña entran', (string) ($conCorreo['data']['error'] ?? ''));
        $conUsuario = $pedir($rutaIngresar, null, ['username' => "{$prefijo}-root", 'password' => $clave, 'twoFactor' => '']);
        $check(($conUsuario['data']['auth'] ?? null) === true, 'd2 y el usuario y la contraseña, también');
        echoTerminal(' ');

        //─── p · El pie con el propietario ──────────────────────────────────────────────────────────────
        echoTerminal('[p] El pie: «Todos los derechos reservados a <propietario>», o el nombre de la aplicación si está vacío');
        $marca = "{$prefijo}-propietario <b>";
        $poner('owner', $marca);
        $esperado = escape_html(strReplaceTemplate(__(USER_LOGIN_LANG_GROUP, '© ${year} · Todos los derechos reservados a ${owner}'), ['${year}' => date('Y'), '${owner}' => $marca]));
        $pie = fn(string $html, string $atributo): string => preg_match('/<small[^>]*' . $atributo . '[^>]*>(.*?)<\/small>/s', $html, $m) === 1 ? trim($m[1]) : '(no está)';
        $check($pie($pedir($rutaAcceso)['body'], 'data-access-footer') === $esperado, 'p1 DISCRIMINANTE: el acceso, con el propietario escapado', $pie($pedir($rutaAcceso)['body'], 'data-access-footer'));
        $check($pie($pedir($rutaAyuda)['body'], 'data-access-footer') === $esperado, 'p2 la ayuda para entrar, igual');
        $check($pie($pedir($ruta404)['body'], 'data-error-footer') === $esperado, 'p3 y la página de error');
        $poner('owner', '');
        $deRespaldo = escape_html(strReplaceTemplate(__(USER_LOGIN_LANG_GROUP, '© ${year} · Todos los derechos reservados a ${owner}'), ['${year}' => date('Y'), '${owner}' => (string) get_config('title_app')]));
        $check($pie($pedir($rutaAcceso)['body'], 'data-access-footer') === $deRespaldo, 'p4 vacío, el nombre de la aplicación', $pie($pedir($rutaAcceso)['body'], 'data-access-footer'));
        $partners = trim((string) get_config('partners'));
        $html404 = $pedir($ruta404)['body'];
        $check(!str_contains($html404, 'data-error-developer') && ($partners === '' || $dom($html404)->query('//*[@data-error-partners]//img[contains(@src,"' . \PiecesPHP\Settings\Controllers\SettingsController::brandImagePath('partners') . '")]')?->length >= 1), 'p5 DISCRIMINANTE: la página de error no lleva el crédito del desarrollador, y con imagen de patrocinadores la lleva en el pie');
        $check($partners === '' || $dom($pedir($rutaAcceso)['body'])->query('//*[@data-access-mobile-partners][contains(@class,"mobile tablet only")]//img[not(contains(@class,"brand-image-dark"))]')?->length === 1, 'p6 el patrocinador, bajo el formulario en móvil y tableta');
        echoTerminal(' ');

        //─── e · El 404, con y sin sesión ───────────────────────────────────────────────────────────────
        echoTerminal('[e] El 404: sin sesión, «Iniciar sesión» primero (con el regreso); con sesión, «Volver al inicio»');
        $botones = function (string $html) use ($dom, $texto): array {
            $salida = [];
            foreach ($dom($html)->query('//main//a[contains(@class,"button")]') ?: [] as $boton) {
                if ($boton instanceof \DOMElement) {
                    $salida[] = ['texto' => $texto($boton), 'href' => $boton->getAttribute('href'), 'principal' => str_contains($boton->getAttribute('class'), 'primary')];
                }
            }
            return $salida;
        };
        $sinSesion = $pedir($ruta404);
        $b = $botones($sinSesion['body']);
        $check($sinSesion['status'] === 404 && ($b[0]['texto'] ?? '') === __('page404', 'Iniciar sesión') && ($b[0]['principal'] ?? false) === true && str_starts_with($camino($b[0]['href'] ?? ''), $rutaAcceso), 'e1 DISCRIMINANTE: sin sesión, el principal es «Iniciar sesión», al acceso', json_encode($b) ?: '');
        $check(str_contains($b[0]['href'] ?? '', 'next=') && str_contains(rawurldecode($b[0]['href'] ?? ''), $ruta404), 'e2 con el regreso a la página pedida (next)', $b[0]['href'] ?? '');
        $check(($b[1]['texto'] ?? '') === __('page404', 'Volver al inicio') && ($b[2]['texto'] ?? '') === __('page404', 'Reportar problema'), 'e3 después «Volver al inicio» y «Reportar problema»');
        $check(str_contains($sinSesion['body'], escape_html(__('page404', 'La dirección que buscas no existe o requiere que inicies sesión. Verifica el enlace o ingresa con tu usuario para continuar.'))), 'e4 y el texto del prototipo');
        $jwt = UserSessions::issueToken((int) $u->id);
        $conSesion = $pedir($ruta404, $jwt);
        $b = $botones($conSesion['body']);
        $check(($b[0]['texto'] ?? '') === __('page404', 'Volver al inicio') && ($b[0]['principal'] ?? false) === true && !in_array(__('page404', 'Iniciar sesión'), array_column($b, 'texto'), true), 'e5 DISCRIMINANTE: con sesión, «Volver al inicio» es el principal y no se ofrece entrar', json_encode($b) ?: '');

        //─── m · Luna/sol en las pantallas sin sesión ────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[m] Luna/sol: sin sesión, el script en el <head> y el botón; con sesión, ninguno (H411)');
        $lunaSol = function (string $html) use ($dom): array {
            $xpath = $dom($html);
            $boton = $xpath->query('//body//button[@data-theme-toggle]')?->item(0);
            return [
                'script' => $xpath->query('//head/script[not(@src)][contains(., "pcsphp-access-theme")]')->length,
                'botones' => $xpath->query('//button[@data-theme-toggle]')->length,
                'etiquetas' => $boton instanceof \DOMElement ? [$boton->getAttribute('data-label-dark'), $boton->getAttribute('data-label-light'), $boton->hasAttribute('hidden')] : null,
            ];
        };
        $pantallasLunaSol = [
            'acceso' => $rutaAcceso,
            'ayuda' => $camino(UserProblemsController::routeName('problems-list', [], true)),
            'olvido' => $camino(UsersController::routeName('forget-form', [], true)),
            'bloqueo' => $camino(UsersController::routeName('blocked-form', [], true)),
            'otros' => $camino(UserProblemsController::routeName('other-problems-form', [], true)),
            'clave' => $camino(\PiecesPHP\UserSystem\Controllers\RecoveryPasswordController::routeName('recovery-form', [], true)),
            '404' => $ruta404,
        ];
        $esperadoLunaSol = ['script' => 1, 'botones' => 1, 'etiquetas' => [__(USER_LOGIN_LANG_GROUP, 'Cambiar a modo oscuro'), __(USER_LOGIN_LANG_GROUP, 'Cambiar a modo claro'), true]];
        $faltan = array_keys(array_filter(array_map(fn (string $ruta): bool => $lunaSol($pedir($ruta)['body']) !== $esperadoLunaSol, $pantallasLunaSol)));
        $check($faltan === [], 'm1 DISCRIMINANTE: sin sesión, en las siete pantallas, un script en el <head> y un botón (oculto hasta el script) con sus dos textos', implode(', ', $faltan));
        $conSesionLunaSol = $lunaSol($pedir($ruta404, $jwt)['body']);
        $check($conSesionLunaSol['script'] === 0 && $conSesionLunaSol['botones'] === 0, 'm2 DISCRIMINANTE: con sesión, el 404 no lleva ni el script ni el botón: manda la preferencia de la persona', json_encode($conSesionLunaSol) ?: '');
        $vista = (string) file_get_contents(app_basepath('view/panel/built-in/content/theme-toggle.php'));
        $incluyen = array_filter([
            'UserSystem/Views/usuarios/login.php', 'UserSystem/Views/usuarios/problems/problems-list.php', 'UserSystem/Views/usuarios/problems/user_forget.php',
            'UserSystem/Views/usuarios/problems/user_blocked.php', 'UserSystem/Views/usuarios/problems/other-problems.php', 'UserSystem/Views/usuarios/problems/password.php',
            'Tokens/Views/layout/header-for-token.php',
        ], fn (string $archivo): bool => !str_contains((string) file_get_contents(app_basepath('classes/PiecesPHP/' . $archivo)), "\$themeTogglePart = 'head'; include app_basepath('view/panel/built-in/content/theme-toggle.php');"));
        $check($incluyen === [] && str_contains((string) file_get_contents(app_basepath('classes/PiecesPHP/Tokens/Views/layout/header-for-token.php')), "\$themeTogglePart = 'button';") && str_contains($vista, "add_global_asset('statics/core/js/theme-toggle.js', 'js');"), 'm3 una sola pieza: las cabeceras la incluyen (también la de los tokens), y el mismo JS va además como recurso para una CSP sin lo en línea', implode(', ', $incluyen));
        $js = (string) file_get_contents(basepath('statics/core/js/theme-toggle.js'));
        $check(str_contains($js, "const storageKey = 'pcsphp-access-theme'") && substr_count($js, 'try {') === 2 && !str_contains($js, 'jQuery(') && !str_contains($js, '$(') && !str_contains($js, '.dropdown('), 'm4 recordado en el navegador con try/catch, sin jQuery ni Fomantic');

        //─── t · La ayuda para entrar, traducida ─────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[t] Los títulos y los pasos de la ayuda para entrar pasan por la traducción');
        $ayuda = [
            'olvido' => $camino(UsersController::routeName('forget-form', [], true)),
            'clave' => $camino(\PiecesPHP\UserSystem\Controllers\RecoveryPasswordController::routeName('recovery-form', [], true)),
            'bloqueo' => $camino(UsersController::routeName('blocked-form', [], true)),
        ];
        $enIngles = fn (string $ruta): string => $pedir('en/' . ltrim($ruta, '/'))['body'];
        $titulos = fn (string $html): array => array_values(array_unique(array_map($texto, iterator_to_array($dom($html)->query('//div[contains(@class,"topbar")]//h1') ?: []))));
        $pasos = fn (string $html): array => array_map($texto, iterator_to_array($dom($html)->query('//*[contains(@class,"number-step")]') ?: []));
        $olvido = $enIngles($ayuda['olvido']);
        $check($titulos($olvido) === ['I do not remember my user'] && $pasos($olvido) === ['STEP 1', 'STEP 2'] && str_contains($olvido, 'placeholder="Enter the code"'), 't1 DISCRIMINANTE: «No recuerdo mi usuario» en inglés, con sus pasos y el placeholder', json_encode([$titulos($olvido), $pasos($olvido)]) ?: '');
        $clave = $enIngles($ayuda['clave']);
        $check($titulos($clave) === ['I do not remember my password'] && $pasos($clave) === ['STEP 1', 'STEP 2'], 't2 DISCRIMINANTE: «No recuerdo mi contraseña» en inglés', json_encode([$titulos($clave), $pasos($clave)]) ?: '');
        $bloqueo = $enIngles($ayuda['bloqueo']);
        $check($titulos($bloqueo) === ['User is locked', 'User Unlocked'] && $pasos($bloqueo) === ['STEP 1', 'STEP 2'], 't3 DISCRIMINANTE: «Usuario bloqueado» y «Usuario desbloqueado» en inglés', json_encode([$titulos($bloqueo), $pasos($bloqueo)]) ?: '');
        $check(str_contains($clave, 'placeholder="Enter the code"') && str_contains($bloqueo, 'placeholder="Enter the code"'), 't5 DISCRIMINANTE: el placeholder del código, en inglés en «No recuerdo mi contraseña» y en «Usuario bloqueado» (H226)');
        $espanol = $pedir($ayuda['olvido'])['body'];
        $check($pasos($espanol) === ['PASO 1', 'PASO 2'] && str_contains($espanol, 'placeholder="Ingrese el código"') && !str_contains($espanol, 'codigó'), 't4 en español, «PASO» y el placeholder sin la errata');

        //─── l · El logo de fábrica, en blanco en oscuro también fuera del panel ─────────────────────
        echoTerminal(' ');
        echoTerminal('[l] El logo, con su modo oscuro, en el acceso, la ayuda (${logo}) y el 404, como en el panel (H340)');
        $logos = function (string $html) use ($dom): array {
            $x = $dom($html);
            $salida = [];
            foreach (['//section[contains(@class,"access-brand")]//div[contains(@class,"raised segment")]/img', '//div[contains(@class,"mobile tablet only row")]//img[contains(@class,"mini")]', '//div[@class="logo"]/img', '//header//a[contains(@class,"header")]/img'] as $consulta) {
                foreach ($x->query($consulta) ?: [] as $img) {
                    if ($img instanceof \DOMElement) {
                        $salida[] = str_contains(' ' . $img->getAttribute('class') . ' ', ' invert-on-dark ');
                    }
                }
            }
            return $salida;
        };
        $opcionLogo = \PiecesPHP\Settings\Controllers\SettingsController::BRAND_IMAGES_INVERT_ON_DARK['logo'];
        $deFabrica = \PiecesPHP\Settings\Controllers\SettingsController::isFactoryBrandImage('logo');
        //El modo guardado manda sobre la opción vieja, que se quita para que no decida nada.
        $poner($opcionLogo, null);
        $poner($opcionModo, 'invert');
        $paginas = ['acceso' => [$rutaAcceso, 2], 'ayuda' => [$rutaAyuda, 3], '404' => [$ruta404, 1]];
        foreach ($paginas as $nombre => [$ruta, $minimo]) {
            $cuerpo = $pedir($ruta)['body'];
            $marcas = $logos($cuerpo);
            $check(count($marcas) >= $minimo && !in_array(false, $marcas, true) && !str_contains($cuerpo, '${logo'), "l1 DISCRIMINANTE: {$nombre}, sus " . count($marcas) . ' logos llevan invert-on-dark con «Invertir en blanco»', json_encode($marcas) ?: '');
        }
        $check(str_contains($pedir($ruta404)['body'], 'statics/core/css/ui-pcs.css'), 'l2 el 404 carga ui-pcs.css, donde vive la regla');
        $poner($opcionModo, 'same');
        foreach ($paginas as $nombre => [$ruta, $minimo]) {
            $marcas = $logos($pedir($ruta)['body']);
            $check(count($marcas) >= $minimo && !in_array(true, $marcas, true), "l3 DISCRIMINANTE: con «Igual», {$nombre} no invierte ninguno", json_encode($marcas) ?: '');
        }
        //De fábrica y sin modo guardado: «Otra imagen», la clara y la oscura de fábrica, cada una con su clase.
        if ($deFabrica) {
            $poner($opcionModo, null);
            foreach ($paginas as $nombre => [$ruta, $minimo]) {
                $x = $dom($pedir($ruta)['body']);
                //Solo el logo: los patrocinadores y el logo del desarrollador traen también su par (P377).
                $claras = $x->query('//img[contains(@class,"brand-image-light")][contains(@src,"' . \PiecesPHP\Settings\Controllers\SettingsController::brandImagePath('logo') . '")]')->length;
                $oscuras = $x->query('//img[contains(@class,"brand-image-dark")][contains(@src,"statics/images/white-logo.png")]')->length;
                $check($claras >= $minimo && $claras === $oscuras && ($x->query('//img[contains(@class,"brand-image-")][contains(@class,"invert-on-dark")]')->length) === 0, "l4 DISCRIMINANTE: de fábrica y sin modo, {$nombre} lleva {$claras} pares clara y oscura, sin invertir", "{$claras} · {$oscuras}");
            }
        }

        //─── k · El logo del desarrollador (config.php, no el panel) ──────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[k] El logo del desarrollador: de fábrica y sin la clave, su clara y su oscura; la regla de una imagen, invert-on-dark solo con el de fábrica');
        $logoDesarrollador = trim((string) get_config('developer_logo'));
        $claveDesarrollador = get_config('developer_logo_invert_on_dark');
        $deFabricaDesarrollador = $logoDesarrollador !== '' && \PiecesPHP\Settings\Controllers\SettingsController::isFactoryDeveloperLogo() && $claveDesarrollador === false && trim((string) get_config('developer')) !== '';
        if ($deFabricaDesarrollador) {
            //config.php no se cambia por HTTP (no es opción declarada): por HTTP, el estado de fábrica; los demás, en el proceso.
            $paginasDesarrollador = [
                'acceso' => [$rutaAcceso, null, '//small[contains(@class,"developer-credit")]/img'],
                'menú lateral' => ['admin/', $jwt, '//img[contains(@class,"developer-logo")]'],
            ];
            foreach ($paginasDesarrollador as $nombre => [$ruta, $token, $consulta]) {
                $imagenes = [];
                foreach ($dom($pedir($ruta, $token)['body'])->query($consulta) ?: [] as $img) {
                    if ($img instanceof \DOMElement) {
                        $clases = ' ' . $img->getAttribute('class') . ' ';
                        $src = (string) preg_replace('/\?.*$/', '', $img->getAttribute('src'));
                        $imagenes[] = (str_contains($clases, ' invert-on-dark ') ? 'invertida' : (str_contains($clases, ' brand-image-light ') ? 'clara' : (str_contains($clases, ' brand-image-dark ') ? 'oscura' : 'sola'))) . ':' . $src;
                    }
                }
                $clara = 'clara:' . \PiecesPHP\Settings\Controllers\SettingsController::FACTORY_DEVELOPER_LOGO;
                $oscura = 'oscura:' . \PiecesPHP\Settings\Controllers\SettingsController::FACTORY_DEVELOPER_LOGO_DARK;
                $pares = count(array_keys($imagenes, $clara, true));
                $check($pares >= 1 && $pares === count(array_keys($imagenes, $oscura, true)) && count($imagenes) === 2 * $pares, "k1 DISCRIMINANTE: {$nombre}, el logo del desarrollador va en par, la clara y developer-logo-dark.png, sin invertir", json_encode($imagenes) ?: '');
            }
        } else {
            echoTerminal('   k1 no corre: config.php no tiene el logo de fábrica sin la clave, o no hay desarrollador (lo cambió el clon)');
        }
        //─── v · Las ventanas de los problemas de acceso siguen el modo de color ─────────────────────
        echoTerminal(' ');
        echoTerminal('[v] Las ventanas de los problemas de acceso, sin fondo blanco fijo (sobre el SCSS: el CSS compilado no se versiona)');
        $hojaProblemas = (string) preg_replace('~/\*.*?\*/|//[^\n]*~s', '', (string) file_get_contents(basepath('app/classes/PiecesPHP/UserSystem/Statics/sass/problems.scss')));
        $bloque = function (string $selector) use ($hojaProblemas): string {
            $inicio = strpos($hojaProblemas, $selector . ' {');
            if ($inicio === false) {
                return '';
            }
            $nivel = 0;
            $caracteres = str_split(substr($hojaProblemas, $inicio));
            foreach ($caracteres as $posicion => $caracter) {
                $nivel += $caracter === '{' ? 1 : ($caracter === '}' ? -1 : 0);
                if ($caracter === '}' && $nivel === 0) {
                    return substr($hojaProblemas, $inicio, $posicion + 1);
                }
            }
            return '';
        };
        $blanco = '~background(-color)?\s*:\s*(white|#fff(fff)?)\b~i';
        foreach (['.ui.modal.user-and-pass', '.card-ui-center'] as $selector) {
            $cuerpo = $bloque($selector);
            $check($cuerpo !== '' && preg_match($blanco, $cuerpo) !== 1, "v1 DISCRIMINANTE: {$selector} no fija un fondo blanco", $cuerpo === '' ? 'no encuentro el bloque' : '');
        }
        $check(str_contains($bloque('.card-ui-center'), 'background-color: var(--theme-surface)'), 'v2 .card-ui-center toma el fondo del tema (--theme-surface)');
        $botones = $bloque('> .buttons-action-area');
        $check($botones !== '' && preg_match('~^\s*color\s*:\s*(gray|grey|#[0-9a-f]{3,6})\s*;~mi', $botones) !== 1 && str_contains($botones, 'color: var(--theme-text);'), 'v3 DISCRIMINANTE: el texto de los botones de «Recuperar mi contraseña» es el del tema, no un gris fijo (2,81:1 en oscuro)', $botones === '' ? 'no encuentro el bloque' : '');

        try {
            set_config('developer_logo', \PiecesPHP\Settings\Controllers\SettingsController::FACTORY_DEVELOPER_LOGO);
            set_config('developer_logo_invert_on_dark', null);
            $check(\PiecesPHP\Settings\Controllers\SettingsController::developerLogoInvertsOnDark(), 'k2 el de fábrica sin la clave, se invierte');
            set_config('developer_logo_invert_on_dark', false);
            $check(\PiecesPHP\Settings\Controllers\SettingsController::developerLogoInvertsOnDark(), 'k3 false cuenta como sin poner: el de fábrica se sigue invirtiendo');
            set_config('developer_logo_invert_on_dark', 0);
            $check(!\PiecesPHP\Settings\Controllers\SettingsController::developerLogoInvertsOnDark(), 'k4 DISCRIMINANTE: con la clave a 0, no');
            set_config('developer_logo', 'statics/images/zz-prueba-otro-logo.png');
            set_config('developer_logo_invert_on_dark', null);
            $check(!\PiecesPHP\Settings\Controllers\SettingsController::developerLogoInvertsOnDark(), 'k5 DISCRIMINANTE: un logo ajeno sin la clave, no');
            set_config('developer_logo_invert_on_dark', '1');
            $check(\PiecesPHP\Settings\Controllers\SettingsController::developerLogoInvertsOnDark(), 'k6 un logo ajeno con la clave a 1, sí');
        } finally {
            set_config('developer_logo', $logoDesarrollador);
            set_config('developer_logo_invert_on_dark', $claveDesarrollador === false ? null : $claveDesarrollador);
        }
        echoTerminal(' ');

        //─── f · El logo de fábrica, el de hoy y el anterior, por su sha1 (H336) ─────────────────────
        echoTerminal('[f] El logo de fábrica se reconoce por su sha1, el de hoy y el anterior (v10.41.0)');
        $logoAntes = get_config('logo');
        $opcionAntes = get_config($opcionLogo);
        $modoGetAntes = get_config($opcionModo);
        $oscuraGetAntes = get_config($opcionOscura);
        $viejo = 'app/cache/zz-logo-de-fabrica-viejo-' . bin2hex(random_bytes(3)) . '.png';
        try {
            //Las opciones sin guardar (get_config() da false): decide si el logo es el de fábrica.
            set_config($opcionLogo, false);
            set_config($opcionModo, false);
            set_config($opcionOscura, false);
            set_config('logo', 'statics/images/logo.png');
            $esImagen = fn (): bool => \PiecesPHP\Settings\Controllers\SettingsController::brandImageDarkMode('logo') === 'image' && \PiecesPHP\Settings\Controllers\SettingsController::brandImageDarkImage('logo') === 'statics/images/white-logo.png' && !\PiecesPHP\Settings\Controllers\SettingsController::brandImageInvertsOnDark('logo');
            $check($esImagen(), 'f1 DISCRIMINANTE: el logo de fábrica de hoy, sin modo guardado, enseña en oscuro el logo blanco de fábrica (y no se invierte)', (string) sha1_file(basepath('statics/images/logo.png')));
            set_config($opcionLogo, 1);
            $check($esImagen(), 'f5 DISCRIMINANTE: y también con la opción vieja a 1: la fábrica gana a la opción vieja');
            set_config($opcionLogo, false);
            $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
            $git = proc_open(['git', '--no-optional-locks', '-C', $proyecto, 'show', 'v10.41.0:src/statics/images/logo.png'], [1 => ['file', basepath($viejo), 'w'], 2 => ['file', '/dev/null', 'w']], $tuberias);
            $sacado = is_resource($git) && proc_close($git) === 0 && sha1_file(basepath($viejo)) === '9cf27f1fbb82e550d03fbaca632f9c6563eec81a';
            set_config('logo', $viejo);
            $check($sacado && $esImagen(), 'f2 el logo de fábrica anterior (el de v10.41.0) también', $sacado ? '' : 'no se pudo sacar de git');
            set_config('logo', 'statics/images/framework/main_logo.svg');
            $check(\PiecesPHP\Settings\Controllers\SettingsController::brandImageDarkMode('logo') === 'same', 'f3 otra imagen, sin opciones, «Igual»');
            set_config($opcionLogo, 1);
            $check(\PiecesPHP\Settings\Controllers\SettingsController::brandImageInvertsOnDark('logo'), 'f6 DISCRIMINANTE: otra imagen con la opción vieja a 1 y sin modo, «Invertir en blanco»');
            set_config($opcionModo, 'image');
            $una = \PiecesPHP\Settings\Controllers\SettingsController::brandImageTag('logo', 'zz');
            $check(substr_count($una, '<img') === 1 && !str_contains($una, 'brand-image-') && !str_contains($una, 'invert-on-dark'), 'f7 DISCRIMINANTE: «Otra imagen» sin oscura, una sola imagen, como «Igual» (nunca una rota)', $una);
            set_config($opcionOscura, 'statics/images/zz-no-existe.png');
            $check(substr_count(\PiecesPHP\Settings\Controllers\SettingsController::brandImageTag('logo', 'zz'), '<img') === 1, 'f8 y con una oscura que no existe en disco, también');
            set_config($opcionOscura, 'statics/images/white-logo.png');
            $par = \PiecesPHP\Settings\Controllers\SettingsController::brandImageTag('logo', 'zz', 'Alt "x"');
            $check(substr_count($par, '<img') === 2 && str_contains($par, 'class="zz brand-image-light"') && str_contains($par, 'class="zz brand-image-dark"') && substr_count($par, 'alt="Alt &quot;x&quot;"') === 2, 'f9 DISCRIMINANTE: con oscura que existe, las dos, con sus clases y el alt escapado', $par);
            //─ r · El marcador viejo `${logoClass}` y el logo de fábrica sin su oscura (revisión de A.1) ─
            set_config($opcionModo, false);
            set_config($opcionOscura, false);
            set_config('logo', 'statics/images/logo.png');
            $check(\PiecesPHP\Settings\Controllers\SettingsController::brandImageLooksInvertedOnDark('logo') && !\PiecesPHP\Settings\Controllers\SettingsController::brandImageInvertsOnDark('logo'), 'r1 DISCRIMINANTE: de fábrica y sin modo (oscura de fábrica), `${logoClass}` invierte, y brandImageInvertsOnDark() sigue en false');
            set_config($opcionOscura, 'statics/images/zz-no-existe.png');
            $sinOscura = \PiecesPHP\Settings\Controllers\SettingsController::brandImageTag('logo', 'zz');
            $check(\PiecesPHP\Settings\Controllers\SettingsController::brandImageEffectiveDarkMode('logo') === 'invert' && \PiecesPHP\Settings\Controllers\SettingsController::brandImageInvertsOnDark('logo') && substr_count($sinOscura, '<img') === 1 && str_contains($sinOscura, 'class="zz invert-on-dark"'), 'r2 DISCRIMINANTE: de fábrica en «Otra imagen» sin oscura en disco, se pinta invertido (como antes), no igual', $sinOscura);
            $check(\PiecesPHP\Settings\Controllers\SettingsController::brandImageLooksInvertedOnDark('logo'), 'r3 y `${logoClass}` también invierte');
            set_config($opcionOscura, 'statics/images/partners.png');
            $check(!\PiecesPHP\Settings\Controllers\SettingsController::brandImageLooksInvertedOnDark('logo') && \PiecesPHP\Settings\Controllers\SettingsController::brandImageEffectiveDarkMode('logo') === 'image', 'r4 DISCRIMINANTE: con una oscura propia que existe, `${logoClass}` no invierte');
            set_config('logo', 'statics/images/framework/main_logo.svg');
            set_config($opcionOscura, 'statics/images/zz-no-existe.png');
            set_config($opcionModo, 'image');
            $check(\PiecesPHP\Settings\Controllers\SettingsController::brandImageEffectiveDarkMode('logo') === 'same' && !\PiecesPHP\Settings\Controllers\SettingsController::brandImageLooksInvertedOnDark('logo'), 'r5 DISCRIMINANTE: un logo propio en «Otra imagen» sin oscura se pinta igual: la vuelta a invertir es solo de la fábrica');
            set_config('logo', 'statics/images/logo.png');
            $check(\PiecesPHP\Settings\Controllers\SettingsController::isFactoryBrandImage('logo'), 'r6 la caché de isFactoryBrandImage() va por ruta: cambiar la opción en la misma petición se ve sin vaciarla');
        } finally {
            set_config('logo', $logoAntes);
            set_config($opcionLogo, $opcionAntes);
            set_config($opcionModo, $modoGetAntes);
            set_config($opcionOscura, $oscuraGetAntes);
            if (is_file(basepath($viejo))) {
                //RETORNO-IGNORADO: un temporal en app/cache/, que git ignora; f4 lo comprueba.
                unlink(basepath($viejo));
            }
        }
        $check(!is_file(basepath($viejo)), 'f4 no queda el logo viejo temporal');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $poner('owner', $propietarioAntes);
        $poner(\PiecesPHP\Settings\Controllers\SettingsController::BRAND_IMAGES_INVERT_ON_DARK['logo'], $invertirAntes);
        $poner($opcionModo, $modoAntes);
        //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
        MailLogMapper::deleteByRecipientMark($prefijo);
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($ids as $id) {
            $database->prepare('DELETE FROM `' . UserSessionsModel::TABLE . '` WHERE userID = ?')->execute([$id]);
            foreach ($referencias as [$tablaReferencia, $columna]) {
                $database->prepare("DELETE FROM `{$tablaReferencia}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $database->prepare('DELETE FROM `' . LoginAttemptsModel::TABLE . '` WHERE usernameAttempt LIKE ?')->execute(["{$prefijo}%"]);
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn()
         + (int) $database->query('SELECT COUNT(*) FROM `' . LoginAttemptsModel::TABLE . '` WHERE usernameAttempt LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0 && $crudo('owner') === $propietarioAntes && $crudo(\PiecesPHP\Settings\Controllers\SettingsController::BRAND_IMAGES_INVERT_ON_DARK['logo']) === $invertirAntes && $crudo($opcionModo) === $modoAntes, 'z1 no quedan el usuario, sus intentos ni sus sesiones, y el propietario, la inversión y el modo oscuro del logo vuelven a su valor en crudo', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('Las pantallas del acceso por HTTP: los enlaces de la ayuda, el error dentro del formulario, el ojo, el código de seis cifras hasta el ingreso, el correo como usuario, el pie con el propietario y el 404 con y sin sesión. Repone el propietario.')->setEffects([CliActions::EFFECT_DATABASE])->register();
