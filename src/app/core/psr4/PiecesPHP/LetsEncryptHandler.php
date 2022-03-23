<?php
/**
 * LetsEncryptHandler.php
 */

namespace PiecesPHP;

use LEClient\LEClient;
use LEClient\LEOrder;
use Psr\Log\LoggerInterface;

/**
 * LetsEncryptHandler.
 *
 * @package     PiecesPHP
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class LetsEncryptHandler implements LoggerInterface
{

    /**
     * @var string
     */
    private $clientNotInstantiatedMessage = 'El cliente no ha sido inicializado';
    /**
     * @var string
     */
    private $orderNotCreateMessage = 'No ha sido creada una orden';
    /**
     * @var string
     */
    private $directoryRootNotSetMessage = 'El directorio raíz no ha sido configurado';

    /**
     * @var array $configurations
     * @var string $configurations['domain']
     * @var string $configurations['email']
     * @var string $configurations['directoryRoot'] Directorio raíz de trabajo, relativo al directorio de ejecución
     * @var string $configurations['challengePath'] Directorio de archivo de autorización HTTP
     */
    protected $configurations = [
        'domain' => '',
        'email' => '',
        'directoryRoot' => 'pcsphp-certs',
        'challengePath' => null,
    ];

    /**
     * Modo sandbox
     *
     * @var bool
     */
    protected $testMode = false;

    /**
     * @var LEClient
     */
    protected $client = null;

    /**
     * @var LEOrder
     */
    protected $order = null;

    /**
     * @var array
     */
    protected $logs = [
        'emergency' => [],
        'alert' => [],
        'critical' => [],
        'error' => [],
        'warning' => [],
        'notice' => [],
        'info' => [],
        'debug' => [],
        'log' => [],
    ];

    /**
     * Logs contexts
     *
     * @var array
     */
    protected $logsContexts = [
        'emergency' => [],
        'alert' => [],
        'critical' => [],
        'error' => [],
        'warning' => [],
        'notice' => [],
        'info' => [],
        'debug' => [],
        'log' => [],
    ];

    /**
     * @param string $domain
     * @param string $email
     * @param string $challengePath
     * @param string $directoryRoot
     * @return static
     */
    public function __construct(string $domain, string $email, string $challengePath, string $directoryRoot = null)
    {

        $this->domain($domain);
        $this->email($email);
        $this->challengePath($challengePath);
        $this->directoryRoot($directoryRoot);

    }

    /**
     * Inicializa el cliente ACME
     * @return static
     */
    public function init()
    {

        $apiURL = $this->testMode() ? LEClient::LE_STAGING : LEClient::LE_PRODUCTION;
        $certificateKeys = $this->appendToDirectoryRoot($this->domain() . '/keys/');
        $accountKeys = '__account/';

        $this->client = new LEClient(
            [$this->email()],
            $apiURL,
            LEClient::LOG_OFF,
            $certificateKeys,
            $accountKeys
        );

        return $this;
    }

    /**
     * Revisa las autorizaciones pendientes y las intenta autorizar
     * @return static
     */
    public function checkPendingAuthorizations()
    {

        if ($this->order === null) {
            throw new \Exception($this->orderNotCreateMessage);
        }

        $order = $this->order;

        // Verificar si no han sido autorizadas todas las órdenes
        if (!$order->allAuthorizationsValid()) {

            // Obtener todas las autorizaciones pendientes de tipo HTTP
            $pending = $order->getPendingAuthorizations(LEOrder::CHALLENGE_TYPE_HTTP);

            // Revisar pendientes
            if (!empty($pending)) {

                foreach ($pending as $challenge) {

                    // Directorio de archivo del reto HTTP
                    $folder = str_replace('//', '/', rtrim($this->challengePath() . '/.well-known/acme-challenge', '/'));

                    if (!file_exists($folder)) {
                        mkdir($folder, 0777, true);
                    }

                    // Crear archivo de autorización
                    file_put_contents($folder . '/' . $challenge['filename'], $challenge['content']);

                    // Hacer la verificación de LetsEncrypt
                    $order->verifyPendingOrderAuthorization($challenge['identifier'], LEOrder::CHALLENGE_TYPE_HTTP, false);

                }

            }

        }

        return $this;
    }

    /**
     * Intenta generar los certificados
     * @return bool
     */
    public function certify()
    {

        $this->checkPendingAuthorizations();

        $order = $this->order;
        $result = false;

        // Check once more whether all authorizations are valid before we can finalize the order.
        if ($order->allAuthorizationsValid()) {
            // Finalize the order first, if that is not yet done.
            if (!$order->isFinalized()) {
                $result = $order->finalizeOrder();
            }

            // Check whether the order has been finalized before we can get the certificate. If finalized, get the certificate.
            if ($order->isFinalized()) {
                $result = $order->getCertificate();
            }

        }

        return $result;
    }

    /**
     * Crea u obtiene la orden si existe
     * @return static
     */
    public function order()
    {
        if ($this->client === null) {
            throw new \Exception($this->clientNotInstantiatedMessage);
        }
        $domains = [
            $this->domain(),
            'www.' . $this->domain(),
        ];
        $this->order = $this->client->getOrCreateOrder($this->domain(), $domains);
        return $this;
    }

    /**
     * @param bool $value
     * @return bool|static
     */
    public function testMode(bool $value = null)
    {

        if ($value === null) {
            return $this->testMode;
        } else {
            $this->testMode = $value;
        }

        return $this;

    }

    /**
     * @param string $value
     * @return string|static
     */
    public function directoryRoot(string $value = null)
    {

        $nameProperty = 'directoryRoot';

        if ($value === null) {

            $value = $this->configurations[$nameProperty];

            if ($value === null) {
                throw new \Exception($this->directoryRootNotSetMessage);
            }

            return $value;

        } else {
            $this->configurations[$nameProperty] = $value;
        }

        return $this;

    }

    /**
     * @param string $value
     * @return string|static
     */
    public function challengePath(string $value = null)
    {

        $nameProperty = 'challengePath';

        if ($value === null) {

            return $this->configurations[$nameProperty];

        } else {
            $this->configurations[$nameProperty] = $value;
        }

        return $this;

    }

    /**
     * @param string $value
     * @return string|static
     */
    public function domain(string $value = null)
    {

        $nameProperty = 'domain';

        if ($value === null) {
            return $this->configurations[$nameProperty];
        } else {
            $this->configurations[$nameProperty] = self::cleanDomain($value);
        }

        return $this;

    }

    /**
     * @param string $value
     * @return string|static
     */
    public function email(string $value = null)
    {

        $nameProperty = 'email';

        if ($value === null) {
            return $this->configurations[$nameProperty];
        } else {
            $this->configurations[$nameProperty] = $value;
        }

        return $this;

    }

    /**
     * @param string $value
     * @return string
     */
    private function appendToDirectoryRoot(string $value)
    {
        $value = $this->directoryRoot() . \DIRECTORY_SEPARATOR . $value;
        $value = trim($value, \DIRECTORY_SEPARATOR);
        $value = str_replace(\DIRECTORY_SEPARATOR . \DIRECTORY_SEPARATOR, \DIRECTORY_SEPARATOR, $value);
        return $value;
    }

    /**
     * @param string $value
     * @return string
     */
    public static function cleanDomain(string $url)
    {
        $domainParse = parse_url($url);

        if (is_array($domainParse) && isset($domainParse['host'])) {
            $url = $domainParse['host'];
        }

        $urlWhioutWWW = preg_replace("/^www\./i", "", $url);

        $url = $urlWhioutWWW !== null ? $urlWhioutWWW : $url;

        return $url;

    }

    /**
     * @return string[]
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * Return the log
     *
     * @param string $type
     * @return string[]
     */
    public function getLog(string $type)
    {
        return isset($this->logs[$type]) ? $this->logs[$type] : [];
    }

    /**
     * @return array[]
     */
    public function getLogsContexts()
    {
        return $this->logsContexts;
    }

    /**
     * Return the log contexts
     *
     * @param string $type
     * @return array[]
     */
    public function getLogContexts(string $type)
    {
        return isset($this->logsContexts[$type]) ? $this->logsContexts[$type] : [];
    }

    /**
     * Multipurpose logger
     *
     * @param string $type
     * @param string $message
     * @param array $context
     * @return void
     */
    public function toLog(string $type, string $message, array $context = array())
    {

        $sign = "(ID: " . uniqid() . " Date: " . date('d-m-Y h:i:s A') . ")";

        $this->logs[$type][] = $message . " ({$sign})";
        $this->logsContexts[$type][$sign] = $context;

    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function emergency($message, array $context = array())
    {
        $this->toLog('emergency', $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function alert($message, array $context = array())
    {
        $this->toLog('alert', $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function critical($message, array $context = array())
    {
        $this->toLog('critical', $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function error($message, array $context = array())
    {
        $this->toLog('error', $message, $context);
    }
    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function warning($message, array $context = array())
    {
        $this->toLog('warning', $message, $context);
    }
    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function notice($message, array $context = array())
    {
        $this->toLog('notice', $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function info($message, array $context = array())
    {
        $this->toLog('info', $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    public function debug($message, array $context = array())
    {
        $this->toLog('debug', $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        $this->toLog('log', $message, $context);
    }

}
