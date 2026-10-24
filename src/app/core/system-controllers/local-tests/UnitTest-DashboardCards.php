<?php

//El inicio del panel con tarjetas (C5, FY): el registro, DashboardCardBody, la ayuda vacía sin icono y lo plegado (la de
//errores nace plegada y, plegada, no calcula ni enseña cifras pero sí su pie). En proceso y por HTTP.

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\AdminPanel\Controllers\HelperController;
use PiecesPHP\AdminPanel\DashboardCard;
use PiecesPHP\AdminPanel\DashboardCardBody;
use PiecesPHP\AdminPanel\DashboardCardRegistry;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;

CliActions::make('unit-tests:core/dashboard-cards', function ($args) {

    echoTerminal("\e[33m[TEST:DashboardCards] Las tarjetas del inicio: el registro, lo plegado por usuario y la de errores plegada sin cifras\e[39m");
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

    $database = (new BaseModel())->getDatabase();
    $prefijo = 'zz-prueba-inicio-' . bin2hex(random_bytes(3));
    $ids = [];
    $crear = function (string $sufijo, int $tipo) use ($prefijo, &$ids): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.invalid";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = $sufijo;
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $ids[] = (int) $u->id;
        return (int) $u->id;
    };

    try {
        //──── [r] El registro, en memoria y con lo de siempre repuesto al final ──────────────
        echoTerminal('[r] El registro');
        $originales = DashboardCardRegistry::swapForTesting();
        try {
            $llamadas = 0;
            $contenido = function (UserDataPackage $u) use (&$llamadas): DashboardCardBody {
                $llamadas++;
                return DashboardCardBody::numbers([['value' => 42, 'concept' => 'zz-cifra']]);
            };
            DashboardCardRegistry::register(new DashboardCard('zz-a', 'A', 'star', 'admin', $contenido, 20));
            DashboardCardRegistry::register(new DashboardCard('zz-b', 'B', 'star', 'admin-cropper-testing', $contenido, 10));
            $check(array_keys(DashboardCardRegistry::all()) === ['zz-b', 'zz-a'], 'r1 registra y ordena por posición');
            $lanza = false;
            try {
                DashboardCardRegistry::register(new DashboardCard('zz-a', 'A otra', 'star', 'admin', $contenido));
            } catch (\InvalidArgumentException $e) {
                $lanza = true;
            }
            $check($lanza, 'r2 una clave repetida lanza');
            DashboardCardRegistry::replace(new DashboardCard('zz-a', 'A sustituida', 'star', 'admin', $contenido, 20));
            $check(DashboardCardRegistry::get('zz-a')?->title() === 'A sustituida', 'r3 replace() sustituye la de esa clave');
            $quitada = DashboardCardRegistry::remove('zz-b');
            $yaNoEsta = DashboardCardRegistry::get('zz-b') === null;
            $otraVez = DashboardCardRegistry::remove('zz-b');
            $check($quitada && $yaNoEsta && !$otraVez, 'r4 remove() la quita, y quitarla otra vez dice false');
            DashboardCardRegistry::register(new DashboardCard('zz-b', 'B', 'star', 'admin-cropper-testing', $contenido, 10));
            $check(array_key_exists('zz-b', DashboardCardRegistry::visibleFor(UsersModel::TYPE_USER_ROOT)) && !array_key_exists('zz-b', DashboardCardRegistry::visibleFor(UsersModel::TYPE_USER_GENERAL)), 'r5 visibleFor() mira el permiso de su ruta: admin-cropper-testing solo para root');
            $lanzaPlegada = false;
            try {
                new DashboardCard('zz-c', 'C', 'star', 'admin', $contenido, 100, false, true);
            } catch (\InvalidArgumentException $e) {
                $lanzaPlegada = true;
            }
            $check($lanzaPlegada, 'r6 una tarjeta no puede nacer plegada sin poder plegarse');
            //Como la escribiría un clon con la API de antes: HTML en vez de un DashboardCardBody. Por reflexión, que es lo que hace
            //un clon sin tipos: el análisis estático no puede frenarla, y la tarjeta tiene que hacerlo al pintar.
            $vieja = (new \ReflectionClass(DashboardCard::class))->newInstanceArgs(['zz-d', 'D', 'star', 'admin', fn(UserDataPackage $u): string => '<p>html de antes</p>']);
            $lanzaVieja = false;
            try {
                $vieja->content(new UserDataPackage($crear('vieja', UsersModel::TYPE_USER_GENERAL)));
            } catch (\UnexpectedValueException $e) {
                $lanzaVieja = true;
            }
            $check($lanzaVieja, 'r7 una función de contenido que devuelve HTML (la API de antes) lanza, no se pinta');
        } finally {
            DashboardCardRegistry::swapForTesting($originales);
        }
        echoTerminal(' ');

        //──── [b] El cuerpo y la pieza ────────────────────────────────────────────────────────
        echoTerminal('[b] DashboardCardBody y statistic-card.php');
        $lanzaCero = false;
        try {
            DashboardCardBody::numbers([]);
        } catch (\InvalidArgumentException $e) {
            $lanzaCero = true;
        }
        $cifra = ['value' => 1, 'concept' => 'zz'];
        $lanzaCuatro = false;
        try {
            DashboardCardBody::numbers([$cifra, $cifra, $cifra, $cifra]);
        } catch (\InvalidArgumentException $e) {
            $lanzaCuatro = true;
        }
        $tres = DashboardCardBody::numbers([$cifra, $cifra, $cifra]);
        $check($lanzaCero && $lanzaCuatro && count($tres->items()) === 3, 'b1 numbers() admite de una a tres cifras: cero y cuatro lanzan, tres no');
        $vista = new HelperController();
        $conAyuda = (string) $vista->_render('panel/built-in/statistics/statistic-card.php', ['title' => 'zz', 'help' => 'zz-ayuda'], false, false);
        $sinAyuda = (string) $vista->_render('panel/built-in/statistics/statistic-card.php', ['title' => 'zz', 'help' => ''], false, false);
        $check(str_contains($conAyuda, 'class="help"') && str_contains($conAyuda, 'zz-ayuda') && !str_contains($sinAyuda, 'class="help"'), 'b2 con ayuda se pinta su icono; con la ayuda vacía, no');
        $accionSin = DashboardCardBody::action('zz-url', 'zz-frase');
        $accionCon = DashboardCardBody::action('zz-url', 'zz-frase', 'zz-pie');
        $check($accionSin->footerCaption() === null && $accionCon->footerCaption() === 'zz-pie', 'b3 action() guarda el texto del pie si se le da, y sin él queda null');
        $tarjetaSin = (string) $vista->_render('panel/built-in/statistics/action-card.php', ['url' => 'zz-url', 'title' => 'zz', 'caption' => $accionSin->footerCaption()], false, false);
        $tarjetaCon = (string) $vista->_render('panel/built-in/statistics/action-card.php', ['url' => 'zz-url', 'title' => 'zz', 'caption' => $accionCon->footerCaption()], false, false);
        $check(!str_contains($tarjetaSin, 'class="caption"') && str_contains($tarjetaCon, 'class="caption"') && str_contains($tarjetaCon, 'zz-pie') && str_contains($tarjetaCon, 'arrow right icon'), 'b4 action-card.php pinta el texto del pie junto a la flecha, y sin texto la flecha sola');
        echoTerminal(' ');

        //──── [e] La de errores, de verdad ───────────────────────────────────────────────────
        echoTerminal('[e] «Errores y avisos»: nace plegada y, plegada, no enseña cifras');
        $errores = DashboardCardRegistry::get('errors-alerts');
        $check($errores !== null && $errores->collapsible() && $errores->collapsedByDefault(), 'e1 la registra su módulo, plegable y plegada por omisión');
        $root = $crear('root', UsersModel::TYPE_USER_ROOT);
        $check($errores !== null && DashboardCardRegistry::isCollapsed($errores, $root), 'e2 un usuario que nunca eligió la ve plegada');

        $base = rtrim((string) (getenv('PCSPHP_WALK_BASE') ?: ''), '/');
        $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
        if ($base === '') {
            $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
            $base = rtrim(is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '', '/');
        }
        $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
        $inicio = (string) parse_url(AdminPanelController::routeName('', [], true), \PHP_URL_PATH);
        $inicio = $prefijoBase !== '' && str_starts_with($inicio, $prefijoBase) ? substr($inicio, strlen($prefijoBase)) : $inicio;
        $jwt = UserSessions::issueToken($root);
        $pedir = function () use ($base, $inicio, $jwt): array {
            $h = \Terminal\TestRun::curlInit();
            \Terminal\TestRun::curlSetoptArray($h, [CURLOPT_URL => $base . '/' . ltrim($inicio, '/'), CURLOPT_RETURNTRANSFER => true, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => 0, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => [SessionToken::tokenName() . ": {$jwt}"]]);
            $body = curl_exec($h);
            //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
            return ['status' => (int) curl_getinfo($h, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
        };
        //Solo el bloque de la tarjeta: el aviso global del pie dice la misma frase («Hay N errores nuevos…»).
        $tarjeta = function (string $html): string {
            $inicio = strpos($html, 'data-dashboard-card="errors-alerts"');
            if ($inicio === false) {
                return '';
            }
            $fin = strpos($html, 'data-dashboard-card="', $inicio + 10);
            return substr($html, $inicio, $fin !== false ? $fin - $inicio : 4000);
        };
        $r = $pedir();
        $bloque = $tarjeta($r['body']);
        $check($r['status'] === 200 && $bloque !== '' && !str_contains($bloque, 'class="numbers-cards"'), 'e3 el inicio de root pinta la tarjeta de errores plegada, sin ninguna cifra', "HTTP {$r['status']}");
        $check(str_contains($bloque, 'class="footer') && str_contains($bloque, 'data-dashboard-card-toggle="errors-alerts"') && str_contains($bloque, 'class="help"'), 'e3b plegada pinta su barra (botón y ayuda) y su pie con el enlace');
        $check(DashboardCardRegistry::setCollapsed('errors-alerts', $root, false), 'e4 se despliega para ese usuario');
        $r = $pedir();
        $check($r['status'] === 200 && str_contains($tarjeta($r['body']), 'class="numbers-cards"'), 'e5 CANARIO: desplegada, sí enseña sus cifras', "HTTP {$r['status']}");
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($ids as $id) {
            foreach ($referencias as [$tabla, $columna]) {
                $database->prepare("DELETE FROM `{$tabla}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('Las tarjetas del inicio (C5, FY): registrar, clave repetida, quitar, sustituir, visibles por permiso, de una a tres cifras, la ayuda vacía sin icono, y la de errores nace plegada y plegada no enseña cifras pero sí su pie. En proceso y por HTTP; siembra zz- y lo retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
