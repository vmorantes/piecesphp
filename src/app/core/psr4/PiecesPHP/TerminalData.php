<?php
/**
 * TerminalData.php
 */

namespace PiecesPHP;

/**
 * TerminalData.
 *
 * @package     PiecesPHP
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class TerminalData
{

    /**
     * @var static
     */
    protected static $instance = null;
    /**
     * @var bool
     */
    protected $isTerminal = false;
    /**
     * @var bool
     */
    protected $local = false;
    /**
     * @var array
     */
    protected $arguments = [];
    /**
     * @var string
     */
    protected $route = '';
    /**
     * @var array
     */
    protected $basicServerVariables = [];
    /**
     * @var array<string,mixed>
     */
    protected $sharedData = [];

    private function __construct()
    {
        $defscheme = 'http';
        $defport = 80;

        $this->basicServerVariables = [
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_SCHEME' => $defscheme,
            'SCRIPT_NAME' => '',
            'REQUEST_URI' => '',
            'QUERY_STRING' => '',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => $defport,
            'HTTP_HOST' => 'localhost',
            'HTTP_ACCEPT' => '*',
            'HTTP_ACCEPT_LANGUAGE' => '*',
            'HTTP_ACCEPT_CHARSET' => 'utf-8',
            'HTTP_USER_AGENT' => 'PiecesPHP Framework',
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
        ];

    }

    /**
     * @param array<string,mixed> $data
     * @return static
     */
    public function setSharedData(array $data)
    {
        $this->sharedData = [];
        foreach ($data as $k => $i) {
            if (is_string($k)) {
                $this->addSharedData($k, $i);
            }
        }
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function addSharedData(string $name, $value)
    {
        $this->sharedData[$name] = $value;
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getSharedData(string $name, $default = null)
    {
        return array_key_exists($name, $this->sharedData) ? $this->sharedData[$name] : $default;
    }

    /**
     * @param string $name
     * @return static
     */
    public function removeSharedData(string $name)
    {
        if (isset($this->sharedData[$name])) {
            unset($this->sharedData[$name]);
        }
        return $this;
    }

    /**
     * @return array<string,mixed>
     */
    public function getAllSharedData()
    {
        return $this->sharedData;
    }

    /**
     * @param array $data
     * @return static
     */
    public function setData(array $data)
    {
        $dataConfig = $this->dataConfig();
        foreach ($data as $k => $i) {
            if (!in_array($k, array_keys($dataConfig))) {
                unset($data[$k]);
            }
            $dataConfig[$k]['set']($i);
        }
        return $this->syncWithGlobals();
    }

    /**
     * @param bool $value
     * @return bool|static
     */
    public function isTerminal(?bool $value = null)
    {
        if ($value !== null) {
            $this->isTerminal = $value;
            $this->syncWithGlobals();
        }
        return $value !== null ? $this : $this->isTerminal;
    }

    /**
     * @param array $value
     * @return array|static
     */
    public function arguments(?array $value = null)
    {
        if ($value !== null) {
            $this->arguments = $value;
            $this->syncWithGlobals();
        }
        return $value !== null ? $this : $this->arguments;
    }

    /**
     * @param string $value
     * @return string|static
     */
    public function route(?string $value = null)
    {
        if ($value !== null) {
            $this->route = $value;
            $this->syncWithGlobals();
        }
        return $value !== null ? $this : $this->route;
    }

    /**
     * @param bool $value
     * @return bool|static
     */
    public function local(?bool $value = null)
    {
        if ($value !== null) {
            $this->local = $value;
            $this->syncWithGlobals();
        }
        return $value !== null ? $this : $this->local;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function addArgument(string $name, $value)
    {
        $this->arguments[$name] = $value;
        $this->syncWithGlobals();
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function setArgument(string $name, $value)
    {
        $this->arguments[$name] = $value;
        $this->syncWithGlobals();
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getArgument(string $name, $default = null)
    {
        return array_key_exists($name, $this->arguments) ? $this->arguments[$name] : $default;
    }

    /**
     * @return array
     */
    public function basicServerVariables(): array
    {
        return $this->basicServerVariables;
    }

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getServerVariable(string $name, $default = null)
    {
        return array_key_exists($name, $this->basicServerVariables) ? $this->basicServerVariables[$name] : $default;
    }

    /**
     * @return static
     */
    public function syncWithGlobals(): self
    {

        $globals = [];
        $dataConfig = $this->dataConfig();
        foreach ($dataConfig as $k => $i) {
            $registerGlobal = $i['registerGlobal'];
            $value = $i['value'];
            if ($registerGlobal) {
                $globals[$k] = $value;
            }
        }
        $_SERVER['PCSPHP_TERMINAL_DATA'] = $globals;
        return $this;
    }

    /**
     * @return array<string, array{'value': mixed, 'set': callable, 'registerGlobal': bool}>
     */
    public function dataConfig(): array
    {
        return [
            'isTerminal' => [
                'value' => $this->isTerminal,
                'set' => fn($value) => $this->isTerminal($value),
                'registerGlobal' => true,
            ],
            'arguments' => [
                'value' => $this->arguments,
                'set' => fn($value) => $this->arguments($value),
                'registerGlobal' => true,
            ],
            'route' => [
                'value' => $this->route,
                'set' => fn($value) => $this->route($value),
                'registerGlobal' => true,
            ],
            'local' => [
                'value' => $this->local,
                'set' => fn($value) => $this->local($value),
                'registerGlobal' => true,
            ],
        ];
    }

    /**
     * @return static
     */
    public static function getInstance()
    {

        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;

    }

    /**
     * @return static
     */
    public static function instance()
    {
        return self::getInstance();
    }

}