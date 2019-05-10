<?php

/**
 * MalformedChildException.php
 */
namespace PiecesPHP\Core\Exceptions;

/**
 * MalformedChildException - ....
 * 
 * @category 	Exceptions
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @version     v.1
 * @copyright   Copyright (c) 2018
 * @info No funciona como módulo independiente
 */
class MalformedChildException extends \PiecesPHP\Core\Exceptions\BaseException
{
	/**
	 * __construct
	 *
	 * @param \Throwable $previous
	 */
	public function __construct(int $code = 0, \Throwable $previous = null)
	{	
		parent::__construct(__('html_exceptions','malformed_child'),$code,$previous);
	}
}
