<?php
/**
 * OTPHandler.php
 */
namespace PiecesPHP\UserSystem\Authentication;

use App\Model\UsersModel;
use PiecesPHP\Core\ConfigHelpers\MailConfig;
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
        if ($userDataPackage !== null) {
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
     * @param string $username
     * @throws SafeException Si el usuario no existe o ocurre algún error
     */
    public static function generateOTP(string $username)
    {
        $userData = self::getUserDataByUsername($username);
        if ($userData !== null) {

            $code = generate_code(8, true);

            $OTPCreated = OTPSecretsUsersMapper::setOTP($userData->id, $code, OTPSecretsUsersMapper::METHOD_ONE_USE_CODE, 20);

            if ($OTPCreated) {

                $mailer = new Mailer();
                $mailConfig = new MailConfig;
                $mailer->SMTPDebug = 2;
                $mailer->isHTML(true);
                $mailer->setFrom($mailConfig->user());
                $mailer->addAddress($userData->email);
                $mailer->Subject = mb_convert_encoding((string) __(self::LANG_GROUP, 'Contraseña de un uso'), 'UTF-8');
                $bodyMessage = __(self::LANG_GROUP, 'La siguiente es una contraseña de un solo uso que tiene una validez de 20 minutos') . ": <strong>{$code}</strong>";
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
        if ($userDataPackage !== null) {
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
        if ($userDataPackage !== null) {
            $totpManager = new TOTPStandard($userDataPackage->TOTPData->secret);
            $qrData = $totpManager->getQRCodeUrl($userDataPackage->username, $userDataPackage->TOTPData->twoAuthFactorAlias);
        }
        return $qrData;
    }

    /**
     * @return bool
     */
    public static function wasViewedCurrentUserQRData()
    {
        $wasVieved = true;
        $userDataPackage = getLoggedFrameworkUser();
        if ($userDataPackage !== null) {
            $wasVieved = $userDataPackage->TOTPData->twoAuthFactorQRViewed == 1;
        }
        return $wasVieved;
    }

    /**
     * @return bool
     */
    public static function isEnabled2FA()
    {
        $enabled = false;
        $userDataPackage = getLoggedFrameworkUser();
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
    public static function toggleCurrentUser2AF(bool $enable, string $securityCode, string $alias = null)
    {
        $totpManager = null;
        $userDataPackage = getLoggedFrameworkUser();
        if ($userDataPackage !== null) {
            OTPSecretsUsersMapper::toggle2FA($userDataPackage->id, $enable, $securityCode, $alias);
            if ($enable) {
                $userDataPackage = getLoggedFrameworkUser(true);
                $totpManager = new TOTPStandard($userDataPackage->TOTPData->secret);
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
        $model->select()->where("username = '{$username}' OR email = '{$username}'")->execute();
        $result = $model->result();
        return !empty($result) ? $result[0] : null;
    }
}
