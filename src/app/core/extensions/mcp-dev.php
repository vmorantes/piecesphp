<?php

/**
 * @pcsphp-config framework
 * Qué conviene editar aquí: nada: es el servidor MCP para quien desarrolla (bin/mcp, ADR 0075). Solo lee.
 */

use PiecesPHP\Core\Mcp\DevServer;
use PiecesPHP\Terminal\CliActions;

CliActions::make('mcp-dev', function ($args) {

    //Por la salida estándar solo va JSON-RPC, una respuesta por línea: cualquier otra cosa rompe al cliente.
    $write = function (array $response): void {
        //RETORNO-IGNORADO: si la salida estándar se cerró, el cliente ya no está y no hay a quién avisar.
        fwrite(STDOUT, json_encode($response, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE) . "\n");
    };

    if (!is_local()) {
        //Solo en local: se contesta UNA vez, al primer mensaje con id, y se sale con 1; sin mensaje, el motivo a la salida de error.
        $line = fgets(STDIN);
        $message = is_string($line) ? json_decode($line, true) : null;
        if (is_array($message) && array_key_exists('id', $message)) {
            $write(['jsonrpc' => '2.0', 'id' => $message['id'], 'error' => ['code' => -32000, 'message' => 'bin/mcp solo funciona en local (--local).']]);
        } else {
            //RETORNO-IGNORADO: el aviso a la salida de error es lo último que se hace; se sale con 1 igual.
            fwrite(STDERR, "bin/mcp solo funciona en local (--local).\n");
        }
        exit(1);
    }

    while (($line = fgets(STDIN)) !== false) {
        if (trim($line) === '') {
            continue;
        }
        try {
            $message = json_decode($line, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            $write(DevServer::parseError());
            continue;
        }
        $response = is_array($message) ? DevServer::handle($message) : DevServer::parseError();
        if ($response !== null) {
            $write($response);
        }
    }

    return ['success' => true, 'message' => ''];

})->setDescription('El servidor MCP de solo lectura para quien desarrolla (bin/mcp): rutas, permisos, la API y la documentación. JSON-RPC 2.0 por la entrada y la salida estándar. Solo en local.')->setEffects([CliActions::EFFECT_NONE])->register();
