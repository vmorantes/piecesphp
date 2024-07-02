<?php

/**
 * BaseException.php
 */
namespace PiecesPHP\Core\Exceptions;

/**
 * BaseException - ....
 *
 * @category    Exceptions
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class BaseException extends \Exception
{

    /**
     * @var array
     */
    protected array $extraData = [];

    /**
     * __construct
     *
     * @param string $message
     * @param mixed $code
     * @param mixed \Throwable $previous
     */
    public function __construct(string $message = '', int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * setMessage
     *
     * @param string $message
     * @return void
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    /**
     * @return array
     */
    public function extraData()
    {
        return $this->extraData;
    }

    /**
     * @param array $data
     * @return BaseException
     */
    public function setExtraData(array $data)
    {
        $this->extraData = $data;
        return $this;
    }
}
