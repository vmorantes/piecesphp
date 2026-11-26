<?php

//El formulario de usuario por tipo (ADR 0089): el de su carpeta si existe y el genérico si no, en alta, edición y perfil.
//Con PCSPHP_FORM_MARK=<marca> exige verla en la edición de un general y no en la de otro tipo. Crea usuarios zz- y los retira.
//@areas nucleo
//@requires-module Organizations\OrganizationsRoutes

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\UserSessionsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

CliActions::make('unit-tests:core/user-form-by-type', function ($args) {

    echoTerminal("\e[33m[TEST:UserFormByType] El formulario de usuario por tipo, con el genérico de respaldo\e[39m");
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

    //─── a · La vista que elige el controlador ─────────────────────────────────────────────────────
    echoTerminal('[a] La vista que elige el controlador');
    $controlador = new UsersController();
    $elegir = new \ReflectionMethod(UsersController::class, 'formViewByType');
    $generico = 'usuarios/form-by-type/user-form';
    foreach (['create', 'edit', 'profile'] as $modo) {
        foreach (UsersController::FORM_TYPE_FOLDERS as $codigo => $carpeta) {
            $vista = $elegir->invoke($controlador, $modo, $codigo);
            $check($vista === "usuarios/form-by-type/{$modo}/{$carpeta}/form", "a1 {$modo}/{$carpeta}: el de su carpeta", $vista);
        }
    }
    //Un tipo propio: sin carpeta, el genérico; con `type-<código>`, el suyo. La carpeta es de la prueba y se retira.
    $propio = 987654;
    $vistas = rtrim((string) $controlador->getInstanceViewDir(), '/\\') . '/usuarios/form-by-type/edit';
    $carpetaPropia = "{$vistas}/type-{$propio}";
    $check($elegir->invoke($controlador, 'edit', $propio) === $generico, 'a2 un tipo propio sin carpeta: el genérico');
    try {
        $creada = is_dir($carpetaPropia) || mkdir($carpetaPropia, 0775, true);
        $check($creada && file_put_contents("{$carpetaPropia}/form.php", "<?php\n//zz-prueba: formulario de un tipo propio.\n") !== false, 'a3 la carpeta temporal del tipo propio');
        $vista = $elegir->invoke($controlador, 'edit', $propio);
        $check($vista === "usuarios/form-by-type/edit/type-{$propio}/form", 'a4 un tipo propio con type-<código>: el suyo', $vista);
        $check($elegir->invoke($controlador, 'create', $propio) === $generico, 'a5 y en otro modo sin carpeta, el genérico');
        //RETORNO-IGNORADO: lo comprueba a7, que cae si el archivo no quedó vacío.
        file_put_contents("{$carpetaPropia}/form.php", '');
        $check(filesize("{$carpetaPropia}/form.php") === 0 && $elegir->invoke($controlador, 'edit', $propio) === $generico, 'a7 vaciado, cuenta como ausente: el genérico');
    } finally {
        if (is_file("{$carpetaPropia}/form.php")) {
            //RETORNO-IGNORADO: lo comprueba a6.
            unlink("{$carpetaPropia}/form.php");
        }
        if (is_dir($carpetaPropia)) {
            //RETORNO-IGNORADO: lo comprueba a6.
            rmdir($carpetaPropia);
        }
    }
    clearstatcache();
    $check(!file_exists($carpetaPropia), 'a6 la carpeta temporal se retiró');

    //─── b · Las tres pantallas por HTTP ──────────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal('[b] Las tres pantallas, por HTTP');
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    if (!$check($base !== '', 'b0 hay una base HTTP para pedir', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $camino = function (string $url) use ($prefijoBase): string {
        $url = '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
        return $prefijoBase !== '' && str_starts_with($url, $prefijoBase) ? substr($url, strlen($prefijoBase)) : $url;
    };
    $cabeceraToken = SessionToken::headerName();
    $pedir = function (string $path, string $jwt) use ($base, $cabeceraToken): array {
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . $path,
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

    $database = (new BaseModel())->getDatabase();
    $prefijo = 'zz-prueba-form-tipo-' . bin2hex(random_bytes(3));
    $usuarios = [];
    $organizacion = null;
    $crear = function (string $sufijo, int $tipo, ?int $org) use ($prefijo, &$usuarios): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Formulario';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        if ($org !== null) {
            $u->organization = $org;
        }
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        if (!$u->save() || (int) $u->id < 1) {
            throw new \RuntimeException("No se pudo crear el usuario {$sufijo}.");
        }
        $usuarios[] = (int) $u->id;
        return (int) $u->id;
    };
    $marca = (string) (getenv('PCSPHP_FORM_MARK') ?: '');

    try {
        $o = new OrganizationMapper();
        $o->name = "{$prefijo}-org";
        $o->nit = "{$prefijo}-nit";
        $o->folder = "{$prefijo}-org";
        $o->status = OrganizationMapper::ACTIVE;
        $o->save();
        $organizacion = (int) $o->id;
        $root = $crear('root', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL);
        $general = $crear('general', UsersModel::TYPE_USER_GENERAL, null);
        $adminOrg = $crear('admin-org', UsersModel::TYPE_USER_ADMIN_ORG, $organizacion);
        $jwtRoot = UserSessions::issueToken($root);
        $paginas = [];
        foreach (['general' => [$general, UsersModel::TYPE_USER_GENERAL], 'admin-organization' => [$adminOrg, UsersModel::TYPE_USER_ADMIN_ORG]] as $carpeta => [$id, $tipo]) {
            $alta = $pedir($camino(UsersController::routeName('form-create', ['type' => UsersController::signedType($tipo)], true)), $jwtRoot);
            $edicion = $pedir($camino(UsersController::routeName('form-edit', ['id' => $id], true)), $jwtRoot);
            $perfil = $pedir($camino(UsersController::routeName('form-profile', [], true)), UserSessions::issueToken($id));
            foreach (['create' => $alta, 'edit' => $edicion, 'profile' => $perfil] as $modo => $r) {
                $check($r['status'] === 200 && str_contains($r['body'], "users {$modo} {$carpeta}"), "b1 {$modo}/{$carpeta}: 200 y su formulario", "HTTP {$r['status']}");
            }
            $paginas[$carpeta] = $edicion['body'];
        }
        if ($marca !== '') {
            $check(str_contains($paginas['general'] ?? '', $marca), "b2 la marca «{$marca}» sale en la edición de un general");
            $check(!str_contains($paginas['admin-organization'] ?? '', $marca), 'b3 y no en la de un administrador de organización');
        }
    } catch (\Throwable $e) {
        $check(false, 'b0 la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        foreach ($usuarios as $id) {
            $database->prepare('DELETE FROM `' . UserSessionsModel::TABLE . '` WHERE userID = ?')->execute([$id]);
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $perfiles->delete(['belongsTo' => $id])->execute();
        }
        $usuariosModelo = UsersModel::model();
        $usuariosModelo->resetAll();
        //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
        $usuariosModelo->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        if ($organizacion !== null) {
            $database->prepare('DELETE FROM `' . OrganizationMapper::TABLE . '` WHERE id = ?')->execute([$organizacion]);
        }
        $restos = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $restos += $organizacion !== null ? (int) $database->query('SELECT COUNT(*) FROM `' . OrganizationMapper::TABLE . '` WHERE id = ' . (int) $organizacion)->fetchColumn() : 0;
        $check($restos === 0, 'z1 no queda nada de la prueba', "quedan {$restos}");
    }

    return $balance();

})->setDescription('El formulario de usuario por tipo (ADR 0089): cada tipo de fábrica pinta el de su carpeta, un tipo propio el de type-<código> si existe y el genérico si no; las pantallas de alta, edición y perfil responden con su formulario.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
