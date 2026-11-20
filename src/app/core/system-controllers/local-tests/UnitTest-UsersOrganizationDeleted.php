<?php

//No se asigna un usuario a una organización eliminada (P380), ni por herencia al importar; quien ya está en una la
//conserva al guardar su ficha sin tocarla. Por HTTP el alta y la edición; la importación, por su definición.
//@areas nucleo src/app/classes/DataImportExportUtility/

use DataImportExportUtility\Controllers\DataTransferController;
use DataImportExportUtility\Definitions\UsersImportDefinition;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\DataTransfer\Import\ImportRunner;
use PiecesPHP\Core\DataTransfer\Source\ArrayRowSource;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/users-organization-deleted', function ($args) {

    echoTerminal("\e[33m[TEST:UsersOrganizationDeleted] Una organización eliminada no se asigna: como una que no existe\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve.
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
    $pedir = function (string $path, string $jwt, array $post) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}", 'X-Requested-With: XMLHttpRequest'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($post),
        ]);
        $body = curl_exec($handle);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    $leer = function (string $path, string $jwt) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ["{$cabeceraToken}: {$jwt}"],
        ]);
        $body = curl_exec($handle);
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    $json = fn (array $r): array => is_array($decodificado = json_decode($r['body'], true)) ? $decodificado : [];
    $resumen = fn (array $r): string => "HTTP {$r['status']}, success " . var_export($json($r)['success'] ?? null, true) . ', «' . (string) ($json($r)['message'] ?? '') . '»';
    $igual = fn (array $a, array $b): bool => $a['status'] === $b['status'] && ($json($a)['success'] ?? null) === ($json($b)['success'] ?? null) && ($json($a)['message'] ?? null) === ($json($b)['message'] ?? null);

    //La limpieza retira lo de cualquier corrida de esta suite, también lo que dejara una que se cortó.
    $prefijoGeneral = 'zz-prueba-usuarios-org-eliminada-';
    $prefijo = $prefijoGeneral . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $usuarios = [];
    $organizaciones = [];

    try {
        $crearUsuario = function (string $sufijo, int $tipo) use ($prefijo, &$usuarios): int {
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            //Nadie entra con contraseña: el token lo fabrica la prueba.
            $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Organizacion';
            $u->secondLastname = '';
            $u->type = $tipo;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            return (int) $u->id;
        };
        $root = UserSessions::issueToken($crearUsuario('root', UsersModel::TYPE_USER_ROOT));
        $miembro = $crearUsuario('miembro', UsersModel::TYPE_USER_GENERAL);
        $ahora = date('Y-m-d H:i:s');
        $codigos = [];
        foreach (['activa' => OrganizationMapper::ACTIVE, 'eliminada' => OrganizationMapper::DELETED, 'otra-eliminada' => OrganizationMapper::DELETED] as $cual => $estado) {
            $codigos[$cual] = OrganizationMapper::generateCode();
            $database->prepare("INSERT INTO `{$tablaOrganizaciones}` (code, name, nit, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute([$codigos[$cual], "{$prefijo}-org-{$cual}", 'zz-', "{$prefijo}-org-{$cual}", $ahora, $usuarios['root'], $estado, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
            $organizaciones[$cual] = (int) $database->lastInsertId();
        }
        $inexistente = (int) $database->query("SELECT COALESCE(MAX(id), 0) + 1000 FROM `{$tablaOrganizaciones}`")->fetchColumn();
        $organizacionDe = fn (int $id): int => (int) $database->query("SELECT organization FROM `{$tablaUsuarios}` WHERE id = {$id}")->fetchColumn();

        echoTerminal('[a] El alta');
        $alta = $camino(UsersController::routeName('register-request', [], true));
        $clave = bin2hex(random_bytes(12));
        $cuerpoAlta = fn (string $sufijo, int $organizacion): array => ['username' => "{$prefijo}-{$sufijo}", 'email' => "{$prefijo}-{$sufijo}@example.com", 'password' => $clave, 'password2' => $clave, 'firstname' => 'Zz', 'first_lastname' => 'Organizacion', 'type' => UsersModel::TYPE_USER_GENERAL, 'status' => UsersModel::STATUS_USER_ACTIVE, 'organization' => $organizacion];
        $altaActiva = $pedir($alta, $root, $cuerpoAlta('alta-activa', $organizaciones['activa']));
        $check(($json($altaActiva)['success'] ?? false) === true, 'a1 CANARIO: el alta en una organización activa se acepta', $resumen($altaActiva));
        $altaNada = $pedir($alta, $root, $cuerpoAlta('alta-nada', $inexistente));
        $altaEliminada = $pedir($alta, $root, $cuerpoAlta('alta-eliminada', $organizaciones['eliminada']));
        $check($igual($altaEliminada, $altaNada) && ($json($altaEliminada)['success'] ?? null) !== true, 'a2 DISCRIMINANTE: el alta en una eliminada responde como en una que no existe', 'eliminada ' . $resumen($altaEliminada) . '; inexistente ' . $resumen($altaNada));
        $creada = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username = " . $database->quote("{$prefijo}-alta-eliminada"))->fetchColumn();
        $check($creada === 0, 'a3 y no crea el usuario', "filas {$creada}");

        echoTerminal('');
        echoTerminal('[b] La edición');
        $editar = $camino(UsersController::routeName('edit-request', [], true));
        $cuerpoEdicion = fn (int $organizacion): array => ['id' => (string) $miembro, 'username' => "{$prefijo}-miembro", 'email' => "{$prefijo}-miembro@example.com", 'firstname' => 'Zz', 'first_lastname' => 'Organizacion', 'status' => (string) UsersModel::STATUS_USER_ACTIVE, 'organization' => (string) $organizacion];
        $edicionActiva = $pedir($editar, $root, $cuerpoEdicion($organizaciones['activa']));
        $check(($json($edicionActiva)['success'] ?? false) === true && $organizacionDe($miembro) === $organizaciones['activa'], 'b1 CANARIO: pasarlo a una organización activa se acepta', $resumen($edicionActiva));
        $edicionNada = $pedir($editar, $root, $cuerpoEdicion($inexistente));
        $edicionEliminada = $pedir($editar, $root, $cuerpoEdicion($organizaciones['eliminada']));
        $check($igual($edicionEliminada, $edicionNada) && ($json($edicionEliminada)['success'] ?? null) !== true, 'b2 DISCRIMINANTE: pasarlo a una eliminada responde como a una que no existe', 'eliminada ' . $resumen($edicionEliminada) . '; inexistente ' . $resumen($edicionNada));
        $check($organizacionDe($miembro) === $organizaciones['activa'], 'b3 y sigue en la suya', 'organización ' . $organizacionDe($miembro));

        echoTerminal('');
        echoTerminal('[c] La importación, por id y por código');
        $resolver = new \ReflectionMethod(UsersImportDefinition::class, 'organizationID');
        $check($resolver->invoke(null, (string) $organizaciones['activa']) === $organizaciones['activa'] && $resolver->invoke(null, $codigos['activa']) === $organizaciones['activa'], 'c1 CANARIO: una activa se resuelve por su id y por su código');
        $check($resolver->invoke(null, (string) $organizaciones['eliminada']) === null && $resolver->invoke(null, (string) $inexistente) === null, 'c2 DISCRIMINANTE: una eliminada por su id no se resuelve, como una que no existe');
        $check($resolver->invoke(null, $codigos['eliminada']) === null, 'c3 DISCRIMINANTE: ni por su código');

        echoTerminal('');
        echoTerminal('[d] La ficha de quien ya está en una eliminada');
        $enEliminada = $crearUsuario('en-eliminada', UsersModel::TYPE_USER_GENERAL);
        $database->prepare("UPDATE `{$tablaUsuarios}` SET organization = ? WHERE id = ?")->execute([$organizaciones['eliminada'], $enEliminada]);
        $ficha = $leer($camino(UsersController::routeName('form-edit', ['id' => $enEliminada], true)), $root);
        $opcion = preg_match('/<option value="' . $organizaciones['eliminada'] . '"([^>]*)>([^<]*)</', $ficha['body'], $m) === 1 ? $m : [];
        $check($ficha['status'] === 200 && $opcion !== [], 'd1 el selector la pinta', "HTTP {$ficha['status']}");
        $check(str_contains($opcion[1] ?? '', 'disabled') && str_contains($opcion[1] ?? '', 'selected') && str_contains($opcion[2] ?? '', '(eliminada)'), 'd2 DISCRIMINANTE: deshabilitada, seleccionada y con «(eliminada)»', trim(($opcion[1] ?? '') . ' ' . ($opcion[2] ?? '')));
        $check(preg_match_all('/<option value="' . $organizaciones['otra-eliminada'] . '"/', $ficha['body']) === 0, 'd3 y no pinta ninguna otra eliminada');
        $cuerpoFicha = fn (?int $organizacion): array => array_filter(['id' => (string) $enEliminada, 'username' => "{$prefijo}-en-eliminada", 'email' => "{$prefijo}-en-eliminada@example.com", 'firstname' => 'Zz', 'first_lastname' => 'Organizacion', 'status' => (string) UsersModel::STATUS_USER_ACTIVE, 'organization' => $organizacion === null ? null : (string) $organizacion], fn ($v) => $v !== null);
        //El navegador no envía una opción deshabilitada: el campo no llega.
        $sinTocar = $pedir($editar, $root, $cuerpoFicha(null));
        $check(($json($sinTocar)['success'] ?? false) === true && $organizacionDe($enEliminada) === $organizaciones['eliminada'], 'd4 DISCRIMINANTE: guardar sin tocarla (no llega) la conserva', $resumen($sinTocar) . ', organización ' . $organizacionDe($enEliminada));
        $laSuya = $pedir($editar, $root, $cuerpoFicha($organizaciones['eliminada']));
        $check(($json($laSuya)['success'] ?? false) === true && $organizacionDe($enEliminada) === $organizaciones['eliminada'], 'd5 DISCRIMINANTE: enviar la suya, aunque eliminada, la conserva', $resumen($laSuya));
        $otraEliminada = $pedir($editar, $root, $cuerpoFicha($organizaciones['otra-eliminada']));
        $check($igual($otraEliminada, $edicionNada) && ($json($otraEliminada)['success'] ?? null) !== true && $organizacionDe($enEliminada) === $organizaciones['eliminada'], 'd6 DISCRIMINANTE: otra eliminada se rechaza como una que no existe', $resumen($otraEliminada));
        $aViva = $pedir($editar, $root, $cuerpoFicha($organizaciones['activa']));
        $check(($json($aViva)['success'] ?? false) === true && $organizacionDe($enEliminada) === $organizaciones['activa'], 'd7 CANARIO: pasarla a una viva se acepta', $resumen($aViva));

        echoTerminal('');
        echoTerminal('[e] La importación con la organización heredada');
        $importadorVivo = $crearUsuario('importador-vivo', UsersModel::TYPE_USER_ADMIN_GRAL);
        $importadorMuerto = $crearUsuario('importador-muerto', UsersModel::TYPE_USER_ADMIN_GRAL);
        $database->prepare("UPDATE `{$tablaUsuarios}` SET organization = ? WHERE id = ?")->execute([$organizaciones['activa'], $importadorVivo]);
        $database->prepare("UPDATE `{$tablaUsuarios}` SET organization = ? WHERE id = ?")->execute([$organizaciones['eliminada'], $importadorMuerto]);
        //Con el módulo encendido, quien tiene una eliminada no tiene sesión: la herencia solo se alcanza con él apagado.
        $formularioImportar = $camino(DataTransferController::routeName('import-users', [], true));
        $vivoEntra = $leer($formularioImportar, UserSessions::issueToken($importadorVivo));
        $muertoEntra = $leer($formularioImportar, UserSessions::issueToken($importadorMuerto));
        $check($vivoEntra['status'] === 200 && $muertoEntra['status'] !== 200, 'e1 encendido: el importador con la suya eliminada no llega a importar', "vivo HTTP {$vivoEntra['status']}, eliminada HTTP {$muertoEntra['status']}");
        $como = function (int $id): void {
            set_config('current_user', (object) ['id' => $id]);
            set_config('pcsphp_current_user_stored', null);
        };
        $importar = fn (string $sufijo) => (new ImportRunner())->run(new UsersImportDefinition(), new ArrayRowSource(['username', 'email', 'firstname', 'first_lastname', 'organization'], [["{$prefijo}-{$sufijo}", "{$prefijo}-{$sufijo}@example.com", 'Zz', 'Importado', null]]));
        $creadoCon = fn (string $sufijo): ?int => ($fila = $database->query("SELECT organization FROM `{$tablaUsuarios}` WHERE username = " . $database->quote("{$prefijo}-{$sufijo}"))->fetchColumn()) === false ? null : (int) $fila;
        $como($importadorVivo);
        $heredaViva = $importar('hereda-viva');
        $check($heredaViva->persisted() && $creadoCon('hereda-viva') === $organizaciones['activa'], 'e2 CANARIO: hereda la organización viva de quien importa', var_export($creadoCon('hereda-viva'), true));
        $como($importadorMuerto);
        $heredaEliminada = $importar('hereda-eliminada');
        $errorHerencia = implode(' | ', ($heredaEliminada->rowResults()[0] ?? null)?->errors() ?? []);
        $check(!$heredaEliminada->persisted() && $creadoCon('hereda-eliminada') === null && str_contains($errorHerencia, 'Organización: no trae ninguna'), 'e3 DISCRIMINANTE: la eliminada no se hereda; la fila dice que no tiene organización', $errorHerencia);
        $persistida = null;
        try {
            (new UsersImportDefinition())->persist([new \PiecesPHP\Core\DataTransfer\Import\ParsedRow(1, ['username' => "{$prefijo}-persist-eliminada", 'email' => "{$prefijo}-persist-eliminada@example.com", 'firstname' => 'Zz', 'secondname' => null, 'first_lastname' => 'Importado', 'second_lastname' => null, 'password' => null, 'type' => null, 'organization' => null])]);
        } catch (\Throwable $e) {
            $persistida = get_class($e);
        }
        $check($persistida !== null && $creadoCon('persist-eliminada') === null, 'e4 DISCRIMINANTE: persist() sin validación tampoco la hereda', var_export($persistida, true));
    } finally {
        set_config('current_user', null);
        set_config('pcsphp_current_user_stored', null);
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $creados = array_map('intval', $database->query("SELECT id FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijoGeneral}%"))->fetchAll(\PDO::FETCH_COLUMN));
            $organizacionesCreadas = array_map('intval', $database->query("SELECT id FROM `{$tablaOrganizaciones}` WHERE name LIKE " . $database->quote("{$prefijoGeneral}%"))->fetchAll(\PDO::FETCH_COLUMN));
            foreach ([[$tablaUsuarios, $creados], [$tablaOrganizaciones, $organizacionesCreadas]] as [$tablaReferencia, $ids]) {
                foreach ($ids as $id) {
                    $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ? AND referenceValue = ?')->execute([$tablaReferencia, (string) $id]);
                }
            }
            foreach ($creados as $id) {
                $database->prepare('DELETE FROM `' . UserProfileMapper::TABLE . '` WHERE belongsTo = ?')->execute([$id]);
                //La sesión que abre issueToken() para el root de la prueba.
                $database->prepare('DELETE FROM `' . UserSessions::TABLE . '` WHERE userID = ?')->execute([$id]);
            }
            //Las claves foráneas van en los dos sentidos (usuario → organización, organización → createdBy): primero los
            //usuarios a la global, luego las organizaciones y al final los usuarios.
            $database->prepare("UPDATE `{$tablaUsuarios}` SET organization = ? WHERE username LIKE ?")->execute([OrganizationMapper::INITIAL_ID_GLOBAL, "{$prefijoGeneral}%"]);
            foreach ($organizacionesCreadas as $id) {
                $database->prepare("DELETE FROM `{$tablaOrganizaciones}` WHERE id = ?")->execute([$id]);
            }
            $database->prepare("DELETE FROM `{$tablaUsuarios}` WHERE username LIKE ?")->execute(["{$prefijoGeneral}%"]);
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UserSessions::TABLE . "` WHERE userID IN (SELECT id FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijoGeneral}%") . ')')->fetchColumn()
         + (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijoGeneral}%"))->fetchColumn()
         + (int) $database->query("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name LIKE " . $database->quote("{$prefijoGeneral}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario, sesión ni organización de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('Una organización eliminada no se asigna a un usuario: el alta y la edición por HTTP responden como con una organización que no existe (mismo código, éxito y mensaje), la importación no la resuelve ni por su id ni por su código, la ficha de quien ya está en una eliminada la conserva al guardar sin tocarla, y la importación no hereda la eliminada de quien importa (P380).')->setEffects([CliActions::EFFECT_DATABASE])->register();
