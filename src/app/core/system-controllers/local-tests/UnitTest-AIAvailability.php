<?php

//La disponibilidad de la IA en pantalla (ADR 0077, punto 4): availability() oculta, en su límite o disponible, con la misma
//resolución que denialReason(), el fin del periodo y el botón «Traducir». Sin red; repone lo que toca y borra su consumo zz-.
//@areas nucleo

use PiecesPHP\AI\AICapabilityRegistry;
use PiecesPHP\AI\AIGateway;
use PiecesPHP\AI\Mappers\AIUsageMapper;
use PiecesPHP\AI\Providers\AIProviderRegistry;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\ConfigOptions;
use PiecesPHP\Mcp\Assistant;
use PiecesPHP\Mcp\McpRoutes;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;
use Publications\PublicationsLang;

CliActions::make('unit-tests:core/ai-availability', function ($args) {

    echoTerminal("\e[33m[TEST:AIAvailability] La IA que no está disponible no se ve, y la que llegó a su límite lo dice\e[39m");
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
    //La del asistente solo se registra con MCP encendido; las demás pruebas no la necesitan.
    if (!McpRoutes::ENABLE) {
        echoTerminal("   \e[33m[OMITIDA]\e[39m c0 no pide la capacidad del asistente: esta instalación tiene MCP apagado.");
    }
    if (!$check($database !== null && $idRoot > 0 && AICapabilityRegistry::get(AIGateway::TRANSLATION_CAPABILITY) !== null && (!McpRoutes::ENABLE || AICapabilityRegistry::get(Assistant::CAPABILITY) !== null) && AICapabilityRegistry::get(AIGateway::SPEECH_CAPABILITY) !== null, 'c0 CANARIO: conexión, un root y las capacidades registradas (la del asistente, con MCP encendido)')) {
        return ['success' => false, 'message' => "{$passed}/" . ($passed + $failed)];
    }
    $root = new UserDataPackage($idRoot);
    //Un usuario general sin guardar: userTypeOf() solo lee su tipo.
    $general = new UsersModel();
    $general->type = UsersModel::TYPE_USER_GENERAL;

    $opciones = [AIGateway::CONFIG_NAME, AIProviderRegistry::KEYS_CONFIG_NAME, AIGateway::MODELS_CONFIG_NAME, 'current_user', 'pcsphp_current_user_stored', 'translationAI', 'OpenAIApiKey', 'modelOpenAI'];
    $previos = array_combine($opciones, array_map(fn(string $opcion) => get_config($opcion), $opciones));
    $fijadas = ConfigOptions::fixedByFile();
    $acumulado = GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED);
    $anclaRegistro = '2001-02-03 04:05:06';
    $borrarConsumo = function () use ($anclaRegistro): void {
        $database = (new BaseModel())->getDatabase();
        //RETORNO-IGNORADO: limpieza de la prueba; lo comprueba z0 contando lo que queda.
        $database?->prepare('DELETE FROM `' . AIUsageMapper::TABLE . '` WHERE provider = ? OR (capability = ? AND provider = ? AND periodStart = ?)')->execute(['zz-disp', AIGateway::PROVIDER_USAGE_ROW, 'zz-d', $anclaRegistro]);
    };

    try {
        set_config('current_user', null);
        set_config('pcsphp_current_user_stored', null);
        ConfigOptions::swapFixedByFileForTesting([]);
        $borrarConsumo();
        set_config(AIProviderRegistry::KEYS_CONFIG_NAME, AIProviderRegistry::sealKeys(['zz-d' => 'zz-clave-d']));
        $encendida = ['enabled' => true, 'userTypes' => [], 'quota' => 0, 'period' => 'accumulated'];
        $proveedores = [['id' => 'zz-d', 'type' => 'deepseek', 'alias' => 'Zz disponible'], ['id' => 'zz-sin', 'type' => 'deepseek', 'alias' => 'Zz sin clave']];
        $centro = function (array $traduccion = [], array $extra = []) use ($encendida, $proveedores): array {
            return array_replace_recursive(['enabled' => true, AIProviderRegistry::CONVERTED_FLAG => true, 'providers' => $proveedores, 'capabilities' => [
                AIGateway::TRANSLATION_CAPABILITY => array_merge($encendida, ['provider' => 'zz-d', 'model' => 'zz-modelo'], $traduccion),
            ]], $extra);
        };

        //─── 1 · La tabla de casos ───────────────────────────────────────────────────────────────
        echoTerminal('[1] Cada caso: su state, y denialReason() es null si y solo si state es available');
        $usoTraduccion = AIGateway::usage(AIGateway::TRANSLATION_CAPABILITY);
        $casos = [
            'IA general apagada' => [fn() => $centro([], ['enabled' => false]), AIGateway::TRANSLATION_CAPABILITY, $root, AIGateway::AVAILABILITY_HIDDEN],
            'función apagada' => [fn() => $centro(['enabled' => false]), AIGateway::TRANSLATION_CAPABILITY, $root, AIGateway::AVAILABILITY_HIDDEN],
            'tipo sin permiso' => [fn() => $centro(), AIGateway::TRANSLATION_CAPABILITY, $general, AIGateway::AVAILABILITY_HIDDEN],
            'sin usuario' => [fn() => $centro(), AIGateway::TRANSLATION_CAPABILITY, null, AIGateway::AVAILABILITY_HIDDEN],
            'sin proveedor' => [fn() => $centro(['provider' => null, 'model' => null]), AIGateway::TRANSLATION_CAPABILITY, $root, AIGateway::AVAILABILITY_HIDDEN],
            'registro inexistente' => [fn() => $centro(['provider' => 'zz-no-existe']), AIGateway::TRANSLATION_CAPABILITY, $root, AIGateway::AVAILABILITY_HIDDEN],
            'sin clave' => [fn() => $centro(['provider' => 'zz-sin']), AIGateway::TRANSLATION_CAPABILITY, $root, AIGateway::AVAILABILITY_HIDDEN],
            //Ningún tipo del catálogo sabe chat sin herramientas: el «no sabe hacer» se mide con la voz, que DeepSeek no sabe.
            'tipo que no sabe lo que pide (voz con DeepSeek)' => [fn() => $centro([], ['capabilities' => [AIGateway::SPEECH_CAPABILITY => array_merge($encendida, ['provider' => 'zz-d', 'model' => 'zz'])]]), AIGateway::SPEECH_CAPABILITY, $root, AIGateway::AVAILABILITY_HIDDEN],
            'sin modelo' => [fn() => $centro(['model' => null]), AIGateway::TRANSLATION_CAPABILITY, $root, AIGateway::AVAILABILITY_HIDDEN],
            'cuota de la función, mensual' => [fn() => $centro(['quota' => 5, 'period' => 'monthly']), AIGateway::TRANSLATION_CAPABILITY, $root, AIGateway::AVAILABILITY_LIMIT, 'mensual'],
            'cuota de la función, acumulada' => [fn() => $centro(['quota' => 5, 'period' => 'accumulated']), AIGateway::TRANSLATION_CAPABILITY, $root, AIGateway::AVAILABILITY_LIMIT, 'acumulada'],
            'cuota del registro' => [fn() => $centro([], ['providers' => [['id' => 'zz-d', 'type' => 'deepseek', 'alias' => 'Zz disponible', 'quota' => 3, 'period' => 'every-days', 'periodLength' => 99999, 'periodAnchor' => $anclaRegistro]]]), AIGateway::TRANSLATION_CAPABILITY, $root, AIGateway::AVAILABILITY_LIMIT, 'registro'],
            'disponible' => [fn() => $centro(), AIGateway::TRANSLATION_CAPABILITY, $root, AIGateway::AVAILABILITY_AVAILABLE],
        ];
        $tabla = [];
        $malos = [];
        foreach ($casos as $nombre => $caso) {
            [$hacer, $capacidad, $usuario, $esperado] = $caso;
            $config = $hacer();
            if (($caso[4] ?? null) === 'mensual' || ($caso[4] ?? null) === 'acumulada') {
                //La cuota, sobre lo que la traducción ya llevara en ese periodo: se gasta lo que falta, con la serie zz-disp.
                $config['capabilities'][AIGateway::TRANSLATION_CAPABILITY]['quota'] = 5 + AIGateway::usage(AIGateway::TRANSLATION_CAPABILITY, null);
                set_config(AIGateway::CONFIG_NAME, $config);
                $config['capabilities'][AIGateway::TRANSLATION_CAPABILITY]['quota'] = 5 + AIGateway::usage(AIGateway::TRANSLATION_CAPABILITY, null);
                set_config(AIGateway::CONFIG_NAME, $config);
                AIGateway::recordUsage(AIGateway::TRANSLATION_CAPABILITY, 'zz-disp', 5);
            } elseif (($caso[4] ?? null) === 'registro') {
                set_config(AIGateway::CONFIG_NAME, $config);
                AIGateway::recordUsage(AIGateway::TRANSLATION_CAPABILITY, 'zz-disp', 3, 0, 0, 'zz', 'zz-d');
            } else {
                set_config(AIGateway::CONFIG_NAME, $config);
            }
            $estado = AIGateway::availability($capacidad, $usuario);
            $motivo = AIGateway::denialReason($capacidad, $usuario);
            $tabla[$nombre] = [$estado['state'], $motivo];
            if ($estado['state'] !== $esperado || ($motivo === null) !== ($estado['state'] === AIGateway::AVAILABILITY_AVAILABLE)) {
                $malos[] = "{$nombre}: " . json_encode([$estado, $motivo], \JSON_UNESCAPED_UNICODE);
            }
            if (($caso[4] ?? null) !== null) {
                $tabla[$nombre][] = $estado['message'];
                $tabla[$nombre][] = $estado['renewsAt'];
            }
            $borrarConsumo();
        }
        foreach ($tabla as $nombre => $fila) {
            echoTerminal('   medido · ' . $nombre . ': ' . json_encode($fila, \JSON_UNESCAPED_UNICODE));
        }
        $check($malos === [], '1a los trece casos dan su state, y denialReason() es null si y solo si state es available', implode(' · ', $malos));
        $noRegistrada = AIGateway::availability('zz-no-registrada', $root);
        $check($noRegistrada === ['state' => AIGateway::AVAILABILITY_HIDDEN, 'message' => null, 'renewsAt' => null], '1b una capacidad no registrada: hidden, sin lanzar (denialReason(), como siempre, lanza)');
        //A quien no puede usarla, la misma razón genérica con la IA o la función apagadas que con todo encendido (H311 2).
        set_config(AIGateway::CONFIG_NAME, $centro());
        $sinPermisoEncendida = AIGateway::denialReason(AIGateway::TRANSLATION_CAPABILITY, $general);
        set_config(AIGateway::CONFIG_NAME, $centro([], ['enabled' => false]));
        $sinPermisoIaApagada = AIGateway::denialReason(AIGateway::TRANSLATION_CAPABILITY, $general);
        $rootIaApagada = AIGateway::denialReason(AIGateway::TRANSLATION_CAPABILITY, $root);
        set_config(AIGateway::CONFIG_NAME, $centro(['enabled' => false]));
        $sinPermisoFuncionApagada = AIGateway::denialReason(AIGateway::TRANSLATION_CAPABILITY, $general);
        $check($sinPermisoEncendida !== null && $sinPermisoIaApagada === $sinPermisoEncendida && $sinPermisoFuncionApagada === $sinPermisoEncendida, '1e DISCRIMINANTE: sin permiso, la misma razón con la IA o la función apagadas que con todo encendido: no sabe si está apagada', json_encode([$sinPermisoEncendida, $sinPermisoIaApagada, $sinPermisoFuncionApagada], \JSON_UNESCAPED_UNICODE) ?: '');
        $check($rootIaApagada === __('ai', 'La inteligencia artificial está apagada.'), '1f y root sigue sabiendo que la IA está apagada', (string) $rootIaApagada);
        $mensual = $tabla['cuota de la función, mensual'] ?? [];
        $acumulada = $tabla['cuota de la función, acumulada'] ?? [];
        $registro = $tabla['cuota del registro'] ?? [];
        $nombreTraduccion = AICapabilityRegistry::get(AIGateway::TRANSLATION_CAPABILITY)?->name() ?? '';
        $finMes = (new \DateTimeImmutable('first day of next month'))->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $check(str_starts_with((string) ($mensual[2] ?? ''), strReplaceTemplate(__('ai', 'Se alcanzó el límite de uso de «%función%» en este periodo.'), ['%función%' => $nombreTraduccion])) && ($mensual[3] ?? null) === $finMes && str_contains((string) $mensual[2], strReplaceTemplate(__('ai', 'Se renueva el %fecha%.'), ['%fecha%' => (new \DateTimeImmutable($finMes))->format('d-m-Y H:i')])), '1c el límite mensual de la función: su mensaje, y se renueva el día 1 del mes siguiente', (string) json_encode($mensual, \JSON_UNESCAPED_UNICODE));
        $check(array_key_exists(3, $acumulada) && $acumulada[3] === null && str_ends_with((string) ($acumulada[2] ?? ''), __('ai', 'Quien administra puede reiniciar el contador en Configuración › IA.')), '1d el límite acumulado: renewsAt null y «Quien administra puede reiniciar el contador»', (string) json_encode($acumulada, \JSON_UNESCAPED_UNICODE));
        $check(str_starts_with((string) ($registro[2] ?? ''), strReplaceTemplate(__('ai', 'Se alcanzó el límite de uso del proveedor «%alias%» en este periodo.'), ['%alias%' => 'Zz disponible'])) && ($registro[3] ?? null) === (new \DateTimeImmutable($anclaRegistro))->modify('+99999 days')->format('Y-m-d H:i:s'), '1e el límite del registro: con su sobrenombre, y se renueva al final de su periodo', (string) json_encode($registro, \JSON_UNESCAPED_UNICODE));
        echoTerminal(' ');

        //─── 2 · El fin del periodo ──────────────────────────────────────────────────────────────
        echoTerminal('[2] periodEnd() y providerPeriodEnd() con relojes fijos');
        $reloj = (int) strtotime('2026-11-16 12:00:00');
        $fin = function (array $traduccion) use ($centro, $reloj): ?string {
            set_config(AIGateway::CONFIG_NAME, $centro($traduccion));
            return AIGateway::periodEnd(AIGateway::TRANSLATION_CAPABILITY, $reloj);
        };
        $fines = [
            'mensual' => [$fin(['period' => 'monthly']), '2026-12-01 00:00:00'],
            'mensual, reiniciado a mitad de mes' => [$fin(['period' => 'monthly', 'periodAnchor' => '2026-11-10 08:00:00']), '2026-12-01 00:00:00'],
            'cada 10 días' => [$fin(['period' => 'every-days', 'periodLength' => 10, 'periodAnchor' => '2026-11-01 08:00:00']), '2026-11-21 08:00:00'],
            'cada 2 meses, ancla el 31' => [$fin(['period' => 'every-months', 'periodLength' => 2, 'periodAnchor' => '2026-01-31 10:00:00']), '2026-11-30 10:00:00'],
            'acumulado' => [$fin(['period' => 'accumulated']), null],
        ];
        set_config(AIGateway::CONFIG_NAME, $centro([], ['providers' => [['id' => 'zz-d', 'type' => 'deepseek', 'alias' => 'Zz', 'quota' => 1, 'period' => 'every-days', 'periodLength' => 10, 'periodAnchor' => '2026-11-01 08:00:00']]]));
        $fines['registro, cada 10 días'] = [AIGateway::providerPeriodEnd('zz-d', $reloj), '2026-11-21 08:00:00'];
        $fines['registro no guardado'] = [AIGateway::providerPeriodEnd('zz-no-existe', $reloj), null];
        $malosFin = array_filter($fines, fn(array $par): bool => $par[0] !== $par[1]);
        $check($malosFin === [], '2a mensual → día 1 siguiente, también reiniciado a mitad de mes; cada 10 días y cada 2 meses con ancla (el 31 cae en el 30); acumulado y un registro no guardado → null', (string) json_encode($fines));
        echoTerminal(' ');

        //─── 3 · Sin cerrar la base y sin red ────────────────────────────────────────────────────
        echoTerminal('[3] La consulta no cierra la base ni pide nada a la red');
        set_config(AIGateway::CONFIG_NAME, $centro());
        $conexion = BaseModel::getDb(Config::app_db('default')['db']);
        $peticiones = new \ArrayObject();
        $cliente = new class ($peticiones) implements \Psr\Http\Client\ClientInterface {
            public function __construct(private \ArrayObject $peticiones)
            {
            }

            public function sendRequest(\Psr\Http\Message\RequestInterface $request): \Psr\Http\Message\ResponseInterface
            {
                $this->peticiones->append((string) $request->getUri());
                throw new \GuzzleHttp\Exception\ConnectException('zz sin respuesta', $request);
            }
        };
        AIGateway::setProviderHttpClientForTesting($cliente);
        $estados = array_map(fn(string $capacidad): string => AIGateway::availability($capacidad, $root)['state'], [AIGateway::TRANSLATION_CAPABILITY, Assistant::CAPABILITY, AIGateway::SPEECH_CAPABILITY]);
        AIGateway::setProviderHttpClientForTesting(null);
        $despues = BaseModel::getDb(Config::app_db('default')['db']);
        $check($conexion !== null && $despues === $conexion, '3a DISCRIMINANTE: la conexión a la base es la misma antes y después (no la cierra)');
        $check($peticiones->count() === 0, '3b con el cliente simulado de fallo puesto, no recibe ninguna petición', (string) json_encode([$estados, $peticiones->getArrayCopy()]));
        echoTerminal(' ');

        //─── 4 · El botón «Traducir» de Publicaciones ────────────────────────────────────────────
        echoTerminal('[4] El botón «Traducir» de Publicaciones en sus tres estados');
        $pintar = function (array $aiTranslation): string {
            $langGroup = PublicationsLang::LANG_GROUP;
            $baseLang = 'es';
            $langCode = 'en';
            ob_start();
            include basepath('app/classes/Publications/Views/publications/forms/inc/translation-button.php');
            return (string) ob_get_clean();
        };
        set_config(AIGateway::CONFIG_NAME, $centro([], ['enabled' => false]));
        $oculto = $pintar(AIGateway::availability(AIGateway::TRANSLATION_CAPABILITY, $root));
        $config = $centro(['quota' => 5, 'period' => 'monthly']);
        $config['capabilities'][AIGateway::TRANSLATION_CAPABILITY]['quota'] = 5 + AIGateway::usage(AIGateway::TRANSLATION_CAPABILITY, null);
        set_config(AIGateway::CONFIG_NAME, $config);
        $config['capabilities'][AIGateway::TRANSLATION_CAPABILITY]['quota'] = 5 + AIGateway::usage(AIGateway::TRANSLATION_CAPABILITY, null);
        set_config(AIGateway::CONFIG_NAME, $config);
        AIGateway::recordUsage(AIGateway::TRANSLATION_CAPABILITY, 'zz-disp', 5);
        $limite = $pintar(AIGateway::availability(AIGateway::TRANSLATION_CAPABILITY, $root));
        $borrarConsumo();
        set_config(AIGateway::CONFIG_NAME, $centro());
        $disponible = $pintar(AIGateway::availability(AIGateway::TRANSLATION_CAPABILITY, $root));
        $check(trim($oculto) === '' && !str_contains($oculto, 'do-translation'), '4a con la IA apagada, el botón NO está en el HTML (ni su hueco)', mb_substr($oculto, 0, 120));
        $check(preg_match('/<button[^>]*\bdisabled\b[^>]*do-translation|<button[^>]*do-translation[^>]*\bdisabled\b/', $limite) === 1 && str_contains($limite, ' disabled"') && str_contains($limite, 'Se alcanzó el límite') && str_contains($limite, 'data-ai-limit-message'), '4b con la cuota alcanzada: el botón con la clase y el atributo disabled, y «Se alcanzó el límite» a la vista', mb_substr(strip_tags($limite), 0, 160));
        $check(str_contains($disponible, 'do-translation') && !str_contains($disponible, 'disabled') && !str_contains($disponible, 'data-ai-limit-message') && str_contains($disponible, 'from-lang="es"') && str_contains($disponible, 'to-lang="en"'), '4c disponible: el botón como hoy, sin disabled ni aviso');
        echoTerminal(' ');

        //─── 5 · chatHandler(), en desuso, no cambia ─────────────────────────────────────────────
        echoTerminal('[5] chatHandler() (en desuso) mira solo los controles de antes, no el registro de proveedores');
        set_config('translationAI', AI_OPENAI);
        set_config('OpenAIApiKey', 'zz-clave-vieja');
        set_config('modelOpenAI', 'zz-modelo-viejo');
        //Con la conversión escrita y la traducción sin proveedor: para la puerta no hay proveedor; para chatHandler(), el de antes.
        set_config(AIGateway::CONFIG_NAME, $centro(['provider' => null, 'model' => null]));
        $oculta = AIGateway::availability(AIGateway::TRANSLATION_CAPABILITY, $root)['state'];
        try {
            $viejo = AIGateway::chatHandler(AIGateway::TRANSLATION_CAPABILITY, $root);
            $deVerdad = $viejo instanceof \API\Adapters\OpenAIHandlerAdapter ? 'OpenAIHandlerAdapter' : get_debug_type($viejo);
        } catch (\PiecesPHP\AI\AIUnavailableException $e) {
            $deVerdad = 'AIUnavailableException: ' . $e->getMessage();
        }
        set_config(AIGateway::CONFIG_NAME, $centro(['provider' => null, 'model' => null], ['enabled' => false]));
        try {
            AIGateway::chatHandler(AIGateway::TRANSLATION_CAPABILITY, $root);
            $apagada = 'sin excepción';
        } catch (\PiecesPHP\AI\AIUnavailableException $e) {
            $apagada = $e->getMessage();
        }
        $check($oculta === AIGateway::AVAILABILITY_HIDDEN && $deVerdad === 'OpenAIHandlerAdapter' && $apagada === __('ai', 'La inteligencia artificial está apagada.'), '5a sin proveedor en el registro la puerta da hidden, pero chatHandler() devuelve su adaptador de antes; con la IA apagada, lanza como siempre', (string) json_encode([$oculta, $deVerdad, $apagada], \JSON_UNESCAPED_UNICODE));
        echoTerminal(' ');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        AIGateway::setProviderHttpClientForTesting(null);
        ConfigOptions::swapFixedByFileForTesting($fijadas);
        foreach ($previos as $opcion => $valor) {
            set_config($opcion, $valor);
        }
        $borrarConsumo();
        GenericContentPseudoMapper::setContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED, $acumulado);
    }
    $quedan = $database->prepare('SELECT COUNT(*) FROM `' . AIUsageMapper::TABLE . '` WHERE provider = ? OR (capability = ? AND provider = ? AND periodStart = ?)');
    $quedan->execute(['zz-disp', AIGateway::PROVIDER_USAGE_ROW, 'zz-d', $anclaRegistro]);
    $check((int) $quedan->fetchColumn() === 0 && count(array_filter($opciones, fn(string $opcion): bool => get_config($opcion) !== $previos[$opcion])) === 0, 'z0 sin consumo de la prueba; las opciones en memoria, como estaban');

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La disponibilidad de la IA en pantalla (ADR 0077, punto 4): availability() con trece casos (oculta, en su límite o disponible) y denialReason() null si y solo si está disponible, el fin del periodo con relojes fijos, sin cerrar la base ni pedir a la red, y el botón «Traducir» de Publicaciones en sus tres estados. Anota consumo zz-disp y lo borra.')->setEffects([CliActions::EFFECT_DATABASE])->register();
