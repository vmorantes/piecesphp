<?php

//QueueTask::dispatch() devuelve el id de la tarea encolada (database 5.2.0: save() deja el id). Escribe una tarea zz
//en la cola local y la borra al final.

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\Terminal\QueueTask;
use Terminal\Mappers\QueueJobMapper;

CliActions::make('unit-tests:core/queue-dispatch', function ($args) {

    echoTerminal("\e[33m[TEST:QueueDispatch] dispatch() devuelve el id de la tarea encolada\e[39m");
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

    $name = 'zz-prueba-queue-dispatch-' . bin2hex(random_bytes(4));
    $database = (new BaseModel())->getDatabase();
    $table = QueueJobMapper::TABLE;

    try {
        $id = QueueTask::dispatch($name, ['zz' => true], 1);
        $check(is_int($id) && $id > 0, 'a1 dispatch() devuelve un id entero', var_export($id, true));

        $statement = $database->prepare("SELECT id, name, status FROM `{$table}` WHERE name = ?");
        $statement->execute([$name]);
        $rows = $statement->fetchAll(\PDO::FETCH_OBJ);
        $check(count($rows) === 1 && (int) $rows[0]->id === $id, 'a2 es el id de la fila encolada, y hay una sola', (string) json_encode($rows));
        $check(count($rows) === 1 && $rows[0]->status === QueueJobMapper::STATUS_PENDING, 'a3 la tarea queda pendiente');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        $statement = $database->prepare("DELETE FROM `{$table}` WHERE name = ?");
        $statement->execute([$name]);
        $statement = $database->prepare("SELECT COUNT(*) FROM `{$table}` WHERE name = ?");
        $statement->execute([$name]);
        $check((int) $statement->fetchColumn() === 0, 'z1 la tarea de prueba se borró');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('QueueTask::dispatch() devuelve el id de la tarea encolada (database 5.2.0).')->setEffects([CliActions::EFFECT_DATABASE])->register();
