<?php

//La traducción con IA por el centro (ADR 0077): el prompt de siempre, lo que se manda por cada heredado y la ruta de la API.
//Sin red: un cliente HTTP simulado. Anota consumo «zz-prov-tr» y lo borra; las opciones se reponen en memoria.
//@areas nucleo
//@areas src/app/classes/API/

use API\Controllers\APIController;
use PiecesPHP\AI\AIGateway;
use PiecesPHP\AI\AITranslator;
use PiecesPHP\AI\Mappers\AIUsageMapper;
use PiecesPHP\AI\Providers\AIProviderRegistry;
use PiecesPHP\AI\Providers\OpenAICompatibleChatProvider;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Mcp\Assistant;
use PiecesPHP\Mcp\McpRoutes;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;
use PiecesPHP\UserSystem\UserTypes;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;

CliActions::make('unit-tests:core/ai-translator', function ($args) {

    echoTerminal("\e[33m[TEST:AITranslator] La traducción con IA por el centro, con un cliente HTTP simulado\e[39m");
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

    /** @var \ArrayObject<string,mixed> $captura */
    $captura = new \ArrayObject();
    $cliente = new class ($captura) implements \Psr\Http\Client\ClientInterface {
        public string $contenido = '{"title":"Hello"}';

        public function __construct(private \ArrayObject $captura)
        {
        }

        public function sendRequest(\Psr\Http\Message\RequestInterface $request): \Psr\Http\Message\ResponseInterface
        {
            $this->captura['url'] = (string) $request->getUri();
            $this->captura['cuerpo'] = json_decode((string) $request->getBody(), true);
            $this->captura['crudo'] = (string) $request->getBody();
            return new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], (string) json_encode(['id' => 'zz', 'object' => 'chat.completion', 'created' => 1, 'model' => 'zz', 'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => $this->contenido], 'finish_reason' => 'stop']], 'usage' => ['prompt_tokens' => 7, 'completion_tokens' => 3, 'total_tokens' => 10], 'zz_campo_del_proveedor' => 'zz']));
        }
    };

    $modelo = UsersModel::model();
    $modelo->resetAll();
    $modelo->select(['id'])->where(new WhereSegment([WhereItem::isEqual('type', UsersModel::TYPE_USER_ROOT), WhereItem::isEqual('status', UsersModel::STATUS_USER_ACTIVE)]))->execute(false, 1, 1);
    $idRoot = (int) (((array) (((array) $modelo->result())[0] ?? []))['id'] ?? 0);
    if (!$check($idRoot > 0, 'c0 hay un root', "root {$idRoot}")) {
        return ['success' => false, 'message' => "{$passed}/" . ($passed + $failed)];
    }
    $root = new UserDataPackage($idRoot);

    $opciones = [AIGateway::CONFIG_NAME, AIProviderRegistry::KEYS_CONFIG_NAME, 'translationAI', 'OpenAIApiKey', 'MistralAIApiKey', 'modelOpenAI', 'modelMistral', 'current_user', 'pcsphp_current_user_stored'];
    $previos = array_combine($opciones, array_map(fn(string $opcion) => get_config($opcion), $opciones));
    $rolActual = new \ReflectionProperty(Roles::class, 'currentRole');
    $rolAntes = $rolActual->getValue();
    $acumulado = GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED);
    $encendida = ['enabled' => true, 'userTypes' => UserTypes::codes(), 'quota' => 0];

    try {
        //─── 1 · El prompt ───────────────────────────────────────────────────────────────────────
        echoTerminal('[1] El prompt: el de siempre, byte a byte');
        //Los sha1 de buildTranslationPrompt() ANTES de moverlo (JKI, medidos en los dos adaptadores: iguales).
        $esperados = [
            [[['title' => 'Hola mundo', 'body' => "Línea 1\nLínea 2\t<b>negrita</b> &amp; más"], 'es', 'en'], 'f5645e946df6d9450663f3fc3f9d3bb541f2d921'],
            [[['segment' => '<p class="x">Texto con "comillas" y ñ</p>'], 'Español', 'Inglés'], '4e35235e18def00547f8db541dccdf65a0dadba5'],
            [[[], 'en', 'fr'], '3857c58a509a229de03719b007d12298f34df1d0'],
        ];
        $malos = [];
        foreach ($esperados as $n => [[$entrada, $de, $a], $sha1]) {
            if (sha1(AITranslator::prompt($entrada, $de, $a)) !== $sha1) {
                $malos[] = (string) $n;
            }
        }
        $check($malos === [], '1a AITranslator::prompt() da el mismo texto que el buildTranslationPrompt() de antes en los tres casos', implode(',', $malos));
        $check(sha1(AITranslator::prompt($esperados[0][0][0], 'es', 'en') . ' ') !== $esperados[0][1], '1b DISCRIMINANTE: un espacio de más ya no casa');
        echoTerminal(' ');

        //─── 2-5 · Por los heredados ─────────────────────────────────────────────────────────────
        echoTerminal('[2] Lo que se manda por el heredado de OpenAI y por el de Mistral, y lo que vuelve');
        AIGateway::setProviderHttpClientForTesting($cliente);
        set_config('OpenAIApiKey', 'zz-clave-openai');
        set_config('MistralAIApiKey', 'zz-clave-mistral');
        set_config('modelOpenAI', 'zz-modelo-openai');
        set_config('modelMistral', 'zz-modelo-mistral');
        set_config(AIGateway::CONFIG_NAME, ['enabled' => true, 'capabilities' => [AIGateway::TRANSLATION_CAPABILITY => $encendida]]);
        $entrada = ['title' => 'Hola'];
        $prompt = AITranslator::prompt($entrada, 'es', 'en');
        $recibido = [];
        $guarda = function ($valor) use (&$recibido) {
            $recibido[] = $valor;
            return $valor;
        };
        set_config('translationAI', AI_OPENAI);
        $captura->exchangeArray([]);
        $openai = AIGateway::translator($root);
        $resultadoOpenAI = $openai->translate($entrada, 'es', 'en', $guarda);
        $peticionOpenAI = $captura->getArrayCopy();
        $check(($peticionOpenAI['url'] ?? null) === 'https://api.openai.com/v1/chat/completions' && ($peticionOpenAI['cuerpo'] ?? null) === ['model' => 'zz-modelo-openai', 'messages' => [['role' => 'user', 'content' => $prompt]]], '2a OpenAI: exactamente {model, messages: [user con el prompt]}: sin temperature, sin tope y sin response_format', (string) json_encode(array_keys((array) ($peticionOpenAI['cuerpo'] ?? []))));
        set_config('translationAI', AI_MISTRAL);
        $captura->exchangeArray([]);
        $mistral = AIGateway::translator($root);
        $resultadoMistral = $mistral->translate($entrada, 'es', 'en', $guarda);
        $peticionMistral = $captura->getArrayCopy();
        $cuerpoMistral = is_array($peticionMistral['cuerpo'] ?? null) ? $peticionMistral['cuerpo'] : [];
        $check(($peticionMistral['url'] ?? null) === 'https://api.mistral.ai/v1/chat/completions' && array_keys($cuerpoMistral) === ['model', 'messages', 'temperature', 'response_format'] && $cuerpoMistral['temperature'] == 0.0 && $cuerpoMistral['response_format'] === ['type' => 'json_object'] && $cuerpoMistral['messages'] === [['role' => 'user', 'content' => $prompt]], '3a Mistral: con temperature 0.0 y response_format json_object, sin tope', (string) json_encode(array_keys($cuerpoMistral)));
        $check(($recibido[0] ?? null) === '{"title":"Hello"}' && ($recibido[1] ?? null) === ['title' => 'Hello'] && $resultadoOpenAI === '{"title":"Hello"}' && $resultadoMistral === ['title' => 'Hello'] && $openai->translate($entrada) === ['title' => 'Hello'], '4a la función de análisis recibe lo que recibía: el texto con OpenAI, el JSON ya decodificado con Mistral; la de por omisión decodifica', (string) json_encode($recibido));
        $check($mistral->lastUsage() === [['prompt_tokens' => 7, 'completion_tokens' => 3, 'total_tokens' => 10]] && $mistral->getTokensUsed() === 10 && $mistral->getTokensUsed([['total_tokens' => 4], ['total_tokens' => 6], ['otro' => 1]]) === 10 && ($mistral->getLastAskToChatOriginalResponse()['zz_campo_del_proveedor'] ?? null) === 'zz', '5a lastUsage(): la lista de usage del proveedor; getTokensUsed(): la suma de total_tokens; la respuesta original, tal cual (con sus campos propios)', (string) json_encode($mistral->lastUsage()));
        echoTerminal(' ');

        //─── 6-7 · La capacidad y los tiempos ────────────────────────────────────────────────────
        echoTerminal('[6] La capacidad con su registro y su modelo; y los tiempos máximos');
        set_config(AIProviderRegistry::KEYS_CONFIG_NAME, AIProviderRegistry::sealKeys(['zz-prov-tr' => 'zz-clave-tr']));
        set_config(AIGateway::CONFIG_NAME, ['enabled' => true, 'providers' => [['id' => 'zz-prov-tr', 'type' => 'deepseek', 'alias' => 'Zz traducción']], 'capabilities' => [AIGateway::TRANSLATION_CAPABILITY => $encendida + ['provider' => 'zz-prov-tr', 'model' => 'zz-modelo-tr'], Assistant::CAPABILITY => $encendida]]);
        $elegido = AIGateway::translator($root);
        $captura->exchangeArray([]);
        $elegido->translate($entrada, 'es', 'en');
        $cuerpoElegido = is_array($captura['cuerpo'] ?? null) ? $captura['cuerpo'] : [];
        $check($elegido->provider()->recordId() === 'zz-prov-tr' && $elegido->provider()->model() === 'zz-modelo-tr' && ($captura['url'] ?? null) === 'https://api.deepseek.com/chat/completions' && ($cuerpoElegido['response_format'] ?? null) === ['type' => 'json_object'] && !array_key_exists('temperature', $cuerpoElegido), '6a con provider y model: ese registro (DeepSeek), su modelo y sus parámetros de traducción (json_object, sin temperature)', (string) json_encode(array_keys($cuerpoElegido)));
        set_config('translationAI', AI_OPENAI);
        set_config(AIGateway::CONFIG_NAME, ['enabled' => true, 'capabilities' => [AIGateway::TRANSLATION_CAPABILITY => $encendida, Assistant::CAPABILITY => $encendida]]);
        $check(AIGateway::translator($root)->provider()->recordId() === AIProviderRegistry::CONVERTED_OPENAI, '6b sin ellos: el convertido de «IA para traducciones» (sin la conversión escrita)');
        //El chat es la capacidad del asistente, que solo existe con MCP encendido.
        if (!McpRoutes::ENABLE) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m 7a: esta instalación tiene MCP apagado y no hay chat del asistente.");
        } else {
            $traductor = AIGateway::translator($root)->provider();
            $chat = AIGateway::chatProvider(Assistant::CAPABILITY, $root);
            $check($traductor instanceof OpenAICompatibleChatProvider && $chat instanceof OpenAICompatibleChatProvider && $traductor->timeoutSeconds() === 120 && $chat->timeoutSeconds() === 30, '7a el tiempo máximo de la traducción es 120 s y el del chat sigue en 30');
        }
        echoTerminal(' ');

        //─── 8 · La ruta de la API ───────────────────────────────────────────────────────────────
        echoTerminal('[8] La acción «translate» de la API, en proceso, con el registro elegido');
        set_config(AIGateway::CONFIG_NAME, ['enabled' => true, 'providers' => [['id' => 'zz-prov-tr', 'type' => 'deepseek', 'alias' => 'Zz traducción']], 'capabilities' => [AIGateway::TRANSLATION_CAPABILITY => $encendida + ['provider' => 'zz-prov-tr', 'model' => 'zz-modelo-tr']]]);
        set_config('current_user', (object) ['id' => $idRoot]);
        set_config('pcsphp_current_user_stored', null);
        Roles::setCurrentRole(UsersModel::TYPE_USER_ROOT);
        $antes = AIGateway::usage(AIGateway::TRANSLATION_CAPABILITY, 'zz-prov-tr');
        $peticion = (new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-prueba-traduccion'), new Headers(), [], [], (new StreamFactory())->createStream('')))
            ->withParsedBody(['text' => (string) json_encode($entrada), 'from' => 'es', 'to' => 'en'])
            ->withAttribute('actionType', 'translate')
            ->withAttribute('route', null);
        $respuesta = (new APIController())->translations($peticion, new ResponseRoute());
        $json = json_decode((string) $respuesta->getBody(), true);
        $json = is_array($json) ? $json : [];
        $despues = AIGateway::usage(AIGateway::TRANSLATION_CAPABILITY, 'zz-prov-tr');
        $check(($json['success'] ?? null) === true && ($json['result']['translation'] ?? null) === ['title' => 'Hello'] && ($json['AI']['provider'] ?? null) === 'zz-prov-tr' && ($json['AI']['lastUsage'] ?? null) === [['prompt_tokens' => 7, 'completion_tokens' => 3, 'total_tokens' => 10]] && ($json['AI']['tokensUsed'] ?? null) === 10 && $despues - $antes === 10, '8a la respuesta de siempre (translation, AI.provider, AI.lastUsage, AI.tokensUsed) y 10 tokens anotados con el registro', (string) json_encode(['success' => $json['success'] ?? null, 'message' => $json['message'] ?? null, 'provider' => $json['AI']['provider'] ?? null, 'tokens' => $despues - $antes]));
        echoTerminal(' ');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        AIGateway::setProviderHttpClientForTesting(null);
        foreach ($previos as $opcion => $valor) {
            set_config($opcion, $valor);
        }
        $rolActual->setValue(null, $rolAntes);
        $database = (new BaseModel())->getDatabase();
        $database?->prepare('DELETE FROM `' . AIUsageMapper::TABLE . '` WHERE provider = ?')->execute(['zz-prov-tr']);
        GenericContentPseudoMapper::setContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED, $acumulado);
    }
    $database = (new BaseModel())->getDatabase();
    $quedan = $database?->prepare('SELECT COUNT(*) FROM `' . AIUsageMapper::TABLE . '` WHERE provider = ?');
    $quedan?->execute(['zz-prov-tr']);
    $check($quedan !== null && $quedan !== false && (int) $quedan->fetchColumn() === 0 && GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED) == $acumulado, 'z0 sin consumo zz-prov-tr y el acumulado como estaba');

    echoTerminal('');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La traducción con IA por el centro (ADR 0077), sin red: el prompt de siempre (tres casos guardados), lo que se manda por los heredados de OpenAI y de Mistral, lo que recibe la función de análisis, el consumo, la capacidad con su registro, los tiempos (120 s y 30 s) y la acción «translate» de la API en proceso. Anota consumo zz-prov-tr y lo borra.')->setEffects([CliActions::EFFECT_DATABASE])->register();
