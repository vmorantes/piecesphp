<?php

//El anterior solo se borra dentro de las subidas de su módulo y el nuevo va a la carpeta de la entidad; un tipo no permitido
//se explica. Documents, Publications y BuiltInBanner, por HTTP. Crea sus entidades y copias en statics/images/, zz-, y las retira.
//@areas src/app/classes/Documents/
//@areas src/app/classes/PiecesPHP/BuiltIn/
//@areas src/app/classes/Publications/

use Documents\Controllers\DocumentsController;
use Documents\Mappers\DocumentsMapper;
use Forms\DocumentTypes\Mappers\DocumentTypesMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\BuiltIn\Banner\Controllers\BuiltInBannerController;
use PiecesPHP\BuiltIn\Banner\Mappers\BuiltInBannerMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Publications\Controllers\PublicationsController;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/upload-previous-confined', function ($args) {

    echoTerminal("\e[33m[TEST:UploadPreviousConfined] El archivo anterior solo se borra dentro de las subidas de su módulo\e[39m");
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
    $cabeceraToken = SessionToken::tokenName();
    $guardar = function (string $ruta, string $jwt, array $campos) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . $ruta,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $campos,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ]);
        $body = curl_exec($handle);
        $datos = is_string($body) ? json_decode($body, true) : null;
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'success' => is_array($datos) ? ($datos['success'] ?? null) : null, 'message' => is_array($datos) ? (string) ($datos['message'] ?? '') : '', 'body' => is_string($body) ? $body : ''];
    };

    $database = (new BaseModel())->getDatabase();
    $prefijo = 'zz-prueba-confinado-' . bin2hex(random_bytes(3));
    $lang = Config::get_default_lang();
    $ahora = date('Y-m-d H:i:s');
    $temporal = sys_get_temp_dir() . "/{$prefijo}";
    $raizSubidas = (string) get_config('upload_dir');
    $meta = json_encode(['baseLang' => $lang, 'langData' => new \stdClass]);
    $usuarios = [];
    $filas = [];
    $carpetas = [];
    $ajenos = [];
    $registroErrores = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\') . '/error.log.jsonl';
    $lineasRegistro = function () use ($registroErrores): int {
        clearstatcache();
        return is_file($registroErrores) ? count(file($registroErrores) ?: []) : 0;
    };
    $tipoInvalido = __(FileValidator::LANG_GROUP, 'Tipo de archivo inválido.');

    try {
        //RETORNO-IGNORADO: lo comprueba c2.
        mkdir($temporal, 0700, true);
        $lienzo = imagecreatetruecolor(8, 8);
        $imagenes = [];
        foreach (['a', 'b'] as $letra) {
            $imagenes[$letra] = "{$temporal}/zz-confinado-{$letra}.jpg";
            if ($lienzo !== false) {
                //RETORNO-IGNORADO: lo comprueba c2.
                imagejpeg($lienzo, $imagenes[$letra]);
            }
        }
        $html = "{$temporal}/zz-confinado.html";
        $check(is_file($imagenes['a']) && is_file($imagenes['b']) && file_put_contents($html, '<html><body>zz-confinado</body></html>') !== false, 'c2 banco: dos imágenes .jpg y un .html');

        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Confinado';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ROOT;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $root = (int) $u->id;
        $usuarios[] = $root;
        $jwt = UserSessions::issueToken($root);

        //Cada módulo: su tabla, la columna probada, su carpeta de subidas, cómo se siembra y qué más pide su edición.
        $categoria = (int) (PublicationCategoryMapper::uncategorizedCategory()->id ?? 0);
        $database->prepare('INSERT INTO `' . DocumentTypesMapper::TABLE . '` (documentTypeName, folder, status, createdAt, createdBy, meta) VALUES (?, ?, ?, ?, ?, ?)')->execute(["{$prefijo}-tipo", "{$prefijo}-tipo", 1, $ahora, $root, $meta]);
        $tipoDocumento = (int) $database->lastInsertId();
        $modulos = [
            'Documents' => [
                'tabla' => DocumentsMapper::TABLE,
                'campo' => 'documentImage',
                'dir' => DocumentsController::UPLOAD_DIR,
                'ruta' => $camino(DocumentsController::class, 'actions-edit'),
                'sembrar' => function (string $carpeta, string $ajeno) use ($database, $prefijo, $tipoDocumento, $ahora, $root, $meta): int {
                    $database->prepare('INSERT INTO `' . DocumentsMapper::TABLE . '` (documentType, documentName, description, document, documentImage, folder, status, createdAt, createdBy, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                        ->execute([$tipoDocumento, "{$prefijo}-documento", '', $ajeno, $ajeno, $carpeta, 1, $ahora, $root, $meta]);
                    return (int) $database->lastInsertId();
                },
                'campos' => ['lang' => $lang, 'documentType' => $tipoDocumento, 'documentName' => "{$prefijo}-documento"],
            ],
            'Publications' => [
                'tabla' => PublicationMapper::TABLE,
                'campo' => 'ogImage',
                'dir' => PublicationsController::UPLOAD_DIR,
                'ruta' => $camino(PublicationsController::class, 'actions-edit'),
                'sembrar' => function (string $carpeta, string $ajeno) use ($database, $prefijo, $categoria, $ahora, $root, $meta): int {
                    $database->prepare('INSERT INTO `' . PublicationMapper::TABLE . '` (title, content, seoDescription, author, category, mainImage, thumbImage, ogImage, folder, visits, publicDate, createdAt, createdBy, status, featured, meta)'
                        . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, 0, ?)')
                        ->execute(["{$prefijo}-publicacion", 'zz-', '', $root, $categoria, $ajeno, $ajeno, $ajeno, $carpeta, $ahora, $ahora, $root, PublicationMapper::DRAFT, $meta]);
                    return (int) $database->lastInsertId();
                },
                'campos' => ['lang' => $lang, 'author' => $root, 'title' => "{$prefijo}-publicacion", 'content' => 'zz-', 'category' => $categoria, 'publicDate' => date('d-m-Y'), 'draft' => 'yes'],
            ],
            'BuiltInBanner' => [
                'tabla' => BuiltInBannerMapper::TABLE,
                'campo' => 'desktopImage',
                'dir' => BuiltInBannerController::UPLOAD_DIR,
                'ruta' => $camino(BuiltInBannerController::class, 'actions-edit'),
                'sembrar' => function (string $carpeta, string $ajeno) use ($database, $prefijo, $ahora, $root, $meta): int {
                    $database->prepare('INSERT INTO `' . BuiltInBannerMapper::TABLE . '` (title, content, link, desktopImage, mobileImage, orderPosition, folder, createdAt, createdBy, status, meta)'
                        . ' VALUES (?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?)')
                        ->execute(["{$prefijo}-banner", 'zz-', '', $ajeno, '', $carpeta, $ahora, $root, BuiltInBannerMapper::ACTIVE, $meta]);
                    return (int) $database->lastInsertId();
                },
                'campos' => ['lang' => $lang, 'title' => "{$prefijo}-banner"],
            ],
        ];

        foreach ($modulos as $modulo => $m) {
            echoTerminal('');
            echoTerminal("[{$modulo}] {$m['campo']}");
            $carpeta = "{$prefijo}-" . strtolower($modulo);
            //La carpeta la crea el terminal y abierta: si la creara Apache, el terminal no podría vaciarla.
            $enDisco = append_to_path_system($raizSubidas, "{$m['dir']}/{$carpeta}");
            //RETORNO-IGNORADO: lo comprueba x0.
            mkdir($enDisco, 0777, true);
            //RETORNO-IGNORADO: lo comprueba x0.
            chmod($enDisco, 0777);
            $carpetas[] = $enDisco;
            //El anterior, FUERA de las subidas del módulo: una copia propia en statics/images/.
            $ajenoRelativo = "statics/images/{$carpeta}.jpg";
            $ajeno = basepath($ajenoRelativo);
            $ajenos[] = $ajeno;
            //RETORNO-IGNORADO: lo comprueba x0.
            copy($imagenes['a'], $ajeno);
            $ajenoSha = is_file($ajeno) ? (string) sha1_file($ajeno) : '';
            $id = $m['sembrar']($carpeta, $ajenoRelativo);
            $filas[$m['tabla']][] = $id;
            $valor = function () use ($database, $m, $id): string {
                return (string) $database->query("SELECT `{$m['campo']}` FROM `{$m['tabla']}` WHERE id = {$id}")->fetchColumn();
            };
            //En disco, sin el sufijo de lo privado: unos módulos sirven sus subidas públicas y otros privadas.
            $archivos = function () use ($enDisco): array {
                clearstatcache();
                return array_values(array_map(fn (string $f): string => str_ends_with(basename($f), '.protected') ? substr(basename($f), 0, -strlen('.protected')) : basename($f), glob("{$enDisco}/*") ?: []));
            };
            $check(is_dir($enDisco) && is_writable($enDisco) && $ajenoSha !== '' && $id > 0 && $valor() === $ajenoRelativo, 'x0 CANARIO: la entidad existe, su carpeta está y el anterior apunta a la copia ajena', "id {$id} · {$m['campo']}=" . $valor());

            $lineasAntes = $lineasRegistro();
            $r = $guardar($m['ruta'], $jwt, ['id' => $id] + $m['campos'] + [$m['campo'] => new \CURLFile($html, 'text/html', 'zz-confinado.html')]);
            $check($r['success'] === false && str_contains($r['message'], $tipoInvalido) && $valor() === $ajenoRelativo, "t1 un .html se rechaza con «{$tipoInvalido}» y no cambia nada", "HTTP {$r['status']} · " . mb_substr($r['message'], 0, 160));
            $check($lineasRegistro() === $lineasAntes, 't2 y no escribe en error.log.jsonl', "{$lineasAntes} → " . $lineasRegistro());

            $r = $guardar($m['ruta'], $jwt, ['id' => $id] + $m['campos'] + [$m['campo'] => new \CURLFile($imagenes['a'], 'image/jpeg', 'zz-confinado-a.jpg')]);
            clearstatcache();
            $check($r['success'] === true, 'x1 sustituir el anterior ajeno: el guardado responde success', "HTTP {$r['status']} · " . mb_substr($r['body'], 0, 200));
            $check(is_file($ajeno) && sha1_file($ajeno) === $ajenoSha, 'x2 el anterior FUERA de las subidas del módulo SIGUE, igual (sha1)', $ajeno);
            $check(str_contains($valor(), "/{$m['dir']}/{$carpeta}/") && in_array('zz-confinado-a.jpg', $archivos(), true), 'x3 el nuevo queda en la carpeta de la entidad', $valor() . ' · en disco: ' . implode(', ', $archivos()));

            $r = $guardar($m['ruta'], $jwt, ['id' => $id] + $m['campos'] + [$m['campo'] => new \CURLFile($imagenes['b'], 'image/jpeg', 'zz-confinado-b.jpg')]);
            $check($r['success'] === true && str_ends_with($valor(), 'zz-confinado-b.jpg'), 'x4 sustituir uno DENTRO: el guardado apunta al nuevo', "HTTP {$r['status']} · {$m['campo']}=" . $valor());
            $check(!in_array('zz-confinado-a.jpg', $archivos(), true) && in_array('zz-confinado-b.jpg', $archivos(), true), 'x5 y el anterior, dentro de las subidas del módulo, SÍ se borra', 'en disco: ' . implode(', ', $archivos()));
        }
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            foreach (array_merge(glob("{$temporal}/*") ?: [], $ajenos) as $archivo) {
                if (is_file($archivo)) {
                    //RETORNO-IGNORADO: lo comprueba z3.
                    unlink($archivo);
                }
            }
            if (is_dir($temporal)) {
                //RETORNO-IGNORADO: lo comprueba z3.
                rmdir($temporal);
            }
            foreach ($carpetas as $carpeta) {
                foreach (glob("{$carpeta}/*") ?: [] as $archivo) {
                    //RETORNO-IGNORADO: lo comprueba z3.
                    unlink($archivo);
                }
                if (is_dir($carpeta)) {
                    //RETORNO-IGNORADO: lo comprueba z3.
                    rmdir($carpeta);
                }
            }
            $aprobaciones = SystemApprovalsMapper::model();
            foreach (array_merge($filas, [UsersModel::TABLE => $usuarios, DocumentTypesMapper::TABLE => isset($tipoDocumento) ? [$tipoDocumento] : []]) as $tabla => $ids) {
                foreach ($ids as $id) {
                    $aprobaciones->resetAll();
                    //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                    $aprobaciones->delete(new WhereSegment([
                        new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tabla),
                        new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                    ]))->execute();
                    if ($tabla !== UsersModel::TABLE) {
                        $database->prepare("DELETE FROM `{$tabla}` WHERE id = ?")->execute([$id]);
                    }
                }
            }
            foreach ($usuarios as $id) {
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            $usuariosModelo = UsersModel::model();
            $usuariosModelo->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $usuariosModelo->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $restos = 0;
        foreach (array_merge($filas, [DocumentTypesMapper::TABLE => isset($tipoDocumento) ? [$tipoDocumento] : []]) as $tabla => $ids) {
            foreach ($ids as $id) {
                $restos += (int) $database->query("SELECT COUNT(*) FROM `{$tabla}` WHERE id = " . (int) $id)->fetchColumn();
            }
        }
        $restos += (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($restos === 0, 'z1 no queda ninguna fila de la prueba', "quedan {$restos}");
        clearstatcache();
        $quedan = array_filter(array_merge($carpetas, $ajenos, [$temporal]), fn (string $ruta): bool => file_exists($ruta));
        $check(count($quedan) === 0, 'z3 no quedan carpetas, copias en statics/images/ ni el temporal', implode(', ', $quedan));
    }

    return $balance();

})->setDescription('Sustituir un archivo en Documents, Publications y BuiltInBanner no borra el anterior si está fuera de las subidas de su módulo (y el nuevo va a la carpeta de la entidad); dentro, sí lo borra. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
