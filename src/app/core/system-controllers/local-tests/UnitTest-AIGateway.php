<?php

//Centro de mando de la IA (ADR 0074): registro, chatHandler() sin llamar a ningún proveedor y recordUsage().
//Escribe filas zz- en pcsphp_ai_usage y las borra; CONTENT_TOKENS_USED se repone como estaba.
//@areas nucleo

use API\Adapters\MistralHandlerAdapter;
use API\Adapters\OpenAIHandlerAdapter;
use PiecesPHP\AI\AICapability;
use PiecesPHP\AI\AICapabilityRegistry;
use PiecesPHP\AI\AIGateway;
use PiecesPHP\AI\Mappers\AIUsageMapper;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/ai-gateway', function ($args) {

    echoTerminal("\e[33m[TEST:AIGateway] el registro de capacidades y la puerta única de la IA\e[39m");
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
    $lanza = function (callable $accion): ?string {
        try {
            $accion();
        } catch (\InvalidArgumentException $e) {
            return $e->getMessage();
        }
        return null;
    };
    $capacidad = fn(string $key, string $tipo = AICapability::PROVIDER_CHAT): AICapability => new AICapability($key, fn(): string => "zz {$key}", $tipo);

    $registroPrevio = AICapabilityRegistry::swapForTesting();
    $opciones = ['translationAI', 'OpenAIApiKey', 'MistralAIApiKey'];
    $opcionesPrevias = array_combine($opciones, array_map(fn(string $nombre): mixed => get_config($nombre), $opciones));
    $acumuladoPrevio = GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED);
    $database = null;

    try {
        //─── a · El registro ────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] AICapabilityRegistry');
        AICapabilityRegistry::swapForTesting([]);
        AICapabilityRegistry::register($capacidad('zz-uno'));
        AICapabilityRegistry::register($capacidad('zz-dos', AICapability::PROVIDER_SPEECH));
        $check(array_keys(AICapabilityRegistry::all()) === ['zz-uno', 'zz-dos'], 'a1 all() en el orden de registro');
        $check($lanza(fn() => AICapabilityRegistry::register($capacidad('zz-uno'))) !== null, 'a2 una key repetida lanza');
        AICapabilityRegistry::replace(new AICapability('zz-uno', fn(): string => 'zz sustituida', AICapability::PROVIDER_CHAT));
        $check(array_keys(AICapabilityRegistry::all()) === ['zz-uno', 'zz-dos'] && AICapabilityRegistry::get('zz-uno')?->name() === 'zz sustituida', 'a3 replace() la sustituye en su sitio');
        $check($lanza(fn() => AICapabilityRegistry::replace($capacidad('zz-no-esta'))) !== null, 'a4 replace() de una que no está lanza');
        $primera = AICapabilityRegistry::remove('zz-dos');
        $segunda = AICapabilityRegistry::remove('zz-dos');
        $check($primera && !$segunda && AICapabilityRegistry::get('zz-dos') === null, 'a5 remove(): true la primera vez, false después');
        $check($lanza(fn() => $capacidad('Zz Mal')) !== null && $lanza(fn() => new AICapability('zz-tipo', fn(): string => '', 'zz')) !== null, 'a6 una key o un tipo de proveedor que no valen lanzan');
        $check($lanza(fn() => new AICapability('zz-cuota', fn(): string => '', AICapability::PROVIDER_CHAT, true, [], -1)) !== null && $lanza(fn() => new AICapability('zz-periodo', fn(): string => '', AICapability::PROVIDER_CHAT, true, [], 0, 'zz')) !== null, 'a7 una cuota negativa o un periodo desconocido lanzan');
        echoTerminal(' ');

        //─── b · chatHandler(), sin llamar al proveedor ─────────────────────────────────────────────────
        echoTerminal('[b] AIGateway::chatHandler()');
        AICapabilityRegistry::register($capacidad('zz-chat'));
        AICapabilityRegistry::register($capacidad('zz-voz', AICapability::PROVIDER_SPEECH));
        set_config('OpenAIApiKey', 'zz-sin-clave');
        set_config('MistralAIApiKey', 'zz-sin-clave');
        set_config('translationAI', null);
        $check(AIGateway::chatHandler('zz-chat') === null, 'b1 sin proveedor configurado: null');
        set_config('translationAI', AI_OPENAI);
        $check(AIGateway::chatHandler('zz-chat') instanceof OpenAIHandlerAdapter, 'b2 con OpenAI: su adaptador');
        set_config('translationAI', AI_MISTRAL);
        $check(AIGateway::chatHandler('zz-chat') instanceof MistralHandlerAdapter, 'b3 con Mistral: su adaptador');
        $check($lanza(fn() => AIGateway::chatHandler('zz-no-registrada')) !== null, 'b4 una capacidad no registrada lanza');
        $check($lanza(fn() => AIGateway::chatHandler('zz-voz')) !== null, 'b5 una capacidad de voz lanza');
        $check(isset($registroPrevio['translation']) && $registroPrevio['translation']->providerType() === AICapability::PROVIDER_CHAT, 'b6 la traducción está registrada, como capacidad de chat');
        echoTerminal(' ');

        //─── c · recordUsage(), en la tabla y en el acumulado ───────────────────────────────────────────
        echoTerminal('[c] AIGateway::recordUsage()');
        $database = (new BaseModel())->getDatabase();
        $existe = $database?->prepare('SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = DATABASE() AND `table_name` = ?');
        //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o hace su trabajo o lanza. Lo que cuenta es el COUNT que sigue.
        $existe?->execute([AIUsageMapper::TABLE]);
        $hayTabla = $existe !== null && $existe !== false && ((int) $existe->fetchColumn()) > 0;
        if ($check($database !== null && $hayTabla, 'c0 la tabla del consumo existe', AIUsageMapper::TABLE . ': aplica ' . AIUsageMapper::UPDATE_FILE)) {
            $fila = function (string $capability) use ($database): ?array {
                $consulta = $database->prepare('SELECT tokens, requests FROM `' . AIUsageMapper::TABLE . '` WHERE capability = ? AND provider = ? AND periodStart = ?');
                //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o hace su trabajo o lanza. Lo que cuenta es el fetch que sigue.
                $consulta->execute([$capability, 'zz-proveedor', AIGateway::periodStart()]);
                $leida = $consulta->fetch(\PDO::FETCH_ASSOC);
                return is_array($leida) ? ['tokens' => (int) $leida['tokens'], 'requests' => (int) $leida['requests']] : null;
            };
            AIGateway::recordUsage('zz-chat', 'zz-proveedor', 7);
            AIGateway::recordUsage('zz-chat', 'zz-proveedor', 5);
            $check($fila('zz-chat') === ['tokens' => 12, 'requests' => 2], 'c1 dos llamadas suman tokens y peticiones en la misma fila', (string) json_encode($fila('zz-chat')));
            AIGateway::recordUsage('zz-voz', 'zz-proveedor', 3);
            $check($fila('zz-voz') === ['tokens' => 3, 'requests' => 1] && $fila('zz-chat') === ['tokens' => 12, 'requests' => 2], 'c2 otra capacidad, otra fila', (string) json_encode([$fila('zz-voz'), $fila('zz-chat')]));
            $acumulado = (array) GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED);
            $check(($acumulado['zz-proveedor'] ?? null) === 15, 'c3 el acumulado de siempre también sube, desde una clave que no existía y sin aviso', (string) json_encode($acumulado['zz-proveedor'] ?? null));
            $check($lanza(fn() => AIGateway::recordUsage('zz-no-registrada', 'zz-proveedor', 1)) !== null, 'c4 una capacidad no registrada lanza');
        }

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        AICapabilityRegistry::swapForTesting($registroPrevio);
        foreach ($opcionesPrevias as $nombre => $valor) {
            set_config($nombre, $valor);
        }
        GenericContentPseudoMapper::setContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED, $acumuladoPrevio);
        try {
            $database = (new BaseModel())->getDatabase();
            $borrar = $database?->prepare('DELETE FROM `' . AIUsageMapper::TABLE . '` WHERE capability LIKE ?');
            //RETORNO-IGNORADO: limpieza de la prueba; si la tabla no existe no hay nada que borrar.
            $borrar?->execute(['zz-%']);
        } catch (\Throwable) {
        }
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El centro de mando de la IA, ronda 1 (ADR 0074): el registro (repetida, replace, remove, orden), chatHandler() (sin proveedor, OpenAI, Mistral, desconocida, de voz; sin llamar a ninguno) y recordUsage() (misma fila, otra fila, el acumulado viejo sin aviso).')->setEffects([CliActions::EFFECT_DATABASE])->register();
