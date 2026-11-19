<?php

//Estilo del panel por HTTP. El usuario zz-agente-estilo-* que crea (con su tamaño de letra) lo borra al salir, y repone en crudo el modo oscuro del logo.
//@areas src/app/classes/PiecesPHP/Settings/

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

CliActions::make('unit-tests:core/style-panel', function ($args) {

    echoTerminal("\e[33m[TEST:StylePanel] Estilo del panel: pestañas, muestras, fábrica, redirección y menú\e[39m");
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

    //──── [m] El menú, sin pedir nada: el href no depende del usuario ─────────────────────
    echoTerminal('[m] Una sola entrada de menú');
    $colores = (string) SettingsController::routeName('appearance-colors', [], true);
    $accesibilidad = (string) SettingsController::routeName('appearance-accessibility', [], true);
    $hrefs = [];
    foreach (SettingsController::panelGroups() as $grupo) {
        foreach ($grupo['items'] as $item) {
            $hrefs[] = (string) $item['href'];
        }
    }
    $entradas = array_values(array_filter(SettingsController::panelGroups(), fn (array $grupo): bool => count(array_filter($grupo['items'], fn (array $item): bool => $item['href'] === $colores)) > 0));
    $entrada = $entradas !== [] ? array_values(array_filter($entradas[0]['items'], fn (array $item): bool => $item['href'] === $colores))[0] : [];
    $check(count(array_keys($hrefs, $colores, true)) === 1 && !in_array($accesibilidad, $hrefs, true), 'm1 appearance-colors sale una vez y appearance-accessibility ninguna', (string) json_encode(array_count_values($hrefs)[$colores] ?? 0));
    $check(($entrada['name'] ?? '') === __(SettingsController::LANG_GROUP, 'Estilo del panel') && ($entrada['keywords'] ?? '') === __(SettingsController::LANG_GROUP, 'colores, accesibilidad, fuente, tipografía, contraste'), 'm2 se llama «Estilo del panel» y busca por colores, accesibilidad, fuente, tipografía y contraste', (string) json_encode($entrada));
    $check(is_array(get_route_info('configurations-appearance-accessibility')) && is_array(get_route_info('configurations-appearance-accessibility-save')), 'm3 CANARIO: la ruta vieja y su guardado siguen (el nombre es el permiso)');

    //──── Por HTTP ───────────────────────────────────────────────────────────────────────
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    echoTerminal(' ');
    if (!$check($base !== '' && $database !== null, 'p1 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::headerName();
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, string $jwt, ?array $campos = null) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $location = '';
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array_merge(["{$cabeceraToken}: {$jwt}"], $campos !== null ? ['X-Requested-With: XMLHttpRequest'] : []),
            CURLOPT_HEADERFUNCTION => function ($curl, string $linea) use (&$location): int {
                if (stripos($linea, 'Location:') === 0) {
                    $location = trim(substr($linea, 9));
                }
                return strlen($linea);
            },
        ] + ($campos !== null ? [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($campos)] : []));
        $body = curl_exec($handle);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : '', 'location' => $location];
    };

    $prefijo = 'zz-agente-estilo-' . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    //El modo oscuro del logo, en crudo (null: sin guardar): t5 lo pone en invert y [z] repone este valor.
    $tablaOpciones = (new SettingsModel())->getModel()->getTable();
    $opcionModoLogo = SettingsController::BRAND_IMAGES_DARK_MODE['logo']['mode'];
    $modoLogoCrudo = function () use ($database, $tablaOpciones, $opcionModoLogo): ?string {
        $consulta = $database->prepare("SELECT value FROM `{$tablaOpciones}` WHERE BINARY name = ?");
        $consulta->execute([$opcionModoLogo]);
        $valor = $consulta->fetchColumn();
        return $valor === false ? null : (string) $valor;
    };
    $modoLogoAntes = $modoLogoCrudo();
    try {
        $usuario = new UsersModel();
        $usuario->username = "{$prefijo}-root";
        $usuario->email = "{$prefijo}-root@example.com";
        $usuario->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $usuario->firstname = 'Zz';
        $usuario->secondname = '';
        $usuario->firstLastname = 'Estilo';
        $usuario->secondLastname = '';
        $usuario->type = UsersModel::TYPE_USER_ROOT;
        $usuario->status = UsersModel::STATUS_USER_ACTIVE;
        $usuario->failedAttempts = 0;
        $usuario->organization = \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL;
        $usuario->createdAt = new \DateTime();
        $usuario->modifiedAt = $usuario->createdAt;
        $usuario->save();
        $jwtRoot = UserSessions::issueToken((int) $usuario->id);

        echoTerminal(' ');
        echoTerminal('[r] La ruta vieja lleva a la pestaña');
        $r = $pedir($camino($accesibilidad), $jwtRoot);
        $check($r['status'] === 302 && str_ends_with($r['location'], '#accesibilidad') && $camino(explode('#', $r['location'])[0]) === $camino($colores), 'r1 appearance-accessibility: 302 a appearance-colors#accesibilidad', "HTTP {$r['status']}, Location {$r['location']}");

        echoTerminal(' ');
        echoTerminal('[t] La pantalla');
        //Una preferencia personal distinta de la del sitio: las muestras enseñan la del sitio (t4). Se va con el usuario.
        $tamanoSitio = SettingsController::accessibilityTextSize();
        $tamanoPersonal = array_values(array_diff(AdminPanelController::TEXT_SIZES, [$tamanoSitio]))[0];
        $r = $pedir($camino((string) AdminPanelController::routeName('text-size', [], true)), $jwtRoot, ['size' => $tamanoPersonal]);
        $check($r['status'] === 200 && AdminPanelController::textSizeOf((int) $usuario->id) === $tamanoPersonal, "t0 el usuario de la prueba elige «{$tamanoPersonal}» y el sitio tiene «{$tamanoSitio}»", "HTTP {$r['status']}");
        //Invert: la muestra oscura tiene que llevar el filtro, que el logo claro sin más no lleva (t5 DISCRIMINANTE).
        $r = $pedir($camino((string) SettingsController::routeName('appearance-brand-images', [], true)), $jwtRoot, ['darkMode' => 'logo', 'mode' => SettingsController::BRAND_IMAGE_DARK_INVERT]);
        $check($r['status'] === 200 && $modoLogoCrudo() === SettingsController::BRAND_IMAGE_DARK_INVERT, 't00 el modo oscuro del logo, en «Invertir en blanco» por su ruta de guardado', "HTTP {$r['status']} · " . var_export($modoLogoCrudo(), true));
        $r = $pedir($camino($colores), $jwtRoot);
        $dom = new \DOMDocument();
        //RETORNO-IGNORADO: un HTML que no se pueda leer deja el DOM vacío y los casos de abajo fallan.
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $r['body']);
        $xpath = new \DOMXPath($dom);
        /** @var \Closure(string): list<\DOMElement> $nodos */
        $nodos = fn (string $consulta): array => array_values(array_filter(iterator_to_array($xpath->query($consulta) ?: new \ArrayIterator([])), fn ($nodo): bool => $nodo instanceof \DOMElement));
        $pestanas = array_map(fn (\DOMElement $nodo): string => $nodo->getAttribute('data-tab'), $nodos("//*[@data-style-panel-tabs]//*[@data-tab]"));
        $check($r['status'] === 200 && $pestanas === ['colores', 'tipografia', 'accesibilidad'], 't1 tres pestañas: Colores, Tipografía y forma, Accesibilidad', "HTTP {$r['status']}, " . json_encode($pestanas));
        $paneles = array_map(fn (\DOMElement $nodo): string => $nodo->getAttribute('data-tab'), $nodos("//div[contains(concat(' ', @class, ' '), ' tab ')][@data-tab]"));
        $check($paneles === ['colores', 'tipografia', 'accesibilidad'] && count($nodos("//form[@colors-form]//div[@data-tab='colores' or @data-tab='tipografia']")) === 2 && count($nodos("//div[@data-tab='accesibilidad']//form[@accessibility-form]")) === 1, 't2 un panel por pestaña: las dos primeras en el formulario de colores, la tercera con el de accesibilidad', (string) json_encode($paneles));
        $muestras = $nodos('//iframe[@data-style-preview]');
        $esperado = ['light' => __(SettingsController::LANG_GROUP, 'Muestra en modo claro'), 'dark' => __(SettingsController::LANG_GROUP, 'Muestra en modo oscuro')];
        $vistas = [];
        foreach ($muestras as $muestra) {
            $modo = $muestra->getAttribute('data-style-preview');
            $doc = $muestra->getAttribute('srcdoc');
            $vistas[$modo] = $muestra->getAttribute('title') === ($esperado[$modo] ?? null)
                && $muestra->getAttribute('tabindex') === '-1'
                && $muestra->getAttribute('aria-hidden') === 'true'
                && preg_match('/<html\b[^>]*>/', $doc, $html) === 1
                && substr_count($html[0], 'data-theme=') === 1
                && str_contains($html[0], "data-theme=\"{$modo}\"")
                && str_contains($html[0], 'data-text-size=')
                && str_contains($doc, 'data-style-preview-content')
                && str_contains($doc, 'semantic.min.css');
        }
        $check(count($muestras) === 2 && $vistas === ['light' => true, 'dark' => true], 't3 dos muestras, claro y oscuro: title, tabindex -1, aria-hidden, su data-theme y los atributos del panel, la muestra y las hojas del tema', (string) json_encode($vistas));
        $documentos = [];
        foreach ($muestras as $muestra) {
            $documentos[$muestra->getAttribute('data-style-preview')] = $muestra->getAttribute('srcdoc');
        }
        $tamanos = array_map(fn (string $doc): string => preg_match('/<html\b[^>]*\bdata-text-size="([^"]*)"/', $doc, $m) === 1 ? $m[1] : '', $documentos);
        $check($tamanos === ['light' => $tamanoSitio, 'dark' => $tamanoSitio], "t4 DISCRIMINANTE: las dos muestras, con el tamaño del SITIO («{$tamanoSitio}») y no el de quien edita", (string) json_encode($tamanos));
        //Con invert, lo que enseña brandImageDarkView() es la clara con el filtro; aquí no se lee: get_config() de este proceso no ve el cambio.
        $logoEsperado = [
            'light' => [SettingsController::brandImagePath('logo'), false],
            'dark' => [SettingsController::brandImagePath('logo'), true],
        ];
        $logos = [];
        foreach ($documentos as $modo => $doc) {
            $logos[$modo] = preg_match('/<svg width="28" height="28"( class="invert-on-dark")?><image href="([^"]*)"/', $doc, $m) === 1 ? [html_entity_decode($m[2], \ENT_QUOTES), $m[1] !== ''] : null;
        }
        $check($logos === $logoEsperado && str_contains($documentos['dark'] ?? '', 'ui-pcs.css'), 't5 DISCRIMINANTE: el logo de cada muestra: el claro en la clara y, con «Invertir en blanco», el mismo con invert-on-dark en la oscura (ui-pcs.css en el iframe)', (string) json_encode(['visto' => $logos, 'esperado' => $logoEsperado]));

        echoTerminal(' ');
        echoTerminal('[f] «Restablecer» lleva lo de fábrica de su pestaña');
        $restablecer = [];
        foreach ($nodos('//*[@style-reset]') as $boton) {
            $panel = $xpath->query('ancestor::div[@data-tab][1]', $boton);
            $nombre = $panel !== false && $panel->item(0) instanceof \DOMElement ? $panel->item(0)->getAttribute('data-tab') : '';
            $restablecer[$nombre] = json_decode($boton->getAttribute('style-reset'), true);
        }
        $valores = fn (string $pestana): array => is_array($restablecer[$pestana]['values'] ?? null) ? $restablecer[$pestana]['values'] : [];
        $check(array_keys($restablecer) === ['colores', 'tipografia', 'accesibilidad'] && count(array_filter($restablecer, fn ($uno): bool => is_array($uno) && ($uno['items'] ?? []) !== [] && ($uno['title'] ?? '') !== '')) === 3, 'f1 un Restablecer por pestaña, con su título y su lista', (string) json_encode(array_keys($restablecer)));
        $colorFabrica = $valores('colores');
        $series = array_intersect_key($colorFabrica, array_flip(array_values(SettingsController::DATA_TOKENS)));
        $check(($colorFabrica['main_brand_color'] ?? null) === SettingsController::FACTORY_COLORS['main_brand_color'] && ($colorFabrica['main_brand_color_dark'] ?? null) === '' && ($colorFabrica['second_brand_color'] ?? null) === SettingsController::FACTORY_COLORS['second_brand_color'] && ($colorFabrica['third_brand_color'] ?? null) === SettingsController::FACTORY_COLORS['third_brand_color'] && ($colorFabrica['browser_bar'] ?? null) === SettingsController::BROWSER_BAR_ACCENT && count($series) === 4 && array_values(array_unique($series)) === [''], 'f2 Colores: acentos de fábrica, oscuro y series vacíos, barra con el acento', (string) json_encode($colorFabrica));
        $guardado = ['theme_font' => SettingsController::themeFont(), 'theme_font_size' => SettingsController::themeFontSize(), 'theme_radius' => SettingsController::themeRadius()];
        $check(array_intersect_key($colorFabrica, $guardado) === $guardado, 'f3 y la tipografía, la guardada: no la de fábrica ni la editada', (string) json_encode(array_intersect_key($colorFabrica, $guardado)));
        $tipoFabrica = $valores('tipografia');
        $check(($tipoFabrica['theme_font'] ?? null) === SettingsController::THEME_FONT_FACTORY && ($tipoFabrica['theme_font_size'] ?? null) === SettingsController::THEME_FONT_SIZE_FACTORY && ($tipoFabrica['theme_radius'] ?? null) === SettingsController::THEME_RADIUS_FACTORY && ($tipoFabrica['main_brand_color'] ?? null) === SettingsController::themeTokens()['accent'], 'f4 Tipografía: fuente, tamaño y radio de fábrica; los colores, los guardados', (string) json_encode($tipoFabrica));
        $check($valores('accesibilidad') === SettingsController::ACCESSIBILITY_FACTORY, 'f5 Accesibilidad: los siete de ACCESSIBILITY_FACTORY', (string) json_encode($valores('accesibilidad')));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $tablaAprobaciones = \SystemApprovals\Mappers\SystemApprovalsMapper::TABLE;
        $consultaUsuarios = $database->query("SELECT id FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"));
        foreach ($consultaUsuarios->fetchAll(\PDO::FETCH_COLUMN) as $id) {
            $database->prepare("DELETE FROM `{$tablaAprobaciones}` WHERE referenceTable = ? AND referenceValue = ?")->execute([$tablaUsuarios, (string) $id]);
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $perfiles->delete(['belongsTo' => (int) $id])->execute();
        }
        $database->exec("DELETE FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"));
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario de la prueba', "quedan {$quedan}");
        if ($modoLogoAntes === null) {
            $database->prepare("DELETE FROM `{$tablaOpciones}` WHERE BINARY name = ?")->execute([$opcionModoLogo]);
        } elseif ($modoLogoCrudo() === null) {
            $database->prepare("INSERT INTO `{$tablaOpciones}` (name, value) VALUES (?, ?)")->execute([$opcionModoLogo, $modoLogoAntes]);
        } else {
            $database->prepare("UPDATE `{$tablaOpciones}` SET value = ? WHERE BINARY name = ?")->execute([$modoLogoAntes, $opcionModoLogo]);
        }
        $check($modoLogoCrudo() === $modoLogoAntes, 'z2 el modo oscuro del logo vuelve al que tenía', var_export(['antes' => $modoLogoAntes, 'ahora' => $modoLogoCrudo()], true));
    }

    return $balance();

})->setDescription('Estilo del panel: la pantalla de appearance-colors con tres pestañas y dos muestras (claro y oscuro) accesibles, Restablecer con lo de fábrica de su pestaña, appearance-accessibility redirige a su pestaña y el menú tiene una sola entrada.')->setEffects([CliActions::EFFECT_DATABASE])->register();
