<?php

//Editar exige sobre el registro que se GUARDA lo mismo que la puerta exige sobre el que mira: ni un `?id=` propio en la
//dirección ni un cuerpo JSON sin `?id` dejan guardar uno ajeno. Por HTTP. Crea usuarios y registros, zz-, y los retira.
//@areas src/app/classes/Documents/
//@areas src/app/classes/Forms/Categories/
//@areas src/app/classes/Forms/DocumentTypes/
//@areas src/app/classes/Organizations/
//@requires-module Documents\DocumentsRoutes
//@requires-module Forms\Categories\CategoriesRoutes
//@requires-module Forms\DocumentTypes\DocumentTypesRoutes
//@requires-module Organizations\OrganizationsRoutes
//@requires-module SystemApprovals\SystemApprovalsRoutes

use Documents\Controllers\DocumentsController;
use Documents\Mappers\DocumentsMapper;
use Forms\Categories\Controllers\CategoriesController;
use Forms\Categories\Mappers\CategoriesMapper;
use Forms\DocumentTypes\Controllers\DocumentTypesController;
use Forms\DocumentTypes\Mappers\DocumentTypesMapper;
use Organizations\Controllers\MyOrganizationProfileController;
use Organizations\Controllers\OrganizationsController;
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

CliActions::make('unit-tests:core/edit-recheck', function ($args) {

    echoTerminal("\e[33m[TEST:EditRecheck] Editar vuelve a comprobar el permiso sobre el registro que se guarda\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que «Mi organización».
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
    $camino = function (string $controlador, string $sufijo) use ($prefijoBase): string {
        $url = $controlador::routeName($sufijo, [], true);
        $url = '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
        return $prefijoBase !== '' && str_starts_with($url, $prefijoBase) ? substr($url, strlen($prefijoBase)) : $url;
    };
    $cabeceraToken = SessionToken::headerName();
    //Un POST de formulario, o JSON (entonces `$_POST` llega vacío y solo el cuerpo analizado trae el id).
    $pedir = function (string $ruta, string $jwt, array $campos, array $query = [], bool $json = false) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $cabeceras = ["{$cabeceraToken}: {$jwt}"];
        if ($json) {
            $cabeceras[] = 'Content-Type: application/json';
        }
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . $ruta . ($query !== [] ? '?' . http_build_query($query) : ''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json ? (string) json_encode($campos) : http_build_query($campos),
            CURLOPT_HTTPHEADER => $cabeceras,
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
    $prefijoUsuario = "zz-agente-idor-{$sello}";
    $prefijo = "zz-idor-{$sello}";
    $lang = Config::get_default_lang();
    $ahora = date('Y-m-d H:i:s');
    $meta = json_encode(['baseLang' => $lang, 'langData' => new \stdClass]);
    //La huella de la fila entera: si la acción la guardó, cambian el nombre, `updatedAt` o `modifiedBy`.
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
        $u->firstLastname = 'Recomprobación';
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
        $ids = [];
        $ids['root'] = $crearUsuario('root', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL);
        $ids['a'] = $crearUsuario('a', UsersModel::TYPE_USER_GENERAL, null);
        $ids['b'] = $crearUsuario('b', UsersModel::TYPE_USER_GENERAL, null);
        $jwt = array_map(fn (int $id): string => UserSessions::issueToken($id), $ids);

        //Un registro de A y otro de B en cada módulo; los documentos cuelgan de un tipo de root.
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
            'Documents' => [
                'controlador' => DocumentsController::class,
                'tabla' => DocumentsMapper::TABLE,
                'a' => $insertar(DocumentsMapper::TABLE, 'documentName', "{$prefijo}-doc-a", $ids['a'], $extraDocumento),
                'b' => $insertar(DocumentsMapper::TABLE, 'documentName', "{$prefijo}-doc-b", $ids['b'], $extraDocumento),
                'campos' => fn (int $id, string $nombre): array => ['id' => $id, 'lang' => $lang, 'documentType' => $tipoBase, 'documentName' => $nombre, 'description' => ''],
            ],
            'Categories' => [
                'controlador' => CategoriesController::class,
                'tabla' => CategoriesMapper::TABLE,
                'a' => $insertar(CategoriesMapper::TABLE, 'categoryName', "{$prefijo}-cat-a", $ids['a']),
                'b' => $insertar(CategoriesMapper::TABLE, 'categoryName', "{$prefijo}-cat-b", $ids['b']),
                'campos' => fn (int $id, string $nombre): array => ['id' => $id, 'lang' => $lang, 'categoryName' => $nombre],
            ],
            'DocumentTypes' => [
                'controlador' => DocumentTypesController::class,
                'tabla' => DocumentTypesMapper::TABLE,
                'a' => $insertar(DocumentTypesMapper::TABLE, 'documentTypeName', "{$prefijo}-tipo-a", $ids['a']),
                'b' => $insertar(DocumentTypesMapper::TABLE, 'documentTypeName', "{$prefijo}-tipo-b", $ids['b']),
                'campos' => fn (int $id, string $nombre): array => ['id' => $id, 'lang' => $lang, 'documentTypeName' => $nombre],
            ],
        ];

        //─── a · B no guarda lo de A; B guarda lo suyo; quien puede editarlo todo guarda lo de A ──────
        foreach ($modulos as $modulo => $m) {
            echoTerminal('');
            echoTerminal("[{$modulo}]");
            $ruta = $camino($m['controlador'], 'actions-edit');
            $antes = $huella($m['tabla'], $m['a']);
            //(i) La dirección señala lo propio; el cuerpo, lo ajeno.
            $r = $pedir($ruta, $jwt['b'], ($m['campos'])($m['a'], "{$prefijo}-probe"), ['id' => $m['b']]);
            $check($r['status'] === 403, "{$modulo} i: `?id=` propio y cuerpo ajeno → 403", $resumen($r));
            $check($huella($m['tabla'], $m['a']) === $antes, "{$modulo} i: el registro de A sigue intacto");
            //(ii) Cuerpo JSON y sin `?id`: la puerta no encuentra el id en `$_GET` ni en `$_POST`.
            $r = $pedir($ruta, $jwt['b'], ($m['campos'])($m['a'], "{$prefijo}-probe"), [], true);
            $check($r['status'] === 403, "{$modulo} ii: cuerpo JSON ajeno sin `?id` → 403", $resumen($r));
            $check($huella($m['tabla'], $m['a']) === $antes, "{$modulo} ii: el registro de A sigue intacto");
            //Lo legítimo.
            $antesB = $huella($m['tabla'], $m['b']);
            $r = $pedir($ruta, $jwt['b'], ($m['campos'])($m['b'], "{$prefijo}-b-editado"));
            $check($r['status'] === 200 && $r['success'] === true && $huella($m['tabla'], $m['b']) !== $antesB, "{$modulo} B guarda lo suyo", $resumen($r));
            //Y por JSON sin `?id`: el 403 de (ii) no puede venir de rechazar todo JSON.
            $antesB = $huella($m['tabla'], $m['b']);
            $r = $pedir($ruta, $jwt['b'], ($m['campos'])($m['b'], "{$prefijo}-b-editado-json"), [], true);
            $check($r['status'] === 200 && $r['success'] === true && $huella($m['tabla'], $m['b']) !== $antesB, "{$modulo} B guarda lo suyo por JSON sin `?id`", $resumen($r));
            $r = $pedir($ruta, $jwt['root'], ($m['campos'])($m['a'], "{$prefijo}-a-por-root"));
            $check($r['status'] === 200 && $r['success'] === true && $huella($m['tabla'], $m['a']) !== $antes, "{$modulo} quien puede editarlo todo guarda lo de A", $resumen($r));
        }

        //─── b · Organizations: el encargado de una no guarda la otra; root sí, y «Mi organización» sigue guardando ──
        //Con DISABLE_NORMAL_EDIT_FORM en true el 403 lo da la puerta: la recomprobación de action() no se ve; se probó con la constante en false.
        echoTerminal('');
        echoTerminal('[Organizations]');
        $orgA = $crearOrganizacion('a');
        $orgB = $crearOrganizacion('b');
        $encargadoB = $crearUsuario('encargado-b', UsersModel::TYPE_USER_ADMIN_ORG, $orgB);
        $modeloB = new OrganizationMapper($orgB);
        $modeloB->administrator = $encargadoB;
        $modeloB->update();
        $jwtEncargado = UserSessions::issueToken($encargadoB);
        $ruta = $camino(OrganizationsController::class, 'actions-edit');
        $campos = fn (int $id, string $nombre): array => ['id' => $id, 'lang' => $lang, 'name' => $nombre, 'nit' => "{$prefijo}-nit-{$id}", 'status' => OrganizationMapper::ACTIVE];
        $antes = $huella(OrganizationMapper::TABLE, $orgA);
        $r = $pedir($ruta, $jwtEncargado, $campos($orgA, "{$prefijo}-probe"), ['id' => $orgB]);
        $check($r['status'] === 403, 'Organizations i: `?id=` propio y cuerpo ajeno → 403', $resumen($r));
        $check($huella(OrganizationMapper::TABLE, $orgA) === $antes, 'Organizations i: la organización A sigue intacta');
        $r = $pedir($ruta, $jwtEncargado, $campos($orgA, "{$prefijo}-probe"), [], true);
        $check($r['status'] === 403, 'Organizations ii: cuerpo JSON ajeno sin `?id` → 403', $resumen($r));
        $check($huella(OrganizationMapper::TABLE, $orgA) === $antes, 'Organizations ii: la organización A sigue intacta');
        $r = $pedir($ruta, $jwt['root'], $campos($orgA, "{$prefijo}-org-a-por-root"));
        $check($r['status'] === 200 && $r['success'] === true && $huella(OrganizationMapper::TABLE, $orgA) !== $antes, 'Organizations root guarda la organización A', $resumen($r));
        //«Mi organización» reutiliza el guardado con su propia autorización: la recomprobación no puede quitársela.
        $antesB = $huella(OrganizationMapper::TABLE, $orgB);
        $r = $pedir($camino(MyOrganizationProfileController::class, 'actions-save-profile'), $jwtEncargado, ['name' => "{$prefijo}-org-b-propia"]);
        $check($r['status'] === 200 && $r['success'] === true && $huella(OrganizationMapper::TABLE, $orgB) !== $antesB, 'Organizations el encargado guarda la suya desde «Mi organización»', $resumen($r));

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

})->setDescription('Editar vuelve a comprobar el permiso sobre el registro que se guarda en Documents, Categories y DocumentTypes: un `?id=` propio con cuerpo ajeno y un cuerpo JSON sin `?id` dan 403; lo propio (también por JSON) y quien puede editarlo todo siguen guardando. En Organizations, con su formulario de edición desactivado, el 403 lo da la puerta, y «Mi organización» sigue guardando. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
