<?php

/**
 * MailDemoTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Mailer;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\MailCatalog\MailCatalog;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * MailDemoTask — manda un correo de cada plantilla del catálogo a la bandeja de pruebas, para verlos.
 *
 * Las suites limpian lo que mandan (ADR 0047), así que el registro de correos de una instalación de
 * desarrollo queda vacío. Esto deja correos para mirar: cada plantilla, pintada con datos de ejemplo y
 * enviada por el `Mailer` real, que la anota en el registro con su cuerpo. **No ejecuta ningún flujo**:
 * no crea códigos, tokens ni aprobaciones; solo pinta y envía. Marca `demo-`, no `zz-`: no cuenta como
 * resto de prueba.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class MailDemoTask extends TerminalTaskAbstract
{

    /**
     * A quién van todos. `localhost.test` no resuelve fuera de esta máquina.
     *
     * @var string
     */
    const RECIPIENT = 'demo-correos@localhost.test';

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }
        $this->description = new StringArray([
            "Manda un correo de cada plantilla del catálogo a la bandeja de pruebas, para poder verlos.\r\n",
            "\tSOLO en una instalación `local` y SOLO si la entrega efectiva del correo es «Retenido»:\r\n",
            "\tsi no, se niega antes de mandar nada. Van a " . self::RECIPIENT . ", con datos de ejemplo,\r\n",
            "\tpor el Mailer real: quedan en Mailpit (o en el buzón en disco) y en el registro de correos,\r\n",
            "\tcon su cuerpo. No borra nada: cada vez, otra tanda.\r\n",
            "\tParámetros:\r\n",
            "\t  N/A",
        ]);
        $this->route = "{$startRoute}/mail-demo[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'mail-demo';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    /**
     * Las dos guardas, en orden. **Las dos fallan cerrado**: sin `environment.php` no es `local`, y si la
     * entrega efectiva es la real, se niega aunque el entorno sea `local` (`mail-real-en-local` existe
     * porque eso pasa).
     *
     * @return array<int,array{ok:bool,line:string}>
     */
    public static function guards(): array
    {
        $local = AppEnvironment::isConfigured() && AppEnvironment::get() === AppEnvironment::LOCAL;
        $sink = MailDelivery::goesToSink();
        return [
            [
                'ok' => $local,
                'line' => $local
                    ? 'entorno: local.'
                    : "\e[31mentorno: NO local\e[39m — solo se manda en una instalación de desarrollo. Declare `return 'local';` en src/" . AppEnvironment::FILE_RELATIVE_PATH . '.',
            ],
            [
                'ok' => $sink,
                'line' => $sink
                    ? 'entrega: retenida; nada sale de esta máquina.'
                    : "\e[31mentrega: REAL\e[39m — estos correos saldrían de esta máquina. Ponga «Entrega del correo» en «Retenido» en Configuración → Sistema → Correo, o en «Según el entorno».",
            ],
        ];
    }

    /**
     * Un correo de cada definición del catálogo de correos (MailCatalog), pintado con sus datos de muestra por el mismo
     * camino que su envío real. **No envía nada**.
     *
     * @return array<int,array{template:string,subject:string,body:string}>
     */
    public static function catalog(): array
    {
        $catalogo = [];
        foreach (MailCatalog::all() as $key => $definition) {
            $muestra = MailCatalog::preview($definition);
            $catalogo[] = ['template' => (string) $key, 'subject' => $muestra['subject'], 'body' => $muestra['body']];
        }
        return $catalogo;
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = 'Correos de muestra';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");

        //LO PRIMERO, antes de pintar nada: si una guarda no deja, no se manda ni uno.
        $permitido = true;
        foreach (self::guards() as $guard) {
            echoTerminal('   ' . $guard['line']);
            $permitido = $permitido && $guard['ok'];
        }
        if (!$permitido) {
            echoTerminal("\e[31m*** {$titleTask}, NO SE MANDA NINGUNO ***\e[39m");
            exit(2);
        }

        $mailConfig = new MailConfig();
        $remitente = $mailConfig->user();
        $remitente = is_string($remitente) && filter_var($remitente, \FILTER_VALIDATE_EMAIL) !== false ? $remitente : 'demo-remitente@localhost.test';
        $nombreRemitente = $mailConfig->name();
        $nombreRemitente = is_string($nombreRemitente) ? $nombreRemitente : '';
        $porResultado = [];
        $catalogo = self::catalog();
        foreach ($catalogo as $correo) {
            try {
                $mailer = new Mailer();
                $mailer->setFrom($remitente, $nombreRemitente);
                $mailer->addAddress(self::RECIPIENT, 'Demo');
                $mailer->isHTML(true);
                $mailer->Subject = mb_convert_encoding($correo['subject'], 'UTF-8');
                $mailer->Body = $correo['body'];
                $mailer->AltBody = strip_tags($correo['body']);
                //RETORNO-IGNORADO: a dónde fue lo dice la fila del registro, que se lee justo después.
                $mailer->send();
            } catch (\Throwable $throwable) {
                echoTerminal("   \e[31m{$correo['template']}: no se pudo mandar\e[39m — " . get_class($throwable) . ': ' . mb_substr($throwable->getMessage(), 0, 200));
            }
            $fila = MailLogMapper::latest(1)[0] ?? null;
            $resultado = $fila !== null && str_contains((string) $fila->recipients, self::RECIPIENT) ? (string) $fila->result : 'sin fila en el registro';
            $porResultado[$resultado] = ($porResultado[$resultado] ?? 0) + 1;
            $destino = match ($resultado) {
                MailLogMapper::RESULT_DELIVERED => 'a Mailpit',
                MailLogMapper::RESULT_OUTBOX => 'al buzón en disco (' . MailDelivery::outboxDirectory() . ')',
                default => "\e[31m{$resultado}\e[39m",
            };
            echoTerminal("   {$correo['template']} → {$destino}");
        }

        echoTerminal('');
        $resumen = [];
        foreach ($porResultado as $resultado => $cuantos) {
            $resumen[] = "{$cuantos} {$resultado}";
        }
        echoTerminal('   ' . count($catalogo) . ' correo(s) a ' . self::RECIPIENT . ': ' . implode(', ', $resumen) . '.');
        echoTerminal('   Ábralos en Sistema → Registro de correos, o en Mailpit (http://127.0.0.1:8025).');
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new MailDemoTask($startRoute, $namePrefix);
        return new Route(
            $instance->route,
            $instance->controller,
            $instance->name,
            $instance->method,
            $instance->requireLogin,
            null,
            $instance->rolesAllowed->getArrayCopy(),
            $instance->defaultParamsValues,
            $instance->middlewares
        );
    }
}
