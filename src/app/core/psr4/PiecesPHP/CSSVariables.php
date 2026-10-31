<?php
/**
 * CSSVariables.php
 */

namespace PiecesPHP;

use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;

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
     * @var CSSVariables[]
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
    public function setVariable(string $name, string $value, bool $quote = false): static
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
    public function getVariable(string $name, ?string $defaultValue = null, bool $quote = false)
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
     * La huella del contenido, que es la versión de su URL (ADR 0034): cambia cuando cambia una variable.
     *
     * @return string
     */
    public function fingerprint(): string
    {
        return substr(sha1($this->__toString()), 0, 16);
    }

    /**
     * La URL con `cacheStamp=<huella>`: con ella, y solo con ella, toResponse() la deja guardar un año.
     *
     * @param string $url
     * @return string
     */
    public function versionedURL(string $url): string
    {
        return $url . (str_contains($url, '?') ? '&' : '?') . 'cacheStamp=' . $this->fingerprint();
    }

    /**
     * La URL versionada del CSS de variables del panel (admin-global-variables-css): la que piden containers.php y las
     * páginas 403, 404 y 503.
     *
     * @return string
     */
    public static function globalVersionedURL(): string
    {
        $url = get_route('admin-global-variables-css');
        return is_string($url) ? self::instance('global')->versionedURL($url) : '';
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param bool $overwriteCache
     * @param string|null $cacheControl Sin él, un año e inmutable si la petición trae su huella como `cacheStamp`, y
     * `no-cache` (revalidar con el ETag) sin marca o con otra, como la global que llevan los de los módulos
     * @return Response
     */
    public function toResponse(Request $request, Response $response, bool $overwriteCache = false, ?string $cacheControl = null)
    {

        $css = $this->__toString();
        $contentETag = $css;
        if ($cacheControl === null) {
            $cacheControl = $request->getQueryParam('cacheStamp', null) === $this->fingerprint() ? 'public, max-age=31536000, immutable' : 'no-cache';
        }

        if ($overwriteCache) {
            $contentETag .= uniqid();
        }

        $lastModification = new \DateTime('1990-01-01 00:00:00');
        $headersAndStatus = generateCachingHeadersAndStatus($request, $lastModification, $contentETag);

        if (mb_strlen($cacheControl) > 0) {
            $headersAndStatus['headers']['Cache-Control'] = $cacheControl;
        }

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
