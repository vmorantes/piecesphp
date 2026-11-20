<?php

//La recuperación de contraseña por la API responde antes de crear y enviar el código (RespondFirst), con el mismo cuerpo
//para un activo, uno que no existe y uno Eliminado. Por HTTP, con usuarios zz- y correos de Mailpit que crea y retira.
//@areas src/app/classes/API/

use API\Controllers\APIController;
use EventsLog\Mappers\LogsMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use PiecesPHP\UserSystem\Controllers\RecoveryPasswordController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/api-recovery-respond-first', function ($args) {

    echoTerminal("\e[33m[TEST:ApiRecoveryRespondFirst] La recuperación por la API responde antes de enviar, igual exista o no el usuario\e[39m");
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

    if (!$check(API_MODULE, 'p0 el módulo de la API está encendido (API_MODULE)')) {
        return $balance();
    }

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la pantalla de respaldos.
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
    $url = APIController::routeName('users-actions', ['actionType' => 'recovery-password'], true);
    $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
    $ruta = $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;

    $prefijo = 'zz-prueba-api-recup-' . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    $usuarios = [];
    $correoDe = fn (string $sufijo): string => "{$prefijo}-{$sufijo}@example.invalid";
    $nombreDe = fn (string $sufijo): string => "{$prefijo}-{$sufijo}";
    $contar = function (string $sql, array $valores) use ($database): int {
        $consulta = $database->prepare($sql);
        $consulta->execute($valores);
        return (int) $consulta->fetchColumn();
    };
    $codigos = fn (string $sufijo): int => $contar('SELECT COUNT(*) FROM `pcsphp_recovery_password` WHERE email = ?', [$correoDe($sufijo)]);
    //Un código por minuto y correo: se borran los de la prueba para que cada petición del activo cree y envíe.
    $pedir = function (string $sufijo) use ($base, $ruta, $database, $correoDe, $nombreDe): array {
        $database->prepare('DELETE FROM `pcsphp_recovery_password` WHERE email = ?')->execute([$correoDe($sufijo)]);
        $handle = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($handle, [
            CURLOPT_URL => $base . '/' . ltrim($ruta, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['username' => $nombreDe($sufijo)]),
            CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'],
        ]);
        $body = curl_exec($handle);
        $segundos = (float) curl_getinfo($handle, CURLINFO_TOTAL_TIME);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : '', 'ms' => $segundos * 1000];
    };
    $esperar = function (callable $condicion, float $topeSegundos = 6.0): bool {
        $limite = microtime(true) + $topeSegundos;
        do {
            if ($condicion()) {
                return true;
            }
            usleep(200000);
        } while (microtime(true) < $limite);
        return (bool) $condicion();
    };
    //La bandeja de Mailpit es DE LA MÁQUINA: se cuenta y se retira solo lo dirigido a las direcciones de esta pasada.
    $api = 'http://127.0.0.1:8025/api/v1';
    $pedirMailpit = function (string $metodo, string $rutaApi, ?array $cuerpoApi = null) use ($api): ?array {
        $opciones = ['method' => $metodo, 'timeout' => 5, 'ignore_errors' => true];
        if ($cuerpoApi !== null) {
            $opciones['header'] = 'Content-Type: application/json';
            $opciones['content'] = json_encode($cuerpoApi, JSON_THROW_ON_ERROR);
        }
        $respuestaApi = @file_get_contents($api . $rutaApi, false, stream_context_create(['http' => $opciones]));
        $datos = is_string($respuestaApi) ? json_decode($respuestaApi, true) : null;
        return is_array($datos) ? $datos : null;
    };
    $correosA = function (string $direccion) use ($pedirMailpit): array {
        $ids = [];
        foreach ((array) (($pedirMailpit('GET', '/messages?limit=500') ?? [])['messages'] ?? []) as $mensaje) {
            foreach ((array) (is_array($mensaje) ? ($mensaje['To'] ?? []) : []) as $destino) {
                if (is_array($destino) && strcasecmp((string) ($destino['Address'] ?? ''), $direccion) === 0) {
                    $ids[] = (string) ($mensaje['ID'] ?? '');
                    continue 2;
                }
            }
        }
        return array_values(array_filter($ids));
    };
    //Mediana de N peticiones iguales; con $alResponder, cuántas veces su correo ya estaba al llegar la respuesta.
    $medir = function (string $sufijo, int $veces = 7) use ($pedir, $correosA, $correoDe, $esperar): array {
        $tiempos = [];
        $primera = null;
        $yaEstaba = 0;
        foreach (range(1, $veces) as $_) {
            $antes = count($correosA($correoDe($sufijo)));
            $r = $pedir($sufijo);
            if (count($correosA($correoDe($sufijo))) > $antes) {
                $yaEstaba++;
            }
            $primera ??= $r;
            $tiempos[] = $r['ms'];
            //Lo de después de la respuesta termina antes de la siguiente, para no medir dos a la vez.
            $esperar(fn (): bool => count($correosA($correoDe($sufijo))) > $antes, $sufijo === 'activo' ? 6.0 : 0.3);
        }
        sort($tiempos);
        $primera['mediana'] = $tiempos[intdiv(count($tiempos), 2)];
        $primera['yaEstaba'] = $yaEstaba;
        return $primera;
    };
    $resumen = fn (array $r): string => "HTTP {$r['status']}, " . mb_substr($r['body'], 0, 160) . (isset($r['mediana']) ? sprintf(', mediana %.1f ms', $r['mediana']) : '');
    $igual = fn (array $a, array $b): bool => $a['status'] === $b['status'] && $a['body'] === $b['body'];

    $mailpitVivo = $pedirMailpit('GET', '/info') !== null;
    if (!$check($mailpitVivo, 'p2 Mailpit responde en 127.0.0.1:8025 (el sumidero local de los correos de la prueba)')) {
        return $balance();
    }

    try {
        $crearUsuario = function (string $sufijo, int $estado) use ($nombreDe, $correoDe, &$usuarios): int {
            $u = new UsersModel();
            $u->username = $nombreDe($sufijo);
            $u->email = $correoDe($sufijo);
            $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Recupera';
            $u->secondLastname = '';
            $u->type = UsersModel::TYPE_USER_GENERAL;
            $u->status = $estado;
            $u->failedAttempts = 0;
            $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            return (int) $u->id;
        };
        $crearUsuario('activo', UsersModel::STATUS_USER_ACTIVE);
        $crearUsuario('baja', UsersModel::STATUS_USER_DELETED);

        $activo = $medir('activo');
        $nada = $medir('nadie');
        $baja = $medir('baja');
        echoTerminal("   medido: activo {$resumen($activo)}, correo ya en la bandeja al responder {$activo['yaEstaba']}/7");
        echoTerminal("   medido: inexistente {$resumen($nada)}");
        echoTerminal("   medido: eliminado {$resumen($baja)}");

        $esperado = ['send_mail' => true, 'error' => RecoveryPasswordController::NO_ERROR, 'message' => OTPRateLimiter::uniformOTPMessage()];
        $check($activo['status'] === 200 && json_decode($activo['body'], true) === $esperado, 'a1 CANARIO: el activo recibe 200 y la respuesta uniforme', $resumen($activo));
        $check($esperar(fn (): bool => $codigos('activo') > 0) && count($correosA($correoDe('activo'))) >= 7, 'a2 CANARIO: al activo se le crea el código y le LLEGAN sus correos', 'códigos ' . $codigos('activo') . ', correos ' . count($correosA($correoDe('activo'))));
        $check($igual($activo, $nada) && $igual($baja, $nada), 'a3 el mismo código y cuerpo para el activo, el inexistente y el eliminado (ADR 0018)', "activo {$resumen($activo)}; inexistente {$resumen($nada)}; eliminado {$resumen($baja)}");
        $check($activo['yaEstaba'] === 0, 'a4 DISCRIMINANTE: al llegar la respuesta el correo del activo aún no está (se envía después de responder)', "ya estaba en {$activo['yaEstaba']} de 7");
        usleep(1500000);
        $check($codigos('baja') === 0 && count($correosA($correoDe('baja'))) === 0, 'a5 al eliminado no se le crea código ni le llega correo', 'códigos ' . $codigos('baja') . ', correos ' . count($correosA($correoDe('baja'))));
        $registros = fn (string $sufijo): int => $contar('SELECT COUNT(*) FROM `' . LogsMapper::TABLE . '` WHERE referenceSource = ? AND referenceValue = ?', [$tablaUsuarios, (string) $usuarios[$sufijo]]);
        $check($registros('activo') > 0 && $registros('baja') === 0, 'a6 la petición del activo queda en el registro de eventos, después de responder; la del eliminado no', 'activo ' . $registros('activo') . ', eliminado ' . $registros('baja'));
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        //Lo de después de la respuesta puede seguir escribiendo un instante.
        usleep(1500000);
        $correos = array_map($correoDe, ['activo', 'baja', 'nadie']);
        try {
            $propios = [];
            foreach ($correos as $correo) {
                $propios = array_merge($propios, $correosA($correo));
            }
            if ($propios !== []) {
                $pedirMailpit('DELETE', '/messages', ['IDs' => array_values(array_unique($propios))]);
            }
            foreach (['pcsphp_recovery_password', 'pcsphp_tickets_log'] as $tabla) {
                $database->prepare("DELETE FROM `{$tabla}` WHERE email LIKE ?")->execute(["{$prefijo}%"]);
            }
            $database->prepare('DELETE FROM `' . MailLogMapper::TABLE . '` WHERE recipients LIKE ?')->execute(["%{$prefijo}%"]);
            $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '{$tablaUsuarios}' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
            foreach ($usuarios as $id) {
                $database->prepare('DELETE FROM `' . LogsMapper::TABLE . '` WHERE createdBy = ? OR (referenceSource = ? AND referenceValue = ?)')->execute([$id, $tablaUsuarios, (string) $id]);
                $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ? AND referenceValue = ?')->execute([$tablaUsuarios, (string) $id]);
                $database->prepare('DELETE FROM user_system_profile WHERE belongsTo = ?')->execute([$id]);
                foreach ($referencias as [$tabla, $columna]) {
                    $database->prepare("DELETE FROM `{$tabla}` WHERE `{$columna}` = ?")->execute([$id]);
                }
                $database->prepare("DELETE FROM `{$tablaUsuarios}` WHERE id = ?")->execute([$id]);
            }
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $ids = $usuarios !== [] ? implode(',', array_map('intval', $usuarios)) : '0';
        $quedan = [
            'usuarios' => $contar("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE ?", ["{$prefijo}%"]),
            'perfiles' => (int) $database->query("SELECT COUNT(*) FROM user_system_profile WHERE belongsTo IN ({$ids})")->fetchColumn(),
            'registro' => $contar('SELECT COUNT(*) FROM `' . LogsMapper::TABLE . "` WHERE createdBy IN ({$ids}) OR (referenceSource = ? AND referenceValue IN ('" . str_replace(',', "','", $ids) . "'))", [$tablaUsuarios]),
            'recuperacion' => $contar('SELECT COUNT(*) FROM `pcsphp_recovery_password` WHERE email LIKE ?', ["{$prefijo}%"]),
            'tickets' => $contar('SELECT COUNT(*) FROM `pcsphp_tickets_log` WHERE email LIKE ?', ["{$prefijo}%"]),
            'correoLog' => $contar('SELECT COUNT(*) FROM `' . MailLogMapper::TABLE . '` WHERE recipients LIKE ?', ["%{$prefijo}%"]),
            'mailpit' => array_sum(array_map(fn (string $c): int => count($correosA($c)), $correos)),
        ];
        $check(array_sum($quedan) === 0, 'z1 no queda ningún usuario, perfil, registro, código, ticket ni correo de la prueba', json_encode($quedan) ?: '');
    }

    return $balance();

})->setDescription('La recuperación de contraseña por la API (users-actions/recovery-password) responde ANTES de crear y enviar el código (RespondFirst), con el mismo código y cuerpo para un activo, uno que no existe y uno Eliminado; al activo le llega el correo y al Eliminado nada. Por HTTP, con usuarios zz- que crea y retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
