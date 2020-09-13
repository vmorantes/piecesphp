<?php
/**
 * LetsEncryptHandler.php
 */

namespace PiecesPHP;

use LEClient\LEClient;
use LEClient\LEOrder;

/**
 * LetsEncryptHandler.
 *
 * @package     PiecesPHP
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class LetsEncryptHandler
{

    const LOG_OFF = LEClient::LOG_OFF; // Logs no messages or faults, except Runtime Exceptions.
    const LOG_STATUS = LEClient::LOG_STATUS; // Logs only messages and faults.
    const LOG_DEBUG = LEClient::LOG_DEBUG; // Logs messages, faults and raw responses from HTTP requests.

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
     * Configuraciones
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
     * Cliente
     *
     * @var LEClient
     */
    protected $client = null;

    /**
     * Orden
     *
     * @var LEOrder
     */
    protected $order = null;

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
     * @param int $logMode
     * @return static
     */
    public function init(int $logMode = LetsEncryptHandler::LOG_OFF)
    {

        $apiURL = $this->testMode() ? LEClient::LE_STAGING : LEClient::LE_PRODUCTION;
        $certificateKeys = $this->appendToDirectoryRoot($this->domain() . '/keys/');
        $accountKeys = '__account/';

        $this->client = new LEClient(
            [$this->email()],
            $apiURL,
            $logMode,
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

        // Check once more whether all authorizations are valid before we can finalize the order.
        if ($order->allAuthorizationsValid()) {
            // Finalize the order first, if that is not yet done.
            if (!$order->isFinalized()) {
                return $order->finalizeOrder();
            }

            // Check whether the order has been finalized before we can get the certificate. If finalized, get the certificate.
            if ($order->isFinalized()) {
                return $order->getCertificate();
            }

        }

        return false;
    }

    /**
     * Crea u obtiene la orden si existe
     * @param int $logMode
     * @return static
     */
    public function order()
    {
        if ($this->client === null) {
            throw new \Exception($this->clientNotInstantiatedMessage);
        }
        $this->order = $this->client->getOrCreateOrder($this->domain(), [$this->domain()]);
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
            $this->configurations[$nameProperty] = $value;
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

}
