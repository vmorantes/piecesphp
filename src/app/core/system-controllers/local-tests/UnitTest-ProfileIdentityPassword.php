<?php

//Mi perfil pide la contraseña actual para cambiar el usuario o el correo (P206), y ven borradores de publicaciones solo
//quienes pueden editarlas (P207). En proceso; crea usuarios zz-perfil-* y los borra.
//@areas nucleo
//@areas src/app/classes/Publications/

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use PiecesPHP\UserSystem\UserDataPackage;
use Publications\Mappers\PublicationMapper;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/profile-identity-password', function ($args) {

    echoTerminal("\e[33m[TEST:ProfileIdentityPassword] Mi perfil pide la contraseña para el usuario y el correo; borradores, solo quien edita\e[39m");
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

    $database = (new BaseModel())->getDatabase();
    if ($database === null) {
        $check(false, 'p0 hay conexión a la base');
        return $balance();
    }
    $tablaUsuarios = UsersModel::TABLE;
    $prefijo = 'zz-perfil-' . bin2hex(random_bytes(3));
    $global = OrganizationMapper::INITIAL_ID_GLOBAL;
    /** @var array<string, int> $usuarios */
    $usuarios = [];
    /** @var array<string, string> $claves */
    $claves = [];
    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');
    $mensajeErronea = __(UsersController::LANG_GROUP, 'La contraseña es errónea.');

    //La contraseña se comprueba, nunca se lee ni se imprime.
    $fila = function (int $id) use ($database, $tablaUsuarios): array {
        $consulta = $database->prepare("SELECT username, email, firstname FROM `{$tablaUsuarios}` WHERE id = ?");
        $consulta->execute([$id]);
        $resultado = $consulta->fetch(\PDO::FETCH_ASSOC);
        return is_array($resultado) ? $resultado : [];
    };
    //Lo que manda el formulario de perfil: usuario, correo y nombres siempre, con los valores de hoy.
    $cuerpoPerfil = function (string $sufijo, array $cambios) use ($fila, &$usuarios, $global): array {
        $actual = $fila($usuarios[$sufijo]);
        return array_merge([
            'id' => $usuarios[$sufijo], 'is_profile' => 'yes', 'username' => $actual['username'], 'email' => $actual['email'],
            'firstname' => $actual['firstname'], 'first_lastname' => 'Perfil', 'status' => UsersModel::STATUS_USER_ACTIVE,
            'organization' => $global, 'current-password' => '', 'password' => '', 'password2' => '',
        ], $cambios);
    };
    $editar = function (string $como, array $cuerpo) use (&$usuarios): string {
        set_config('current_user', (object) ['id' => $usuarios[$como]]);
        set_config('pcsphp_current_user_stored', null);
        $peticion = (new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz'), new Headers(), [], [], (new StreamFactory())->createStream('')))
            ->withParsedBody($cuerpo);
        $respuesta = json_decode((string) (new UsersController())->edit($peticion, new ResponseRoute())->getBody(), true);
        return is_array($respuesta) ? (string) ($respuesta['message'] ?? '') : '';
    };

    try {
        $crear = function (string $sufijo, int $tipo) use ($prefijo, $global, &$usuarios, &$claves): void {
            $clave = bin2hex(random_bytes(12));
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            $u->password = password_hash($clave, \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Perfil';
            $u->secondLastname = '';
            $u->type = $tipo;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            $u->organization = $global;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            $claves[$sufijo] = $clave;
        };
        $crear('general', UsersModel::TYPE_USER_GENERAL);
        $crear('admin-general', UsersModel::TYPE_USER_ADMIN_GRAL);
        $crear('admin-org', UsersModel::TYPE_USER_ADMIN_ORG);
        $crear('institucional', UsersModel::TYPE_USER_INSTITUCIONAL);
        $crear('comunicaciones', UsersModel::TYPE_USER_COMUNICACIONES);
        $check(count($usuarios) === 5, 'p1 cinco usuarios zz en la organización global', $prefijo);

        echoTerminal(' ');
        echoTerminal('[a] El correo, desde Mi perfil');
        $correo = "{$prefijo}-general-nuevo@example.com";
        $antes = $fila($usuarios['general']);
        $mensaje = $editar('general', $cuerpoPerfil('general', ['email' => $correo]));
        $check($mensaje === $mensajeErronea && $fila($usuarios['general']) === $antes, 'a1 DISCRIMINANTE: sin la contraseña actual, rechazado y el correo intacto', $mensaje);
        $mensaje = $editar('general', $cuerpoPerfil('general', ['email' => $correo, 'current-password' => 'no-es-la-suya']));
        $check($mensaje === $mensajeErronea && $fila($usuarios['general']) === $antes, 'a2 con una contraseña que no es la suya, rechazado', $mensaje);
        $mensaje = $editar('general', $cuerpoPerfil('general', ['email' => $correo, 'current-password' => $claves['general']]));
        $check(($fila($usuarios['general'])['email'] ?? '') === $correo, 'a3 CANARIO: con la suya, guardado', $mensaje);

        echoTerminal(' ');
        echoTerminal('[b] El nombre de usuario, desde Mi perfil');
        $nombreUsuario = "{$prefijo}-general-otro";
        $antes = $fila($usuarios['general']);
        $mensaje = $editar('general', $cuerpoPerfil('general', ['username' => $nombreUsuario]));
        $check($mensaje === $mensajeErronea && $fila($usuarios['general']) === $antes, 'b1 DISCRIMINANTE: sin la contraseña actual, rechazado y el usuario intacto', $mensaje);
        $mensaje = $editar('general', $cuerpoPerfil('general', ['username' => $nombreUsuario, 'current-password' => 'no-es-la-suya']));
        $check($mensaje === $mensajeErronea && $fila($usuarios['general']) === $antes, 'b2 con una contraseña que no es la suya, rechazado', $mensaje);
        $mensaje = $editar('general', $cuerpoPerfil('general', ['username' => $nombreUsuario, 'current-password' => $claves['general']]));
        $check(($fila($usuarios['general'])['username'] ?? '') === $nombreUsuario, 'b3 CANARIO: con la suya, guardado', $mensaje);

        echoTerminal(' ');
        echoTerminal('[c] Lo que no pide la contraseña, como hoy');
        $editar('general', $cuerpoPerfil('general', ['firstname' => 'SoloNombre']));
        $check(($fila($usuarios['general'])['firstname'] ?? '') === 'SoloNombre', 'c1 CANARIO: cambiar solo el nombre, con el usuario y el correo de siempre en el cuerpo, se guarda sin ella');
        $correoAjeno = "{$prefijo}-por-admin@example.com";
        $mensaje = $editar('admin-general', [
            'id' => $usuarios['comunicaciones'], 'username' => "{$prefijo}-comunicaciones", 'email' => $correoAjeno,
            'firstname' => 'Zz', 'first_lastname' => 'Perfil', 'status' => UsersModel::STATUS_USER_ACTIVE, 'organization' => $global,
        ]);
        $check(($fila($usuarios['comunicaciones'])['email'] ?? '') === $correoAjeno, 'c2 un administrador cambia el correo de otro sin su contraseña', $mensaje);

        echoTerminal(' ');
        echoTerminal('[d] Ver borradores: solo quien puede editar publicaciones (canBePreviewedBy, misma organización)');
        $borrador = new PublicationMapper();
        $borrador->createdBy = $usuarios['comunicaciones'];
        $check(!PublicationMapper::canBePreviewedBy($borrador, new UserDataPackage($usuarios['general'])), 'd1 DISCRIMINANTE: el usuario general, no');
        $check(!PublicationMapper::canBePreviewedBy($borrador, new UserDataPackage($usuarios['admin-org'])), 'd2 el administrador de organización, no');
        $check(PublicationMapper::canBePreviewedBy($borrador, new UserDataPackage($usuarios['institucional'])), 'd3 CANARIO: el institucional de la misma organización, sí, como hoy');
        $check(PublicationMapper::canBePreviewedBy($borrador, new UserDataPackage($usuarios['comunicaciones'])), 'd4 CANARIO: comunicaciones, sí, como hoy');
    } catch (\Throwable $e) {
        $check(false, 'e* el recorrido corre entero', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        foreach ($usuarios as $id) {
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $perfiles->delete(['belongsTo' => (int) $id])->execute();
            $database->exec("DELETE FROM `{$tablaUsuarios}` WHERE id = " . (int) $id);
        }
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('Mi perfil pide la contraseña actual para cambiar el usuario o el correo (sin ella o con otra, nada se guarda); el nombre solo, o un administrador editando a otro, no la piden. Y ven borradores solo quienes pueden editar publicaciones: ni el usuario general ni el administrador de organización.')->setEffects([CliActions::EFFECT_DATABASE])->register();
