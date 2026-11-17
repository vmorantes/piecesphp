<?php

//Configuración › IA (ADR 0074 y 0077): guardar ai_center, la conversión del primer guardado, «Reiniciar el contador» y permisos.
//Deja las filas de las opciones de IA como las encontró (H318, por sha1) y retira sus usuarios zz-.
//@areas src/app/classes/PiecesPHP/Settings/
//@areas src/app/classes/API/

use API\Controllers\APIController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\AI\AICapability;
use PiecesPHP\AI\AICapabilityRegistry;
use PiecesPHP\AI\AIGateway;
use PiecesPHP\AI\Mappers\AIUsageMapper;
use PiecesPHP\AI\Providers\AIProviderRegistry;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\ConfigHelpers\ConfigOptions;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\Routing\Slim3Compatibility\Exception\NotFoundException;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Slim\Exception\HttpForbiddenException;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/ai-center-screen', function ($args) {

    echoTerminal("\e[33m[TEST:AICenterScreen] Configuración › IA, el centro de mando\e[39m");
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
    if (!$check($database !== null, 'b0 hay conexión a la base')) {
        return ['success' => false, 'message' => 'sin conexión'];
    }

    $prefijo = 'zz-prueba-centro-ia-' . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    $usuarios = [];
    //Todas las de IA que un guardado podría tocar: el centro, las claves y la caché de modelos, y las siete de antes (que no
    //se tocan nunca: H318 era modelGroq vaciado por esta suite).
    $opciones = [AIGateway::CONFIG_NAME, AIProviderRegistry::KEYS_CONFIG_NAME, AIGateway::MODELS_CONFIG_NAME, 'OpenAIApiKey', 'MistralAIApiKey', 'GroqAPIKey', 'modelOpenAI', 'modelMistral', 'modelGroq', 'translationAI'];
    $viejas = ['OpenAIApiKey', 'MistralAIApiKey', 'GroqAPIKey', 'modelOpenAI', 'modelMistral', 'modelGroq', 'translationAI'];
    $tablaOpciones = (new SettingsModel())->getModel()->getTable();
    $fila = function (string $nombre) use ($database, $tablaOpciones): ?array {
        $consulta = $database->prepare("SELECT * FROM `{$tablaOpciones}` WHERE BINARY name = ?");
        //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o lee o lanza. Lo que cuenta es el fetch que sigue.
        $consulta->execute([$nombre]);
        $leida = $consulta->fetch(\PDO::FETCH_ASSOC);
        return $leida === false ? null : $leida;
    };
    $filasPrevias = array_combine($opciones, array_map($fila, $opciones));
    $huella = fn(): string => sha1((string) json_encode(array_combine($opciones, array_map($fila, $opciones))));
    $huellaAntes = $huella();
    $fijadasPorArchivo = ConfigOptions::fixedByFile();
    $valoresPrevios = array_combine($opciones, array_map(fn(string $nombre): mixed => get_config($nombre), $opciones));
    $usuarioPrevio = get_config('current_user');
    $guardadoPrevio = get_config('pcsphp_current_user_stored');
    $registroPrevio = AICapabilityRegistry::all();
    //recordUsage() también suma en el acumulado de siempre: se repone sin las claves zz- (también las que dejara otra pasada).
    $acumuladoPrevio = (array) GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED);

    //El ai_center guardado en la base, como lo leerá la petición siguiente.
    $centroGuardado = function () use ($fila): ?array {
        $leida = $fila(AIGateway::CONFIG_NAME);
        $valor = $leida === null ? null : json_decode((string) $leida['value'], true);
        return is_array($valor) ? $valor : null;
    };
    //Por el mismo camino que el arranque (index.php → getConfigurations → applyDeclared): la fila llega como OBJETO.
    $recargar = function (): void {
        $configuraciones = SettingsModel::getConfigurations();
        set_config(AIGateway::CONFIG_NAME, $configuraciones[AIGateway::CONFIG_NAME] ?? false);
    };
    $peticion = fn(array $cuerpo): RequestRoute => (new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-prueba-centro-ia'), new Headers(), [], [], (new StreamFactory())->createStream('')))
        ->withParsedBody($cuerpo)
        ->withAttribute('route', null);
    //El formulario: solo el centro (las opciones de antes ya no viajan, ADR 0077).
    $guardar = function (array $centro) use ($peticion): array {
        $respuesta = (new SettingsController())->ai($peticion(SettingsController::aiAssignProviderIds([
            AIGateway::CONFIG_NAME => $centro,
        ])), new ResponseRoute());
        $cuerpo = json_decode((string) $respuesta->getBody(), true);
        return is_array($cuerpo) ? $cuerpo : [];
    };
    $traduccion = function (array $ajustes) {
        return ['enabled' => $ajustes['enabled'] ?? '1', 'userTypes' => $ajustes['userTypes'] ?? [''], 'quota' => $ajustes['quota'] ?? '0', 'period' => $ajustes['period'] ?? 'accumulated', 'periodLength' => $ajustes['periodLength'] ?? '1'];
    };
    $comoUsuario = function (int $id): void {
        set_config('current_user', (object) ['id' => $id]);
        set_config('pcsphp_current_user_stored', null);
    };
    $atender = function (string $accion): string {
        $peticion = (new RequestRoute('POST', (new UriFactory())->createUri('http://localhost/zz-prueba-centro-ia'), new Headers(), [], [], (new StreamFactory())->createStream('')))
            ->withAttribute('actionType', $accion)
            ->withAttribute('route', null);
        try {
            (new APIController())->translations($peticion, new ResponseRoute());
            return 'sin excepción';
        } catch (HttpForbiddenException $e) {
            return '403';
        } catch (NotFoundException $e) {
            return '404';
        }
    };
    $crearUsuario = function (string $sufijo, int $tipo) use ($prefijo, &$usuarios): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Centro';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[$sufijo] = (int) $u->id;
        return (int) $u->id;
    };

    try {
        $check(AICapabilityRegistry::get('translation') !== null, 'b1 CANARIO: la traducción está registrada (si no, lo de abajo no mide nada)');
        $check(is_object((object) []) && AIGateway::normalizeCenter((object) ['enabled' => false]) === ['enabled' => false], 'b2 lo guardado llega de la base como objeto, y la puerta lo lee como array');
        set_config(AIGateway::CONFIG_NAME, false);

        //─── s1 · Capacidad apagada ─────────────────────────────────────────────────────────────────────
        echoTerminal('[s1] Guardar con la traducción apagada');
        $comoUsuario(1);
        $respuesta = $guardar(['enabled' => '1', 'capabilities' => ['translation' => $traduccion(['enabled' => '0'])]]);
        $recargar();
        $guardado = $centroGuardado();
        $check(($respuesta['success'] ?? null) === true && ($guardado['capabilities']['translation']['enabled'] ?? null) === false, 's1a ai_center guarda la capacidad apagada', (string) json_encode([$respuesta['message'] ?? null, $guardado['capabilities']['translation'] ?? null], \JSON_UNESCAPED_UNICODE));
        $check(!APIController::translationAIAvailable() && $atender('translate') === '404' && $atender('translateGroup') === '404', 's1b y la traducción da 404 en sus acciones de IA (root, que sí puede traducir)');
        $guardar(['enabled' => '1', 'capabilities' => ['translation' => $traduccion([])]]);
        $recargar();
        $check(APIController::translationAIAvailable() && $atender('translate') !== '404', 's1c encendida otra vez, la acción vuelve a estar', $atender('translate'));
        $guardar(['enabled' => '0', 'capabilities' => ['translation' => $traduccion([])]]);
        $recargar();
        $check(!APIController::translationAIAvailable() && $atender('translate') === '404', 's1d con el interruptor general apagado, también 404');
        echoTerminal(' ');

        //─── s2 · «Quién la usa» ────────────────────────────────────────────────────────────────────────
        echoTerminal('[s2] Guardar «Quién la usa»');
        $general = $crearUsuario('general', UsersModel::TYPE_USER_GENERAL);
        $comunicaciones = $crearUsuario('comunicaciones', UsersModel::TYPE_USER_COMUNICACIONES);
        $comoUsuario(1);
        $guardar(['enabled' => '1', 'capabilities' => ['translation' => $traduccion(['userTypes' => [(string) UsersModel::TYPE_USER_GENERAL]])]]);
        $recargar();
        $comoUsuario($general);
        $puedeGeneral = APIController::currentUserCanTranslate();
        $comoUsuario($comunicaciones);
        $puedeComunicaciones = APIController::currentUserCanTranslate();
        $check($puedeGeneral && !$puedeComunicaciones && $atender('translate') === '403', 's2 currentUserCanTranslate() sigue lo guardado: el general sí, comunicaciones no (y recibe 403)');
        $comoUsuario(1);
        echoTerminal(' ');

        //─── s3 · Lo inválido no se guarda ──────────────────────────────────────────────────────────────
        echoTerminal('[s3] Valores inválidos');
        $antes = $fila(AIGateway::CONFIG_NAME);
        $cuotaMala = $guardar(['enabled' => '1', 'capabilities' => ['translation' => $traduccion(['quota' => '-1'])]]);
        $check(($cuotaMala['success'] ?? null) === false && str_contains((string) ($cuotaMala['message'] ?? ''), 'no es válido') && $fila(AIGateway::CONFIG_NAME) === $antes, 's3a cuota -1: rechazo de validación, con su motivo, y ai_center SIN cambios', (string) ($cuotaMala['message'] ?? ''));
        $periodoMalo = $guardar(['enabled' => '1', 'capabilities' => ['translation' => $traduccion(['period' => 'zz-inventado'])]]);
        $check(($periodoMalo['success'] ?? null) === false && str_contains((string) ($periodoMalo['message'] ?? ''), 'no es válido') && $fila(AIGateway::CONFIG_NAME) === $antes, 's3b periodo inventado: rechazo de validación y ai_center SIN cambios', (string) ($periodoMalo['message'] ?? ''));
        echoTerminal(' ');

        //─── s4 · Lo que no viene del formulario se conserva ────────────────────────────────────────────
        echoTerminal('[s4] Lo que el formulario no trae');
        $previo = $centroGuardado() ?? [];
        $previo['capabilities']['zz-fantasma'] = ['enabled' => false, 'quota' => 3];
        $previo['capabilities']['translation']['periodAnchor'] = '2026-01-02 03:04:05';
        //Como objeto, igual que lo deja el arranque: así se prueba que guardar parte de lo guardado de verdad.
        set_config(AIGateway::CONFIG_NAME, json_decode((string) json_encode($previo)));
        $guardar(['enabled' => '1', 'capabilities' => ['translation' => $traduccion([])]]);
        $guardado = $centroGuardado();
        $check(($guardado['capabilities']['zz-fantasma'] ?? null) === ['enabled' => false, 'quota' => 3], 's4a la entrada de una capacidad no registrada se conserva', (string) json_encode($guardado['capabilities']['zz-fantasma'] ?? null));
        $check(($guardado['capabilities']['translation']['periodAnchor'] ?? null) === '2026-01-02 03:04:05', 's4b y el periodAnchor guardado de la traducción también (no viene del formulario)', (string) json_encode($guardado['capabilities']['translation'] ?? null));
        $recargar();
        echoTerminal(' ');

        //─── s5 · «Reiniciar el contador» ───────────────────────────────────────────────────────────────
        echoTerminal('[s5] Reiniciar el contador');
        AICapabilityRegistry::register(new AICapability('zz-centro', fn(): string => 'zz centro', AICapability::PROVIDER_CHAT));
        //RETORNO-IGNORADO: limpieza de la prueba antes del caso; lo que importa lo miden los casos.
        $database->prepare('DELETE FROM `' . AIUsageMapper::TABLE . '` WHERE capability LIKE ?')->execute(['zz-%']);
        AIGateway::recordUsage('zz-centro', 'zz-proveedor', 7);
        $usoAntes = AIGateway::usage('zz-centro');
        $reinicio = (new SettingsController())->aiUsageReset($peticion(['capability' => 'zz-centro']), new ResponseRoute());
        $cuerpo = json_decode((string) $reinicio->getBody(), true);
        $ancla = $centroGuardado()['capabilities']['zz-centro']['periodAnchor'] ?? '';
        $filasViejas = (int) $database->query('SELECT COUNT(*) FROM `' . AIUsageMapper::TABLE . "` WHERE capability = 'zz-centro' AND tokens = 7")->fetchColumn();
        $check($usoAntes === 7 && is_array($cuerpo) && ($cuerpo['success'] ?? null) === true && ($cuerpo['message'] ?? null) === 'Contador reiniciado.', 's5a responde «Contador reiniciado.»', (string) json_encode($cuerpo, \JSON_UNESCAPED_UNICODE));
        $check(abs((int) strtotime($ancla) - time()) <= 5 && AIGateway::periodStart('zz-centro') === $ancla && ($cuerpo['periodStart'] ?? null) === $ancla, 's5b periodAnchor = ahora, y el periodo empieza ahí', "{$ancla} / " . AIGateway::periodStart('zz-centro'));
        $check(AIGateway::usage('zz-centro') === 0 && ($cuerpo['usage'] ?? null) === 0 && $filasViejas === 1, 's5c usage() vuelve a 0 y la fila vieja sigue intacta', 'filas viejas ' . $filasViejas);
        $noExiste = (new SettingsController())->aiUsageReset($peticion(['capability' => 'zz-no-existe']), new ResponseRoute());
        $check($noExiste->getStatusCode() === 400, 's5d una clave no registrada: 400', (string) $noExiste->getStatusCode());
        echoTerminal(' ');

        //─── s7 · El primer guardado escribe la conversión ──────────────────────────────────────────────
        echoTerminal('[s7] Guardar la pantalla escribe los convertidos y la marca, y no toca las opciones de antes');
        set_config(AIGateway::CONFIG_NAME, false);
        set_config(AIProviderRegistry::KEYS_CONFIG_NAME, false);
        set_config('OpenAIApiKey', 'zz-clave-openai-s7');
        set_config('MistralAIApiKey', '');
        set_config('GroqAPIKey', 'zz-clave-groq-s7');
        set_config('translationAI', AI_OPENAI);
        set_config('modelOpenAI', 'zz-modelo-s7');
        set_config('modelGroq', '');
        ConfigOptions::swapFixedByFileForTesting(['GroqAPIKey']);
        $viejasAntes = sha1((string) json_encode(array_map($fila, $viejas)));
        //Lo que manda la pantalla: cada convertido como una fila, y cada función con su selección efectiva ya rellena.
        $filasConvertidas = [];
        foreach (array_values(AIProviderRegistry::convertedPending()) as $convertido) {
            $filasConvertidas[] = array_merge(array_map(fn($valor) => $valor === null ? '' : (is_array($valor) ? $valor : (string) $valor), array_diff_key($convertido['entry'], ['models' => true])), ['key' => '']);
        }
        $funciones = [];
        foreach (['translation', AIGateway::SPEECH_CAPABILITY] as $capacidad) {
            $efectiva = AIGateway::effectiveSelectionFor($capacidad);
            $funciones[$capacidad] = $traduccion([]) + ['provider' => (string) $efectiva['provider'], 'model' => (string) $efectiva['model']];
        }
        $tablaAntes = array_map(fn(string $capacidad): array => AIGateway::effectiveSelectionFor($capacidad), array_combine(array_keys($funciones), array_keys($funciones)));
        //B4: un formulario de antes (sin converted_sent) guarda sin convertir; el de esta versión lo manda.
        $deAntes = $guardar(['enabled' => '1', 'providers_sent' => '1', 'providers' => [], 'capabilities' => array_map(fn(array $f): array => array_merge($f, ['provider' => '', 'model' => '']), $funciones)]);
        $recargar();
        $sinMarca = $centroGuardado() ?? [];
        $check(($deAntes['success'] ?? null) === true && !array_key_exists(AIProviderRegistry::CONVERTED_FLAG, $sinMarca) && array_keys(AIProviderRegistry::convertedPending()) === ['openai', 'groq'], 's7f un guardado con providers_sent y sin converted_sent (una pestaña de antes) NO pone la marca: los convertidos siguen en memoria', (string) json_encode([$deAntes['message'] ?? null, array_keys($sinMarca)], \JSON_UNESCAPED_UNICODE));
        $respuesta = $guardar(['enabled' => '1', 'providers_sent' => '1', 'converted_sent' => '1', 'providers' => $filasConvertidas, 'capabilities' => $funciones]);
        $recargar();
        set_config(AIProviderRegistry::KEYS_CONFIG_NAME, SettingsModel::getConfigurations()[AIProviderRegistry::KEYS_CONFIG_NAME] ?? false);
        $guardado = $centroGuardado() ?? [];
        $ids = array_map(fn($entrada) => $entrada['id'] ?? null, $guardado['providers'] ?? []);
        $claves = AIProviderRegistry::keys();
        $check(($respuesta['success'] ?? null) === true && ($guardado[AIProviderRegistry::CONVERTED_FLAG] ?? null) === true && $ids === ['openai', 'groq'], 's7a la marca puesta y los convertidos escritos como registros (openai y groq; Mistral, sin clave, no)', (string) json_encode([$respuesta['message'] ?? null, $ids], \JSON_UNESCAPED_UNICODE));
        $check(($claves['openai'] ?? null) === 'zz-clave-openai-s7' && !isset($claves['groq']) && ($guardado['providers'][1]['keySource'] ?? null) === 'file' && ($guardado['providers'][1]['keyFile'] ?? null) === 'groq', 's7b la clave «stored» del convertido pasa a ai_provider_keys; la de Groq, «del archivo», no se escribe');
        $tablaDespues = array_map(fn(string $capacidad): array => AIGateway::effectiveSelectionFor($capacidad), array_combine(array_keys($funciones), array_keys($funciones)));
        $check($tablaDespues === $tablaAntes && ($guardado['capabilities']['translation']['provider'] ?? null) === 'openai' && ($guardado['capabilities']['translation']['model'] ?? null) === 'zz-modelo-s7', 's7c cada función queda con su proveedor y su modelo explícitos, los mismos que antes', (string) json_encode(['antes' => $tablaAntes, 'despues' => $tablaDespues]));
        $check(sha1((string) json_encode(array_map($fila, $viejas))) === $viejasAntes, 's7d las siete opciones de antes, idénticas en la base (sha1)');
        set_config('OpenAIApiKey', 'zz-clave-cambiada-s7');
        set_config('translationAI', AI_MISTRAL);
        $check(AIProviderRegistry::get('openai')?->key() === 'zz-clave-openai-s7' && AIGateway::effectiveSelectionFor('translation') === $tablaDespues['translation'] && AIProviderRegistry::convertedPending() === [], 's7e DISCRIMINANTE: después, cambiar OpenAIApiKey o translationAI en memoria no cambia nada');
        ConfigOptions::swapFixedByFileForTesting($fijadasPorArchivo);
        echoTerminal(' ');

        //─── s8 · Cambiar el destino de un registro guardado ────────────────────────────────────────────
        echoTerminal('[s8] Cambiar la dirección de un registro guardado borra su clave escrita y su lista de modelos');
        $registrosS8 = [
            ['id' => 'zz-prov-d', 'type' => 'custom', 'alias' => 'Zz destino', 'baseUrl' => 'https://zz-a.example/v1', 'keyHeader' => null, 'models' => [], 'quota' => 0, 'period' => 'accumulated', 'periodLength' => 1, 'keySource' => 'stored', 'keyFile' => null],
            ['id' => 'zz-prov-e', 'type' => 'custom', 'alias' => 'Zz queda', 'baseUrl' => 'https://zz-e.example/v1', 'keyHeader' => null, 'models' => [], 'quota' => 0, 'period' => 'accumulated', 'periodLength' => 1, 'keySource' => 'stored', 'keyFile' => null],
        ];
        set_config(AIGateway::CONFIG_NAME, ['enabled' => true, AIProviderRegistry::CONVERTED_FLAG => true, 'providers' => $registrosS8, 'capabilities' => []]);
        set_config(AIProviderRegistry::KEYS_CONFIG_NAME, AIProviderRegistry::sealKeys(['zz-prov-d' => 'zz-clave-d', 'zz-prov-e' => 'zz-clave-e']));
        set_config(AIGateway::MODELS_CONFIG_NAME, ['zz-prov-d' => ['models' => ['zz-m'], 'fetchedAt' => '2026-11-16 10:00:00'], 'zz-prov-e' => ['models' => ['zz-m'], 'fetchedAt' => '2026-11-16 10:00:00']]);
        $filaS8 = fn(array $registro, array $cambios): array => array_merge(array_map(fn($valor) => $valor === null ? '' : (is_array($valor) ? $valor : (string) $valor), array_diff_key($registro, ['models' => true])), ['key' => ''], $cambios);
        $respuestaS8 = $guardar(['enabled' => '1', 'providers_sent' => '1', 'converted_sent' => '1', 'providers' => [$filaS8($registrosS8[0], ['baseUrl' => 'https://zz-b.example/v1']), $filaS8($registrosS8[1], ['alias' => 'Zz queda renombrado'])], 'capabilities' => []]);
        $configuraciones = SettingsModel::getConfigurations();
        set_config(AIProviderRegistry::KEYS_CONFIG_NAME, $configuraciones[AIProviderRegistry::KEYS_CONFIG_NAME] ?? false);
        set_config(AIGateway::MODELS_CONFIG_NAME, $configuraciones[AIGateway::MODELS_CONFIG_NAME] ?? false);
        $clavesS8 = AIProviderRegistry::keys();
        $cacheS8 = AIGateway::modelsCache();
        $check(($respuestaS8['success'] ?? null) === true && !isset($clavesS8['zz-prov-d']) && ($clavesS8['zz-prov-e'] ?? null) === 'zz-clave-e' && !isset($cacheS8['zz-prov-d']) && isset($cacheS8['zz-prov-e']), 's8a la dirección nueva sin clave nueva: su clave sale de ai_provider_keys y su lista de ai_provider_models; el que solo cambió de sobrenombre conserva las dos', (string) json_encode([array_keys($clavesS8), array_keys($cacheS8), $respuestaS8['message'] ?? null], \JSON_UNESCAPED_UNICODE));
        echoTerminal(' ');

        //─── s9 · Quitar un convertido vacía su opción de antes ─────────────────────────────────────────
        echoTerminal('[s9] Con la marca, quitar un convertido «stored» vacía su opción de antes; la que fija el archivo, no');
        set_config('MistralAIApiKey', 'zz-clave-mistral-s9');
        set_config('OpenAIApiKey', 'zz-clave-openai-archivo-s9');
        ConfigOptions::swapFixedByFileForTesting(['OpenAIApiKey']);
        $convertidosS9 = [
            ['id' => 'mistral', 'type' => 'mistral', 'alias' => 'Mistral', 'baseUrl' => null, 'keyHeader' => null, 'models' => [], 'quota' => 0, 'period' => 'accumulated', 'periodLength' => 1, 'keySource' => 'stored', 'keyFile' => null],
            ['id' => 'openai', 'type' => 'openai', 'alias' => 'OpenAI', 'baseUrl' => null, 'keyHeader' => null, 'models' => [], 'quota' => 0, 'period' => 'accumulated', 'periodLength' => 1, 'keySource' => 'file', 'keyFile' => 'openai'],
        ];
        set_config(AIGateway::CONFIG_NAME, ['enabled' => true, AIProviderRegistry::CONVERTED_FLAG => true, 'providers' => $convertidosS9, 'capabilities' => []]);
        set_config(AIProviderRegistry::KEYS_CONFIG_NAME, AIProviderRegistry::sealKeys(['mistral' => 'zz-clave-mistral-s9']));
        $respuestaS9 = $guardar(['enabled' => '1', 'providers_sent' => '1', 'converted_sent' => '1', 'providers' => [], 'capabilities' => []]);
        $mistralEnBase = $fila('MistralAIApiKey');
        $check(($respuestaS9['success'] ?? null) === true && get_config('MistralAIApiKey') === '' && ($mistralEnBase === null || (string) $mistralEnBase['value'] === '') && get_config('OpenAIApiKey') === 'zz-clave-openai-archivo-s9', 's9a quitar mistral vacía MistralAIApiKey; quitar openai, que fija el archivo, no toca OpenAIApiKey', (string) json_encode([$respuestaS9['message'] ?? null, get_config('MistralAIApiKey')], \JSON_UNESCAPED_UNICODE));
        ConfigOptions::swapFixedByFileForTesting($fijadasPorArchivo);
        echoTerminal(' ');

        //─── s10 · Las pestañas ─────────────────────────────────────────────────────────────────────────
        echoTerminal('[s10] La pantalla en pestañas: General, Proveedores y Funciones dentro del formulario; la cuota, aparte');
        $comoUsuario(1);
        set_config(AIGateway::CONFIG_NAME, false);
        $vista = (function (): string {
            $langGroup = SettingsController::LANG_GROUP;
            $title = 'zz';
            $description = '';
            $actionURL = '';
            $indexLink = '';
            $helpController = new \PiecesPHP\AdminPanel\Controllers\HelperController();
            ob_start();
            include basepath('app/classes/PiecesPHP/Settings/Views/panel/pages/app_configurations/ai.php');
            return (string) ob_get_clean();
        })();
        $dom = new \DOMDocument();
        //RETORNO-IGNORADO: el HTML del panel trae etiquetas que libxml no conoce; lo que cuenta es lo que se busca después.
        @$dom->loadHTML('<?xml encoding="utf-8"?>' . $vista);
        $xpath = new \DOMXPath($dom);
        $cuenta = function (string $consulta) use ($xpath): int {
            $nodos = $xpath->query($consulta);
            return $nodos === false ? 0 : $nodos->length;
        };
        $pestanas = [];
        foreach ($xpath->query('//*[@data-ai-tabs]//a[@data-tab]') ?: [] as $nodo) {
            $pestanas[] = $nodo instanceof \DOMElement ? $nodo->getAttribute('data-tab') : '';
        }
        $panelesDentro = [];
        foreach (['general', 'proveedores', 'funciones'] as $panel) {
            $panelesDentro[$panel] = $cuenta("//form[contains(concat(' ', normalize-space(@class), ' '), ' ai ')]//div[contains(concat(' ', normalize-space(@class), ' '), ' tab ')][@data-tab='{$panel}']") === 1;
        }
        $cuota = $xpath->query('//form[@data-ai-quota]');
        //libxml cierra solo un <form> al encontrar otro dentro: el anidamiento se mide en el HTML, por la profundidad de las etiquetas.
        $profundidad = 0;
        $cuotaDentro = 0;
        //RETORNO-IGNORADO: lo que cuenta son las etiquetas que deja en $etiquetas; sin ninguna, s10c cae por la cuota.
        preg_match_all('#<(/?)form\b([^>]*)>#i', $vista, $etiquetas, \PREG_SET_ORDER);
        foreach ($etiquetas as $etiqueta) {
            if ($etiqueta[1] === '/') {
                $profundidad--;
                continue;
            }
            $profundidad++;
            if (str_contains($etiqueta[2], 'data-ai-quota') && $profundidad > 1) {
                $cuotaDentro++;
            }
        }
        $check($pestanas === ['general', 'proveedores', 'funciones'], 's10a las tres pestañas, con sus data-tab, en ese orden', (string) json_encode($pestanas));
        $check($panelesDentro === ['general' => true, 'proveedores' => true, 'funciones' => true] && $cuenta("//form[contains(concat(' ', normalize-space(@class), ' '), ' ai ')]//*[@data-ai-providers]") === 1 && $cuenta("//form[contains(concat(' ', normalize-space(@class), ' '), ' ai ')]//*[@data-ai-capability]") > 0, 's10b los paneles (con los proveedores y las funciones) están DENTRO del formulario principal', (string) json_encode($panelesDentro));
        $check($cuota !== false && $cuota->length === 1 && $cuotaDentro === 0, 's10c DISCRIMINANTE: el formulario de la cuota existe y NO está dentro del principal (nada anidado)', 'cuota: ' . ($cuota !== false ? $cuota->length : -1) . ', formularios dentro: ' . $cuotaDentro);
        echoTerminal(' ');

        //─── s6 · Sin permiso ───────────────────────────────────────────────────────────────────────────
        echoTerminal('[s6] Permisos de las rutas (lo que index.php comprueba antes de llamar)');
        foreach (['configurations-integrations-ai', 'configurations-integrations-ai-usage-reset', 'configurations-integrations-ai-provider-models'] as $ruta) {
            $info = get_route_info($ruta);
            $check(is_array($info) && ($info['require_login'] ?? false) === true, "s6a {$ruta} existe y pide sesión: sin ella, 403");
            $check(!Roles::hasPermissions($ruta, UsersModel::TYPE_USER_GENERAL, true) && !Roles::hasPermissions($ruta, UsersModel::TYPE_USER_COMUNICACIONES, true) && Roles::hasPermissions($ruta, UsersModel::TYPE_USER_ROOT, true) && Roles::hasPermissions($ruta, UsersModel::TYPE_USER_ADMIN_GRAL, true), "s6b {$ruta}: 403 a general y comunicaciones; root y administrador general, sí");
        }

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        ConfigOptions::swapFixedByFileForTesting($fijadasPorArchivo);
        AICapabilityRegistry::swapForTesting($registroPrevio);
        GenericContentPseudoMapper::setContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED, array_filter($acumuladoPrevio, fn($clave) => !str_starts_with((string) $clave, 'zz-'), \ARRAY_FILTER_USE_KEY));
        set_config('current_user', $usuarioPrevio);
        set_config('pcsphp_current_user_stored', $guardadoPrevio);
        foreach ($opciones as $nombre) {
            set_config($nombre, $valoresPrevios[$nombre]);
            try {
                //RETORNO-IGNORADO: lo comprueba z1 comparando la fila con la de antes.
                $database->prepare("DELETE FROM `{$tablaOpciones}` WHERE BINARY name = ?")->execute([$nombre]);
                if ($filasPrevias[$nombre] !== null) {
                    $columnas = implode('`, `', array_keys($filasPrevias[$nombre]));
                    $marcas = implode(', ', array_fill(0, count($filasPrevias[$nombre]), '?'));
                    //RETORNO-IGNORADO: lo comprueba z1 comparando la fila con la de antes.
                    $database->prepare("INSERT INTO `{$tablaOpciones}` (`{$columnas}`) VALUES ({$marcas})")->execute(array_values($filasPrevias[$nombre]));
                }
            } catch (\Throwable $e) {
                $check(false, "z1 la opción {$nombre} se repone", get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 200));
            }
        }
        try {
            //RETORNO-IGNORADO: limpieza de la prueba.
            $database->prepare('DELETE FROM `' . AIUsageMapper::TABLE . '` WHERE capability LIKE ?')->execute(['zz-%']);
            foreach ($usuarios as $id) {
                $aprobaciones = SystemApprovalsMapper::model();
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z2 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tablaUsuarios),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z2 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z2 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 200));
        }
    }

    $acumulado = (array) GenericContentPseudoMapper::getContentData(GenericContentPseudoMapper::CONTENT_TOKENS_USED);
    $check(count(array_filter(array_keys($acumulado), fn($clave) => str_starts_with((string) $clave, 'zz-'))) === 0, 'z3 el acumulado de siempre no conserva ninguna clave zz-', (string) json_encode(array_keys($acumulado)));
    $huellaDespues = $huella();
    echoTerminal("   medido: sha1 de las filas de IA antes {$huellaAntes}, después {$huellaDespues}; modelGroq en la base: " . json_encode($filasPrevias['modelGroq']['value'] ?? null));
    $check(array_combine($opciones, array_map($fila, $opciones)) === $filasPrevias && $huellaDespues === $huellaAntes, 'z1 las opciones de IA quedan como estaban (ai_center, ai_provider_keys, ai_provider_models y las siete de antes), con su fila o sin ninguna (sha1)');
    $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
    $check($quedan === 0, 'z2 no queda ningún usuario de la prueba', "quedan {$quedan}");

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Configuración › IA (ADR 0074, ronda 3, y la revisión del ADR 0077): el primer guardado escribe la conversión (marca, convertidos, la clave «stored» en ai_provider_keys, la selección explícita y las opciones de antes intactas); guardar ai_center (capacidad apagada → 404 de la traducción, quién la usa → currentUserCanTranslate()), rechazo de lo inválido sin guardar a medias, lo que el formulario no trae se conserva, «Reiniciar el contador» (periodo nuevo, filas viejas intactas, 400 sin clave registrada) y permisos de las dos rutas.')->setEffects([CliActions::EFFECT_DATABASE])->register();
