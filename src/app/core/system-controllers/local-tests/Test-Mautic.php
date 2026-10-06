<?php

//Prueba la INTEGRACIÓN con Mautic, no la ENTREGA: Mautic encola el envío y lo entrega su consumidor
//(`messenger:consume email`), que aquí no corre, así que el correo no llega al sumidero. 313.

use API\Adapters\MauticEmailAdapter;
use PiecesPHP\Core\BaseController;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\CliActions;

$langGroup = 'TestPCSPHP-Lang';
$cliArguments = TerminalData::instance()->arguments();
$cliTaskName = 'tests';
$cliTaskFlag = 'mautic-batch-send';
$cliTaskDescription = "Prueba de envío masivo de correos con Mautic: la integración, no la entrega.";
CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) use ($langGroup) {

    echoTerminal("\e[33m[TEST:Mautic] La integración con Mautic: contactos, segmento, plantilla y la orden de envío\e[39m");
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
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal(' ');
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];
    };

    //Buscar credeenciales
    $crendentials = explode('::', getKeyFromSecureKeys('mautic'));
    $baseURL = $crendentials[0] ?? null;
    $clientID = $crendentials[1] ?? null;
    $clientSecret = $crendentials[2] ?? null;
    $fromEmail = $crendentials[3] ?? null;

    //La credencial es la de prueba del PO (ADR 0042): su Mautic rechaza crear una nuestra y su CSRF no se fuerza.
    $completas = $baseURL !== null && $clientID !== null && $clientSecret !== null && $fromEmail !== null;
    if (!$check($completas, 'c0 la línea de secure-keys/mautic trae los cuatro campos separados por «::»', 'campos: ' . count($crendentials))) {
        return $balance();
    }

    //Controlador
    $controller = new BaseController();
    $basePathView = realpath(__DIR__ . '/../../system-views');
    if ($basePathView !== false) {
        $controller->setViewDir($basePathView);
    }

    //Listado de personas
    $emails = include __DIR__ . '/../test-data/persons.php';

    //Configuración de Mautic
    $mauticAdapter = new MauticEmailAdapter((string) $baseURL, (string) $clientID, (string) $clientSecret);
    //El prefijo lleva «zz-» para que lo que esta prueba cree en una instalación ajena se reconozca y se pueda
    //limpiar, como exige el ADR 0040 §1. Antes era «AutomaticTestingSend_» y no se distinguía de lo del dueño.
    $prefix = 'zz-prueba-mautic-';

    //El canario NO puede ser solo el token: `getAccessToken()` lo sirve de una CACHÉ con `expires_at`, así que
    //devuelve uno válido aunque la URL no responda (medido: con la base en un puerto cerrado, pasaba igual).
    $estadoCanario = null;
    try {
        $clienteCanario = $mauticAdapter->httpClientWithApiKeyHeader();
        $clienteCanario->timeout(10);
        $clienteCanario->request('/api/contacts', 'GET', ['limit' => 1]);
        $estadoCanario = $clienteCanario->getResponseStatus();
    } catch (\Throwable $e) {
        $estadoCanario = null;
    }
    if (!$check((int) $estadoCanario === 200, 'c1 CANARIO: su API responde 200 a una lectura con esta credencial', 'estado ' . var_export($estadoCanario, true) . ': la credencial, la URL o su API fallan')) {
        return $balance();
    }

    //El recorrido vive en `Mautic-BatchFlow.php` y lo comparte con la mitad que NO sale a
    //la red, `unit-tests:core/mautic-batch-logic`. Aquí solo se le da el transporte real.
    $response = pcsphp_mautic_batch_flow($mauticAdapter, $emails, (string) $fromEmail, $controller, $prefix, $langGroup);

    $extra = is_array($response['extra_data'] ?? null) ? $response['extra_data'] : [];
    $mensaje = (string) ($response['message'] ?? '');

    //Cada paso del recorrido deja su propio mensaje de fallo: si uno no salió, esto FALLA ALTO en vez
    //de terminar con un silencio que parecía un acierto (hasta el 2026-10-02 no se miraba nada).
    echoTerminal(' ');
    echoTerminal('[1] El recorrido completo');
    $check(($response['success'] ?? false) === true, '1a los cuatro pasos salieron bien', $mensaje);
    $check(count((array) ($extra['contactIDs'] ?? [])) === count((array) $emails), '1b se creó un contacto por cada persona de la lista', count((array) ($extra['contactIDs'] ?? [])) . ' de ' . count((array) $emails));
    $check(($extra['segmentID'] ?? null) !== null, '1c el segmento se creó', var_export($extra['segmentID'] ?? null, true));
    $check(($extra['templateEmailID'] ?? null) !== null, '1d la plantilla de correo se creó', var_export($extra['templateEmailID'] ?? null, true));
    $check((int) ($extra['sentCount'] ?? 0) > 0, '1e Mautic aceptó la orden de envío y dijo a cuántos', (string) ($extra['sentCount'] ?? 0));

    echoTerminal(' ');
    echoTerminal('[2] Lo que esta prueba NO cubre, y hay que decirlo');
    echoTerminal('   La ENTREGA no se verifica: Mautic encola el correo y lo entrega su consumidor');
    echoTerminal('   (bin/console messenger:consume email). Sin ese consumidor arriba, nada llega al');
    echoTerminal('   sumidero, y esta prueba no puede afirmar que el correo salió de Mautic.');

    //─── z · Lo que esta prueba creó en una instalación AJENA se retira, y solo eso ──────────────────
    echoTerminal(' ');
    echoTerminal('[z] Limpieza de lo creado en Mautic');
    //Se borra POR LOS IDENTIFICADORES que devolvió esta corrida, nunca por nombre ni por prefijo: si el
    //recorrido falló antes, no hay ids y no se borra nada de nadie.
    $borrados = ['contacts' => 0, 'segments' => 0, 'emails' => 0];
    $fallosDeBorrado = [];
    $borrar = function (string $recurso, int $id) use ($mauticAdapter, &$borrados, &$fallosDeBorrado): void {
        try {
            $cliente = $mauticAdapter->httpClientWithApiKeyHeader();
            $cliente->request("/api/{$recurso}/{$id}/delete", 'DELETE');
            $estado = $cliente->getResponseStatus();
            if ((int) $estado === 200) {
                $borrados[$recurso]++;
            } else {
                $fallosDeBorrado[] = "{$recurso}/{$id} → HTTP " . var_export($estado, true);
            }
        } catch (\Throwable $e) {
            $fallosDeBorrado[] = "{$recurso}/{$id} → " . get_class($e);
        }
    };
    foreach ((array) ($extra['contactIDs'] ?? []) as $contactID) {
        $borrar('contacts', (int) $contactID);
    }
    if (($extra['segmentID'] ?? null) !== null) {
        $borrar('segments', (int) $extra['segmentID']);
    }
    if (($extra['templateEmailID'] ?? null) !== null) {
        $borrar('emails', (int) $extra['templateEmailID']);
    }
    echoTerminal("   borrados: {$borrados['contacts']} contacto(s), {$borrados['segments']} segmento(s), {$borrados['emails']} plantilla(s) de correo");
    if ($fallosDeBorrado !== []) {
        echoTerminal('   NO se pudieron borrar: ' . implode(' · ', $fallosDeBorrado));
    }
    $check($fallosDeBorrado === [], 'z1 nada de lo que esta prueba creó se queda en la instalación ajena', implode(' · ', $fallosDeBorrado));

    return $balance();

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_NETWORK, CliActions::EFFECT_EMAIL])->register();
