<?php

//El reparto por país del informe de Informes (P196 b): los dos países con más registros y «otros», sacados de los datos y
//no de dos países fijos. La función se prueba sola; la forma del JSON, por HTTP como root. Crea un root zz- y lo retira.
//@areas src/app/classes/ReportsManage/
//@areas src/app/classes/API/

use API\Controllers\APIController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UserSessionsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use ReportsManage\Queries\ReportsManageQueries;

CliActions::make('unit-tests:core/reports-by-country', function ($args) {

    echoTerminal("\e[33m[TEST:ReportsByCountry] Los dos países con más registros y «otros», sacados de los datos\e[39m");
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
    $fila = fn (?int $country, ?string $name, int $qty): array => ['country' => $country, 'name' => $name, 'qty' => $qty];
    $nombres = fn (array $reparto): string => implode(',', array_column($reparto['byCountry'], 'name'));

    echoTerminal('[a] La función, sola');
    $r = ReportsManageQueries::splitTopCountries([$fila(3, 'Gamma', 1), $fila(1, 'Alfa', 5), $fila(null, null, 2), $fila(2, 'Beta', 3)]);
    $check($nombres($r) === 'Alfa,Beta' && $r['others'] === 3 && $r['total'] === 11, 'a1 tres países y sin país: los dos primeros por cuenta; «otros» lleva el tercero y los sin país', (string) json_encode($r));
    $r = ReportsManageQueries::splitTopCountries([$fila(1, 'Alfa', 2), $fila(2, 'Beta', 4)]);
    $check($nombres($r) === 'Beta,Alfa' && $r['others'] === 0 && $r['total'] === 6, 'a2 dos países: los dos, de más a menos, y «otros» 0', (string) json_encode($r));
    $r = ReportsManageQueries::splitTopCountries([$fila(1, 'Alfa', 2), $fila(null, null, 1)]);
    $check($nombres($r) === 'Alfa' && $r['others'] === 1 && $r['total'] === 3, 'a3 un país: solo él, y los sin país a «otros»', (string) json_encode($r));
    $r = ReportsManageQueries::splitTopCountries([]);
    $check($r['byCountry'] === [] && $r['others'] === 0 && $r['total'] === 0, 'a4 ninguno: sin países, «otros» 0 y total 0', (string) json_encode($r));
    $r = ReportsManageQueries::splitTopCountries([$fila(9, 'Zeta', 3), $fila(1, 'Alfa', 3), $fila(2, 'Beta', 3)]);
    $check($nombres($r) === 'Alfa,Beta' && $r['others'] === 3, 'a5 empate: a igual cuenta, por nombre', (string) json_encode($r));
    echoTerminal(' ');

    echoTerminal('[b] La forma del JSON, por HTTP como root');
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $prefijo = 'zz-agente-informes-' . bin2hex(random_bytes(3));
    $database = (new BaseModel())->getDatabase();
    $idRoot = null;
    try {
        $u = new UsersModel();
        $u->username = "{$prefijo}-root";
        $u->email = "{$prefijo}-root@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Agente';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ROOT;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $idRoot = (int) $u->id;

        $ruta = APIController::routeName('reports-actions', ['actionType' => 'get-generic-data'], true);
        $ruta = '/' . ltrim(str_contains($ruta, '://') ? (string) parse_url($ruta, \PHP_URL_PATH) : $ruta, '/');
        $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
        $ruta = $prefijoBase !== '' && str_starts_with($ruta, $prefijoBase) ? substr($ruta, strlen($prefijoBase)) : $ruta;
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($ruta, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [SessionToken::headerName() . ': ' . UserSessions::issueToken($idRoot)],
        ]);
        $cuerpo = (string) curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        $estado = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $json = json_decode($cuerpo, true);
        $json = is_array($json) ? $json : [];
        $check($estado === 200 && $json !== [], 'b1 la API responde 200 con un JSON', "HTTP {$estado}, " . mb_substr($cuerpo, 0, 120));
        foreach (['peopleData', 'organizationsData'] as $clave) {
            $parte = is_array($json[$clave] ?? null) ? $json[$clave] : [];
            $forma = array_keys($parte) === ['total', 'byCountry', 'others', 'chartData'];
            $cuadra = $forma && $parte['total'] === array_sum(array_column($parte['byCountry'], 'qty')) + $parte['others'];
            $serie = $forma && count($parte['chartData']['series'] ?? []) === count($parte['byCountry']) + 1 && !array_key_exists('colors', $parte['chartData']);
            $check($forma && $cuadra && $serie, "b2 {$clave}: total, byCountry, others y chartData sin colors; el total cuadra y hay una serie por país más «otros»", (string) json_encode($parte));
        }
        $viejas = array_values(array_intersect(['researchersData', 'publicationsProjectionData'], array_keys($json)));
        $check($viejas === [] && array_key_exists('totalApprovedPublicationsQty', $json) && array_key_exists('totalPendingPublicationsQty', $json) && array_key_exists('totalRemainingTokens', $json), 'b3 fuera las claves de antes; las de publicaciones y tokens siguen', implode(', ', $viejas));
    } finally {
        if ($idRoot !== null) {
            $database->prepare('DELETE FROM `' . UserSessionsModel::TABLE . '` WHERE userID = ?')->execute([$idRoot]);
            $database->prepare('DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?')->execute([UsersModel::TABLE, (string) $idRoot]);
            $database->prepare('DELETE FROM `user_system_profile` WHERE belongsTo = ?')->execute([$idRoot]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$idRoot]);
        }
        $quedan = $database->prepare('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ?');
        $quedan->execute(["{$prefijo}%"]);
        $check($idRoot !== null && (int) $quedan->fetchColumn() === 0, 'z1 no queda el usuario de la prueba');
    }
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El reparto por país del informe de Informes: los dos primeros y «otros» desde los datos (la función, sola) y la forma del JSON por HTTP. Crea un root zz- y lo retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
