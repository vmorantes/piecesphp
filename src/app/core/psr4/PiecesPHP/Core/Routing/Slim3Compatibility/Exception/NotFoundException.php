<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace PiecesPHP\Core\Routing\Slim3Compatibility\Exception;

use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpNotFoundException;
use Throwable;

class NotFoundException extends HttpNotFoundException
{
    /**
     * @var ServerRequestInterface
     */
    protected ServerRequestInterface $request;

    /**
     * @var ResponseRoute
     */
    protected $response;

    /**
     * @param RequestRoute $request
     * @param ResponseRoute $response
     */
    public function __construct(RequestRoute $request, ?ResponseRoute $response = null, ?string $message = null, ?Throwable $previous = null)
    {
        parent::__construct($request, $message, $previous);
        $this->response = $response;
    }

    /**
     * @return ResponseRoute
     */
    public function getResponse()
    {
        return $this->response;
    }
}
