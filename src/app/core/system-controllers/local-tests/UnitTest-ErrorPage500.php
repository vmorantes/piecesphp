<?php

//La 500 fuera de local: con la marca, el mensaje genérico y la referencia, y NADA de la excepción (P56); la suelta si falla.
//Con PCSPHP_500_HTML_OUT=<ruta> guarda el HTML, con su <base> en la instalación servida, para abrirlo en un navegador.
//@areas nucleo

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\CustomErrorsHandlers\CustomSlimErrorHandler;
use PiecesPHP\Core\CustomErrorsHandlers\GenericHandler;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/error-page-500', function ($args) {

    echoTerminal("\e[33m[TEST:ErrorPage500] La página del error 500 con la marca, y la suelta si falla\e[39m");
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

    $secreto = 'zz-secreto-' . bin2hex(random_bytes(4));
    $request = new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/zz-error-page-500'), new Headers(), [], [], (new StreamFactory())->createStream(''));
    $terminal = TerminalData::getInstance();
    //Desde la terminal la respuesta es siempre JSON: la rama HTML solo se alcanza fingiendo una petición del navegador.
    $eraTerminal = $terminal->isTerminal() === true;

    try {
        $handler = new CustomSlimErrorHandler(new \DomainException($secreto), 'zz-error-page-500');
        $reference = $handler->reference();
        $terminal->isTerminal(false);
        try {
            $response = $handler->getResponse($request, false);
        } finally {
            $terminal->isTerminal($eraTerminal);
        }
        $html = (string) $response->getBody();
        $conMarca = str_contains($html, 'error-page-title');
        echoTerminal('   · página servida: ' . ($conMarca ? 'con la marca (500.php)' : 'la suelta'));

        $check($response->getStatusCode() === 500 && str_contains($html, $reference), 'a1 estado 500 y la referencia en el HTML', "HTTP {$response->getStatusCode()}");
        $check(!str_contains($html, $secreto), 'a2 el mensaje de la excepción no sale');
        $check(!str_contains($html, 'DomainException'), 'a3 ni el nombre de su clase');
        $check($conMarca, 'a4 es la página con la marca, no la suelta');
        $check(str_contains($html, \PiecesPHP\CSSVariables::globalVersionedURL()) && str_contains($html, 'statics/core/css/ui-pcs.css'), 'a5 con la hoja del tema');
        $logo = SettingsController::brandImageTag('logo', 'ui mini image');
        $check($logo !== '' && str_contains($html, $logo), 'a6 con el logo de la marca');
        $check(str_contains($html, CustomSlimErrorHandler::genericMessage($reference)) || str_contains($html, htmlspecialchars(CustomSlimErrorHandler::genericMessage($reference), \ENT_QUOTES, 'UTF-8')), 'a7 con el mensaje genérico');
        $check(str_contains($html, \PiecesPHP\UserSystem\Controllers\UserProblemsController::routeName('other-problems-form')), 'a8 con «Reportar problema»');

        $salida = (string) (getenv('PCSPHP_500_HTML_OUT') ?: '');
        if ($salida !== '') {
            $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
            $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
            $base = is_array($matrix) ? rtrim((string) ($matrix['medido']['base'] ?? ''), '/') . '/' : '';
            //En la terminal base_url() es http://localhost: los enlaces y la hoja apuntarían a otra parte.
            $guardado = $base !== '/' ? str_replace(base_url(), $base, $html) : $html;
            $check(file_put_contents($salida, $guardado) !== false, 'a9 HTML guardado para el navegador', $salida);
        }
    } catch (\Throwable $e) {
        $check(false, 'a0 la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    }

    $registro = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\') . '/' . GenericHandler::FILE_ENTRIES;
    $lineasCon = function (string $texto) use ($registro): int {
        clearstatcache();
        $lineas = is_file($registro) ? (file($registro) ?: []) : [];
        return count(array_filter($lineas, fn (string $l): bool => str_contains($l, $texto)));
    };
    //Pinta como una petición del navegador (fuera de la terminal) y devuelve la respuesta.
    $pintar = function (CustomSlimErrorHandler $handler) use ($terminal, $eraTerminal, $request) {
        $terminal->isTerminal(false);
        try {
            return $handler->getResponse($request, false);
        } finally {
            $terminal->isTerminal($eraTerminal);
        }
    };

    //─── b · Con la vista rota: la suelta, y la nota suave una vez ─────────────────────────────────
    echoTerminal('');
    echoTerminal('[b] Con la vista rota');
    $marcaVista = 'zz-vista-rota-' . bin2hex(random_bytes(4));
    $vistaRota = sys_get_temp_dir() . "/{$marcaVista}.php";
    try {
        $check(file_put_contents($vistaRota, "<?php\nob_start();\necho 'zz-medio';\nthrow new \\RuntimeException('{$marcaVista}');\n") !== false, 'b0 una vista rota en un temporal');
        $rota = new class (new \DomainException($secreto), 'zz-error-page-500') extends CustomSlimErrorHandler {
            public string $vista = '';
            protected function brandedViewPath(): string
            {
                return $this->vista;
            }
        };
        $rota->vista = $vistaRota;
        $antes = $lineasCon($marcaVista);
        $respuesta = $pintar($rota);
        $html = (string) $respuesta->getBody();
        $check($respuesta->getStatusCode() === 500 && str_contains($html, $rota->reference()) && !str_contains($html, 'error-page-title'), 'b1 sale la suelta, 500 y con la referencia');
        $check(!str_contains($html, $marcaVista) && !str_contains($html, 'zz-medio') && !str_contains($html, $secreto), 'b2 sin nada de la vista rota ni de la excepción');
        $check($lineasCon($marcaVista) - $antes === 1, 'b3 el fallo de la vista queda anotado, una vez', 'entradas nuevas: ' . ($lineasCon($marcaVista) - $antes));
    } catch (\Throwable $e) {
        $check(false, 'b0 la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        if (is_file($vistaRota)) {
            //RETORNO-IGNORADO: el temporal es de la prueba y no deja rastro en el repositorio.
            unlink($vistaRota);
        }
    }

    //─── c · Sin preguntar por la persona: con una sesión que la base no puede cargar, nada nuevo en el registro ──
    echoTerminal('');
    echoTerminal('[c] Una sesión que la base no puede cargar');
    $usuarioAntes = get_config('current_user');
    $guardadoAntes = get_config('pcsphp_current_user_stored');
    try {
        $sinBase = new CustomSlimErrorHandler(new \DomainException($secreto), 'zz-error-page-500');
        //Cada getLoggedFrameworkUser() con este usuario reintenta la carga, falla y la anota: contar entradas es contarlas.
        //Un id propio de la corrida: el registro es compartido, y cada entrada de esta consulta lleva su userID.
        $idSinBase = -random_int(100000000, 999999999);
        set_config('current_user', (object) ['id' => $idSinBase]);
        set_config('pcsphp_current_user_stored', null);
        $antes = $lineasCon("\"userID\":{$idSinBase}");
        $respuesta = $pintar($sinBase);
        $nuevas = $lineasCon("\"userID\":{$idSinBase}") - $antes;
        $check($respuesta->getStatusCode() === 500 && str_contains((string) $respuesta->getBody(), 'error-page-title'), 'c1 sale la página con la marca');
        //Queda una: el enlace «Reportar problema», porque routeName() filtra por rol y pregunta por la persona (sin la marca, 4).
        $check($nuevas === 1, 'c2 la sesión se consulta una sola vez: una entrada nueva en el registro', "entradas nuevas: {$nuevas}");
    } catch (\Throwable $e) {
        $check(false, 'c0 la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        set_config('current_user', $usuarioAntes);
        set_config('pcsphp_current_user_stored', $guardadoAntes);
    }

    //─── d · Con la persona cargada: sus preferencias y sin el botón de tema, como en el resto del panel ─────
    echoTerminal('');
    echoTerminal('[d] Con una sesión válida');
    $usuarioAntes = get_config('current_user');
    $guardadoAntes = get_config('pcsphp_current_user_stored');
    $prefijoUsuario = 'zz-prueba-error-500-' . bin2hex(random_bytes(3));
    $idPersona = null;
    try {
        $u = new UsersModel();
        $u->username = "{$prefijoUsuario}-persona";
        $u->email = "{$prefijoUsuario}-persona@example.com";
        //Nadie entra con contraseña: la sesión la finge la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Error 500';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_GENERAL;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        if (!$u->save() || (int) $u->id < 1) {
            throw new \RuntimeException('No se pudo crear el usuario de la prueba.');
        }
        $idPersona = (int) $u->id;
        //Un tema distinto del del sitio: si la 500 lo lleva, son las preferencias de la persona.
        $temaPersona = SettingsController::accessibilityThemeMode() === 'dark' ? 'light' : 'dark';
        $check(UsersModel::setMetaValue($idPersona, AdminPanelController::THEME_MODE_META, $temaPersona), "d0 la persona guarda el tema {$temaPersona}");
        set_config('current_user', (object) ['id' => $idPersona]);
        set_config('pcsphp_current_user_stored', null);
        $html = (string) $pintar(new CustomSlimErrorHandler(new \DomainException($secreto), 'zz-error-page-500'))->getBody();
        $check(str_contains($html, 'error-page-title'), 'd1 sale la página con la marca');
        $check(str_contains($html, "data-theme=\"{$temaPersona}\""), 'd2 con el tema de la persona, no el del sitio');
        $check(!str_contains($html, 'data-theme-toggle'), 'd3 y sin el botón de tema, que con sesión no sale');
    } catch (\Throwable $e) {
        $check(false, 'd0 la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        set_config('current_user', $usuarioAntes);
        set_config('pcsphp_current_user_stored', $guardadoAntes);
        if ($idPersona !== null) {
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            //RETORNO-IGNORADO: lo comprueba d4 contando lo que queda.
            $perfiles->delete(['belongsTo' => $idPersona])->execute();
        }
        $usuariosModelo = UsersModel::model();
        $usuariosModelo->resetAll();
        //RETORNO-IGNORADO: lo comprueba d4 contando lo que queda.
        $usuariosModelo->delete(new WhereSegment([WhereItem::like('username', "{$prefijoUsuario}%")]))->execute();
        $restos = UsersModel::model();
        $restos->resetAll();
        $restos->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijoUsuario}%")]))->execute();
        $check(count((array) $restos->result()) === 0, 'd4 no queda el usuario de la prueba');
    }

    //─── e · Una dirección base con comillas y «<» (un Host forjado) sale escapada ─────────────────
    echoTerminal('');
    echoTerminal('[e] Una dirección base forjada');
    //appURL es protegida y Config no tiene escritor: se fuerza por reflexión y se restaura SIEMPRE (como en access-guards).
    $configuracion = (new \ReflectionMethod(\PiecesPHP\Core\Config::class, 'get_instance'))->invoke(null);
    $appURL = new \ReflectionProperty(\PiecesPHP\Core\Config::class, 'appURL');
    $appURLAntes = $appURL->getValue($configuracion);
    $forjada = 'https://zz-host"><zz-inyectado x="/';
    try {
        $appURL->setValue($configuracion, $forjada);
        $html = (string) $pintar(new CustomSlimErrorHandler(new \DomainException($secreto), 'zz-error-page-500'))->getBody();
        $check(str_contains($html, 'error-page-title'), 'e1 sale la página con la marca');
        $check(str_contains($html, '<base href="' . escape_html($forjada) . '">'), 'e2 el <base href> lleva la dirección escapada');
        $check(!str_contains($html, '<zz-inyectado'), 'e3 y nada de ella entra como etiqueta');
    } catch (\Throwable $e) {
        $check(false, 'e0 la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        $appURL->setValue($configuracion, $appURLAntes);
    }
    $check($appURL->getValue($configuracion) === $appURLAntes, 'e4 appURL restaurada');

    return $balance();

})->setDescription('La página del error 500 fuera de local lleva la marca, el mensaje genérico y la referencia, y nada de la excepción; si su vista falla, sale la página suelta.')->setEffects([CliActions::EFFECT_FILES, CliActions::EFFECT_DATABASE])->register();
