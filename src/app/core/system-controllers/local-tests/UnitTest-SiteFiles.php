<?php

//robots.txt, humans.txt y llms.txt servidos por ruta (ADR 0032 §3), y su pantalla «Archivos para buscadores», por HTTP.
//Crea dos usuarios zz-prueba-archivos-* y escribe los tres añadidos; lo devuelve todo como estaba.

use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\Controllers\SiteFilesController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/site-files', function ($args) {

    echoTerminal("\e[33m[TEST:SiteFiles] robots.txt, humans.txt y llms.txt los sirve la aplicación, con lo que añada la instalación\e[39m");
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

    //─── Puro · la validación de lo añadido a robots.txt ──────────────────────────────────────
    echoTerminal('[0] SiteFilesController::isValidRobotsAddition()');
    $casos = [
        'vacío' => ['', true],
        'comentario y campos válidos' => ["# nota\nUser-agent: Bingbot\nDisallow: /privado/\n\nAllow: /privado/publico/\nCrawl-delay: 5\nSitemap: https://x.test/s.xml", true],
        'campo en minúsculas' => ['disallow: /x/', true],
        'campo inventado' => ['Noindex: /x/', false],
        'sin dos puntos' => ['Disallow /x/', false],
        'campo sin valor' => ['Disallow:', false],
        'texto suelto' => ['hola', false],
    ];
    foreach ($casos as $nombre => [$texto, $esperado]) {
        $check(SiteFilesController::isValidRobotsAddition($texto) === $esperado, "0 {$nombre} → " . ($esperado ? 'válido' : 'rechazado'));
    }
    echoTerminal(' ');

    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $ruta = function (string $nombre): string {
        $url = get_route($nombre, [], true);
        return is_string($url) && $url !== '' ? '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/') : '';
    };
    $fallosAntesDelCanario = $failed;
    echoTerminal('[canario] Lo que esta prueba da por hecho');
    $check($base !== '', 'c1 hay una base con la que pedir');
    //Se resuelven UNA vez: una ruta vacía pediría la portada, y un 200 de la portada no prueba nada.
    $rutas = [
        'robots' => $ruta('site-files-robots'),
        'humans' => $ruta('site-files-humans'),
        'llms' => $ruta('site-files-llms'),
        'pantalla' => $ruta('configurations-appearance-site-files'),
        'accion' => $ruta('configurations-appearance-site-files-save'),
    ];
    foreach ($rutas as $clave => $valor) {
        $check($valor !== '' && $valor !== '/', "c2 se resuelve la ruta de {$clave}", $valor);
    }
    if ($failed > $fallosAntesDelCanario) {
        return $balance();
    }
    echoTerminal(' ');

    $cabeceraToken = SessionToken::tokenName();
    $pedir = function (string $metodo, string $path, ?string $jwt = null, array $datos = [], bool $xhr = false) use ($base, $cabeceraToken): array {
        $cabeceras = $jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [];
        if ($xhr) {
            $cabeceras[] = 'X-Requested-With: XMLHttpRequest';
        }
        $recibidas = [];
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $cabeceras,
            CURLOPT_HEADERFUNCTION => function (\CurlHandle $h, string $linea) use (&$recibidas): int {
                $partes = explode(':', $linea, 2);
                if (count($partes) === 2) {
                    $recibidas[mb_strtolower(trim($partes[0]))] = trim($partes[1]);
                }
                return strlen($linea);
            },
        ]);
        if ($metodo === 'POST') {
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($datos));
        }
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $body = is_string($body) ? $body : '';
        return ['status' => $status, 'body' => $body, 'h' => $recibidas, 'json' => json_decode($body, true)];
    };

    $prefijo = 'zz-prueba-archivos-' . bin2hex(random_bytes(3));
    $crear = function (string $sufijo, int $tipo) use ($prefijo): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        //Nadie entra con contraseña: los tokens los fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Prueba';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        return (int) $u->id;
    };
    $token = fn(int $id): string => SessionToken::generateToken(['id' => $id], null, null, false);
    $opciones = [SiteFilesController::CONFIG_ROBOTS, SiteFilesController::CONFIG_HUMANS, SiteFilesController::CONFIG_LLMS, SiteFilesController::CONFIG_UPDATED];
    $previas = [];
    foreach ($opciones as $opcion) {
        $previas[$opcion] = [SettingsModel::optionExists($opcion), SettingsModel::getConfigValue($opcion)];
    }
    $guardado = fn(): string => (string) json_encode(array_map(fn($o) => SettingsModel::getConfigValue($o), $opciones));
    $ids = [];

    try {
        $ids['admin'] = $crear('admin', UsersModel::TYPE_USER_ADMIN_GRAL);
        $ids['general'] = $crear('general', UsersModel::TYPE_USER_GENERAL);
        $admin = $token($ids['admin']);
        $general = $token($ids['general']);
        //La primera petición registra a los usuarios nuevos en las aprobaciones.
        $pedir('GET', '/');
        $check(in_array(UsersModel::TYPE_USER_ADMIN_GRAL, SettingsController::ROLES_SEO, true) && !in_array(UsersModel::TYPE_USER_GENERAL, SettingsController::ROLES_SEO, true), 'banco: la pantalla lleva los roles de «Identidad y SEO»: el administrador general sí, el general no');
        $check($pedir('GET', $rutas['pantalla'], $admin)['status'] === 200, 'banco: el administrador general abre la pantalla (sin él, todo 403 sería gratis)');
        echoTerminal(' ');

        //─── A · Los tres archivos, sin sesión ───────────────────────────────────────────────────
        echoTerminal('[A] Los tres archivos, sin sesión');
        //No es canario a propósito: con el archivo viejo en disco, lo que hay que ver es que Apache lo sirve en vez de la ruta.
        $check(!is_file(basepath('robots.txt')), 'a0 no hay src/robots.txt en disco: si lo hubiera, Apache lo serviría antes que la ruta');
        $tipos = ['robots' => 'text/plain; charset=utf-8', 'humans' => 'text/plain; charset=utf-8', 'llms' => 'text/markdown; charset=utf-8'];
        $servidos = [];
        foreach ($tipos as $clave => $tipo) {
            $r = $pedir('GET', $rutas[$clave]);
            $servidos[$clave] = $r['body'];
            $check($r['status'] === 200 && ($r['h']['content-type'] ?? '') === $tipo && ($r['h']['cache-control'] ?? '') === 'public, max-age=3600', "a1 {$clave}: 200, {$tipo} y caché de una hora", "HTTP {$r['status']} · " . ($r['h']['content-type'] ?? '') . ' · ' . ($r['h']['cache-control'] ?? ''));
        }
        $check(!preg_match('/^Disallow:\s*\/statics\/\s*$/m', $servidos['robots']) && str_contains($servidos['robots'], 'Disallow: /admin/'), 'a2 robots.txt cierra lo privado y NO cierra /statics/');
        $check(preg_match('/^Sitemap: https?:\/\/\S+\/sitemap\.xml$/m', $servidos['robots']) === 1 && str_contains($servidos['robots'], "Sitemap: {$base}/sitemap.xml"), 'a3 y nombra el sitemap con URL absoluta', $servidos['robots']);
        $propietario = (string) SettingsModel::getConfigValue(SettingsController::SEO_OPTION_OWNER, true);
        $check(str_contains($servidos['humans'], '/* TEAM */') && str_contains($servidos['humans'], "Owner: {$propietario}") && stripos($servidos['humans'], 'piecesphp') === false, 'a4 humans.txt con el equipo y el propietario, y sin nombrar el framework', $servidos['humans']);
        $titulo = (string) SettingsModel::getConfigValue(SettingsController::SEO_OPTION_TITLE_APP, true);
        $check(str_starts_with($servidos['llms'], "# {$titulo}\n") && str_contains($servidos['llms'], "\n> ") && str_contains($servidos['llms'], "]({$base}/)") && str_contains($servidos['llms'], "]({$base}/publications/list/)"), 'a5 llms.txt con título, descripción y las dos URL absolutas', $servidos['llms']);
        echoTerminal(' ');

        //─── B · Los añadidos, por la acción real ────────────────────────────────────────────────
        echoTerminal('[B] Lo que añade la instalación');
        $formulario = ['robots' => "# {$prefijo}\nDisallow: /{$prefijo}/", 'humans' => "/* THANKS */\n\t{$prefijo}", 'llms' => "## {$prefijo}\n\nTexto añadido."];
        $r = $pedir('POST', $rutas['accion'], $admin, $formulario);
        $check($r['status'] === 200 && ($r['json']['success'] ?? null) === true, 'b1 el administrador general guarda por la acción', "HTTP {$r['status']} " . mb_substr($r['body'], 0, 160));
        $robots = $pedir('GET', $rutas['robots'])['body'];
        $check(str_ends_with(rtrim($robots), "Disallow: /{$prefijo}/") && mb_strpos($robots, 'Sitemap:') < mb_strpos($robots, $prefijo), 'b2 el añadido de robots.txt sale al FINAL, después de la base', $robots);
        $check(str_contains($pedir('GET', $rutas['humans'])['body'], $prefijo) && str_contains($pedir('GET', $rutas['llms'])['body'], "## {$prefijo}"), 'b3 y los de humans.txt y llms.txt también');
        $check(preg_match('/Last update: \d{4}\/\d{2}\/\d{2}/', $pedir('GET', $rutas['humans'])['body']) === 1, 'b4 humans.txt dice la fecha del último cambio');
        $antes = $guardado();
        $mala = $pedir('POST', $rutas['accion'], $admin, ['robots' => "Disallow: /bien/\nNoindex: /mal/", 'humans' => 'no debe quedar', 'llms' => 'no debe quedar']);
        $check(($mala['json']['success'] ?? null) === false && $guardado() === $antes, 'b5 una línea inválida en robots.txt: rechazo y NADA escrito, tampoco los otros dos', mb_substr($mala['body'], 0, 200));
        $incompleta = $pedir('POST', $rutas['accion'], $admin, ['robots' => '']);
        $check(($incompleta['json']['success'] ?? null) === false && $guardado() === $antes, 'b6 un formulario sin los tres campos no vacía lo guardado');
        echoTerminal(' ');

        //─── C · Quién entra ─────────────────────────────────────────────────────────────────────
        echoTerminal('[C] El general y el visitante, fuera');
        $check($pedir('GET', $rutas['pantalla'], $general, [], true)['status'] === 403, 'c1 el general: 403 en la pantalla');
        $rg = $pedir('POST', $rutas['accion'], $general, $formulario, true);
        $check($rg['status'] === 403 && $guardado() === $antes, 'c2 el general: 403 en la acción, y nada escrito', "HTTP {$rg['status']}");
        $check($pedir('GET', $rutas['pantalla'], null, [], true)['status'] === 403, 'c3 el visitante: 403 en la pantalla');
        $rv = $pedir('POST', $rutas['accion'], null, $formulario, true);
        $check($rv['status'] === 403 && $guardado() === $antes, 'c4 el visitante: 403 en la acción, y nada escrito', "HTTP {$rv['status']}");
        $sinXHR = $pedir('GET', $rutas['pantalla']);
        echoTerminal("      MEDIDA: el visitante sin cabecera XHR en la pantalla recibe HTTP {$sinXHR['status']}");

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[Z] Limpieza');
        try {
            foreach ($previas as $opcion => [$existia, $valor]) {
                if ($existia) {
                    SettingsModel::setConfigValue($opcion, $valor);
                } else {
                    SettingsModel::model()->delete(['name' => $opcion])->execute();
                }
            }
            foreach ($ids as $id) {
                $aprobaciones = SystemApprovalsMapper::model();
                $aprobaciones->resetAll();
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, UsersModel::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            $usuarios = UsersModel::model();
            $usuarios->resetAll();
            $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
            echoTerminal('      - ' . count($ids) . " usuarios {$prefijo}-*; los tres añadidos como estaban");
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $opcionesBien = true;
        foreach ($previas as $opcion => [$existia, $valor]) {
            $opcionesBien = $opcionesBien && SettingsModel::optionExists($opcion) === $existia && json_encode(SettingsModel::getConfigValue($opcion)) === json_encode($valor);
        }
        $usuarios = UsersModel::model();
        $usuarios->resetAll();
        $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $check(count((array) $usuarios->result()) === 0 && $opcionesBien, 'z1 limpieza: 0 usuarios de la prueba y los añadidos como estaban');
    }

    return $balance();

})->setDescription('robots.txt, humans.txt y llms.txt por ruta: tipos, caché, base y añadidos; la pantalla solo para los roles de SEO; una línea inválida no escribe.')->setEffects([CliActions::EFFECT_NETWORK, CliActions::EFFECT_DATABASE])->register();
