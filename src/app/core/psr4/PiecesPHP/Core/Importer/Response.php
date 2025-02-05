<?php
/**
 * Response.php
 */
namespace PiecesPHP\Core\Importer;

/**
 * Response.
 *
 * Respuesta del proceso de importación
 *
 * @package     PiecesPHP\Core\Importer
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class Response implements \JsonSerializable
{
    /**
     * @var boolean
     */
    protected $success = true;
    /**
     * @var string
     */
    protected $message = '';
    /**
     * @var int
     */
    protected $position = 0;

    /**
     * @param boolean $success
     * @param string $message
     * @param int $position
     * @return static
     */
    public function __construct(bool $success = true, string $message = '', int $position = 0)
    {
        $this->success = $success;
        $this->message = $message;
        $this->position = $position;
    }

    /**
     * @param string $message
     * @return void
     */
    public function setMessage(string $message)
    {
        $this->message = "$message</br>";
    }

    /**
     * @param string $message
     * @return void
     */
    public function appendMessage(string $message)
    {
        $this->message .= "$message</br>";
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $position
     * @return void
     */
    public function setPosition(int $position)
    {
        $this->position = $position;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param bool $success
     * @return void
     */
    public function setSuccess(bool $success)
    {
        $this->success = $success;
    }

    /**
     * @return bool
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'position' => $this->position,
        ];
    }
}
