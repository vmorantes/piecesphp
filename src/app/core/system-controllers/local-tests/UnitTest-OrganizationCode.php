<?php

//P63: el código público de una organización. `ORG` + 7 dígitos al azar, único, inmutable, y reservado para la global.
//Todo dentro de una transacción de la conexión compartida que se revierte: la base queda como estaba.

use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use Terminal\Tasks\OrganizationsAssignCodesTask;

CliActions::make('unit-tests:organizations/code', function ($args) {

    echoTerminal("\e[33m[TEST:OrganizationCode] El código público: forma, unicidad, inmutabilidad y reparto\e[39m");
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

    $marca = 'zz-prueba-code-' . bin2hex(random_bytes(3));
    $pdo = OrganizationMapper::model()::getDb(Config::app_db('default')['db']);
    $creadas = [];

    $crear = function (string $sufijo) use ($marca, &$creadas): ?OrganizationMapper {
        $organizacion = OrganizationsController::createOrganization([
            'name' => "{$marca}-{$sufijo}",
            'nit' => "{$marca}-nit-{$sufijo}",
        ]);
        if ($organizacion !== null) {
            $creadas[] = "{$marca}-{$sufijo}";
        }
        return $organizacion;
    };
    $codigoEnBase = function (int $id): ?string {
        $modelo = OrganizationMapper::model();
        $modelo->resetAll();
        $modelo->select()->where(new WhereSegment([WhereItem::isEqual('id', $id)]))->execute();
        $filas = (array) $modelo->result();
        $fila = $filas[0] ?? null;
        return $fila !== null && isset($fila->code) ? (string) $fila->code : null;
    };

    //RETORNO-IGNORADO: la conexión va en ERRMODE_EXCEPTION; si no pudiera abrir la transacción, lanzaría.
    $pdo->beginTransaction();
    try {

        //─── a1 · La forma ──────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a1] Una organización nueva nace con su código');
        $primera = $crear('a1');
        $codigoPrimera = $primera !== null ? (string) $primera->code : '';
        $check($primera !== null && $primera->id !== null, 'a1 la organización se creó', $primera !== null ? 'id ' . var_export($primera->id, true) : 'null');
        $check(preg_match('/^ORG[0-9]{7}$/', $codigoPrimera) === 1, 'a1 su código es ORG + 7 dígitos', $codigoPrimera);
        $check(OrganizationMapper::codeIsWellFormed($codigoPrimera), 'a1 y el mapper lo da por bien formado');
        $check($primera !== null && $codigoEnBase((int) $primera->id) === $codigoPrimera, 'a1 el código está guardado en la base, no solo en memoria', (string) ($primera !== null ? $codigoEnBase((int) $primera->id) : ''));
        echoTerminal(' ');

        //─── a2 · Distintos ─────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a2] Dos altas seguidas dan códigos distintos');
        $segunda = $crear('a2');
        $codigoSegunda = $segunda !== null ? (string) $segunda->code : '';
        $check($codigoSegunda !== '' && $codigoSegunda !== $codigoPrimera, 'a2 el código de la segunda no es el de la primera', "{$codigoPrimera} y {$codigoSegunda}");
        //Cien sorteos seguidos: si el generador devolviera siempre lo mismo, aquí se vería.
        $sorteados = [];
        for ($i = 0; $i < 100; $i++) {
            $sorteados[] = OrganizationMapper::generateCode();
        }
        $check(count(array_unique($sorteados)) === 100, 'a2 cien códigos generados seguidos son cien códigos distintos', 'distintos: ' . count(array_unique($sorteados)));
        $malFormados = array_filter($sorteados, fn(string $c) => !OrganizationMapper::codeIsWellFormed($c));
        $check(count($malFormados) === 0, 'a2 y los cien tienen la forma', 'mal formados: ' . count($malFormados));
        echoTerminal(' ');

        //─── a3 · Inmutable ─────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a3] El código no se puede cambiar después');
        $idPrimera = $primera !== null ? (int) $primera->id : 0;
        $recargada = new OrganizationMapper($idPrimera);
        $recargada->code = 'ORG9999999';
        $recargada->update();
        $check($codigoEnBase($idPrimera) === $codigoPrimera, 'a3 tras intentar cambiarlo y guardar, en la base sigue el de siempre', (string) $codigoEnBase($idPrimera));
        $check((string) (new OrganizationMapper($idPrimera))->code === $codigoPrimera, 'a3 y al recargarla, también');
        //El resto del guardado SÍ pasa: se repone el código, no se rechaza la edición entera.
        $otra = new OrganizationMapper($idPrimera);
        $otra->code = 'ORG9999999';
        $otra->setLangData((string) get_config('default_lang'), 'address', "{$marca}-direccion-nueva");
        $otra->update();
        $recargadaDeNuevo = new OrganizationMapper($idPrimera);
        $check((string) $recargadaDeNuevo->address === "{$marca}-direccion-nueva" && (string) $recargadaDeNuevo->code === $codigoPrimera, 'a3 el resto de la edición sí se guarda: se repone el código, no se tira el guardado', (string) $recargadaDeNuevo->address);
        echoTerminal(' ');

        //─── a4 · La global ─────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a4] La organización global lleva el código reservado');
        $global = new OrganizationMapper(OrganizationMapper::INITIAL_ID_GLOBAL);
        $check($global->id !== null && (string) $global->code === OrganizationMapper::CODE_GLOBAL, 'a4 la global tiene ' . OrganizationMapper::CODE_GLOBAL, (string) $global->code);
        $check(OrganizationMapper::CODE_GLOBAL === 'ORG0000000', 'a4 y el reservado es el que dice el diseño');
        //El sorteo va de 1 a 9999999: el cero no sale. Se comprueba con los cien de a2, y con el mínimo.
        $check(!in_array(OrganizationMapper::CODE_GLOBAL, $sorteados, true), 'a4 ninguno de los cien sorteados fue el reservado');
        $check(OrganizationMapper::codeIsWellFormed(OrganizationMapper::CODE_GLOBAL), 'a4 el reservado tiene la misma forma que los demás');
        echoTerminal(' ');

        //─── a5 · La tarea ──────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a5] La tarea de asignación es idempotente');
        $antes = OrganizationsAssignCodesTask::assign();
        $check($antes['asignados'] === 0 && count($antes['fallos']) === 0, 'a5 con todas con código, la pasada no toca ninguna', 'asignados ' . $antes['asignados'] . ', fallos ' . count($antes['fallos']));
        $check($antes['conCodigo'] === $antes['total'] && $antes['total'] > 0, 'a5 y cuenta todas como ya tenidas', $antes['conCodigo'] . ' de ' . $antes['total']);
        //Se le quita el código a una por SQL, que es como llegan las filas viejas, y se reparte otra vez.
        $tabla = OrganizationMapper::TABLE;
        $sinCodigo = $pdo->prepare("UPDATE `{$tabla}` SET `code` = '' WHERE `id` = :id");
        $sinCodigo->execute([':id' => $idPrimera]);
        $despues = OrganizationsAssignCodesTask::assign();
        $codigoNuevo = $codigoEnBase($idPrimera);
        $check($despues['asignados'] === 1 && count($despues['fallos']) === 0, 'a5 a la que se quedó sin código se le pone uno', 'asignados ' . $despues['asignados'] . ', fallos ' . implode(' · ', $despues['fallos']));
        $check(is_string($codigoNuevo) && OrganizationMapper::codeIsWellFormed($codigoNuevo), 'a5 y el que le pone tiene la forma', (string) $codigoNuevo);
        $tercera = OrganizationsAssignCodesTask::assign();
        $check($tercera['asignados'] === 0, 'a5 la pasada siguiente vuelve a tocar 0', 'asignados ' . $tercera['asignados']);
        echoTerminal(' ');

        //─── a6 · El listado ────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a6] El listado encuentra por código');
        $codigoBuscado = (string) $codigoEnBase($idPrimera);
        $peticion = (new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/zz-prueba-code'), new Headers(), [], [], (new StreamFactory())->createStream('')))
            ->withQueryParams([
                'draw' => '1',
                'start' => '0',
                'length' => '10',
                'search' => ['value' => $codigoBuscado, 'regex' => 'false'],
                //Como las manda DataTables: sin la lista de columnas, el helper no busca por ninguna.
                'columns' => array_fill(0, 6, ['searchable' => 'true']),
                'order' => [],
            ]);
        $respuesta = (new OrganizationsController())->dataTables($peticion, new ResponseRoute());
        $cuerpo = json_decode((string) $respuesta->getBody(), true);
        $filas = is_array($cuerpo) && isset($cuerpo['data']) && is_array($cuerpo['data']) ? $cuerpo['data'] : [];
        $primeraFila = $filas[0] ?? [];
        $check(count($filas) === 1, 'a6 buscando el código, el listado devuelve una fila', 'filas: ' . count($filas));
        $check(is_array($primeraFila) && ($primeraFila[0] ?? '') === $codigoBuscado, 'a6 y la primera columna de esa fila es el código', is_array($primeraFila) ? (string) ($primeraFila[0] ?? '') : 'sin fila');
        $check(is_array($primeraFila) && str_contains((string) ($primeraFila[2] ?? ''), "{$marca}-a1"), 'a6 y es la organización que buscábamos', is_array($primeraFila) ? (string) ($primeraFila[2] ?? '') : '');

        echoTerminal(' ');

        //─── a7 · Las filas de antes de la migración ────────────────────────────────────────────────────
        echoTerminal('[a7] Una organización sin código todavía se carga sin reventar');
        //El camino que tumbó la aplicación en la migración: index.php hidrata con objectToMapper() y llegaba un
        //`code` nulo. Va sobre la fila REAL, con el código quitado en memoria: el esquema no se toca.
        $modeloFila = OrganizationMapper::model();
        $modeloFila->resetAll();
        $modeloFila->select()->where(new WhereSegment([WhereItem::isEqual('id', $idPrimera)]))->execute();
        $filasA7 = (array) $modeloFila->result();
        $filaA7 = $filasA7[0] ?? null;
        $hidratado = null;
        $errorHidratando = null;
        if ($filaA7 instanceof \stdClass) {
            $filaA7->code = null;
            try {
                $hidratado = OrganizationMapper::objectToMapper($filaA7);
            } catch (\Throwable $e) {
                $errorHidratando = $e;
            }
        }
        $check($filaA7 instanceof \stdClass, 'a7 se leyó la fila de la organización');
        $check($errorHidratando === null, 'a7 hidratarla con el código nulo no lanza nada', $errorHidratando !== null ? get_class($errorHidratando) . ': ' . mb_substr($errorHidratando->getMessage(), 0, 160) : '');
        $check($hidratado !== null && (int) $hidratado->id === $idPrimera && str_contains((string) $hidratado->name, "{$marca}-a1"), 'a7 y la organización se lee entera: su id y su nombre', $hidratado !== null ? (string) $hidratado->name : 'sin mapper');
        $check($hidratado !== null && $hidratado->code === null, 'a7 lo único que le falta es el código', $hidratado !== null ? var_export($hidratado->code, true) : '');
        //Y asignarle null a mano tampoco revienta: es lo que hace el hidratado de cada petición.
        $errorAsignando = null;
        try {
            $enBlanco = new OrganizationMapper();
            $enBlanco->code = null;
        } catch (\Throwable $e) {
            $errorAsignando = $e;
        }
        $check($errorAsignando === null, 'a7 asignar null al código no lanza «no acepta valores nulos»', $errorAsignando !== null ? mb_substr($errorAsignando->getMessage(), 0, 160) : '');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        echoTerminal(' ');
        echoTerminal('[z1] Los registros de la prueba');
        echoTerminal('   organizaciones creadas: ' . (count($creadas) > 0 ? implode(', ', $creadas) : 'ninguna'));
        if ($pdo->inTransaction()) {
            //RETORNO-IGNORADO: ERRMODE_EXCEPTION; y z1 comprueba de verdad que no quedó ningún registro.
            $pdo->rollBack();
        }
        $restos = OrganizationMapper::model();
        $restos->resetAll();
        $restos->select()->where(new WhereSegment([WhereItem::like('name', "{$marca}%")]))->execute();
        $cuantas = count((array) $restos->result());
        $global = new OrganizationMapper(OrganizationMapper::INITIAL_ID_GLOBAL);
        $check($cuantas === 0, 'z1 la transacción se revirtió: 0 organizaciones de la prueba', "restos {$cuantas}");
        $check((string) $global->code === OrganizationMapper::CODE_GLOBAL, 'z1 y la organización global conserva su código reservado', (string) $global->code);
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('P63: el código público de una organización, su unicidad, su inmutabilidad y el reparto a las existentes.')->setEffects([CliActions::EFFECT_DATABASE])->register();
