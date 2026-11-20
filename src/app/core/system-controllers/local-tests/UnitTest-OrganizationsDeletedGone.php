<?php

//Una organización eliminada no vuelve por la interfaz (P380): se responde como un id que no existe y no sale en los listados.
//Por HTTP. Crea un principal, un encargado y organizaciones, zz-, y los retira.
//@areas src/app/classes/Organizations/

use Organizations\Controllers\MyOrganizationProfileController;
use Organizations\Controllers\OrganizationProfileController;
use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\App\Locations\Mappers\CityMapper;
use PiecesPHP\App\Locations\Mappers\StateMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/organizations-deleted-gone', function ($args) {

    echoTerminal("\e[33m[TEST:OrganizationsDeletedGone] Una organización eliminada responde como una que no existe\e[39m");
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
    $camino = function (string $sufijo, array $parametros = [], string $controlador = OrganizationsController::class) use ($prefijoBase): string {
        $url = $controlador::routeName($sufijo, $parametros, true);
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, string $jwt, ?array $post = null) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}", 'X-Requested-With: XMLHttpRequest'],
        ];
        if ($post !== null) {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($post);
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    $mensaje = fn (array $r): string => (string) (json_decode($r['body'], true)['message'] ?? '');
    $exito = fn (array $r): bool => $r['status'] === 200 && (json_decode($r['body'], true)['success'] ?? null) === true;
    $resumen = fn (array $r): string => "HTTP {$r['status']} «" . ($mensaje($r) !== '' ? $mensaje($r) : mb_substr(trim(strip_tags($r['body'])), 0, 80)) . '»';
    $medida = function (string $que, array $r) use ($resumen): void {
        echoTerminal("   · medida: {$que}: " . $resumen($r));
    };

    $prefijo = 'zz-prueba-org-eliminada-' . bin2hex(random_bytes(3));
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $usuarios = [];
    $organizaciones = [];
    $lang = Config::get_default_lang();

    try {
        $crearUsuario = function (string $sufijo, int $tipo, int $organizacion) use ($prefijo, &$usuarios): int {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            //Nadie entra con contraseña: el token lo fabrica la prueba.
            $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Organización eliminada';
            $u->secondLastname = '';
            $u->type = $tipo;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            $u->organization = $organizacion;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            return (int) $u->id;
        };
        //Completa según profileIsComplete(), para que su perfil público pinte la ficha y no el aviso de incompleta.
        $cities = CityMapper::PREFIX_TABLE . CityMapper::TABLE;
        $states = StateMapper::PREFIX_TABLE . StateMapper::TABLE;
        $lugar = $database->query("SELECT c.id AS city, s.country AS country FROM `{$cities}` c INNER JOIN `{$states}` s ON s.id = c.state ORDER BY c.id LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
        $crearOrganizacion = function (string $sufijo, int $estado) use ($prefijo, $lugar, &$organizaciones): int {
            $o = new OrganizationMapper();
            $o->name = "{$prefijo}-{$sufijo}";
            $o->nit = "{$prefijo}-nit-{$sufijo}";
            $o->folder = "{$prefijo}-{$sufijo}";
            $o->status = $estado;
            $o->activitySector = 'Sector de prueba';
            $o->informativeEmail = "{$prefijo}-{$sufijo}@example.com";
            $o->logo = 'statics/images/default-avatar-org.jpg';
            $o->country = is_array($lugar) ? (int) $lugar['country'] : null;
            $o->city = is_array($lugar) ? (int) $lugar['city'] : null;
            $o->latitude = 4.6;
            $o->longitude = -74.1;
            $o->save();
            $organizaciones[$sufijo] = (int) $o->id;
            return (int) $o->id;
        };
        $fila = function (int $id) use ($database, $tablaOrganizaciones): array {
            $consulta = $database->prepare("SELECT name, status FROM `{$tablaOrganizaciones}` WHERE id = ?");
            $consulta->execute([$id]);
            $resultado = $consulta->fetch(\PDO::FETCH_ASSOC);
            return is_array($resultado) ? ['name' => (string) $resultado['name'], 'status' => (int) $resultado['status']] : ['name' => '', 'status' => -1];
        };

        $root = UserSessions::issueToken($crearUsuario('root', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL));
        $eliminada = $crearOrganizacion('eliminada', OrganizationMapper::ACTIVE);
        $activa = $crearOrganizacion('activa', OrganizationMapper::ACTIVE);
        $inactiva = $crearOrganizacion('inactiva', OrganizationMapper::INACTIVE);
        $encargadoID = $crearUsuario('encargado', UsersModel::TYPE_USER_ADMIN_ORG, $eliminada);
        $conEncargado = new OrganizationMapper($eliminada);
        $conEncargado->administrator = $encargadoID;
        $conEncargado->update();
        $encargado = UserSessions::issueToken($encargadoID);
        $inexistente = (int) $database->query("SELECT COALESCE(MAX(id), 0) + 1000 FROM `{$tablaOrganizaciones}`")->fetchColumn();
        $nombreEliminada = "{$prefijo}-eliminada";
        $nombreActiva = "{$prefijo}-activa";
        //Los listados se cotejan por el código público: el nombre puede cambiar y la carpeta lo repite.
        $codigo = function (int $id) use ($database, $tablaOrganizaciones): string {
            $consulta = $database->prepare("SELECT code FROM `{$tablaOrganizaciones}` WHERE id = ?");
            $consulta->execute([$id]);
            return (string) $consulta->fetchColumn();
        };
        $codigoEliminada = $codigo($eliminada);
        $codigoActiva = $codigo($activa);
        $codigoInactiva = $codigo($inactiva);
        $check($codigoEliminada !== '' && $codigoActiva !== '' && $codigoInactiva !== '', 'p2 las organizaciones sembradas tienen código público para cotejarlas en los listados');

        $formulario = fn (int $id): string => $camino('forms-edit', ['id' => $id, 'lang' => $lang]);
        $editar = fn (int $id, array $extra = []): array => $pedir($camino('actions-edit'), $root, array_merge(['id' => (string) $id, 'lang' => $lang, 'name' => "{$prefijo}-cambiada"], $extra));
        $eliminar = fn (int $id): array => $pedir($camino('actions-delete', ['id' => $id]), $root, ['id' => (string) $id]);
        $perfil = fn (int $id, string $jwt): array => $pedir($camino('profile', ['organizationID' => $id], OrganizationProfileController::class), $jwt);
        $miOrganizacion = fn (int $id): array => $pedir($camino('my-organization-profile', ['organizationID' => $id], MyOrganizationProfileController::class), $root);
        $guardarPerfil = fn (int $id): array => $pedir($camino('actions-save-profile', ['organizationID' => $id], MyOrganizationProfileController::class), $root, ['name' => "{$prefijo}-cambiada"]);
        $todas = fn (string $estado): array => $pedir($camino('ajax-all') . '?per_page=100&name=' . rawurlencode($prefijo) . ($estado !== '' ? '&status=' . rawurlencode($estado) : ''), $root);
        $tabla = function (?string $estado) use ($pedir, $camino, $root, $prefijo): array {
            $consulta = [
                'draw' => 1,
                'start' => 0,
                'length' => 100,
                'columns' => array_fill(0, 6, ['searchable' => 'true']),
                'search' => ['value' => $prefijo, 'regex' => 'false'],
            ];
            if ($estado !== null) {
                $consulta['status'] = $estado;
            }
            return $pedir($camino('datatables') . '?' . http_build_query($consulta), $root);
        };

        echoTerminal('[a] Activa: se abre, se guarda y sale en los listados (canarios)');
        $abrirActiva = $pedir($formulario($activa), $root);
        $check($abrirActiva['status'] === 200 && str_contains($abrirActiva['body'], $nombreActiva), 'a1 CANARIO: su formulario de edición se abre', $resumen($abrirActiva));
        $guardarActiva = $editar($activa, ['name' => $nombreActiva]);
        $check($exito($guardarActiva) && $fila($activa)['status'] === OrganizationMapper::ACTIVE, 'a2 CANARIO: su POST de edición se guarda', $resumen($guardarActiva));
        $check($perfil($activa, $root)['status'] === 200, 'a3 CANARIO: su perfil público responde 200');
        $check($miOrganizacion($activa)['status'] === 200, 'a4 CANARIO: su «mi organización» con id responde 200');
        $todasActiva = $todas('ANY');
        $check(str_contains($todasActiva['body'], $codigoActiva), 'a5 CANARIO: all con status=ANY la devuelve', $resumen($todasActiva));
        $tablaActiva = $tabla(null);
        $check(str_contains($tablaActiva['body'], $codigoActiva), 'a6 CANARIO: datatables sin status la devuelve', $resumen($tablaActiva));
        $tablaInactiva = $tabla((string) OrganizationMapper::INACTIVE);
        $medida('datatables con status=2 (pestaña Inactivas), ¿sale la inactiva?', ['status' => $tablaInactiva['status'], 'body' => str_contains($tablaInactiva['body'], $codigoInactiva) ? 'sí' : 'no']);
        //El criterio fijo es «status != DELETED»: antes era «!= INACTIVE» y la pestaña de inactivas salía siempre vacía.
        $check(str_contains($tablaInactiva['body'], $codigoInactiva), 'a6b la pestaña Inactivas (status=2) devuelve la inactiva', $resumen($tablaInactiva));
        $check($perfil($eliminada, $encargado)['status'] === 200, 'a7 CANARIO: antes de eliminarla, su encargado entra y ve su perfil');

        echoTerminal('');
        echoTerminal('[b] Eliminada: responde como un id que no existe');
        $borrada = $eliminar($eliminada);
        $check($exito($borrada) && $fila($eliminada)['status'] === OrganizationMapper::DELETED, 'b1 eliminarla la deja en DELETED (0)', $resumen($borrada) . ', estado ' . $fila($eliminada)['status']);

        $formularioNada = $pedir($formulario($inexistente), $root);
        $formularioBorrada = $pedir($formulario($eliminada), $root);
        $medida('forms-edit, inexistente', $formularioNada);
        $medida('forms-edit, eliminada', $formularioBorrada);
        $check($formularioBorrada['status'] === $formularioNada['status'] && $formularioBorrada['status'] !== 200 && !str_contains($formularioBorrada['body'], $nombreEliminada), 'b2 su formulario de edición responde como el de un id que no existe', $resumen($formularioBorrada) . ' / inexistente ' . $resumen($formularioNada));

        $editarNada = $editar($inexistente);
        $editarBorrada = $editar($eliminada);
        $medida('actions-edit, inexistente', $editarNada);
        $medida('actions-edit, eliminada', $editarBorrada);
        $despues = $fila($eliminada);
        $check($editarBorrada['status'] === $editarNada['status'] && $mensaje($editarBorrada) === $mensaje($editarNada) && !$exito($editarBorrada), 'b3 DISCRIMINANTE: su POST de edición responde como el de un id que no existe', $resumen($editarBorrada) . ' / inexistente ' . $resumen($editarNada));
        $check($despues['status'] === OrganizationMapper::DELETED && $despues['name'] === $nombreEliminada, 'b4 y sigue en 0, sin el cambio', "estado {$despues['status']}, nombre {$despues['name']}");

        $eliminarNada = $eliminar($inexistente);
        $eliminarOtraVez = $eliminar($eliminada);
        $medida('actions-delete, inexistente', $eliminarNada);
        $medida('actions-delete, eliminada', $eliminarOtraVez);
        $check($eliminarOtraVez['status'] === $eliminarNada['status'] && $mensaje($eliminarOtraVez) === $mensaje($eliminarNada) && !$exito($eliminarOtraVez), 'b5 eliminarla otra vez responde como eliminar un id que no existe', $resumen($eliminarOtraVez) . ' / inexistente ' . $resumen($eliminarNada));

        $perfilNada = $perfil($inexistente, $root);
        $perfilBorrada = $perfil($eliminada, $root);
        $medida('profile, inexistente', $perfilNada);
        $medida('profile, eliminada', $perfilBorrada);
        $check($perfilBorrada['status'] === $perfilNada['status'] && $perfilBorrada['status'] !== 200 && !str_contains($perfilBorrada['body'], $nombreEliminada), 'b6 su perfil público responde como el de un id que no existe, también a root', $resumen($perfilBorrada) . ' / inexistente ' . $resumen($perfilNada));

        $miNada = $miOrganizacion($inexistente);
        $miBorrada = $miOrganizacion($eliminada);
        $medida('my-organization-profile, inexistente', $miNada);
        $medida('my-organization-profile, eliminada', $miBorrada);
        $check($miBorrada['status'] === $miNada['status'] && $miBorrada['status'] !== 200 && !str_contains($miBorrada['body'], $nombreEliminada), 'b7 su «mi organización» con id responde como la de un id que no existe', $resumen($miBorrada) . ' / inexistente ' . $resumen($miNada));
        $guardarPerfilNada = $guardarPerfil($inexistente);
        $guardarPerfilBorrada = $guardarPerfil($eliminada);
        $medida('actions-save-profile, inexistente', $guardarPerfilNada);
        $medida('actions-save-profile, eliminada', $guardarPerfilBorrada);
        $check($guardarPerfilBorrada['status'] === $guardarPerfilNada['status'] && !$exito($guardarPerfilBorrada) && $fila($eliminada)['name'] === $nombreEliminada, 'b8 guardar su perfil responde como el de un id que no existe, y no cambia', $resumen($guardarPerfilBorrada) . ' / inexistente ' . $resumen($guardarPerfilNada));

        echoTerminal('');
        echoTerminal('[c] Los listados no la devuelven con ningún estado pedido');
        foreach (['0', 'ANY', '1', ''] as $estado) {
            $listado = $todas($estado);
            $medida("all status={$estado}, ¿sale la eliminada?", ['status' => $listado['status'], 'body' => str_contains($listado['body'], $codigoEliminada) ? 'sí' : 'no']);
            $check($listado['status'] === 200 && !str_contains($listado['body'], $codigoEliminada), "c1 DISCRIMINANTE: all con status=" . ($estado !== '' ? $estado : '(sin)') . ' no la devuelve', $resumen($listado));
        }
        foreach ([null, '0', '1', '2', '3'] as $estado) {
            $listado = $tabla($estado);
            $medida('datatables status=' . ($estado ?? '(sin)') . ', ¿sale la eliminada?', ['status' => $listado['status'], 'body' => str_contains($listado['body'], $codigoEliminada) ? 'sí' : 'no']);
            $check($listado['status'] === 200 && !str_contains($listado['body'], $codigoEliminada), 'c2 DISCRIMINANTE: datatables con status=' . ($estado ?? '(sin)') . ' no la devuelve', $resumen($listado));
        }
        $check(str_contains($tabla(null)['body'], $codigoActiva) && str_contains($todas('ANY')['body'], $codigoActiva), 'c3 CANARIO: la activa sigue saliendo en los dos listados');

        echoTerminal('');
        echoTerminal('[d] Nadie pone el estado 0 por POST');
        $aCero = $editar($activa, ['status' => (string) OrganizationMapper::DELETED, 'name' => "{$prefijo}-a-cero"]);
        $medida('actions-edit con status=0 sobre una activa', $aCero);
        $tras = $fila($activa);
        $check(!$exito($aCero) && $tras['status'] === OrganizationMapper::ACTIVE && $tras['name'] === $nombreActiva, 'd1 DISCRIMINANTE: un POST con status=0 sobre una activa se rechaza y sigue como estaba', $resumen($aCero) . ", estado {$tras['status']}, nombre {$tras['name']}");
        $aInactiva = $editar($activa, ['status' => (string) OrganizationMapper::INACTIVE, 'name' => $nombreActiva]);
        $check($exito($aInactiva) && $fila($activa)['status'] === OrganizationMapper::INACTIVE, 'd2 CANARIO: el mismo POST con status=2 (Inactiva, elegible) sí se guarda', $resumen($aInactiva));
        $altaCero = $pedir($camino('actions-add'), $root, ['name' => "{$prefijo}-alta-cero", 'nit' => "{$prefijo}-nit-alta-cero", 'status' => (string) OrganizationMapper::DELETED]);
        $altaCreada = (int) $database->query("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name = " . $database->quote("{$prefijo}-alta-cero") . ' AND status = ' . OrganizationMapper::DELETED)->fetchColumn();
        $medida('actions-add con status=0', $altaCero);
        $check($altaCreada === 0 && !$exito($altaCero), 'd3 un alta con status=0 no nace eliminada', $resumen($altaCero) . ", creadas en 0: {$altaCreada}");

        echoTerminal('');
        echoTerminal('[e] Sus usuarios (solo medida; no se cambia aquí)');
        $medida('su encargado, perfil público de la eliminada', $perfil($eliminada, $encargado));
        $medida('su encargado, «mi organización» sin id', $pedir($camino('my-organization-profile', [], MyOrganizationProfileController::class), $encargado));
        $medida('su encargado, inicio del panel', $pedir($camino('', [], \PiecesPHP\AdminPanel\Controllers\AdminPanelController::class), $encargado));
        try {
            $nuevo = UserSessions::issueToken($encargadoID);
            echoTerminal('   · medida: un token nuevo para su encargado: emitido (' . strlen($nuevo) . ' caracteres)');
        } catch (\Throwable $e) {
            echoTerminal('   · medida: un token nuevo para su encargado: rechazado, ' . get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 120));
        }
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ($usuarios as $id) {
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, UsersModel::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            //Quien guarda queda en `modifiedBy` y `createdBy` de la organización, con clave foránea al usuario: se suelta antes.
            $soltar = OrganizationMapper::model();
            $soltar->resetAll();
            //RETORNO-IGNORADO: si no soltara, el borrado de usuarios lanzaría y lo diría z0.
            $soltar->update(['modifiedBy' => null])->where(new WhereSegment([WhereItem::like('name', "{$prefijo}%")]))->execute();
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
            $restantes = $database->query("SELECT id FROM `{$tablaOrganizaciones}` WHERE name LIKE " . $database->quote("{$prefijo}%"))->fetchAll(\PDO::FETCH_COLUMN);
            foreach (array_unique(array_merge(array_values($organizaciones), array_map('intval', $restantes))) as $id) {
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z2 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, OrganizationMapper::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
            }
            $modeloOrganizaciones = OrganizationMapper::model();
            $modeloOrganizaciones->resetAll();
            //RETORNO-IGNORADO: lo comprueba z2 contando lo que queda.
            $modeloOrganizaciones->delete(new WhereSegment([WhereItem::like('name', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedanUsuarios = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedanUsuarios === 0, 'z1 no queda ningún usuario de la prueba', "quedan {$quedanUsuarios}");
        $quedanOrganizaciones = (int) $database->query("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name LIKE " . $database->quote("{$prefijo}%") . ' OR nit LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $quedanAprobaciones = 0;
        foreach ($organizaciones as $id) {
            $quedanAprobaciones += (int) $database->query('SELECT COUNT(*) FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ' . $database->quote($tablaOrganizaciones) . ' AND referenceValue = ' . $database->quote((string) $id))->fetchColumn();
        }
        $check($quedanOrganizaciones === 0 && $quedanAprobaciones === 0, 'z2 no queda ninguna organización ni aprobación de la prueba', "organizaciones {$quedanOrganizaciones}, aprobaciones {$quedanAprobaciones}");
    }

    return $balance();

})->setDescription('Una organización eliminada (status 0) responde como un id que no existe en su formulario, su acción de edición, eliminar, su perfil público y «mi organización»; no sale en all ni en datatables con ningún estado pedido; y nadie pone el estado 0 por POST (P380). Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
