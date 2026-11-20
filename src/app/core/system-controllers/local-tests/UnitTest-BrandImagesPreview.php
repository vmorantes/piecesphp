<?php

//Imágenes de marca en pestañas con la vista previa de cada sitio (H416), por HTTP. Solo lee: el usuario zz-agente-marca-* que crea lo borra al salir.
//@areas src/app/classes/PiecesPHP/Settings/

use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

CliActions::make('unit-tests:core/brand-images-preview', function ($args) {

    echoTerminal("\e[33m[TEST:BrandImagesPreview] Imágenes de marca: pestañas y vista previa de cada sitio\e[39m");
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
    if (!$check($base !== '' && $database !== null, 'p1 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::headerName();
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, string $jwt) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => $jwt !== '' ? ["{$cabeceraToken}: {$jwt}"] : [],
        ]);
        $body = curl_exec($handle);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    /** @return array{0:\DOMXPath,1:\Closure(string): list<\DOMElement>} */
    $leer = function (string $html): array {
        $dom = new \DOMDocument();
        //RETORNO-IGNORADO: un HTML que no se pueda leer deja el DOM vacío y los casos de abajo fallan.
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        $xpath = new \DOMXPath($dom);
        $nodos = fn (string $consulta): array => array_values(array_filter(iterator_to_array($xpath->query($consulta) ?: new \ArrayIterator([])), fn ($nodo): bool => $nodo instanceof \DOMElement));
        return [$xpath, $nodos];
    };

    $prefijo = 'zz-agente-marca-' . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    try {
        $usuario = new UsersModel();
        $usuario->username = "{$prefijo}-root";
        $usuario->email = "{$prefijo}-root@example.com";
        $usuario->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $usuario->firstname = 'Zz';
        $usuario->secondname = '';
        $usuario->firstLastname = 'Marca';
        $usuario->secondLastname = '';
        $usuario->type = UsersModel::TYPE_USER_ROOT;
        $usuario->status = UsersModel::STATUS_USER_ACTIVE;
        $usuario->failedAttempts = 0;
        $usuario->organization = \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL;
        $usuario->createdAt = new \DateTime();
        $usuario->modifiedAt = $usuario->createdAt;
        $usuario->save();
        $jwtRoot = UserSessions::issueToken((int) $usuario->id);
        //Un tamaño de letra y un contraste personales distintos de los del sitio: la página de error los traería (t17).
        $tamanoSitio = SettingsController::accessibilityTextSize();
        $tamanoPersonal = array_values(array_diff(AdminPanelController::TEXT_SIZES, [$tamanoSitio]))[0];
        $contrasteSitio = SettingsController::accessibilityContrast();
        $contrastePersonal = array_values(array_diff(AdminPanelController::CONTRASTS, [$contrasteSitio]))[0];
        $check(UsersModel::setMetaValue((int) $usuario->id, AdminPanelController::TEXT_SIZE_META, $tamanoPersonal) && UsersModel::setMetaValue((int) $usuario->id, AdminPanelController::CONTRAST_META, $contrastePersonal), "p2 el usuario de la prueba elige «{$tamanoPersonal}» y «{$contrastePersonal}»; el sitio tiene «{$tamanoSitio}» y «{$contrasteSitio}»");
        $pantalla = $camino((string) SettingsController::routeName('appearance-brand-images', [], true));

        echoTerminal(' ');
        echoTerminal('[t] Las pestañas');
        $r = $pedir($pantalla, $jwtRoot);
        [, $nodos] = $leer($r['body']);
        $pestanas = array_map(fn (\DOMElement $nodo): string => $nodo->getAttribute('data-tab'), $nodos('//*[@data-brand-images-tabs]//*[@data-tab]'));
        $paneles = array_map(fn (\DOMElement $nodo): string => $nodo->getAttribute('data-tab'), $nodos("//div[contains(concat(' ', @class, ' '), ' tab ')][@data-tab]"));
        $check($r['status'] === 200 && $pestanas === ['images', 'loader', 'preview'] && $paneles === $pestanas, 't1 tres pestañas, Imágenes, Cargador y Vista previa, con un panel cada una', "HTTP {$r['status']}, " . json_encode([$pestanas, $paneles]));
        $enPanel = fn (string $panel, array $ids): bool => count(array_filter($ids, fn (string $id): bool => count($nodos("//div[@data-tab='{$panel}']//article[@id='{$id}']")) === 1)) === count($ids);
        $direccion = fn (string $atributo): string => ($nodos("//*[@{$atributo}]")[0] ?? null)?->getAttribute($atributo) ?? '';
        $check(substr_count($direccion('data-preview-url'), '?') === 1 && str_ends_with($direccion('data-preview-url'), 'preview=yes') && substr_count($direccion('data-loader-url'), '?') === 1 && str_ends_with($direccion('data-loader-url'), 'loader=yes'), 't1b las direcciones de recarga y del cargador, con un solo «?» y su parámetro', (string) json_encode([$direccion('data-preview-url'), $direccion('data-loader-url')]));
        $check($enPanel('images', ['image-logo', 'image-favicon-back', 'image-favicon', 'image-mailing-logo', 'image-partners', 'image-partners-vertical']) && count($nodos("//div[@data-tab='images']//form[@partners-text-form]")) === 1, 't2 Imágenes: logo, los dos favicon, correos y Terceros con el texto de los patrocinadores');
        $check($enPanel('loader', ['image-loader-animation', 'image-loader-logo']) && count($nodos("//div[@data-tab='images']//article[@id='image-loader-animation' or @id='image-loader-logo']")) === 0, 't3 Cargador: la animación y el logo del cargador, y solo ahí');
        //Las filas de dos tarjetas, en columnas: una fila de tarjetas las estiraba y dejaba un hueco sobre las vistas previas.
        $check(count($nodos("//*[@data-tab='images' or @data-tab='loader']//div[contains(concat(' ', @class, ' '), ' two ') and contains(concat(' ', @class, ' '), ' cards ')]")) === 0, 't4 ninguna fila «two cards»: cada tarjeta en su columna');
        $check(count($nodos("//*[@data-tab='preview']//*[@data-brand-previews][@data-preview-url]")) === 1 && count($nodos("//*[@data-image-editor=''][not(ancestor::*[@data-tab])]")) === 1, 't5 la vista previa con su dirección de recarga, y el editor de la pantalla una sola vez y fuera de las pestañas');

        echoTerminal(' ');
        echoTerminal('[v] La vista previa: un sitio por muestra, en claro y oscuro, con el marcado de verdad');
        $esperadas = ['menu-expanded', 'menu-collapsed', 'mobile-bar', 'access', 'loader', 'error', 'mail', 'browser-tab'];
        $muestras = $nodos("//div[@data-tab='preview']//iframe[@data-brand-preview]");
        $documentos = [];
        $accesibles = [];
        foreach ($muestras as $muestra) {
            $clave = $muestra->getAttribute('data-brand-preview');
            $modo = $muestra->getAttribute('data-theme-preview');
            $documentos[$clave][$modo] = $muestra->getAttribute('srcdoc');
            $accesibles["{$clave}/{$modo}"] = $muestra->getAttribute('title') !== '' && $muestra->getAttribute('tabindex') === '-1' && $muestra->getAttribute('aria-hidden') === 'true';
        }
        $completas = array_filter($documentos, fn (array $modos): bool => array_keys($modos) === ['light', 'dark']);
        $check(array_keys($completas) === $esperadas && count($muestras) === 2 * count($esperadas), 't6 las ocho muestras, cada una en claro y oscuro', (string) json_encode(array_map('array_keys', $documentos)));
        $check($accesibles !== [] && !in_array(false, $accesibles, true), 't7 cada una con su title, tabindex -1 y aria-hidden', (string) json_encode(array_keys(array_filter($accesibles, fn (bool $bien): bool => !$bien))));
        $modoDe = fn (string $doc): array => preg_match('/<html\b[^>]*>/i', $doc, $html) === 1 && preg_match_all('/\bdata-theme="([^"]*)"/', $html[0], $m) > 0 ? $m[1] : [];
        $temas = [];
        foreach ($documentos as $clave => $modos) {
            foreach ($modos as $modo => $doc) {
                if ($clave !== 'mail') {
                    $temas["{$clave}/{$modo}"] = $modoDe($doc) === [$modo];
                }
            }
        }
        $check($temas !== [] && !in_array(false, $temas, true), 't8 DISCRIMINANTE: cada documento con un solo data-theme, el de su modo (también la página de error, que trae el suyo)', (string) json_encode(array_keys(array_filter($temas, fn (bool $bien): bool => !$bien))));
        $doc = fn (string $clave, string $modo = 'dark'): string => $documentos[$clave][$modo] ?? '';
        $check(str_contains($doc('menu-expanded'), '<span class="logo">' . SettingsController::brandImageTag('logo', '') . '</span>') && str_contains($doc('menu-expanded'), SettingsController::brandImageTag('partners', 'partners')), 't9 DISCRIMINANTE: el menú expandido es menu.php: el logo y la bandera horizontal de brandImageTag()');
        $check(str_contains($doc('menu-collapsed'), 'ui collapsed shell') && str_contains($doc('menu-collapsed'), SettingsController::brandImageTag('partnersVertical', 'partners')), 't10 el contraído, con la bandera vertical');
        $check(str_contains($doc('mobile-bar'), '<span class="mobile only logo" aria-hidden="true">' . SettingsController::brandImageTag('logo') . '</span>'), 't11 la barra del móvil, con el logo de brandImageTag()');
        $check(str_contains($doc('access'), 'access-brand') && str_contains($doc('access'), SettingsController::brandImageTag('logo', 'ui tiny image')) && str_contains($doc('access'), 'UserSystem/Statics/css/login.css'), 't12 el acceso es access-frame.php, con las hojas de la página del acceso');
        $check(str_contains($doc('loader'), 'data-loader-animation="' . escape_html(SettingsController::brandImagePath('loaderAnimation')) . '"') && str_contains($doc('loader'), 'GenericLoaderElement.js') && str_contains($doc('loader'), 'brand-preview-loader.js'), 't13 el cargador lo construye GenericLoaderElement con los data-loader-* guardados');
        $check(str_contains($doc('error'), 'error-page-title') && str_contains($doc('error'), SettingsController::brandImageTag('logo', 'ui mini image')), 't14 la página de error es 404.php con error-page.php');
        //La página de error trae en su <html> las preferencias de quien la mira: la muestra lleva las del SITIO.
        $htmlDe = fn (string $documento): string => preg_match('/<html\b[^>]*>/i', $documento, $html) === 1 ? $html[0] : '';
        $atributoDe = fn (string $html, string $nombre): array => preg_match_all('/\s' . preg_quote($nombre, '/') . '="([^"]*)"/', $html, $m) > 0 ? $m[1] : [];
        $errorHtml = $htmlDe($doc('error'));
        $check($atributoDe($errorHtml, 'data-text-size') === [$tamanoSitio] && $atributoDe($errorHtml, 'data-contrast') === [$contrasteSitio] && str_contains($errorHtml, trim(SettingsController::themePreviewAttributes())), "t17 DISCRIMINANTE: la página de error, con la Accesibilidad del SITIO («{$tamanoSitio}», «{$contrasteSitio}») y no la de quien edita, una vez cada una", $errorHtml);
        $check(str_contains($doc('mail'), mailing_logo()) && $doc('mail', 'light') === $doc('mail'), 't15 el correo es template_base.php con mailing_logo(), igual en los dos modos');
        $check(str_contains($doc('browser-tab'), (string) get_config('favicon-back')) && str_contains($doc('browser-tab'), (string) get_config('favicon')), 't16 la pestaña, con los dos favicon');

        echoTerminal(' ');
        echoTerminal('[f] La vista previa sola, por la misma ruta');
        $r = $pedir("{$pantalla}?preview=yes", $jwtRoot);
        [, $nodosFragmento] = $leer($r['body']);
        $check($r['status'] === 200 && !str_contains($r['body'], '<!DOCTYPE html>') && count($nodosFragmento('//iframe[@data-brand-preview]')) === 2 * count($esperadas) && count($nodosFragmento('//*[@data-brand-images-tabs]')) === 0, 'f1 ?preview=yes da solo las dieciséis muestras, sin el armazón', "HTTP {$r['status']}");
        $r = $pedir("{$pantalla}?preview=yes", '');
        $check($r['status'] !== 200 && !str_contains($r['body'], 'data-brand-preview='), 'f2 sin sesión, nada', "HTTP {$r['status']}");
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $consultaUsuarios = $database->query("SELECT id FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"));
        foreach ($consultaUsuarios->fetchAll(\PDO::FETCH_COLUMN) as $id) {
            $database->prepare('DELETE FROM `' . \SystemApprovals\Mappers\SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ? AND referenceValue = ?')->execute([$tablaUsuarios, (string) $id]);
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $perfiles->delete(['belongsTo' => (int) $id])->execute();
        }
        $database->exec("DELETE FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"));
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('Imágenes de marca: tres pestañas (Imágenes, Cargador, Vista previa) y, en la última, una muestra por sitio que pinta una imagen de marca (menú expandido y contraído, barra del móvil, acceso, cargador, página de error, correo y pestaña del navegador), en claro y oscuro, con el marcado real de cada sitio; ?preview=yes da solo la vista previa. Solo lee.')->setEffects([CliActions::EFFECT_DATABASE])->register();
