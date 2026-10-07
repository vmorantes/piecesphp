<?php

//Una publicación incompleta —sin langData en su meta— no tumba los listados del panel (el de DataTables y el JSON de
//todas): se lista con sus campos base. Por HTTP. Siembra dos publicaciones y un principal, todos zz-, y los retira.

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Publications\Controllers\PublicationsController;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/publications-incomplete', function ($args) {

    echoTerminal("\e[33m[TEST:PublicationsIncomplete] Una publicación incompleta no tumba los listados del panel\e[39m");
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
    if (!$check($base !== '' && $database !== null, 'c1 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }

    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::tokenName();
    $camino = function (string $sufijo) use ($prefijoBase): string {
        $url = PublicationsController::routeName($sufijo, [], true);
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, ?string $jwt) use ($base, $cabeceraToken): array {
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [],
        ]);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };

    $prefijo = 'zz-prueba-publicaciones-' . bin2hex(random_bytes(3));
    $tablaPublicaciones = PublicationMapper::TABLE;
    $usuario = 0;
    $publicaciones = [];

    try {
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Publicaciones';
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

        //La categoría por defecto lleva id negativo (-10): se acepta cualquiera que exista.
        $categoria = $database->query('SELECT id FROM `' . \Publications\Mappers\PublicationCategoryMapper::TABLE . '` ORDER BY id LIMIT 1')->fetchColumn();
        if (!$check($categoria !== false, 'c2 hay una categoría de publicaciones en la que sembrar')) {
            return $balance();
        }
        $sembrar = function (string $sufijo, array $meta) use ($database, $tablaPublicaciones, $prefijo, $usuario, $categoria, &$publicaciones): void {
            $ahora = date('Y-m-d H:i:s');
            $database->prepare("INSERT INTO `{$tablaPublicaciones}` (title, content, seoDescription, author, category, mainImage, thumbImage, ogImage, folder, visits, publicDate, createdAt, createdBy, status, featured, meta)"
                . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, 0, ?)')
                ->execute(["{$prefijo}-{$sufijo}", 'zz-', 'zz-', $usuario, (int) $categoria, '', '', '', "{$prefijo}-{$sufijo}", $ahora, $ahora, $usuario, PublicationMapper::ACTIVE, json_encode($meta)]);
            $publicaciones[$sufijo] = (int) $database->lastInsertId();
        };
        //La completa es el canario: si los listados dejaran de pintar publicaciones, la prueba de la incompleta pasaría sola.
        $sembrar('completa', ['baseLang' => 'es', 'langData' => new \stdClass]);
        $sembrar('incompleta', ['baseLang' => 'es']);

        echoTerminal('[a] El listado del panel (DataTables)');
        $tabla = $pedir($camino('datatables') . '?draw=1&start=0&length=100&search[value]=' . rawurlencode($prefijo), $root);
        $check($tabla['status'] === 200, 'a1 responde 200 con una publicación sin langData en la base', "HTTP {$tabla['status']}");
        $check(str_contains($tabla['body'], "{$prefijo}-completa"), 'a2 CANARIO: la completa está en el listado');
        $check(str_contains($tabla['body'], "{$prefijo}-incompleta"), 'a3 y la incompleta también: sin langData se lista con sus campos base');

        echoTerminal('');
        echoTerminal('[b] El JSON con todas');
        $todas = $pedir($camino('ajax-all') . '?page=1&perPage=100', $root);
        $check($todas['status'] === 200, 'b1 responde 200 con una publicación sin langData en la base', "HTTP {$todas['status']}");
        $check(str_contains($todas['body'], "{$prefijo}-completa"), 'b2 CANARIO: la completa está en el JSON');
        $check(str_contains($todas['body'], "{$prefijo}-incompleta"), 'b3 y la incompleta también');

        echoTerminal('');
        echoTerminal('[c] El JSON del listado PÚBLICO, sin sesión: delega en el mismo _all()');
        $urlPublica = \Publications\Controllers\PublicationsPublicController::routeName('ajax-all', [], true);
        $caminoPublico = str_contains($urlPublica, '://') ? (string) parse_url($urlPublica, \PHP_URL_PATH) : $urlPublica;
        $caminoPublico = $prefijoBase !== '' && str_starts_with($caminoPublico, $prefijoBase) ? substr($caminoPublico, strlen($prefijoBase)) : $caminoPublico;
        $publico = $pedir($caminoPublico . '?page=1&perPage=100', null);
        $check($publico['status'] === 200, 'c1 un visitante sin sesión recibe 200 con una publicación sin langData en la base', "HTTP {$publico['status']}");
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $aprobaciones = SystemApprovalsMapper::model();
            foreach (array_merge([[$tablaPublicaciones, $publicaciones]], [[UsersModel::TABLE, $usuario > 0 ? [$usuario] : []]]) as [$tablaReferencia, $ids]) {
                foreach ($ids as $id) {
                    $aprobaciones->resetAll();
                    //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                    $aprobaciones->delete(new WhereSegment([
                        new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tablaReferencia),
                        new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                    ]))->execute();
                }
            }
            foreach ($publicaciones as $id) {
                $database->exec("DELETE FROM `{$tablaPublicaciones}` WHERE id = " . (int) $id);
            }
            if ($usuario > 0) {
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
        $quedanPublicaciones = (int) $database->query("SELECT COUNT(*) FROM `{$tablaPublicaciones}` WHERE title LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $quedanAprobaciones = 0;
        foreach ($publicaciones as $id) {
            $quedanAprobaciones += (int) $database->query('SELECT COUNT(*) FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ' . $database->quote($tablaPublicaciones) . ' AND referenceValue = ' . $database->quote((string) $id))->fetchColumn();
        }
        $quedanUsuarios = UsersModel::model();
        $quedanUsuarios->resetAll();
        $quedanUsuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $check($quedanPublicaciones === 0 && $quedanAprobaciones === 0 && count((array) $quedanUsuarios->result()) === 0, 'z1 no queda ninguna publicación, aprobación ni usuario de la prueba', "publicaciones {$quedanPublicaciones}, aprobaciones {$quedanAprobaciones}");
    }

    return $balance();

})->setDescription('Una publicación sin langData en su meta no tumba el listado del panel ni el JSON de todas: se lista con sus campos base, como las completas. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
