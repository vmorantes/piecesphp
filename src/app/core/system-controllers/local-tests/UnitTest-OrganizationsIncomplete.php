<?php

//Organizaciones a medias no tumban el panel: menú sin organización, edición de una que no existe, sin encargado o con
//él Eliminado, y listado sin langData. Por HTTP. Siembra organizaciones y usuarios zz-, y los retira.
//@areas src/app/classes/Organizations/
//@areas src/app/classes/SystemApprovals/

use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/organizations-incomplete', function ($args) {

    echoTerminal("\e[33m[TEST:OrganizationsIncomplete] Organizaciones con datos a medias no tumban el panel\e[39m");
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
    $cabeceraToken = SessionToken::headerName();
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, string $jwt, bool $seguir = false) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => $seguir,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ]);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    $enviar = function (string $path, string $jwt, array $campos) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($campos),
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}", 'X-Requested-With: XMLHttpRequest'],
        ]);
        $body = curl_exec($handle);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'json' => json_decode(is_string($body) ? $body : '', true)];
    };
    //La etiqueta del desplegable del encargado, para mirar si es obligatorio.
    $selectEncargado = fn (string $html): string => preg_match('/<select[^>]*name="administrator"[^>]*>/', $html, $m) === 1 ? $m[0] : '';

    $prefijo = 'zz-prueba-organizaciones-' . bin2hex(random_bytes(3));
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $tablaUsuarios = UsersModel::TABLE;
    $organizaciones = [];
    $usuarios = [];

    try {
        $crearUsuario = function (string $sufijo, int $tipo, ?int $organizacion) use ($prefijo, &$usuarios): int {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            //Nadie entra con contraseña: el token lo fabrica la prueba.
            $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Organizaciones';
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
            return (int) $u->id;
        };
        $root = UserSessions::issueToken($crearUsuario('root', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL));

        $ahora = date('Y-m-d H:i:s');
        $sembrar = function (string $sufijo, array $meta) use ($database, $tablaOrganizaciones, $prefijo, $ahora, &$usuarios, &$organizaciones): int {
            $database->prepare("INSERT INTO `{$tablaOrganizaciones}` (code, name, nit, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute(['zz' . bin2hex(random_bytes(4)), "{$prefijo}-{$sufijo}", 'zz-', "{$prefijo}-{$sufijo}", $ahora, $usuarios['root'], OrganizationMapper::ACTIVE, json_encode($meta)]);
            $organizaciones[$sufijo] = (int) $database->lastInsertId();
            return $organizaciones[$sufijo];
        };
        $propia = $sembrar('completa', ['baseLang' => 'es', 'langData' => new \stdClass]);
        $adminOrgID = $crearUsuario('admin-org', UsersModel::TYPE_USER_ADMIN_ORG, $propia);
        $database->prepare("UPDATE `{$tablaOrganizaciones}` SET meta = ? WHERE id = ?")
            ->execute([json_encode(['baseLang' => 'es', 'langData' => new \stdClass, 'administrator' => $adminOrgID]), $propia]);
        $adminOrg = UserSessions::issueToken($adminOrgID);
        $sinOrganizacion = UserSessions::issueToken($crearUsuario('sin-organizacion', UsersModel::TYPE_USER_ADMIN_ORG, null));
        $incompleta = $sembrar('incompleta', ['baseLang' => 'es']);
        $sinEncargado = $sembrar('sin-encargado', ['baseLang' => 'es', 'langData' => new \stdClass, 'administrator' => null]);
        $inexistente = (int) $database->query("SELECT COALESCE(MAX(id), 0) + 1000 FROM `{$tablaOrganizaciones}`")->fetchColumn();
        $editar = fn (int $id): string => $camino(OrganizationsController::routeName('forms-edit', ['id' => $id, 'lang' => 'es'], true));

        echoTerminal('[a] El menú del panel');
        $inicio = $camino(AdminPanelController::routeName('', [], true));
        //El inicio redirige a quien no es el principal: se sigue hasta la página que pinta el menú.
        $deAdmin = $pedir($inicio, $adminOrg, true);
        //La entrada «Organización» solo la ve el principal (OrganizationMapper::DISABLE_NORMAL_EDIT_FORM): lo que se mira es
        //que el grupo del menú se pinte, es decir, que la rama que la construye corrió entera.
        $check($deAdmin['status'] === 200 && str_contains($deAdmin['body'], 'Gestión de la organización'), 'a1 CANARIO: el administrador de una organización recibe el panel con el grupo de su organización', "HTTP {$deAdmin['status']}");
        $deSinOrganizacion = $pedir($inicio, $sinOrganizacion, true);
        $check($deSinOrganizacion['status'] === 200, 'a2 un usuario sin organización recibe el panel, no un 500', "HTTP {$deSinOrganizacion['status']}");

        echoTerminal('');
        echoTerminal('[b] La edición de una organización que no existe');
        $noExiste = $pedir($editar($inexistente), $root);
        $check(in_array($noExiste['status'], [403, 404], true), 'b1 responde 403 o 404, nunca 500', "HTTP {$noExiste['status']}");
        $canario = $pedir($editar($propia), $root);
        $check($canario['status'] === 200 && str_contains($canario['body'], "{$prefijo}-completa"), 'b2 CANARIO: la de una que existe se pinta', "HTTP {$canario['status']}");

        echoTerminal('');
        echoTerminal('[c] La edición de una organización sin encargado');
        $vacia = $pedir($editar($sinEncargado), $root);
        $check($vacia['status'] === 200 && str_contains($vacia['body'], "{$prefijo}-sin-encargado"), 'c1 se pinta, sin encargado seleccionado', "HTTP {$vacia['status']}");

        echoTerminal('');
        echoTerminal('[d] La edición de una organización cuyo encargado fue eliminado (C3)');
        $conEliminado = $sembrar('encargado-eliminado', ['baseLang' => 'es', 'langData' => new \stdClass]);
        $eliminadoID = $crearUsuario('encargado-eliminado', UsersModel::TYPE_USER_ADMIN_ORG, $conEliminado);
        $database->prepare("UPDATE `{$tablaUsuarios}` SET status = ? WHERE id = ?")->execute([UsersModel::STATUS_USER_DELETED, $eliminadoID]);
        //Con un NIT propio: guardar rechaza un NIT repetido, y las sembradas comparten «zz-».
        $database->prepare("UPDATE `{$tablaOrganizaciones}` SET meta = ?, nit = ? WHERE id = ?")
            ->execute([json_encode(['baseLang' => 'es', 'langData' => new \stdClass, 'administrator' => $eliminadoID]), "{$prefijo}-nit", $conEliminado]);
        $aviso = 'El encargado actual fue eliminado: elija otro cuando pueda.';
        $pagina = $pedir($editar($conEliminado), $root);
        $select = $selectEncargado($pagina['body']);
        $check($pagina['status'] === 200 && $select !== '' && !str_contains($select, 'required') && str_contains($pagina['body'], $aviso), 'd1 se pinta con el encargado sin obligar y con el aviso', "HTTP {$pagina['status']}, {$select}");
        $selectCompleta = $selectEncargado($canario['body']);
        $check(str_contains($selectCompleta, 'required') && !str_contains($canario['body'], $aviso), 'd2 CANARIO: la de un encargado activo sigue obligando y sin aviso', $selectCompleta);
        $guardar = $enviar($camino(OrganizationsController::routeName('actions-edit', [], true)), $root, ['id' => $conEliminado, 'lang' => 'es', 'name' => "{$prefijo}-encargado-eliminado", 'administrator' => '']);
        $guardada = new OrganizationMapper($conEliminado);
        $encargado = $guardada->administrator instanceof UsersModel ? $guardada->administrator->id : $guardada->administrator;
        $check($guardar['status'] === 200 && ($guardar['json']['success'] ?? false) === true && (int) $encargado === $eliminadoID, 'd3 se guarda con el encargado vacío, y lo conserva', "HTTP {$guardar['status']}, success " . var_export($guardar['json']['success'] ?? null, true) . ", encargado " . var_export($encargado, true) . ", mensaje " . mb_substr((string) ($guardar['json']['message'] ?? ''), 0, 200));

        echoTerminal('');
        echoTerminal('[e] El listado del panel con una organización sin langData');
        $tabla = $pedir($camino(OrganizationsController::routeName('datatables', [], true)) . '?status=' . OrganizationMapper::ACTIVE . '&draw=1&start=0&length=100&search[value]=' . rawurlencode($prefijo), $root);
        $check($tabla['status'] === 200, 'e1 responde 200', "HTTP {$tabla['status']}");
        $check(str_contains($tabla['body'], "{$prefijo}-completa"), 'e2 CANARIO: la completa está en el listado');
        $check(str_contains($tabla['body'], "{$prefijo}-incompleta"), 'e3 y la incompleta también: sin langData se lista con sus campos base');
        $check($incompleta > 0, 'e4 la incompleta se sembró');

        echoTerminal('');
        echoTerminal('[f] El listado JSON del panel no publica el esquema de la tabla');
        $todas = $pedir($camino(OrganizationsController::routeName('ajax-all', [], true)) . '?' . http_build_query(['page' => 1, 'per_page' => 2, 'name' => $prefijo]), $root);
        $primera = (json_decode($todas['body'], true)['parsedElements'] ?? [])[0] ?? [];
        $check($todas['status'] === 200 && isset($primera['id'], $primera['name']), 'f1 CANARIO: responde 200 y la primera organización trae id y name', "HTTP {$todas['status']}");
        $esquema = array_values(array_intersect(['table', 'primaryKey', 'foreingsKeys', 'fields'], array_keys($primera)));
        $check($primera !== [] && $esquema === [], 'f2 y ninguna de table, primaryKey, foreingsKeys ni fields', implode(', ', $esquema));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ([[$tablaOrganizaciones, $organizaciones], [$tablaUsuarios, $usuarios]] as [$tablaReferencia, $ids]) {
                foreach ($ids as $id) {
                    $aprobaciones->resetAll();
                    //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                    $aprobaciones->delete(new WhereSegment([
                        new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tablaReferencia),
                        new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                    ]))->execute();
                }
            }
            foreach ($usuarios as $id) {
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            //Las claves van en los dos sentidos (el usuario a su organización, la organización a su creador): los usuarios
            //pasan a la global, caen las organizaciones y después los usuarios.
            $database->exec("UPDATE `{$tablaUsuarios}` SET organization = " . OrganizationMapper::INITIAL_ID_GLOBAL . ' WHERE username LIKE ' . $database->quote("{$prefijo}%"));
            foreach ($organizaciones as $id) {
                $database->exec("DELETE FROM `{$tablaOrganizaciones}` WHERE id = " . (int) $id);
            }
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedanOrganizaciones = (int) $database->query("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $quedanUsuarios = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $quedanAprobaciones = 0;
        foreach ([[$tablaOrganizaciones, $organizaciones], [$tablaUsuarios, $usuarios]] as [$tablaReferencia, $ids]) {
            foreach ($ids as $id) {
                $quedanAprobaciones += (int) $database->query('SELECT COUNT(*) FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ' . $database->quote($tablaReferencia) . ' AND referenceValue = ' . $database->quote((string) $id))->fetchColumn();
            }
        }
        $check($quedanOrganizaciones + $quedanUsuarios + $quedanAprobaciones === 0, 'z1 no queda ninguna organización, usuario ni aprobación de la prueba', "organizaciones {$quedanOrganizaciones}, usuarios {$quedanUsuarios}, aprobaciones {$quedanAprobaciones}");
    }

    return $balance();

})->setDescription('Organizaciones con datos a medias no tumban el panel: el menú de un usuario sin organización, la edición de una que no existe, sin encargado o con el encargado Eliminado, y el listado con una sin langData. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
