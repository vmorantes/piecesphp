<?php

//La cola de ida y vuelta (D15, 11): lo encola dispatch(), lo toma claim() y lo procesa processJob(), con trabajos suyos
//y sin tocar los demás pendientes. Y un fallo sin reintento no se reintenta (G10, 2). Crea filas zz- y las borra.

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\Terminal\QueueHandlerResponse;
use PiecesPHP\Terminal\QueueTask;
use Terminal\Mappers\QueueJobMapper;
use Terminal\Tasks\ProcessQueueTask;

CliActions::make('unit-tests:core/queue-round-trip', function ($args) {

    echoTerminal("\e[33m[TEST:QueueRoundTrip] La cola, de dispatch() a la fila terminada\e[39m");
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

    $database = (new BaseModel())->getDatabase();
    $tabla = QueueJobMapper::TABLE;
    $prefijo = 'zz-prueba-queue-round-trip-' . bin2hex(random_bytes(4));
    $ids = [];

    $recibido = null;
    $exito = "{$prefijo}-exito";
    QueueTask::addQueueHandler(QueueTask::make($exito, function ($data) use (&$recibido): QueueHandlerResponse {
        $recibido = $data;
        return QueueHandlerResponse::success('zz hecho');
    }));
    $sinReintento = "{$prefijo}-sin-reintento";
    QueueTask::addQueueHandler(QueueTask::make($sinReintento, fn(): QueueHandlerResponse => QueueHandlerResponse::fail('zz falla sin reintento', false)));
    $conReintento = "{$prefijo}-con-reintento";
    QueueTask::addQueueHandler(QueueTask::make($conReintento, fn(): QueueHandlerResponse => QueueHandlerResponse::fail('zz falla con reintento', true)));

    //Encola, toma y procesa SOLO este trabajo; devuelve la fila releída.
    $recorrer = function (string $nombre, array $datos) use (&$ids): ?QueueJobMapper {
        $id = QueueTask::dispatch($nombre, $datos);
        if ($id === null) {
            return null;
        }
        $ids[] = (int) $id;
        if (!QueueJobMapper::claim((int) $id)) {
            return null;
        }
        ProcessQueueTask::processJob(new QueueJobMapper((int) $id), QueueTask::getQueueHandlers());
        return new QueueJobMapper((int) $id);
    };

    try {
        echoTerminal('[1] Ida y vuelta: dispatch(), claim() y processJob()');
        $datos = ['zz' => 'x', 'n' => 7];
        $fila = $recorrer($exito, $datos);
        $check($fila !== null && $fila->status === QueueJobMapper::STATUS_COMPLETED, '1a la fila queda completed', $fila === null ? 'sin fila' : (string) $fila->status);
        $check($fila !== null && (int) $fila->attempts === 1, '1b con un intento', $fila === null ? '' : (string) $fila->attempts);
        $check($fila !== null && $fila->finishedAt !== null, '1c con fecha de fin');
        $check($recibido === $datos, '1d el manejador recibió exactamente los datos encolados', var_export($recibido, true));
        echoTerminal(' ');

        echoTerminal('[2] Un fallo sin reintento');
        $fila = $recorrer($sinReintento, []);
        $check($fila !== null && $fila->status === QueueJobMapper::STATUS_FAILED, '2a queda failed en el primer intento', $fila === null ? 'sin fila' : (string) $fila->status);
        $check($fila !== null && (int) $fila->attempts === 1 && (int) $fila->maxAttempts === 3, '2b con 1 intento de 3', $fila === null ? '' : "{$fila->attempts}/{$fila->maxAttempts}");
        $check($fila !== null && $fila->errorMessage === 'zz falla sin reintento', '2c con su mensaje', $fila === null ? '' : var_export($fila->errorMessage, true));
        echoTerminal(' ');

        echoTerminal('[3] Un fallo con reintento (como hoy)');
        $fila = $recorrer($conReintento, []);
        $check($fila !== null && $fila->status === QueueJobMapper::STATUS_PENDING, '3a vuelve a pending', $fila === null ? 'sin fila' : (string) $fila->status);
        $check($fila !== null && $fila->scheduledAt !== null, '3b con fecha programada');
        $check($fila !== null && (int) $fila->attempts === 1, '3c con un intento', $fila === null ? '' : (string) $fila->attempts);
    } finally {
        foreach ($ids as $id) {
            $statement = $database->prepare("DELETE FROM `{$tabla}` WHERE id = ? AND name LIKE ?");
            $statement->execute([$id, "{$prefijo}%"]);
        }
        $quedan = $database->prepare("SELECT COUNT(*) FROM `{$tabla}` WHERE name LIKE ?");
        $quedan->execute(["{$prefijo}%"]);
        $check((int) $quedan->fetchColumn() === 0, "z1 no queda ninguna fila {$prefijo}-*");
    }
    echoTerminal(' ');

    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La cola de ida y vuelta: dispatch(), claim() y processJob() con trabajos propios (éxito con sus datos, fallo sin reintento que queda failed y fallo con reintento que vuelve a pending). Crea filas zz- y las borra.')->setEffects([CliActions::EFFECT_DATABASE])->register();
