<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace PiecesPHP\Core\Routing\Slim3Compatibility\Exception;

use PiecesPHP\Core\Routing\RequestRoutePiecesPHP;
use PiecesPHP\Core\Routing\ResponseRoutePiecesPHP;
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
     * @var ResponseRoutePiecesPHP
     */
    protected $response;

    /**
     * @param RequestRoutePiecesPHP $request
     * @param ResponseRoutePiecesPHP $response
     */
    public function __construct(RequestRoutePiecesPHP $request, ?ResponseRoutePiecesPHP $response = null, ?string $message = null, ?Throwable $previous = null)
    {
        parent::__construct($request, $message, $previous);
        $this->response = $response;
    }

    /**
     * @return ResponseRoutePiecesPHP
     */
    public function getResponse()
    {
        return $this->response;
    }
}
