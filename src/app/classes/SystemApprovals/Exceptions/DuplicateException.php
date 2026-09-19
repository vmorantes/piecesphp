<?php

/**
 * DuplicateException.php
 */

namespace SystemApprovals\Exceptions;

/**
 * DuplicateException.
 *
 * @package     SystemApprovals\Exceptions
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class DuplicateException extends \Exception
{
    const UNDEFINED_CODE = 0;

    const CODES = [
        self::UNDEFINED_CODE,
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
