<?php

/**
 * OTPSecretsSeeder.php
 */

namespace PiecesPHP\UserSystem\Seeds;

use PiecesPHP\UserSystem\ORM\OTPSecretsUsersMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * OTPSecretsSeeder.
 *
 * El doble factor de los usuarios sembrados, para Registros › Tokens y códigos: la mitad con el segundo factor activo
 * y la otra mitad con un código de un solo uso vigente (dos días, para que se vea). Una fila por usuario, como la crea
 * la aplicación.
 *
 * El secreto va cifrado como lo cifra la aplicación (`setPlainSecret()`): uno en claro rompería la rotación de la
 * clave, que reescribe todos los de la tabla.
 *
 * @package     PiecesPHP\UserSystem\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class OTPSecretsSeeder extends ModuleSeeder
{

    public function mapper(): string
    {
        return OTPSecretsUsersMapper::class;
    }

    /**
     * El alias del dispositivo del segundo factor: texto libre, y solo lo ve el propio usuario en su Seguridad.
     *
     * @return string
     */
    public function markerColumn(): string
    {
        return 'twoAuthFactorAlias';
    }

    public function dependsOn(): array
    {
        return [UsersModel::class];
    }

    public function defaultCount(): int
    {
        return 6;
    }

    /**
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed>
     */
    public function row(int $index, SeedContext $context): array
    {
        $totp = $index % 2 === 0;
        $sealed = new OTPSecretsUsersMapper();
        $sealed->setPlainSecret($totp ? self::base32Secret($index) : '');
        return [
            'user' => $context->reference(UsersModel::class, $index),
            'secret' => $sealed->secret,
            'intervalTOTP' => OTPSecretsUsersMapper::DEFAULT_INTERVAL_TOTP,
            'oneUseCode' => $totp ? '' : str_pad((string) ((161803 + $index * 4421) % 1000000), 6, '0', STR_PAD_LEFT),
            'maxDate' => $totp ? null : (new \DateTime())->modify('+2 days'),
            'method' => $totp ? OTPSecretsUsersMapper::METHOD_TOTP : OTPSecretsUsersMapper::METHOD_ONE_USE_CODE,
            'twoAuthFactor' => $totp ? OTPSecretsUsersMapper::TWOAF_STATUS_ENABLED : OTPSecretsUsersMapper::TWOAF_STATUS_DISABLED,
            'twoAuthFactorQRViewed' => $totp ? 1 : 0,
            'twoAuthFactorAlias' => $this->text('dispositivo', $index),
            'twoAuthFactorSecurityCode' => '',
        ];
    }

    /**
     * Un secreto TOTP con forma de verdad (base32, 32 caracteres), distinto por fila y fijo entre siembras.
     *
     * @param int $index
     * @return string
     */
    protected static function base32Secret(int $index): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 32; $i++) {
            $secret .= $alphabet[($index * 7 + $i * 11) % 32];
        }
        return $secret;
    }

}
