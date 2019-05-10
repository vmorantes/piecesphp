<?php

/**
 * MissingQualifiedNameException.php
 */
namespace PiecesPHP\Core\Exceptions;

/**
 * MissingQualifiedNameException - ....
 * 
 * @category 	Exceptions
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class MissingQualifiedNameException extends \PiecesPHP\Core\Exceptions\BaseException
{
	/**
	 * __construct
	 *
	 * @param \Throwable $previous
	 */
	public function __construct(int $code = 0, \Throwable $previous = null)
	{	
		parent::__construct(__('array_of_exceptions','missing_qualified_name'),$code,$previous);
	}
}
