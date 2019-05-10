<?php

/**
 * NotAllowedTypeException.php
 */
namespace PiecesPHP\Core\Exceptions;

/**
 * NotAllowedTypeException - ....
 * 
 * @category 	Exceptions
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class NotAllowedTypeException extends \PiecesPHP\Core\Exceptions\BaseException
{
	/**
	 * __construct
	 *
	 * @param \Throwable $previous
	 */
	public function __construct(int $code = 0, \Throwable $previous = null)
	{	
		parent::__construct(__('exceptions','not_allowed_type'),$code,$previous);
	}
}
