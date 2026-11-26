<?php

//P414 b: el encargado de una organización edita y borra en Documentos, Categorías y Tipos de documento lo que creó
//alguien de SU organización, y recibe 403 en lo de otra. Por HTTP. Crea usuarios y registros zz- y los retira.
//@areas src/app/classes/Documents/
//@areas src/app/classes/Forms/Categories/
//@areas src/app/classes/Forms/DocumentTypes/
//@areas src/app/classes/Organizations/
//@requires-module Documents\DocumentsRoutes
//@requires-module Forms\Categories\CategoriesRoutes
//@requires-module Forms\DocumentTypes\DocumentTypesRoutes
//@requires-module Organizations\OrganizationsRoutes

use Documents\Controllers\DocumentsController;
use Documents\Mappers\DocumentsMapper;
use Forms\Categories\Controllers\CategoriesController;
use Forms\Categories\Mappers\CategoriesMapper;
use Forms\DocumentTypes\Controllers\DocumentTypesController;
use Forms\DocumentTypes\Mappers\DocumentTypesMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UserSessionsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/org-admin-records', function ($args) {

    echoTerminal("\e[33m[TEST:OrgAdminRecords] El encargado de organización administra SU organización en Documentos y Formularios\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la recomprobación de editar.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    if (!$check($base !== '', 'c1 hay una base HTTP para pedir', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $camino = function (string $controlador, string $sufijo, array $parametros = []) use ($prefijoBase): string {
        $url = $controlador::routeName($sufijo, $parametros, true);
        $url = '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
        return $prefijoBase !== '' && str_starts_with($url, $prefijoBase) ? substr($url, strlen($prefijoBase)) : $url;
    };
    $cabeceraToken = SessionToken::headerName();
    $pedir = function (string $ruta, string $jwt, array $campos) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . $ruta,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($campos),
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ]);
        $body = curl_exec($handle);
        $datos = is_string($body) ? json_decode($body, true) : null;
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'success' => is_array($datos) ? ($datos['success'] ?? null) : null, 'body' => is_string($body) ? $body : ''];
    };
    $resumen = fn (array $r): string => "HTTP {$r['status']} · " . mb_substr(preg_replace('/\s+/', ' ', $r['body']) ?? '', 0, 160);

    $database = (new BaseModel())->getDatabase();
    if (!$check($database !== null, 'c2 hay conexión a la base')) {
        return $balance();
    }
    $sello = bin2hex(random_bytes(3));
    $prefijoUsuario = "zz-agente-jop-{$sello}";
    $prefijo = "zz-jop-{$sello}";
    $lang = Config::get_default_lang();
    $ahora = date('Y-m-d H:i:s');
    $meta = json_encode(['baseLang' => $lang, 'langData' => new \stdClass]);
    $huella = function (string $tabla, int $id) use ($database): string {
        $fila = $database->query("SELECT * FROM `{$tabla}` WHERE id = " . $id)->fetch(\PDO::FETCH_ASSOC);
        return is_array($fila) ? sha1((string) json_encode($fila)) : 'sin-fila';
    };
    $usuarios = [];
    $filas = [DocumentsMapper::TABLE => [], CategoriesMapper::TABLE => [], DocumentTypesMapper::TABLE => []];
    $organizaciones = [];
    $crearUsuario = function (string $sufijo, int $tipo, ?int $organizacion) use ($prefijoUsuario, &$usuarios): int {
        $u = new UsersModel();
        $u->username = "{$prefijoUsuario}-{$sufijo}";
        $u->email = "{$prefijoUsuario}-{$sufijo}@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Encargado';
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
        $usuarios[] = (int) $u->id;
        return (int) $u->id;
    };
    $crearOrganizacion = function (string $sufijo) use ($prefijo, &$organizaciones): int {
        $o = new OrganizationMapper();
        $o->name = "{$prefijo}-org-{$sufijo}";
        $o->nit = "{$prefijo}-nit-{$sufijo}";
        $o->folder = "{$prefijo}-{$sufijo}";
        $o->status = OrganizationMapper::ACTIVE;
        $o->save();
        $organizaciones[] = (int) $o->id;
        return (int) $o->id;
    };

    try {
        $orgA = $crearOrganizacion('a');
        $orgB = $crearOrganizacion('b');
        $ids = [
            'root' => $crearUsuario('root', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL),
            'encargado' => $crearUsuario('encargado-a', UsersModel::TYPE_USER_ADMIN_ORG, $orgA),
            'otroAdmin' => $crearUsuario('admin-a-no-encargado', UsersModel::TYPE_USER_ADMIN_ORG, $orgA),
            'a' => $crearUsuario('general-a', UsersModel::TYPE_USER_GENERAL, $orgA),
            'b' => $crearUsuario('general-b', UsersModel::TYPE_USER_GENERAL, $orgB),
        ];
        $modeloA = new OrganizationMapper($orgA);
        $modeloA->administrator = $ids['encargado'];
        $modeloA->update();
        //Un usuario general puesto de encargado (PROFILE_EDITOR lo admite): no administra lo de los demás (P414 b).
        $orgC = $crearOrganizacion('c');
        $ids['encargadoGeneral'] = $crearUsuario('encargado-general-c', UsersModel::TYPE_USER_GENERAL, $orgC);
        $ids['c'] = $crearUsuario('general-c', UsersModel::TYPE_USER_GENERAL, $orgC);
        $modeloC = new OrganizationMapper($orgC);
        $modeloC->administrator = $ids['encargadoGeneral'];
        $modeloC->update();
        $jwt = array_map(fn (int $id): string => UserSessions::issueToken($id), $ids);

        $insertar = function (string $tabla, string $columnaNombre, string $nombre, int $creador, array $extra = []) use ($database, $ahora, $meta, &$filas): int {
            $columnas = array_merge([$columnaNombre => $nombre, 'folder' => $nombre, 'status' => 1, 'createdAt' => $ahora, 'createdBy' => $creador, 'meta' => $meta], $extra);
            $marcadores = implode(', ', array_fill(0, count($columnas), '?'));
            $database->prepare("INSERT INTO `{$tabla}` (`" . implode('`, `', array_keys($columnas)) . "`) VALUES ({$marcadores})")->execute(array_values($columnas));
            $id = (int) $database->lastInsertId();
            $filas[$tabla][] = $id;
            return $id;
        };
        $tipoBase = $insertar(DocumentTypesMapper::TABLE, 'documentTypeName', "{$prefijo}-tipo-base", $ids['root']);
        $extraDocumento = ['documentType' => $tipoBase, 'description' => '', 'document' => "{$prefijo}/sin-archivo.pdf", 'documentImage' => ''];
        $modulos = [
            'Documents' => [DocumentsController::class, DocumentsMapper::TABLE, 'documentName', $extraDocumento,
                fn (int $id, string $nombre): array => ['id' => $id, 'lang' => $lang, 'documentType' => $tipoBase, 'documentName' => $nombre, 'description' => '']],
            'Categories' => [CategoriesController::class, CategoriesMapper::TABLE, 'categoryName', [],
                fn (int $id, string $nombre): array => ['id' => $id, 'lang' => $lang, 'categoryName' => $nombre]],
            'DocumentTypes' => [DocumentTypesController::class, DocumentTypesMapper::TABLE, 'documentTypeName', [],
                fn (int $id, string $nombre): array => ['id' => $id, 'lang' => $lang, 'documentTypeName' => $nombre]],
        ];

        foreach ($modulos as $modulo => [$controlador, $tabla, $columnaNombre, $extra, $campos]) {
            echoTerminal('');
            echoTerminal("[{$modulo}]");
            $deA = $insertar($tabla, $columnaNombre, "{$prefijo}-{$modulo}-a", $ids['a'], $extra);
            $deB = $insertar($tabla, $columnaNombre, "{$prefijo}-{$modulo}-b", $ids['b'], $extra);
            $editar = $camino($controlador, 'actions-edit');

            $antes = $huella($tabla, $deB);
            $r = $pedir($editar, $jwt['encargado'], $campos($deB, "{$prefijo}-probe"));
            $check($r['status'] === 403 && $huella($tabla, $deB) === $antes, "{$modulo} 1 el encargado de A edita lo de alguien de B → 403 y queda intacto", $resumen($r));
            $r = $pedir($camino($controlador, 'actions-delete', ['id' => $deB]), $jwt['encargado'], ['id' => $deB]);
            $check($r['status'] === 403 && $huella($tabla, $deB) === $antes, "{$modulo} 2 y lo borra → 403 y queda intacto", $resumen($r));

            $antes = $huella($tabla, $deA);
            $r = $pedir($editar, $jwt['otroAdmin'], $campos($deA, "{$prefijo}-probe"));
            $check($r['status'] === 403 && $huella($tabla, $deA) === $antes, "{$modulo} 3 un administrador de A que no es su encargado edita lo de A → 403 (la regla de Publicaciones)", $resumen($r));

            $deC = $insertar($tabla, $columnaNombre, "{$prefijo}-{$modulo}-c", $ids['c'], $extra);
            $antesC = $huella($tabla, $deC);
            $r = $pedir($editar, $jwt['encargadoGeneral'], $campos($deC, "{$prefijo}-probe"));
            $check($r['status'] === 403 && $huella($tabla, $deC) === $antesC, "{$modulo} 3b un usuario general encargado de C edita lo de alguien de C → 403", $resumen($r));

            $r = $pedir($editar, $jwt['encargado'], $campos($deA, "{$prefijo}-{$modulo}-a-por-encargado"));
            $check($r['status'] === 200 && $r['success'] === true && $huella($tabla, $deA) !== $antes, "{$modulo} 4 el encargado de A edita lo de alguien de A", $resumen($r));
            $antes = $huella($tabla, $deA);
            $r = $pedir($camino($controlador, 'actions-delete', ['id' => $deA]), $jwt['encargado'], ['id' => $deA]);
            $check($r['status'] === 200 && $r['success'] === true && $huella($tabla, $deA) !== $antes, "{$modulo} 5 y lo borra", $resumen($r));

            $antes = $huella($tabla, $deB);
            $r = $pedir($editar, $jwt['root'], $campos($deB, "{$prefijo}-{$modulo}-b-por-root"));
            $check($r['status'] === 200 && $r['success'] === true && $huella($tabla, $deB) !== $antes, "{$modulo} 6 root sigue editando lo de cualquiera", $resumen($r));
        }

    } catch (\Throwable $e) {
        $check(false, 'c0 la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @ ' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        echoTerminal('');
        echoTerminal('[z] Limpieza');
        try {
            $aprobaciones = SystemApprovalsMapper::model();
            //Los documentos antes que los tipos: su clave foránea apunta al tipo.
            $todas = [DocumentsMapper::TABLE => $filas[DocumentsMapper::TABLE], CategoriesMapper::TABLE => $filas[CategoriesMapper::TABLE], DocumentTypesMapper::TABLE => $filas[DocumentTypesMapper::TABLE], OrganizationMapper::TABLE => $organizaciones, UsersModel::TABLE => $usuarios];
            foreach ($todas as $tabla => $listaIds) {
                foreach ($listaIds as $id) {
                    $aprobaciones->resetAll();
                    //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                    $aprobaciones->delete(new WhereSegment([
                        new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tabla),
                        new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                    ]))->execute();
                    if ($tabla !== UsersModel::TABLE && $tabla !== OrganizationMapper::TABLE) {
                        $database->prepare("DELETE FROM `{$tabla}` WHERE id = ?")->execute([$id]);
                    }
                }
            }
            //Quien guarda queda en `modifiedBy` de la organización, con clave foránea al usuario: se suelta antes.
            foreach ($organizaciones as $id) {
                $database->prepare('UPDATE `' . OrganizationMapper::TABLE . '` SET modifiedBy = NULL WHERE id = ?')->execute([$id]);
            }
            foreach ($usuarios as $id) {
                //issueToken() abre una sesión por usuario, y la tabla no tiene clave foránea que la arrastre.
                $database->prepare('DELETE FROM `' . UserSessionsModel::TABLE . '` WHERE userID = ?')->execute([$id]);
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            $usuariosModelo = UsersModel::model();
            $usuariosModelo->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $usuariosModelo->delete(new WhereSegment([WhereItem::like('username', "{$prefijoUsuario}%")]))->execute();
            foreach ($organizaciones as $id) {
                $database->prepare('DELETE FROM `' . OrganizationMapper::TABLE . '` WHERE id = ?')->execute([$id]);
            }
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $restos = 0;
        foreach (array_merge($filas, [OrganizationMapper::TABLE => $organizaciones]) as $tabla => $listaIds) {
            foreach ($listaIds as $id) {
                $restos += (int) $database->query("SELECT COUNT(*) FROM `{$tabla}` WHERE id = " . (int) $id)->fetchColumn();
            }
        }
        $restos += (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijoUsuario}%"))->fetchColumn();
        foreach ($usuarios as $id) {
            $restos += (int) $database->query('SELECT COUNT(*) FROM `' . UserSessionsModel::TABLE . '` WHERE userID = ' . (int) $id)->fetchColumn();
        }
        $check($restos === 0, 'z1 no queda ninguna fila de la prueba (sesiones incluidas)', "quedan {$restos}");
    }

    return $balance();

})->setDescription('P414 b: el encargado de una organización edita y borra en Documentos, Categorías y Tipos de documento lo de su organización; lo de otra, y un administrador que no es el encargado, 403; root sigue editándolo todo. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
