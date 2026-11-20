<?php

//Un banner eliminado (borrado suave) no vuelve por la interfaz (P380): su formulario, su acción de edición y eliminarlo otra
//vez responden como un id que no existe, y no sale en los listados ni pidiendo status=0 o ANY. Por HTTP.
//@areas src/app/classes/PiecesPHP/BuiltIn/

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController;
use PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerPublicController;
use PiecesPHP\BuiltIn\Banner\Mappers\BuiltInBannerMapper;
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

CliActions::make('unit-tests:core/banners-deleted', function ($args) {

    echoTerminal("\e[33m[TEST:BannersDeleted] Un banner eliminado no vuelve por la interfaz\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
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
    if (!$check($base !== '' && $database !== null, 'a0 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }

    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::headerName();
    $relativa = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $camino = fn (string $sufijo, array $parametros = []): string => $relativa(BuiltInBannerController::routeName($sufijo, $parametros, true));
    $pedir = function (string $path, ?string $jwt, ?array $campos = null) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [],
        ];
        if ($campos !== null) {
            //Multipart, como el FormData del navegador.
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = $campos;
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    $json = fn (array $r): array => (array) (json_decode($r['body'], true) ?: []);
    $mensaje = fn (array $r): string => (string) ($json($r)['message'] ?? '');
    $exito = fn (array $r): ?bool => isset($json($r)['success']) ? (bool) $json($r)['success'] : null;
    $resumen = fn (array $r): string => "HTTP {$r['status']} «" . ($mensaje($r) !== '' ? $mensaje($r) : mb_substr(trim((string) preg_replace('/\s+/', ' ', strip_tags($r['body']))), 0, 60)) . '»';

    $prefijo = 'zz-prueba-banners-borrados-' . bin2hex(random_bytes(3));
    $tablaBanners = BuiltInBannerMapper::TABLE;
    $usuario = 0;
    $banners = [];

    try {
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Banners';
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

        $sembrar = function (string $sufijo) use ($database, $tablaBanners, $prefijo, $usuario, &$banners): int {
            $ahora = date('Y-m-d H:i:s');
            $database->prepare("INSERT INTO `{$tablaBanners}` (title, content, link, desktopImage, mobileImage, orderPosition, folder, createdAt, createdBy, status, meta)"
                . ' VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?)')
                ->execute(["{$prefijo}-{$sufijo}", 'zz-', '', 'statics/images/open_graph.jpg', '', "{$prefijo}-{$sufijo}", $ahora, $usuario, BuiltInBannerMapper::ACTIVE, json_encode(['langData' => new \stdClass])]);
            $banners[$sufijo] = (int) $database->lastInsertId();
            return $banners[$sufijo];
        };
        $columna = function (int $id, string $campo) use ($database, $tablaBanners): string {
            $consulta = $database->prepare("SELECT `{$campo}` FROM `{$tablaBanners}` WHERE id = ?");
            return $consulta->execute([$id]) ? (string) $consulta->fetchColumn() : '';
        };
        $campos = fn (int $id, string $titulo): array => [
            'id' => (string) $id,
            'lang' => Config::get_default_lang(),
            'title' => $titulo,
            'content' => 'zz-contenido',
            'orderPosition' => '0',
            'link' => '',
        ];
        $listado = function (string $estado) use ($pedir, $camino, $root, $prefijo): array {
            return $pedir($camino('ajax-all') . '?page=1&per_page=100&title=' . rawurlencode($prefijo) . '&status=' . $estado, $root);
        };
        $activa = $sembrar('activa');
        $borrada = $sembrar('borrada');
        $inexistente = (int) $database->query("SELECT COALESCE(MAX(id), 0) + 1000 FROM `{$tablaBanners}`")->fetchColumn();

        echoTerminal('[a] CANARIOS: un banner ACTIVE se abre, se guarda y sale en los listados');
        $formularioActiva = $pedir($camino('forms-edit', ['id' => $activa]), $root);
        $check($formularioActiva['status'] === 200 && str_contains($formularioActiva['body'], "{$prefijo}-activa"), 'a1 su formulario de edición se abre', "HTTP {$formularioActiva['status']}");
        $guardarActiva = $pedir($camino('actions-edit'), $root, $campos($activa, "{$prefijo}-activa-editada"));
        $check($exito($guardarActiva) === true && $columna($activa, 'title') === "{$prefijo}-activa-editada" && (int) $columna($activa, 'status') === BuiltInBannerMapper::ACTIVE, 'a2 su POST de edición guarda y sigue ACTIVE', $resumen($guardarActiva));
        $check(str_contains($listado('ANY')['body'], "{$prefijo}-activa"), 'a3 sale en el listado con status=ANY');
        $urlPublica = $relativa(BuiltInBannerPublicController::routeName('ajax-all', [], true)) . '?page=1&per_page=100&title=' . rawurlencode($prefijo);
        $check(str_contains($pedir($urlPublica, null)['body'], "{$prefijo}-activa"), 'a4 el JSON público, sin sesión, la pinta');

        echoTerminal('');
        echoTerminal('[b] Eliminado: responde como un id que no existe (P380)');
        $eliminar = fn (int $id): array => $pedir($camino('actions-delete', ['id' => $id]), $root, ['id' => (string) $id]);
        $eliminacion = $eliminar($borrada);
        $check($exito($eliminacion) === true && (int) $columna($borrada, 'status') === BuiltInBannerMapper::INACTIVE, 'b1 eliminarlo lo deja INACTIVE', $resumen($eliminacion));
        $formularioNada = $pedir($camino('forms-edit', ['id' => $inexistente]), $root);
        $formularioBorrada = $pedir($camino('forms-edit', ['id' => $borrada]), $root);
        $check($formularioBorrada['status'] === $formularioNada['status'] && $formularioBorrada['status'] !== 200, 'b2 su formulario responde como el de un id que no existe', "eliminado HTTP {$formularioBorrada['status']}, inexistente HTTP {$formularioNada['status']}");
        $guardarNada = $pedir($camino('actions-edit'), $root, $campos($inexistente, "{$prefijo}-nada"));
        $guardarBorrada = $pedir($camino('actions-edit'), $root, $campos($borrada, "{$prefijo}-resucitada"));
        $check($guardarBorrada['status'] === $guardarNada['status'] && $mensaje($guardarBorrada) === $mensaje($guardarNada) && $exito($guardarBorrada) !== true, 'b3 DISCRIMINANTE: su POST de edición responde como el de un id que no existe', 'eliminado ' . $resumen($guardarBorrada) . ', inexistente ' . $resumen($guardarNada));
        $check((int) $columna($borrada, 'status') === BuiltInBannerMapper::INACTIVE && $columna($borrada, 'title') !== "{$prefijo}-resucitada", 'b4 y sigue INACTIVE, sin el título enviado', 'estado ' . $columna($borrada, 'status') . ', título ' . $columna($borrada, 'title'));
        $borrarNada = $eliminar($inexistente);
        $borrarOtraVez = $eliminar($borrada);
        $check($borrarOtraVez['status'] === $borrarNada['status'] && $mensaje($borrarOtraVez) === $mensaje($borrarNada) && $exito($borrarOtraVez) !== true, 'b5 eliminarlo otra vez responde como eliminar un id que no existe', 'eliminado ' . $resumen($borrarOtraVez) . ', inexistente ' . $resumen($borrarNada));

        echoTerminal('');
        echoTerminal('[c] Los listados no lo devuelven con ningún filtro de estado');
        $cualquiera = $listado('ANY');
        $check($cualquiera['status'] === 200 && str_contains($cualquiera['body'], "{$prefijo}-activa"), 'c1 CANARIO: con status=ANY sale el activo', "HTTP {$cualquiera['status']}");
        $check(!str_contains($cualquiera['body'], "{$prefijo}-borrada"), 'c2 DISCRIMINANTE: con status=ANY no sale el eliminado');
        $eliminados = $listado((string) BuiltInBannerMapper::INACTIVE);
        $check($eliminados['status'] === 200 && !str_contains($eliminados['body'], "{$prefijo}-borrada"), 'c3 con status=0 tampoco', "HTTP {$eliminados['status']}");
        $tabla = $pedir($camino('datatables') . '?draw=1&start=0&length=100&search[value]=' . rawurlencode($prefijo), $root);
        $check(str_contains($tabla['body'], "{$prefijo}-activa") && !str_contains($tabla['body'], "{$prefijo}-borrada"), 'c4 DataTables da el activo y no el eliminado', "HTTP {$tabla['status']}");
        $publico = $pedir($urlPublica, null);
        $check($publico['status'] === 200 && !str_contains($publico['body'], "{$prefijo}-borrada"), 'c5 el JSON público, sin sesión, no lo pinta', "HTTP {$publico['status']}");
        $publicoAny = $pedir($urlPublica . '&status=ANY', $root);
        $check($publicoAny['status'] === 200 && str_contains($publicoAny['body'], "{$prefijo}-activa") && !str_contains($publicoAny['body'], "{$prefijo}-borrada"), 'c6 ni con root pidiendo status=ANY al JSON público', "HTTP {$publicoAny['status']}");
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ([[$tablaBanners, $banners], [UsersModel::TABLE, $usuario > 0 ? [$usuario] : []]] as [$tablaReferencia, $ids]) {
                foreach ($ids as $id) {
                    $aprobaciones->resetAll();
                    //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                    $aprobaciones->delete(new WhereSegment([
                        new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tablaReferencia),
                        new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                    ]))->execute();
                }
            }
            foreach ($banners as $id) {
                $database->exec("DELETE FROM `{$tablaBanners}` WHERE id = " . (int) $id);
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
        $quedanBanners = (int) $database->query("SELECT COUNT(*) FROM `{$tablaBanners}` WHERE title LIKE " . $database->quote("{$prefijo}%") . ' OR folder LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $quedanAprobaciones = 0;
        foreach ([[$tablaBanners, $banners], [UsersModel::TABLE, $usuario > 0 ? [$usuario] : []]] as [$tablaReferencia, $ids]) {
            foreach ($ids as $id) {
                $quedanAprobaciones += (int) $database->query('SELECT COUNT(*) FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ' . $database->quote($tablaReferencia) . ' AND referenceValue = ' . $database->quote((string) $id))->fetchColumn();
            }
        }
        $quedanUsuarios = UsersModel::model();
        $quedanUsuarios->resetAll();
        $quedanUsuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $check($quedanBanners === 0 && $quedanAprobaciones === 0 && count((array) $quedanUsuarios->result()) === 0, 'z1 no queda ningún banner, aprobación ni usuario de la prueba', "banners {$quedanBanners}, aprobaciones {$quedanAprobaciones}");
    }

    return $balance();

})->setDescription('Un banner eliminado responde como un id que no existe en su formulario, su acción de edición y al eliminarlo otra vez, y no sale en los listados del panel ni en el JSON público, tampoco con status=0 o ANY (P380). Con canarios de un banner activo. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
