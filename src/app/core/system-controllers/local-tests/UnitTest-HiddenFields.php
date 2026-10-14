<?php

//Los campos de HIDDEN_FIELDS no salen por ninguna vía. Todo en memoria, con mappers sin guardar, salvo [5]: as_mapper
//solo existe leyendo filas, así que LEE la tabla de usuarios local, no escribe y no imprime ningún valor leído.

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/hidden-fields', function ($args) {

    echoTerminal("\e[33m[TEST:HiddenFields] El hash, los secretos del doble factor y los datos de seguridad no se serializan por ninguna vía\e[39m");
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

    //Un valor centinela por campo oculto: se busca el VALOR en la salida, no solo la clave.
    $userValues = [
        'password' => 'zz-oculto-hash',
        'meta' => 'zz-oculto-meta',
        'failedAttempts' => 9871,
        'sessionsValidFrom' => '1999-12-31 23:59:58',
    ];
    $otpValues = [
        'secret' => 'zz-oculto-secreto',
        'oneUseCode' => 'zz-oculto-codigo',
        'twoAuthFactorSecurityCode' => 'zz-oculto-seguridad',
    ];
    $user = new UsersModel();
    //Un id que no existe: la meta-propiedad de tipo MAPPER solo pide que lo tenga. Nada se guarda ni se lee de la base.
    $user->id = 2147483001;
    $user->username = 'zz-oculto-usuario';
    foreach ($userValues as $field => $value) {
        $user->$field = $value;
    }
    //Con $byKey, además del valor se mira la clave de primer nivel; no vale para un mapper que tiene su PROPIA columna
    //con ese nombre, como la `meta` de una organización.
    $leaks = function ($data, array $values, bool $byKey = true): array {
        $json = (string) json_encode($data);
        $found = [];
        foreach ($values as $field => $value) {
            if (str_contains($json, (string) $value) || ($byKey && is_array($data) && array_key_exists($field, $data))) {
                $found[] = $field;
            }
        }
        return $found;
    };

    echoTerminal('[canario] El instrumento ve');
    $check(UsersModel::hiddenFields() === array_keys($userValues), 'c1 UsersModel declara sus cuatro campos ocultos', json_encode(UsersModel::hiddenFields()) ?: '');
    $check(OTPSecretsUsersMapper::hiddenFields() === array_keys($otpValues), 'c2 OTPSecretsUsersMapper declara sus tres secretos', json_encode(OTPSecretsUsersMapper::hiddenFields()) ?: '');
    $check($user->password === $userValues['password'] && count(array_diff(array_keys($userValues), array_keys(UsersModel::getFields()))) === 0, 'c3 los campos existen en el mapper y el usuario de prueba los lleva puestos');
    $readable = $user->humanReadable();
    $check(($readable['username'] ?? null) === 'zz-oculto-usuario', 'c4 humanReadable() devuelve el resto de la fila: lo de abajo no pasa por salida vacía');
    echoTerminal(' ');

    echoTerminal('[1] El usuario, pedido directamente');
    $check($leaks($readable, $userValues) === [], '1a humanReadable() no entrega ninguno de los cuatro', implode(', ', $leaks($readable, $userValues)));
    $check($leaks($user->jsonSerialize(), $userValues) === [], '1b jsonSerialize() tampoco', implode(', ', $leaks($user->jsonSerialize(), $userValues)));
    $check($leaks(json_decode((string) json_encode($user), true), $userValues) === [], '1c ni un json_encode() del mapper', implode(', ', $leaks(json_decode((string) json_encode($user), true), $userValues)));
    echoTerminal(' ');

    echoTerminal('[2] El usuario como referencia de otro mapper, y como meta-propiedad');
    $organization = new OrganizationMapper();
    $organization->name = 'zz-oculto-organizacion';
    $organization->createdBy = $user;
    $organization->administrator = $user;
    $orgReadable = $organization->humanReadable();
    $check(is_array($orgReadable['createdBy'] ?? null) && ($orgReadable['createdBy']['username'] ?? null) === 'zz-oculto-usuario', '2a CANARIO: la referencia se expande de verdad (recursiveHumanization)', json_encode(array_keys((array) ($orgReadable['createdBy'] ?? []))) ?: '');
    $check($leaks($orgReadable['createdBy'] ?? [], $userValues) === [], '2b la referencia expandida no entrega ninguno de los cuatro', implode(', ', $leaks($orgReadable['createdBy'] ?? [], $userValues)));
    $check(is_array($orgReadable['META:administrator'] ?? null) && ($orgReadable['META:administrator']['username'] ?? null) === 'zz-oculto-usuario', '2c CANARIO: la meta-propiedad se expande de verdad', json_encode(array_keys((array) ($orgReadable['META:administrator'] ?? []))) ?: '');
    $check($leaks($orgReadable['META:administrator'] ?? [], $userValues) === [], '2d la meta-propiedad no entrega ninguno de los cuatro', implode(', ', $leaks($orgReadable['META:administrator'] ?? [], $userValues)));
    $check($leaks(json_decode((string) json_encode($organization), true), $userValues, false) === [], '2e un json_encode() de la organización tampoco arrastra sus valores', implode(', ', $leaks(json_decode((string) json_encode($organization), true), $userValues, false)));
    echoTerminal(' ');

    echoTerminal('[3] Los secretos del doble factor, y su dueño');
    $otp = new OTPSecretsUsersMapper();
    foreach ($otpValues as $field => $value) {
        $otp->$field = $value;
    }
    $otp->user = $user;
    $all = $otpValues + $userValues;
    $check($leaks($otp->humanReadable(), $otpValues) === [], '3a humanReadable() no entrega el secreto ni los códigos', implode(', ', $leaks($otp->humanReadable(), $otpValues)));
    $check($leaks(json_decode((string) json_encode($otp), true), $all, false) === [], '3b un json_encode() no entrega los secretos ni el hash de su dueño', implode(', ', $leaks(json_decode((string) json_encode($otp), true), $all)));
    echoTerminal(' ');

    echoTerminal('[4] La conversión a texto: un `echo $mapper` en una vista');
    $userText = (string) $user;
    $userTextData = json_decode($userText, true);
    $check($leaks($userText, $userValues, false) === [], '4a (string) del usuario no entrega ninguno de los cuatro valores', implode(', ', $leaks($userText, $userValues, false)));
    $check(is_array($userTextData) && count(array_intersect(array_keys($userValues), array_keys($userTextData))) === 0, '4b ni sus claves', is_array($userTextData) ? implode(', ', array_intersect(array_keys($userValues), array_keys($userTextData))) : 'no es JSON');
    $check(is_array($userTextData) && ($userTextData['username'] ?? null) === 'zz-oculto-usuario' && ($userTextData['table'] ?? null) === UsersModel::TABLE, '4c y sigue siendo útil: el resto de la fila y la tabla, no una cadena vacía', substr($userText, 0, 80));
    $otpText = (string) $otp;
    $check($leaks($otpText, $all, false) === [], '4d (string) del doble factor no entrega los secretos ni el hash de su dueño', implode(', ', $leaks($otpText, $all, false)));
    echoTerminal(' ');

    echoTerminal('[5] El camino as_mapper de DataTablesHelper, sobre los usuarios de la base local (solo lectura)');
    $request = (new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/datatables'), new Headers(), [], [], (new StreamFactory())->createStream('')))->withQueryParams(['draw' => 1, 'start' => 0, 'length' => 50]);
    $fieldNames = array_keys(UsersModel::getFields());
    $rows = null;
    $error = null;
    try {
        $result = DataTablesHelper::process(['mapper' => new UsersModel(), 'request' => $request, 'columns_order' => $fieldNames, 'as_mapper' => true]);
        $decoded = json_decode((string) json_encode($result), true);
        $rows = is_array($decoded) ? ($decoded['values']['data'] ?? null) : null;
    } catch (\Throwable $exception) {
        //Solo la clase y el mensaje: el de un acceso a una propiedad nombra el campo, no su valor.
        $error = get_class($exception) . ': ' . $exception->getMessage();
    }
    $check($error === null && is_array($rows), '5a no aborta con un mapper que declara campos ocultos', (string) $error);
    $rows = is_array($rows) ? $rows : [];
    $widths = array_unique(array_map(fn ($row) => is_array($row) ? count($row) : -1, $rows));
    $check(count($rows) > 0 && $widths === [count($fieldNames)], '5b CANARIO: trae filas, y cada una con una celda por campo: nada se corre de columna', count($rows) . ' fila(s), anchos ' . implode(',', $widths) . ' de ' . count($fieldNames));
    $hiddenIndexes = array_keys(array_intersect($fieldNames, UsersModel::hiddenFields()));
    $filled = 0;
    $hashLike = 0;
    foreach ($rows as $row) {
        foreach ($hiddenIndexes as $index) {
            $filled += ($row[$index] ?? '') === '' ? 0 : 1;
        }
        foreach ((array) $row as $cell) {
            $hashLike += is_string($cell) && preg_match('/^\$(2[abxy]|argon2i?d?)\$/', $cell) === 1 ? 1 : 0;
        }
    }
    $check($filled === 0, '5c la celda de cada campo oculto va vacía en todas las filas', "{$filled} celda(s) con valor");
    $check($hashLike === 0, '5d ninguna celda tiene forma de hash de contraseña', "{$hashLike} celda(s)");
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Los campos de HIDDEN_FIELDS (el hash y los datos de seguridad del usuario, los secretos del doble factor) no salen por humanReadable(), su recursión, una meta-propiedad, jsonSerialize(), la conversión a texto ni las tablas de DataTablesHelper.')->setEffects([CliActions::EFFECT_NONE])->register();
