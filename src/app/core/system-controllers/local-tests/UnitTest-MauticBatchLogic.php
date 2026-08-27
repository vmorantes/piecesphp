<?php

//La mitad de `tests:mautic-batch-send` que NO sale a la red: el mismo recorrido contra un
//transporte falso, sin claves y sin correo. Ver T125.

use API\Adapters\MauticEmailAdapter;
use PiecesPHP\Core\BaseController;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/mautic-batch-logic', function ($args) {

    echoTerminal("\e[33m[TEST:MauticBatchLogic] El envío masivo, contra un transporte falso\e[39m");
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

    //El falso HEREDA del adaptador real: la firma que ejercita es la de producción, no una copia.
    //No llama al constructor padre a propósito: ese crea directorios y aquí no hace falta nada.
    $transporte = new class ('', '', '') extends MauticEmailAdapter {
        /** @var array<int,array<int,array<string,string>>> */
        public array $lotesRecibidos = [];
        /** @var array<int,int> */
        public array $contactosDelSegmento = [];
        public ?int $idPlantillaPedida = null;
        public array $contactosDevueltos = [1, 2, 3];
        public ?int $segmentoDevuelto = 77;
        public ?int $plantillaDevuelta = 99;
        public int $enviadosDevueltos = 3;

        public function __construct(string $baseURL, string $clientID, string $clientSecret)
        {
            //NO se llama al padre a proposito: el suyo crea directorios y aqui no hace falta.
            $this->baseURL = $baseURL;
            $this->clientID = $clientID;
            $this->clientSecret = $clientSecret;
        }
        public function createBatchContacts(array $contacts): array
        {
            $this->lotesRecibidos[] = $contacts;
            return $this->contactosDevueltos;
        }
        public function createSegment(array $configurations, array $contactIDs = []): ?int
        {
            $this->contactosDelSegmento = $contactIDs;
            return $this->segmentoDevuelto;
        }
        public function createEmailTemplate(string $fromAddress, string $fromName, string $subject, string $bodyHTML, array $otherConfigurations = [], ?string $nameMailID = null): ?int
        {
            return $this->plantillaDevuelta;
        }
        public function sendEmail(int $emailID): int
        {
            $this->idPlantillaPedida = $emailID;
            return $this->enviadosDevueltos;
        }
    };

    $controlador = new BaseController();
    $basePathView = realpath(__DIR__ . '/../../system-views');
    if ($basePathView !== false) {
        $controlador->setViewDir($basePathView);
    }
    $personas = include __DIR__ . '/../test-data/persons.php';
    $correr = fn ($t) => pcsphp_mautic_batch_flow($t, $personas, 'de@ejemplo.test', $controlador, 'Prueba_', 'TestPCSPHP-Lang');

    //─── 1/3 · El lote y la lista ──────────────────────────────────────────────────────
    echoTerminal('[1/3] Lotear y pasar la lista');

    $resultado = $correr($transporte);
    $check($resultado['success'] === true, 'el recorrido completo termina en éxito', $resultado['message']);
    $check(count($transporte->lotesRecibidos) === 1, 'los contactos van en UN solo lote, no de uno en uno',
        'lotes=' . count($transporte->lotesRecibidos));
    $lote = $transporte->lotesRecibidos[0] ?? [];
    $check(count($lote) === count($personas), 'y el lote lleva a todas las personas',
        count($lote) . ' de ' . count($personas));
    $claves = count($lote) > 0 ? array_keys($lote[0]) : [];
    sort($claves);
    $check($claves === ['email', 'lastNames', 'names'], 'cada contacto lleva las tres claves que Mautic espera',
        implode(', ', $claves));
    //El segmento tiene que recibir los IDs que devolvió la creación, no la lista de entrada.
    $check($transporte->contactosDelSegmento === [1, 2, 3], 'el segmento recibe los IDs devueltos, no los correos',
        (string) json_encode($transporte->contactosDelSegmento));
    $check($transporte->idPlantillaPedida === 99, 'y se envía la plantilla que se acaba de crear',
        var_export($transporte->idPlantillaPedida, true));
    echoTerminal(' ');

    //─── 2/3 · Los caminos de error, uno por uno ───────────────────────────────────────
    echoTerminal('[2/3] Cada fallo devuelve SU motivo y para ahí');

    $casos = [
        ['contactosDevueltos', [], 'No se pudo crear el contacto.'],
        ['segmentoDevuelto', null, 'No se pudo crear el segmento.'],
        ['plantillaDevuelta', null, 'No se pudo crear la plantilla de correo.'],
        ['enviadosDevueltos', 0, 'No se pudo enviar el correo.'],
    ];
    foreach ($casos as [$propiedad, $valor, $mensaje]) {
        $falso = clone $transporte;
        $falso->lotesRecibidos = [];
        $falso->$propiedad = $valor;
        $r = $correr($falso);
        $check($r['success'] === false && $r['message'] === $mensaje,
            "«{$propiedad}» que falla devuelve «{$mensaje}»", $r['message']);
    }
    echoTerminal(' ');

    //─── 3/3 · Y no se ha salido al exterior ───────────────────────────────────────────
    echoTerminal('[3/3] Sin claves y sin red');

    //Si el recorrido llamara a un QUINTO método, el falso no lo cubriría y saldría a la red.
    $reflexion = new \ReflectionClass($transporte);
    $sinCubrir = [];
    foreach (['createBatchContacts', 'createSegment', 'createEmailTemplate', 'sendEmail'] as $metodo) {
        if ($reflexion->getMethod($metodo)->getDeclaringClass()->getName() === MauticEmailAdapter::class) {
            $sinCubrir[] = $metodo;
        }
    }
    $check($sinCubrir === [], 'los cuatro métodos que usa el recorrido están sobreescritos',
        implode(', ', $sinCubrir));
    $check(function_exists('pcsphp_mautic_batch_flow'), 'y el recorrido es el mismo que usa la mitad real');

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El envío masivo de Mautic contra un transporte falso: lotes, listas y caminos de error.')->setEffects([CliActions::EFFECT_FILES])->register();
