<?php

//El importador de usuarios: una celda `id` no entra en el SQL, y la columna `type` no crea roots ni administradores.
//El insertador se sustituye en cada caso: la suite solo LEE de la base.

use App\Model\UsersModel;
use Importers\Managers\ImporterUsers;
use PiecesPHP\Core\Importer\Schema;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/importer-users-guards', function ($args) {

    echoTerminal("\e[33m[TEST:ImporterUsersGuards] El importador no inyecta SQL ni crea roots\e[39m");
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

    //─── 1/2 · getByID() ────────────────────────────────────────────────────────────────────────────────
    echoTerminal('[1/2] UsersModel::getByID() no deja entrar SQL por el id');

    $inyectado = (new UsersModel())->getByID("1' OR '1'='1");
    $check($inyectado === null, "getByID(\"1' OR '1'='1\") devuelve null", is_object($inyectado) ? 'devolvió la fila con id ' . var_export($inyectado->id ?? null, true) : var_export($inyectado, true));

    //Una excepción cuenta como fallo, no aborta la suite: sin la validación previa, null da error de SQL.
    $buscar = function ($id): array {
        try {
            return [(new UsersModel())->getByID($id), null];
        } catch (\Throwable $e) {
            return [null, get_class($e) . ': ' . $e->getMessage()];
        }
    };
    foreach ([['null', null], ["''", ''], ["'1abc'", '1abc'], ['0', 0]] as [$etiqueta, $valor]) {
        [$encontrado, $error] = $buscar($valor);
        $check($error === null && $encontrado === null, "getByID({$etiqueta}) devuelve null, sin error", $error ?? (is_object($encontrado) ? 'devolvió la fila con id ' . var_export($encontrado->id ?? null, true) : ''));
    }

    $modelo = (new UsersModel())->getModel();
    $modelo->resetAll();
    $alguno = $modelo->select()->row();
    $idExistente = is_object($alguno) && isset(get_object_vars($alguno)['id']) ? (int) get_object_vars($alguno)['id'] : null;
    $check($idExistente !== null, 'hay al menos un usuario con el que probar un id real');
    if ($idExistente !== null) {
        $real = (new UsersModel())->getByID($idExistente);
        $check(is_object($real) && (int) (get_object_vars($real)['id'] ?? 0) === $idExistente,"getByID({$idExistente}) devuelve ese usuario", is_object($real) ? 'id ' . var_export($real->id ?? null, true) : var_export($real, true));
    }
    echoTerminal(' ');

    //─── 2/2 · ImporterUsers ────────────────────────────────────────────────────────────────────────────
    echoTerminal('[2/2] ImporterUsers, con el insertador sustituido');

    $importar = function (array $extra): array {
        $marca = uniqid();
        $fila = array_merge([
            'username' => "zz-prueba-imp-{$marca}",
            'email' => "zz-prueba-imp-{$marca}@localhost.test",
            'password' => 'x',
            'firstname' => 'Zz',
            'first_lastname' => 'Prueba',
        ], $extra);
        $insertados = [];
        $importer = new ImporterUsers([$fila]);
        //El validador real de email consulta el DNS (MX) y la suite no puede depender de la red: aquí se prueban type e id.
        $importer->getSchema()->getFieldByName('email')->setValidator(function ($value) {
            return is_string($value) && str_contains($value, '@');
        });
        //Sin esto, import() escribiría en pcsphp_users.
        $importer->getSchema()->setAlternativeInsert(function (Schema $instance, array $values) use (&$insertados) {
            $insertados[] = $values;
            return true;
        });
        $importer->import();
        return [$importer->getResponses(), $insertados];
    };
    $resumen = function (array $respuestas): string {
        return implode(' | ', array_map(fn($r) => var_export($r->getSuccess(), true) . ': ' . strip_tags((string) $r->getMessage()), $respuestas));
    };

    [$respuestas, $insertados] = $importar([]);
    $check(count($respuestas) === 1 && $respuestas[0]->getSuccess() === true, 'a) sin columna type: la fila pasa', $resumen($respuestas));
    $check(count($insertados) === 1 && (string) ($insertados[0]['type'] ?? '') === (string) UsersModel::TYPE_USER_GENERAL, 'a) y el type insertado es 2 (general)', json_encode($insertados, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

    [$respuestas, $insertados] = $importar(['type' => '2']);
    $check(count($respuestas) === 1 && $respuestas[0]->getSuccess() === true, "b) type = '2': la fila pasa", $resumen($respuestas));

    [$respuestas, $insertados] = $importar(['type' => '0']);
    $check(count($respuestas) === 1 && $respuestas[0]->getSuccess() === false, "c) type = '0' (root): la fila NO pasa", $resumen($respuestas));
    $check(count($insertados) === 0, "c) y no se insertó nada", json_encode($insertados, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

    [$respuestas, $insertados] = $importar(['type' => '1']);
    $check(count($respuestas) === 1 && $respuestas[0]->getSuccess() === false, "d) type = '1' (administrador): la fila NO pasa", $resumen($respuestas));

    //Un id inyectado que casa con una fila pasa por «duplicado» aunque haya inyección: este no casa con ninguna.
    [$respuestas, $insertados] = $importar(['id' => "0' OR '1'='0"]);
    $check(count($respuestas) === 1 && $respuestas[0]->getSuccess() === false, "e2) id inyectado que no casa con ninguna fila: la fila NO pasa", $resumen($respuestas));
    $check(count($insertados) === 0, "e2) y no se insertó nada", json_encode($insertados, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

    [$respuestas, $insertados] = $importar(['id' => '']);
    $check(count($respuestas) === 1 && $respuestas[0]->getSuccess() === true, "f) id vacío: la fila pasa", $resumen($respuestas));

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El importador de usuarios no deja entrar SQL por el id ni crear roots por la columna type.')->setEffects([CliActions::EFFECT_NONE])->register();
