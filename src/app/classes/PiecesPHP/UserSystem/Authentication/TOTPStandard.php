<?php
/**
 * TOTPStandard.php
 */
namespace PiecesPHP\UserSystem\Authentication;

use PragmaRX\Google2FA\Google2FA;

/**
 * TOTPStandard.
 *
 * @package     PiecesPHP\UserSystem\Authentication
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class TOTPStandard
{
    /**
     * Google2FA object
     * @var Google2FA
     */
    private $google2fa;
    /**
     * Clave secreta
     * @var string
     */
    protected $secret;

    /**
     * @param string $secret
     */
    public function __construct(string $secret)
    {
        $this->google2fa = new Google2FA();
        $this->secret = $secret;
    }

    /**
     * Generar un código TOTP basado en el tiempo actual.
     * @return string
     */
    public function generateTOTP()
    {
        return $this->google2fa->getCurrentOtp($this->secret);
    }

    /**
     * Verificar si el código TOTP proporcionado es válido.
     *
     * @param string $code Código TOTP introducido por el usuario
     * @param string $secret Secreto compartido en formato base32
     * @param int $tolerance Intervalos de tiempo permitidos (por defecto 1, para manejar pequeños desfases de tiempo)
     * @return bool
     */
    public function verifyTOTP(string $code, string $secret, int $tolerance = 1)
    {
        return $this->google2fa->verifyKey($secret, $code, $tolerance);
    }

    /**
     * Generar la URL QR para que el usuario pueda escanear con Google Authenticator.
     * @param string $label Nombre de la cuenta o servicio
     * @param string $issuer Nombre del emisor
     * @return string
     */
    public function getQRCodeUrl(string $label, string $issuer)
    {
        return $this->google2fa->getQRCodeUrl($issuer, $label, $this->secret);
    }

    /**
     * Generar un secreto compartido en formato base32
     * @return string
     */
    public static function generateSecret()
    {
        return (new Google2FA())->generateSecretKey();
    }
}
