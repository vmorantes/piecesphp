<?php

//Una categoría de formularios eliminada no vuelve por la interfaz (P380): su formulario, su guardado y eliminarla otra vez
//responden como un id que no existe, también para root. Por HTTP; siembra zz-prueba- y lo retira.
//@areas src/app/classes/Forms/Categories/
//@requires-module Forms\Categories\CategoriesRoutes
//@requires-module Organizations\OrganizationsRoutes
//@requires-module SystemApprovals\SystemApprovalsRoutes

use Forms\Categories\Controllers\CategoriesController;
use Forms\Categories\Mappers\CategoriesMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/forms-categories-deleted', function ($args) {

    echoTerminal("\e[33m[TEST:FormsCategoriesDeleted] Una categoría de formularios eliminada responde como una que no existe\e[39m");
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
    $camino = function (string $sufijo, array $parametros = []) use ($prefijoBase): string {
        $url = CategoriesController::routeName($sufijo, $parametros, true);
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, string $jwt, ?array $campos = null) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ];
        if ($campos !== null) {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = $campos;
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    $json = fn (array $r): array => (array) (json_decode($r['body'], true) ?? []);
    $mensaje = fn (array $r): string => (string) ($json($r)['message'] ?? '');
    $resumen = fn (array $r): string => "HTTP {$r['status']} «" . mb_substr($mensaje($r), 0, 120) . '»';

    $prefijo = 'zz-prueba-categorias-eliminadas-' . bin2hex(random_bytes(3));
    $tabla = CategoriesMapper::TABLE;
    $tablaUsuarios = UsersModel::TABLE;
    $usuarios = [];
    $categorias = [];

    try {
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Categorias';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ROOT;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[] = (int) $u->id;
        $root = UserSessions::issueToken((int) $u->id);

        $sembrar = function (string $sufijo) use ($database, $tabla, $prefijo, $usuarios, &$categorias): int {
            $database->prepare("INSERT INTO `{$tabla}` (categoryName, folder, status, createdAt, createdBy, meta) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute(["{$prefijo}-{$sufijo}", "{$prefijo}-{$sufijo}", CategoriesMapper::STATUS_ACTIVE, date('Y-m-d H:i:s'), $usuarios[0], json_encode(['langData' => new \stdClass])]);
            $categorias[$sufijo] = (int) $database->lastInsertId();
            return $categorias[$sufijo];
        };
        $fila = function (int $id) use ($database, $tabla): array {
            $r = $database->query("SELECT status, categoryName FROM `{$tabla}` WHERE id = {$id}")->fetch(\PDO::FETCH_ASSOC);
            return is_array($r) ? ['status' => (int) $r['status'], 'name' => (string) $r['categoryName']] : ['status' => -1, 'name' => ''];
        };
        $idioma = (string) get_config('default_lang');
        $formulario = fn (int $id): array => $pedir($camino('forms-edit', ['id' => $id, 'lang' => $idioma]), $root);
        $guardar = fn (int $id, string $nombre): array => $pedir($camino('actions-edit'), $root, ['id' => (string) $id, 'lang' => $idioma, 'categoryName' => $nombre]);
        $eliminar = fn (int $id): array => $pedir($camino('actions-delete', ['id' => $id]), $root, ['id' => (string) $id]);

        $activa = $sembrar('activa');
        $eliminada = $sembrar('eliminada');
        $inexistente = (int) $database->query("SELECT COALESCE(MAX(id), 0) + 1000 FROM `{$tabla}`")->fetchColumn();

        echoTerminal('[a] Canarios: una categoría activa se abre y se guarda');
        $formActiva = $formulario($activa);
        $check($formActiva['status'] === 200 && str_contains($formActiva['body'], "{$prefijo}-activa"), 'a1 CANARIO: su formulario de edición se abre', "HTTP {$formActiva['status']}");
        $guardadoActiva = $guardar($activa, "{$prefijo}-activa-cambiada");
        $check(($json($guardadoActiva)['success'] ?? null) === true && $fila($activa)['name'] === "{$prefijo}-activa-cambiada", 'a2 CANARIO: guardarla cambia el nombre', $resumen($guardadoActiva));

        echoTerminal('');
        echoTerminal('[b] Eliminada responde como un id que no existe (P380)');
        $borrado = $eliminar($eliminada);
        $check(($json($borrado)['success'] ?? null) === true && $fila($eliminada)['status'] === CategoriesMapper::STATUS_INACTIVE, 'b1 eliminarla la deja INACTIVE (la fila queda como histórico)', $resumen($borrado) . ', estado ' . $fila($eliminada)['status']);

        $formNada = $formulario($inexistente);
        $formBorrada = $formulario($eliminada);
        echoTerminal("      medido: formulario de un id inexistente HTTP {$formNada['status']}, de la eliminada HTTP {$formBorrada['status']}");
        $check($formBorrada['status'] === $formNada['status'] && $formBorrada['status'] !== 200 && !str_contains($formBorrada['body'], "{$prefijo}-eliminada"), 'b2 su formulario de edición responde como el de un id que no existe', "eliminada HTTP {$formBorrada['status']}, inexistente HTTP {$formNada['status']}");

        $guardarNada = $guardar($inexistente, "{$prefijo}-nada");
        $guardarBorrada = $guardar($eliminada, "{$prefijo}-resucitada");
        echoTerminal('      medido: guardar un id inexistente ' . $resumen($guardarNada) . ', la eliminada ' . $resumen($guardarBorrada));
        $check($guardarBorrada['status'] === $guardarNada['status'] && $mensaje($guardarBorrada) === $mensaje($guardarNada) && ($json($guardarBorrada)['success'] ?? null) !== true, 'b3 DISCRIMINANTE: su POST de edición responde como el de un id que no existe', 'eliminada ' . $resumen($guardarBorrada) . ', inexistente ' . $resumen($guardarNada));
        $despues = $fila($eliminada);
        $check($despues['status'] === CategoriesMapper::STATUS_INACTIVE && $despues['name'] === "{$prefijo}-eliminada", 'b4 y sigue INACTIVE, sin el nombre enviado', "estado {$despues['status']}, nombre {$despues['name']}");

        $borrarNada = $eliminar($inexistente);
        $borrarOtraVez = $eliminar($eliminada);
        echoTerminal('      medido: eliminar un id inexistente ' . $resumen($borrarNada) . ', la eliminada ' . $resumen($borrarOtraVez));
        $check($borrarOtraVez['status'] === $borrarNada['status'] && $mensaje($borrarOtraVez) === $mensaje($borrarNada) && ($json($borrarOtraVez)['success'] ?? null) !== true, 'b5 eliminarla otra vez responde como eliminar un id que no existe', 'eliminada ' . $resumen($borrarOtraVez) . ', inexistente ' . $resumen($borrarNada));
        $check($borrarOtraVez['status'] === 404 && $borrarNada['status'] === 404, 'b5b DISCRIMINANTE: y los dos con 404, no 403, también a root (H489)', 'eliminada ' . $resumen($borrarOtraVez) . ', inexistente ' . $resumen($borrarNada));

        echoTerminal('');
        echoTerminal('[c] Los listados ya filtran por ACTIVE');
        $todas = $pedir($camino('ajax-all') . '?per_page=5000', $root);
        $check($todas['status'] === 200 && str_contains($todas['body'], "{$prefijo}-activa") && !str_contains($todas['body'], "{$prefijo}-eliminada"), 'c1 -ajax-all trae la activa y no la eliminada', "HTTP {$todas['status']}");
        $tablaDatos = $pedir($camino('datatables') . '?draw=1&start=0&length=5000&search%5Bvalue%5D=' . rawurlencode($prefijo), $root);
        $check($tablaDatos['status'] === 200 && str_contains($tablaDatos['body'], "{$prefijo}-activa") && !str_contains($tablaDatos['body'], "{$prefijo}-eliminada"), 'c2 -datatables trae la activa y no la eliminada', "HTTP {$tablaDatos['status']}");
        $conEstado = $pedir($camino('datatables') . '?draw=1&start=0&length=5000&status=0&search%5Bvalue%5D=' . rawurlencode($prefijo), $root);
        $check($conEstado['status'] === 200 && !str_contains($conEstado['body'], "{$prefijo}-eliminada"), 'c3 ni pidiendo status=0', "HTTP {$conEstado['status']}");
    } catch (\Throwable $e) {
        $check(false, 'e* el recorrido corre entero', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $database->prepare("DELETE FROM `{$tabla}` WHERE categoryName LIKE ? OR folder LIKE ?")->execute(["{$prefijo}%", "{$prefijo}%"]);
            foreach ($usuarios as $id) {
                $aprobaciones = SystemApprovalsMapper::model();
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tablaUsuarios),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tabla}` WHERE categoryName LIKE " . $database->quote("{$prefijo}%") . ' OR folder LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn()
         + (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $quedanAprobaciones = 0;
        foreach ($usuarios as $id) {
            $quedanAprobaciones += (int) $database->query('SELECT COUNT(*) FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ' . $database->quote($tablaUsuarios) . ' AND referenceValue = ' . $database->quote((string) $id))->fetchColumn();
        }
        $check($quedan === 0 && $quedanAprobaciones === 0, 'z1 no queda ninguna categoría, usuario ni aprobación de la prueba', "quedan {$quedan}, aprobaciones {$quedanAprobaciones}");
    }

    return $balance();

})->setDescription('Una categoría de formularios eliminada (INACTIVE) responde como un id que no existe en su formulario de edición, su acción de edición (no cambia el nombre ni el estado) y eliminarla otra vez, también para root; -ajax-all y -datatables no la traen ni con status=0 (P380). Canarios: una activa se abre y se guarda. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
