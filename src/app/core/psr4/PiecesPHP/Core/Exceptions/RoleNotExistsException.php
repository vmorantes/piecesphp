<?php

/**
 * RoleNotExistsException.php
 */
namespace PiecesPHP\Core\Exceptions;

/**
 * RoleNotExistsException - ....
 * 
 * @category 	Exceptions
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class RoleNotExistsException extends \PiecesPHP\Core\Exceptions\BaseException
{
	/**
	 * __construct
	 *
	 * @param \Throwable $previous
	 */
	public function __construct(int $code = 0, \Throwable $previous = null)
	{	
		parent::__construct(__('roles_exceptions','not_exists'),$code,$previous);
	}
}
