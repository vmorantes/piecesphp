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
     * @var string
     */
    protected $name = '';
    /**
     * @var boolean
     */
    protected $success = false;
    /**
     * @var boolean
     */
    protected $required = true;
    /**
     * @var string
     */
    protected $message = '';
    /**
     * @var \DateTime
     */
    protected $time = null;

    /**
     * @param string $name
     * @param string $message
     * @param bool $success
     * @param bool $required
     * @return static
     */
    public function __construct(string $name = null, string $message = '', bool $success = false, bool $required = true)
    {
        $this->time = new \DateTime();
        $this->name = is_null($name) ? uniqid() : $name;
        $this->message = $message;
        $this->success = $success;
        $this->required = $required;
    }

    /**
     * @param string $name
     * @return static
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param string $message
     * @return static
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @param bool $success
     * @return static
     */
    public function setSuccess(bool $success)
    {
        $this->success = $success;
        return $this;
    }

    /**
     * @param bool $required
     * @return static
     */
    public function setRequired(bool $required)
    {
        $this->required = $required;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function getSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return bool
     */
    public function getRequired(): bool
    {
        return $this->required;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return \DateTime
     */
    public function getTime(): \DateTime
    {
        return $this->time;
    }

    /**
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
