<?php

/**
 * RouteDuplicateNameException.php
 */
namespace PiecesPHP\Core\Exceptions;

/**
 * RouteDuplicateNameException
 *
 * @category     Exceptions
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class DataTablesHelperProcessException extends \PiecesPHP\Core\Exceptions\BaseException
{
    /**
     * __construct
     *
     * @param int $code
     * @param \Throwable $previous
     * @param array $errorData
     */
    public function __construct(int $code = 0, \Throwable $previous = null, array $errorData = [])
    {
        parent::__construct("Error al procesar la tabla.", $code, $previous);        $this->setExtraData($errorData);
    }
}
