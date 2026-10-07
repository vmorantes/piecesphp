<?php

//Quien no puede verlo todo y no tiene organización no ve ninguna publicación en el listado del panel: falla cerrada.
//Por HTTP. Siembra una organización, tres usuarios y una publicación, todos zz-, y los retira.

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

CliActions::make('unit-tests:core/publications-without-organization', function ($args) {

    echoTerminal("\e[33m[TEST:PublicationsWithoutOrganization] Sin organización, el listado de publicaciones sale vacío\e[39m");
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
    $camino = function (string $sufijo) use ($prefijoBase): string {
        $url = PublicationsController::routeName($sufijo, [], true);
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

    $prefijo = 'zz-prueba-publicaciones-sin-org-' . bin2hex(random_bytes(3));
    $tablaPublicaciones = PublicationMapper::TABLE;
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $tablaUsuarios = UsersModel::TABLE;
    $usuarios = [];
    $organizacion = 0;
    $publicacion = 0;

    try {
        $crearUsuario = function (string $sufijo, int $tipo, ?int $organizacion) use ($prefijo, &$usuarios): string {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            //Nadie entra con contraseña: el token lo fabrica la prueba.
            $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Publicaciones';
            $u->secondLastname = '';
            $u->type = $tipo;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            if ($organizacion !== null) {
                $u->organization = $organizacion;
            }
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            return SessionToken::generateToken(['id' => (int) $u->id], null, null, false);
        };
        $root = $crearUsuario('root', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL);
        $ahora = date('Y-m-d H:i:s');
        $database->prepare("INSERT INTO `{$tablaOrganizaciones}` (code, name, nit, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute(['zz' . bin2hex(random_bytes(4)), "{$prefijo}-organizacion", 'zz-', "{$prefijo}-organizacion", $ahora, $usuarios['root'], OrganizationMapper::ACTIVE, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
        $organizacion = (int) $database->lastInsertId();
        $conOrganizacion = $crearUsuario('con-organizacion', UsersModel::TYPE_USER_COMUNICACIONES, $organizacion);
        $sinOrganizacion = $crearUsuario('sin-organizacion', UsersModel::TYPE_USER_COMUNICACIONES, null);

        $categoria = $database->query('SELECT id FROM `' . \Publications\Mappers\PublicationCategoryMapper::TABLE . '` ORDER BY id LIMIT 1')->fetchColumn();
        if (!$check($categoria !== false, 'p2 hay una categoría de publicaciones en la que sembrar')) {
            return $balance();
        }
        //La crea el de la organización: su organizationID es esa, y es lo que el filtro compara.
        $database->prepare("INSERT INTO `{$tablaPublicaciones}` (title, content, seoDescription, author, category, mainImage, thumbImage, ogImage, folder, visits, publicDate, createdAt, createdBy, status, featured, meta)"
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, 0, ?)')
            ->execute(["{$prefijo}-publicacion", 'zz-', 'zz-', $usuarios['con-organizacion'], (int) $categoria, '', '', '', "{$prefijo}-publicacion", $ahora, $ahora, $usuarios['con-organizacion'], PublicationMapper::ACTIVE, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
        $publicacion = (int) $database->lastInsertId();

        $listado = $camino('datatables') . '?draw=1&start=0&length=100&search[value]=' . rawurlencode($prefijo);
        $filas = function (array $respuesta): ?int {
            $json = json_decode($respuesta['body'], true);
            return is_array($json) && is_array($json['data'] ?? null) ? count($json['data']) : null;
        };

        echoTerminal('[a] Los canarios: quien sí debe verla, la ve');
        $dePrincipal = $pedir($listado, $root);
        $check($dePrincipal['status'] === 200 && str_contains($dePrincipal['body'], "{$prefijo}-publicacion"), 'a1 CANARIO: el principal la ve', "HTTP {$dePrincipal['status']}");
        $deSuOrganizacion = $pedir($listado, $conOrganizacion);
        $check($deSuOrganizacion['status'] === 200 && str_contains($deSuOrganizacion['body'], "{$prefijo}-publicacion"), 'a2 CANARIO: el de su organización, del mismo tipo, la ve', "HTTP {$deSuOrganizacion['status']}");

        echoTerminal('');
        echoTerminal('[b] Sin organización');
        $deSinOrganizacion = $pedir($listado, $sinOrganizacion);
        $check($deSinOrganizacion['status'] === 200, 'b1 responde 200', "HTTP {$deSinOrganizacion['status']}");
        $check($filas($deSinOrganizacion) === 0, 'b2 y no trae ninguna fila: sin organización no hay de cuál ver', 'filas ' . var_export($filas($deSinOrganizacion), true));
        //Sin búsqueda: ni la sembrada ni ninguna de las que ya hubiera en la base.
        $todas = $pedir($camino('datatables') . '?draw=1&start=0&length=100', $sinOrganizacion);
        $check($filas($todas) === 0, 'b3 tampoco sin filtrar por la prueba: ninguna publicación de la base', 'filas ' . var_export($filas($todas), true));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ([[$tablaPublicaciones, $publicacion > 0 ? [$publicacion] : []], [$tablaOrganizaciones, $organizacion > 0 ? [$organizacion] : []], [$tablaUsuarios, $usuarios]] as [$tablaReferencia, $ids]) {
                foreach ($ids as $id) {
                    $aprobaciones->resetAll();
                    //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                    $aprobaciones->delete(new WhereSegment([
                        new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tablaReferencia),
                        new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                    ]))->execute();
                }
            }
            if ($publicacion > 0) {
                $database->exec("DELETE FROM `{$tablaPublicaciones}` WHERE id = {$publicacion}");
            }
            foreach ($usuarios as $id) {
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            //Las claves van en los dos sentidos (el usuario a su organización, la organización a su creador): los usuarios
            //pasan a la global, cae la organización y después los usuarios.
            $database->exec("UPDATE `{$tablaUsuarios}` SET organization = " . OrganizationMapper::INITIAL_ID_GLOBAL . ' WHERE username LIKE ' . $database->quote("{$prefijo}%"));
            if ($organizacion > 0) {
                $database->exec("DELETE FROM `{$tablaOrganizaciones}` WHERE id = {$organizacion}");
            }
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaPublicaciones}` WHERE title LIKE " . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name LIKE " . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ninguna publicación, organización ni usuario de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('Quien no puede ver todas las publicaciones y no tiene organización no ve ninguna en el listado del panel: falla cerrada. El principal y el de la organización sí la ven. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
