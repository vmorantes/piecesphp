<?php

/**
 * RouteDuplicateNameException.php
 */
namespace PiecesPHP\Core\Exceptions;

/**
 * RouteDuplicateNameException - ....
 * 
 * @category 	Exceptions
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class RouteDuplicateNameException extends \PiecesPHP\Core\Exceptions\BaseException
{
	/**
	 * __construct
	 *
	 * @param \Throwable $previous
	 */
	public function __construct(int $code = 0, \Throwable $previous = null)
	{	
		parent::__construct(__('routes_exceptions','duplicate_name_exceptions'),$code,$previous);
	}
}
