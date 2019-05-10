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
	 * $success
	 *
	 * @var boolean
	 */
	protected $success = true;
	/**
	 * $message
	 *
	 * @var string
	 */
	protected $message = '';
	/**
	 * $position
	 *
	 * @var int
	 */
	protected $position = 0;

    /**
     * __construct
     *
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
	 * setMessage
	 *
	 * @param string $message
	 * @return void
	 */
	public function setMessage(string $message){
		$this->message = "$message</br>";
	}
	
	/**
	 * appendMessage
	 *
	 * @param string $message
	 * @return void
	 */
	public function appendMessage(string $message){
		$this->message .= "$message</br>";
	}
	
	/**
	 * getMessage
	 *
	 * @return string
	 */
	public function getMessage(){
		return $this->message;
	}
	
	/**
	 * setPosition
	 *
	 * @param string $position
	 * @return void
	 */
	public function setPosition(int $position){
		$this->position = $position;
	}
	
	/**
	 * getPosition
	 *
	 * @return int
	 */
	public function getPosition(){
		return $this->position;
	}
	
	/**
	 * setSuccess
	 *
	 * @param bool $success
	 * @return void
	 */
	public function setSuccess(bool $success){
		$this->success = $success;
	}
	
	/**
	 * getSuccess
	 *
	 * @return bool
	 */
	public function getSuccess(){
		return $this->success;
	}

	public function jsonSerialize()
	{
		return [
			'success' => $this->success,
			'message' => $this->message,
			'position' => $this->position,
		];
	}
}
