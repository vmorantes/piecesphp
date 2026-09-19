<?php

/**
 * SafeException.php
 */

namespace PiecesPHP\UserSystem\Exceptions;

/**
 * SafeException.
 *
 * @package     PiecesPHP\UserSystem\Exceptions
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class SafeException extends \Exception
{
    const UNDEFINED_CODE = 0;
    const USER_NOT_EXISTS = 1;

    const CODES = [
        self::UNDEFINED_CODE,
        self::USER_NOT_EXISTS,
    ];

    /**
     * @param string $message
     * @param integer $code
     */
    public function __construct(string $message, int $code = 0)
    {
        if (!in_array($code, self::CODES)) {
            $code = self::UNDEFINED_CODE;
        }

        parent::__construct($message, $code);

    }

}
