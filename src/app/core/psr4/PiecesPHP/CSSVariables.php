<?php
/**
 * CSSVariables.php
 */

namespace PiecesPHP;

use Slim\Http\Request;
use Slim\Http\Response;

/**
 * CSSVariables.
 *
 * @package     PiecesPHP
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class CSSVariables
{

    /**
     * @var array
     */
    protected $variables = [];

    /**
     * @var static[]
     */
    protected static $instances = [];

    /**
     * @ignore
     */
    private function __construct()
    {
    }

    /**
     * Crea y devuelve una instancia con el identificador $code, si ya existe devuelve la existente
     *
     * @param string $code El código siempre es convertido a mayúsculas y sin espacios
     * @return static
     */
    public static function instance(string $code)
    {

        $code = str_replace(' ', '', trim(mb_strtoupper($code)));
        $exists = array_key_exists($code, self::$instances);
        $instance = null;

        if ($exists) {
            $instance = self::$instances[$code];
        } else {
            self::$instances[$code] = new static;
            $instance = self::$instances[$code];
        }

        return $instance;
    }

    /**
     * @param string $name
     * @param string $value
     * @param bool $quote
     * @return static
     */
    public function setVariable(string $name, string $value, bool $quote = false)
    {
        $name = "--{$name}";
        $value = $quote ? "\"{$value}\"" : $value;
        $this->variables[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @param string $defaultValue
     * @param bool $quote
     * @return string|null
     */
    public function getVariable(string $name, string $defaultValue = null, bool $quote = false)
    {
        $value = null;
        if ($this->variableExists($name)) {
            $value = $this->variables["--{$name}"];
        } else if ($defaultValue !== null) {
            $value = $quote ? "\"{$defaultValue}\"" : $defaultValue;
        }
        return $value;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function variableExists(string $name)
    {
        $name = "--{$name}";
        $exists = array_key_exists($name, $this->variables);
        return $exists;
    }

    /**
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param bool $overwriteCache
     * @return Response
     */
    public function toResponse(Request $request, Response $response, bool $overwriteCache = false)
    {

        $css = $this->__toString();
        $contentETag = $css;

        if ($overwriteCache) {
            $contentETag .= uniqid();
        }

        $lastModification = \DateTime::createFromFormat('d-m-Y h:i A', '01-01-1990 12:00 AM');
        $headersAndStatus = generateCachingHeadersAndStatus($request, $lastModification, $contentETag);

        foreach ($headersAndStatus['headers'] as $header => $value) {
            $response = $response->withHeader($header, $value);
        }

        return $response
            ->write($css)
            ->withStatus($headersAndStatus['status'])
            ->withHeader('Content-Type', 'text/css');

    }

    /**
     * @return string
     */
    public function __toString()
    {
        $cssVariables = [];

        foreach ($this->variables as $name => $value) {
            $cssVariables[] = "{$name}: {$value};";
        }

        $cssVariables = implode("\n", $cssVariables);

        $cssVariables = ":root {\n{$cssVariables}\n}";

        return $cssVariables;
    }
}
