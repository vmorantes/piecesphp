<?php

//Guardar sin tocar no publica un borrador (H421) y un ajeno no edita por POST; envía lo que pinta el formulario.
//@areas src/app/classes/Publications/

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
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ([[$tablaPublicaciones, $publicacion > 0 ? [$publicacion] : []], [$tablaUsuarios, $usuarios]] as [$tablaReferencia, $ids]) {
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
        foreach ($publicacion > 0 ? [[$tablaPublicaciones, $publicacion]] : [] as [$tablaReferencia, $id]) {
            $quedanAprobaciones += (int) $database->query('SELECT COUNT(*) FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ' . $database->quote($tablaReferencia) . ' AND referenceValue = ' . $database->quote((string) $id))->fetchColumn();
        }
        $check($quedan === 0 && $quedanAprobaciones === 0, 'z1 no queda ninguna publicación, aprobación ni usuario de la prueba', "quedan {$quedan}, aprobaciones {$quedanAprobaciones}");
    }

    return $balance();

})->setDescription('Editar una publicación respeta su casilla de borrador (guardar sin tocar no publica; desmarcada publica; marcada vuelve a borrador), y un usuario del mismo tipo sin relación con una publicación ajena no la edita por POST (403). Por HTTP, con los campos que pinta el formulario.')->setEffects([CliActions::EFFECT_DATABASE])->register();
