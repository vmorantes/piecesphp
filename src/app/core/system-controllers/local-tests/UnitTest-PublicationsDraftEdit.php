<?php

//Guardar sin tocar no publica un borrador (H421), un ajeno no edita por POST y lo eliminado no vuelve (P380); envía lo que pinta el formulario.
//@areas src/app/classes/Publications/ src/app/classes/API/
//@requires-module API\APIRoutes
//@requires-module Organizations\OrganizationsRoutes
//@requires-module SystemApprovals\SystemApprovalsRoutes

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Publications\Controllers\PublicationsController;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/publications-draft-edit', function ($args) {

    echoTerminal("\e[33m[TEST:PublicationsDraftEdit] Editar una publicación respeta su casilla de borrador y su autoría\e[39m");
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
        $url = PublicationsController::routeName($sufijo, $parametros, true);
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $relativa = function (string $url) use ($prefijoBase): string {
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
            //Multipart, como el FormData del navegador.
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = $campos;
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    //Los campos que el navegador enviaría del formulario del idioma base: una casilla sin marcar no viaja.
    $camposDelFormulario = function (string $html, string $idioma): ?array {
        $documento = new \DOMDocument();
        $previo = libxml_use_internal_errors(true);
        $cargado = $documento->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors();
        libxml_use_internal_errors($previo);
        if (!$cargado) {
            return null;
        }
        $xpath = new \DOMXPath($documento);
        $formularios = $xpath->query('//form[@data-content-form]');
        if ($formularios === false) {
            return null;
        }
        foreach ($formularios as $formulario) {
            if (!$formulario instanceof \DOMElement) {
                continue;
            }
            $idiomaFormulario = $xpath->query('.//input[@name="lang"]', $formulario);
            $nodoIdioma = $idiomaFormulario !== false ? $idiomaFormulario->item(0) : null;
            if (!$nodoIdioma instanceof \DOMElement || $nodoIdioma->getAttribute('value') !== $idioma) {
                continue;
            }
            $campos = [];
            $nodos = $xpath->query('.//input[@name] | .//select[@name] | .//textarea[@name]', $formulario);
            foreach ($nodos !== false ? $nodos : [] as $nodo) {
                if (!$nodo instanceof \DOMElement) {
                    continue;
                }
                $nombre = $nodo->getAttribute('name');
                if ($nodo->tagName === 'textarea') {
                    $campos[$nombre] = $nodo->textContent;
                } elseif ($nodo->tagName === 'select') {
                    $opciones = $xpath->query('.//option', $nodo);
                    $valor = '';
                    foreach ($opciones !== false ? $opciones : [] as $indice => $opcion) {
                        if ($opcion instanceof \DOMElement && ($indice === 0 || $opcion->hasAttribute('selected'))) {
                            $valor = $opcion->hasAttribute('value') ? $opcion->getAttribute('value') : $opcion->textContent;
                        }
                    }
                    $campos[$nombre] = $valor;
                } else {
                    $tipo = strtolower($nodo->getAttribute('type'));
                    if (in_array($tipo, ['file', 'submit', 'button'], true) || (in_array($tipo, ['checkbox', 'radio'], true) && !$nodo->hasAttribute('checked'))) {
                        continue;
                    }
                    $campos[$nombre] = $nodo->getAttribute('value');
                }
            }
            return $campos;
        }
        return null;
    };
    //El calendario del panel convierte la fecha al formato de la acción antes de enviar (d-m-Y).
    $comoCalendario = function (array $campos): array {
        if (isset($campos['publicDate']) && is_string($campos['publicDate'])) {
            $fecha = \DateTime::createFromFormat('Y-m-d h:i:s A', $campos['publicDate']);
            $campos['publicDate'] = $fecha !== false ? $fecha->format('d-m-Y') : $campos['publicDate'];
        }
        return $campos;
    };

    $prefijo = 'zz-prueba-publicaciones-borrador-' . bin2hex(random_bytes(3));
    $tablaPublicaciones = PublicationMapper::TABLE;
    $tablaUsuarios = UsersModel::TABLE;
    $usuarios = [];
    $publicacion = 0;
    $publicacionesBloque = [];
    $carpetaArchivos = '';

    try {
        $crearUsuario = function (string $sufijo, int $tipo) use ($prefijo, &$usuarios): string {
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
            $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            return UserSessions::issueToken((int) $u->id);
        };
        $root = $crearUsuario('root', UsersModel::TYPE_USER_ROOT);
        //Autor y ajeno, del mismo tipo y sin «editar todas»: los separa solo la relación con la publicación.
        $autor = $crearUsuario('autor', UsersModel::TYPE_USER_COMUNICACIONES);
        $ajeno = $crearUsuario('ajeno', UsersModel::TYPE_USER_COMUNICACIONES);
        $check(!in_array(UsersModel::TYPE_USER_COMUNICACIONES, PublicationMapper::CAN_EDIT_ALL), 'p2 el tipo de autor y ajeno no edita todas: la autoría es lo único que los separa');

        $categoria = $database->query('SELECT id FROM `' . \Publications\Mappers\PublicationCategoryMapper::TABLE . '` ORDER BY id LIMIT 1')->fetchColumn();
        if (!$check($categoria !== false, 'p3 hay una categoría de publicaciones en la que sembrar')) {
            return $balance();
        }
        $ahora = date('Y-m-d H:i:s');
        $database->prepare("INSERT INTO `{$tablaPublicaciones}` (title, content, seoDescription, author, category, mainImage, thumbImage, ogImage, folder, visits, publicDate, createdAt, createdBy, status, featured, meta)"
            . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, 0, ?)')
            ->execute(["{$prefijo}-publicacion", 'zz-contenido', 'zz-', $usuarios['autor'], (int) $categoria, '', '', '', "{$prefijo}-publicacion", $ahora, $ahora, $usuarios['autor'], PublicationMapper::DRAFT, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
        $publicacion = (int) $database->lastInsertId();
        $estado = function () use ($database, $tablaPublicaciones, $publicacion): int {
            return (int) $database->query("SELECT status FROM `{$tablaPublicaciones}` WHERE id = {$publicacion}")->fetchColumn();
        };
        $guardar = function (string $jwt, ?callable $tocar = null) use ($pedir, $camino, $camposDelFormulario, $comoCalendario, $publicacion): array {
            $formulario = $pedir($camino('forms-edit', ['id' => $publicacion]), $jwt);
            $campos = $formulario['status'] === 200 ? $camposDelFormulario($formulario['body'], 'es') : null;
            if ($campos === null) {
                return ['formulario' => $formulario['status'], 'campos' => null, 'status' => 0, 'json' => null];
            }
            $campos = $comoCalendario($campos);
            if ($tocar !== null) {
                $campos = $tocar($campos);
            }
            $respuesta = $pedir($camino('actions-edit'), $jwt, $campos);
            return ['formulario' => $formulario['status'], 'campos' => $campos, 'status' => $respuesta['status'], 'json' => json_decode($respuesta['body'], true)];
        };
        $exito = function (array $guardado): bool {
            return $guardado['status'] === 200 && is_array($guardado['json']) && ($guardado['json']['success'] ?? false) === true;
        };
        $detalle = function (array $guardado) use ($estado): string {
            return "formulario HTTP {$guardado['formulario']}, acción HTTP {$guardado['status']}, estado " . $estado() . ', campos ' . implode(',', array_keys((array) $guardado['campos']));
        };

        echoTerminal('[a] Guardar el formulario de un borrador');
        $check($estado() === PublicationMapper::DRAFT, 'a1 la publicación sembrada es un borrador');
        $sinTocar = $guardar($root);
        $check($sinTocar['campos'] !== null && array_key_exists('draft', $sinTocar['campos']), 'a2 el formulario de edición pinta la casilla de borrador, marcada, con el nombre que lee la acción', $detalle($sinTocar));
        $check($exito($sinTocar), 'a3 CANARIO: guardar sin tocar nada se acepta', $detalle($sinTocar));
        $check($estado() === PublicationMapper::DRAFT, 'a4 y sigue en borrador: guardar no publica', $detalle($sinTocar));

        $desmarcada = $guardar($root, function (array $campos): array {
            unset($campos['draft']);
            return $campos;
        });
        $check($exito($desmarcada) && $estado() === PublicationMapper::ACTIVE, 'a5 con la casilla desmarcada, queda publicada', $detalle($desmarcada));

        $publicada = $guardar($root);
        $check($publicada['campos'] !== null && !array_key_exists('draft', $publicada['campos']), 'a6 publicada, el formulario pinta la casilla sin marcar', $detalle($publicada));
        $marcada = $guardar($root, function (array $campos): array {
            $campos['draft'] = 'yes';
            return $campos;
        });
        $check($exito($marcada) && $estado() === PublicationMapper::DRAFT, 'a7 con la casilla marcada, vuelve a borrador', $detalle($marcada));

        echoTerminal('');
        echoTerminal('[b] La autoría: un usuario del mismo tipo, sin relación con la publicación');
        $delAutor = $guardar($autor);
        $check($exito($delAutor) && $estado() === PublicationMapper::DRAFT, 'b1 CANARIO: el autor, del mismo tipo, guarda la suya', $detalle($delAutor));
        $check($pedir($camino('forms-edit', ['id' => $publicacion]), $ajeno)['status'] === 403, 'b2 el ajeno no abre su formulario de edición');
        $intruso = $pedir($camino('actions-edit'), $ajeno, array_merge((array) $delAutor['campos'], ['title' => "{$prefijo}-intruso"]));
        $check($intruso['status'] === 403, 'b3 y su POST de edición se rechaza con 403', "HTTP {$intruso['status']}");
        $titulo = (string) $database->query("SELECT title FROM `{$tablaPublicaciones}` WHERE id = {$publicacion}")->fetchColumn();
        $check($titulo !== "{$prefijo}-intruso" && $estado() === PublicationMapper::DRAFT, 'b4 la publicación queda como estaba', "título {$titulo}, estado " . $estado());

        echoTerminal('');
        echoTerminal('[c] Lo eliminado no vuelve por la interfaz (P380): responde como un id que no existe');
        $activa = $guardar($root, function (array $campos): array {
            unset($campos['draft']);
            return $campos;
        });
        $check($exito($activa) && $estado() === PublicationMapper::ACTIVE, 'c1 CANARIO: una ACTIVE se abre y se guarda', $detalle($activa));
        $camposEliminada = (array) $activa['campos'];
        $urlApi = \API\Controllers\APIController::routeName('publications-actions', ['context' => 'publications', 'actionType' => 'detail'], true);
        $pathApi = str_contains($urlApi, '://') ? (string) parse_url($urlApi, \PHP_URL_PATH) : $urlApi;
        $detalleApi = ($prefijoBase !== '' && str_starts_with($pathApi, $prefijoBase) ? substr($pathApi, strlen($prefijoBase)) : $pathApi) . '?id=' . $publicacion;
        $apiActiva = $pedir($detalleApi, $root);
        $check($apiActiva['status'] === 200 && str_contains($apiActiva['body'], "{$prefijo}-publicacion"), 'c1b CANARIO: la API de detalle la da mientras está activa', "HTTP {$apiActiva['status']}");
        $urlBloque = \PiecesPHP\Components\Controllers\ComponentProvider::routeName('provide', ['group' => 'publications'], true);
        $pathBloque = str_contains($urlBloque, '://') ? (string) parse_url($urlBloque, \PHP_URL_PATH) : $urlBloque;
        $bloque = ($prefijoBase !== '' && str_starts_with($pathBloque, $prefijoBase) ? substr($pathBloque, strlen($prefijoBase)) : $pathBloque) . '?slugs[]=' . rawurlencode(PublicationMapper::getEncryptIDForSlug($publicacion));
        $bloqueActiva = $pedir($bloque, $root);
        $check($bloqueActiva['status'] === 200 && str_contains($bloqueActiva['body'], "{$prefijo}-publicacion"), 'c1c CANARIO: el bloque público de publicaciones la pinta mientras está activa', "HTTP {$bloqueActiva['status']}");
        //Dos archivos en su carpeta: uno privado (lo sirve PHP tras el validador) y uno con su nombre público (el servidor web).
        $carpetaArchivos = append_to_path_system(append_to_path_system((string) get_config('upload_dir'), PublicationsController::UPLOAD_DIR), "{$prefijo}-publicacion");
        $privado = append_to_path_system($carpetaArchivos, 'zz.txt') . \PiecesPHP\Core\Statics\ProtectedUploads::suffix();
        $publico = append_to_path_system($carpetaArchivos, 'zz-publico.txt');
        $sembrados = (is_dir($carpetaArchivos) || mkdir($carpetaArchivos, 0775, true)) && file_put_contents($privado, "{$prefijo}-contenido") !== false && file_put_contents($publico, "{$prefijo}-publico") !== false;
        $urlArchivo = $relativa(append_to_url((string) get_config('upload_dir_url'), PublicationsController::UPLOAD_DIR . "/{$prefijo}-publicacion/zz.txt"));
        $archivoActiva = $pedir($urlArchivo, $root);
        $check($sembrados && $archivoActiva['status'] === 200 && str_contains($archivoActiva['body'], "{$prefijo}-contenido"), 'c1d CANARIO: root recibe su archivo privado mientras está activa', "HTTP {$archivoActiva['status']}");
        $eliminar = fn (int $id): array => $pedir($camino('actions-delete', ['id' => $id]), $root, ['id' => (string) $id]);
        $mensaje = fn (array $r): string => (string) (json_decode($r['body'], true)['message'] ?? '');
        $borrada = $eliminar($publicacion);
        $check($borrada['status'] === 200 && $estado() === PublicationMapper::INACTIVE, 'c2 eliminarla la deja INACTIVE', "HTTP {$borrada['status']}, estado " . $estado());
        $inexistente = (int) $database->query("SELECT COALESCE(MAX(id), 0) + 1000 FROM `{$tablaPublicaciones}`")->fetchColumn();
        $formularioNada = $pedir($camino('forms-edit', ['id' => $inexistente]), $root);
        $formularioBorrada = $pedir($camino('forms-edit', ['id' => $publicacion]), $root);
        $check($formularioBorrada['status'] === $formularioNada['status'] && $formularioBorrada['status'] !== 200, 'c3 su formulario de edición responde como el de un id que no existe', "eliminada HTTP {$formularioBorrada['status']}, inexistente HTTP {$formularioNada['status']}");
        $guardarNada = $pedir($camino('actions-edit'), $root, array_merge($camposEliminada, ['id' => (string) $inexistente]));
        $guardarBorrada = $pedir($camino('actions-edit'), $root, array_merge($camposEliminada, ['title' => "{$prefijo}-resucitada"]));
        $titulo = (string) $database->query("SELECT title FROM `{$tablaPublicaciones}` WHERE id = {$publicacion}")->fetchColumn();
        $check($guardarBorrada['status'] === $guardarNada['status'] && $mensaje($guardarBorrada) === $mensaje($guardarNada) && ($guardarBorrada['status'] !== 200 || (json_decode($guardarBorrada['body'], true)['success'] ?? null) === false), 'c4 DISCRIMINANTE: su POST de edición responde como el de un id que no existe', "eliminada HTTP {$guardarBorrada['status']} «{$mensaje($guardarBorrada)}», inexistente HTTP {$guardarNada['status']} «{$mensaje($guardarNada)}»");
        $check($estado() === PublicationMapper::INACTIVE && $titulo !== "{$prefijo}-resucitada", 'c5 y sigue INACTIVE, sin el título enviado', "estado " . $estado() . ", título {$titulo}");
        $archivoBorrada = $pedir($urlArchivo, $root);
        $check($archivoBorrada['status'] !== 200 && !str_contains($archivoBorrada['body'], "{$prefijo}-contenido"), 'c5d DISCRIMINANTE: eliminada, su archivo privado no se sirve ni a root', "HTTP {$archivoBorrada['status']}");
        $check(!is_file($publico) && is_file(\PiecesPHP\Core\Statics\ProtectedUploads::privatePath($publico)), 'c5e al eliminarla, el archivo con nombre público pasa a privado: el servidor web ya no lo sirve solo', is_file($publico) ? 'sigue con su nombre público' : 'no está ni privado');
        $apiBorrada = $pedir($detalleApi, $root);
        $check($apiBorrada['status'] === 404 && !str_contains($apiBorrada['body'], "{$prefijo}-publicacion"), 'c5b la API de detalle no la da eliminada: 404, como lo que no existe', "HTTP {$apiBorrada['status']}");
        $bloqueBorrada = $pedir($bloque, $root);
        $check($bloqueBorrada['status'] === 200 && !str_contains($bloqueBorrada['body'], "{$prefijo}-publicacion"), 'c5c y eliminada no la pinta, como un id que no existe', "HTTP {$bloqueBorrada['status']}");
        $borrarNada = $eliminar($inexistente);
        $borrarOtraVez = $eliminar($publicacion);
        $check($borrarOtraVez['status'] === $borrarNada['status'] && $mensaje($borrarOtraVez) === $mensaje($borrarNada), 'c6 eliminarla otra vez responde como eliminar un id que no existe', "eliminada HTTP {$borrarOtraVez['status']} «{$mensaje($borrarOtraVez)}», inexistente HTTP {$borrarNada['status']} «{$mensaje($borrarNada)}»");

        echoTerminal('');
        echoTerminal('[d] El bloque público de publicaciones enseña lo mismo que la vista individual');
        $sembrarBloque = function (string $sufijo, int $estado, ?string $inicio) use ($database, $tablaPublicaciones, $prefijo, $categoria, $ahora, $usuarios, &$publicacionesBloque): void {
            $database->prepare("INSERT INTO `{$tablaPublicaciones}` (title, content, seoDescription, author, category, mainImage, thumbImage, ogImage, folder, visits, publicDate, startDate, createdAt, createdBy, status, featured, meta)"
                . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?, 0, ?)')
                ->execute(["{$prefijo}-bloque-{$sufijo}", 'zz-contenido', 'zz-', $usuarios['autor'], (int) $categoria, '', '', '', "{$prefijo}-bloque-{$sufijo}", $ahora, $inicio, $ahora, $usuarios['autor'], $estado, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
            $publicacionesBloque[$sufijo] = (int) $database->lastInsertId();
            //Aprobadas las tres: lo que las separa es el estado y la fecha.
            $database->prepare('INSERT INTO `' . SystemApprovalsMapper::TABLE . '` (referenceAlias, referenceValue, referenceTable, referenceDate, createdAt, createdBy, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute(["{$prefijo}-bloque-{$sufijo}", (string) $publicacionesBloque[$sufijo], $tablaPublicaciones, $ahora, $ahora, $usuarios['autor'], SystemApprovalsMapper::STATUS_APPROVED]);
        };
        $sembrarBloque('visible', PublicationMapper::ACTIVE, null);
        $sembrarBloque('borrador', PublicationMapper::DRAFT, null);
        $sembrarBloque('programada', PublicationMapper::ACTIVE, date('Y-m-d H:i:s', strtotime('+30 days')));
        $slugs = array_map(fn (int $id): string => 'slugs[]=' . rawurlencode(PublicationMapper::getEncryptIDForSlug($id)), array_merge(array_values($publicacionesBloque), [$publicacion]));
        $bloqueTodas = strtok($bloque, '?') . '?' . implode('&', $slugs);
        $pedirSinSesion = function (string $path) use ($base): array {
            $handle = \Terminal\TestRun::curlInit();
            \Terminal\TestRun::curlSetoptArray($handle, [
                CURLOPT_URL => $base . '/' . ltrim($path, '/'),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => 30,
            ]);
            $body = curl_exec($handle);
            return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
        };
        $salen = function (string $html) use ($prefijo): array {
            return preg_match_all('/' . preg_quote($prefijo, '/') . '-(bloque-[a-z]+|publicacion)/', $html, $m) > 0 ? array_values(array_unique($m[1])) : [];
        };
        $anonimo = $pedirSinSesion($bloqueTodas);
        $vistoAnonimo = $salen($anonimo['body']);
        $check($anonimo['status'] === 200 && in_array('bloque-visible', $vistoAnonimo, true), 'd1 CANARIO: sin sesión, la publicada y aprobada sale', "HTTP {$anonimo['status']}, salen " . (json_encode($vistoAnonimo) ?: ''));
        $check(!in_array('bloque-borrador', $vistoAnonimo, true) && !in_array('bloque-programada', $vistoAnonimo, true), 'd2 DISCRIMINANTE: sin sesión, ni el borrador ni la programada', 'salen ' . (json_encode($vistoAnonimo) ?: ''));
        $conRoot = $pedir($bloqueTodas, $root);
        $vistoRoot = $salen($conRoot['body']);
        $check(in_array('bloque-borrador', $vistoRoot, true) && in_array('bloque-programada', $vistoRoot, true), 'd3 con root, el borrador y la programada salen: los previsualiza, como en la vista individual', 'salen ' . (json_encode($vistoRoot) ?: ''));
        $check(!in_array('publicacion', $vistoAnonimo, true) && !in_array('publicacion', $vistoRoot, true), 'd4 la eliminada no sale con nadie', 'sin sesión ' . (json_encode($vistoAnonimo) ?: '') . ', root ' . (json_encode($vistoRoot) ?: ''));

        echoTerminal('');
        echoTerminal('[e] Los listados no devuelven lo eliminado, tampoco con status=0 o ANY (P380)');
        //Aprobada, como las del bloque: lo único que la separa de ellas es estar eliminada.
        $tablaAprobaciones = SystemApprovalsMapper::TABLE;
        $database->prepare("DELETE FROM `{$tablaAprobaciones}` WHERE referenceTable = ? AND referenceValue = ?")->execute([$tablaPublicaciones, (string) $publicacion]);
        $database->prepare("INSERT INTO `{$tablaAprobaciones}` (referenceAlias, referenceValue, referenceTable, referenceDate, createdAt, createdBy, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute(["{$prefijo}-publicacion", (string) $publicacion, $tablaPublicaciones, $ahora, $ahora, $usuarios['autor'], SystemApprovalsMapper::STATUS_APPROVED]);
        foreach (['panel' => PublicationsController::routeName('ajax-all', [], true), 'público' => \Publications\Controllers\PublicationsPublicController::routeName('ajax-all', [], true)] as $zona => $urlListado) {
            $consulta = fn (string $estadoPedido): array => $salen($pedir($relativa($urlListado) . '?per_page=100&title=' . rawurlencode($prefijo) . '&status=' . $estadoPedido, $root)['body']);
            $cualquiera = $consulta('ANY');
            $check(in_array('bloque-borrador', $cualquiera, true) && in_array('bloque-visible', $cualquiera, true), "e1 CANARIO: {$zona}, con status=ANY root recibe el borrador y la visible", json_encode($cualquiera) ?: '');
            $check(!in_array('publicacion', $cualquiera, true), "e2 DISCRIMINANTE: {$zona}, con status=ANY no sale la eliminada", json_encode($cualquiera) ?: '');
            $eliminadas = $consulta((string) PublicationMapper::INACTIVE);
            $check(!in_array('publicacion', $eliminadas, true), "e3 {$zona}, con status=0 tampoco", json_encode($eliminadas) ?: '');
        }
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ([[$tablaPublicaciones, array_merge($publicacion > 0 ? [$publicacion] : [], array_values($publicacionesBloque))], [$tablaUsuarios, $usuarios]] as [$tablaReferencia, $ids]) {
                foreach ($ids as $id) {
                    $aprobaciones->resetAll();
                    //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                    $aprobaciones->delete(new WhereSegment([
                        new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tablaReferencia),
                        new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                    ]))->execute();
                }
            }
            if ($carpetaArchivos !== '' && is_dir($carpetaArchivos)) {
                foreach (glob($carpetaArchivos . '/*') ?: [] as $archivo) {
                    //RETORNO-IGNORADO: lo comprueba z2 mirando si queda la carpeta.
                    unlink($archivo);
                }
                //RETORNO-IGNORADO: lo comprueba z2 mirando si queda la carpeta.
                rmdir($carpetaArchivos);
            }
            if ($publicacion > 0) {
                $database->exec("DELETE FROM `{$tablaPublicaciones}` WHERE id = {$publicacion}");
            }
            foreach ($publicacionesBloque as $idBloque) {
                $database->exec("DELETE FROM `{$tablaPublicaciones}` WHERE id = " . (int) $idBloque);
            }
            foreach ($usuarios as $id) {
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
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaPublicaciones}` WHERE title LIKE " . $database->quote("{$prefijo}%"))->fetchColumn()
         + (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $quedanAprobaciones = 0;
        foreach (array_merge($publicacion > 0 ? [$publicacion] : [], array_values($publicacionesBloque)) as $id) {
            $tablaReferencia = $tablaPublicaciones;
            $quedanAprobaciones += (int) $database->query('SELECT COUNT(*) FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ' . $database->quote($tablaReferencia) . ' AND referenceValue = ' . $database->quote((string) $id))->fetchColumn();
        }
        $check($quedan === 0 && $quedanAprobaciones === 0, 'z1 no queda ninguna publicación, aprobación ni usuario de la prueba', "quedan {$quedan}, aprobaciones {$quedanAprobaciones}");
        $check($carpetaArchivos === '' || !is_dir($carpetaArchivos), 'z2 no queda la carpeta de archivos de la prueba', $carpetaArchivos);
    }

    return $balance();

})->setDescription('Editar una publicación respeta su casilla de borrador (guardar sin tocar no publica; desmarcada publica; marcada vuelve a borrador), un usuario del mismo tipo sin relación con una publicación ajena no la edita por POST (403), y una eliminada responde como un id que no existe en su formulario, su acción de edición, eliminar, la API de detalle y el bloque público, no sale en los listados ni con status=0 o ANY, y sus archivos no se sirven (P380). Por HTTP, con los campos que pinta el formulario.')->setEffects([CliActions::EFFECT_DATABASE])->register();
