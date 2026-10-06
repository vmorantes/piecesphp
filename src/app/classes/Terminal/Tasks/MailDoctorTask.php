<?php

/**
 * MailDoctorTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Email\MailDelivery;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Mailer;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\SystemStatus\Mappers\MailLogMapper;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;

/**
 * MailDoctorTask - Si el correo puede salir de aquí, y por dónde. NO envía nada.
 *
 * Existe para enterarse de que el SMTP o el puerto están mal **antes** de que haga falta el primer
 * correo, que hasta ahora se descubría cuando un usuario llamaba por teléfono (ADR 0043 §6).
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class MailDoctorTask extends TerminalTaskAbstract
{

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }
        $this->description = new StringArray([
            "Dice si el correo puede salir de esta instalación y por dónde. NO ENVÍA NINGÚN CORREO.\r\n",
            "\tPOR OMISIÓN NO TOCA LA RED: comprueba el entorno, la entrega declarada y la efectiva,\r\n",
            "\tel sumidero, el buzón en disco y el registro de correos, y dice que el SMTP no se probó.\r\n",
            "\tParámetros:\r\n",
            "\t  horas=<n>  ventana del registro de correos. Por omisión, la del aviso del panel\r\n",
            "\t  smtp=yes   ADEMÁS abre una conexión al SMTP configurado y la corta sin enviar nada.\r\n",
            "\t             Si ese SMTP es un servidor de internet, esto SALE A LA RED. Un agente\r\n",
            "\t             necesita el permiso expreso del dueño del proyecto cada vez que lo use\r\n",
            "\t             (ADR 0046 §3). Si el SMTP configurado apunta a esta misma máquina, la\r\n",
            "\t             sonda se hace sin pedir nada: no hay red por medio.\r\n",
        ]);
        $this->route = "{$startRoute}/mail-doctor[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'mail-doctor';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    /**
     * La línea del cuerpo del diagnóstico. Con la clave de relleno lo dice en los dos casos (ADR 0052 §3):
     * en `local` se guarda pero no protege; fuera de `local` no se guarda.
     *
     * @param bool $hasBodyColumn
     * @return string
     */
    public static function bodyLine(bool $hasBodyColumn): string
    {
        if (!$hasBodyColumn) {
            return "\e[33mNO se guarda\e[39m: falta la columna; aplique " . MailLogMapper::BODY_UPDATE_FILE;
        }
        if (MailLogMapper::bodyKey() === null) {
            return "\e[33mNO se guarda\e[39m: la clave de la aplicación es la de relleno; genere una con bin/cli generate-app-key";
        }
        if (MailLogMapper::appKeyIsPlaceholder()) {
            return "se guarda, pero con la clave de relleno: \e[33mno protege\e[39m (vale en local; genere una con bin/cli generate-app-key)";
        }
        return 'se guarda, cifrado';
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        //La MISMA ventana que el aviso del panel: dos cifras distintas harían que el panel avise de
        //algo que esta tarea no encuentra (MailLogMapper::ALERT_WINDOW_HOURS).
        $horas = MailLogMapper::ALERT_WINDOW_HOURS;
        $sondaPedida = false;
        foreach ((array) ($_SERVER['argv'] ?? []) as $argument) {
            if (!is_string($argument)) {
                continue;
            }
            if (preg_match('/^horas=(\d{1,4})$/', $argument, $matches) === 1) {
                $horas = max(1, (int) $matches[1]);
            }
            //Solo `smtp=yes`, exacto: un `smtp=1` que conectara por parecerse saldría a la red sin
            //haberlo pedido con la palabra que dice la documentación.
            if ($argument === 'smtp=yes') {
                $sondaPedida = true;
            }
        }

        $mailConfig = new MailConfig();
        $entregaEfectiva = MailDelivery::resolved();
        $retenido = $entregaEfectiva === MailDelivery::SINK;

        echoTerminal("\e[33m*** El correo de esta instalación ***\e[39m");
        echoTerminal('');
        echoTerminal('Entorno:            ' . (AppEnvironment::isConfigured()
            ? AppEnvironment::get()
            : "\e[31msin declarar\e[39m (falta app/config/environment.php)"));
        echoTerminal('Entrega declarada:  ' . MailDelivery::declared());
        echoTerminal('Entrega efectiva:   ' . ($retenido
            ? "\e[33mretenido\e[39m: el correo NO sale de esta máquina"
            : "\e[31mreal\e[39m: el correo SALE por el SMTP de abajo") . '  (porque: ' . MailDelivery::reason() . ')');
        echoTerminal('Marca de la instalación: ' . (MailDelivery::installationTag() !== '' ? MailDelivery::installationTag() : 'ninguna'));
        echoTerminal('');

        //El sumidero, que es a donde va el correo retenido.
        $sumideroHost = is_string($mailConfig->testHost()) ? (string) $mailConfig->testHost() : '127.0.0.1';
        $sumideroPuerto = is_int($mailConfig->testPort()) ? (int) $mailConfig->testPort() : 1025;
        $sumideroVivo = Mailer::probeSMTP($sumideroHost, $sumideroPuerto);
        echoTerminal("Sumidero de pruebas: {$sumideroHost}:{$sumideroPuerto} → " . ($sumideroVivo
            ? "\e[32mresponde\e[39m"
            : "\e[31mno responde\e[39m" . ($retenido ? ' (el correo retenido irá al buzón)' : '')));

        //El buzón, que es la reserva cuando el correo está retenido y el sumidero no está.
        $buzon = MailDelivery::outboxDirectory();
        $enBuzon = is_dir($buzon) ? count((array) glob("{$buzon}/*.eml")) : 0;
        echoTerminal("Buzón en disco:      {$buzon} → {$enBuzon} mensaje(s) sin entregar"
            . ($enBuzon > 0 ? "  (se vacía con: bin/cli clean-logs)" : ''));
        echoTerminal('');

        //El SMTP real. La sonda NO se hace por omisión (ADR 0046 §1): en un despliegue de verdad
        //el SMTP está en internet, así que conectar aquí sería salir a la red sin pedirlo.
        $smtpHost = is_string($mailConfig->host()) ? (string) $mailConfig->host() : '';
        $puertoCrudo = $mailConfig->port();
        $smtpPuerto = is_int($puertoCrudo) || is_string($puertoCrudo) ? (int) $puertoCrudo : 0;
        echoTerminal("SMTP configurado:    {$smtpHost}:{$smtpPuerto}");

        $smtpEsLocal = MailDelivery::smtpIsLoopback($smtpHost);
        $sonda = $sondaPedida || $smtpEsLocal;
        $smtpResponde = null;

        if ($sonda) {
            //`probeSMTP()` y no `checkSettedSMTP()`: ese mira la configuración ya cargada, que con el
            //correo retenido apunta al sumidero, y daba por bueno un SMTP cerrado (medido el 2026-10-03).
            $smtpResponde = Mailer::probeSMTP($smtpHost, $smtpPuerto);
            echoTerminal('                     → ' . ($smtpResponde
                ? "\e[32mcontesta\e[39m (no se envió ningún correo, y no se comprobaron las credenciales)"
                : "\e[31mNO contesta o rechaza la conexión\e[39m: con la entrega real, el correo no saldría"));
            echoTerminal('                       sonda hecha porque ' . ($smtpEsLocal
                ? 'ese SMTP apunta a esta máquina: no hay red por medio'
                : 'se pidió con smtp=yes'));
        } else {
            //Un diagnóstico que calla lo que no mira miente por omisión (ADR 0046 §2).
            echoTerminal("                     → \e[33mSIN COMPROBAR\e[39m: la sonda no se hizo, así que esta tarea no ha tocado la red.");
            echoTerminal('                       Para probarlo: bin/cli mail-doctor smtp=yes');
            echoTerminal('                       OJO: eso abre una conexión a ese SMTP. Si está en internet, sale a la red.');
        }
        echoTerminal('');

        //Y lo que contesta a «¿está saliendo el correo?»: el registro.
        $cuentas = MailLogMapper::recentCounts($horas);
        echoTerminal("Registro de correos, últimas {$horas} h:");
        echoTerminal('   entregados: ' . $cuentas['delivered']);
        echoTerminal('   al buzón:   ' . $cuentas['outbox']);
        echoTerminal('   fallidos:   ' . ($cuentas['failed'] > 0
            ? "\e[31m{$cuentas['failed']}\e[39m"
            : (string) $cuentas['failed']));
        $conCuerpo = MailLogMapper::tableExists() && MailLogMapper::bodyColumnExists();
        echoTerminal('   cuerpo:     ' . self::bodyLine($conCuerpo));
        echoTerminal('');

        //El veredicto, que es lo que se lee de un vistazo.
        $problemas = [];
        if (!AppEnvironment::isConfigured()) {
            $problemas[] = 'el entorno no está declarado, así que el correo se retiene';
        }
        if ($retenido && AppEnvironment::isConfigured() && AppEnvironment::get() === AppEnvironment::PRODUCTION) {
            $problemas[] = 'es producción y el correo está retenido: nadie recibe nada';
        }
        if (!$retenido && AppEnvironment::isConfigured() && AppEnvironment::get() === AppEnvironment::LOCAL) {
            $problemas[] = 'es local y el correo sale de verdad';
        }
        if (!$retenido && $smtpResponde === false) {
            $problemas[] = 'la entrega es real y el SMTP no responde: el correo no está saliendo';
        }
        //Con la entrega real y sin sonda, «el correo puede salir» es EXACTAMENTE lo que no se ha
        //comprobado: decir que nada pide atención sería el veredicto de un diagnóstico que no miró.
        if (!$retenido && $smtpResponde === null) {
            $problemas[] = 'la entrega es real y el SMTP no se comprobó: para saber si el correo sale, bin/cli mail-doctor smtp=yes';
        }
        if ($retenido && !$sumideroVivo && $enBuzon > 0) {
            $problemas[] = "hay {$enBuzon} mensaje(s) esperando en el buzón";
        }
        if ($cuentas['failed'] > 0) {
            $problemas[] = "{$cuentas['failed']} envío(s) han fallado en las últimas {$horas} h";
        }
        if (MailLogMapper::tableExists() && !$conCuerpo) {
            $problemas[] = 'el registro no guarda el cuerpo de los correos: aplique ' . MailLogMapper::BODY_UPDATE_FILE;
        }
        if ($conCuerpo && MailLogMapper::bodyKey() === null) {
            $problemas[] = 'el registro no guarda el cuerpo de los correos: la clave de la aplicación es la de relleno; genere una con bin/cli generate-app-key';
        }

        if (count($problemas) === 0) {
            //«Puede salir» con la entrega retenida sería lo contrario de lo que pasa: el correo NO
            //sale, y está bien que no salga. El veredicto dice lo que hay, no una frase de plantilla.
            echoTerminal($retenido
                ? "\e[32mVEREDICTO: el correo está RETENIDO a propósito y nada pide atención.\e[39m"
                : "\e[32mVEREDICTO: el correo puede salir y nada pide atención.\e[39m");
        } else {
            echoTerminal("\e[31mVEREDICTO: " . count($problemas) . " cosa(s) piden atención:\e[39m");
            foreach ($problemas as $problema) {
                echoTerminal('   - ' . $problema);
            }
        }
        echoTerminal('');
        echoTerminal('*** El correo de esta instalación, tarea finalizada ***');
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new MailDoctorTask($startRoute, $namePrefix);
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
