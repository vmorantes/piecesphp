<?php

//El formulario de una aprobación no responde 500: si lo aprobado ya no existe, o su tabla no tiene manejador, 404;
//un usuario sin organización ni perfil guardado se pinta con sus datos vacíos. Por HTTP. Todo zz-, y se retira.

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Controllers\SystemApprovalsController;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/approval-forms', function ($args) {

    echoTerminal("\e[33m[TEST:ApprovalForms] El formulario de una aprobación responde 404 o se pinta, nunca 500\e[39m");
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
    if (!$check($base !== '' && $database !== null, 'c1 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }

    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::tokenName();
    $formulario = function (int $id) use ($prefijoBase): string {
        $url = SystemApprovalsController::routeName('forms-approval', ['id' => $id], true);
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, string $jwt) use ($base, $cabeceraToken): array {
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ]);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };

    $prefijo = 'zz-prueba-aprobaciones-' . bin2hex(random_bytes(3));
    $tabla = SystemApprovalsMapper::TABLE;
    $usuarios = [];
    $aprobaciones = [];
    $organizacion = 0;
    $crearUsuario = function (string $sufijo, int $tipo, ?int $organizacion) use ($prefijo, &$usuarios): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Aprobaciones';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        //Sin organización se deja el campo sin asignar: la columna admite null y es su valor de partida.
        if ($organizacion !== null) {
            $u->organization = $organizacion;
        }
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[$sufijo] = (int) $u->id;
        return (int) $u->id;
    };
    $sembrar = function (string $referenceTable, string $referenceValue) use ($database, $tabla, $prefijo, &$usuarios, &$aprobaciones): int {
        $ahora = date('Y-m-d H:i:s');
        $database->prepare("INSERT INTO `{$tabla}` (referenceAlias, referenceValue, referenceTable, referenceDate, createdAt, createdBy, status) VALUES (?, ?, ?, ?, ?, ?, ?)")
            ->execute(["{$prefijo}-aprobacion", $referenceValue, $referenceTable, $ahora, $ahora, $usuarios['root'], SystemApprovalsMapper::STATUS_PENDING]);
        $id = (int) $database->lastInsertId();
        $aprobaciones[] = $id;
        return $id;
    };

    try {
        $root = SessionToken::generateToken(['id' => $crearUsuario('root', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL)], null, null, false);
        $completo = $crearUsuario('completo', UsersModel::TYPE_USER_GENERAL, OrganizationMapper::INITIAL_ID_GLOBAL);
        $sinOrganizacion = $crearUsuario('sin-org', UsersModel::TYPE_USER_GENERAL, null);
        $noExiste = '2147483000';

        //─── a · Lo aprobado ya no existe ─────────────────────────────────────────────────────────
        echoTerminal('[a] Lo aprobado ya no existe: 404');
        foreach ([PublicationMapper::TABLE => 'una publicación', UsersModel::TABLE => 'un usuario', OrganizationMapper::TABLE => 'una organización'] as $referencia => $que) {
            $respuesta = $pedir($formulario($sembrar($referencia, $noExiste)), $root);
            $check($respuesta['status'] === 404, "a la aprobación de {$que} que ya no existe responde 404", "HTTP {$respuesta['status']}");
        }
        $desconocida = $pedir($formulario($sembrar('zz_tabla_sin_manejador', '1')), $root);
        $check($desconocida['status'] === 404, 'a la de una tabla sin manejador responde 404', "HTTP {$desconocida['status']}");

        //─── b · Un usuario con sus datos a medias ────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[b] Usuarios: el completo es el canario, y el que no tiene organización ni perfil se pinta igual');
        $canario = $pedir($formulario($sembrar(UsersModel::TABLE, (string) $completo)), $root);
        $check($canario['status'] === 200 && str_contains($canario['body'], "{$prefijo}-completo"), 'b1 CANARIO: la de un usuario con organización se pinta', "HTTP {$canario['status']}");
        $sinOrg = $pedir($formulario($sembrar(UsersModel::TABLE, (string) $sinOrganizacion)), $root);
        $check($sinOrg['status'] === 200 && str_contains($sinOrg['body'], "{$prefijo}-sin-org"), 'b2 la de un usuario sin organización ni perfil guardado se pinta: 200', "HTTP {$sinOrg['status']}");

        //─── c · Mirar no escribe ─────────────────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[c] La aprobación de una organización sin encargado se mira sin cambiarla');
        $tablaOrganizaciones = OrganizationMapper::TABLE;
        $ahora = date('Y-m-d H:i:s');
        $database->prepare("INSERT INTO `{$tablaOrganizaciones}` (code, name, nit, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute(['zz' . bin2hex(random_bytes(4)), "{$prefijo}-organizacion", 'zz-', "{$prefijo}-organizacion", $ahora, $usuarios['root'], 1, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
        $organizacion = (int) $database->lastInsertId();
        $fila = fn (): array => (array) $database->query("SELECT * FROM `{$tablaOrganizaciones}` WHERE id = {$organizacion}")->fetch(\PDO::FETCH_ASSOC);
        $antes = $fila();
        $vista = $pedir($formulario($sembrar($tablaOrganizaciones, (string) $organizacion)), $root);
        $check($vista['status'] === 200 && str_contains($vista['body'], 'Sin encargado'), 'c1 se pinta, y dice que no tiene encargado', "HTTP {$vista['status']}");
        $check($fila() === $antes, 'c2 la fila de la organización es la misma antes y después del GET: mirar no escribe');
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            foreach ($aprobaciones as $id) {
                $database->exec("DELETE FROM `{$tabla}` WHERE id = " . (int) $id);
            }
            if ($organizacion > 0) {
                $database->exec("DELETE FROM `{$tabla}` WHERE referenceTable = " . $database->quote(OrganizationMapper::TABLE) . " AND referenceValue = " . $database->quote((string) $organizacion));
                $database->exec('DELETE FROM `' . OrganizationMapper::TABLE . "` WHERE id = {$organizacion}");
            }
            $modelo = SystemApprovalsMapper::model();
            foreach ($usuarios as $id) {
                $modelo->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $modelo->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, UsersModel::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
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
        $quedanAprobaciones = (int) $database->query("SELECT COUNT(*) FROM `{$tabla}` WHERE referenceAlias = " . $database->quote("{$prefijo}-aprobacion"))->fetchColumn();
        $quedanUsuarios = UsersModel::model();
        $quedanUsuarios->resetAll();
        $quedanUsuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $quedanOrganizaciones = (int) $database->query('SELECT COUNT(*) FROM `' . OrganizationMapper::TABLE . '` WHERE name = ' . $database->quote("{$prefijo}-organizacion"))->fetchColumn();
        $check($quedanAprobaciones === 0 && $quedanOrganizaciones === 0 && count((array) $quedanUsuarios->result()) === 0, 'z1 no queda ninguna aprobación, organización ni usuario de la prueba', "aprobaciones {$quedanAprobaciones}, organizaciones {$quedanOrganizaciones}");
    }

    return $balance();

})->setDescription('El formulario de una aprobación: si lo aprobado ya no existe o su tabla no tiene manejador, 404; un usuario sin organización ni perfil guardado se pinta. Nunca 500. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
