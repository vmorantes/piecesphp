<?php

/**
 * RoleMalformedException.php
 */
namespace PiecesPHP\Core\Exceptions;

/**
 * RoleMalformedException - ....
 * 
 * @category 	Exceptions
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @version     v.1
 * @copyright   Copyright (c) 2018
 * @info No funciona como módulo independiente
 */
class RoleMalformedException extends \PiecesPHP\Core\Exceptions\BaseException
{
	/**
	 * __construct
	 *
	 * @param \Throwable $previous
	 */
	public function __construct(int $code = 0, \Throwable $previous = null)
	{	
		parent::__construct(__('roles_exceptions','role_malformed'),$code,$previous);
	}
}
