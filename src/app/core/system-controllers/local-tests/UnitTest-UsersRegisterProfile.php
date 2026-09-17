<?php

//El alta de usuarios crea el perfil: UsersModel::save() tiene que dejar el id insertado en el mapper.
//Todo dentro de una transacción de la conexión compartida que se revierte: la base queda como estaba.

use App\Controller\UsersController;
use App\Model\UsersModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/users-register-profile', function ($args) {

    echoTerminal("\e[33m[TEST:UsersRegisterProfile] El alta de usuarios deja el id y crea el perfil\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };

    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');
    $pdo = UsersModel::model()::getDb(Config::app_db('default')['db']);
    $marca = 'zz-prueba-reg-' . bin2hex(random_bytes(3));

    $pdo->beginTransaction();
    try {
        //─── 1/2 · UsersModel::save() ───────────────────────────────────────────────────────────────────
        echoTerminal('[1/2] UsersModel::save() deja el id insertado');
        $usuario = new UsersModel();
        $usuario->username = "{$marca}-modelo";
        $usuario->email = "{$marca}-modelo@example.com";
        $usuario->password = password_hash('zz-Clave-1', \PASSWORD_DEFAULT);
        $usuario->firstname = 'Zz';
        $usuario->first_lastname = 'Prueba';
        $usuario->type = UsersModel::TYPE_USER_GENERAL;
        $usuario->status = UsersModel::STATUS_USER_ACTIVE;
        $usuario->failed_attempts = 0;
        $usuario->organization = \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL;
        $usuario->created_at = new \DateTime();
        $usuario->modified_at = $usuario->created_at;
        $guardado = $usuario->save();
        $insertado = (int) $usuario->getInsertIDOnSave();
        $check($guardado && $insertado > 0 && $usuario->id === $insertado, 'm1 tras save(), ->id es el id insertado, como entero', var_export($usuario->id, true) . ' frente a ' . $insertado);
        echoTerminal(' ');

        //─── 2/2 · El alta real ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[2/2] UsersController::register() crea el usuario y su perfil');
        $root = UsersModel::model();
        $root->resetAll();
        $root->select()->where(['type' => UsersModel::TYPE_USER_ROOT])->execute();
        $filasRoot = (array) $root->result();
        $idRoot = isset($filasRoot[0]->id) ? (int) $filasRoot[0]->id : 0;
        set_config('current_user', (object) ['id' => $idRoot]);
        set_config('pcsphp_current_user_stored', null);

        $nombre = "{$marca}-alta";
        $peticion = (new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-prueba'), new Headers(), [], [], (new StreamFactory())->createStream('')))->withParsedBody([
            'username' => $nombre,
            'email' => "{$nombre}@example.com",
            'password' => 'zz-Clave-1',
            'password2' => 'zz-Clave-1',
            'firstname' => 'Zz',
            'secondname' => '',
            'first_lastname' => 'Prueba',
            'second_lastname' => '',
            'type' => (string) UsersModel::TYPE_USER_GENERAL,
            'status' => (string) UsersModel::STATUS_USER_ACTIVE,
            'organization' => (string) \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL,
        ]);
        $respuesta = (new UsersController())->register($peticion, new ResponseRoute());
        $cuerpo = json_decode((string) $respuesta->getBody(), true);
        $modelo = UsersModel::model();
        $modelo->resetAll();
        $modelo->select()->where(['username' => $nombre])->execute();
        $fila = ((array) $modelo->result())[0] ?? null;
        $check($idRoot > 0 && ($cuerpo['success'] ?? null) === true && $fila !== null, 'a1 el alta responde success y el usuario existe', mb_substr((string) $respuesta->getBody(), 0, 160));
        $check($fila !== null && UserProfileMapper::getProfile((int) $fila->id) !== null, 'a2 y tiene perfil');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        $restos = UsersModel::model();
        $restos->resetAll();
        $restos->select()->where(new \PiecesPHP\Core\Database\ORM\Statements\WhereSegment([\PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem::like('username', "{$marca}%")]))->execute();
        $cuantos = count((array) $restos->result());
        $check($cuantos === 0, 'z1 la transacción se revirtió: 0 usuarios de la prueba', "restos {$cuantos}");
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El alta de usuarios deja el id insertado en el mapper y crea el perfil.')->setEffects([CliActions::EFFECT_DATABASE])->register();
