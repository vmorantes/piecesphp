<?php

//El asistente del panel (ADR 0075 §3) con un proveedor simulado: la puerta, el bucle de herramientas, el historial y la ruta.
//Anota consumo «zz-proveedor» y lo borra; crea un usuario zz-agente-asis-* con sus sesiones e intentos, y lo borra todo.
//@areas nucleo
//@areas src/app/classes/PiecesPHP/Mcp/
//@areas src/app/classes/PiecesPHP/Notifications/
//@areas src/app/classes/API/
//@requires-module PiecesPHP\Mcp\McpRoutes

use EventsLog\Mappers\LogsMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AI\AIGateway;
use PiecesPHP\AI\Mappers\AIUsageMapper;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Mcp\McpTool;
use PiecesPHP\Core\Mcp\McpToolRegistry;
use PiecesPHP\Core\ProjectStart;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Mcp\Assistant;
use PiecesPHP\Notifications\Notifications;
use PiecesPHP\Notifications\NotificationsRoutes;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/assistant', function ($args) {

    echoTerminal("\e[33m[TEST:Assistant] El asistente del panel, con un proveedor simulado\e[39m");
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

    $primero = function (int $tipo): int {
        $modelo = UsersModel::model();
        $modelo->resetAll();
        $modelo->select(['id'])->where(new WhereSegment([WhereItem::isEqual('type', $tipo), WhereItem::isEqual('status', UsersModel::STATUS_USER_ACTIVE)]))->execute(false, 1, 1);
        return (int) (((array) (((array) $modelo->result())[0] ?? []))['id'] ?? 0);
    };
    $idRoot = $primero(UsersModel::TYPE_USER_ROOT);
    $idGeneral = $primero(UsersModel::TYPE_USER_GENERAL);
    $database = (new BaseModel())->getDatabase();
    if (!$check($idRoot > 0 && $idGeneral > 0 && $database !== null, 'c0 hay un root, un usuario general y conexión a la base', "root {$idRoot}, general {$idGeneral}")) {
        return ['success' => false, 'message' => "{$passed}/" . ($passed + $failed)];
    }
    $root = new UserDataPackage($idRoot);
    $general = new UserDataPackage($idGeneral);

    //Lo que hace index.php en una petición con sesión: el usuario, su rol y su menú lateral (panel_find lo lee).
    $previos = [get_config('current_user'), get_config('pcsphp_current_user_stored'), get_config('menus'), get_config(AIGateway::CONFIG_NAME), get_config('translationAI'), get_config(\PiecesPHP\AI\Providers\AIProviderRegistry::KEYS_CONFIG_NAME)];
    //Un registro con clave y modelo para el asistente: desde JKM, sin proveedor que sirva la puerta deniega antes del adaptador
    //simulado (AIGateway::availability()). Nunca sale a la red: el adaptador lo pone la suite.
    $proveedorAsistente = ['providers' => [['id' => 'zz-prov-asis', 'type' => 'deepseek', 'alias' => 'Zz asistente']]];
    $seleccionAsistente = ['provider' => 'zz-prov-asis', 'model' => 'zz-modelo-asis'];
    set_config(\PiecesPHP\AI\Providers\AIProviderRegistry::KEYS_CONFIG_NAME, \PiecesPHP\AI\Providers\AIProviderRegistry::sealKeys(['zz-prov-asis' => 'zz-clave-asis']));
    $rolActual = new \ReflectionProperty(Roles::class, 'currentRole');
    $rolAntes = $rolActual->getValue();
    $acumulado = GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED);
    $como = function (int $id, int $tipo): void {
        set_config('current_user', (object) ['id' => $id]);
        set_config('pcsphp_current_user_stored', null);
        Roles::setCurrentRole($tipo);
        $silencio = Roles::getSilentMode();
        Roles::setSilentMode(true);
        include basepath('app/config/menu.php');
        Roles::setSilentMode($silencio);
    };
    $encender = fn() => set_config(AIGateway::CONFIG_NAME, ['enabled' => true, 'capabilities' => [Assistant::CAPABILITY => ['enabled' => true, 'userTypes' => [UsersModel::TYPE_USER_GENERAL]] + $seleccionAsistente]] + $proveedorAsistente);

    //El proveedor simulado: sigue un guion de turnos y guarda lo que recibe.
    $falso = new class {
        /** @var list<array<string,mixed>> */
        public array $turnos = [];
        /** @var list<array{messages:array<mixed>,tools:array<mixed>}> */
        public array $recibido = [];
        public ?\Throwable $lanzar = null;

        /**
         * @param array<int,array<string,mixed>> $messages
         * @param array<int,array<string,mixed>> $tools
         * @param float $temperature
         * @return array<string,mixed>
         */
        public function chatWithTools(array $messages, array $tools, float $temperature = 0.2, int $maxTokens = 800): array
        {
            $this->recibido[] = ['messages' => $messages, 'tools' => $tools];
            if ($this->lanzar !== null) {
                throw $this->lanzar;
            }
            return array_shift($this->turnos) ?? ['content' => 'zz-fin', 'toolCalls' => [], 'usage' => 3];
        }

        /**
         * @param list<array<string,mixed>> $turnos
         * @return void
         */
        public function guion(array $turnos): void
        {
            $this->turnos = $turnos;
            $this->recibido = [];
            $this->lanzar = null;
        }
    };
    $llamada = fn(string $id, string $nombre, string $argumentos): array => ['id' => $id, 'name' => $nombre, 'arguments' => $argumentos];
    $herramientaDe = function (int $vuelta, string $id) use ($falso): ?string {
        foreach ($falso->recibido[$vuelta]['messages'] ?? [] as $m) {
            if (($m['role'] ?? null) === 'tool' && ($m['tool_call_id'] ?? null) === $id) {
                return (string) $m['content'];
            }
        }
        return null;
    };
    $ejecutadas = [];
    $prueba = ['zz_prueba_solo_root', 'zz_prueba_bandera', 'zz_prueba_larga', 'zz_prueba_8000'];

    try {
        Assistant::setAdapterFactoryForTesting(fn(): object => $falso);
        set_config('translationAI', 'zz-proveedor');
        McpToolRegistry::register(new McpTool('zz_prueba_solo_root', 'Zz', 'Zz de prueba', ['type' => 'object', 'properties' => [], 'additionalProperties' => false], 'system-status-health', function () use (&$ejecutadas): array {
            $ejecutadas[] = 'zz_prueba_solo_root';
            return ['secreto' => 'zz-solo-root'];
        }));
        McpToolRegistry::register(new McpTool('zz_prueba_bandera', 'Zz', 'Zz de prueba', ['type' => 'object', 'properties' => [], 'additionalProperties' => false], 'admin', function () use (&$ejecutadas): array {
            $ejecutadas[] = 'zz_prueba_bandera';
            return ['ok' => true];
        }));
        McpToolRegistry::register(new McpTool('zz_prueba_larga', 'Zz', 'Zz de prueba', ['type' => 'object', 'properties' => [], 'additionalProperties' => false], 'admin', fn(): array => ['x' => str_repeat('a', 9000)]));
        McpToolRegistry::register(new McpTool('zz_prueba_8000', 'Zz', 'Zz de prueba', ['type' => 'object', 'properties' => [], 'additionalProperties' => false], 'admin', function () use (&$ejecutadas): array {
            $ejecutadas[] = 'zz_prueba_8000';
            return ['x' => str_repeat('a', Assistant::MAX_TOOL_RESULT_CHARS)];
        }));
        $como($idRoot, UsersModel::TYPE_USER_ROOT);

        //─── 1 · La puerta ──────────────────────────────────────────────────────────────────────────
        echoTerminal('[1] La puerta: apagada por omisión');
        set_config(AIGateway::CONFIG_NAME, false);
        $falso->guion([]);
        $apagado = Assistant::reply($root, 'zz hola');
        $motivo = AIGateway::denialReason(Assistant::CAPABILITY, $root);
        $check($apagado['ok'] === false && $motivo !== null && $apagado['error'] === $motivo && $falso->recibido === [], '1a DISCRIMINANTE: apagada (lo de fábrica), ok false con el motivo de la puerta, y el proveedor NO se llama', (string) $apagado['error']);
        $encender();
        $falso->guion([['content' => 'zz-respuesta', 'toolCalls' => [], 'usage' => 2]]);
        $encendido = Assistant::reply($root, 'zz hola');
        $check($encendido['ok'] === true && $encendido['answer'] === 'zz-respuesta' && count($falso->recibido) === 1 && $encendido['error'] === null, '1b encendida, sin motivo: llama al proveedor y devuelve su respuesta');
        $nombres = array_map(fn(array $t): string => (string) ($t['function']['name'] ?? ''), $falso->recibido[0]['tools'] ?? []);
        //notifications_mine es de Notificaciones: con el módulo apagado no se registra.
        if (!NotificationsRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m 1c no pide notifications_mine: esta instalación tiene Notificaciones apagado.");
        }
        $check(in_array('panel_find', $nombres, true) && (!NotificationsRoutes::ENABLE || in_array('notifications_mine', $nombres, true)) && in_array('zz_prueba_solo_root', $nombres, true) && ($falso->recibido[0]['tools'][0]['type'] ?? null) === 'function' && is_array($falso->recibido[0]['tools'][0]['function']['parameters'] ?? null), '1c al modelo le llegan las herramientas de root, en formato function con parameters', implode(', ', $nombres));
        echoTerminal(' ');

        //─── 2 · Una herramienta por PanelServer ────────────────────────────────────────────────────
        echoTerminal('[2] panel_find, ejecutada y devuelta como role tool');
        $falso->guion([['content' => '', 'toolCalls' => [$llamada('c1', 'panel_find', '{"query":"salud","limit":5}')], 'usage' => 4], ['content' => 'zz-aquí está', 'toolCalls' => [], 'usage' => 4]]);
        $conHerramienta = Assistant::reply($root, '¿Dónde veo la salud de la instalación?');
        $devuelta = $herramientaDe(1, 'c1');
        $urls = array_column($conHerramienta['links'], 'url');
        $check($conHerramienta['ok'] && $conHerramienta['toolsUsed'] === ['panel_find'] && $devuelta !== null && str_contains($devuelta, 'system-status/health'), '2a la llamada se ejecuta y su resultado vuelve al modelo como role tool con su tool_call_id', mb_substr((string) $devuelta, 0, 120));
        $check(count(array_filter($urls, fn(string $u): bool => str_contains($u, 'system-status/health'))) === 1 && $conHerramienta['links'][0]['title'] !== '', '2b DISCRIMINANTE: links trae la url de panel_find, con su título, sin repetir', implode(', ', $urls));
        $asistente = array_values(array_filter($falso->recibido[1]['messages'] ?? [], fn(array $m): bool => ($m['role'] ?? null) === 'assistant'));
        $check(($asistente[0]['tool_calls'][0]['function']['name'] ?? null) === 'panel_find' && ($asistente[0]['tool_calls'][0]['id'] ?? null) === 'c1', '2c antes del resultado, el mensaje del asistente con su tool_calls');
        echoTerminal(' ');

        //─── 3 · Una herramienta que el usuario no ve ───────────────────────────────────────────────
        echoTerminal('[3] Una herramienta que el usuario no ve');
        $como($idGeneral, UsersModel::TYPE_USER_GENERAL);
        $ejecutadas = [];
        $falso->guion([['content' => '', 'toolCalls' => [$llamada('c2', 'zz_prueba_solo_root', '{}')], 'usage' => 1]]);
        $ajena = Assistant::reply($general, 'zz dame lo de root');
        $devuelta = (string) $herramientaDe(1, 'c2');
        $nombresGeneral = array_map(fn(array $t): string => (string) ($t['function']['name'] ?? ''), $falso->recibido[0]['tools'] ?? []);
        $check($ajena['ok'] && str_contains($devuelta, 'Herramienta desconocida') && $ejecutadas === [] && !str_contains($devuelta, 'zz-solo-root') && !in_array('zz_prueba_solo_root', $nombresGeneral, true), '3a DISCRIMINANTE: al modelo le llega «Herramienta desconocida», la función NO se ejecuta, y ni siquiera se le ofrece', $devuelta);
        $como($idRoot, UsersModel::TYPE_USER_ROOT);
        echoTerminal(' ');

        //─── 4 · Argumentos que no son JSON ─────────────────────────────────────────────────────────
        echoTerminal('[4] Argumentos que no son JSON');
        $ejecutadas = [];
        $falso->guion([['content' => '', 'toolCalls' => [$llamada('c3', 'zz_prueba_bandera', '{no es json'), $llamada('c4', 'zz_prueba_bandera', '[1,2]')], 'usage' => 1]]);
        $malos = Assistant::reply($root, 'zz');
        $check($malos['ok'] && str_contains((string) $herramientaDe(1, 'c3'), 'no son JSON válido') && str_contains((string) $herramientaDe(1, 'c4'), 'no son JSON válido') && $ejecutadas === [], '4a DISCRIMINANTE: arguments que no son un objeto JSON (texto roto, una lista): error al modelo, sin ejecutar', (string) $herramientaDe(1, 'c3'));
        echoTerminal(' ');

        //─── 5 · El tope de vueltas ─────────────────────────────────────────────────────────────────
        echoTerminal('[5] El tope de vueltas');
        $cinco = [];
        for ($i = 1; $i <= 5; $i++) {
            $cinco[] = ['content' => '', 'toolCalls' => [$llamada("r{$i}", 'panel_find', '{"query":"a"}')], 'usage' => 1];
        }
        $falso->guion($cinco);
        $sinFin = Assistant::reply($root, 'zz');
        $check($sinFin['ok'] && $sinFin['answer'] === __(\PiecesPHP\Core\Mcp\McpProtocol::LANG_GROUP, 'No he podido terminar de buscarlo; prueba a preguntarlo de otra forma.') && count($falso->recibido) === Assistant::MAX_TOOL_ROUNDS + 1 && count($sinFin['toolsUsed']) === Assistant::MAX_TOOL_ROUNDS, '5a DISCRIMINANTE: cinco peticiones seguidas de herramientas: se para en MAX_TOOL_ROUNDS (4 ejecutadas, 5 llamadas) con «no he podido terminar»', count($falso->recibido) . ' llamadas · ' . $sinFin['answer']);
        echoTerminal(' ');

        //─── 6 · El historial y la pregunta ─────────────────────────────────────────────────────────
        echoTerminal('[6] El historial y la pregunta');
        $historial = [['role' => 'system', 'content' => 'zz-ajeno'], ['role' => 'tool', 'content' => 'zz-ajeno'], ['role' => 'user'], 'zz'];
        for ($i = 1; $i <= 12; $i++) {
            $contenido = $i === 12 ? str_repeat('b', 2500) : "zz-{$i}";
            //Los del asistente, firmados (el de 2500 se recorta a 2000 antes de comprobar: se firma lo recortado).
            $historial[] = ['role' => $i % 2 ? 'user' : 'assistant', 'content' => $contenido, 'extra' => 'zz', 'signature' => Assistant::sign(mb_substr($contenido, 0, Assistant::MAX_HISTORY_MESSAGE_CHARS))];
        }
        $falso->guion([]);
        Assistant::reply($root, 'zz-pregunta', $historial);
        $mensajes = $falso->recibido[0]['messages'] ?? [];
        $intermedios = array_slice($mensajes, 1, -1);
        $check(count($mensajes) === 12 && ($mensajes[0]['role'] ?? null) === 'system' && count($intermedios) === Assistant::MAX_HISTORY_MESSAGES && ($intermedios[0]['content'] ?? null) === 'zz-3' && mb_strlen((string) ($intermedios[9]['content'] ?? '')) === Assistant::MAX_HISTORY_MESSAGE_CHARS && !str_contains((string) json_encode($mensajes), 'zz-ajeno') && !array_key_exists('extra', $intermedios[0]) && end($mensajes) === ['role' => 'user', 'content' => 'zz-pregunta'], '6a DISCRIMINANTE: fuera los roles ajenos y las claves de más; los 10 últimos, cada uno a 2000; la pregunta, la última', count($mensajes) . ' mensajes');
        $falso->guion([]);
        $vacia = Assistant::reply($root, '   ');
        $larga = Assistant::reply($root, str_repeat('a', Assistant::MAX_QUESTION_CHARS + 1));
        $justa = Assistant::reply($root, str_repeat('a', Assistant::MAX_QUESTION_CHARS));
        $check($vacia['ok'] === false && $larga['ok'] === false && is_string($vacia['error']) && is_string($larga['error']) && count($falso->recibido) === 1 && $justa['ok'] === true, '6b pregunta vacía y de 1001: ok false sin llamar al proveedor; de 1000, sí', count($falso->recibido) . ' llamada(s)');
        echoTerminal(' ');

        //─── 7 · Las instrucciones ──────────────────────────────────────────────────────────────────
        echoTerminal('[7] El texto de sistema');
        $sistema = (string) ($falso->recibido[0]['messages'][0]['content'] ?? '');
        $sitio = (string) get_config('title_app');
        $check(($falso->recibido[0]['messages'][0]['role'] ?? null) === 'system' && $sitio !== '' && str_contains($sistema, "«{$sitio}»") && str_contains($sistema, 'Solo atiendes') && str_contains($sistema, 'declínala') && str_contains($sistema, mb_strtolower((string) __('lang', Config::get_lang()))), '7a DISCRIMINANTE: el primero, con el nombre del sitio, «Solo atiendes», «declínala» y el idioma', mb_substr($sistema, 0, 120));
        echoTerminal(' ');

        //─── 8 · El consumo ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[8] El consumo, en la puerta');
        $antes = AIGateway::usage(Assistant::CAPABILITY, 'zz-proveedor');
        $falso->guion([['content' => '', 'toolCalls' => [$llamada('u1', 'panel_find', '{"query":"a"}')], 'usage' => 7], ['content' => 'zz', 'toolCalls' => [], 'usage' => 5]]);
        Assistant::reply($root, 'zz');
        $despues = AIGateway::usage(Assistant::CAPABILITY, 'zz-proveedor');
        $check($despues - $antes === 12, "8a DISCRIMINANTE: recordUsage suma el usage de cada llamada al proveedor (7 + 5): {$antes} → {$despues}");
        echoTerminal(' ');

        //─── 9 · El proveedor falla ─────────────────────────────────────────────────────────────────
        echoTerminal('[9] Una excepción del adaptador');
        $secreto = 'zz-secreto-del-proveedor-' . bin2hex(random_bytes(3));
        $falso->guion([]);
        $falso->lanzar = new \RuntimeException($secreto);
        $fallo = Assistant::reply($root, 'zz');
        $check($fallo['ok'] === false && $fallo['error'] === __(\PiecesPHP\Core\Mcp\McpProtocol::LANG_GROUP, 'El asistente no pudo responder. El error quedó en el registro de errores de la instalación.') && !str_contains((string) json_encode($fallo), $secreto), '9a DISCRIMINANTE: ok false con el error genérico, sin el texto de la excepción', (string) $fallo['error']);
        echoTerminal(' ');

        //─── 10 · La reconexión ─────────────────────────────────────────────────────────────────────
        if (!NotificationsRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m 10a: esta instalación tiene Notificaciones apagado y no hay notifications_mine.");
        } else {
            echoTerminal('[10] La base cerrada (como hace chatHandler) y una herramienta que lee de ella');
            BaseModel::destroyDb(Config::app_db('default')['db'], Config::app_db('default')['host']);
            $falso->guion([['content' => '', 'toolCalls' => [$llamada('n1', 'notifications_mine', '{"filter":"unread","limit":2}')], 'usage' => 1], ['content' => 'zz', 'toolCalls' => [], 'usage' => 1]]);
            $tras = Assistant::reply($root, 'zz ¿tengo notificaciones?');
            $leidas = json_decode((string) $herramientaDe(1, 'n1'), true);
            $pagina = Notifications::page($idRoot, Notifications::FILTER_UNREAD, 1, 2);
            $esperadas = json_decode((string) json_encode(['unreadTotal' => (int) ($pagina['counts'][Notifications::FILTER_UNREAD] ?? 0), 'totals' => $pagina['counts'], 'page' => ['filter' => Notifications::FILTER_UNREAD, 'size' => count($pagina['items']), 'hasMore' => $pagina['hasMore'], 'items' => $pagina['items']]]), true);
            $check($tras['ok'] && is_array($leidas) && $leidas === $esperadas, '10a tras cerrar la conexión, notifications_mine reconecta y devuelve el total y la página de Notifications::page()', mb_substr((string) $herramientaDe(1, 'n1'), 0, 160));
        }
        echoTerminal(' ');

        //─── A · Los topes de gasto ─────────────────────────────────────────────────────────────────
        echoTerminal('[A] Los topes de gasto de una pregunta');
        $ejecutadas = [];
        $siete = [];
        for ($i = 1; $i <= 7; $i++) {
            $siete[] = $llamada("s{$i}", 'zz_prueba_bandera', '{}');
        }
        $falso->guion([['content' => '', 'toolCalls' => $siete, 'usage' => 1]]);
        $muchas = Assistant::reply($root, 'zz');
        $check(count($ejecutadas) === Assistant::MAX_TOOL_CALLS_PER_ROUND && str_contains((string) $herramientaDe(1, 's6'), 'Demasiadas herramientas') && str_contains((string) $herramientaDe(1, 's7'), 'Demasiadas herramientas') && !str_contains((string) $herramientaDe(1, 's5'), 'Demasiadas') && count($muchas['toolsUsed']) === 7, 'A1 DISCRIMINANTE: 7 tool_calls en una vuelta: se ejecutan 5; las 2 últimas vuelven como error con su tool_call_id; toolsUsed lleva las 7', count($ejecutadas) . ' ejecutadas');
        $marca = ' […recortado]';
        $falso->guion([['content' => '', 'toolCalls' => [$llamada('l1', 'zz_prueba_larga', '{}')], 'usage' => 1]]);
        Assistant::reply($root, 'zz');
        $larga = (string) $herramientaDe(1, 'l1');
        $check(mb_strlen($larga) === Assistant::MAX_TOOL_RESULT_CHARS + mb_strlen($marca) && str_ends_with($larga, $marca), 'A2a DISCRIMINANTE: un resultado de 9000 caracteres llega con 8000 y la marca « […recortado]»', (string) mb_strlen($larga));
        $ejecutadas = [];
        $cinco = [];
        for ($i = 1; $i <= 5; $i++) {
            $cinco[] = $llamada("o{$i}", 'zz_prueba_8000', '{}');
        }
        $falso->guion([['content' => '', 'toolCalls' => $cinco, 'usage' => 1]]);
        $acumulados = Assistant::reply($root, 'zz');
        $noTermina = __(\PiecesPHP\Core\Mcp\McpProtocol::LANG_GROUP, 'No he podido terminar de buscarlo; prueba a preguntarlo de otra forma.');
        $check($acumulados['ok'] && $acumulados['answer'] === $noTermina && count($ejecutadas) === 4 && count($falso->recibido) === 1, 'A2b DISCRIMINANTE: cinco resultados de 8000 seguidos: para por el acumulado (32000) tras el 4.º, con «no he podido terminar»', count($ejecutadas) . ' ejecutadas · ' . count($falso->recibido) . ' llamada(s)');
        //A3: max_tokens en la petición de los dos adaptadores, sin red.
        /** @var \ArrayObject<string, mixed> $capturaOpenAI */
        $capturaOpenAI = new \ArrayObject();
        $clienteFalso = new class ($capturaOpenAI) implements \Psr\Http\Client\ClientInterface {
            public function __construct(private \ArrayObject $captura)
            {
            }

            public function sendRequest(\Psr\Http\Message\RequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                $this->captura['cuerpo'] = json_decode((string) $request->getBody(), true);
                return new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], (string) json_encode(['id' => 'zz', 'object' => 'chat.completion', 'created' => 1, 'model' => 'zz', 'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => 'zz-openai'], 'finish_reason' => 'stop']], 'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 1, 'total_tokens' => 2]]));
            }
        };
        $openAI = new class ('zz-clave-de-prueba', '-', 'zz-modelo', $clienteFalso) extends \API\Adapters\OpenAIHandlerAdapter {
            public function __construct(string $apiKey, string $assistantID, string $model, private \Psr\Http\Client\ClientInterface $falso)
            {
                parent::__construct($apiKey, $assistantID, $model);
            }

            protected function chatHttpClient(): \Psr\Http\Client\ClientInterface
            {
                return $this->falso;
            }
        };
        $respuestaOpenAI = $openAI->chatWithTools([['role' => 'user', 'content' => 'zz']], [], 0.2, Assistant::MAX_ANSWER_TOKENS);
        $peticionOpenAI = is_array($capturaOpenAI['cuerpo'] ?? null) ? $capturaOpenAI['cuerpo'] : [];
        /** @var \ArrayObject<string, mixed> $capturaMistral */
        $capturaMistral = new \ArrayObject();
        $mistral = new class ('zz-clave-de-prueba', 'zz-modelo', $capturaMistral) extends \API\Adapters\MistralHandlerAdapter {
            public function __construct(string $apiKey, string $model, private \ArrayObject $captura)
            {
                parent::__construct($apiKey, $model);
            }

            protected function chatHttpClient(): \PiecesPHP\Core\Http\HttpClient
            {
                return new class ('https://zz.invalid/', $this->captura) extends \PiecesPHP\Core\Http\HttpClient {
                    public function __construct(string $baseURL, private \ArrayObject $captura)
                    {
                        parent::__construct($baseURL);
                    }

                    public function request(string $request_uri, string $method, array $contents = [], array $headers = [], bool $shared_cookies = true, bool $override_defaults = true): string|false
                    {
                        $this->captura['cuerpo'] = $contents;
                        return '{}';
                    }

                    public function getResponseStatus()
                    {
                        return '200';
                    }

                    public function getResponseParsedBody(string $mode)
                    {
                        return ['choices' => [['message' => ['content' => 'zz-mistral']]], 'usage' => ['total_tokens' => 2]];
                    }
                };
            }
        };
        $respuestaMistral = $mistral->chatWithTools([['role' => 'user', 'content' => 'zz']], [], 0.2, Assistant::MAX_ANSWER_TOKENS);
        $peticionMistral = is_array($capturaMistral['cuerpo'] ?? null) ? $capturaMistral['cuerpo'] : [];
        $check(($peticionOpenAI['max_tokens'] ?? null) === 800 && $respuestaOpenAI['content'] === 'zz-openai' && ($peticionMistral['max_tokens'] ?? null) === 800 && $respuestaMistral['content'] === 'zz-mistral', 'A3 la petición de los dos adaptadores lleva max_tokens 800 (OpenAI: cliente PSR-18 simulado; Mistral: HttpClient simulado)', 'OpenAI ' . json_encode($peticionOpenAI['max_tokens'] ?? null) . ' · Mistral ' . json_encode($peticionMistral['max_tokens'] ?? null));
        //A4: la cuota se alcanza a mitad.
        $gastado = AIGateway::usage(Assistant::CAPABILITY);
        set_config(AIGateway::CONFIG_NAME, ['enabled' => true, 'capabilities' => [Assistant::CAPABILITY => ['enabled' => true, 'userTypes' => [UsersModel::TYPE_USER_GENERAL], 'quota' => $gastado + 500] + $seleccionAsistente]] + $proveedorAsistente);
        $falso->guion([['content' => '', 'toolCalls' => [$llamada('q1', 'zz_prueba_bandera', '{}')], 'usage' => 1000], ['content' => 'zz-no-debería', 'toolCalls' => [], 'usage' => 1]]);
        $cuota = Assistant::reply($root, 'zz');
        $motivoCuota = AIGateway::denialReason(Assistant::CAPABILITY, $root);
        $check($cuota['ok'] === false && $motivoCuota !== null && $cuota['error'] === $motivoCuota && count($falso->recibido) === 1, 'A4 DISCRIMINANTE: la cuota se cruza en la 1.ª llamada: la 2.ª no se hace, ok false con el motivo de la cuota', (string) $cuota['error']);
        $encender();
        //A5: la cuota por omisión.
        set_config(AIGateway::CONFIG_NAME, false);
        $porOmision = AIGateway::settingsFor(Assistant::CAPABILITY);
        $check($porOmision['quota'] === 200000 && $porOmision['period'] === 'monthly' && $porOmision['enabled'] === false, 'A5 sin nada guardado: apagada, 200000 tokens y mensual', (string) json_encode($porOmision));
        $encender();
        echoTerminal(' ');

        //─── B · La firma del historial ─────────────────────────────────────────────────────────────
        echoTerminal('[B] Solo los turnos del asistente que firmó el servidor');
        $falso->guion([['content' => 'zz-dijo', 'toolCalls' => [], 'usage' => 1]]);
        $anterior = Assistant::reply($root, 'zz');
        $check(is_string($anterior['signature']) && hash_equals(hash_hmac('sha256', 'zz-dijo', (string) Config::app_key_derived('assistant-history')), $anterior['signature']) && Assistant::reply($root, '')['signature'] === null, 'B0 reply() devuelve signature = hash_hmac sobre answer con la clave derivada; con ok false, null');
        $falso->guion([]);
        Assistant::reply($root, 'zz', [
            ['role' => 'user', 'content' => 'zz-pregunta-1'],
            ['role' => 'assistant', 'content' => 'zz-dijo', 'signature' => $anterior['signature']],
            ['role' => 'assistant', 'content' => 'zz-sin-firma'],
            ['role' => 'assistant', 'content' => 'zz-otro-texto', 'signature' => $anterior['signature']],
            ['role' => 'assistant', 'content' => 'zz-otra-clave', 'signature' => hash_hmac('sha256', 'zz-otra-clave', 'zz-clave-ajena')],
        ]);
        $contenidos = array_column(array_slice($falso->recibido[0]['messages'] ?? [], 1, -1), 'content');
        $check($contenidos === ['zz-pregunta-1', 'zz-dijo'], 'B1 DISCRIMINANTE: se conserva el turno bien firmado; sin firma, con la firma de otro texto o de otra clave, se descartan', implode(', ', $contenidos));
        echoTerminal(' ');

        //─── C · Los tiempos ────────────────────────────────────────────────────────────────────────
        echoTerminal('[C] Tiempo máximo hacia el proveedor y plazo total');
        $exponerOpenAI = new class ('zz', '-', 'zz') extends \API\Adapters\OpenAIHandlerAdapter {
            public function zzCliente(): \Psr\Http\Client\ClientInterface
            {
                return $this->chatHttpClient();
            }
        };
        $exponerMistral = new class ('zz', 'zz') extends \API\Adapters\MistralHandlerAdapter {
            public function zzCliente(): \PiecesPHP\Core\Http\HttpClient
            {
                return $this->chatHttpClient();
            }
        };
        $guzzle = $exponerOpenAI->zzCliente();
        //Por reflexión: getConfig() está marcado obsoleto en Guzzle.
        $configuracion = $guzzle instanceof \GuzzleHttp\Client ? (new \ReflectionProperty(\GuzzleHttp\Client::class, 'config'))->getValue($guzzle) : [];
        $check(is_array($configuracion) && ($configuracion['timeout'] ?? null) === 30 && ($configuracion['connect_timeout'] ?? null) === 10 && $exponerMistral->zzCliente()->timeout(null) === 30, 'C1a el cliente de chatWithTools: OpenAI con timeout 30 y connect_timeout 10; Mistral con timeout 30');
        $reloj = 0.0;
        Assistant::setClockForTesting(function () use (&$reloj): float {
            $reloj += 50.0;
            return $reloj;
        });
        $falso->guion([['content' => '', 'toolCalls' => [$llamada('t1', 'zz_prueba_bandera', '{}')], 'usage' => 1], ['content' => 'zz-no-debería', 'toolCalls' => [], 'usage' => 1]]);
        $plazo = Assistant::reply($root, 'zz');
        Assistant::setClockForTesting(null);
        $check($plazo['ok'] && $plazo['answer'] === $noTermina && count($falso->recibido) === 1, 'C1b DISCRIMINANTE: pasados 90 s (reloj simulado, 50 s por lectura) el bucle para antes de la 2.ª llamada, con «no he podido terminar»', count($falso->recibido) . ' llamada(s) · ' . $plazo['answer']);
        echoTerminal(' ');
    } catch (\Throwable $e) {
        $check(false, 'la parte en proceso corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        Assistant::setAdapterFactoryForTesting(null);
        Assistant::setClockForTesting(null);
        foreach ($prueba as $key) {
            McpToolRegistry::remove($key);
        }
        set_config('current_user', $previos[0]);
        set_config('pcsphp_current_user_stored', $previos[1]);
        set_config('menus', $previos[2]);
        set_config(AIGateway::CONFIG_NAME, $previos[3]);
        set_config('translationAI', $previos[4]);
        set_config(\PiecesPHP\AI\Providers\AIProviderRegistry::KEYS_CONFIG_NAME, $previos[5]);
        $rolActual->setValue(null, $rolAntes);
        $database = (new BaseModel())->getDatabase();
        $database?->prepare('DELETE FROM `' . AIUsageMapper::TABLE . '` WHERE capability = ? AND provider = ?')->execute([Assistant::CAPABILITY, 'zz-proveedor']);
        GenericContentPseudoMapper::setContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED, $acumulado);
    }
    $quedan = $database?->prepare('SELECT COUNT(*) FROM `' . AIUsageMapper::TABLE . '` WHERE capability = ? AND provider = ?');
    $quedan?->execute([Assistant::CAPABILITY, 'zz-proveedor']);
    $check($quedan !== null && $quedan !== false && (int) $quedan->fetchColumn() === 0 && array_intersect($prueba, array_keys(McpToolRegistry::all())) === [] && GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED) == $acumulado, 'z0 no queda consumo zz-proveedor, ni herramientas de prueba, y el acumulado está como estaba');

    //─── 11 · Por HTTP ──────────────────────────────────────────────────────────────────────────────
    echoTerminal('[11] La ruta del asistente, por HTTP');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    if ($check($base !== '' && $database !== null, '11 hay una base HTTP para pedir y conexión a la base', $base)) {
        $ruta = function (string $nombre): string {
            $url = get_route($nombre, [], true);
            return is_string($url) && $url !== '' ? '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/') : '';
        };
        $http = function (string $path, array $cabeceras, ?string $cuerpo = null, ?array $formulario = null) use ($base): array {
            $handle = \Terminal\TestRun::curlInit();
            \Terminal\TestRun::curlSetoptArray($handle, [
                CURLOPT_URL => $base . '/' . ltrim($path, '/'),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_HTTPHEADER => $cabeceras,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $formulario !== null ? http_build_query($formulario) : (string) $cuerpo,
            ]);
            $respuesta = curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            $tamCabecera = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
            //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
            $respuesta = is_string($respuesta) ? $respuesta : '';
            $cuerpoRespuesta = substr($respuesta, $tamCabecera);
            return ['status' => $status, 'body' => $cuerpoRespuesta, 'json' => json_decode($cuerpoRespuesta, true)];
        };
        $preguntar = $ruta('mcp-assistant-ask');
        $prefijo = 'zz-agente-asis-' . bin2hex(random_bytes(3));
        $clave = bin2hex(random_bytes(12)) . 'Aa1!';
        $usuarios = [];
        $tablaUsuarios = UsersModel::TABLE;
        $tablaIntentos = LoginAttemptsModel::TABLE;
        $maxIntento = (int) $database->query("SELECT COALESCE(MAX(id), 0) FROM `{$tablaIntentos}`")->fetchColumn();
        $maxLog = (int) $database->query('SELECT COALESCE(MAX(id), 0) FROM `' . LogsMapper::TABLE . '`')->fetchColumn();
        try {
            $u = new UsersModel();
            $u->username = $prefijo;
            $u->email = "{$prefijo}@example.com";
            $u->password = password_hash($clave, \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Agente';
            $u->secondLastname = '';
            $u->type = UsersModel::TYPE_USER_GENERAL;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[] = (int) $u->id;
            $acceso = $http($ruta('users-login-request'), ['X-Requested-With: XMLHttpRequest'], null, ['username' => $prefijo, 'password' => $clave, 'twoFactor' => '']);
            $token = is_string($acceso['json']['token'] ?? null) ? $acceso['json']['token'] : '';
            $check($preguntar !== '' && $token !== '', '11a CANARIO: la ruta existe y el usuario de la prueba entra', "{$preguntar} · HTTP {$acceso['status']}");
            $json = 'Content-Type: application/json';
            $sesion = SessionToken::headerName() . ": {$token}";
            //El motivo que da la puerta con la configuración real (la de la petición), para ese usuario.
            $motivoReal = AIGateway::denialReason(Assistant::CAPABILITY, new UserDataPackage((int) $u->id));
            $apagada = $http($preguntar, [$json, $sesion], '{"question":"zz hola"}');
            $check($motivoReal !== null && $apagada['status'] === 200 && ($apagada['json']['ok'] ?? null) === false && ($apagada['json']['error'] ?? null) === $motivoReal, '11b con la capacidad apagada (de fábrica): 200 {ok:false, error: el motivo de la puerta}', "HTTP {$apagada['status']} · " . mb_substr($apagada['body'], 0, 160));
            $sinPregunta = $http($preguntar, [$json, $sesion], '{"history":[]}');
            $check($sinPregunta['status'] === 400 && ($sinPregunta['json']['ok'] ?? null) === false && is_string($sinPregunta['json']['error'] ?? null), '11c sin question: 400 {ok:false, error}', "HTTP {$sinPregunta['status']}");
            $texto = $http($preguntar, ['Content-Type: text/plain', $sesion], '{"question":"zz"}');
            $check($texto['status'] === 415 && $texto['body'] === '', '11d text/plain: 415', "HTTP {$texto['status']}");
            $nulo = $http($preguntar, [$json, $sesion, 'Origin: null', "Referer: {$base}/" . ProjectStart::adminPath() . '/'], '{"question":"zz"}');
            $check($nulo['status'] === 403 && $nulo['body'] === '', '11e «Origin: null»: 403', "HTTP {$nulo['status']}");
        } catch (\Throwable $e) {
            $check(false, 'la parte HTTP corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
        } finally {
            try {
                if ($usuarios !== []) {
                    $marcas = implode(',', array_fill(0, count($usuarios), '?'));
                    $database->prepare('DELETE FROM `' . UserSessions::TABLE . "` WHERE userID IN ({$marcas})")->execute($usuarios);
                    $database->prepare("DELETE FROM `{$tablaIntentos}` WHERE id > ? AND (userID IN ({$marcas}) OR usernameAttempt = ?)")->execute(array_merge([$maxIntento], $usuarios, [$prefijo]));
                    $database->prepare('DELETE FROM `' . LogsMapper::TABLE . "` WHERE id > ? AND createdBy IN ({$marcas})")->execute(array_merge([$maxLog], $usuarios));
                    //Crear el usuario abre su aprobación: sin esto quedaría una fila huérfana por corrida.
                    $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . "` WHERE referenceTable = ? AND referenceValue IN ({$marcas})")->execute(array_merge([$tablaUsuarios], array_map('strval', $usuarios)));
                    $database->prepare("DELETE FROM `{$tablaUsuarios}` WHERE id IN ({$marcas})")->execute($usuarios);
                }
            } catch (\Throwable $e) {
                $check(false, 'z1 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
            }
            $consulta = $database->prepare("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username = ?");
            $consulta->execute([$prefijo]);
            $check((int) $consulta->fetchColumn() === 0, 'z2 no queda el usuario de la prueba');
        }
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El asistente del panel (ADR 0075 §3) con un proveedor simulado: la puerta (apagada por omisión, sin llamar al proveedor), el bucle de herramientas por PanelServer (permiso, argumentos que no son JSON, tope de vueltas, enlaces), el historial y la pregunta, el texto de sistema, el consumo, una excepción del proveedor, la reconexión a la base y la ruta por HTTP (motivo, 400, 415, Origin null). Anota consumo zz-proveedor y lo borra; crea y borra un usuario.')->setEffects([CliActions::EFFECT_DATABASE])->register();
