<?php

//Las páginas de 404 y 403 salen con su código aunque pesen más que el búfer, el preflight con el suyo, y el idioma de la cabecera se valida.
//Crea un usuario zz-agente-errores-* y lo retira.
//@areas nucleo

use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/error-status', function ($args) {

    echoTerminal("\e[33m[TEST:ErrorStatus] El código de estado de las páginas de error y del preflight\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que backups-screen.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $check($base !== '', 'c1 hay una base HTTP para pedir', $base);
    $ruta = function (string $nombre): string {
        $url = get_route($nombre, [], true);
        if (!is_string($url) || $url === '') {
            return '';
        }
        return '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
    };
    $rutaSeo = (string) \PiecesPHP\Settings\Controllers\SettingsController::routeName('appearance-seo');
    $rutaSeo = str_contains($rutaSeo, '://') ? (string) parse_url($rutaSeo, \PHP_URL_PATH) : $rutaSeo;
    $rutaPrivada = $ruta('configurations-system-backups-save');
    $rutaCors = $ruta('publications-ajax-all');
    $check($rutaSeo !== '' && $rutaPrivada !== '' && $rutaCors !== '', 'c2 existen configurations-appearance-seo, configurations-system-backups-save y publications-ajax-all', "{$rutaSeo} · {$rutaPrivada} · {$rutaCors}");
    if ($failed > 0) {
        return $balance();
    }
    //La misma que vigila core/sitemap 4b: su página de 404 pasa de 4096 B, el búfer de salida del FPM local.
    $rutaInexistente = rtrim($rutaSeo, '/') . '/sitemap/';

    $cabeceraToken = SessionToken::headerName();
    $pedir = function (string $metodo, string $path, ?string $jwt = null, array $extra = []) use ($base, $cabeceraToken): array {
        $cabeceras = $jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [];
        foreach ($extra as $linea) {
            $cabeceras[] = $linea;
        }
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CUSTOMREQUEST => $metodo,
            CURLOPT_HTTPHEADER => $cabeceras,
        ]);
        if ($metodo === 'POST') {
            \Terminal\TestRun::curlSetopt($handle, CURLOPT_POSTFIELDS, http_build_query(['zz' => 'prueba']));
        }
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $body = is_string($body) ? $body : '';
        echoTerminal("      · {$metodo} {$path} → HTTP {$status}, " . strlen($body) . ' B');
        return ['status' => $status, 'body' => $body];
    };
    $paginaEntera = fn(string $body): bool => str_contains($body, 'id="error-page-title"') && str_contains($body, '</html>');

    $prefijo = 'zz-agente-errores-' . bin2hex(random_bytes(3));

    try {
        //─── a · El 404 ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] Una ruta inexistente de /admin/ da 404 con la página entera, sea cual sea el método');
        $get = $pedir('GET', $rutaInexistente);
        $check(strlen($get['body']) > 4096, 'a0 CANARIO: la página de 404 pesa más de 4096 B (si no, el búfer la tapa y la prueba no discrimina)', strlen($get['body']) . ' B');
        $check($get['status'] === 404 && $paginaEntera($get['body']), 'a1 GET: 404 y la página entera', "HTTP {$get['status']}");
        $post = $pedir('POST', $rutaInexistente);
        $check($post['status'] === 404 && $paginaEntera($post['body']), 'a2 DISCRIMINANTE: POST: 404 y la página entera', "HTTP {$post['status']}");
        $options = $pedir('OPTIONS', $rutaInexistente);
        //El contenedor `cors` da 204 a todo OPTIONS en modo API: 204 sin cuerpo, nunca 200.
        $check($options['status'] === (API_MODULE ? 204 : 404), 'a3 DISCRIMINANTE: OPTIONS sin cabeceras CORS: ' . (API_MODULE ? '204 (modo API)' : '404'), "HTTP {$options['status']}");

        //─── b · El 403 ─────────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] Una ruta del panel sin permiso da 403 con la página entera');
        $u = new UsersModel();
        $u->username = "{$prefijo}-admin";
        $u->email = "{$prefijo}-admin@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Agente';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ADMIN_GRAL;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $jwt = UserSessions::issueToken((int) $u->id);
        $prohibido = $pedir('POST', $rutaPrivada, $jwt);
        //Con sesión no lleva luna/sol y pesa menos de 4096 B: no discrimina el búfer; sin sesión, el panel redirige o da 404.
        $check($prohibido['status'] === 403 && $paginaEntera($prohibido['body']), 'b1 POST de un administrador general a la acción de respaldos: 403 y la página entera (' . strlen($prohibido['body']) . ' B)', "HTTP {$prohibido['status']}");

        //─── c · El preflight ───────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] El preflight de CORS a una ruta pública de datos');
        $preflight = $pedir('OPTIONS', $rutaCors, null, ['Origin: https://ajeno.test', 'Access-Control-Request-Method: GET']);
        $check($preflight['status'] === 204 && $preflight['body'] === '', 'c3 DISCRIMINANTE: 204 y sin cuerpo', "HTTP {$preflight['status']}, " . strlen($preflight['body']) . ' B');

        //─── d · El idioma de la cabecera ───────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[d] PCSPHP-Response-Expected-Language solo acepta un idioma de la lista; si no, el de por omisión');
        $porOmision = (string) \PiecesPHP\Core\Config::get_default_lang();
        $permitidos = \PiecesPHP\Core\Config::get_allowed_langs();
        $otro = array_values(array_diff($permitidos, [$porOmision]))[0] ?? '';
        $check($otro !== '', 'd0 canario: hay un idioma permitido distinto del de por omisión', implode(',', $permitidos));
        $ajeno = $pedir('GET', $rutaInexistente, null, ['PCSPHP-Response-Expected-Language: ' . $porOmision . '"><x']);
        $check(str_contains($ajeno['body'], '<html lang="' . $porOmision . '" dlang='), "d1 DISCRIMINANTE: un valor ajeno deja <html lang=\"{$porOmision}\">", (string) (preg_match('/<html lang="[^"]*"/', $ajeno['body'], $m) ? $m[0] : ''));
        $check($ajeno['body'] !== '' && !str_contains($ajeno['body'], '"><x'), 'd2 y el valor crudo no aparece en la página');
        if ($otro !== '') {
            $valido = $pedir('GET', $rutaInexistente, null, ["PCSPHP-Response-Expected-Language: {$otro}"]);
            $check(str_contains($valido['body'], '<html lang="' . $otro . '" dlang='), "d3 un idioma permitido ({$otro}) se respeta");
        }

    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    }

    //─── z · Limpieza ───────────────────────────────────────────────────────────────────────────
    echoTerminal(' ');
    echoTerminal('[z] Limpieza: el usuario de la prueba');
    try {
        $usuarios = UsersModel::model();
        $usuarios->resetAll();
        $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $deLaPrueba = array_map(fn($u) => (int) $u->id, (array) $usuarios->result());
        foreach ($deLaPrueba as $id) {
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando los usuarios que quedan.
            $perfiles->delete(['belongsTo' => $id])->execute();
            //`SystemApprovalManager::init()` anota cada usuario nuevo al servir: esa fila no cae con él.
            $aprobaciones = SystemApprovalsMapper::model();
            $aprobaciones->resetAll();
            $aprobaciones->delete(new WhereSegment([
                new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, UsersModel::TABLE),
                new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
            ]))->execute();
        }
        $usuarios->resetAll();
        //RETORNO-IGNORADO: lo comprueba z1 contando los usuarios que quedan.
        $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        echoTerminal('      - ' . count($deLaPrueba) . " usuario(s) {$prefijo}-*");
    } catch (\Throwable $e) {
        $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    }
    $usuarios = UsersModel::model();
    $usuarios->resetAll();
    $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
    $check(count((array) $usuarios->result()) === 0, 'z1 no queda ningún usuario de la prueba');

    return $balance();

})->setDescription('Las páginas de 404 y 403 salen con su código aunque pesen más que el búfer de salida (GET, POST y OPTIONS), el preflight de CORS con 204 y el idioma de la cabecera solo de la lista permitida.')->setEffects([CliActions::EFFECT_DATABASE])->register();
