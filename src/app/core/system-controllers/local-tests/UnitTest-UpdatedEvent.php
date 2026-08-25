<?php

//`updated` solo salta si la base dice que cambió una fila. Ver T76.

use App\Model\UsersModel;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use PiecesPHP\Terminal\CliActions;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/updated-event', function ($args) {

    echoTerminal("\e[33m[TEST:updated] Guardar sin cambios no reabre un rechazo\e[39m");
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
    $balance = function () use (&$passed, &$failed): void {
        echoTerminal('');
        echoTerminal("   Total: " . ($passed + $failed) . " | \e[32mPasaron: {$passed}\e[39m | \e[31mFallaron: {$failed}\e[39m");
    };

    $database = (new BaseModel())->getDatabase();
    if ($database === null) {
        $check(false, 'hay conexión a base de datos');
        $balance();
        return ['success' => false, 'message' => 'sin conexión'];
    }

    //LEY 13: sin el accesor esto NO se omite, FALLA. Una puerta que no corre no es un verde.
    if (!method_exists((new UsersModel())->getModel(), 'getLastChangedRowsCount')) {
        $check(false, 'el paquete instalado trae getLastChangedRowsCount()', 'hace falta piecesphp/database >= 3.7.0: LA SUITE NO PUDO CORRER');
        $balance();
        return ['success' => false, 'message' => 'la suite no pudo correr'];
    }

    //El accesor llega en piecesphp/database 3.7.0. Aquí se pregunta una vez y se estrecha.
    $changedRows = static function (EntityMapperExtensible $mapper): int {
        $model = $mapper->getModel();
        return method_exists($model, 'getLastChangedRowsCount') ? (int) $model->getLastChangedRowsCount() : -1;
    };

    $table = SystemApprovalsMapper::TABLE;
    $reference = $database->query("SELECT referenceValue FROM `{$table}` WHERE referenceTable = 'pcsphp_users' LIMIT 1")->fetchColumn();
    if ($reference === false) {
        $check(false, 'hay un usuario con fila de aprobación que usar de sujeto');
        $balance();
        return ['success' => false, 'message' => 'sin sujeto'];
    }

    $id = (int) $reference;
    $where = "referenceTable = 'pcsphp_users' AND referenceValue = " . $database->quote((string) $reference);

    $statusOf = static fn (): string => (string) $database->query("SELECT status FROM `{$table}` WHERE {$where}")->fetchColumn();
    $aliasOf = static fn (): string => (string) $database->query("SELECT referenceAlias FROM `{$table}` WHERE {$where}")->fetchColumn();
    $secondNameOf = static fn (): string => (string) $database->query("SELECT secondname FROM `pcsphp_users` WHERE id = {$id}")->fetchColumn();

    //Se apartan los valores REALES y se devuelven al terminar: el sujeto es una fila viva.
    $originalStatus = $statusOf();
    $originalAlias = $aliasOf();
    $originalSecondName = $secondNameOf();

    $setStatus = static function (string $status) use ($database, $table, $where): void {
        $database->exec("UPDATE `{$table}` SET status = " . $database->quote($status) . " WHERE {$where}");
    };

    try {

        //──── RECHAZADO, guardado sin tocar nada ────────────────────────────────────────
        $setStatus(SystemApprovalsMapper::STATUS_REJECTED);
        $subject = new UsersModel($id);
        $subject->update();
        $check(
            $changedRows($subject) === 0,
            'un guardado que no toca nada cuenta CERO filas cambiadas',
            'contó ' . $changedRows($subject)
        );
        $check(
            $statusOf() === SystemApprovalsMapper::STATUS_REJECTED,
            'un RECHAZADO guardado sin cambios SIGUE RECHAZADO',
            'quedó en ' . $statusOf()
        );

        //──── RECHAZADO, guardado cambiando algo de verdad ──────────────────────────────
        //La intención del escuchador se CONSERVA, y hay que demostrarlo, no solo no romperla.
        $setStatus(SystemApprovalsMapper::STATUS_REJECTED);
        $subject = new UsersModel($id);
        $subject->secondname = 'ZZ-CENTINELA-' . $id;
        $subject->update();
        $check(
            $changedRows($subject) === 1,
            'un guardado que cambia de verdad cuenta UNA fila cambiada',
            'contó ' . $changedRows($subject)
        );
        $check(
            $statusOf() === SystemApprovalsMapper::STATUS_PENDING,
            'un RECHAZADO guardado CON cambios pasa a PENDIENTE',
            'quedó en ' . $statusOf()
        );

        //──── PENDIENTE, guardado sin cambios: no se reescribe ──────────────────────────
        //El centinela lo dice: si el escuchador corre, sobreescribe el alias con el suyo.
        $setStatus(SystemApprovalsMapper::STATUS_PENDING);
        $database->exec("UPDATE `{$table}` SET referenceAlias = 'ZZ-CENTINELA' WHERE {$where}");
        $subject = new UsersModel($id);
        $subject->update();
        $check(
            $aliasOf() === 'ZZ-CENTINELA',
            'un PENDIENTE guardado sin cambios NO se reescribe',
            'el alias quedó en «' . $aliasOf() . '»'
        );

        //──── EL LÍMITE, MEDIDO: un mapper que sella `updatedAt` SÍ cambia una fila ─────
        $stamper = \Organizations\Mappers\OrganizationMapper::class;
        $organizationId = (int) $database->query("SELECT referenceValue FROM `{$table}` WHERE referenceTable = 'organizations_elements' LIMIT 1")->fetchColumn();
        if ($organizationId !== 0) {
            $organization = new $stamper($organizationId);
            $organization->update();
            $check(
                $changedRows($organization) >= 1,
                'un mapper que sella updatedAt cambia una fila AUNQUE no se toque nada',
                'contó ' . $changedRows($organization)
                    . '. Si esto falla, alguien cambió el sellado y el límite de T76 ya no es el que dice'
            );
        }

    } finally {
        $database->exec("UPDATE `pcsphp_users` SET secondname = " . $database->quote($originalSecondName) . " WHERE id = {$id}");
        $database->exec("UPDATE `{$table}` SET status = " . $database->quote($originalStatus)
            . ", referenceAlias = " . $database->quote($originalAlias) . " WHERE {$where}");
    }

    $check(
        $statusOf() === $originalStatus && $aliasOf() === $originalAlias && $secondNameOf() === $originalSecondName,
        'el sujeto queda como estaba',
        'estado «' . $statusOf() . '», alias «' . $aliasOf() . '», secondname «' . $secondNameOf() . '»'
    );

    $balance();

    return ['success' => $failed === 0, 'message' => "{$passed}/" . ($passed + $failed)];

})->setDescription('El evento `updated` solo salta cuando la base dice que cambió una fila.')->setEffects([CliActions::EFFECT_DATABASE])->register();
