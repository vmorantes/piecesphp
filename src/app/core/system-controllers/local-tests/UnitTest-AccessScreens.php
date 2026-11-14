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
        $developer = trim((string) get_config('developer'));
        if ($developer !== '') {
            $check(str_contains($pedir($ruta404)['body'], 'data-error-developer'), 'p5 la página de error lleva el crédito «Desarrollado por», como el menú lateral');
        }
        $partners = trim((string) get_config('partners'));
        $check($partners === '' || $dom($pedir($rutaAcceso)['body'])->query('//*[@data-access-mobile-partners][contains(@class,"mobile tablet only")]//img')?->length === 1, 'p6 el patrocinador, bajo el formulario en móvil y tableta');
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
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $poner('owner', $propietarioAntes);
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
        $check($quedan === 0 && $crudo('owner') === $propietarioAntes, 'z1 no quedan el usuario, sus intentos ni sus sesiones, y el propietario vuelve a su valor en crudo', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('Las pantallas del acceso por HTTP: los enlaces de la ayuda, el error dentro del formulario, el ojo, el código de seis cifras hasta el ingreso, el correo como usuario, el pie con el propietario y el 404 con y sin sesión. Repone el propietario.')->setEffects([CliActions::EFFECT_DATABASE])->register();
