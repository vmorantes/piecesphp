<?php

//La ventana del asistente (ADR 0075, ronda 3b): lanzador, contenedor, JS, posición y tipo de fallo. Sin red; el centro en
//memoria. Anota consumo «zz-ventana» y lo borra.
//@areas nucleo
//@areas src/app/classes/PiecesPHP/Mcp/

use PiecesPHP\AI\AIGateway;
use PiecesPHP\AI\Mappers\AIUsageMapper;
use PiecesPHP\AI\Providers\AIProviderRegistry;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Mcp\McpProtocol;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Mcp\Assistant;
use PiecesPHP\Mcp\Controllers\McpController;
use PiecesPHP\Mcp\McpRoutes;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;

CliActions::make('unit-tests:core/assistant-window', function ($args) {

    echoTerminal("\e[33m[TEST:AssistantWindow] La ventana del asistente: lanzador, contenedor, posición y tipo de fallo\e[39m");
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

    $database = (new BaseModel())->getDatabase();
    $modelo = UsersModel::model();
    $modelo->resetAll();
    $modelo->select(['id'])->where(['type' => UsersModel::TYPE_USER_ROOT, 'status' => UsersModel::STATUS_USER_ACTIVE])->execute(false, 1, 1);
    $idRoot = (int) (((array) (((array) $modelo->result())[0] ?? []))['id'] ?? 0);
    if (!$check($database !== null && $idRoot > 0 && McpRoutes::ENABLE, 'c0 CANARIO: conexión, un root y el módulo MCP encendido')) {
        return ['success' => false, 'message' => "{$passed}/" . ($passed + $failed)];
    }

    $opciones = [AIGateway::CONFIG_NAME, AIProviderRegistry::KEYS_CONFIG_NAME, 'current_user', 'pcsphp_current_user_stored', 'global_assets', 'menus', 'alternatives_url', 'translationAI'];
    $previos = array_combine($opciones, array_map(fn(string $opcion) => get_config($opcion), $opciones));
    $rolActual = new \ReflectionProperty(Roles::class, 'currentRole');
    $rolAntes = $rolActual->getValue();
    $acumulado = GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED);
    $borrarConsumo = function () use ($database): void {
        //RETORNO-IGNORADO: limpieza de la prueba; lo comprueba z1 contando lo que queda.
        $database->prepare('DELETE FROM `' . AIUsageMapper::TABLE . '` WHERE provider = ?')->execute(['zz-ventana']);
    };
    $filasAsistente = fn(): int => (int) $database->query('SELECT COUNT(*) FROM `' . AIUsageMapper::TABLE . "` WHERE capability IN ('" . Assistant::CAPABILITY . "', '" . AIGateway::PROVIDER_USAGE_ROW . "')")->fetchColumn();
    $filasAntes = $filasAsistente();
    $centro = fn(array $asistente = [], bool $encendida = true): array => [
        'enabled' => $encendida,
        AIProviderRegistry::CONVERTED_FLAG => true,
        'providers' => [['id' => 'zz-prov-ventana', 'type' => 'deepseek', 'alias' => 'Zz ventana']],
        'capabilities' => [Assistant::CAPABILITY => array_merge(['enabled' => true, 'userTypes' => [], 'quota' => 0, 'period' => 'accumulated', 'provider' => 'zz-prov-ventana', 'model' => 'zz-modelo-ventana'], $asistente)],
    ];
    //La barra y lo flotante, como los pinta header.php, con root conectado; y si la página cargaría el JS.
    $pintar = function () use ($previos): array {
        set_config('global_assets', $previos['global_assets']);
        $controlador = new BaseController();
        $barra = (string) $controlador->render('panel/layout/topbar-bar', [], false);
        $flotante = (string) $controlador->render('panel/layout/topbar', [], false);
        $js = get_config('global_assets');
        $conJS = is_array($js) && count(array_filter((array) ($js['js'] ?? []), fn($asset) => is_string($asset) && str_ends_with($asset, 'js/panel-assistant.js'))) > 0;
        return ['barra' => $barra, 'flotante' => $flotante, 'js' => $conJS];
    };
    $contenedores = function (string $html): array {
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>');
        $hallados = [];
        foreach ((new \DOMXPath($dom))->query('//*[@id="panel-assistant"]') ?: [] as $nodo) {
            if ($nodo instanceof \DOMElement) {
                $hallados[] = $nodo;
            }
        }
        return $hallados;
    };
    $lanzadores = fn(string $html): int => (int) preg_match_all('/data-chat="#panel-assistant"/', $html);

    try {
        $borrarConsumo();
        set_config('current_user', (object) ['id' => $idRoot]);
        set_config('pcsphp_current_user_stored', null);
        Roles::setCurrentRole(UsersModel::TYPE_USER_ROOT);
        $silencio = Roles::getSilentMode();
        Roles::setSilentMode(true);
        include basepath('app/config/menu.php');
        Roles::setSilentMode($silencio);
        //Lo que el arranque de una petición pone y la terminal no: las direcciones de cada idioma (el selector de topbar.php).
        set_config('alternatives_url', array_fill_keys(\PiecesPHP\Core\Config::get_allowed_langs(), baseurl()));
        set_config(AIProviderRegistry::KEYS_CONFIG_NAME, AIProviderRegistry::sealKeys(['zz-prov-ventana' => 'zz-clave-ventana']));
        $root = new UserDataPackage($idRoot);

        //─── 1 · Lanzador, contenedor y JS según availability('assistant') ─────────────────────────
        echoTerminal('[1] Lanzador, contenedor y JS según la disponibilidad');
        set_config(AIGateway::CONFIG_NAME, $centro([], false));
        $oculta = $pintar();
        $check(AIGateway::availability(Assistant::CAPABILITY, $root)['state'] === AIGateway::AVAILABILITY_HIDDEN && Assistant::panelWindow($root) === null, '1a con la IA apagada, availability da hidden y panelWindow() null');
        $check($lanzadores($oculta['barra']) === 0 && $contenedores($oculta['flotante']) === [] && !$oculta['js'], '1b hidden: ni lanzador, ni contenedor, ni JS', (string) json_encode(['lanzadores' => $lanzadores($oculta['barra']), 'contenedores' => count($contenedores($oculta['flotante'])), 'js' => $oculta['js']]));

        set_config(AIGateway::CONFIG_NAME, $centro());
        $disponible = $pintar();
        $nodos = $contenedores($disponible['flotante']);
        $nodo = $nodos[0] ?? null;
        $check(AIGateway::availability(Assistant::CAPABILITY, $root)['state'] === AIGateway::AVAILABILITY_AVAILABLE, '1c encendida, con registro y modelo: available');
        $check(preg_match('/<button class="[^"]*\bmobile hidden\b[^"]*" type="button" data-chat="#panel-assistant"/', $disponible['barra']) === 1 && preg_match('/<a class="mobile only item" href="#" data-chat="#panel-assistant">/', $disponible['barra']) === 1, '1d available: el lanzador de la barra (mobile hidden) y el item del menú de usuario (mobile only)');
        $check(count($nodos) === 1 && $disponible['js'], '1e available: un contenedor y el JS', (string) json_encode(['contenedores' => count($nodos), 'js' => $disponible['js']]));
        $textos = $nodo !== null ? json_decode($nodo->getAttribute('data-text'), true) : null;
        $sugerencias = $nodo !== null ? json_decode($nodo->getAttribute('data-suggestions'), true) : null;
        $check($nodo !== null && $nodo->hasAttribute('hidden') && $nodo->getAttribute('class') === 'ui chat' && $nodo->getAttribute('data-url') === McpController::routeName('assistant-ask') && $nodo->getAttribute('data-position') === Assistant::POSITION_RIGHT && !$nodo->hasAttribute('data-limit-message'), '1f el contenedor: oculto, la dirección de mcp-assistant-ask por routeName(), la posición por omisión y sin aviso de límite', $nodo !== null ? $nodo->getAttribute('data-url') . ' · ' . $nodo->getAttribute('data-position') : 'sin nodo');
        $check(is_array($textos) && is_string($textos['title'] ?? null) && is_array($textos['notices'] ?? null) && is_string($textos['networkError'] ?? null) && is_array($sugerencias) && count($sugerencias) === 3, '1g los textos y las tres sugerencias llegan como JSON en data-*');

        //El límite: una cuota de 1 y 5 tokens anotados.
        set_config(AIGateway::CONFIG_NAME, $centro(['quota' => 1]));
        AIGateway::recordUsage(Assistant::CAPABILITY, 'zz-ventana', 5);
        $limite = AIGateway::availability(Assistant::CAPABILITY, $root);
        $enLimite = $pintar();
        $nodo = $contenedores($enLimite['flotante'])[0] ?? null;
        $check($limite['state'] === AIGateway::AVAILABILITY_LIMIT && is_string($limite['message']), '1h con la cuota alcanzada: limit, con su aviso');
        $check($lanzadores($enLimite['barra']) === 2 && $nodo !== null && $enLimite['js'] && $nodo->getAttribute('data-limit-message') === $limite['message'], '1i limit: lanzador, contenedor y JS, con el aviso en data-limit-message', $nodo !== null ? $nodo->getAttribute('data-limit-message') : 'sin nodo');
        $borrarConsumo();
        echoTerminal(' ');

        //─── 2 · La posición ───────────────────────────────────────────────────────────────────────
        echoTerminal('[2] La posición de la ventana');
        $leidas = [];
        foreach (Assistant::POSITIONS as $posicion) {
            set_config(AIGateway::CONFIG_NAME, $centro(['position' => $posicion]));
            $leidas[$posicion] = Assistant::position();
        }
        $check($leidas === array_combine(Assistant::POSITIONS, Assistant::POSITIONS), '2a las cuatro posiciones del componente se leen tal cual', (string) json_encode($leidas));
        $raras = [];
        foreach (['arriba', 'RIGHT', '', 7, null] as $rara) {
            set_config(AIGateway::CONFIG_NAME, $centro(['position' => $rara]));
            $raras[] = Assistant::position();
        }
        set_config(AIGateway::CONFIG_NAME, $centro());
        $raras[] = Assistant::position();
        set_config(AIGateway::CONFIG_NAME, false);
        $raras[] = Assistant::position();
        $check(array_unique($raras) === [Assistant::POSITION_RIGHT], '2b una posición que no es de las cuatro, ninguna o sin centro: right', (string) json_encode($raras));
        set_config(AIGateway::CONFIG_NAME, $centro(['position' => 'floating left']));
        $nodo = $contenedores($pintar()['flotante'])[0] ?? null;
        $check($nodo !== null && $nodo->getAttribute('data-position') === 'floating left', '2c el contenedor lleva la posición guardada', $nodo !== null ? $nodo->getAttribute('data-position') : 'sin nodo');

        //El guardado de Configuración › IA.
        $guardado = $centro(['position' => 'left']);
        $entrada = fn(array $asistente): array => ['enabled' => '1', 'capabilities' => [Assistant::CAPABILITY => array_merge(['enabled' => '1', 'userTypes' => [''], 'quota' => '0', 'period' => 'accumulated', 'periodLength' => '1'], $asistente)]];
        $guardadas = [];
        foreach (Assistant::POSITIONS as $posicion) {
            $guardadas[$posicion] = SettingsController::aiCenterFromInput($entrada(['position' => $posicion]), $guardado)['capabilities'][Assistant::CAPABILITY]['position'] ?? null;
        }
        $check($guardadas === array_combine(Assistant::POSITIONS, Assistant::POSITIONS), '2d el guardado acepta las cuatro', (string) json_encode($guardadas));
        $rechazos = 0;
        foreach (['arriba', 'floating', ['right'], 'right; DROP'] as $rara) {
            try {
                SettingsController::aiCenterFromInput($entrada(['position' => $rara]), $guardado);
            } catch (InvalidParameterValueException $e) {
                $rechazos++;
            }
        }
        $check($rechazos === 4, '2e el guardado rechaza cualquier otra', "{$rechazos}/4");
        $sinCampo = SettingsController::aiCenterFromInput($entrada([]), $guardado)['capabilities'][Assistant::CAPABILITY]['position'] ?? null;
        $check($sinCampo === 'left', '2f un formulario sin el campo conserva la guardada', (string) json_encode($sinCampo));
        $check(array_keys(SettingsController::aiAssistantPositionOptions()) === Assistant::POSITIONS, '2g el desplegable ofrece exactamente las cuatro, en orden');
        echoTerminal(' ');

        //─── 3 · El tipo de fallo de reply() ───────────────────────────────────────────────────────
        echoTerminal('[3] reply() dice el tipo de fallo');
        //El adaptador simulado no es un AIChatProvider: reply() anota su consumo con translationAI.
        set_config('translationAI', 'zz-ventana');
        Assistant::setAdapterFactoryForTesting(fn(): object => new class {
            /**
             * @param array<int,array<string,mixed>> $messages
             * @param array<int,array<string,mixed>> $tools
             * @param float $temperature
             * @param int $maxTokens
             * @return array{content:string,toolCalls:list<array{id:string,name:string,arguments:string}>,usage:int}
             */
            public function chatWithTools(array $messages, array $tools, float $temperature = 0.2, int $maxTokens = 800): array
            {
                return ['content' => 'zz-respuesta-ventana', 'toolCalls' => [], 'usage' => 0];
            }
        });
        set_config(AIGateway::CONFIG_NAME, $centro([], false));
        $apagada = Assistant::reply($root, '¿zz?');
        set_config(AIGateway::CONFIG_NAME, $centro(['quota' => 1]));
        AIGateway::recordUsage(Assistant::CAPABILITY, 'zz-ventana', 5);
        $cuota = Assistant::reply($root, '¿zz?');
        $borrarConsumo();
        set_config(AIGateway::CONFIG_NAME, $centro());
        $vacia = Assistant::reply($root, '   ');
        $larga = Assistant::reply($root, str_repeat('z', Assistant::MAX_QUESTION_CHARS + 1));
        $buena = Assistant::reply($root, '¿zz?');
        $borrarConsumo();
        $check($apagada['ok'] === false && $apagada['kind'] === Assistant::KIND_UNAVAILABLE, '3a apagada: unavailable', (string) json_encode($apagada));
        $check($cuota['ok'] === false && $cuota['kind'] === Assistant::KIND_UNAVAILABLE, '3b con la cuota alcanzada: unavailable', (string) json_encode($cuota));
        $check($vacia['ok'] === false && $vacia['kind'] === Assistant::KIND_ERROR && $larga['ok'] === false && $larga['kind'] === Assistant::KIND_ERROR, '3c la pregunta vacía y la demasiado larga: error');
        $check($buena['ok'] === true && $buena['kind'] === null && $buena['answer'] === 'zz-respuesta-ventana', '3d una respuesta: kind null', (string) json_encode($buena));
        echoTerminal(' ');

        //─── 4 · La vuelta final vacía o cortada (D1) ───────────────────────────────────────────────
        echoTerminal('[4] Una vuelta final vacía o cortada no se da por buena');
        $final = ['content' => '', 'finishReason' => 'stop'];
        Assistant::setAdapterFactoryForTesting(function () use (&$final): object {
            return new class ($final) {
                /**
                 * @param array{content:string,finishReason:string} $final
                 */
                public function __construct(private array &$final)
                {
                }

                /**
                 * @param array<int,array<string,mixed>> $messages
                 * @param array<int,array<string,mixed>> $tools
                 * @param float $temperature
                 * @param int $maxTokens
                 * @param array<string,mixed> $extra
                 * @return array{content:string,toolCalls:list<array{id:string,name:string,arguments:string}>,usage:int,finishReason:string,reasoningTokens:int}
                 */
                public function chatWithTools(array $messages, array $tools, float $temperature = 0.2, int $maxTokens = 800, array $extra = []): array
                {
                    return ['content' => $this->final['content'], 'toolCalls' => [], 'usage' => 0, 'finishReason' => $this->final['finishReason'], 'reasoningTokens' => 790];
                }
            };
        });
        $cortada = McpProtocol::text('La respuesta se cortó antes de terminar. Prueba a preguntarlo de forma más concreta.');
        $sinTerminar = McpProtocol::text('No he podido terminar de buscarlo; prueba a preguntarlo de otra forma.');
        $casos = [];
        foreach ([['', 'length'], ['zz a medias', 'length'], ['', 'stop'], ["  \n ", 'stop']] as [$contenido, $motivo]) {
            $final = ['content' => $contenido, 'finishReason' => $motivo];
            $r = Assistant::reply($root, '¿zz?');
            $casos[] = [$r['ok'], $r['kind'], $r['error']];
        }
        $borrarConsumo();
        $check($casos[0] === [false, Assistant::KIND_ERROR, $cortada] && $casos[1] === [false, Assistant::KIND_ERROR, $cortada], '4a con finish_reason «length», vacía o a medias: ok false, kind error, «La respuesta se cortó…»', (string) json_encode(array_slice($casos, 0, 2)));
        $check($casos[2] === [false, Assistant::KIND_ERROR, $sinTerminar] && $casos[3] === [false, Assistant::KIND_ERROR, $sinTerminar], '4b vacía o solo espacios por otra causa: ok false, kind error, «No he podido terminar…»', (string) json_encode(array_slice($casos, 2)));
        $check(Assistant::lastTurn() === ['finishReason' => 'stop', 'reasoningTokens' => 790, 'outputTokens' => 0], '4c lastTurn() dice cómo terminó la última vuelta, sin su contenido', (string) json_encode(Assistant::lastTurn()));
        echoTerminal(' ');

        //─── 5 · Texto plano (D2) ──────────────────────────────────────────────────────────────────
        echoTerminal('[5] La respuesta sale en texto plano');
        $markdown = "## Correo\nPara configurarlo ve a **Sistema › Correo**: [http://localhost/admin/mail/](http://localhost/admin/mail/)\n- Uno __dos__\n- [Gestión de usuarios](/admin/users/list/)";
        $plano = "Correo\nPara configurarlo ve a Sistema › Correo: http://localhost/admin/mail/\n- Uno dos\n- Gestión de usuarios";
        $check(Assistant::plainText($markdown) === $plano, '5a fuera el título, el énfasis y [texto](url) (queda el texto); las viñetas y los saltos se quedan', (string) json_encode(Assistant::plainText($markdown)));
        $check(Assistant::plainText('Sin nada que quitar: 3 * 4, a_b y [nota].') === 'Sin nada que quitar: 3 * 4, a_b y [nota].', '5b un texto sin Markdown sale igual');
        $final = ['content' => $markdown, 'finishReason' => 'stop'];
        $limpia = Assistant::reply($root, '¿zz?');
        $borrarConsumo();
        $check($limpia['ok'] === true && $limpia['answer'] === $plano && $limpia['signature'] === Assistant::sign($plano), '5c reply() devuelve el texto limpio, y la firma es la del texto limpio', (string) json_encode(['answer' => $limpia['answer']]));
        $check(str_contains(Assistant::instructions(), 'sin Markdown') || str_contains(Assistant::instructions(), 'without Markdown'), '5d las instrucciones piden texto plano, sin Markdown');
        echoTerminal(' ');

        //─── 6 · Olvidar la conversación (H329) ────────────────────────────────────────────────────
        echoTerminal('[6] Los minutos tras los que el navegador olvida la conversación');
        $olvidos = [];
        foreach ([null, 5, 90, 1440, 4, 1441, '90', 0] as $guardadoOlvido) {
            set_config(AIGateway::CONFIG_NAME, $centro($guardadoOlvido === null ? [] : ['forgetAfterMinutes' => $guardadoOlvido]));
            $olvidos[] = Assistant::forgetAfterMinutes();
        }
        $check($olvidos === [60, 5, 90, 1440, 60, 60, 60, 60], '6a guardado: 5 a 1440 tal cual; sin guardar, fuera de rango o en texto, 60', (string) json_encode($olvidos));
        $entradaOlvido = fn($valor): array => ['enabled' => '1', 'capabilities' => [Assistant::CAPABILITY => ['enabled' => '1', 'userTypes' => [''], 'quota' => '0', 'period' => 'accumulated', 'periodLength' => '1', 'forgetAfterMinutes' => $valor]]];
        $aceptados = [];
        foreach (['5', '1440', '', ' 30 '] as $valorOlvido) {
            $aceptados[] = SettingsController::aiCenterFromInput($entradaOlvido($valorOlvido), $centro())['capabilities'][Assistant::CAPABILITY]['forgetAfterMinutes'] ?? null;
        }
        $rechazosOlvido = 0;
        foreach (['4', '1441', 'texto', '12.5', '-10'] as $valorOlvido) {
            try {
                SettingsController::aiCenterFromInput($entradaOlvido($valorOlvido), $centro());
            } catch (InvalidParameterValueException $e) {
                $rechazosOlvido++;
            }
        }
        $check($aceptados === [5, 1440, 60, 30] && $rechazosOlvido === 5, '6b el guardado: 5 y 1440 entran, vacío vale 60; 4, 1441, texto, decimales o negativo, rechazo', (string) json_encode(['aceptados' => $aceptados, 'rechazos' => $rechazosOlvido]));
        set_config(AIGateway::CONFIG_NAME, $centro(['forgetAfterMinutes' => 90]));
        $nodo = $contenedores($pintar()['flotante'])[0] ?? null;
        $check($nodo !== null && $nodo->getAttribute('data-forget-after-minutes') === '90' && $nodo->getAttribute('data-user-id') === (string) $idRoot, '6c el contenedor lleva los minutos (data-forget-after-minutes) y el usuario de la clave (data-user-id)', $nodo !== null ? $nodo->getAttribute('data-forget-after-minutes') . ' / ' . $nodo->getAttribute('data-user-id') : 'sin nodo');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        Assistant::setAdapterFactoryForTesting(null);
        foreach ($opciones as $opcion) {
            set_config($opcion, $previos[$opcion]);
        }
        $rolActual->setValue(null, $rolAntes);
        GenericContentPseudoMapper::setContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED, $acumulado);
        try {
            $borrarConsumo();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 200));
        }
    }
    $consulta = $database->prepare('SELECT COUNT(*) FROM `' . AIUsageMapper::TABLE . '` WHERE provider = ?');
    $consulta->execute(['zz-ventana']);
    $check((int) $consulta->fetchColumn() === 0, 'z1 no queda consumo zz-ventana');
    $filasDespues = $filasAsistente();
    $check($filasDespues === $filasAntes, 'z2 las filas de consumo del asistente son las mismas que al empezar', "antes {$filasAntes}, después {$filasDespues}");

    echoTerminal('');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La ventana del asistente del panel (ADR 0075, ronda 3b), sin red: el lanzador, el contenedor y su JS según AIGateway::availability() (hidden, available, limit con su aviso), la posición (las cuatro del componente, por omisión right, el guardado de Configuración › IA que rechaza otra) y el tipo de fallo de Assistant::reply(). Anota consumo zz-ventana y lo borra.')->setEffects([CliActions::EFFECT_DATABASE])->register();
