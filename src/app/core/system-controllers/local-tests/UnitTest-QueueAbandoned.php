<?php

//La cola: lo abandonado se reanima o falla (292.1), un trabajo lo toma un solo trabajador, lo fallido lleva su fin y
//lo viejo se purga (G10). Crea filas zz- en la cola local y las borra; la purga, con un «ahora» de 2001.

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\Terminal\QueueHandlerResponse;
use PiecesPHP\Terminal\QueueTask;
use Terminal\Mappers\QueueJobMapper;
use Terminal\Tasks\ProcessQueueTask;

CliActions::make('unit-tests:core/queue-abandoned', function ($args) {

    echoTerminal("\e[33m[TEST:QueueAbandoned] Un trabajo abandonado vuelve a la cola o se marca fallido; uno vivo no se toca\e[39m");
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
    if (!$check($database !== null, 'c0 hay conexión a la base')) {
        return $balance();
    }

    $tabla = QueueJobMapper::TABLE;
    $prefijo = 'zz-abandonada-' . bin2hex(random_bytes(3));
    $creadas = [];

    //La edad se FABRICA por SQL, no esperando: un umbral de 30 minutos medido con el reloj haría
    //la prueba inservible, y ya nos mordió una vez (opcache, bloque CM).
    $crear = function (string $sufijo, string $estado, int $intentos, int $tope, ?int $minutosDeAntiguedad) use ($database, $tabla, $prefijo, &$creadas): int {
        $job = new QueueJobMapper();
        $job->name = "{$prefijo}-{$sufijo}";
        $job->data = ['zz' => true];
        $job->status = $estado;
        $job->attempts = $intentos;
        $job->maxAttempts = $tope;
        $job->createdAt = date('Y-m-d H:i:s');
        $job->updatedAt = date('Y-m-d H:i:s');
        $job->save();
        $id = (int) $job->id;
        $creadas[] = $id;
        if ($minutosDeAntiguedad !== null) {
            $marca = date('Y-m-d H:i:s', time() - ($minutosDeAntiguedad * 60));
            $statement = $database->prepare("UPDATE `{$tabla}` SET startedAt = ?, updatedAt = ? WHERE id = ?");
            $statement->execute([$marca, $marca, $id]);
        }
        return $id;
    };
    $estadoDe = function (int $id) use ($database, $tabla): ?string {
        $statement = $database->prepare("SELECT status FROM `{$tabla}` WHERE id = ?");
        $statement->execute([$id]);
        $valor = $statement->fetchColumn();
        return is_string($valor) ? $valor : null;
    };
    $campoDe = function (int $id, string $campo) use ($database, $tabla) {
        $statement = $database->prepare("SELECT `{$campo}` FROM `{$tabla}` WHERE id = ?");
        $statement->execute([$id]);
        return $statement->fetchColumn();
    };

    $viejo = ProcessQueueTask::ABANDONED_AFTER_MINUTES + 5;
    $ids = [];

    try {

        //─── El banco de pruebas ────────────────────────────────────────────────────────────────
        $ids['reanimable'] = $crear('reanimable', QueueJobMapper::STATUS_RUNNING, 1, 3, $viejo);
        $ids['agotada'] = $crear('agotada', QueueJobMapper::STATUS_RUNNING, 3, 3, $viejo);
        $ids['reciente'] = $crear('reciente', QueueJobMapper::STATUS_RUNNING, 1, 3, 1);
        $ids['pendiente'] = $crear('pendiente', QueueJobMapper::STATUS_PENDING, 0, 3, null);
        $ids['completada'] = $crear('completada', QueueJobMapper::STATUS_COMPLETED, 1, 3, $viejo);
        $check(count(array_filter($ids)) === 5, 'c1 las cinco filas de prueba están creadas: ' . implode(', ', $ids));

        //─── La pasada de recuperación ──────────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[a] La recuperación, con el umbral de ' . ProcessQueueTask::ABANDONED_AFTER_MINUTES . ' minutos');
        $lineas = ProcessQueueTask::reclaimAbandoned();
        $check(count($lineas) === 2, 'a1 dice haber tocado DOS trabajos, no más: ' . count($lineas), implode(' | ', array_map('strip_tags', $lineas)));

        //a) con intentos → pending
        $check($estadoDe($ids['reanimable']) === QueueJobMapper::STATUS_PENDING,
            'a2 la abandonada CON intentos vuelve a «pending»', (string) $estadoDe($ids['reanimable']));

        //b) sin intentos → failed, con motivo
        $check($estadoDe($ids['agotada']) === QueueJobMapper::STATUS_FAILED,
            'a3 la abandonada SIN intentos pasa a «failed»', (string) $estadoDe($ids['agotada']));
        $motivo = (string) $campoDe($ids['agotada'], 'errorMessage');
        $check(str_contains($motivo, 'murió'), 'a4 y su motivo dice que su proceso murió', $motivo);
        $check((string) $campoDe($ids['agotada'], 'finishedAt') !== '', 'a5 y queda con fecha de fin');

        //c) EL CANARIO DEL UMBRAL: la reciente no se toca
        $check($estadoDe($ids['reciente']) === QueueJobMapper::STATUS_RUNNING,
            'a6 CANARIO: la RECIENTE sigue en «running», no se le toca', (string) $estadoDe($ids['reciente']));

        //d) las que no están en running, intactas
        $check($estadoDe($ids['pendiente']) === QueueJobMapper::STATUS_PENDING, 'a7 la pendiente sigue pendiente');
        $check($estadoDe($ids['completada']) === QueueJobMapper::STATUS_COMPLETED, 'a8 la completada sigue completada, aunque su marca sea vieja');

        //Y el recuento de intentos se respeta: recuperar no gasta un intento.
        $check((int) $campoDe($ids['reanimable'], 'attempts') === 1, 'a9 recuperar NO gasta un intento: sigue en 1', (string) $campoDe($ids['reanimable'], 'attempts'));

        //─── b · Idempotente: una segunda pasada no toca nada ───────────────────────────────────
        echoTerminal('');
        echoTerminal('[b] Una segunda pasada no vuelve a tocar nada');
        $segunda = ProcessQueueTask::reclaimAbandoned();
        $check(count($segunda) === 0, 'b1 la segunda pasada no recupera nada: ' . count($segunda));
        $check($estadoDe($ids['reciente']) === QueueJobMapper::STATUS_RUNNING, 'b2 y la reciente sigue intacta');

        //─── c · Un trabajo lo toma un solo trabajador ─────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[c] Tomar es un UPDATE condicional: de dos tomas, gana una');
        $ids['disputada'] = $crear('disputada', QueueJobMapper::STATUS_PENDING, 0, 3, null);
        $primera = QueueJobMapper::claim($ids['disputada']);
        $segunda = QueueJobMapper::claim($ids['disputada']);
        $check($primera && !$segunda, 'c1 la primera toma gana y la segunda no', var_export([$primera, $segunda], true));
        $check($estadoDe($ids['disputada']) === QueueJobMapper::STATUS_RUNNING && (int) $campoDe($ids['disputada'], 'attempts') === 1
            && (string) $campoDe($ids['disputada'], 'startedAt') !== '', 'c2 queda «en curso», con su inicio y UN intento');

        //─── d · Lo fallido lleva su fin ───────────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[d] Un trabajo que falla queda con fecha de fin');
        $lanza = "{$prefijo}-lanza";
        //Un \Error: QueueTask::execute() atrapa las Exception y las vuelve un fallo; lo que se le escapa llega aquí.
        QueueTask::addQueueHandler(QueueTask::make($lanza, function (): QueueHandlerResponse {
            throw new \Error('zz fallo de prueba');
        }));
        $agota = "{$prefijo}-agota";
        QueueTask::addQueueHandler(QueueTask::make($agota, fn(): QueueHandlerResponse => QueueHandlerResponse::fail('zz sin más intentos')));
        $ids['lanza'] = $crear('lanza', QueueJobMapper::STATUS_PENDING, 0, 3, null);
        $ids['agota'] = $crear('agota', QueueJobMapper::STATUS_PENDING, 0, 1, null);
        $ids['sin-manejador'] = $crear('sin-manejador', QueueJobMapper::STATUS_PENDING, 0, 3, null);
        ProcessQueueTask::processPending();
        foreach (['lanza' => 'una excepción', 'agota' => 'los intentos agotados', 'sin-manejador' => 'un manejador que no existe'] as $clave => $como) {
            $check($estadoDe($ids[$clave]) === QueueJobMapper::STATUS_FAILED && (string) $campoDe($ids[$clave], 'finishedAt') !== '',
                "d1 «failed» por {$como}, con fecha de fin", (string) $estadoDe($ids[$clave]));
        }

        //─── e · La purga ──────────────────────────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[e] Se purga lo terminado de más de ' . QueueJobMapper::PURGE_COMPLETED_DAYS . ' días y lo fallido de más de ' . QueueJobMapper::PURGE_FAILED_DAYS);
        //Un «ahora» de 2001 y fechas de 2000-2001: la purga no puede alcanzar ninguna fila real.
        $fechar = function (int $id, string $fin) use ($database, $tabla): void {
            $statement = $database->prepare("UPDATE `{$tabla}` SET finishedAt = ?, updatedAt = ?, createdAt = ? WHERE id = ?");
            //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION; lo comprueba la purga que sigue.
            $statement->execute([$fin, $fin, $fin, $id]);
        };
        $ahora = new \DateTime('2001-03-01 00:00:00');
        $ids['vieja-completada'] = $crear('vieja-completada', QueueJobMapper::STATUS_COMPLETED, 1, 3, null);
        $fechar($ids['vieja-completada'], '2001-01-01 00:00:00');
        $ids['reciente-completada'] = $crear('reciente-completada', QueueJobMapper::STATUS_COMPLETED, 1, 3, null);
        $fechar($ids['reciente-completada'], '2001-02-20 00:00:00');
        $ids['vieja-fallida'] = $crear('vieja-fallida', QueueJobMapper::STATUS_FAILED, 1, 3, null);
        $fechar($ids['vieja-fallida'], '2000-11-01 00:00:00');
        $ids['reciente-fallida'] = $crear('reciente-fallida', QueueJobMapper::STATUS_FAILED, 1, 3, null);
        $fechar($ids['reciente-fallida'], '2001-01-01 00:00:00');
        $purgadas = QueueJobMapper::purge($ahora);
        $check($estadoDe($ids['vieja-completada']) === null && $estadoDe($ids['vieja-fallida']) === null, 'e1 la completada de hace 59 días y la fallida de hace 120 se borran', json_encode($purgadas) ?: '');
        $check($estadoDe($ids['reciente-completada']) === QueueJobMapper::STATUS_COMPLETED && $estadoDe($ids['reciente-fallida']) === QueueJobMapper::STATUS_FAILED,
            'e2 la completada de hace 9 días y la fallida de hace 59 se quedan');
        $check($purgadas === ['completed' => 1, 'failed' => 1], 'e3 y no borra nada más: una y una', json_encode($purgadas) ?: '');

    } catch (\Throwable $e) {
        $check(false, 'la prueba corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    }

    //─── z · Limpieza ───────────────────────────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal('[z] Limpieza: las filas de la prueba salen de la cola');
    $borradas = 0;
    foreach ($creadas as $id) {
        $statement = $database->prepare("DELETE FROM `{$tabla}` WHERE id = ? AND name LIKE ?");
        $statement->execute([$id, "{$prefijo}%"]);
        $borradas += $statement->rowCount();
    }
    $quedan = $database->prepare("SELECT COUNT(*) FROM `{$tabla}` WHERE name LIKE ?");
    $quedan->execute(["{$prefijo}%"]);
    $check((int) $quedan->fetchColumn() === 0, "z1 no queda ninguna fila {$prefijo}-* ({$borradas} borradas)");

    return $balance();

})->setDescription('La cola: un trabajo cuyo proceso murió vuelve a «pending» si le quedan intentos o pasa a «failed» si no, uno reciente no se toca y recuperar no gasta un intento; de dos tomas del mismo trabajo gana una; lo fallido (excepción, intentos agotados, sin manejador) lleva su fin; y la purga borra lo terminado de más de 30 días y lo fallido de más de 90, nada más.')->setEffects([CliActions::EFFECT_DATABASE])->register();
