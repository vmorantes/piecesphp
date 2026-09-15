<?php
/**
 * OTPHandler.php
 */
namespace PiecesPHP\UserSystem\Authentication;

use App\Model\UsersModel;
use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Mailer;
use PiecesPHP\UserSystem\Exceptions\SafeException;
use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;
use PiecesPHP\UserSystem\UserDataPackage;
use PiecesPHP\UserSystem\UserSystemFeaturesLang;

/**
 * OTPHandler.
 *
 * @package     PiecesPHP\UserSystem\Authentication
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class OTPHandler
{

    const LANG_GROUP = UserSystemFeaturesLang::LANG_GROUP;
    const METHOD_TOTP = OTPSecretsUsersMapper::METHOD_TOTP;
    const METHOD_ONE_USE_CODE = OTPSecretsUsersMapper::METHOD_ONE_USE_CODE;
    const METHODS = OTPSecretsUsersMapper::METHODS;

    /**
     * @param string $password
     * @param string $username
     * @return bool
     */
    public static function checkValidityOTP(string $password, string $username)
    {
        $valid = false;
        $userData = self::getUserDataByUsername($username);
        $userDataPackage = $userData !== null ? new UserDataPackage($userData->id) : null;
        if ($userDataPackage !== null) {
            $otpData = OTPSecretsUsersMapper::getOTPData($userData->id, OTPSecretsUsersMapper::METHOD_ONE_USE_CODE);
            if ($otpData !== null) {
                $now = new \DateTime();
                $expired = $now > $otpData->maxDate;
                $math = $password === $otpData->oneUseCode;

                if (!$expired) {
                    if ($math) {
                        $valid = true;
                    }
                }
            }
        }
        return $valid;
    }

    /**
     * @param string $username
     * @return void
     */
    public static function toExpireOTP(string $username)
    {
        $userData = self::getUserDataByUsername($username);
        $userDataPackage = $userData !== null ? new UserDataPackage($userData->id) : null;
        if ($userDataPackage !== null) {
            $otpData = OTPSecretsUsersMapper::getOTPData($userData->id, OTPSecretsUsersMapper::METHOD_ONE_USE_CODE);
            //Sin registro no hay código que caducar. NO crear uno: esta ruta no autentica.
            if ($otpData === null) {
                log_exception(new \Exception("toExpireOTP: el usuario {$userData->id} no tiene registro ONE_USE_CODE; no hay código que caducar."), false);
                return;
            }
            $otpData->maxDate = new \DateTime('2000-01-01');
            $otpData->oneUseCode = "";
            $otpData->update();
        }
    }

    /**
     * @param string $totp TOTP o código de seguridad
     * @param string $username
     * @return bool
     */
    public static function checkValidityTOTP(string $totp, string $username)
    {
        $valid = false;
        $userData = self::getUserDataByUsername($username);
        $userDataPackage = $userData !== null ? new UserDataPackage($userData->id) : null;
        //Sin registro TOTP no hay segundo factor configurado: «no válido» es la respuesta.
        if ($userDataPackage !== null && $userDataPackage->TOTPData !== null) {
            $secret = $userDataPackage->TOTPData->secret;
            $totpManager = new TOTPStandard($secret);
            $valid = $totpManager->verifyTOTP($totp, $secret, 1);
            if (!$valid) {
                $valid = password_verify($totp, $userDataPackage->TOTPData->twoAuthFactorSecurityCode);
            }
        }
        return $valid;
    }

    /**
     * @param BaseController $controller
     * @param string $username
     * @param string $relativeView
     * @throws SafeException Si el usuario no existe o ocurre algún error
     */
    public static function generateOTP(BaseController $controller, string $username, string $relativeView = 'mails/otp-code')
    {
        $userData = self::getUserDataByUsername($username);
        if ($userData !== null) {

            $code = generate_code(6, true);

            $minutes = OTPRateLimiter::config()['oneUseCodeMinutes'];
            $OTPCreated = OTPSecretsUsersMapper::setOTP($userData->id, $code, OTPSecretsUsersMapper::METHOD_ONE_USE_CODE, $minutes);

            if ($OTPCreated) {

                $subject = mb_convert_encoding((string) __(self::LANG_GROUP, 'Contraseña de un uso'), 'UTF-8') . ' - ' . Config::app_title();
                $bodyMessage = $controller->render($relativeView, [
                    'text' => __(self::LANG_GROUP, 'Contraseña de un solo uso'),
                    'note' => vsprintf(__(self::LANG_GROUP, 'Tiene una validez de %s minutos'), [$minutes]),
                    'code' => $code,
                ], false);
                $bodyMessage = mb_convert_encoding($bodyMessage, 'UTF-8');
                $mailer = new Mailer();
                $mailConfig = new MailConfig;
                $mailer->SMTPDebug = 2;
                $mailer->isHTML(true);
                $mailer->setFrom($mailConfig->user());
                $mailer->addAddress($userData->email);
                $mailer->Subject = mb_convert_encoding($subject, 'UTF-8');
                $mailer->Body = $bodyMessage;
                if (!$mailer->checkSettedSMTP() && !is_local()) {
                    $mailer->asGoDaddy(true);
                }
                $sended = $mailer->send();

                if (!$sended) {
                    throw new SafeException(__(self::LANG_GROUP, 'Ha ocurrido un error, el correo no pudo ser enviado. Aún puede ingresar con su contraseña de siempre.'));
                }

            } else {
                throw new SafeException(__(self::LANG_GROUP, 'Ha ocurrido un error, la contraseña no pudo ser creada. Aún puede ingresar con su contraseña de siempre.'));
            }

        } else {
            throw new SafeException(__(self::LANG_GROUP, 'El usuario no existe'), SafeException::USER_NOT_EXISTS);
        }
    }

    /**
     * @return string
     */
    public static function getCurrentUserTOTP()
    {
        $totp = "";
        $userDataPackage = getLoggedFrameworkUser();
        if ($userDataPackage !== null && $userDataPackage->TOTPData !== null) {
            $totpManager = new TOTPStandard($userDataPackage->TOTPData->secret);
            $totp = $totpManager->generateTOTP();
        }
        return $totp;
    }

    /**
     * @return string
     */
    public static function getCurrentUserQRData()
    {
        $qrData = "";
        $userDataPackage = getLoggedFrameworkUser();
        if ($userDataPackage !== null && $userDataPackage->TOTPData !== null) {
            $totpManager = new TOTPStandard($userDataPackage->TOTPData->secret);
            //twoAuthFactorAlias está vacío hasta que el usuario lo bautice: sin este respaldo, TypeError.
            $issuer = $userDataPackage->TOTPData->twoAuthFactorAlias;
            $issuer = is_string($issuer) && $issuer !== '' ? $issuer : (string) get_config('owner');
            $qrData = $totpManager->getQRCodeUrl($userDataPackage->username, $issuer);
        }
        return $qrData;
    }

    /**
     * @param int|null $userID
     * @return bool
     */
    public static function wasViewedCurrentUserQRData(?int $userID = null)
    {
        $wasViewed = false;
        $userDataPackage = getLoggedFrameworkUser();
        try {
            $userDataPackage = $userID !== null ? new UserDataPackage($userID) : $userDataPackage;
        } catch (\Exception) {
            $userDataPackage = null;
        }
        if ($userDataPackage !== null && $userDataPackage->TOTPData !== null) {
            $wasViewed = $userDataPackage->TOTPData->twoAuthFactorQRViewed == 1;
        }
        return $wasViewed;
    }

    /**
     * @param int|null $userID
     * @return bool
     */
    public static function isEnabled2FA(?int $userID = null)
    {
        $enabled = false;
        $userDataPackage = getLoggedFrameworkUser();
        try {
            $userDataPackage = $userID !== null ? new UserDataPackage($userID) : $userDataPackage;
        } catch (\Exception) {
            $userDataPackage = null;
        }
        if ($userDataPackage !== null) {
            $enabled = OTPSecretsUsersMapper::isEnabled2FA($userDataPackage->id);
        }
        return $enabled;
    }

    /**
     * @param bool $enable
     * @param string $securityCode
     * @param string|null $alias
     * @return TOTPStandard|null
     */
    public static function toggleCurrentUser2AF(bool $enable, string $securityCode, ?string $alias = null)
    {
        $totpManager = null;
        $userDataPackage = getLoggedFrameworkUser();
        if ($userDataPackage !== null) {
            OTPSecretsUsersMapper::toggle2FA($userDataPackage->id, $enable, $securityCode, $alias);
            if ($enable) {
                $userDataPackage = getLoggedFrameworkUser(true);
                if ($userDataPackage !== null && $userDataPackage->TOTPData !== null) {
                    $totpManager = new TOTPStandard($userDataPackage->TOTPData->secret);
                }
            }
        }
        return $totpManager;
    }

    /**
     * @param string $username
     * @return \stdClass|null
     */
    public static function getUserDataByUsername(string $username)
    {
        $model = UsersModel::model();
        //POR MARCADOR: el nombre llega sin sesión desde la petición (generate-otp, check-totp, el login) y viaja como dato.
        $model->select()->where(new WhereSegment([
            WhereItem::isEqual('username', $username, WhereItem::OR_OPERATOR),
            WhereItem::isEqual('email', $username),
        ]))->execute();
        $result = $model->result();
        return !empty($result) ? $result[0] : null;
    }
}
