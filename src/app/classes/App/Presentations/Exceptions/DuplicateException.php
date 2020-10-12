<?php

/**
 * DuplicateException.php
 */

namespace App\Presentations\Exceptions;

/**
 * DuplicateException.
 *
 * @package     App\Presentations\Exceptions
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class DuplicateException extends \Exception
{
    const UNDEFINED_CODE = 0;
    const PRESENTATION_CODE = 1;
    const PRESENTATION_CATEGORY_CODE = 2;

    const CODES = [
        self::PRESENTATION_CODE,
        self::PRESENTATION_CATEGORY_CODE,
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
