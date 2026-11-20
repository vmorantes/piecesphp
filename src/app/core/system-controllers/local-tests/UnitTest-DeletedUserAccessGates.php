<?php

//Las puertas de acceso (y la suplantación) responden a un usuario eliminado como a uno que no existe (P380): mismo código,
//cuerpo y tiempo parecido, sin crearle ni enviarle códigos. Por HTTP, con usuarios zz- que crea y retira.
//@areas nucleo

use EventsLog\Mappers\LogsMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Notifications\Mappers\NotificationMapper;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\OTPHandler;
use PiecesPHP\UserSystem\Authentication\OTPRateLimiter;
use PiecesPHP\UserSystem\Authentication\TOTPStandard;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\ImpersonationController;
use PiecesPHP\UserSystem\Controllers\RecoveryPasswordController;
use PiecesPHP\UserSystem\Controllers\UserProblemsController;
use PiecesPHP\UserSystem\Controllers\UserSystemFeaturesController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/deleted-user-access-gates', function ($args) {

    echoTerminal("\e[33m[TEST:DeletedUserAccessGates] Las puertas de acceso responden a un usuario eliminado como a uno que no existe\e[39m");
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
    if (!$check($base !== '' && $database !== null, 'p1 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }

    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::headerName();
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };

    $prefijo = 'zz-prueba-baja-puertas-' . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    $tablaIntentos = LoginAttemptsModel::TABLE;
    $usuarios = [];
    $correoDe = fn (string $sufijo): string => "{$prefijo}-{$sufijo}@example.invalid";
    $nombreDe = fn (string $sufijo): string => "{$prefijo}-{$sufijo}";

    //El usuario de sistema es de la base: de lo suyo solo se retira lo posterior a estas marcas.
    $sistema = UsersModel::SYSTEM_USER_ID;
    $filaSistema = $database->query("SELECT username, email FROM `{$tablaUsuarios}` WHERE id = {$sistema}")->fetch(\PDO::FETCH_ASSOC);
    $nombresSistema = is_array($filaSistema) ? [(string) $filaSistema['username'], (string) $filaSistema['email']] : [];
    $correoSistema = $nombresSistema[1] ?? '';
    $marca = fn (string $tabla): int => (int) $database->query("SELECT COALESCE(MAX(id), 0) FROM `{$tabla}`")->fetchColumn();
    $marcas = [];
    foreach ([$tablaIntentos, LogsMapper::TABLE, MailLogMapper::TABLE, OTPSecretsUsersMapper::TABLE, 'pcsphp_recovery_password', 'pcsphp_user_problems', 'pcsphp_tickets_log'] as $tablaMarcada) {
        $marcas[$tablaMarcada] = $marca($tablaMarcada);
    }
    $secretosSistema = fn (): string => sha1((string) json_encode($database->query('SELECT * FROM `' . OTPSecretsUsersMapper::TABLE . "` WHERE `user` = {$sistema} ORDER BY id")->fetchAll(\PDO::FETCH_ASSOC)));
    $secretosSistemaAntes = $secretosSistema();

    //Cada intento fallido cuenta para el freno por usuario y por IP: se borran los de la prueba antes de cada petición.
    $limpiarIntentos = function () use ($database, $tablaIntentos, $tablaUsuarios, $prefijo, $sistema, $nombresSistema, &$marcas): void {
        $database->prepare("DELETE FROM `{$tablaIntentos}` WHERE usernameAttempt LIKE ?")->execute(["{$prefijo}%"]);
        $database->prepare("DELETE FROM `{$tablaIntentos}` WHERE userID IN (SELECT id FROM `{$tablaUsuarios}` WHERE username LIKE ?)")->execute(["{$prefijo}%"]);
        if ($nombresSistema !== []) {
            $database->prepare("DELETE FROM `{$tablaIntentos}` WHERE id > ? AND (userID = ? OR usernameAttempt IN (?, ?))")->execute(array_merge([$marcas[$tablaIntentos], $sistema], $nombresSistema));
        }
    };
    $pedir = function (string $metodo, string $path, ?string $jwt, array $datos = []) use ($base, $cabeceraToken, $limpiarIntentos): array {
        $limpiarIntentos();
        $handle = \Terminal\TestRun::curlInit();
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/') . ($metodo === 'GET' && $datos !== [] ? '?' . http_build_query($datos) : ''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array_merge($jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [], ['X-Requested-With: XMLHttpRequest']),
        ];
        if ($metodo === 'POST') {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($datos);
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $body = curl_exec($handle);
        $segundos = (float) curl_getinfo($handle, CURLINFO_TOTAL_TIME);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : '', 'ms' => $segundos * 1000];
    };
    //La primera respuesta y la mediana del tiempo de N peticiones iguales.
    $medir = function (callable $peticion, int $veces = 5): array {
        $primera = null;
        $tiempos = [];
        for ($i = 0; $i < $veces; $i++) {
            $r = $peticion();
            $primera ??= $r;
            $tiempos[] = $r['ms'];
        }
        sort($tiempos);
        $primera['mediana'] = $tiempos[intdiv(count($tiempos), 2)];
        return $primera;
    };
    $json = fn (array $r): array => is_array($decodificado = json_decode($r['body'], true)) ? $decodificado : [];
    //Lo que dice una respuesta sin enseñar un código ni un token.
    $resumen = fn (array $r): string => "HTTP {$r['status']}, " . mb_substr($r['body'], 0, 160) . (isset($r['mediana']) ? sprintf(', mediana %.1f ms', $r['mediana']) : '');
    $igual = fn (array $a, array $b): bool => $a['status'] === $b['status'] && $a['body'] === $b['body'];
    //Tiempo parecido: la diferencia de medianas no pasa de 60 ms ni de la mitad de la menor.
    $tiempoParecido = fn (array $a, array $b): bool => abs($a['mediana'] - $b['mediana']) <= max(60.0, min($a['mediana'], $b['mediana']) * 0.5);
    $contar = function (string $sql, array $valores) use ($database): int {
        $consulta = $database->prepare($sql);
        $consulta->execute($valores);
        return (int) $consulta->fetchColumn();
    };
    //Espera, con tope, a que se cumpla la condición: lo de después de la respuesta llega unos milisegundos más tarde.
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
    //Un código de 6 cifras que no está en la tabla: user_problems busca el código sin mirar el correo.
    $codigoLibre = function (string $tabla) use ($database): string {
        do {
            $codigo = (string) random_int(100000, 999999);
            $consulta = $database->prepare("SELECT COUNT(*) FROM `{$tabla}` WHERE code = ?");
            $consulta->execute([$codigo]);
        } while ((int) $consulta->fetchColumn() > 0);
        return $codigo;
    };
    $plantar = function (string $tabla, string $correo, string $codigo, ?string $tipo = null) use ($database): void {
        $columnas = 'email, code, created, expired' . ($tipo !== null ? ', type' : '');
        $marcadores = '?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR)' . ($tipo !== null ? ', ?' : '');
        $database->prepare("INSERT INTO `{$tabla}` ({$columnas}) VALUES ({$marcadores})")->execute(array_merge([$correo, $codigo], $tipo !== null ? [$tipo] : []));
    };

    $mailpitVivo = $pedirMailpit('GET', '/info') !== null;
    $check($mailpitVivo, 'p2 Mailpit responde en 127.0.0.1:8025 (el sumidero local de los correos de la prueba)');
    $correosSistemaAntes = $mailpitVivo && $correoSistema !== '' ? $correosA($correoSistema) : [];

    try {
        $crearUsuario = function (string $sufijo, int $tipo) use ($nombreDe, $correoDe, &$usuarios): int {
            $u = new UsersModel();
            $u->username = $nombreDe($sufijo);
            $u->email = $correoDe($sufijo);
            $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Puertas';
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
        $activo = $crearUsuario('activo', UsersModel::TYPE_USER_GENERAL);
        $baja = $crearUsuario('baja', UsersModel::TYPE_USER_GENERAL);
        $rootBaja = $crearUsuario('rootbaja', UsersModel::TYPE_USER_ROOT);
        //Doble factor activo y su QR visto, en el activo y en el que se va a dar de baja.
        foreach ([$activo, $baja] as $id) {
            OTPSecretsUsersMapper::toggle2FA($id, true, (string) random_int(10000000, 99999999));
            OTPSecretsUsersMapper::confirm2FA($id);
        }
        //La baja propia solo cambia el estado (UsersController::deleteAccount): aquí, igual.
        $database->exec("UPDATE `{$tablaUsuarios}` SET status = " . UsersModel::STATUS_USER_DELETED . " WHERE id IN ({$baja}, {$rootBaja})");
        $inexistente = (int) $database->query("SELECT COALESCE(MAX(id), 0) + 1000 FROM `{$tablaUsuarios}`")->fetchColumn();
        $check(OTPHandler::isEnabled2FA($activo) && OTPHandler::isEnabled2FA($baja) && OTPRateLimiter::config()['uniformResponse'],
            'p3 banco: el activo y el eliminado con doble factor activo, y generate-otp en modo uniforme');

        //─── a · Suplantación ─────────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[a] Suplantación (root): el id de un eliminado responde como un id que no existe');
        $rutaSuplantar = $camino(ImpersonationController::routeName('start', [], true));
        $suplantar = fn (int $id): array => $pedir('POST', $rutaSuplantar, $root, ['userID' => (string) $id]);
        $r = $suplantar($activo);
        $check($r['status'] === 200 && ($json($r)['success'] ?? null) === true, 'a1 CANARIO: root empieza a actuar como un usuario activo', $resumen($r));
        $nada = $medir(fn (): array => $suplantar($inexistente));
        $eliminado = $medir(fn (): array => $suplantar($baja));
        $eliminadoRoot = $medir(fn (): array => $suplantar($rootBaja));
        echoTerminal("   medido: inexistente {$resumen($nada)}");
        echoTerminal("   medido: eliminado {$resumen($eliminado)}");
        echoTerminal("   medido: eliminado root {$resumen($eliminadoRoot)}");
        $check($igual($eliminado, $nada), 'a2 DISCRIMINANTE: un eliminado responde como un id que no existe (código y cuerpo)', "eliminado {$resumen($eliminado)}; inexistente {$resumen($nada)}");
        $check($igual($eliminadoRoot, $nada), 'a3 DISCRIMINANTE: un root eliminado responde como un id que no existe, no como «otro root»', "eliminado root {$resumen($eliminadoRoot)}; inexistente {$resumen($nada)}");
        $check($tiempoParecido($eliminado, $nada) && $tiempoParecido($eliminadoRoot, $nada), 'a4 y en un tiempo parecido', sprintf('%.1f / %.1f / %.1f ms', $eliminado['mediana'], $eliminadoRoot['mediana'], $nada['mediana']));

        //─── b · Recuperación de contraseña ───────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[b] Recuperación de contraseña: al eliminado no se le crea ni se le envía código, ni le sirve uno de antes');
        $rutaPedirCodigo = $camino(RecoveryPasswordController::routeName('recovery-password-request-code', [], true));
        $codigosRecuperacion = fn (string $sufijo): int => $contar('SELECT COUNT(*) FROM `pcsphp_recovery_password` WHERE email = ?', [$correoDe($sufijo)]);
        $pedirCodigo = fn (string $sufijo): array => $pedir('POST', $rutaPedirCodigo, null, ['username' => $nombreDe($sufijo)]);
        $correosBajaAntes = $mailpitVivo ? count($correosA($correoDe('baja'))) : 0;
        $activoR = $medir(fn (): array => $pedirCodigo('activo'));
        $check($esperar(fn (): bool => $codigosRecuperacion('activo') > 0) && (!$mailpitVivo || $esperar(fn (): bool => count($correosA($correoDe('activo'))) > 0)),
            'b1 CANARIO: el activo recibe su código (guardado y en Mailpit)', 'códigos ' . $codigosRecuperacion('activo'));
        $nada = $medir(fn (): array => $pedirCodigo('nadie'));
        $eliminado = $medir(fn (): array => $pedirCodigo('baja'));
        echoTerminal("   medido: pedir código, activo {$resumen($activoR)}");
        echoTerminal("   medido: pedir código, inexistente {$resumen($nada)}");
        echoTerminal("   medido: pedir código, eliminado {$resumen($eliminado)}");
        $check($igual($eliminado, $nada) && $igual($activoR, $nada), 'b2 pedir el código: la misma respuesta para los tres (ADR 0018)', "eliminado {$resumen($eliminado)}; inexistente {$resumen($nada)}");
        $check($tiempoParecido($eliminado, $nada), 'b3 y en un tiempo parecido', sprintf('%.1f / %.1f ms', $eliminado['mediana'], $nada['mediana']));
        //Lo de después de la respuesta: se le da el mismo plazo que al activo.
        usleep(2500000);
        //Solo los de esta puerta: los de una puerta anterior siguen en la bandeja.
        $correosBaja = $mailpitVivo ? count($correosA($correoDe('baja'))) - $correosBajaAntes : 0;
        $check($codigosRecuperacion('baja') === 0 && $correosBaja === 0, 'b4 DISCRIMINANTE: al eliminado no se le crea código ni le llega correo', 'códigos ' . $codigosRecuperacion('baja') . ", correos {$correosBaja}");

        $rutaVerificar = $camino(RecoveryPasswordController::routeName('new-password-verify-code', [], true));
        $codigoActivo = $codigoLibre('pcsphp_recovery_password');
        $codigoBaja = $codigoLibre('pcsphp_recovery_password');
        $plantar('pcsphp_recovery_password', $correoDe('activo'), $codigoActivo);
        $plantar('pcsphp_recovery_password', $correoDe('baja'), $codigoBaja);
        $plantar('pcsphp_recovery_password', $correoDe('nadie'), $codigoBaja);
        $r = $pedir('POST', $rutaVerificar, null, ['username' => $nombreDe('activo'), 'code' => $codigoActivo]);
        $check(($json($r)['success'] ?? null) === true, 'b5 CANARIO: el código vigente del activo se verifica', "HTTP {$r['status']}, success " . var_export($json($r)['success'] ?? null, true));
        $nada = $medir(fn (): array => $pedir('POST', $rutaVerificar, null, ['username' => $nombreDe('nadie'), 'code' => $codigoBaja]));
        $eliminado = $medir(fn (): array => $pedir('POST', $rutaVerificar, null, ['username' => $nombreDe('baja'), 'code' => $codigoBaja]));
        echoTerminal("   medido: verificar un código vigente, inexistente {$resumen($nada)}");
        echoTerminal("   medido: verificar un código vigente, eliminado {$resumen($eliminado)}");
        $check($igual($eliminado, $nada) && ($json($eliminado)['success'] ?? null) !== true, 'b6 DISCRIMINANTE: un código vigente del eliminado responde como el de uno que no existe', "eliminado {$resumen($eliminado)}; inexistente {$resumen($nada)}");
        $check($tiempoParecido($eliminado, $nada), 'b7 y en un tiempo parecido', sprintf('%.1f / %.1f ms', $eliminado['mediana'], $nada['mediana']));

        $rutaCambiar = $camino(RecoveryPasswordController::routeName('new-password-create-code', [], true));
        $hash = fn (int $id): string => (string) $database->query("SELECT password FROM `{$tablaUsuarios}` WHERE id = {$id}")->fetchColumn();
        $clave = bin2hex(random_bytes(10));
        $cambiar = fn (string $sufijo, string $codigo): array => $pedir('POST', $rutaCambiar, null, ['username' => $nombreDe($sufijo), 'code' => $codigo, 'password' => $clave, 'repassword' => $clave]);
        $hashBaja = $hash($baja);
        $nada = $cambiar('nadie', $codigoBaja);
        $eliminado = $cambiar('baja', $codigoBaja);
        echoTerminal("   medido: cambiar la contraseña con el código, inexistente {$resumen($nada)}");
        echoTerminal("   medido: cambiar la contraseña con el código, eliminado {$resumen($eliminado)}");
        $check($igual($eliminado, $nada) && $hash($baja) === $hashBaja, 'b8 DISCRIMINANTE: con su código, el eliminado no cambia la contraseña y responde como uno que no existe', "eliminado {$resumen($eliminado)}; inexistente {$resumen($nada)}; hash " . ($hash($baja) === $hashBaja ? 'igual' : 'CAMBIADO'));
        $hashActivo = $hash($activo);
        $r = $cambiar('activo', $codigoActivo);
        $check(($json($r)['success'] ?? null) === true && $hash($activo) !== $hashActivo, 'b9 CANARIO: el activo cambia su contraseña con su código', "HTTP {$r['status']}, success " . var_export($json($r)['success'] ?? null, true));

        //─── c · Código de un uso (generate-otp) ──────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[c] Código de un uso: al eliminado no se le genera ni se le envía');
        $rutaOTP = $camino(UserSystemFeaturesController::routeName('generate-otp', [], true));
        $codigoOTP = fn (int $id): int => $contar('SELECT COUNT(*) FROM `' . OTPSecretsUsersMapper::TABLE . '` WHERE `user` = ? AND method = ? AND oneUseCode IS NOT NULL AND oneUseCode <> ?', [$id, OTPSecretsUsersMapper::METHOD_ONE_USE_CODE, '']);
        //El cuerpo repite el nombre pedido (values.user): se compara sin él.
        $sinNombre = function (array $r) use ($json): string {
            $d = $json($r);
            unset($d['values']['user']);
            return (string) json_encode($d);
        };
        $otp = fn (string $sufijo): array => $pedir('GET', $rutaOTP, null, ['username' => $nombreDe($sufijo)]);
        $correosBajaAntes = $mailpitVivo ? count($correosA($correoDe('baja'))) : 0;
        $activoR = $medir(fn (): array => $otp('activo'));
        $check($esperar(fn (): bool => $codigoOTP($activo) > 0), 'c1 CANARIO: al activo se le genera su código de un uso', 'códigos ' . $codigoOTP($activo));
        $nada = $medir(fn (): array => $otp('nadie'));
        $eliminado = $medir(fn (): array => $otp('baja'));
        echoTerminal("   medido: activo {$resumen($activoR)}");
        echoTerminal("   medido: inexistente {$resumen($nada)}");
        echoTerminal("   medido: eliminado {$resumen($eliminado)}");
        $check($eliminado['status'] === $nada['status'] && $sinNombre($eliminado) === $sinNombre($nada) && $sinNombre($activoR) === $sinNombre($nada), 'c2 la misma respuesta para los tres, salvo el nombre que se pidió', "eliminado {$resumen($eliminado)}; inexistente {$resumen($nada)}");
        $check($tiempoParecido($eliminado, $nada), 'c3 y en un tiempo parecido', sprintf('%.1f / %.1f ms', $eliminado['mediana'], $nada['mediana']));
        usleep(2500000);
        //Solo los de esta puerta: los de una puerta anterior siguen en la bandeja.
        $correosBaja = $mailpitVivo ? count($correosA($correoDe('baja'))) - $correosBajaAntes : 0;
        $check($codigoOTP($baja) === 0 && $correosBaja === 0, 'c4 DISCRIMINANTE: al eliminado no se le genera código ni le llega correo', 'códigos ' . $codigoOTP($baja) . ", correos {$correosBaja}");
        //La búsqueda que usa el acceso (login) no cambia: solo las puertas que dicen si alguien existe dejan fuera al 6.
        $check(OTPHandler::getUserDataByUsernameUnlessDeleted($nombreDe('baja')) === null && OTPHandler::getUserDataByUsernameUnlessDeleted($nombreDe('activo')) !== null,
            'c5 la búsqueda de las puertas no da con el eliminado y sí con el activo');
        $check(OTPHandler::getUserDataByUsername($nombreDe('baja')) !== null, 'c6 la búsqueda del acceso sigue igual (no se tocó el login)');

        //─── d · Usuario olvidado ─────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[d] Usuario olvidado: al eliminado no se le envía su nombre, ni le sirve un código de antes');
        $rutaOlvido = $camino(UserProblemsController::routeName('forget-request-code', [], true));
        $codigosProblemas = fn (string $sufijo): int => $contar('SELECT COUNT(*) FROM `pcsphp_user_problems` WHERE email = ?', [$correoDe($sufijo)]);
        $olvido = fn (string $sufijo): array => $pedir('POST', $rutaOlvido, null, ['username' => $correoDe($sufijo), 'type' => UserProblemsController::TYPE_USER_FORGET]);
        $correosActivoAntes = $mailpitVivo ? count($correosA($correoDe('activo'))) : 0;
        $correosBajaAntes = $mailpitVivo ? count($correosA($correoDe('baja'))) : 0;
        $activoR = $medir(fn (): array => $olvido('activo'), 1);
        $check($esperar(fn (): bool => $codigosProblemas('activo') > 0) && (!$mailpitVivo || $esperar(fn (): bool => count($correosA($correoDe('activo'))) > $correosActivoAntes)),
            'd1 CANARIO: el activo recibe su código (guardado y en Mailpit)', 'códigos ' . $codigosProblemas('activo'));
        $nada = $medir(fn (): array => $olvido('nadie'));
        $eliminado = $medir(fn (): array => $olvido('baja'));
        echoTerminal("   medido: activo {$resumen($activoR)}");
        echoTerminal("   medido: inexistente {$resumen($nada)}");
        echoTerminal("   medido: eliminado {$resumen($eliminado)}");
        $check($igual($eliminado, $nada) && $igual($activoR, $nada), 'd2 la misma respuesta para los tres', "eliminado {$resumen($eliminado)}; inexistente {$resumen($nada)}");
        $check($tiempoParecido($eliminado, $nada), 'd3 y en un tiempo parecido', sprintf('%.1f / %.1f ms', $eliminado['mediana'], $nada['mediana']));
        usleep(2500000);
        //Solo los de esta puerta: los de una puerta anterior siguen en la bandeja.
        $correosBaja = $mailpitVivo ? count($correosA($correoDe('baja'))) - $correosBajaAntes : 0;
        $check($codigosProblemas('baja') === 0 && $correosBaja === 0, 'd4 DISCRIMINANTE: al eliminado no se le crea código ni le llega correo', 'códigos ' . $codigosProblemas('baja') . ", correos {$correosBaja}");

        $rutaNombre = $camino(UserProblemsController::routeName('forget-get', [], true));
        $codigoActivo = $codigoLibre('pcsphp_user_problems');
        $plantar('pcsphp_user_problems', $correoDe('activo'), $codigoActivo, UserProblemsController::TYPE_USER_FORGET);
        $r = $pedir('POST', $rutaNombre, null, ['code' => $codigoActivo, 'type' => UserProblemsController::TYPE_USER_FORGET]);
        $check(($json($r)['success'] ?? null) === true && ($json($r)['username'] ?? null) === $nombreDe('activo'), 'd5 CANARIO: con su código, el activo recibe su nombre de usuario', "HTTP {$r['status']}, success " . var_export($json($r)['success'] ?? null, true));
        //Un código vigente cuyo correo no es de nadie: la respuesta de «el usuario del código ya no existe».
        $codigoNadie = $codigoLibre('pcsphp_user_problems');
        $plantar('pcsphp_user_problems', $correoDe('nadie'), $codigoNadie, UserProblemsController::TYPE_USER_FORGET);
        $codigoBaja = $codigoLibre('pcsphp_user_problems');
        $plantar('pcsphp_user_problems', $correoDe('baja'), $codigoBaja, UserProblemsController::TYPE_USER_FORGET);
        $nada = $pedir('POST', $rutaNombre, null, ['code' => $codigoNadie, 'type' => UserProblemsController::TYPE_USER_FORGET]);
        $eliminado = $pedir('POST', $rutaNombre, null, ['code' => $codigoBaja, 'type' => UserProblemsController::TYPE_USER_FORGET]);
        echoTerminal('   medido: pedir el nombre con un código vigente, inexistente HTTP ' . $nada['status'] . ', success ' . var_export($json($nada)['success'] ?? null, true) . ', ¿trae nombre? ' . (isset($json($nada)['username']) ? 'sí' : 'no'));
        echoTerminal('   medido: pedir el nombre con un código vigente, eliminado HTTP ' . $eliminado['status'] . ', success ' . var_export($json($eliminado)['success'] ?? null, true) . ', ¿trae nombre? ' . (isset($json($eliminado)['username']) ? 'sí' : 'no'));
        $check($igual($eliminado, $nada) && !str_contains($eliminado['body'], $nombreDe('baja')), 'd6 DISCRIMINANTE: con un código de antes, el eliminado no recibe su nombre y responde como uno que no existe', 'eliminado success ' . var_export($json($eliminado)['success'] ?? null, true) . '; inexistente success ' . var_export($json($nada)['success'] ?? null, true));

        //─── e · Estado del doble factor ──────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[e] Estado del doble factor (pública): el eliminado responde como uno que no existe');
        $rutaDobleFactor = $camino(UserSystemFeaturesController::routeName('two-factor-auth-status', [], true));
        $dobleFactor = fn (string $sufijo): array => $pedir('POST', $rutaDobleFactor, null, ['username' => $nombreDe($sufijo)]);
        $activoR = $medir(fn (): array => $dobleFactor('activo'));
        $check(($json($activoR)['required'] ?? null) === true, 'e1 CANARIO: el activo con doble factor da required true', $resumen($activoR));
        $nada = $medir(fn (): array => $dobleFactor('nadie'));
        $eliminado = $medir(fn (): array => $dobleFactor('baja'));
        echoTerminal("   medido: activo {$resumen($activoR)}");
        echoTerminal("   medido: inexistente {$resumen($nada)}");
        echoTerminal("   medido: eliminado {$resumen($eliminado)}");
        $check($igual($eliminado, $nada), 'e2 DISCRIMINANTE: el eliminado con doble factor responde como uno que no existe', "eliminado {$resumen($eliminado)}; inexistente {$resumen($nada)}");
        $check($tiempoParecido($eliminado, $nada), 'e3 y en un tiempo parecido', sprintf('%.1f / %.1f ms', $eliminado['mediana'], $nada['mediana']));

        //─── f · Verificar el TOTP ────────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[f] Verificar el TOTP (pública): el eliminado responde como uno que no existe, también con su código correcto');
        $rutaTOTP = $camino(UserSystemFeaturesController::routeName('check-totp', [], true));
        //El código vigente se calcula en cada petición (cambia cada 30 s) y nunca se imprime.
        $totpDe = function (int $id): string {
            $paquete = new UserDataPackage($id);
            return $paquete->TOTPData !== null ? (string) (new TOTPStandard($paquete->TOTPData->plainSecret()))->generateTOTP() : '';
        };
        $comprobar = fn (string $nombre, string $codigo): array => $pedir('POST', $rutaTOTP, null, ['username' => $nombre, 'totp' => $codigo]);
        //No es numérico: no casa con ningún TOTP, y obliga a comparar con el código de respaldo si se llega a mirar.
        $codigoMalo = '00000x';
        $r = $comprobar($nombreDe('activo'), $totpDe($activo));
        $check($totpDe($activo) !== '' && ($json($r)['success'] ?? null) === true, 'f1 CANARIO: el activo con su TOTP vigente recibe «Código aceptado.»', "HTTP {$r['status']}, success " . var_export($json($r)['success'] ?? null, true));
        $activoR = $medir(fn (): array => $comprobar($nombreDe('activo'), $codigoMalo));
        $nada = $medir(fn (): array => $comprobar($nombreDe('nadie'), $codigoMalo));
        $eliminado = $medir(fn (): array => $comprobar($nombreDe('baja'), $codigoMalo));
        $eliminadoBueno = $medir(fn (): array => $comprobar($nombreDe('baja'), $totpDe($baja)));
        echoTerminal("   medido: activo, código malo {$resumen($activoR)}");
        echoTerminal("   medido: inexistente, código malo {$resumen($nada)}");
        echoTerminal("   medido: eliminado, código malo {$resumen($eliminado)}");
        echoTerminal("   medido: eliminado, su código vigente {$resumen($eliminadoBueno)}");
        $check($igual($activoR, $nada), 'f2 un código malo: el activo y el inexistente reciben lo mismo', "activo {$resumen($activoR)}; inexistente {$resumen($nada)}");
        $check($igual($eliminado, $nada), 'f3 DISCRIMINANTE: el eliminado con un código malo responde como uno que no existe', "eliminado {$resumen($eliminado)}; inexistente {$resumen($nada)}");
        $check($totpDe($baja) !== '' && $igual($eliminadoBueno, $nada), 'f4 DISCRIMINANTE: con su código vigente, el eliminado no recibe «Código aceptado.»', "eliminado {$resumen($eliminadoBueno)}; inexistente {$resumen($nada)}");
        $check($tiempoParecido($eliminado, $nada) && $tiempoParecido($eliminadoBueno, $nada), 'f5 y en un tiempo parecido', sprintf('%.1f / %.1f / %.1f ms', $eliminado['mediana'], $eliminadoBueno['mediana'], $nada['mediana']));

        //─── g · El usuario de sistema ────────────────────────────────────────────────────────────
        echoTerminal(' ');
        echoTerminal('[g] El usuario de sistema (id ' . $sistema . '), por su nombre y por su correo, responde como uno que no existe');
        if ($check(count($nombresSistema) === 2, 'g0 el usuario de sistema está en la base, con nombre y correo')) {
            $correosSistemaNuevos = fn (): int => $mailpitVivo ? count(array_diff($correosA($correoSistema), $correosSistemaAntes)) : 0;
            foreach ($nombresSistema as $n => $nombreSistema) {
                $cual = $n === 0 ? 'nombre' : 'correo';
                $nada = $medir(fn (): array => $otp('nadie'));
                $deSistema = $medir(fn (): array => $pedir('GET', $rutaOTP, null, ['username' => $nombreSistema]));
                echoTerminal("   medido: generate-otp, sistema por {$cual} {$resumen($deSistema)}");
                $check($deSistema['status'] === $nada['status'] && $sinNombre($deSistema) === $sinNombre($nada) && $tiempoParecido($deSistema, $nada),
                    "g1.{$n} generate-otp por {$cual}: la misma respuesta que un inexistente, en un tiempo parecido", "sistema {$resumen($deSistema)}; inexistente {$resumen($nada)}");
                $nada = $medir(fn (): array => $comprobar($nombreDe('nadie'), $codigoMalo));
                $deSistema = $medir(fn (): array => $comprobar($nombreSistema, $codigoMalo));
                echoTerminal("   medido: check-totp, sistema por {$cual} {$resumen($deSistema)}");
                $check($igual($deSistema, $nada) && $tiempoParecido($deSistema, $nada), "g2.{$n} check-totp por {$cual}: como un inexistente", "sistema {$resumen($deSistema)}; inexistente {$resumen($nada)}");
                $nada = $medir(fn (): array => $dobleFactor('nadie'));
                $deSistema = $medir(fn (): array => $pedir('POST', $rutaDobleFactor, null, ['username' => $nombreSistema]));
                $check($igual($deSistema, $nada), "g3.{$n} two-factor-auth-status por {$cual}: como un inexistente", "sistema {$resumen($deSistema)}; inexistente {$resumen($nada)}");
                $nada = $pedirCodigo('nadie');
                $deSistema = $pedir('POST', $rutaPedirCodigo, null, ['username' => $nombreSistema]);
                $check($igual($deSistema, $nada), "g4.{$n} pedir el código de recuperación por {$cual}: como un inexistente", "sistema {$resumen($deSistema)}; inexistente {$resumen($nada)}");
            }
            $nada = $olvido('nadie');
            $deSistema = $pedir('POST', $rutaOlvido, null, ['username' => $correoSistema, 'type' => UserProblemsController::TYPE_USER_FORGET]);
            $check($igual($deSistema, $nada), 'g5 usuario olvidado por su correo: como un inexistente', "sistema {$resumen($deSistema)}; inexistente {$resumen($nada)}");
            usleep(2500000);
            $codigosSistema = $contar('SELECT COUNT(*) FROM `pcsphp_recovery_password` WHERE email = ? AND id > ?', [$correoSistema, $marcas['pcsphp_recovery_password']]);
            $codigosSistema += $contar('SELECT COUNT(*) FROM `pcsphp_user_problems` WHERE email = ? AND id > ?', [$correoSistema, $marcas['pcsphp_user_problems']]);
            $check($secretosSistema() === $secretosSistemaAntes && $codigosSistema === 0 && $correosSistemaNuevos() === 0,
                'g6 DISCRIMINANTE: al usuario de sistema no se le guarda ningún código ni se le envía correo',
                'secretos ' . ($secretosSistema() === $secretosSistemaAntes ? 'iguales' : 'CAMBIADOS') . ", códigos {$codigosSistema}, correos " . $correosSistemaNuevos());
            $check(OTPHandler::getUserDataByUsernameUnlessDeleted($nombresSistema[0] ?? '') === null && OTPHandler::getUserDataByUsername($nombresSistema[0] ?? '') !== null,
                'g7 la búsqueda de las puertas no da con el usuario de sistema; la del acceso sigue igual');
        }
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        //Lo de después de la respuesta puede seguir escribiendo un instante.
        usleep(1500000);
        $correos = array_map($correoDe, ['root', 'activo', 'baja', 'rootbaja', 'nadie']);
        try {
            if ($mailpitVivo) {
                $propios = [];
                foreach ($correos as $correo) {
                    $propios = array_merge($propios, $correosA($correo));
                }
                if ($propios !== []) {
                    $pedirMailpit('DELETE', '/messages', ['IDs' => array_values(array_unique($propios))]);
                }
            }
            $database->prepare("DELETE FROM `{$tablaIntentos}` WHERE usernameAttempt LIKE ?")->execute(["{$prefijo}%"]);
            foreach (['pcsphp_recovery_password', 'pcsphp_user_problems', 'pcsphp_tickets_log'] as $tabla) {
                $database->prepare("DELETE FROM `{$tabla}` WHERE email LIKE ?")->execute(["{$prefijo}%"]);
            }
            $database->prepare('DELETE FROM `' . MailLogMapper::TABLE . '` WHERE recipients LIKE ?')->execute(["%{$prefijo}%"]);
            $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '{$tablaUsuarios}' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
            foreach ($usuarios as $id) {
                $database->prepare('DELETE FROM `' . UserSessions::TABLE . '` WHERE userID = ?')->execute([$id]);
                $database->prepare("DELETE FROM `{$tablaIntentos}` WHERE userID = ?")->execute([$id]);
                $database->prepare('DELETE FROM `' . NotificationMapper::TABLE . '` WHERE `user` = ?')->execute([$id]);
                $database->prepare('DELETE FROM `' . OTPSecretsUsersMapper::TABLE . '` WHERE `user` = ?')->execute([$id]);
                $database->prepare('DELETE FROM `' . LogsMapper::TABLE . '` WHERE createdBy = ? OR (referenceSource = ? AND referenceValue = ?)')->execute([$id, $tablaUsuarios, (string) $id]);
                $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ? AND referenceValue = ?')->execute([$tablaUsuarios, (string) $id]);
                $database->prepare('DELETE FROM user_system_profile WHERE belongsTo = ?')->execute([$id]);
                foreach ($referencias as [$tabla, $columna]) {
                    $database->prepare("DELETE FROM `{$tabla}` WHERE `{$columna}` = ?")->execute([$id]);
                }
                $database->prepare("DELETE FROM `{$tablaUsuarios}` WHERE id = ?")->execute([$id]);
            }
            //Lo del usuario de sistema: solo lo posterior a las marcas del principio, y los correos nuevos a su dirección.
            if ($correoSistema !== '') {
                $nuevosSistema = $mailpitVivo ? array_values(array_diff($correosA($correoSistema), $correosSistemaAntes)) : [];
                if ($nuevosSistema !== []) {
                    $pedirMailpit('DELETE', '/messages', ['IDs' => $nuevosSistema]);
                }
                $database->prepare("DELETE FROM `{$tablaIntentos}` WHERE id > ? AND (userID = ? OR usernameAttempt IN (?, ?))")->execute(array_merge([$marcas[$tablaIntentos], $sistema], $nombresSistema));
                $database->prepare('DELETE FROM `' . OTPSecretsUsersMapper::TABLE . '` WHERE id > ? AND `user` = ?')->execute([$marcas[OTPSecretsUsersMapper::TABLE], $sistema]);
                $database->prepare('DELETE FROM `' . LogsMapper::TABLE . '` WHERE id > ? AND (createdBy = ? OR (referenceSource = ? AND referenceValue = ?))')->execute([$marcas[LogsMapper::TABLE], $sistema, $tablaUsuarios, (string) $sistema]);
                $database->prepare('DELETE FROM `' . MailLogMapper::TABLE . '` WHERE id > ? AND recipients LIKE ?')->execute([$marcas[MailLogMapper::TABLE], "%{$correoSistema}%"]);
                foreach (['pcsphp_recovery_password', 'pcsphp_user_problems', 'pcsphp_tickets_log'] as $tabla) {
                    $database->prepare("DELETE FROM `{$tabla}` WHERE id > ? AND email = ?")->execute([$marcas[$tabla], $correoSistema]);
                }
            }
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $ids = $usuarios !== [] ? implode(',', array_map('intval', $usuarios)) : '0';
        $quedan = [
            'usuarios' => $contar("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE ?", ["{$prefijo}%"]),
            'perfiles' => (int) $database->query("SELECT COUNT(*) FROM user_system_profile WHERE belongsTo IN ({$ids})")->fetchColumn(),
            'sesiones' => (int) $database->query('SELECT COUNT(*) FROM `' . UserSessions::TABLE . "` WHERE userID IN ({$ids})")->fetchColumn(),
            'intentos' => $contar("SELECT COUNT(*) FROM `{$tablaIntentos}` WHERE userID IN ({$ids}) OR usernameAttempt LIKE ?", ["{$prefijo}%"]),
            'avisos' => (int) $database->query('SELECT COUNT(*) FROM `' . NotificationMapper::TABLE . "` WHERE `user` IN ({$ids})")->fetchColumn(),
            'secretos' => (int) $database->query('SELECT COUNT(*) FROM `' . OTPSecretsUsersMapper::TABLE . "` WHERE `user` IN ({$ids})")->fetchColumn(),
            'registro' => $contar('SELECT COUNT(*) FROM `' . LogsMapper::TABLE . "` WHERE createdBy IN ({$ids}) OR (referenceSource = ? AND referenceValue IN ('" . str_replace(',', "','", $ids) . "'))", [$tablaUsuarios]),
            'recuperacion' => $contar('SELECT COUNT(*) FROM `pcsphp_recovery_password` WHERE email LIKE ?', ["{$prefijo}%"]),
            'problemas' => $contar('SELECT COUNT(*) FROM `pcsphp_user_problems` WHERE email LIKE ?', ["{$prefijo}%"]),
            'tickets' => $contar('SELECT COUNT(*) FROM `pcsphp_tickets_log` WHERE email LIKE ?', ["{$prefijo}%"]),
            'correoLog' => $contar('SELECT COUNT(*) FROM `' . MailLogMapper::TABLE . '` WHERE recipients LIKE ?', ["%{$prefijo}%"]),
            'mailpit' => $mailpitVivo ? array_sum(array_map(fn (string $c): int => count($correosA($c)), $correos)) : 0,
            'sistemaSecretos' => $secretosSistema() === $secretosSistemaAntes ? 0 : 1,
            'sistemaCorreos' => $mailpitVivo && $correoSistema !== '' ? count(array_diff($correosA($correoSistema), $correosSistemaAntes)) : 0,
        ];
        $check(array_sum($quedan) === 0, 'z1 no queda ningún usuario, perfil, sesión, intento, aviso, secreto, registro, código, ticket ni correo de la prueba', json_encode($quedan) ?: '');
    }

    return $balance();

})->setDescription('Un usuario eliminado (status 6, por su baja propia) recibe en las puertas de acceso lo mismo que uno que no existe (P380): la suplantación de root (también si era root), la recuperación de contraseña (pedir, verificar y cambiar con código), el código de un uso, el usuario olvidado (pedir y canjear el código), el estado del doble factor y la verificación del TOTP (también con su código vigente); y el usuario de sistema, por nombre y por correo, igual que uno que no existe; mismo código, mismo cuerpo, tiempo parecido, y sin códigos ni correos para ninguno de los dos. Por HTTP, con usuarios zz- que crea y retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
