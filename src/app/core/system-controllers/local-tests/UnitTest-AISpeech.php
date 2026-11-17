<?php

//La voz a texto por el centro de IA y el consumo con entrada, salida y modelo (ADR 0077), con un cliente HTTP simulado.
//Anota consumo «zz-prov-sp» y lo borra; crea un audio de prueba en el temporal y lo borra; las opciones, en memoria.
//@areas nucleo
//@areas src/app/classes/API/

use PiecesPHP\AI\AIGateway;
use PiecesPHP\AI\AIUnavailableException;
use PiecesPHP\AI\Mappers\AIUsageMapper;
use PiecesPHP\AI\Providers\AIProviderRecord;
use PiecesPHP\AI\Providers\AIProviderRegistry;
use PiecesPHP\AI\Providers\OpenAICompatibleSpeechProvider;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\ConfigHelpers\ConfigOptions;
use PiecesPHP\Core\Crypto\EncryptedDataRegistry;
use PiecesPHP\Core\Crypto\Sealed;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;

CliActions::make('unit-tests:core/ai-speech', function ($args) {

    echoTerminal("\e[33m[TEST:AISpeech] La voz a texto por el centro de IA y el consumo por modelo\e[39m");
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
        public int $status = 200;
        public string $body = '{"text":"zz hola"}';

        public function __construct(private \ArrayObject $captura)
        {
        }

        public function sendRequest(\Psr\Http\Message\RequestInterface $request): \Psr\Http\Message\ResponseInterface
        {
            $this->captura['url'] = (string) $request->getUri();
            $this->captura['headers'] = array_change_key_case($request->getHeaders(), \CASE_LOWER);
            $this->captura['cuerpo'] = (string) $request->getBody();
            return new \GuzzleHttp\Psr7\Response($this->status, ['Content-Type' => 'application/json'], $this->body);
        }
    };

    $modelo = UsersModel::model();
    $modelo->resetAll();
    $modelo->select(['id'])->where(new WhereSegment([WhereItem::isEqual('type', UsersModel::TYPE_USER_ROOT), WhereItem::isEqual('status', UsersModel::STATUS_USER_ACTIVE)]))->execute(false, 1, 1);
    $idRoot = (int) (((array) (((array) $modelo->result())[0] ?? []))['id'] ?? 0);
    //tempnam() da false si no puede: queda '' y c0 lo dice.
    $audio = (string) tempnam(sys_get_temp_dir(), 'zz-audio-');
    $escrito = $audio !== '' ? file_put_contents($audio, 'zz-no-es-audio-de-verdad') : false;
    if (!$check($idRoot > 0 && $audio !== '' && $escrito !== false, 'c0 hay un root y un audio de prueba en el temporal', "root {$idRoot}")) {
        return ['success' => false, 'message' => "{$passed}/" . ($passed + $failed)];
    }
    $root = new UserDataPackage($idRoot);

    $opciones = [AIGateway::CONFIG_NAME, AIProviderRegistry::KEYS_CONFIG_NAME, 'GroqAPIKey', 'modelGroq'];
    $previos = array_combine($opciones, array_map(fn(string $opcion) => get_config($opcion), $opciones));
    $acumulado = GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED);
    $encendida = ['enabled' => true, 'userTypes' => array_keys(UsersModel::TYPES_USERS), 'quota' => 0];

    try {
        //─── 1 · La petición y la respuesta ──────────────────────────────────────────────────────
        echoTerminal('[1] La petición multipart y lo que vuelve');
        $clave = 'zz-clave-secreta-voz-' . bin2hex(random_bytes(4));
        $registro = new AIProviderRecord('zz-prov-sp', 'groq', 'Zz voz', $clave);
        $peticionCorrecta = function (array $vista, string $idioma) use ($clave): bool {
            $cuerpo = (string) ($vista['cuerpo'] ?? '');
            return ($vista['url'] ?? null) === 'https://api.groq.com/openai/v1/audio/transcriptions'
                && (($vista['headers'] ?? [])['authorization'][0] ?? null) === "Bearer {$clave}"
                && str_starts_with((string) ((($vista['headers'] ?? [])['content-type'][0]) ?? ''), 'multipart/form-data; boundary=')
                && preg_match('/name="model"\r\n(?:[^\r\n]+\r\n)*\r\nzz-whisper\r\n/', $cuerpo) === 1
                && preg_match('/name="language"\r\n(?:[^\r\n]+\r\n)*\r\n' . preg_quote($idioma, '/') . '\r\n/', $cuerpo) === 1
                && preg_match('/name="response_format"\r\n(?:[^\r\n]+\r\n)*\r\njson\r\n/', $cuerpo) === 1
                && str_contains($cuerpo, 'zz-no-es-audio-de-verdad');
        };
        $cliente->body = (string) json_encode(['text' => 'zz hola', 'usage' => ['type' => 'tokens', 'input_tokens' => 14, 'output_tokens' => 45, 'total_tokens' => 59]]);
        $captura->exchangeArray([]);
        $porTokens = (new OpenAICompatibleSpeechProvider($registro, 'zz-whisper', $cliente))->transcribe($audio, 'es');
        $vista = $captura->getArrayCopy();
        $check($peticionCorrecta($vista, 'es') && $porTokens === ['text' => 'zz hola', 'usage' => 59, 'inputTokens' => 14, 'outputTokens' => 45, 'seconds' => null], '1a POST {base}/audio/transcriptions con Bearer, multipart (file, model, language, response_format json) y el usage por tokens analizado', (string) json_encode($porTokens));
        $otroIdioma = $vista;
        $otroIdioma['cuerpo'] = str_replace("\r\nes\r\n", "\r\nen\r\n", (string) $vista['cuerpo']);
        $check(!$peticionCorrecta($otroIdioma, 'es') && !$peticionCorrecta(['url' => 'https://zz.example/audio/transcriptions'] + $vista, 'es'), '1b DISCRIMINANTE: otro idioma u otra dirección no pasan');
        $cliente->body = (string) json_encode(['text' => 'zz solo texto', 'x_groq' => ['id' => 'zz']]);
        $soloTexto = (new OpenAICompatibleSpeechProvider($registro, 'zz-whisper', $cliente))->transcribe($audio);
        $cliente->body = (string) json_encode(['text' => 'zz', 'usage' => ['type' => 'duration', 'seconds' => 3.5]]);
        $porDuracion = (new OpenAICompatibleSpeechProvider($registro, 'zz-whisper', $cliente))->transcribe($audio);
        $check($soloTexto === ['text' => 'zz solo texto', 'usage' => 0, 'inputTokens' => 0, 'outputTokens' => 0, 'seconds' => null] && $porDuracion['seconds'] === 3.5 && $porDuracion['usage'] === 0 && !str_contains((string) $captura['cuerpo'], 'name="language"'), '1c la de Groq (solo text): 0 y null; la de duración: seconds; sin idioma, no se manda language', (string) json_encode([$soloTexto, $porDuracion]));
        echoTerminal(' ');

        //─── 2 · Un error ────────────────────────────────────────────────────────────────────────
        echoTerminal('[2] Un 401: \RuntimeException sin la clave ni el cuerpo');
        $cliente->status = 401;
        $cliente->body = (string) json_encode(['error' => ['message' => "Invalid API Key: {$clave}"]]);
        $texto = '';
        try {
            (new OpenAICompatibleSpeechProvider($registro, 'zz-whisper', $cliente))->transcribe($audio, 'es');
        } catch (\RuntimeException $e) {
            $texto = $e->getMessage() . ' ' . $e->getTraceAsString() . ' código ' . $e->getCode();
        }
        $cliente->status = 200;
        $check($texto !== '' && str_contains($texto, 'HTTP 401') && str_contains($texto, 'código 401') && !str_contains($texto, $clave) && !str_contains($texto, 'Invalid API Key'), '2a lanza con el HTTP como código, sin la clave ni el texto del proveedor', mb_substr($texto, 0, 120));
        echoTerminal(' ');

        //─── 3 · speechProvider() ────────────────────────────────────────────────────────────────
        echoTerminal('[3] La puerta de la voz: su registro, el heredado y el motivo');
        set_config('GroqAPIKey', 'zz-clave-groq');
        set_config('modelGroq', 'zz-whisper-heredado');
        set_config(AIProviderRegistry::KEYS_CONFIG_NAME, AIProviderRegistry::sealKeys(['zz-prov-sp' => 'zz-clave-sp', 'zz-prov-chat' => 'zz-clave-chat']));
        $centro = fn(array $voz): array => ['enabled' => true, 'providers' => [['id' => 'zz-prov-sp', 'type' => 'openai', 'alias' => 'Zz voz'], ['id' => 'zz-prov-chat', 'type' => 'deepseek', 'alias' => 'Zz chat']], 'capabilities' => [AIGateway::SPEECH_CAPABILITY => $encendida + $voz]];
        set_config(AIGateway::CONFIG_NAME, $centro(['provider' => 'zz-prov-sp', 'model' => 'zz-whisper-elegido']));
        $elegido = AIGateway::speechProvider(AIGateway::SPEECH_CAPABILITY, $root);
        set_config(AIGateway::CONFIG_NAME, $centro([]));
        $heredado = AIGateway::speechProvider(AIGateway::SPEECH_CAPABILITY, $root);
        $check($elegido->recordId() === 'zz-prov-sp' && $elegido->model() === 'zz-whisper-elegido' && $heredado->recordId() === AIProviderRegistry::CONVERTED_GROQ && $heredado->model() === 'zz-whisper-heredado' && $heredado instanceof OpenAICompatibleSpeechProvider && $heredado->timeoutSeconds() === 120, '3a con provider y model, ese registro; sin ellos, el convertido groq con modelGroq (sin la conversión escrita); 120 s', $elegido->recordId() . ' · ' . $heredado->recordId());
        set_config(AIGateway::CONFIG_NAME, $centro(['provider' => 'zz-prov-chat', 'model' => 'zz']));
        $motivo = '';
        try {
            AIGateway::speechProvider(AIGateway::SPEECH_CAPABILITY, $root);
        } catch (AIUnavailableException $e) {
            $motivo = $e->getMessage();
        }
        $check($motivo === __('ai', 'El proveedor de inteligencia artificial elegido no sabe hacer lo que necesita esta función.'), '3b DISCRIMINANTE: un registro sin voz (DeepSeek) para la voz: «no sabe hacer»', $motivo);
        $check(AIGateway::settingsFor(AIGateway::SPEECH_CAPABILITY)['enabled'] === true && (new \ReflectionMethod(\PiecesPHP\AI\AICapabilityRegistry::class, 'get'))->invoke(null, AIGateway::SPEECH_CAPABILITY)?->enabledByDefault() === false, '3c la capacidad «Voz a texto» existe y viene apagada de fábrica');
        echoTerminal(' ');

        //─── 4 · GroqAPIKey sellada ──────────────────────────────────────────────────────────────
        echoTerminal('[4] La clave de Groq se sella como las de OpenAI y Mistral');
        $sellada = ConfigOptions::valueToStore('GroqAPIKey', 'zz-clave-a-sellar');
        $check(is_string($sellada) && Sealed::isSealed($sellada) && Sealed::open($sellada, Sealed::PURPOSE_CONFIG_SECRET) === 'zz-clave-a-sellar' && isset(EncryptedDataRegistry::all()['config-secret-groq']) && count(EncryptedDataRegistry::purposes()) === 7, '4a se guarda sellada (config-secret), está en el registro de lo cifrado para la rotación, y los propósitos siguen siendo 7');
        echoTerminal(' ');

        //─── 5 · El consumo por modelo ───────────────────────────────────────────────────────────
        echoTerminal('[5] El consumo con entrada, salida y modelo');
        set_config(AIGateway::CONFIG_NAME, $centro([]));
        $antes = AIGateway::usage(AIGateway::SPEECH_CAPABILITY, 'zz-prov-sp');
        AIGateway::recordUsage(AIGateway::SPEECH_CAPABILITY, 'zz-prov-sp', 10, 4, 6, 'zz-modelo-a');
        AIGateway::recordUsage(AIGateway::SPEECH_CAPABILITY, 'zz-prov-sp', 20, 5, 15, 'zz-modelo-b');
        AIGateway::recordUsage(AIGateway::SPEECH_CAPABILITY, 'zz-prov-sp', 1, 1, 0, 'zz-modelo-a');
        AIGateway::recordUsage(AIGateway::SPEECH_CAPABILITY, 'zz-prov-sp', 3);
        $database = (new BaseModel())->getDatabase();
        $consulta = $database?->prepare('SELECT model, tokens, inputTokens, outputTokens, requests FROM `' . AIUsageMapper::TABLE . '` WHERE capability = ? AND provider = ? ORDER BY model');
        $consulta?->execute([AIGateway::SPEECH_CAPABILITY, 'zz-prov-sp']);
        $filas = $consulta !== null && $consulta !== false ? $consulta->fetchAll(\PDO::FETCH_ASSOC) : [];
        $esperadas = [['model' => '', 'tokens' => 3, 'inputTokens' => 0, 'outputTokens' => 0, 'requests' => 1], ['model' => 'zz-modelo-a', 'tokens' => 11, 'inputTokens' => 5, 'outputTokens' => 6, 'requests' => 2], ['model' => 'zz-modelo-b', 'tokens' => 20, 'inputTokens' => 5, 'outputTokens' => 15, 'requests' => 1]];
        $check(array_map(fn(array $f): array => array_map(fn($v) => is_numeric($v) ? (int) $v : $v, $f), $filas) === $esperadas, '5a una fila por modelo, sumando entrada, salida, total y peticiones; la de tres argumentos va con el modelo vacío', (string) json_encode($filas));
        $check(AIGateway::usage(AIGateway::SPEECH_CAPABILITY, 'zz-prov-sp') - $antes === 34 && AIGateway::providerUsage('zz-prov-sp') === 34, '5b DISCRIMINANTE: usage() y providerUsage() suman todos los modelos (34), como siempre', (string) (AIGateway::usage(AIGateway::SPEECH_CAPABILITY, 'zz-prov-sp') - $antes));
        echoTerminal(' ');

        //─── 6 · La actualización aplicada ───────────────────────────────────────────────────────
        echoTerminal('[6] La tabla, con la actualización del 2026-11-14 aplicada');
        $columnas = $database?->query("SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY ORDINAL_POSITION) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . AIUsageMapper::TABLE . "'")->fetchColumn();
        $indice = $database?->query("SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . AIUsageMapper::TABLE . "' AND INDEX_NAME = 'capabilityProviderModelPeriod'")->fetchColumn();
        $check($columnas === 'id,capability,provider,model,periodStart,tokens,inputTokens,outputTokens,requests,createdAt,updatedAt' && $indice === 'capability,provider,model,periodStart', '6a las columnas y el índice único (capability, provider, model, periodStart) de ' . AIUsageMapper::UPDATE_FILE_BY_MODEL, "{$columnas} · {$indice}");
        echoTerminal(' ');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        foreach ($previos as $opcion => $valor) {
            set_config($opcion, $valor);
        }
        $database = (new BaseModel())->getDatabase();
        $database?->prepare('DELETE FROM `' . AIUsageMapper::TABLE . '` WHERE provider = ?')->execute(['zz-prov-sp']);
        GenericContentPseudoMapper::setContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED, $acumulado);
        $borrado = !is_file($audio) || unlink($audio);
    }
    $database = (new BaseModel())->getDatabase();
    $quedan = $database?->prepare('SELECT COUNT(*) FROM `' . AIUsageMapper::TABLE . '` WHERE provider = ?');
    $quedan?->execute(['zz-prov-sp']);
    $check($quedan !== null && $quedan !== false && (int) $quedan->fetchColumn() === 0 && $borrado && !is_file($audio) && GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED) == $acumulado, 'z0 sin consumo zz-prov-sp, sin el audio de prueba y el acumulado como estaba');

    echoTerminal('');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La voz a texto por el centro de IA (ADR 0077) y el consumo por modelo, sin red: la petición multipart de /audio/transcriptions y su respuesta (por tokens, por duración y solo texto), un 401 sin la clave, speechProvider() con su registro, el convertido groq y el motivo de un registro sin voz, GroqAPIKey sellada, recordUsage con entrada, salida y modelo en filas por modelo, usage() con la suma de siempre y la actualización de la tabla aplicada. Anota consumo zz-prov-sp y lo borra.')->setEffects([CliActions::EFFECT_DATABASE])->register();
