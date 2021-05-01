<?php

/**
 * DuplicateException.php
 */

namespace Publications\Exceptions;

/**
 * DuplicateException.
 *
 * @package     Publications\Exceptions
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class DuplicateException extends \Exception
{
    const UNDEFINED_CODE = 0;
    const PUBLICATION_CODE = 1;
    const CATEGORY_CODE = 2;

    const CODES = [
        self::PUBLICATION_CODE,
        self::CATEGORY_CODE,
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
