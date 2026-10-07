<?php

//Una noticia o una categoría sin langData en su meta no tumba los listados del panel: se listan con sus campos base.
//Por HTTP. Siembra dos categorías, dos noticias y un principal, todos zz-, y los retira.

use Organizations\Mappers\OrganizationMapper;
use News\Controllers\NewsCategoryController;
use News\Controllers\NewsController;
use News\Mappers\NewsCategoryMapper;
use News\Mappers\NewsMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/news-incomplete', function ($args) {

    echoTerminal("\e[33m[TEST:NewsIncomplete] Una noticia o categoría incompleta no tumba los listados del panel\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la pantalla de respaldos.
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
    $cabeceraToken = SessionToken::tokenName();
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, string $jwt) use ($base, $cabeceraToken): array {
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ]);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };

    $prefijo = 'zz-prueba-noticias-' . bin2hex(random_bytes(3));
    $tablaNoticias = NewsMapper::TABLE;
    $tablaCategorias = NewsCategoryMapper::TABLE;
    $usuario = 0;
    $noticias = [];
    $categorias = [];

    try {
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Noticias';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ROOT;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuario = (int) $u->id;
        $root = SessionToken::generateToken(['id' => $usuario], null, null, false);

        //Lo completo es el canario: si los listados dejaran de pintar filas, la prueba de lo incompleto pasaría sola.
        foreach (['completa' => ['baseLang' => 'es', 'langData' => new \stdClass], 'incompleta' => ['baseLang' => 'es']] as $sufijo => $meta) {
            $database->prepare("INSERT INTO `{$tablaCategorias}` (name, iconImage, color, meta) VALUES (?, ?, ?, ?)")
                ->execute(["{$prefijo}-categoria-{$sufijo}", '', '#000000', json_encode($meta)]);
            $categorias[$sufijo] = (int) $database->lastInsertId();
        }
        $ahora = date('Y-m-d H:i:s');
        //`draft` va en las dos: sin él, el JSON de todas filtra la fila (`draft = 0`) y la prueba no vería nada.
        foreach (['completa' => ['baseLang' => 'es', 'draft' => 0, 'langData' => new \stdClass], 'incompleta' => ['baseLang' => 'es', 'draft' => 0]] as $sufijo => $meta) {
            $database->prepare("INSERT INTO `{$tablaNoticias}` (newsTitle, profilesTarget, content, category, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute(["{$prefijo}-noticia-{$sufijo}", '[]', 'zz-', $categorias['completa'], "{$prefijo}-{$sufijo}", $ahora, $usuario, NewsMapper::ACTIVE, json_encode($meta)]);
            $noticias[$sufijo] = (int) $database->lastInsertId();
        }

        $listados = [
            'a' => ['noticias, DataTables', NewsController::routeName('datatables', [], true) . '?draw=1&start=0&length=100&search[value]=' . rawurlencode($prefijo), 'noticia'],
            'b' => ['noticias, JSON de todas', NewsController::routeName('ajax-all', [], true) . "?page=1&per_page=100&category={$categorias['completa']}", 'noticia'],
            'c' => ['categorías, DataTables', NewsCategoryController::routeName('datatables', [], true) . '?draw=1&start=0&length=100&search[value]=' . rawurlencode($prefijo), 'categoria'],
            'd' => ['categorías, JSON de todas', NewsCategoryController::routeName('ajax-all', [], true) . '?page=1&per_page=1000', 'categoria'],
        ];
        foreach ($listados as $letra => [$que, $url, $cosa]) {
            echoTerminal('');
            echoTerminal("[{$letra}] El listado de {$que}");
            $respuesta = $pedir($camino($url), $root);
            $check($respuesta['status'] === 200, "{$letra}1 responde 200 con una fila sin langData en la base", "HTTP {$respuesta['status']}");
            $check(str_contains($respuesta['body'], "{$prefijo}-{$cosa}-completa"), "{$letra}2 CANARIO: la completa está en el listado");
            $check(str_contains($respuesta['body'], "{$prefijo}-{$cosa}-incompleta"), "{$letra}3 y la incompleta también: sin langData se lista con sus campos base");
        }
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            foreach ($noticias as $id) {
                $database->exec("DELETE FROM `{$tablaNoticias}` WHERE id = " . (int) $id);
            }
            foreach ($categorias as $id) {
                $database->exec("DELETE FROM `{$tablaCategorias}` WHERE id = " . (int) $id);
            }
            if ($usuario > 0) {
                $aprobaciones = SystemApprovalsMapper::model();
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, UsersModel::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $usuario, WhereItem::AND_OPERATOR),
                ]))->execute();
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $usuario])->execute();
            }
            $usuarios = UsersModel::model();
            $usuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedanNoticias = (int) $database->query("SELECT COUNT(*) FROM `{$tablaNoticias}` WHERE newsTitle LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $quedanCategorias = (int) $database->query("SELECT COUNT(*) FROM `{$tablaCategorias}` WHERE name LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $quedanUsuarios = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedanNoticias + $quedanCategorias + $quedanUsuarios === 0, 'z1 no queda ninguna noticia, categoría ni usuario de la prueba', "noticias {$quedanNoticias}, categorías {$quedanCategorias}, usuarios {$quedanUsuarios}");
    }

    return $balance();

})->setDescription('Una noticia o una categoría de noticias sin langData en su meta no tumba los listados del panel (DataTables y JSON de todas): se listan con sus campos base. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
