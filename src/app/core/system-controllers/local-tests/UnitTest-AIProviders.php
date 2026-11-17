<?php

//Proveedores de IA (ADR 0077) con un cliente HTTP simulado: sin red. Anota consumo zz-prov-a, escribe la caché de modelos y
//crea dos usuarios zz-agente-prov-*: todo se borra o se repone (ai_center, ai_provider_keys y ai_provider_models, por sha1).
//@areas nucleo
//@areas src/app/classes/PiecesPHP/Mcp/

use EventsLog\Mappers\LogsMapper;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AI\AICapabilityRegistry;
use PiecesPHP\AI\AIGateway;
use PiecesPHP\AI\AIUnavailableException;
use PiecesPHP\AI\Mappers\AIUsageMapper;
use PiecesPHP\AI\Providers\AIProviderCatalog;
use PiecesPHP\AI\Providers\AIProviderRecord;
use PiecesPHP\AI\Providers\AIProviderRegistry;
use PiecesPHP\AI\Providers\OpenAICompatibleChatProvider;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\ConfigHelpers\ConfigOptions;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Mcp\Assistant;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\LoginAttemptsModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/ai-providers', function ($args) {

    echoTerminal("\e[33m[TEST:AIProviders] Proveedores de IA extensibles, con un cliente HTTP simulado\e[39m");
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

    //El cliente PSR-18 simulado: guarda la última petición y responde lo que se le diga.
    /** @var \ArrayObject<string,mixed> $captura */
    $captura = new \ArrayObject();
    $cliente = new class ($captura) implements \Psr\Http\Client\ClientInterface {
        public int $status = 200;
        public string $body = '';
        public bool $falla = false;

        public function __construct(private \ArrayObject $captura)
        {
        }

        public function sendRequest(\Psr\Http\Message\RequestInterface $request): \Psr\Http\Message\ResponseInterface
        {
            $this->captura['url'] = (string) $request->getUri();
            $this->captura['metodo'] = $request->getMethod();
            $this->captura['headers'] = array_change_key_case($request->getHeaders(), \CASE_LOWER);
            $this->captura['cuerpo'] = json_decode((string) $request->getBody(), true);
            if ($this->falla) {
                throw new \GuzzleHttp\Exception\ConnectException('zz sin respuesta', $request);
            }
            return new \GuzzleHttp\Psr7\Response($this->status, ['Content-Type' => 'application/json'], $this->body);
        }
    };
    $respuestaConHerramientas = (string) json_encode(['id' => 'zz', 'object' => 'chat.completion', 'created' => 1, 'model' => 'zz', 'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => 'zz-respuesta', 'tool_calls' => [['id' => 'zz-llamada-1', 'type' => 'function', 'function' => ['name' => 'zz_herramienta', 'arguments' => '{"a":1}']]]], 'finish_reason' => 'tool_calls']], 'usage' => ['prompt_tokens' => 11, 'completion_tokens' => 7, 'total_tokens' => 18]]);

    $modelo = UsersModel::model();
    $modelo->resetAll();
    $modelo->select(['id'])->where(new WhereSegment([WhereItem::isEqual('type', UsersModel::TYPE_USER_ROOT), WhereItem::isEqual('status', UsersModel::STATUS_USER_ACTIVE)]))->execute(false, 1, 1);
    $idRoot = (int) (((array) (((array) $modelo->result())[0] ?? []))['id'] ?? 0);
    $database = (new BaseModel())->getDatabase();
    if (!$check($idRoot > 0 && $database !== null, 'c0 hay un root y conexión a la base', "root {$idRoot}")) {
        return ['success' => false, 'message' => "{$passed}/" . ($passed + $failed)];
    }
    $filas = function () use (&$database): string {
        $database = (new BaseModel())->getDatabase();
        $consulta = $database?->prepare('SELECT `name`, `value` FROM `pcsphp_app_config` WHERE `name` IN (?, ?, ?) ORDER BY `name`');
        $consulta?->execute([AIGateway::CONFIG_NAME, AIProviderRegistry::KEYS_CONFIG_NAME, AIGateway::MODELS_CONFIG_NAME]);
        return sha1((string) json_encode($consulta !== null && $consulta !== false ? $consulta->fetchAll(\PDO::FETCH_ASSOC) : null));
    };
    $filasAntes = $filas();
    //La fila de la caché de modelos la escribe «Actualizar modelos» (sección 14): se repone tal cual, o se borra si no había.
    $leerFilaModelos = function () use (&$database): ?array {
        $consulta = $database?->prepare('SELECT * FROM `pcsphp_app_config` WHERE BINARY `name` = ?');
        $consulta?->execute([AIGateway::MODELS_CONFIG_NAME]);
        $leida = $consulta !== null && $consulta !== false ? $consulta->fetch(\PDO::FETCH_ASSOC) : false;
        return $leida === false ? null : $leida;
    };
    $filaModelos = $leerFilaModelos();
    $fijadasPorArchivo = ConfigOptions::fixedByFile();

    $opciones = [AIGateway::CONFIG_NAME, AIProviderRegistry::KEYS_CONFIG_NAME, AIGateway::MODELS_CONFIG_NAME, 'translationAI', 'OpenAIApiKey', 'MistralAIApiKey', 'GroqAPIKey', 'modelOpenAI', 'modelMistral', 'modelGroq'];
    $previos = array_combine($opciones, array_map(fn(string $opcion) => get_config($opcion), $opciones));
    $acumulado = GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED);
    $tablasMedidas = [];

    try {
        //─── 1 · El catálogo ─────────────────────────────────────────────────────────────────────
        echoTerminal('[1] El catálogo medido');
        $catalogoValido = function (array $tipos): bool {
            foreach ($tipos as $clave => $tipo) {
                $documentado = $tipo['docs'] !== [] && count(array_filter($tipo['docs'], fn($url) => !is_string($url) || !str_starts_with($url, 'https://'))) === 0;
                $fecha = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $tipo['measured']);
                if ($fecha === false || $fecha->format('Y-m-d') !== $tipo['measured'] || !in_array(AIProviderCatalog::CAPABILITY_CHAT, $tipo['capabilities'], true)) {
                    return false;
                }
                if ($clave === AIProviderCatalog::TYPE_CUSTOM ? $tipo['baseUrl'] !== null : (!is_string($tipo['baseUrl']) || !str_starts_with($tipo['baseUrl'], 'https://') || !$documentado)) {
                    return false;
                }
            }
            return isset($tipos[AIProviderCatalog::TYPE_CUSTOM]);
        };
        $tipos = AIProviderCatalog::TYPES;
        $check($catalogoValido($tipos), '1a cada tipo con dirección https, su documentación https y la fecha en que se midió; custom sin dirección', implode(', ', array_keys($tipos)));
        $sinHttps = $tipos;
        $sinHttps['openai']['baseUrl'] = 'http://api.openai.com/v1';
        $customConDireccion = $tipos;
        $customConDireccion[AIProviderCatalog::TYPE_CUSTOM]['baseUrl'] = 'https://zz.example';
        $sinDocumentacion = $tipos;
        $sinDocumentacion['groq']['docs'] = [];
        $check(!$catalogoValido($sinHttps) && !$catalogoValido($customConDireccion) && !$catalogoValido($sinDocumentacion), '1b DISCRIMINANTE: una dirección http, un custom con dirección o un tipo sin documentación no pasan');
        echoTerminal(' ');

        //─── 2 · La petición que sale y la respuesta ─────────────────────────────────────────────
        echoTerminal('[2] Por tipo: la petición (dirección, cabecera de la clave, max_tokens) y la respuesta');
        $esperada = ['content' => 'zz-respuesta', 'toolCalls' => [['id' => 'zz-llamada-1', 'name' => 'zz_herramienta', 'arguments' => '{"a":1}']], 'usage' => 18, 'inputTokens' => 11, 'outputTokens' => 7, 'finishReason' => 'tool_calls', 'reasoningTokens' => 0, 'reportedCostUsd' => 0.0];
        $peticionCorrecta = function (array $vista, AIProviderRecord $registro, int $tope): bool {
            $cabeceras = is_array($vista['headers'] ?? null) ? $vista['headers'] : [];
            $clave = $registro->keyHeader() === null
                ? ($cabeceras['authorization'][0] ?? null) === 'Bearer ' . $registro->key()
                : ($cabeceras[strtolower((string) $registro->keyHeader())][0] ?? null) === $registro->key() && !isset($cabeceras['authorization']);
            //El tope con el nombre de su tipo, y no el otro.
            $campo = (string) (AIProviderCatalog::get($registro->type())['maxTokensField'] ?? '');
            $otro = $campo === 'max_tokens' ? 'max_completion_tokens' : 'max_tokens';
            $cuerpo = is_array($vista['cuerpo'] ?? null) ? $vista['cuerpo'] : [];
            return ($vista['url'] ?? null) === $registro->baseUrl() . '/chat/completions' && $clave && ($cuerpo[$campo] ?? null) === $tope && !array_key_exists($otro, $cuerpo) && is_array($cuerpo['tools'] ?? null);
        };
        $cliente->status = 200;
        $cliente->body = $respuestaConHerramientas;
        $herramientas = [['type' => 'function', 'function' => ['name' => 'zz_herramienta', 'description' => 'zz', 'parameters' => ['type' => 'object', 'properties' => []]]]];
        $malos = [];
        $ultimo = null;
        foreach (array_keys(AIProviderCatalog::TYPES) as $tipo) {
            $esCustom = $tipo === AIProviderCatalog::TYPE_CUSTOM;
            $registro = new AIProviderRecord("zz-{$tipo}", $tipo, "Zz {$tipo}", "zz-clave-{$tipo}", $esCustom ? 'https://zz-propio.example/v1/' : null, $esCustom ? 'X-Zz-Key' : null);
            $captura->exchangeArray([]);
            $resultado = (new OpenAICompatibleChatProvider($registro, 'zz-modelo', $cliente))->chatWithTools([['role' => 'user', 'content' => 'zz']], $herramientas, 0.2, 123);
            $vista = $captura->getArrayCopy();
            if (!$peticionCorrecta($vista, $registro, 123) || $resultado !== $esperada || (($vista['cuerpo'] ?? [])['model'] ?? null) !== 'zz-modelo') {
                $malos[] = $tipo . ' ' . json_encode(['url' => $vista['url'] ?? null, 'r' => $resultado]);
            }
            $ultimo = [$vista, $registro];
        }
        $check($malos === [], '2a cada tipo (y custom con su cabecera X-Zz-Key, sin Authorization): base + /chat/completions, la clave en su cabecera, max_tokens 123 y tools; la respuesta a {content, toolCalls, usage, inputTokens, outputTokens}', implode(' · ', $malos));
        $provocadas = 0;
        if ($ultimo !== null) {
            [$vista, $registro] = $ultimo;
            $otraDireccion = $vista;
            $otraDireccion['url'] = 'https://zz-otro.example/chat/completions';
            $sinTope = $vista;
            $sinTope['cuerpo'] = array_map(fn($valor) => $valor === 123 ? 800 : $valor, (array) $sinTope['cuerpo']);
            $conBearer = $vista;
            $conBearer['headers']['authorization'] = ['Bearer ' . $registro->key()];
            $provocadas = (int) !$peticionCorrecta($otraDireccion, $registro, 123) + (int) !$peticionCorrecta($sinTope, $registro, 123) + (int) !$peticionCorrecta($conBearer, $registro, 123);
        }
        $check($provocadas === 3, '2b DISCRIMINANTE: otra dirección, otro max_tokens o un Authorization de más en el custom no pasan', "{$provocadas}/3");
        $sinTotal = OpenAICompatibleChatProvider::parse(['choices' => [['message' => ['content' => 'zz']]], 'usage' => ['prompt_tokens' => 4, 'completion_tokens' => 5]]);
        $check($sinTotal['usage'] === 9 && $sinTotal['inputTokens'] === 4 && $sinTotal['outputTokens'] === 5 && $sinTotal['toolCalls'] === [], '2c sin total_tokens, usage es la suma de entrada y salida', (string) json_encode($sinTotal));
        //Como Groq con gpt-oss (JKQ, #2919): el detalle del consumo solo con reasoning_tokens. La lectura del SDK pide los otros
        //dos campos sin «??» y, en local, el aviso abortaba la respuesta («HTTP 0, ErrorException»).
        $cliente->status = 200;
        $cliente->body = (string) json_encode(['id' => 'zz', 'object' => 'chat.completion', 'created' => 1, 'model' => 'zz', 'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => 'zz-razonada', 'reasoning' => 'zz-pensado'], 'finish_reason' => 'stop']], 'usage' => ['prompt_tokens' => 11, 'completion_tokens' => 7, 'total_tokens' => 18, 'completion_tokens_details' => ['reasoning_tokens' => 5]]]);
        try {
            $razonada = (new OpenAICompatibleChatProvider(new AIProviderRecord('zz-razonada', 'groq', 'Zz razonada', 'zz-clave-razonada'), 'zz-modelo', $cliente))->chatWithTools([['role' => 'user', 'content' => 'zz']]);
            $razonadaError = null;
        } catch (\Throwable $e) {
            $razonada = null;
            $razonadaError = get_class($e) . ': ' . $e->getMessage();
        }
        $check($razonada !== null && $razonada['content'] === 'zz-razonada' && $razonada['usage'] === 18 && $razonada['inputTokens'] === 11 && $razonada['outputTokens'] === 7 && ($razonada['finishReason'] ?? null) === 'stop' && ($razonada['reasoningTokens'] ?? null) === 5, '2d un 200 con completion_tokens_details solo con reasoning_tokens (Groq con gpt-oss): se lee, con sus tokens, su finish_reason y sus reasoning_tokens', (string) ($razonadaError ?? json_encode($razonada)));
        //El coste que da el proveedor (ADR 0077 §8): solo OpenRouter lo documenta (usage.cost, en créditos de base USD).
        $cliente->status = 200;
        $cliente->body = (string) json_encode(['id' => 'zz', 'object' => 'chat.completion', 'created' => 1, 'model' => 'zz', 'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => 'zz-con-coste'], 'finish_reason' => 'stop']], 'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15, 'cost' => 0.0123]]);
        $costes = [];
        foreach (['openrouter', 'groq'] as $tipoCoste) {
            $costes[$tipoCoste] = (new OpenAICompatibleChatProvider(new AIProviderRecord("zz-coste-{$tipoCoste}", $tipoCoste, "Zz {$tipoCoste}", 'zz-clave'), 'zz-modelo', $cliente))->chatWithTools([['role' => 'user', 'content' => 'zz']])['reportedCostUsd'] ?? null;
        }
        $check($costes === ['openrouter' => 0.0123, 'groq' => 0.0] && AIProviderCatalog::reportedCostUsd('openrouter', ['cost' => -1]) === 0.0 && AIProviderCatalog::reportedCostUsd('openrouter', ['cost' => '0.5']) === 0.0, '2e usage.cost: OpenRouter lo da (0.0123); otro tipo, aunque lo traiga, 0; uno negativo o en texto, 0', (string) json_encode($costes));
        echoTerminal(' ');

        //─── 3 · Los errores del proveedor ───────────────────────────────────────────────────────
        echoTerminal('[3] Un 401 y un 429: \RuntimeException sin la clave ni el cuerpo');
        $claveSecreta = 'zz-clave-secreta-3-' . bin2hex(random_bytes(4));
        $cuerpoError = (string) json_encode(['error' => ['message' => "Incorrect API key provided: {$claveSecreta}", 'type' => 'invalid_request_error', 'param' => null, 'code' => 'invalid_api_key']]);
        $limpio = fn(string $texto): bool => mb_strpos($texto, $claveSecreta) === false && mb_strpos($texto, 'Incorrect API key') === false;
        $registroError = new AIProviderRecord('zz-error', 'openai', 'Zz error', $claveSecreta);
        $mensajes = [];
        foreach ([401, 429] as $estado) {
            $cliente->status = $estado;
            $cliente->body = $cuerpoError;
            try {
                (new OpenAICompatibleChatProvider($registroError, 'zz-modelo', $cliente))->chatWithTools([['role' => 'user', 'content' => 'zz']]);
                $mensajes[$estado] = null;
            } catch (\RuntimeException $e) {
                $mensajes[$estado] = $e->getMessage() . ' ' . $e->getTraceAsString() . ' ' . (string) $e->getPrevious()?->getMessage();
            }
        }
        $check(is_string($mensajes[401]) && is_string($mensajes[429]) && $limpio($mensajes[401]) && $limpio($mensajes[429]) && mb_strpos($mensajes[401], 'HTTP 401') !== false && mb_strpos($mensajes[429], 'HTTP 429') !== false, '3a los dos lanzan \RuntimeException con el código HTTP, sin la clave ni el texto del proveedor, ni en la pila ni en la anterior', (string) json_encode($mensajes));
        $check(!$limpio((string) $mensajes[401] . ' ' . $cuerpoError), '3b DISCRIMINANTE: un mensaje que lleve el cuerpo del proveedor no pasa');
        echoTerminal(' ');

        //─── 4 · Los registros ───────────────────────────────────────────────────────────────────
        echoTerminal('[4] Los registros: convertidos (sin la conversión escrita) y personalizados');
        set_config('OpenAIApiKey', 'zz-clave-openai');
        set_config('MistralAIApiKey', '');
        set_config('GroqAPIKey', 'zz-clave-groq');
        set_config(AIGateway::CONFIG_NAME, ['providers' => [
            ['id' => 'zz-otro', 'type' => AIProviderCatalog::TYPE_CUSTOM, 'alias' => 'Zz otro', 'baseUrl' => 'http://otro.com'],
            ['id' => 'zz-local', 'type' => AIProviderCatalog::TYPE_CUSTOM, 'alias' => 'Zz local', 'baseUrl' => 'http://localhost:11434'],
            ['id' => 'legacy-falso', 'type' => 'openai', 'alias' => 'Zz falso'],
        ]]);
        $todos = AIProviderRegistry::all();
        $paraChat = AIProviderRegistry::supporting(AIProviderCatalog::CAPABILITY_CHAT);
        $check(isset($todos[AIProviderRegistry::CONVERTED_OPENAI]) && !isset($todos[AIProviderRegistry::CONVERTED_MISTRAL]) && isset($todos[AIProviderRegistry::CONVERTED_GROQ]), '4a los convertidos solo existen con su clave: OpenAI y Groq sí, Mistral (sin clave) no', implode(', ', array_keys($todos)));
        //Antes, «legacy-groq solo voz»: el convertido es un registro normal y completo (ADR 0077, revisión del 14).
        $check(isset($paraChat[AIProviderRegistry::CONVERTED_GROQ]) && isset($paraChat[AIProviderRegistry::CONVERTED_OPENAI]) && ($todos[AIProviderRegistry::CONVERTED_GROQ] ?? null)?->supports(AIProviderCatalog::CAPABILITY_SPEECH) === true, '4b el convertido groq es completo: se ofrece para chat y para voz', implode(', ', array_keys($paraChat)));
        $check(!isset($todos['zz-otro']) && isset($todos['zz-local']) && !isset($todos['legacy-falso']), '4c un custom con http://otro.com no vale, uno con http://localhost:11434 sí, y un guardado no puede llevar el prefijo legacy-', implode(', ', array_keys($todos)));
        $check(!AIProviderCatalog::isValidCustomBaseUrl('http://otro.com') && !AIProviderCatalog::isValidCustomBaseUrl('https://zz:clave@zz.example') && AIProviderCatalog::isValidCustomBaseUrl('http://127.0.0.1:8080/v1') && AIProviderCatalog::isValidCustomBaseUrl('https://zz.example/v1'), '4d DISCRIMINANTE de la dirección: http a otro anfitrión y una URL con usuario, no; 127.0.0.1 con puerto y https, sí');
        echoTerminal(' ');

        //─── 5 · La clave no se ve ───────────────────────────────────────────────────────────────
        echoTerminal('[5] La clave no aparece al volcar un registro ni en sus excepciones');
        $claveCinco = 'zz-clave-secreta-5-' . bin2hex(random_bytes(4));
        $noLaEnsena = function (object $objeto) use ($claveCinco): bool {
            ob_start();
            var_dump($objeto);
            $volcado = (string) ob_get_clean();
            $textos = [var_export($objeto, true), print_r($objeto, true), (string) json_encode($objeto), $volcado];
            return count(array_filter($textos, fn(string $texto): bool => mb_strpos($texto, $claveCinco) !== false)) === 0;
        };
        $registroCinco = new AIProviderRecord('zz-cinco', 'openai', 'Zz cinco', $claveCinco);
        $serializa = true;
        try {
            serialize($registroCinco);
        } catch (\LogicException $e) {
            $serializa = false;
        }
        $check($noLaEnsena($registroCinco) && $registroCinco->key() === $claveCinco && !$serializa, '5a var_export, print_r, var_dump y json_encode no la enseñan, key() sí, y no se serializa');
        $textoExcepcion = '';
        try {
            new AIProviderRecord('zz-cinco', 'zz-tipo-que-no-existe', 'Zz', $claveCinco);
        } catch (\InvalidArgumentException $e) {
            $textoExcepcion = $e->getMessage() . ' ' . $e->getTraceAsString();
        }
        $check($textoExcepcion !== '' && mb_strpos($textoExcepcion, $claveCinco) === false, '5b la excepción de un registro que no vale no lleva la clave, tampoco en la pila (#[\SensitiveParameter])', mb_substr($textoExcepcion, 0, 160));
        $conPropiedad = new class ($claveCinco) {
            public function __construct(public string $key)
            {
            }
        };
        $check(!$noLaEnsena($conPropiedad), '5c DISCRIMINANTE: un objeto con la clave en una propiedad pública no pasa');
        echoTerminal(' ');

        //─── 6 · La puerta ───────────────────────────────────────────────────────────────────────
        echoTerminal('[6] La puerta: función → registro → modelo');
        $encendido = fn(array $asistente): array => ['enabled' => true, 'providers' => [
            ['id' => 'zz-prov-a', 'type' => 'deepseek', 'alias' => 'Zz A'],
            ['id' => 'zz-prov-b', 'type' => 'openrouter', 'alias' => 'Zz B'],
        ], 'capabilities' => [Assistant::CAPABILITY => array_merge(['enabled' => true, 'userTypes' => [], 'quota' => 0], $asistente)]];
        set_config(AIProviderRegistry::KEYS_CONFIG_NAME, AIProviderRegistry::sealKeys(['zz-prov-a' => 'zz-clave-a']));
        set_config('translationAI', AI_OPENAI);
        set_config('modelOpenAI', 'zz-modelo-heredado');
        $motivo = function (array $asistente) use ($encendido, $idRoot): ?string {
            set_config(AIGateway::CONFIG_NAME, $encendido($asistente));
            try {
                AIGateway::chatProvider(Assistant::CAPABILITY, new UserDataPackage($idRoot));
                return null;
            } catch (AIUnavailableException $e) {
                return $e->getMessage();
            }
        };
        set_config(AIGateway::CONFIG_NAME, $encendido(['provider' => 'zz-prov-a', 'model' => 'zz-modelo-elegido']));
        $elegido = AIGateway::chatProvider(Assistant::CAPABILITY, new UserDataPackage($idRoot));
        $check($elegido->recordId() === 'zz-prov-a' && $elegido->model() === 'zz-modelo-elegido' && AIGateway::usageProviderName($elegido) === 'zz-prov-a', '6a con provider y model: ese registro y ese modelo; se anota con su id', $elegido->recordId() . ' · ' . $elegido->model());
        set_config(AIGateway::CONFIG_NAME, $encendido([]));
        $heredado = AIGateway::chatProvider(Assistant::CAPABILITY, new UserDataPackage($idRoot));
        $check($heredado->recordId() === AIProviderRegistry::CONVERTED_OPENAI && $heredado->model() === 'zz-modelo-heredado' && AIGateway::usageProviderName($heredado) === AI_OPENAI, '6b sin ellos: el convertido de translationAI (openai con modelOpenAI), anotado como «OpenAI»', $heredado->recordId() . ' · ' . $heredado->model());
        $noExiste = __('ai', 'El proveedor de inteligencia artificial elegido para esta función no existe.');
        $sinClave = __('ai', 'El proveedor de inteligencia artificial elegido para esta función no tiene clave.');
        $noSabe = __('ai', 'El proveedor de inteligencia artificial elegido no sabe hacer lo que necesita esta función.');
        $sinModelo = __('ai', 'Esta función de inteligencia artificial no tiene modelo elegido.');
        $check($motivo(['provider' => 'zz-no-existe', 'model' => 'zz']) === $noExiste, '6c un registro que no existe: AIUnavailableException con su motivo');
        $check($motivo(['provider' => 'zz-prov-b', 'model' => 'zz']) === $sinClave, '6d DISCRIMINANTE: un registro sin clave: «no tiene clave» (sin la comprobación, la puerta lo daría)');
        //Antes con legacy-groq (solo voz), que ya no existe: el «no sabe hacer», con un registro sin voz para la voz a texto.
        set_config(AIGateway::CONFIG_NAME, ['enabled' => true, 'providers' => [['id' => 'zz-prov-a', 'type' => 'deepseek', 'alias' => 'Zz A']], 'capabilities' => [AIGateway::SPEECH_CAPABILITY => ['enabled' => true, 'userTypes' => [], 'quota' => 0, 'provider' => 'zz-prov-a', 'model' => 'zz']]]);
        try {
            AIGateway::speechProvider(AIGateway::SPEECH_CAPABILITY, new UserDataPackage($idRoot));
            $vozSinVoz = null;
        } catch (AIUnavailableException $e) {
            $vozSinVoz = $e->getMessage();
        }
        $check($vozSinVoz === $noSabe && $motivo(['provider' => 'zz-prov-a']) === $sinModelo, '6e un registro sin voz (DeepSeek) para la voz a texto: «no sabe hacer»; un registro sin modelo: «sin modelo»', (string) $vozSinVoz);
        $check($motivo(['enabled' => false, 'provider' => 'zz-no-existe']) === AIGateway::denialReason(Assistant::CAPABILITY, new UserDataPackage($idRoot)) && $motivo(['enabled' => false, 'provider' => 'zz-no-existe']) !== $noExiste, '6f denialReason antes que nada: apagada, el motivo es el de apagada aunque el registro no exista');
        echoTerminal(' ');

        //─── 7 · El asistente por la puerta ──────────────────────────────────────────────────────
        echoTerminal('[7] El asistente pide su proveedor a la puerta');
        set_config(AIGateway::CONFIG_NAME, $encendido(['provider' => 'zz-prov-a', 'model' => 'zz-modelo-elegido']));
        $cliente->status = 200;
        $cliente->body = (string) json_encode(['id' => 'zz', 'object' => 'chat.completion', 'created' => 1, 'model' => 'zz', 'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => 'zz-respuesta-del-asistente'], 'finish_reason' => 'stop']], 'usage' => ['prompt_tokens' => 3, 'completion_tokens' => 2, 'total_tokens' => 5]]);
        $captura->exchangeArray([]);
        AIGateway::setProviderHttpClientForTesting($cliente);
        $antes = AIGateway::usage(Assistant::CAPABILITY, 'zz-prov-a');
        $respuesta = Assistant::reply(new UserDataPackage($idRoot), 'zz');
        $despues = AIGateway::usage(Assistant::CAPABILITY, 'zz-prov-a');
        $check($respuesta['ok'] && !array_key_exists('reasoning_effort', (array) ($captura['cuerpo'] ?? [])) && $respuesta['answer'] === 'zz-respuesta-del-asistente' && ($captura['url'] ?? null) === 'https://api.deepseek.com/chat/completions' && ($captura['cuerpo']['model'] ?? null) === 'zz-modelo-elegido' && $despues - $antes === 5, '7a reply() por chatProvider(): el registro elegido (DeepSeek), su modelo, la respuesta y 5 tokens anotados como «zz-prov-a»; sin reasoning_effort', (string) json_encode(['ok' => $respuesta['ok'], 'url' => $captura['url'] ?? null, 'tokens' => $despues - $antes, 'error' => $respuesta['error']]));
        //El coste del proveedor se suma en la fila del consumo (OpenRouter, usage.cost).
        set_config(AIGateway::CONFIG_NAME, ['enabled' => true, 'providers' => [['id' => 'zz-prov-a', 'type' => 'openrouter', 'alias' => 'Zz O']], 'capabilities' => [Assistant::CAPABILITY => ['enabled' => true, 'userTypes' => [], 'quota' => 0, 'provider' => 'zz-prov-a', 'model' => 'zz-modelo']]]);
        $cliente->body = (string) json_encode(['id' => 'zz', 'object' => 'chat.completion', 'created' => 1, 'model' => 'zz', 'choices' => [['index' => 0, 'message' => ['role' => 'assistant', 'content' => 'zz-con-coste'], 'finish_reason' => 'stop']], 'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5, 'total_tokens' => 15, 'cost' => 0.25]]);
        $costeDe = function () use ($database): float {
            $consulta = $database->prepare('SELECT COALESCE(SUM(reportedCostUsd), 0) FROM `' . AIUsageMapper::TABLE . '` WHERE capability = ? AND provider = ?');
            $consulta->execute([Assistant::CAPABILITY, 'zz-prov-a']);
            return (float) $consulta->fetchColumn();
        };
        $costeAntes = $costeDe();
        $dos = [Assistant::reply(new UserDataPackage($idRoot), 'zz')['ok'], Assistant::reply(new UserDataPackage($idRoot), 'zz')['ok']];
        $check($dos === [true, true] && abs($costeDe() - $costeAntes - 0.5) < 1e-9, '7c dos respuestas de OpenRouter con cost 0.25: la fila del consumo suma 0.5 en reportedCostUsd', (string) ($costeDe() - $costeAntes));
        //Groq con gpt-oss: el esfuerzo más bajo documentado (https://console.groq.com/docs/reasoning); con otro modelo, nada.
        $esfuerzos = [];
        foreach (['openai/gpt-oss-20b', 'llama-3.3-70b-versatile'] as $modeloGroq) {
            set_config(AIGateway::CONFIG_NAME, ['enabled' => true, 'providers' => [['id' => 'zz-prov-a', 'type' => 'groq', 'alias' => 'Zz G']], 'capabilities' => [Assistant::CAPABILITY => ['enabled' => true, 'userTypes' => [], 'quota' => 0, 'provider' => 'zz-prov-a', 'model' => $modeloGroq]]]);
            $captura->exchangeArray([]);
            $respuestaGroq = Assistant::reply(new UserDataPackage($idRoot), 'zz');
            $esfuerzos[$modeloGroq] = $respuestaGroq['ok'] ? (((array) ($captura['cuerpo'] ?? []))['reasoning_effort'] ?? '(sin)') : 'ERROR ' . (string) $respuestaGroq['error'];
        }
        $check($esfuerzos === ['openai/gpt-oss-20b' => 'low', 'llama-3.3-70b-versatile' => '(sin)'], '7b Groq: gpt-oss-20b sale con reasoning_effort «low»; llama-3.3-70b-versatile, sin él', (string) json_encode($esfuerzos));
        echoTerminal(' ');

        //─── 8 · El guardado de Configuración › IA ───────────────────────────────────────────────
        echoTerminal('[8] Guardar proveedores: la clave aparte, el id del servidor, los rechazos');
        $nombreAsistente = AICapabilityRegistry::get(Assistant::CAPABILITY)?->name() ?? Assistant::CAPABILITY;
        $claveOcho = 'zz-clave-secreta-8-' . bin2hex(random_bytes(4));
        $fila = fn(array $campos): array => array_merge(['id' => '', 'type' => 'openai', 'alias' => 'Zz Guardado', 'key' => '', 'quota' => '0', 'period' => 'accumulated', 'periodLength' => '1'], $campos);
        $formulario = fn(array $filas, array $asistente = []): array => SettingsController::aiAssignProviderIds([AIGateway::CONFIG_NAME => ['enabled' => '1', 'providers_sent' => '1', 'providers' => $filas, 'capabilities' => [Assistant::CAPABILITY => array_merge(['enabled' => '1', 'userTypes' => [''], 'quota' => '0', 'period' => 'accumulated', 'periodLength' => '1'], $asistente)]]]);
        $rechazo = function (array $entrada, $guardado = null): string {
            try {
                SettingsController::aiCenterFromInput($entrada[AIGateway::CONFIG_NAME], $guardado);
                return '';
            } catch (\Throwable $e) {
                return $e->getMessage();
            }
        };
        $entrada = $formulario([$fila(['key' => $claveOcho, 'id' => '', '_newId' => 'zz-elegido-por-el-cliente'])]);
        $centro = SettingsController::aiCenterFromInput($entrada[AIGateway::CONFIG_NAME], null);
        $nuevoId = (string) ($centro['providers'][0]['id'] ?? '');
        $claves = SettingsController::aiProviderKeysFromInput($entrada, $centro['providers'], []);
        $check(preg_match('/^p-[0-9a-f]{12}$/', $nuevoId) === 1 && mb_strpos((string) json_encode($centro), $claveOcho) === false && ($claves[$nuevoId] ?? null) === $claveOcho, '8a guardar: id del servidor (no el que mande el cliente), la clave en ai_provider_keys y NO en ai_center', $nuevoId);
        $segunda = $formulario([$fila(['id' => $nuevoId])]);
        $conserva = SettingsController::aiProviderKeysFromInput($segunda, SettingsController::aiCenterFromInput($segunda[AIGateway::CONFIG_NAME], $centro)['providers'], $claves);
        $quitar = $formulario([$fila(['id' => $nuevoId, 'clear_key' => '1'])]);
        $quitada = SettingsController::aiProviderKeysFromInput($quitar, SettingsController::aiCenterFromInput($quitar[AIGateway::CONFIG_NAME], $centro)['providers'], $claves);
        $check(($conserva[$nuevoId] ?? null) === $claveOcho && !isset($quitada[$nuevoId]), '8b un campo de clave vacío conserva la guardada; «Quitar la guardada» la borra');
        $repetido = $rechazo($formulario([$fila([]), $fila(['alias' => 'zz guardado'])]));
        $otro = $rechazo($formulario([$fila(['type' => AIProviderCatalog::TYPE_CUSTOM, 'alias' => 'Zz Otro', 'baseUrl' => 'http://otro.com'])]));
        $inventado = $rechazo($formulario([$fila(['id' => 'p-000000000000'])]), $centro);
        $check(mb_strpos($repetido, __('appConfig', 'Sobrenombre')) !== false && mb_strpos($otro, __('appConfig', 'Dirección base')) !== false && mb_strpos($otro, 'Zz Otro') !== false && $inventado !== '', '8c sobrenombre repetido (sin distinguir mayúsculas), un Personalizado con http://otro.com y un id que no está guardado: rechazados con su campo y su registro', "{$repetido} · {$otro}");
        $conAsistente = SettingsController::aiCenterFromInput($formulario([$fila(['id' => $nuevoId])], ['provider' => $nuevoId, 'model' => 'zz-modelo-8'])[AIGateway::CONFIG_NAME], $centro);
        $enUso = $rechazo($formulario([], ['provider' => $nuevoId, 'model' => 'zz-modelo-8']), $conAsistente);
        $cambiaYQuita = $rechazo($formulario([], ['provider' => '', 'model' => '']), $conAsistente);
        $check(mb_strpos($enUso, $nombreAsistente) !== false && $cambiaYQuita === '' && in_array('zz-modelo-8', $conAsistente['providers'][0]['models'] ?? [], true), '8d quitar un registro que usa el asistente: rechazado, nombrando la función; si en el mismo guardado se le quita, pasa; y el modelo elegido queda como sugerencia', $enUso);
        $conAsistente['enabled'] = true;
        $conAsistente['capabilities'][Assistant::CAPABILITY]['enabled'] = true;
        set_config(AIGateway::CONFIG_NAME, $conAsistente);
        set_config(AIProviderRegistry::KEYS_CONFIG_NAME, AIProviderRegistry::sealKeys($claves));
        $resuelto = AIGateway::chatProvider(Assistant::CAPABILITY, new UserDataPackage($idRoot));
        $check($resuelto->recordId() === $nuevoId && $resuelto->model() === 'zz-modelo-8', '8e la función con su proveedor y su modelo guardados resuelve por chatProvider()', $resuelto->recordId() . ' · ' . $resuelto->model());
        echoTerminal(' ');

        //─── 9 · «Probar» ────────────────────────────────────────────────────────────────────────
        echoTerminal('[9] «Probar»: una llamada mínima, sin consumo y sin la respuesta del proveedor');
        $claveNueve = 'zz-clave-secreta-9-' . bin2hex(random_bytes(4));
        $registroNueve = new AIProviderRecord('zz-prov-a', 'openai', 'Zz nueve', $claveNueve);
        AIGateway::setProviderHttpClientForTesting($cliente);
        $filasDe = function () use (&$database): int {
            $database = (new BaseModel())->getDatabase();
            $consulta = $database?->prepare('SELECT COUNT(*) FROM `' . AIUsageMapper::TABLE . '` WHERE provider = ?');
            $consulta?->execute(['zz-prov-a']);
            return $consulta !== null && $consulta !== false ? (int) $consulta->fetchColumn() : -1;
        };
        $antesNueve = $filasDe();
        $resultados = [];
        foreach ([200 => $respuestaConHerramientas, 401 => $cuerpoError, 404 => $cuerpoError, 0 => ''] as $estado => $cuerpo) {
            $cliente->status = $estado === 0 ? 599 : $estado;
            $cliente->body = $cuerpo;
            $cliente->falla = $estado === 0;
            $captura->exchangeArray([]);
            $resultados[$estado] = AIGateway::testProvider($registroNueve, 'zz-modelo-9') + ['peticion' => $captura->getArrayCopy()];
        }
        $cliente->falla = false;
        $esperados = [200 => [true, 'Funciona.'], 401 => [false, 'El proveedor rechazó la clave.'], 404 => [false, 'El proveedor no encuentra ese modelo (o la dirección no es la de un proveedor compatible).'], 0 => [false, 'Sin conexión con el proveedor, o se agotó el tiempo de espera.']];
        $malos = [];
        foreach ($esperados as $estado => [$ok, $texto]) {
            $r = $resultados[$estado];
            if ($r['ok'] !== $ok || $r['message'] !== __('ai', $texto) || mb_strpos($r['message'], $claveNueve) !== false || mb_strpos($r['message'], 'Incorrect') !== false) {
                $malos[] = "{$estado}: " . json_encode($r['message']);
            }
        }
        $peticion = $resultados[200]['peticion']['cuerpo'] ?? [];
        $check($malos === [] && ($peticion['max_completion_tokens'] ?? null) === 16 && !isset($peticion['tools']), '9a 200, 401, 404 y sin respuesta: «Funciona.» o su motivo, sin la clave ni el cuerpo; 16 tokens y sin herramientas', implode(' · ', $malos));
        $check($filasDe() === $antesNueve, '9b DISCRIMINANTE: «Probar» no anota consumo', "{$antesNueve} → {$filasDe()}");
        AIGateway::setProviderHttpClientForTesting(null);
        echoTerminal(' ');

        //─── 10 · La cuota del registro y el tope por tipo ───────────────────────────────────────
        echoTerminal('[10] La cuota del registro deniega aunque la de la función no; el tope con el nombre de cada tipo');
        //Con clave: desde JKM, denialReason() también mira el proveedor, y este caso mide solo la cuota.
        set_config(AIProviderRegistry::KEYS_CONFIG_NAME, AIProviderRegistry::sealKeys(['zz-prov-a' => 'zz-clave-a']));
        set_config(AIGateway::CONFIG_NAME, ['enabled' => true, 'providers' => [['id' => 'zz-prov-a', 'type' => 'deepseek', 'alias' => 'Zz con cuota', 'quota' => 10, 'period' => 'monthly', 'periodLength' => 1]], 'capabilities' => [Assistant::CAPABILITY => ['enabled' => true, 'userTypes' => [], 'quota' => 0, 'provider' => 'zz-prov-a', 'model' => 'zz-modelo']]]);
        $libre = AIGateway::denialReason(Assistant::CAPABILITY, new UserDataPackage($idRoot));
        AIGateway::recordUsage(Assistant::CAPABILITY, 'zz-prov-a', 10);
        $llena = AIGateway::denialReason(Assistant::CAPABILITY, new UserDataPackage($idRoot));
        $check($libre === null && AIGateway::providerUsage('zz-prov-a') === 10 && $llena === strReplaceTemplate(__('ai', 'Se alcanzó la cuota del proveedor «%alias%» en este periodo.'), ['%alias%' => 'Zz con cuota']), '10a DISCRIMINANTE: con 10 tokens gastados y cuota 10, la puerta deniega por el proveedor (la función no tiene cuota)', (string) $llena);
        $topes = [];
        foreach (['openai' => 'max_completion_tokens', 'mistral' => 'max_tokens'] as $tipo => $campo) {
            $cliente->status = 200;
            $cliente->body = $respuestaConHerramientas;
            $captura->exchangeArray([]);
            (new OpenAICompatibleChatProvider(new AIProviderRecord("zz-tope-{$tipo}", $tipo, "Zz {$tipo}", 'zz'), 'zz', $cliente))->chatWithTools([['role' => 'user', 'content' => 'zz']], [], 0.2, 77);
            $cuerpo = is_array($captura['cuerpo'] ?? null) ? $captura['cuerpo'] : [];
            $topes[$tipo] = ($cuerpo[$campo] ?? null) === 77 && count(array_intersect(['max_tokens', 'max_completion_tokens'], array_keys($cuerpo))) === 1;
        }
        $check($topes === ['openai' => true, 'mistral' => true], '10b openai manda max_completion_tokens y mistral max_tokens (y solo ese)', (string) json_encode($topes));
        echoTerminal(' ');

        //─── 12 · Sin proveedores fijos: la conversión ───────────────────────────────────────────
        echoTerminal('[12] La configuración de antes se lee como registros convertidos, con la MISMA selección que antes');
        $encendidas = ['enabled' => true, 'userTypes' => [], 'quota' => 0];
        $antiguas = function (): void {
            set_config('OpenAIApiKey', 'zz-clave-openai-12');
            set_config('MistralAIApiKey', '');
            set_config('GroqAPIKey', 'zz-clave-groq-12');
            set_config('translationAI', AI_OPENAI);
            set_config('modelOpenAI', 'zz-modelo-propio-12');
            set_config('modelMistral', '');
            set_config('modelGroq', '');
            set_config(AIProviderRegistry::KEYS_CONFIG_NAME, false);
        };
        $antiguas();
        $capacidades = [AIGateway::TRANSLATION_CAPABILITY, Assistant::CAPABILITY, AIGateway::SPEECH_CAPABILITY];
        $centroSinMarca = fn(array $extra = []): array => ['enabled' => true, 'capabilities' => array_merge(array_fill_keys($capacidades, $encendidas), $extra)];
        set_config(AIGateway::CONFIG_NAME, $centroSinMarca());
        //Groq lo fija el archivo de claves en esta prueba: su convertido sale «del archivo», con el nombre groq.
        ConfigOptions::swapFixedByFileForTesting(['GroqAPIKey']);
        $convertidos = AIProviderRegistry::all();
        $openai = $convertidos[AIProviderRegistry::CONVERTED_OPENAI] ?? null;
        $groq = $convertidos[AIProviderRegistry::CONVERTED_GROQ] ?? null;
        $check($openai !== null && $openai->keySource() === AIProviderRecord::KEY_SOURCE_STORED && $openai->keyFile() === null && $openai->key() === 'zz-clave-openai-12' && $openai->models() === ['zz-modelo-propio-12'] && $openai->alias() === 'OpenAI'
            && $groq !== null && $groq->keySource() === AIProviderRecord::KEY_SOURCE_FILE && $groq->keyFile() === 'groq' && $groq->models() === [AIGateway::LEGACY_DEFAULT_MODEL_GROQ]
            && !isset($convertidos[AIProviderRegistry::CONVERTED_MISTRAL]), '12a all() da los convertidos: openai «stored» con su clave y su modelo, groq «file» (lo fija el archivo) con el modelo por omisión, y sin Mistral (sin clave)', implode(', ', array_keys($convertidos)));
        //La tabla de ANTES, con las reglas de antes (las de resolveChatProvider() y speechProvider() hasta la v10.28.0), sobre
        //los mismos datos; los ids heredados, a su convertido.
        $tablaAntes = function () use ($capacidades): array {
            $tabla = [];
            foreach ($capacidades as $capacidad) {
                $esVoz = $capacidad === AIGateway::SPEECH_CAPABILITY;
                $id = $esVoz ? AIProviderRegistry::LEGACY_GROQ : match (get_config('translationAI')) {
                    AI_OPENAI => AIProviderRegistry::LEGACY_OPENAI,
                    AI_MISTRAL => AIProviderRegistry::LEGACY_MISTRAL,
                    default => null,
                };
                [$opcion, $omision] = match ($id) {
                    AIProviderRegistry::LEGACY_OPENAI => ['modelOpenAI', AIGateway::LEGACY_DEFAULT_MODEL_OPENAI],
                    AIProviderRegistry::LEGACY_MISTRAL => ['modelMistral', AIGateway::LEGACY_DEFAULT_MODEL_MISTRAL],
                    default => ['modelGroq', AIGateway::LEGACY_DEFAULT_MODEL_GROQ],
                };
                $configurado = get_config($opcion);
                $tabla[$capacidad] = [$id !== null ? AIProviderRegistry::LEGACY_ALIASES[$id] : null, is_string($configurado) && trim($configurado) !== '' ? $configurado : $omision];
            }
            return $tabla;
        };
        //La de AHORA, por la puerta de verdad (chatProvider() y speechProvider()), no por effectiveSelectionFor() solo.
        $tablaAhora = function () use ($capacidades, $idRoot): array {
            $tabla = [];
            foreach ($capacidades as $capacidad) {
                $proveedor = $capacidad === AIGateway::SPEECH_CAPABILITY ? AIGateway::speechProvider($capacidad, new UserDataPackage($idRoot)) : AIGateway::chatProvider($capacidad, new UserDataPackage($idRoot));
                $tabla[$capacidad] = [$proveedor->recordId(), $proveedor->model()];
            }
            return $tabla;
        };
        $antes = $tablaAntes();
        $ahora = $tablaAhora();
        $tablasMedidas['12b'] = ['antes' => $antes, 'ahora' => $ahora];
        $check($ahora === $antes, '12b la tabla capacidad → (registro, modelo) de translation, assistant y speech-to-text es IDÉNTICA a la de antes', (string) json_encode(['antes' => $antes, 'ahora' => $ahora]));
        set_config('translationAI', AI_MISTRAL);
        set_config('MistralAIApiKey', 'zz-clave-mistral-12');
        $antesMistral = $tablaAntes();
        $ahoraMistral = $tablaAhora();
        $tablasMedidas['12c'] = ['antes' => $antesMistral, 'ahora' => $ahoraMistral];
        $check($ahoraMistral === $antesMistral && $ahoraMistral[AIGateway::TRANSLATION_CAPABILITY] === [AIProviderRegistry::CONVERTED_MISTRAL, AIGateway::LEGACY_DEFAULT_MODEL_MISTRAL], '12c con Mistral en translationAI y sin modelMistral: idéntica, mistral con su modelo por omisión', (string) json_encode($ahoraMistral));
        $antiguas();
        set_config(AIGateway::CONFIG_NAME, $centroSinMarca([Assistant::CAPABILITY => $encendidas + ['provider' => AIProviderRegistry::LEGACY_OPENAI, 'model' => 'zz-modelo-alias']]));
        $alias = AIGateway::chatProvider(Assistant::CAPABILITY, new UserDataPackage($idRoot));
        $check($alias->recordId() === AIProviderRegistry::CONVERTED_OPENAI && $alias->model() === 'zz-modelo-alias' && AIGateway::usageProviderName($alias) === AI_OPENAI, '12d una función guardada con provider legacy-openai resuelve al convertido openai (y se anota como «OpenAI»)', $alias->recordId());
        //Con la marca: nada lee las opciones viejas.
        set_config(AIGateway::CONFIG_NAME, ['enabled' => true, AIProviderRegistry::CONVERTED_FLAG => true, 'capabilities' => array_fill_keys($capacidades, $encendidas)]);
        $sinElegido = __('ai', 'No hay ningún proveedor de inteligencia artificial elegido para esta función.');
        $motivos = [];
        foreach ($capacidades as $capacidad) {
            try {
                $capacidad === AIGateway::SPEECH_CAPABILITY ? AIGateway::speechProvider($capacidad, new UserDataPackage($idRoot)) : AIGateway::chatProvider($capacidad, new UserDataPackage($idRoot));
                $motivos[$capacidad] = null;
            } catch (AIUnavailableException $e) {
                $motivos[$capacidad] = $e->getMessage();
            }
        }
        set_config('OpenAIApiKey', 'zz-clave-cambiada-12');
        $check(array_unique($motivos) === [AIGateway::TRANSLATION_CAPABILITY => $sinElegido] && AIProviderRegistry::all() === [] && AIProviderRegistry::convertedPending() === [], '12e DISCRIMINANTE: con la marca y sin registro elegido, las tres funciones no están disponibles con su motivo, y cambiar OpenAIApiKey no crea nada', (string) json_encode($motivos, \JSON_UNESCAPED_UNICODE));
        ConfigOptions::swapFixedByFileForTesting($fijadasPorArchivo);
        echoTerminal(' ');

        //─── 13 · El origen de la clave ──────────────────────────────────────────────────────────
        echoTerminal('[13] El origen de la clave: el nombre atado al tipo, en el constructor, el guardado y «Probar»');
        //Sin red en toda la sección: un nombre que sí esté en el archivo de claves no puede salir a un proveedor de verdad.
        AIGateway::setProviderHttpClientForTesting($cliente);
        $cliente->status = 599;
        $cliente->falla = true;
        $pares = [
            [true, 'openai', 'openai'], [true, 'ai-openai-x', 'openai'], [true, 'ai-custom-local', AIProviderCatalog::TYPE_CUSTOM],
            [false, 'openai', AIProviderCatalog::TYPE_CUSTOM], [false, 'ai-openai-x', AIProviderCatalog::TYPE_CUSTOM], [false, 'mistral', 'openai'],
            [false, 'ai-x', AIProviderCatalog::TYPE_CUSTOM], [false, "ai-custom-a\n", AIProviderCatalog::TYPE_CUSTOM], [false, 'cronjob', AIProviderCatalog::TYPE_CUSTOM],
            [false, 'ai-custom-../x', AIProviderCatalog::TYPE_CUSTOM], [false, 'ai-zz-tipo-x', 'zz-tipo'],
        ];
        $construye = function (string $nombre, string $tipo): bool {
            try {
                new AIProviderRecord('zz-origen', $tipo, 'Zz origen', '', $tipo === AIProviderCatalog::TYPE_CUSTOM ? 'https://zz.example' : null, null, [], null, AIProviderRecord::KEY_SOURCE_FILE, $nombre);
                return true;
            } catch (\InvalidArgumentException $e) {
                return false;
            }
        };
        $malPar = [];
        foreach ($pares as [$esperado, $nombre, $tipo]) {
            if (AIProviderRecord::isValidKeyFile($nombre, $tipo) !== $esperado || $construye($nombre, $tipo) !== $esperado) {
                $malPar[] = json_encode([$nombre, $tipo]);
            }
        }
        $check($malPar === [] && AIProviderRegistry::fileKey('cronjob', AIProviderCatalog::TYPE_CUSTOM) === '' && AIProviderRegistry::fileKey('openai', AIProviderCatalog::TYPE_CUSTOM) === '', '13a isValidKeyFile(nombre, tipo) y el constructor: (openai, openai), (ai-openai-x, openai) y (ai-custom-local, custom) valen; (openai, custom), (ai-openai-x, custom), (mistral, openai), (ai-x, custom), un salto final, cronjob, ai-custom-../x y un tipo fuera del catálogo, no; fileKey() no lee un nombre que no vale para su tipo', implode(' · ', $malPar));
        $guardaOrigen = function (string $nombre, string $tipo = 'openai') use ($formulario, $fila): string {
            try {
                SettingsController::aiCenterFromInput($formulario([$fila(['alias' => 'Zz origen', 'type' => $tipo, 'baseUrl' => 'https://zz.example', 'keySource' => 'file', 'keyFile' => $nombre])])[AIGateway::CONFIG_NAME], null);
                return '';
            } catch (\Throwable $e) {
                return $e->getMessage();
            }
        };
        $nombreEnArchivo = __('appConfig', 'Nombre en el archivo');
        $check(mb_strpos($guardaOrigen('openai', AIProviderCatalog::TYPE_CUSTOM), $nombreEnArchivo) !== false && mb_strpos($guardaOrigen('cronjob', AIProviderCatalog::TYPE_CUSTOM), $nombreEnArchivo) !== false && mb_strpos($guardaOrigen('mistral'), $nombreEnArchivo) !== false && $guardaOrigen('openai') === '' && $guardaOrigen('ai-openai-marketing') === '' && $guardaOrigen('ai-custom-local', AIProviderCatalog::TYPE_CUSTOM) === '', '13b el guardado rechaza un «Personalizado» con keyFile=openai (y cronjob, y mistral en un OpenAI) con «Nombre en el archivo»; acepta openai y ai-openai-… en OpenAI y ai-custom-… en el «Personalizado»');
        $peticion = fn(array $cuerpo): RequestRoute => (new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-prueba-proveedores'), new Headers(), [], [], (new StreamFactory())->createStream('')))->withParsedBody($cuerpo)->withAttribute('route', null);
        $probar = function (array $cuerpo) use ($peticion): array {
            $respuesta = (new SettingsController())->aiProviderTest($peticion($cuerpo), new ResponseRoute());
            $json = json_decode((string) $respuesta->getBody(), true);
            return is_array($json) ? $json : [];
        };
        //El ataque de la auditoría (M1 a): un «Personalizado» sin guardar, a una dirección ajena, con la llave de OpenAI del servidor.
        $captura->exchangeArray([]);
        $ataque = $probar(['type' => AIProviderCatalog::TYPE_CUSTOM, 'baseUrl' => 'https://zz-ajeno.example/v1', 'model' => 'zz', 'keySource' => 'file', 'keyFile' => 'openai']);
        $sinGuardar = $probar(['type' => 'openai', 'model' => 'zz', 'keySource' => 'file', 'keyFile' => 'openai']);
        $check(($ataque['message'] ?? null) === __('appConfig', 'Guarda el proveedor para probarlo con la clave del archivo.') && ($sinGuardar['message'] ?? null) === ($ataque['message'] ?? null) && $captura->getArrayCopy() === [], '13c «Probar» sin id con origen «file» (también el «Personalizado» con keyFile=openai a otra dirección): «guarda antes», y el cliente simulado NO recibe ninguna petición', (string) json_encode([$ataque['message'] ?? null, array_keys($captura->getArrayCopy())], \JSON_UNESCAPED_UNICODE));
        $sinArchivo = 'ai-openai-zz-no-existe-' . bin2hex(random_bytes(3));
        set_config(AIGateway::CONFIG_NAME, ['enabled' => true, AIProviderRegistry::CONVERTED_FLAG => true, 'providers' => [['id' => 'zz-prov-f', 'type' => 'openai', 'alias' => 'Zz archivo', 'keySource' => 'file', 'keyFile' => $sinArchivo]], 'capabilities' => [Assistant::CAPABILITY => $encendidas + ['provider' => 'zz-prov-f', 'model' => 'zz']]]);
        set_config(AIProviderRegistry::KEYS_CONFIG_NAME, AIProviderRegistry::sealKeys(['zz-prov-f' => 'zz-clave-escrita-que-no-cuenta']));
        $captura->exchangeArray([]);
        $conId = $probar(['id' => 'zz-prov-f', 'model' => 'zz', 'keySource' => 'file', 'keyFile' => 'openai']);
        $check(($conId['message'] ?? null) === __('appConfig', 'No está en el archivo de claves.') && $captura->getArrayCopy() === [], '13d «Probar» con id: el keyFile de la petición (openai) se ignora; manda el suyo, que no está en el archivo, y no sale nada', (string) ($conId['message'] ?? ''));
        $registroF = AIProviderRegistry::get('zz-prov-f');
        $check($registroF !== null && !$registroF->hasKey() && (function () use ($idRoot, $sinClave): bool {
            try {
                AIGateway::chatProvider(Assistant::CAPABILITY, new UserDataPackage($idRoot));
                return false;
            } catch (AIUnavailableException $e) {
                return $e->getMessage() === $sinClave;
            }
        })(), '13e un registro «file» con un nombre que no está en el archivo: hasKey() false (la clave escrita no cuenta) y «no tiene clave»');
        $conArchivo = $formulario([$fila(['id' => '', '_newId' => 'p-0000000000aa', 'alias' => 'Zz archivo', 'keySource' => 'file', 'keyFile' => 'ai-openai-zz', 'key' => 'zz-no-se-guarda'])]);
        $centroArchivo = SettingsController::aiCenterFromInput($conArchivo[AIGateway::CONFIG_NAME], null);
        $idArchivo = (string) ($centroArchivo['providers'][0]['id'] ?? '');
        $clavesArchivo = SettingsController::aiProviderKeysFromInput($conArchivo, $centroArchivo['providers'], [$idArchivo => 'zz-guardada-antes']);
        $cliente->falla = false;
        AIGateway::setProviderHttpClientForTesting(null);
        $check($idArchivo !== '' && !isset($clavesArchivo[$idArchivo]) && ($centroArchivo['providers'][0]['keySource'] ?? null) === 'file' && ($centroArchivo['providers'][0]['keyFile'] ?? null) === 'ai-openai-zz', '13f guardar un registro «file»: guarda su origen y su nombre, y NO deja clave en ai_provider_keys (la que tuviera sale)', (string) json_encode($clavesArchivo));
        //M1 b: cambiar el destino de un registro guardado no se lleva su clave escrita, salvo que llegue una nueva.
        $antesDestino = ['zz-prov-d' => ['id' => 'zz-prov-d', 'type' => AIProviderCatalog::TYPE_CUSTOM, 'alias' => 'Zz destino', 'baseUrl' => 'https://zz-a.example/v1', 'keyHeader' => null]];
        $claveDestino = ['zz-prov-d' => 'zz-clave-destino'];
        $filaDestino = fn(array $campos): array => $fila(array_merge(['id' => 'zz-prov-d', 'type' => AIProviderCatalog::TYPE_CUSTOM, 'alias' => 'Zz destino', 'baseUrl' => 'https://zz-a.example/v1', 'keyHeader' => ''], $campos));
        $clavesTras = function (array $filaEnviada) use ($formulario, $antesDestino, $claveDestino): array {
            $entrada = $formulario([$filaEnviada]);
            $centro = SettingsController::aiCenterFromInput($entrada[AIGateway::CONFIG_NAME], ['providers' => array_values($antesDestino)]);
            return SettingsController::aiProviderKeysFromInput($entrada, $centro['providers'], $claveDestino, $antesDestino);
        };
        $otraDireccion = $clavesTras($filaDestino(['baseUrl' => 'https://zz-b.example/v1']));
        $otraCabecera = $clavesTras($filaDestino(['keyHeader' => 'X-Zz-Otra']));
        $otroTipo = $clavesTras($filaDestino(['type' => 'openai', 'baseUrl' => '']));
        $soloNombre = $clavesTras($filaDestino(['alias' => 'Zz destino renombrado']));
        $conNueva = $clavesTras($filaDestino(['baseUrl' => 'https://zz-b.example/v1', 'key' => 'zz-clave-nueva']));
        $check($otraDireccion === [] && $otraCabecera === [] && $otroTipo === [] && ($soloNombre['zz-prov-d'] ?? null) === 'zz-clave-destino' && ($conNueva['zz-prov-d'] ?? null) === 'zz-clave-nueva' && SettingsController::aiProviderDestinationChanged($antesDestino['zz-prov-d'], ['type' => AIProviderCatalog::TYPE_CUSTOM, 'baseUrl' => 'https://zz-a.example/v1/', 'keyHeader' => '']) === false, '13g cambiar la dirección, la cabecera o el tipo de un registro guardado descarta su clave escrita; cambiar solo el sobrenombre la conserva; con una clave nueva en la misma petición, esa', (string) json_encode([$otraDireccion, $soloNombre, $conNueva]));
        echoTerminal(' ');

        //─── 14 · «Actualizar modelos» ───────────────────────────────────────────────────────────
        echoTerminal('[14] La lista de modelos: respaldo, la del proveedor en caché, y sus fallos sin tocar la caché');
        AIGateway::setProviderHttpClientForTesting($cliente);
        $registroM = new AIProviderRecord('zz-prov-m', 'openai', 'Zz modelos', 'zz-clave-m');
        $respaldo = AIGateway::providerModels($registroM, AIProviderCatalog::CAPABILITY_CHAT);
        $check($respaldo['source'] === 'fallback' && $respaldo['models'] === AIProviderCatalog::TYPES['openai']['fallbackModels']['chat'] && $respaldo['fetchedAt'] === null && AIGateway::providerModels($registroM, AIProviderCatalog::CAPABILITY_SPEECH)['models'] === AIProviderCatalog::TYPES['openai']['fallbackModels']['speech'], '14a sin caché: la lista de respaldo de su tipo y capacidad');
        $largo = str_repeat('z', AIGateway::MODEL_ID_MAX_LENGTH + 1);
        $cliente->status = 200;
        $cliente->falla = false;
        $cliente->body = (string) json_encode(['object' => 'list', 'data' => [['id' => 'zz-b'], ['id' => 'zz-a'], ['id' => 'zz-a'], ['id' => ''], ['id' => $largo], ['x' => 1], 'zz-suelto']]);
        $captura->exchangeArray([]);
        $bien = AIGateway::refreshProviderModels($registroM);
        $peticionM = $captura->getArrayCopy();
        $cacheBien = AIGateway::modelsCache();
        $check($bien['ok'] && $bien['models'] === ['zz-a', 'zz-b'] && ($cacheBien['zz-prov-m']['models'] ?? null) === ['zz-a', 'zz-b'] && AIGateway::providerModels($registroM, AIProviderCatalog::CAPABILITY_CHAT)['source'] === 'provider'
            && ($peticionM['url'] ?? null) === 'https://api.openai.com/v1/models' && ($peticionM['metodo'] ?? null) === 'GET' && ($peticionM['headers']['authorization'][0] ?? null) === 'Bearer zz-clave-m', '14b una lista válida: GET a /models con su clave; se filtra (vacíos, repetidos, de más de 200), se ordena y se guarda; después manda la del proveedor', (string) json_encode([$bien['models'], $peticionM['url'] ?? null]));
        $marcador = 'zz-marcador-del-cuerpo-' . bin2hex(random_bytes(4));
        $cliente->status = 401;
        $cliente->body = (string) json_encode(['error' => ['message' => "Incorrect API key {$marcador}"]]);
        $rechazada = AIGateway::refreshProviderModels($registroM);
        $cliente->status = 200;
        $cliente->falla = true;
        $agotado = AIGateway::refreshProviderModels($registroM);
        $cliente->falla = false;
        $cliente->status = 500;
        $quinientos = AIGateway::refreshProviderModels($registroM);
        $sinClaveM = AIGateway::refreshProviderModels(new AIProviderRecord('zz-prov-m', 'openai', 'Zz modelos', ''));
        $check(!$rechazada['ok'] && $rechazada['message'] === __('ai', 'El proveedor rechazó la clave.') && mb_strpos((string) json_encode($rechazada), $marcador) === false
            && !$agotado['ok'] && $agotado['message'] === __('ai', 'Sin conexión con el proveedor, o se agotó el tiempo de espera.')
            && !$quinientos['ok'] && str_contains($quinientos['message'], 'HTTP 500') && !$sinClaveM['ok'] && $sinClaveM['message'] === __('ai', 'Sin clave no se puede pedir la lista.')
            && AIGateway::modelsCache() === $cacheBien, '14c 401 (sin el cuerpo: el marcador no sale), tiempo agotado, 500 y sin clave: su motivo, y la caché como estaba', (string) json_encode([$rechazada['message'], $agotado['message'], $quinientos['message']], \JSON_UNESCAPED_UNICODE));
        $registroPropio = new AIProviderRecord('zz-prov-propio', AIProviderCatalog::TYPE_CUSTOM, 'Zz propio', 'zz-clave-propia', 'https://zz-propio.example/v1/', 'X-Zz-Key');
        $cliente->status = 200;
        $cliente->body = (string) json_encode(['data' => [['id' => 'zz-local']]]);
        $captura->exchangeArray([]);
        $propio = AIGateway::refreshProviderModels($registroPropio);
        $vistaPropia = $captura->getArrayCopy();
        $check($propio['ok'] && ($vistaPropia['url'] ?? null) === 'https://zz-propio.example/v1/models' && ($vistaPropia['headers']['x-zz-key'][0] ?? null) === 'zz-clave-propia' && !isset($vistaPropia['headers']['authorization']), '14d un «Personalizado» con keyHeader propio: su dirección + /models, la clave en X-Zz-Key y sin Authorization', (string) json_encode(array_keys((array) ($vistaPropia['headers'] ?? []))));
        $check(AIGateway::pruneModelsCache(['zz-prov-propio']) && array_keys(AIGateway::modelsCache()) === ['zz-prov-propio'], '14e al guardar, la caché pierde las entradas de los ids que ya no están');
        $check(count(array_filter(AIProviderCatalog::TYPES, fn(array $tipo): bool => count($tipo['fallbackModels']['chat']) > 8 || count($tipo['fallbackModels']['speech']) > 8 || \DateTimeImmutable::createFromFormat('!Y-m-d', $tipo['fallbackMeasured']) === false)) === 0, "14f cada lista de respaldo, con 8 como mucho por capacidad y su fecha (hoy todos los tipos documentan /models: «sin modelsPath» no se puede provocar)");
        //I2: solo ids con caracteres de modelo; B2: un cuerpo de más de 2 MB no se lee.
        $cliente->status = 200;
        $cliente->falla = false;
        $cliente->body = (string) json_encode(['data' => [['id' => 'zz-bueno'], ['id' => 'zz con espacio'], ['id' => "zz\x01control"], ['id' => 'zz<b>'], ['id' => str_repeat('z', 129)], ['id' => 'org/zz-modelo:1@v+2']]]);
        $filtrada = AIGateway::refreshProviderModels($registroM);
        $check($filtrada['ok'] && $filtrada['models'] === ['org/zz-modelo:1@v+2', 'zz-bueno'], '14g los ids con espacio, un carácter de control, < o más de 128 se descartan; los de modelo (/ : @ +) quedan', (string) json_encode($filtrada['models']));
        $cliente->body = str_repeat(' ', AIGateway::MODELS_MAX_BYTES + 1);
        $grande = AIGateway::refreshProviderModels($registroM);
        $cliente->body = str_repeat(' ', AIGateway::MODELS_MAX_BYTES - 20) . '{"data":[]}';
        $justo = AIGateway::refreshProviderModels($registroM);
        $check(!$grande['ok'] && $grande['message'] === __('ai', 'La lista de modelos del proveedor es demasiado grande.') && $justo['ok'], '14h DISCRIMINANTE: 2 MB + 1 → «demasiado grande»; justo por debajo, se lee', (string) json_encode([$grande['message'], $justo['ok']], \JSON_UNESCAPED_UNICODE));
        $cliente->body = (string) json_encode(['data' => [['id' => 'zz-a']]]);
        AIGateway::refreshProviderModels($registroM);
        $cacheAntesB2 = AIGateway::modelsCache();
        $cliente->body = str_repeat(' ', AIGateway::MODELS_MAX_BYTES + 1);
        AIGateway::refreshProviderModels($registroM);
        $check(AIGateway::modelsCache() === $cacheAntesB2, '14i tras el cuerpo de más de 2 MB, la caché como estaba');
        //B1: una clave con un salto en medio hace lanzar a psr7 con la cabecera en el mensaje; ni el mensaje ni el registro la llevan.
        $marcadorB1 = 'zz-marcador-b1-' . bin2hex(random_bytes(4));
        $registroErrores = rtrim((string) (defined('LOG_ERRORS_PATH') ? LOG_ERRORS_PATH : ''), '/') . '/' . \PiecesPHP\Core\CustomErrorsHandlers\GenericHandler::FILE_ENTRIES;
        $tamAntes = is_file($registroErrores) ? (int) filesize($registroErrores) : 0;
        $conSalto = AIGateway::refreshProviderModels(new AIProviderRecord('zz-prov-b1', 'openai', 'Zz b1', "{$marcadorB1}\nresto"));
        clearstatcache();
        $nuevo = is_file($registroErrores) ? (string) file_get_contents($registroErrores, false, null, $tamAntes) : '';
        $vozConSalto = '';
        $audioB1 = (string) tempnam(sys_get_temp_dir(), 'zz-audio-');
        if (file_put_contents($audioB1, 'zz') === false) {
            throw new \RuntimeException('No se pudo escribir el audio temporal de la prueba.');
        }
        try {
            (new \PiecesPHP\AI\Providers\OpenAICompatibleSpeechProvider(new AIProviderRecord('zz-prov-b1', 'openai', 'Zz b1', "{$marcadorB1}\nresto"), 'zz', $cliente))->transcribe($audioB1);
        } catch (\RuntimeException $e) {
            $vozConSalto = $e->getMessage() . ' ' . $e->getTraceAsString() . ' ' . (string) $e->getPrevious()?->getMessage();
        } finally {
            //RETORNO-IGNORADO: un temporal de la prueba en el directorio del sistema; si no se borra, no afecta a nada.
            unlink($audioB1);
        }
        $check(!$conSalto['ok'] && $conSalto['message'] === __('ai', 'No se pudo pedir la lista de modelos al proveedor.') && mb_strpos((string) json_encode($conSalto), $marcadorB1) === false && $nuevo !== '' && mb_strpos($nuevo, $marcadorB1) === false && $vozConSalto !== '' && mb_strpos($vozConSalto, $marcadorB1) === false, '14j una clave con «\n» en medio (con un marcador zz-): la lista y la voz dan su mensaje genérico, y ni la respuesta, ni la excepción, ni lo nuevo del registro de errores llevan la clave', 'nuevo en el registro: ' . strlen($nuevo) . ' bytes');
        AIGateway::setProviderHttpClientForTesting(null);
        echoTerminal(' ');

        //─── 15 · En una prueba no hay red ───────────────────────────────────────────────────────
        echoTerminal('[15] Sin cliente simulado, ningún proveedor sale a la red: falla cerrado, sin petición');
        //La fábrica del cliente «real» se cambia por el simulado de fallo: si la guarda faltara, la petición acabaría aquí
        //(y se contaría), nunca en un proveedor.
        $llamadasReales = 0;
        $fabricaPrevia = AIProviderCatalog::swapRealHttpClientFactoryForTesting(function (array $opciones) use ($cliente, &$llamadasReales): \Psr\Http\Client\ClientInterface {
            $llamadasReales++;
            $cliente->falla = true;
            return $cliente;
        });
        try {
            AIGateway::setProviderHttpClientForTesting(null);
            $registroRed = new AIProviderRecord('zz-prov-red', 'openai', 'Zz red', 'zz-clave-red');
            $audioRed = (string) tempnam(sys_get_temp_dir(), 'zz-audio-');
            if (file_put_contents($audioRed, 'zz') === false) {
                throw new \RuntimeException('No se pudo escribir el audio temporal de la prueba.');
            }
            $cerrados = [];
            $intentos = [
                'probar' => fn() => AIGateway::testProvider($registroRed, 'zz'),
                'modelos' => fn() => AIGateway::refreshProviderModels($registroRed),
                'chat' => fn() => (new OpenAICompatibleChatProvider($registroRed, 'zz'))->chatWithTools([['role' => 'user', 'content' => 'zz']]),
                'voz' => fn() => AIProviderCatalog::makeSpeechProvider($registroRed, 'zz')->transcribe($audioRed),
            ];
            foreach ($intentos as $quien => $intento) {
                try {
                    $intento();
                    $cerrados[$quien] = 'sin excepción';
                } catch (\LogicException $e) {
                    $cerrados[$quien] = $e instanceof \InvalidArgumentException ? get_class($e) . ': ' . $e->getMessage() : 'cerrado';
                } catch (\Throwable $e) {
                    $cerrados[$quien] = get_class($e);
                }
            }
            $check(AIProviderCatalog::inTestRun() && array_unique($cerrados) === ['probar' => 'cerrado'] && $llamadasReales === 0, '15a en la suite (inTestRun()), «Probar», «Actualizar modelos», el chat y la voz sin cliente simulado lanzan \LogicException y no piden el cliente real', (string) json_encode(['cerrados' => $cerrados, 'clienteReal' => $llamadasReales], \JSON_UNESCAPED_UNICODE));
        } finally {
            AIProviderCatalog::swapRealHttpClientFactoryForTesting($fabricaPrevia);
            $cliente->falla = false;
            if (isset($audioRed) && is_file($audioRed)) {
                //RETORNO-IGNORADO: un temporal de la prueba en el directorio del sistema; si no se borra, no afecta a nada.
                unlink($audioRed);
            }
        }
        echoTerminal(' ');

        //─── 16 · La cuota del registro de un convertido y el largo del modelo ───────────────────
        echoTerminal('[16] La cuota de un convertido cuenta por su id (M2), y un modelo de más de 128 se rechaza');
        //Una capacidad propia y un periodo propio del registro: las filas que se escriben son solo de la prueba.
        $registroCapacidades = AICapabilityRegistry::all();
        AICapabilityRegistry::register(new \PiecesPHP\AI\AICapability('zz-m2', fn(): string => 'zz m2', \PiecesPHP\AI\AICapability::PROVIDER_CHAT));
        try {
            set_config(AIProviderRegistry::KEYS_CONFIG_NAME, AIProviderRegistry::sealKeys(['openai' => 'zz-clave-m2']));
            set_config(AIGateway::CONFIG_NAME, ['enabled' => true, AIProviderRegistry::CONVERTED_FLAG => true, 'providers' => [['id' => 'openai', 'type' => 'openai', 'alias' => 'OpenAI', 'quota' => 10, 'period' => 'every-days', 'periodLength' => 99999, 'periodAnchor' => '2001-02-03 04:05:06']], 'capabilities' => ['zz-m2' => ['enabled' => true, 'userTypes' => [], 'quota' => 0, 'provider' => 'openai', 'model' => 'zz']]]);
            $libreM2 = AIGateway::denialReason('zz-m2', new UserDataPackage($idRoot));
            //Como lo anotan los llamadores: la serie con el nombre de antes («OpenAI») y el registro por su id.
            AIGateway::recordUsage('zz-m2', 'zz-serie-OpenAI', 11, 0, 0, 'zz', 'openai');
            $llenaM2 = AIGateway::denialReason('zz-m2', new UserDataPackage($idRoot));
            $check($libreM2 === null && AIGateway::providerUsage('openai') === 11 && $llenaM2 === strReplaceTemplate(__('ai', 'Se alcanzó la cuota del proveedor «%alias%» en este periodo.'), ['%alias%' => 'OpenAI']), '16a DISCRIMINANTE: el convertido openai con cuota de registro 10 y 11 anotados con la serie «OpenAI» y su id: denialReason() lo deniega por la cuota del registro', (string) $llenaM2);
            $largo = str_repeat('m', AIGateway::MODEL_ID_MAX_LENGTH + 1);
            $conLargo = $rechazo($formulario([], ['provider' => 'zz-prov-a', 'model' => $largo]), ['providers' => [['id' => 'zz-prov-a', 'type' => 'deepseek', 'alias' => 'Zz A']]]);
            $conJusto = $rechazo($formulario([$fila(['id' => 'zz-prov-a', 'type' => 'deepseek', 'alias' => 'Zz A'])], ['provider' => 'zz-prov-a', 'model' => str_repeat('m', AIGateway::MODEL_ID_MAX_LENGTH)]), ['providers' => [['id' => 'zz-prov-a', 'type' => 'deepseek', 'alias' => 'Zz A']]]);
            $probarLargo = $probar(['type' => 'openai', 'model' => $largo, 'key' => 'zz']);
            $check(mb_strpos($conLargo, __('appConfig', 'Modelo')) !== false && $conJusto === '' && ($probarLargo['message'] ?? null) === __('appConfig', 'El modelo no vale: como mucho 128 caracteres.'), '16b un modelo de 129 se rechaza al guardar («Modelo») y en «Probar»; uno de 128, no', (string) json_encode([$conLargo, $conJusto, $probarLargo['message'] ?? null], \JSON_UNESCAPED_UNICODE));
        } finally {
            AICapabilityRegistry::swapForTesting($registroCapacidades);
            $database = (new BaseModel())->getDatabase();
            //RETORNO-IGNORADO: limpieza de la prueba; lo comprueba z0c contando lo que queda.
            $database?->prepare('DELETE FROM `' . AIUsageMapper::TABLE . '` WHERE capability = ? OR (capability = ? AND provider = ? AND periodStart = ?)')->execute(['zz-m2', AIGateway::PROVIDER_USAGE_ROW, 'openai', '2001-02-03 04:05:06']);
        }
        echoTerminal(' ');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        AIGateway::setProviderHttpClientForTesting(null);
        ConfigOptions::swapFixedByFileForTesting($fijadasPorArchivo);
        foreach ($previos as $opcion => $valor) {
            set_config($opcion, $valor);
        }
        $database = (new BaseModel())->getDatabase();
        //La fila de la caché de modelos, como estaba (o ninguna).
        $database?->prepare('DELETE FROM `pcsphp_app_config` WHERE BINARY `name` = ?')->execute([AIGateway::MODELS_CONFIG_NAME]);
        if ($filaModelos !== null) {
            $columnas = implode('`, `', array_keys($filaModelos));
            $marcas = implode(', ', array_fill(0, count($filaModelos), '?'));
            $database?->prepare("INSERT INTO `pcsphp_app_config` (`{$columnas}`) VALUES ({$marcas})")->execute(array_values($filaModelos));
        }
        //Las de la función y la propia del registro (AIGateway::PROVIDER_USAGE_ROW).
        $database?->prepare('DELETE FROM `' . AIUsageMapper::TABLE . '` WHERE provider = ?')->execute(['zz-prov-a']);
        GenericContentPseudoMapper::setContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED, $acumulado);
    }
    $quedan = $database?->prepare('SELECT COUNT(*) FROM `' . AIUsageMapper::TABLE . '` WHERE provider = ?');
    $quedan?->execute(['zz-prov-a']);
    $repuestas = count(array_filter($opciones, fn(string $opcion): bool => get_config($opcion) !== $previos[$opcion])) === 0;
    foreach ($tablasMedidas as $caso => $tablas) {
        echoTerminal("   medido {$caso}: " . json_encode($tablas));
    }
    $check($leerFilaModelos() === $filaModelos && ConfigOptions::fixedByFile() === $fijadasPorArchivo, 'z0b la fila de ai_provider_models y las fijadas por el archivo, como estaban');
    $quedanM2 = $database?->prepare('SELECT COUNT(*) FROM `' . AIUsageMapper::TABLE . '` WHERE capability = ? OR (capability = ? AND provider = ? AND periodStart = ?)');
    $quedanM2?->execute(['zz-m2', AIGateway::PROVIDER_USAGE_ROW, 'openai', '2001-02-03 04:05:06']);
    $check($quedanM2 !== null && $quedanM2 !== false && (int) $quedanM2->fetchColumn() === 0, 'z0c no queda consumo de la prueba de la cuota de los convertidos');
    $check($quedan !== null && $quedan !== false && (int) $quedan->fetchColumn() === 0 && $repuestas && $filas() === $filasAntes && GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED) == $acumulado, 'z0 sin consumo zz-prov-a; ai_center, ai_provider_keys y las claves heredadas como estaban, en memoria y en la base; el acumulado igual');

    //─── 11 · Por HTTP ──────────────────────────────────────────────────────────────────────────────
    echoTerminal('[11] Por HTTP: la pantalla, y «Probar» con y sin el permiso de la pantalla');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if ($check($base !== '' && $database !== null, '11 hay una base HTTP y conexión a la base', $base)) {
        $camino = fn(string $url): string => '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
        $http = function (string $path, array $cabeceras, ?array $formulario = null) use ($base): array {
            $handle = \Terminal\TestRun::curlInit();
            $opciones = [
                CURLOPT_URL => $base . '/' . ltrim($path, '/'),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_HTTPHEADER => $cabeceras,
            ];
            if ($formulario !== null) {
                $opciones[CURLOPT_POST] = true;
                $opciones[CURLOPT_POSTFIELDS] = http_build_query($formulario);
            }
            \Terminal\TestRun::curlSetoptArray($handle, $opciones);
            $respuesta = curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            $tamCabecera = (int) curl_getinfo($handle, CURLINFO_HEADER_SIZE);
            //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
            $respuesta = is_string($respuesta) ? $respuesta : '';
            $cuerpo = substr($respuesta, $tamCabecera);
            return ['status' => $status, 'body' => $cuerpo, 'json' => json_decode($cuerpo, true)];
        };
        $prefijo = 'zz-agente-prov-' . bin2hex(random_bytes(3));
        $clave = bin2hex(random_bytes(12)) . 'Aa1!';
        $usuarios = [];
        $tablaUsuarios = UsersModel::TABLE;
        $tablaIntentos = LoginAttemptsModel::TABLE;
        $maxIntento = (int) $database->query("SELECT COALESCE(MAX(id), 0) FROM `{$tablaIntentos}`")->fetchColumn();
        $maxLog = (int) $database->query('SELECT COALESCE(MAX(id), 0) FROM `' . LogsMapper::TABLE . '`')->fetchColumn();
        try {
            $tokens = [];
            foreach (['admin' => UsersModel::TYPE_USER_ADMIN_GRAL, 'general' => UsersModel::TYPE_USER_GENERAL] as $quien => $tipo) {
                $u = new UsersModel();
                $u->username = "{$prefijo}-{$quien}";
                $u->email = "{$prefijo}-{$quien}@example.com";
                $u->password = password_hash($clave, \PASSWORD_DEFAULT);
                $u->firstname = 'Zz';
                $u->secondname = '';
                $u->firstLastname = 'Agente';
                $u->secondLastname = '';
                $u->type = $tipo;
                $u->status = UsersModel::STATUS_USER_ACTIVE;
                $u->failedAttempts = 0;
                $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
                $u->createdAt = new \DateTime();
                $u->modifiedAt = $u->createdAt;
                $u->save();
                $usuarios[] = (int) $u->id;
                $acceso = $http($camino((string) UsersController::routeName('login-request')), ['X-Requested-With: XMLHttpRequest'], ['username' => "{$prefijo}-{$quien}", 'password' => $clave, 'twoFactor' => '']);
                $tokens[$quien] = is_string($acceso['json']['token'] ?? null) ? SessionToken::tokenName() . ': ' . $acceso['json']['token'] : '';
            }
            $pantalla = $camino((string) SettingsController::routeName('integrations-ai'));
            $probar = $camino((string) SettingsController::routeName('integrations-ai-provider-test'));
            $check($tokens['admin'] !== '' && $tokens['general'] !== '', '11a CANARIO: los dos usuarios de la prueba entran');
            $vista = $http($pantalla, [$tokens['admin']]);
            $check($vista['status'] === 200 && mb_strpos($vista['body'], 'data-ai-provider-add') !== false && mb_strpos($vista['body'], 'data-test-url="') !== false, '11b la pantalla, al administrador general: 200, con «Añadir proveedor» y la ruta de «Probar»', "HTTP {$vista['status']}");
            $direccionMala = ['type' => AIProviderCatalog::TYPE_CUSTOM, 'baseUrl' => 'http://otro.com/v1', 'key' => 'zz-clave', 'model' => 'zz-modelo'];
            $conPermiso = $http($probar, [$tokens['admin'], 'X-Requested-With: XMLHttpRequest'], $direccionMala);
            $check($conPermiso['status'] === 200 && ($conPermiso['json']['ok'] ?? null) === false && ($conPermiso['json']['message'] ?? null) === __('appConfig', 'La dirección base no vale: https://, o http://localhost o http://127.0.0.1.'), '11c «Probar» un Personalizado con http://otro.com: {ok: false} con el motivo de la dirección, sin salir a la red', "HTTP {$conPermiso['status']} · " . mb_substr($conPermiso['body'], 0, 160));
            $sinPermiso = $http($probar, [$tokens['general'], 'X-Requested-With: XMLHttpRequest'], $direccionMala);
            $check(in_array($sinPermiso['status'], [302, 403, 404], true) && ($sinPermiso['json']['ok'] ?? null) !== false, '11d «Probar» con un usuario general (sin el permiso de la pantalla): denegado', "HTTP {$sinPermiso['status']}");
        } catch (\Throwable $e) {
            $check(false, 'la parte HTTP corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
        } finally {
            try {
                if ($usuarios !== []) {
                    $marcas = implode(',', array_fill(0, count($usuarios), '?'));
                    $database->prepare('DELETE FROM `' . UserSessions::TABLE . "` WHERE userID IN ({$marcas})")->execute($usuarios);
                    $database->prepare("DELETE FROM `{$tablaIntentos}` WHERE id > ? AND (userID IN ({$marcas}) OR usernameAttempt LIKE ?)")->execute(array_merge([$maxIntento], $usuarios, ["{$prefijo}%"]));
                    $database->prepare('DELETE FROM `' . LogsMapper::TABLE . "` WHERE id > ? AND createdBy IN ({$marcas})")->execute(array_merge([$maxLog], $usuarios));
                    //Crear el usuario abre su aprobación: sin esto quedaría una fila huérfana por corrida.
                    $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . "` WHERE referenceTable = ? AND referenceValue IN ({$marcas})")->execute(array_merge([$tablaUsuarios], array_map('strval', $usuarios)));
                    $database->prepare("DELETE FROM `{$tablaUsuarios}` WHERE id IN ({$marcas})")->execute($usuarios);
                }
            } catch (\Throwable $e) {
                $check(false, 'z1 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
            }
            $consulta = $database->prepare("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE ?");
            $consulta->execute(["{$prefijo}%"]);
            $check((int) $consulta->fetchColumn() === 0, 'z2 no quedan los usuarios de la prueba');
        }
    }
    echoTerminal('');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Proveedores de IA extensibles (ADR 0077, ronda 1), sin red: el catálogo medido, la petición y la respuesta de cada tipo con un cliente HTTP simulado (dirección, cabecera de la clave, max_tokens, tool_calls y usage), un 401 y un 429 sin la clave ni el cuerpo, los registros heredados y personalizados, la clave que no se ve, la puerta (función → registro → modelo, sus motivos y denialReason antes) y el asistente por la puerta. Anota consumo zz-prov-a y lo borra.')->setEffects([CliActions::EFFECT_DATABASE])->register();
