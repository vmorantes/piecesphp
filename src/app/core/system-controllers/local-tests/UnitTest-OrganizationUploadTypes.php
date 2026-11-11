<?php

//RUT y logo: un .svg o un .html se rechazan con un mensaje claro y sin registrar error; un anterior fuera de las subidas no se borra.
//Por HTTP, como el encargado. Crea organización, usuarios y una copia en statics/images/, zz-; repone el logo versionado si cambia.
//@areas src/app/classes/Organizations/

use Organizations\Controllers\MyOrganizationProfileController;
use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use Terminal\Tasks\GeoLoadFixtureTask;

CliActions::make('unit-tests:core/organization-upload-types', function ($args) {

    echoTerminal("\e[33m[TEST:OrganizationUploadTypes] El RUT y el logo rechazan .svg y .html\e[39m");
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
    $accion = MyOrganizationProfileController::routeName('actions-save-profile', [], true);
    $accion = '/' . ltrim(str_contains($accion, '://') ? (string) parse_url($accion, \PHP_URL_PATH) : $accion, '/');
    $accion = $prefijoBase !== '' && str_starts_with($accion, $prefijoBase) ? substr($accion, strlen($prefijoBase)) : $accion;
    $cabeceraToken = SessionToken::headerName();
    $guardar = function (string $jwt, array $campos) use ($base, $accion, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . $accion,
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

    $prefijo = 'zz-prueba-tipos-org-' . bin2hex(random_bytes(3));
    $ids = [];
    $organizacionId = null;
    $temporal = sys_get_temp_dir() . "/{$prefijo}";
    //La carpeta de la organización la crea el terminal y abierta: si la creara Apache, el terminal no podría vaciarla.
    $carpeta = append_to_path_system((string) get_config('upload_dir'), OrganizationsController::UPLOAD_DIR . "/{$prefijo}");
    $enCarpeta = function () use ($carpeta): array {
        clearstatcache();
        return array_values(array_map('basename', glob("{$carpeta}/*") ?: []));
    };
    $valor = function (string $campo) use (&$organizacionId): string {
        return (string) (new OrganizationMapper((int) $organizacionId))->$campo;
    };
    //El logo que guarda la carga del geovisor: un archivo VERSIONADO fuera de las subidas. Una regresión lo borraría.
    $versionado = basepath(GeoLoadFixtureTask::LOGO);
    $versionadoBytes = is_file($versionado) ? file_get_contents($versionado) : false;
    $versionadoSha = is_string($versionadoBytes) ? sha1($versionadoBytes) : '';
    //Y una copia propia, también fuera, para el RUT.
    $ajenoRelativo = "statics/images/{$prefijo}-rut.pdf";
    $ajeno = basepath($ajenoRelativo);
    $registroErrores = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\') . '/error.log.jsonl';
    $lineasRegistro = function () use ($registroErrores): int {
        clearstatcache();
        return is_file($registroErrores) ? count(file($registroErrores) ?: []) : 0;
    };

    try {
        //RETORNO-IGNORADO: lo comprueba c2.
        mkdir($temporal, 0700, true);
        //RETORNO-IGNORADO: lo comprueba c2.
        mkdir($carpeta, 0777, true);
        //RETORNO-IGNORADO: lo comprueba c2.
        chmod($carpeta, 0777);
        $lienzo = imagecreatetruecolor(8, 8);
        $archivos = [
            'svg' => [file_put_contents("{$temporal}/zz-tipos.svg", '<svg xmlns="http://www.w3.org/2000/svg"><script>/*zz-tipos*/</script></svg>') !== false, "{$temporal}/zz-tipos.svg", 'image/svg+xml'],
            'html' => [file_put_contents("{$temporal}/zz-tipos.html", '<html><body>zz-tipos</body></html>') !== false, "{$temporal}/zz-tipos.html", 'text/html'],
            'png' => [$lienzo !== false && imagepng($lienzo, "{$temporal}/zz-tipos.png"), "{$temporal}/zz-tipos.png", 'image/png'],
            'pdf' => [file_put_contents("{$temporal}/zz-tipos.pdf", "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[]/Count 0>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n") !== false, "{$temporal}/zz-tipos.pdf", 'application/pdf'],
        ];
        $check(is_dir($carpeta) && is_writable($carpeta) && count(array_filter(array_column($archivos, 0))) === 4, 'c2 banco: la carpeta de la organización y los cuatro archivos');
        $subida = function (string $tipo) use ($archivos): \CURLFile {
            return new \CURLFile($archivos[$tipo][1], $archivos[$tipo][2], basename($archivos[$tipo][1]));
        };

        $organizacion = new OrganizationMapper();
        $organizacion->name = "{$prefijo}-org";
        $organizacion->nit = "{$prefijo}-nit";
        $organizacion->folder = $prefijo;
        $organizacion->status = OrganizationMapper::ACTIVE;
        $organizacion->activitySector = 'Sector de prueba';
        $organizacion->informativeEmail = "{$prefijo}-org@example.com";
        //Sin logo: con la ruta de una imagen versionada, la subida la tomaría por el anterior y la borraría.
        $organizacion->logo = '';
        $organizacion->save();
        $organizacionId = (int) $organizacion->id;
        $encargado = new UsersModel();
        $encargado->username = "{$prefijo}-encargado";
        $encargado->email = "{$prefijo}-encargado@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $encargado->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $encargado->firstname = 'Zz';
        $encargado->secondname = '';
        $encargado->firstLastname = 'Tipos';
        $encargado->secondLastname = '';
        $encargado->type = UsersModel::TYPE_USER_ADMIN_ORG;
        $encargado->status = UsersModel::STATUS_USER_ACTIVE;
        $encargado->failedAttempts = 0;
        $encargado->organization = $organizacionId;
        $encargado->createdAt = new \DateTime();
        $encargado->modifiedAt = $encargado->createdAt;
        $encargado->save();
        $ids[] = (int) $encargado->id;
        $conEncargado = new OrganizationMapper($organizacionId);
        $conEncargado->administrator = (int) $encargado->id;
        $conEncargado->update();
        $jwt = UserSessions::issueToken((int) $encargado->id);
        $nombre = "{$prefijo}-org";

        echoTerminal('[a] Sin RUT ni logo previos');
        $canario = $guardar($jwt, ['name' => $nombre]);
        $check($canario['status'] === 200 && $canario['success'] === true, 'a0 CANARIO: el encargado guarda su organización sin archivos', "HTTP {$canario['status']} · " . mb_substr($canario['body'], 0, 200));
        foreach (['svg', 'html'] as $tipo) {
            foreach (['rut', 'logo'] as $campo) {
                $r = $guardar($jwt, ['name' => $nombre, $campo => $subida($tipo)]);
                $check($r['success'] !== true && $valor($campo) === '' && $enCarpeta() === [], "a1 un .{$tipo} como {$campo} se rechaza: no se guarda ni queda en disco", "HTTP {$r['status']} · {$campo}=" . $valor($campo) . ' · en disco: ' . implode(', ', $enCarpeta()));
            }
        }

        echoTerminal('');
        echoTerminal('[b] Lo que sí se acepta');
        $r = $guardar($jwt, ['name' => $nombre, 'rut' => $subida('png')]);
        $check($r['success'] === true && str_ends_with($valor('rut'), 'zz-tipos.png'), 'b1 un .png como RUT se acepta', "HTTP {$r['status']} · rut=" . $valor('rut'));
        $r = $guardar($jwt, ['name' => $nombre, 'rut' => $subida('pdf')]);
        $check($r['success'] === true && str_ends_with($valor('rut'), 'zz-tipos.pdf'), 'b2 un .pdf como RUT se acepta', "HTTP {$r['status']} · rut=" . $valor('rut'));
        $r = $guardar($jwt, ['name' => $nombre, 'logo' => $subida('png')]);
        $check($r['success'] === true && str_ends_with($valor('logo'), 'zz-tipos.png'), 'b3 un .png como logo se acepta', "HTTP {$r['status']} · logo=" . $valor('logo'));

        echoTerminal('');
        echoTerminal('[s] Con RUT y logo ya guardados, la sustitución tampoco los admite');
        $antes = ['rut' => $valor('rut'), 'logo' => $valor('logo')];
        $disco = $enCarpeta();
        foreach (['svg', 'html'] as $tipo) {
            foreach (['rut', 'logo'] as $campo) {
                $r = $guardar($jwt, ['name' => $nombre, $campo => $subida($tipo)]);
                $check($r['success'] !== true && $valor($campo) === $antes[$campo] && $enCarpeta() === $disco, "s1 un .{$tipo} sustituyendo el {$campo} se rechaza y el anterior se queda", "HTTP {$r['status']} · {$campo}=" . $valor($campo) . ' · en disco: ' . implode(', ', $enCarpeta()));
            }
        }

        echoTerminal('');
        echoTerminal('[m] El rechazo se explica: el mensaje del validador, sin entrada en el registro de errores');
        $esperado = __(FileValidator::LANG_GROUP, 'Tipo de archivo inválido.');
        foreach (['rut', 'logo'] as $campo) {
            $lineasAntes = $lineasRegistro();
            $r = $guardar($jwt, ['name' => $nombre, $campo => $subida('svg')]);
            $check($r['success'] === false && str_contains($r['message'], $esperado), "m1 un .svg como {$campo}: success false con «{$esperado}»", "HTTP {$r['status']} · " . mb_substr($r['message'], 0, 200));
            $check($lineasRegistro() === $lineasAntes, "m2 y no escribe en error.log.jsonl", "{$lineasAntes} → " . $lineasRegistro());
        }

        echoTerminal('');
        echoTerminal('[f] Un anterior FUERA de las subidas del módulo no se borra, y el nuevo va a la carpeta de la organización');
        $copiado = is_string($versionadoBytes) && file_put_contents($ajeno, "%PDF-1.4\n%zz-ajeno\n%%EOF\n") !== false;
        $ajenoSha = $copiado ? (string) sha1_file($ajeno) : '';
        $lang = Config::get_default_lang();
        $fuera = new OrganizationMapper($organizacionId);
        $fuera->setLangData($lang, 'logo', GeoLoadFixtureTask::LOGO);
        $fuera->setLangData($lang, 'rut', $ajenoRelativo);
        $fuera->update();
        $check($copiado && $valor('logo') === GeoLoadFixtureTask::LOGO && $valor('rut') === $ajenoRelativo, 'f0 CANARIO: el logo apunta al versionado y el RUT a la copia propia, los dos fuera de las subidas', $valor('logo') . ' · ' . $valor('rut'));
        $antesFuera = $enCarpeta();
        $r = $guardar($jwt, ['name' => $nombre, 'logo' => $subida('png'), 'rut' => $subida('pdf')]);
        clearstatcache();
        $check($r['success'] === true, 'f1 el guardado con los dos archivos nuevos responde success', "HTTP {$r['status']} · " . mb_substr($r['body'], 0, 200));
        $check(is_file($versionado) && sha1_file($versionado) === $versionadoSha, 'f2 el logo versionado SIGUE, igual (sha1)', $versionado);
        $check(is_file($ajeno) && sha1_file($ajeno) === $ajenoSha, 'f3 la copia propia del RUT SIGUE, igual (sha1)', $ajeno);
        foreach (['logo', 'rut'] as $campo) {
            $check(str_contains($valor($campo), '/' . OrganizationsController::UPLOAD_DIR . "/{$prefijo}/") && in_array(basename($valor($campo)) . '.protected', $enCarpeta(), true), "f4 el {$campo} nuevo queda en la carpeta de la organización", $valor($campo) . ' · en disco: ' . implode(', ', $enCarpeta()) . ' (antes: ' . implode(', ', $antesFuera) . ')');
        }
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        clearstatcache();
        if (is_string($versionadoBytes) && (!is_file($versionado) || sha1_file($versionado) !== $versionadoSha)) {
            //RETORNO-IGNORADO: lo comprueba z4.
            file_put_contents($versionado, $versionadoBytes);
            $check(false, 'z6 el logo versionado cambió o desapareció durante la prueba: repuesto', $versionado);
        }
        $check(is_string($versionadoBytes) && is_file($versionado) && sha1_file($versionado) === $versionadoSha, 'z4 el logo versionado está, igual que al empezar', $versionado);
        if (is_file($ajeno)) {
            //RETORNO-IGNORADO: lo comprueba z5.
            unlink($ajeno);
        }
        $check(!is_file($ajeno), 'z5 no queda la copia propia en statics/images/', $ajeno);
        try {
            foreach (glob("{$temporal}/*") ?: [] as $archivo) {
                //RETORNO-IGNORADO: el temporal es propio; lo comprueba z3.
                unlink($archivo);
            }
            if (is_dir($temporal)) {
                //RETORNO-IGNORADO: lo comprueba z3.
                rmdir($temporal);
            }
            foreach (glob("{$carpeta}/*") ?: [] as $archivo) {
                //RETORNO-IGNORADO: lo comprueba z3.
                unlink($archivo);
            }
            if (is_dir($carpeta)) {
                //RETORNO-IGNORADO: lo comprueba z3.
                rmdir($carpeta);
            }
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ($ids as $id) {
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
            //Quien guarda queda en `modifiedBy` de la organización, con clave foránea al usuario: se suelta antes.
            $soltar = OrganizationMapper::model();
            $soltar->resetAll();
            //RETORNO-IGNORADO: si no soltara, el borrado de usuarios lanzaría y lo diría z0.
            $soltar->update(['modifiedBy' => null])->where(new WhereSegment([WhereItem::like('name', "{$prefijo}%")]))->execute();
            $usuarios = UsersModel::model();
            $usuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
            if ($organizacionId !== null) {
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z2 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, OrganizationMapper::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $organizacionId, WhereItem::AND_OPERATOR),
                ]))->execute();
            }
            $organizaciones = OrganizationMapper::model();
            $organizaciones->resetAll();
            //RETORNO-IGNORADO: lo comprueba z2 contando lo que queda.
            $organizaciones->delete(new WhereSegment([WhereItem::like('name', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $usuarios = UsersModel::model();
        $usuarios->resetAll();
        $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $check(count((array) $usuarios->result()) === 0, 'z1 no queda ningún usuario de la prueba');
        $restos = OrganizationMapper::model();
        $restos->resetAll();
        $restos->select()->where(new WhereSegment([WhereItem::like('name', "{$prefijo}%")]))->execute();
        $check(count((array) $restos->result()) === 0, 'z2 no queda ninguna organización de la prueba');
        clearstatcache();
        $check(!is_dir($carpeta) && !is_dir($temporal), 'z3 no queda la carpeta de la organización ni el temporal', $carpeta);
    }

    return $balance();

})->setDescription('El RUT y el logo de una organización rechazan un .svg y un .html, también al sustituir uno ya guardado; aceptan .png y, el RUT, .pdf. Por HTTP, como el encargado desde «Mi organización».')->setEffects([CliActions::EFFECT_DATABASE])->register();
