<?php

//P46 tramo 0: la sesión automática de root en terminal, de la que dependen bin/verify y todas las tareas del sistema
//de rutas. Lanza una TAREA en su propio proceso —es el único camino donde esa sesión existe— y no escribe nada.

use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\TerminalData;

CliActions::make('unit-tests:core/session-terminal-root', function ($args) {

    echoTerminal("\e[33m[TEST:SessionTerminalRoot] La sesión de root en terminal, y los DOS mundos del CLI\e[39m");
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

    try {

        //─── a · Lo que la sesión automática necesita para existir ───────────────────────────────────────
        echoTerminal('[a] Las dos condiciones de index.php para fabricar la sesión de terminal');
        $root = new UsersModel(1);
        $check($root->id !== null && (int) $root->id === 1, 'a1 existe el usuario id 1, que es el que index.php busca');
        $check((int) $root->type === UsersModel::TYPE_USER_ROOT, 'a2 y es de tipo root', 'tipo ' . var_export($root->type, true));
        $check(TerminalData::getInstance()->isTerminal() === true, 'a3 la terminal se reconoce como terminal');
        //Sin REMOTE_ADDR, el `aud` del token reventaría: BaseToken::aud() la lee sin `??`.
        $variables = TerminalData::instance()->basicServerVariables();
        $check(($variables['REMOTE_ADDR'] ?? null) === '127.0.0.1', 'a4 la terminal declara REMOTE_ADDR, que es lo que hace posible el aud', (string) ($variables['REMOTE_ADDR'] ?? 'AUSENTE'));
        echoTerminal(' ');

        //─── b · LOS DOS MUNDOS ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[b] Una suite NO tiene sesión; una tarea del sistema de rutas SÍ');
        //Esta suite es del mundo CliActions, que index.php despacha SIN pasar por Slim: el middleware que
        //fabrica la sesión de terminal no corre, así que aquí no hay usuario. No es un defecto: es el contrato.
        $check(getLoggedFrameworkUser() === null, 'b1 en una suite no hay usuario conectado', getLoggedFrameworkUser() === null ? '' : 'había uno');
        $check(SessionToken::getJWTReceived() === '', 'b2 ni token que recibir');

        //Y ahora el otro mundo, de verdad: una tarea root en su propio proceso.
        $raiz = rtrim(str_replace('\\', '/', basepath('..')), '/');
        $orden = escapeshellcmd($raiz . '/bin/cli') . ' ' . escapeshellarg('system-alerts') . ' 2>&1';
        $binario = (string) getenv('PCSPHP_PHP_BIN');
        if ($binario !== '') {
            $orden = 'PCSPHP_PHP_BIN=' . escapeshellarg($binario) . ' ' . $orden;
        }
        $salida = [];
        $codigo = 0;
        //RETORNO-IGNORADO: exec() devuelve la última línea; aquí deciden $salida y $codigo.
        exec($orden, $salida, $codigo);
        $texto = implode("\n", $salida);
        $check($codigo === 0, 'b3 la tarea root corre y sale con 0', "código {$codigo}");
        $check(!str_contains($texto, 'necesita autenticación'), 'b4 y NO responde «necesita autenticación»: la sesión de root se fabricó sola', mb_substr($texto, 0, 160));
        $check(str_contains($texto, 'aviso(s) activo(s)'), 'b5 y llegó a hacer su trabajo, que es lo que prueba que pasó el control de acceso', mb_substr($texto, -80));
        echoTerminal(' ');

        //─── c · Qué se rompe si esa sesión desaparece ───────────────────────────────────────────────────
        echoTerminal('[c] De quién depende esa sesión');
        //La tarea elegida exige root: si la sesión no se fabricara, el control de acceso la pararía. Se comprueba
        //que sigue exigiéndolo, para que b3-b5 no se vuelvan una prueba vacía el día que alguien la abra.
        $ruta = \Terminal\Tasks\SystemAlertsTask::route();
        $check($ruta->requireLogin() === true, 'c1 la tarea de b3 sigue exigiendo sesión', var_export($ruta->requireLogin(), true));
        $check($ruta->rolesAllowed() === [UsersModel::TYPE_USER_ROOT], 'c2 y sigue siendo solo para root', (string) json_encode($ruta->rolesAllowed()));

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('P46: la sesión automática de root en terminal existe en las tareas y NO en las suites; de ella depende bin/verify.')->setEffects([CliActions::EFFECT_DATABASE])->register();
