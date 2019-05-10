<?php

/**
 * MalformedAttributeException.php
 */
namespace PiecesPHP\Core\Exceptions;

/**
 * MalformedAttributeException - ....
 * 
 * @category 	Exceptions
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class MalformedAttributeException extends \PiecesPHP\Core\Exceptions\BaseException
{
	/**
	 * __construct
	 *
	 * @param \Throwable $previous
	 */
	public function __construct(int $code = 0, \Throwable $previous = null)
	{	
		parent::__construct(__('html_exceptions','malformed_attribute'),$code,$previous);
	}
}
