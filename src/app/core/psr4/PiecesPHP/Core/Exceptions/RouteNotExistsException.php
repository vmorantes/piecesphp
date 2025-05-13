<?php
/**
 * RouteNotExistsException.php
 */
namespace PiecesPHP\Core\Exceptions;

/**
 * Excepción que se lanza cuando se intenta acceder a una ruta que no existe
 *
 * Esta excepción se utiliza para manejar los casos donde se intenta acceder o manipular
 * una ruta que no está registrada en el sistema de enrutamiento.
 *
 * @category    Exceptions
 * @package     PiecesPHP\Core\Exceptions
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class RouteNotExistsException extends \PiecesPHP\Core\Exceptions\BaseException
{
    /**
     * @param int $code Código de error de la excepción
     * @param \Throwable|null $previous Excepción previa que causó esta excepción
     * @param string|null $routeName Nombre de la ruta que no existe.
     */
    public function __construct(int $code = 0, \Throwable $previous = null, ?string $routeName = null)
    {
        $routeName = $routeName ?? 'solicitada';
        $message = "La ruta {$routeName} no existe";
        parent::__construct($message, $code, $previous);
    }
}
