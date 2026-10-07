<?php

//«Mi organización» no responde 500: una organización que no existe da 404 y un usuario sin organización, 403.
//Por HTTP. Crea un principal y un usuario general sin organización, zz-, y los retira.

use MySpace\Controllers\MyOrganizationProfileController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/my-organization-profile', function ($args) {

    echoTerminal("\e[33m[TEST:MyOrganizationProfile] «Mi organización» responde 404 o 403, nunca 500\e[39m");
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
    if (!$check($base !== '', 'c1 hay una base HTTP para pedir', $base)) {
        return $balance();
    }

    $camino = function (string $sufijo, array $parametros = []): string {
        $url = MyOrganizationProfileController::routeName($sufijo, $parametros, true);
        return '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
    };
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::tokenName();
    $pedir = function (string $path, ?string $jwt, ?array $post = null) use ($base, $prefijoBase, $cabeceraToken): array {
        $path = $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
        $handle = curl_init();
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [],
        ];
        if ($post !== null) {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($post);
        }
        curl_setopt_array($handle, $opciones);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };

    $prefijo = 'zz-prueba-mi-org-' . bin2hex(random_bytes(3));
    $ids = [];
    $crearUsuario = function (string $sufijo, int $tipo, ?int $organizacion) use ($prefijo, &$ids): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Mi Organización';
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
        $ids[$sufijo] = (int) $u->id;
        return (int) $u->id;
    };

    try {
        $root = SessionToken::generateToken(['id' => $crearUsuario('root', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL)], null, null, false);
        $general = SessionToken::generateToken(['id' => $crearUsuario('general', UsersModel::TYPE_USER_GENERAL, null)], null, null, false);
        $adminSinOrganizacion = SessionToken::generateToken(['id' => $crearUsuario('admin-sin-org', UsersModel::TYPE_USER_ADMIN_GRAL, null)], null, null, false);
        $inexistente = 2147483000;

        //─── a · Con privilegios superiores ───────────────────────────────────────────────────────
        echoTerminal('[a] El principal: la organización sale de la URL');
        $canario = $pedir($camino('my-organization-profile', ['organizationID' => OrganizationMapper::INITIAL_ID_GLOBAL]), $root);
        $check($canario['status'] === 200, 'a1 CANARIO: la organización global responde 200', "HTTP {$canario['status']}");
        $noExiste = $pedir($camino('my-organization-profile', ['organizationID' => $inexistente]), $root);
        $check($noExiste['status'] === 404, 'a2 una organización que no existe responde 404', "HTTP {$noExiste['status']}");
        //Sin organización en la URL abre la propia (pendientes.md 374.5): el formulario guarda en la global, la suya.
        $sinParametro = $pedir($camino('my-organization-profile'), $root);
        $guardaEnLaPropia = str_contains($sinParametro['body'], $camino('actions-save-profile', ['organizationID' => OrganizationMapper::INITIAL_ID_GLOBAL]));
        $check($sinParametro['status'] === 200 && $guardaEnLaPropia, 'a3 sin organización en la URL abre la suya: 200, y el formulario guarda en su organización', "HTTP {$sinParametro['status']}");
        $guardar = $pedir($camino('actions-save-profile', ['organizationID' => $inexistente]), $root, ['name' => 'zz-no-se-guarda']);
        $check($guardar['status'] === 404, 'a4 guardar una organización que no existe responde 404', "HTTP {$guardar['status']}");
        $encargado = $pedir($camino('actions-change-administrator', ['organizationID' => $inexistente]), $root, ['newUserAdminID' => $ids['root']]);
        $check($encargado['status'] !== 500, 'a5 cambiar el encargado de una que no existe no responde 500', "HTTP {$encargado['status']}");
        $sinPropia = $pedir($camino('my-organization-profile'), $adminSinOrganizacion);
        $check($sinPropia['status'] === 404, 'a6 un administrador general SIN organización, sin indicar ninguna, 404: no hay ninguna que abrir', "HTTP {$sinPropia['status']}");

        //─── b · Un usuario sin organización ──────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[b] Un usuario general sin organización');
        $vista = $pedir($camino('my-organization-profile'), $general);
        $check($vista['status'] === 403, 'b1 su «mi organización» responde 403', "HTTP {$vista['status']}");
        $guardarSin = $pedir($camino('actions-save-profile'), $general, ['name' => 'zz-no-se-guarda']);
        $check($guardarSin['status'] === 403, 'b2 y guardar, 403', "HTTP {$guardarSin['status']}");
        $encargadoSin = $pedir($camino('actions-change-administrator'), $general, ['newUserAdminID' => $ids['general']]);
        $check($encargadoSin['status'] === 403, 'b3 y cambiar el encargado, 403', "HTTP {$encargadoSin['status']}");
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
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
            $usuarios = UsersModel::model();
            $usuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $usuarios = UsersModel::model();
        $usuarios->resetAll();
        $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $check(count((array) $usuarios->result()) === 0, 'z1 no queda ningún usuario de la prueba');
    }

    return $balance();

})->setDescription('«Mi organización»: una organización que no existe responde 404 y un usuario sin organización, 403; nunca 500. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
