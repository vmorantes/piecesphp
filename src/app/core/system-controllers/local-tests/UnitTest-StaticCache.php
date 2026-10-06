<?php

//La caché de los estáticos por HTTP (ADR 0034): un año con cacheStamp, no-cache sin él, una sola Cache-Control.
//Crea un principal zz-prueba-cache-* y lo retira.

use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/static-cache', function ($args) {

    echoTerminal("\e[33m[TEST:StaticCache] Un año para lo versionado, revalidación para lo demás, y una sola Cache-Control\e[39m");
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
    if ($base === '') {
        $matrixPath = dirname(rtrim(str_replace('\\', '/', basepath('')), '/')) . '/files/dev/permissions-matrix.json';
        $matrix = is_file($matrixPath) ? json_decode((string) file_get_contents($matrixPath), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    //Un enlace de server-delegated que ya existe, y la ruta PHP del mismo archivo.
    $enlaces = glob(basepath('statics/server-delegated/app/classes/Publications/Statics/css/*.css')) ?: [];
    $enlace = isset($enlaces[0]) && is_link($enlaces[0]) ? ltrim(mb_substr($enlaces[0], mb_strlen(basepath(''))), '/') : '';
    $panel = get_route('configurations-index', [], true);
    $panel = is_string($panel) && str_contains($panel, '://') ? (string) parse_url($panel, \PHP_URL_PATH) : (string) $panel;

    $fallosAntesDelCanario = $failed;
    echoTerminal('[canario] Lo que esta prueba da por hecho');
    $check($base !== '', 'c1 hay una base con la que pedir');
    $check(is_file(basepath('statics/core/css/ui-pcs.css')) && is_file(basepath('statics/images/favicon.png')), 'c2 existen statics/core/css/ui-pcs.css y statics/images/favicon.png');
    $check($enlace !== '', 'c3 hay un enlace de server-delegated de Publications', $enlace);
    $check(is_file(basepath('app/classes/Publications/Statics/css/publications.css.map')), 'c4 existe un estático de módulo que PHP sirve entero (.map no se delega)');
    $check($panel !== '' && $panel !== '/', 'c5 se resuelve la ruta del panel', $panel);
    if ($failed > $fallosAntesDelCanario) {
        return $balance();
    }
    echoTerminal(' ');

    $cabeceras = function (string $path, ?string $jwt = null) use ($base): array {
        $lineas = [];
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $jwt !== null ? [SessionToken::tokenName() . ": {$jwt}"] : [],
            CURLOPT_HEADERFUNCTION => function (\CurlHandle $h, string $linea) use (&$lineas): int {
                $partes = explode(':', $linea, 2);
                if (count($partes) === 2) {
                    $lineas[] = [mb_strtolower(trim($partes[0])), trim($partes[1])];
                }
                return strlen($linea);
            },
        ]);
        //RETORNO-IGNORADO: con CURLOPT_NOBODY no hay cuerpo; lo que se mide son las cabeceras, que recoge HEADERFUNCTION.
        curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $todas = fn(string $nombre): array => array_values(array_map(fn(array $l) => $l[1], array_filter($lineas, fn(array $l) => $l[0] === $nombre)));
        return ['status' => $status, 'cc' => $todas('cache-control'), 'location' => $todas('location')[0] ?? ''];
    };
    $anual = 'public, max-age=31536000, immutable';
    $casos = [
        'CSS de statics/core/css con cacheStamp' => ['statics/core/css/ui-pcs.css?cacheStamp=zzprueba', 200, $anual],
        'CSS de statics/core/css sin cacheStamp' => ['statics/core/css/ui-pcs.css', 200, 'no-cache'],
        'imagen de statics/images con cacheStamp' => ['statics/images/favicon.png?cacheStamp=zzprueba', 200, $anual],
        'imagen de statics/images sin cacheStamp' => ['statics/images/favicon.png', 200, 'no-cache'],
        'enlace de server-delegated con cacheStamp' => [$enlace . '?cacheStamp=zzprueba', 200, $anual],
        'enlace de server-delegated sin cacheStamp' => [$enlace, 200, 'no-cache'],
        'ruta PHP de un estático que PHP sirve entero, con cacheStamp' => ['admin/publications/statics/css/publications.css.map?cacheStamp=zzprueba', 200, $anual],
        'ruta PHP de un estático que PHP sirve entero, sin cacheStamp' => ['admin/publications/statics/css/publications.css.map', 200, 'no-cache'],
    ];

    echoTerminal('[1] Cada estático, su Cache-Control y UNA sola');
    foreach ($casos as $nombre => [$path, $estado, $esperado]) {
        $r = $cabeceras($path);
        $check($r['status'] === $estado && $r['cc'] === [$esperado], "1 {$nombre}: «{$esperado}»", "HTTP {$r['status']} · " . (string) json_encode($r['cc']));
    }
    echoTerminal(' ');

    echoTerminal('[2] La ruta PHP de un estático que se delega: redirige al enlace con su consulta, y con la regla');
    $rutaModulo = 'admin/publications/statics/' . mb_substr($enlace, mb_strlen('statics/server-delegated/app/classes/Publications/Statics/'));
    $r = $cabeceras($rutaModulo . '?cacheStamp=zzprueba');
    $check($r['status'] === 302 && $r['cc'] === [$anual], '2a con cacheStamp: 302, un año e immutable', "HTTP {$r['status']} · " . (string) json_encode($r['cc']));
    $check(str_ends_with($r['location'], $enlace . '?cacheStamp=zzprueba'), '2b y el enlace al que redirige conserva el cacheStamp', $r['location']);
    $r = $cabeceras($rutaModulo);
    $check($r['status'] === 302 && $r['cc'] === ['no-cache'], '2c sin cacheStamp: 302 y no-cache', "HTTP {$r['status']} · " . (string) json_encode($r['cc']));
    echoTerminal(' ');

    echoTerminal('[3] Las páginas no ganan la Cache-Control de los estáticos');
    $prefijo = 'zz-prueba-cache-' . bin2hex(random_bytes(3));
    $id = 0;
    try {
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Prueba';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ROOT;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $id = (int) $u->id;
        $esDeEstatico = fn(array $cc): bool => in_array($anual, $cc, true) || $cc === ['no-cache'];
        $portada = $cabeceras('/');
        $check($portada['status'] === 200 && count($portada['cc']) === 1 && !$esDeEstatico($portada['cc']), '3a la portada: su Cache-Control de página', (string) json_encode($portada['cc']));
        $pantalla = $cabeceras($panel, SessionToken::generateToken(['id' => $id], null, null, false));
        $check($pantalla['status'] === 200 && count($pantalla['cc']) === 1 && !$esDeEstatico($pantalla['cc']), '3b una pantalla del panel: la suya, tampoco la de los estáticos', "HTTP {$pantalla['status']} · " . (string) json_encode($pantalla['cc']));
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        if ($id > 0) {
            $aprobaciones = SystemApprovalsMapper::model();
            $aprobaciones->delete(new WhereSegment([
                new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, UsersModel::TABLE),
                new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
            ]))->execute();
            UserProfileMapper::model()->delete(['belongsTo' => $id])->execute();
        }
        $usuarios = UsersModel::model();
        $usuarios->resetAll();
        $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $usuarios->resetAll();
        $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $check(count((array) $usuarios->result()) === 0, 'z1 limpieza: 0 usuarios de la prueba');
    }

    return $balance();

})->setDescription('La caché de los estáticos por HTTP: un año con cacheStamp, no-cache sin él, una sola Cache-Control, y las páginas intactas.')->setEffects([CliActions::EFFECT_NETWORK, CliActions::EFFECT_DATABASE])->register();
