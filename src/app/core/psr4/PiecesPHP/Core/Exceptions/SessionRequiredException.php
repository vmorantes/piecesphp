<?php

/**
 * SessionRequiredException.php
 */
namespace PiecesPHP\Core\Exceptions;

/**
 * SessionRequiredException - Falta la sesión de usuario donde el código la da por hecha.
 *
 * La lanza `getLoggedFrameworkUserOrFail()`. Extiende de `BaseException`, y por tanto de
 * `\Exception`, para que la siga capturando quien ya capturaba `\Exception`.
 *
 * @category    Exceptions
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SessionRequiredException extends \PiecesPHP\Core\Exceptions\BaseException
{
    /**
     * __construct
     *
     * El mensaje lo pone quien la lanza: así se traduce con el grupo del sistema de usuarios.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
