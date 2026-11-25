<?php

//Una noticia eliminada no vuelve por la interfaz (P380): responde como un id que no existe y no sale en el JSON de todas.
//Por HTTP. Siembra una categoría, dos noticias y un root, todos zz-, y los retira.
//@areas src/app/classes/News/
//@requires-module News\NewsRoutes
//@requires-module Organizations\OrganizationsRoutes
//@requires-module SystemApprovals\SystemApprovalsRoutes

use News\Controllers\NewsController;
use News\Mappers\NewsCategoryMapper;
use News\Mappers\NewsMapper;
use News\Mappers\NewsReadedMapper;
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

CliActions::make('unit-tests:core/news-deleted', function ($args) {

    echoTerminal("\e[33m[TEST:NewsDeleted] Una noticia eliminada responde como un id que no existe (P380)\e[39m");
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
        $url = NewsController::routeName($sufijo, $parametros, true);
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
    $mensaje = fn (array $r): string => (string) (json_decode($r['body'], true)['message'] ?? '');
    $exito = fn (array $r): bool => $r['status'] === 200 && (json_decode($r['body'], true)['success'] ?? null) === true;
    $igual = fn (array $a, array $b): bool => $a['status'] === $b['status'] && $mensaje($a) === $mensaje($b);
    $contar = fn (array $r): string => "HTTP {$r['status']} «" . mb_substr($mensaje($r) !== '' ? $mensaje($r) : strip_tags($r['body']), 0, 120) . '»';

    $prefijo = 'zz-prueba-noticias-eliminadas-' . bin2hex(random_bytes(3));
    $tablaNoticias = NewsMapper::TABLE;
    $tablaCategorias = NewsCategoryMapper::TABLE;
    $tablaLeidas = NewsReadedMapper::TABLE;
    $usuario = 0;
    $noticias = [];
    $categoria = 0;

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
        $root = UserSessions::issueToken($usuario);

        $database->prepare("INSERT INTO `{$tablaCategorias}` (name, iconImage, color, meta) VALUES (?, ?, ?, ?)")
            ->execute(["{$prefijo}-categoria", '', '#000000', json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
        $categoria = (int) $database->lastInsertId();
        $ahora = date('Y-m-d H:i:s');
        foreach (['activa', 'eliminada'] as $sufijo) {
            $database->prepare("INSERT INTO `{$tablaNoticias}` (newsTitle, profilesTarget, content, category, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute(["{$prefijo}-{$sufijo}", '[]', 'zz-', $categoria, "{$prefijo}-{$sufijo}", $ahora, $usuario, NewsMapper::ACTIVE, json_encode(['baseLang' => 'es', 'draft' => 0, 'langData' => new \stdClass])]);
            $noticias[$sufijo] = (int) $database->lastInsertId();
        }
        $fila = function (int $id) use ($database, $tablaNoticias): array {
            $sentencia = $database->query("SELECT status, newsTitle FROM `{$tablaNoticias}` WHERE id = {$id}");
            $datos = $sentencia !== false ? $sentencia->fetch(\PDO::FETCH_ASSOC) : false;
            return is_array($datos) ? ['status' => (int) $datos['status'], 'title' => (string) $datos['newsTitle']] : ['status' => -1, 'title' => ''];
        };
        //Los campos que envía el formulario de edición del idioma base.
        $campos = fn (int $id, string $titulo): array => [
            'id' => (string) $id,
            'lang' => 'es',
            'profilesTarget[0]' => (string) UsersModel::TYPE_USER_ADMIN_GRAL,
            'category' => (string) $categoria,
            'newsTitle[es]' => $titulo,
            'content[es]' => 'zz-contenido',
        ];
        $todas = fn (string $estado): array => $pedir($camino('ajax-all') . '?page=1&per_page=100&status=' . $estado . '&newsTitle=' . rawurlencode($prefijo), $root);
        $eliminar = fn (int $id): array => $pedir($camino('actions-delete', ['id' => $id]), $root, ['id' => (string) $id]);
        $leer = fn (int $id): array => $pedir($camino('actions-mark-as-read', ['newsID' => $id]), $root, ['newsID' => (string) $id]);
        $inexistente = (int) $database->query("SELECT COALESCE(MAX(id), 0) + 1000 FROM `{$tablaNoticias}`")->fetchColumn();

        echoTerminal('[a] Canarios: una ACTIVE se abre, se guarda y sale en el JSON de todas');
        $formularioActiva = $pedir($camino('forms-edit', ['id' => $noticias['activa']]), $root);
        $check($formularioActiva['status'] === 200 && str_contains($formularioActiva['body'], "{$prefijo}-activa"), 'a1 CANARIO: su formulario de edición se abre', "HTTP {$formularioActiva['status']}");
        $guardarActiva = $pedir($camino('actions-edit'), $root, $campos($noticias['activa'], "{$prefijo}-activa-guardada"));
        $check($exito($guardarActiva) && $fila($noticias['activa'])['title'] === "{$prefijo}-activa-guardada", 'a2 CANARIO: su POST de edición se guarda', $contar($guardarActiva) . ', título ' . $fila($noticias['activa'])['title']);
        $todasAntes = $todas('ANY');
        $check($todasAntes['status'] === 200 && str_contains($todasAntes['body'], "{$prefijo}-activa-guardada") && str_contains($todasAntes['body'], "{$prefijo}-eliminada"), 'a3 CANARIO: con status=ANY salen las dos antes de eliminar', "HTTP {$todasAntes['status']}");

        echoTerminal('');
        echoTerminal('[b] Eliminarla');
        $borrada = $eliminar($noticias['eliminada']);
        $check($exito($borrada) && $fila($noticias['eliminada'])['status'] === NewsMapper::INACTIVE, 'b1 eliminarla la deja INACTIVE', $contar($borrada) . ', estado ' . $fila($noticias['eliminada'])['status']);

        echoTerminal('');
        echoTerminal('[c] El JSON de todas no la devuelve con ningún estado (antes de marcar nada como leído: lo leído no sale nunca)');
        foreach (['0', 'ANY', '1'] as $indice => $estado) {
            $lista = $todas($estado);
            $check($lista['status'] === 200 && !str_contains($lista['body'], "{$prefijo}-eliminada"), 'c' . ($indice + 1) . ($estado === 'ANY' ? ' DISCRIMINANTE' : '') . ": con status={$estado} no sale", "HTTP {$lista['status']}");
        }

        echoTerminal('');
        echoTerminal('[d] Eliminada, responde como un id que no existe');
        $formularioNada = $pedir($camino('forms-edit', ['id' => $inexistente]), $root);
        $formularioBorrada = $pedir($camino('forms-edit', ['id' => $noticias['eliminada']]), $root);
        $check($formularioBorrada['status'] === $formularioNada['status'] && $formularioBorrada['status'] !== 200, 'd1 su formulario de edición responde como el de un id que no existe', "eliminada HTTP {$formularioBorrada['status']}, inexistente HTTP {$formularioNada['status']}");

        $guardarNada = $pedir($camino('actions-edit'), $root, $campos($inexistente, "{$prefijo}-fantasma"));
        $guardarBorrada = $pedir($camino('actions-edit'), $root, $campos($noticias['eliminada'], "{$prefijo}-resucitada"));
        $check($igual($guardarBorrada, $guardarNada) && !$exito($guardarBorrada), 'd2 DISCRIMINANTE: su POST de edición responde como el de un id que no existe', 'eliminada ' . $contar($guardarBorrada) . ', inexistente ' . $contar($guardarNada));
        $despues = $fila($noticias['eliminada']);
        $check($despues['status'] === NewsMapper::INACTIVE && $despues['title'] === "{$prefijo}-eliminada", 'd3 y sigue INACTIVE, sin el título enviado', "estado {$despues['status']}, título {$despues['title']}");

        $borrarNada = $eliminar($inexistente);
        $borrarOtraVez = $eliminar($noticias['eliminada']);
        $check($igual($borrarOtraVez, $borrarNada) && !$exito($borrarOtraVez), 'd4 eliminarla otra vez responde como eliminar un id que no existe', 'eliminada ' . $contar($borrarOtraVez) . ', inexistente ' . $contar($borrarNada));
        $check($borrarOtraVez['status'] === 404 && $borrarNada['status'] === 404, 'd4b DISCRIMINANTE: y los dos con 404, no 403, también a root (H489)', 'eliminada ' . $contar($borrarOtraVez) . ', inexistente ' . $contar($borrarNada));

        $leerNada = $leer($inexistente);
        $leerBorrada = $leer($noticias['eliminada']);
        $leidas = (int) $database->query("SELECT COUNT(*) FROM `{$tablaLeidas}` WHERE news = {$noticias['eliminada']}")->fetchColumn();
        $check($igual($leerBorrada, $leerNada) && !$exito($leerBorrada) && $leidas === 0, 'd5 marcarla como leída responde como un id que no existe y no escribe', 'eliminada ' . $contar($leerBorrada) . ', inexistente ' . $contar($leerNada) . ", filas de leída {$leidas}");

        echoTerminal('');
        echoTerminal('[e] Canarios al final');
        $check(str_contains($todas('ANY')['body'], "{$prefijo}-activa-guardada"), 'e1 CANARIO: con status=ANY la ACTIVE sigue saliendo');
        $check($exito($leer($noticias['activa'])), 'e2 CANARIO: la ACTIVE se marca como leída');
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            foreach ($noticias as $id) {
                $database->exec("DELETE FROM `{$tablaLeidas}` WHERE news = " . (int) $id);
                $database->exec("DELETE FROM `{$tablaNoticias}` WHERE id = " . (int) $id);
            }
            if ($categoria > 0) {
                $database->exec("DELETE FROM `{$tablaCategorias}` WHERE id = " . (int) $categoria);
            }
            if ($usuario > 0) {
                $database->exec("DELETE FROM `{$tablaLeidas}` WHERE readerUser = " . (int) $usuario);
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
        $quedanLeidas = $usuario > 0 ? (int) $database->query("SELECT COUNT(*) FROM `{$tablaLeidas}` WHERE readerUser = " . (int) $usuario)->fetchColumn() : 0;
        $quedanUsuarios = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedanNoticias + $quedanCategorias + $quedanLeidas + $quedanUsuarios === 0, 'z1 no queda ninguna noticia, categoría, lectura ni usuario de la prueba', "noticias {$quedanNoticias}, categorías {$quedanCategorias}, lecturas {$quedanLeidas}, usuarios {$quedanUsuarios}");
    }

    return $balance();

})->setDescription('Una noticia eliminada responde como un id que no existe en su formulario, su acción de edición (sigue INACTIVE y sin el cambio), marcar como leída y eliminar otra vez, y no sale en el JSON de todas ni con status=0 o ANY (P380). Por HTTP; canarios con una ACTIVE.')->setEffects([CliActions::EFFECT_DATABASE])->register();
