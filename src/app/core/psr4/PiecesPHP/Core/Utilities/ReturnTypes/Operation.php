<?php

/**
 * Operation.php
 */
namespace PiecesPHP\Core\Utilities\ReturnTypes;

/**
 * Operation
 *
 * Representa una operación
 *
 * @category    Utilidades
 * @package     PiecesPHP\Core\Utilities\ReturnTypes
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class Operation implements \JsonSerializable
{
    /**
     * $name
     *
     * @var string
     */
    protected $name = '';
    /**
     * $success
     *
     * @var boolean
     */
    protected $success = false;
    /**
     * $required
     *
     * @var boolean
     */
    protected $required = true;
    /**
     * $message
     *
     * @var string
     */
    protected $message = '';
    /**
     * $time
     *
     * @var \DateTime
     */
    protected $time = null;

    /**
     * __construct
     *
     * @param string $name
     * @param string $message
     * @param bool $success
     * @param bool $required
     * @return static
     */
    public function __construct(string $name = null, string $message = '', bool $success = false, bool $required = true)
    {
        $this->time = new \DateTime();
        $this->name = is_null($name) ? uniqid(): $name;
        $this->message = $message;
        $this->success = $success;
        $this->required = $required;
    }

    /**
     * setName
     *
     * @param string $name
     * @return static
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * setMessage
     *
     * @param string $message
     * @return static
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * setSuccess
     *
     * @param bool $success
     * @return static
     */
    public function setSuccess(bool $success)
    {
        $this->success = $success;
        return $this;
    }

    /**
     * setRequired
     *
     * @param bool $required
     * @return static
     */
    public function setRequired(bool $required)
    {
        $this->required = $required;
        return $this;
    }

    /**
     * getName
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * getSuccess
     *
     * @return bool
     */
    public function getSuccess(): bool
    {
        return $this->success;
    }

    /**
     * getRequired
     *
     * @return bool
     */
    public function getRequired(): bool
    {
        return $this->required;
    }

    /**
     * getMessage
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * getTime
     *
     * @return \DateTime
     */
    public function getTime(): \DateTime
    {
        return $this->time;
    }

    /**
     * jsonSerialize
     *
     * @return mixed
     */
    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'success' => $this->success,
            'required' => $this->required,
            'message' => $this->message,
            'time' => $this->time->format('Y-m-d h:i:s'),
        ];
    }
}
