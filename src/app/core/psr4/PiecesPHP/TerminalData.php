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

    private function __construct()
    {
        if ((isset($settings['HTTPS']) && $settings['HTTPS'] !== 'off') ||
            ((isset($settings['REQUEST_SCHEME']) && $settings['REQUEST_SCHEME'] === 'https'))) {
            $defscheme = 'https';
            $defport = 443;
        } else {
            $defscheme = 'http';
            $defport = 80;
        }

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
     * @param array $data
     * @return static
     */
    public function setData(array $data)
    {
        $allowedKeys = [
            'isTerminal',
            'arguments',
            'route',
        ];

        foreach ($data as $k => $i) {

            if (!in_array($k, $allowedKeys)) {
                unset($data[$k]);
            }

            if ($k == 'isTerminal') {

                if (is_bool($i)) {
                    $this->isTerminal = $i;
                }

            } elseif ($k == 'arguments') {

                if (is_array($i)) {
                    $this->arguments = $i;
                }

            } elseif ($k == 'route') {

                if (is_string($i)) {
                    $this->route = $i;
                }

            }

        }

        $_SERVER['PCSPHP_TERMINAL_DATA'] = [
            'isTerminal' => $this->isTerminal(),
            'arguments' => $this->arguments(),
            'route' => $this->route(),
        ];

        return $this;
    }

    /**
     * @return bool
     */
    public function isTerminal()
    {
        return $this->isTerminal;
    }

    /**
     * @return array
     */
    public function arguments()
    {
        return $this->arguments;
    }

    /**
     * @return string
     */
    public function route()
    {
        return $this->route;
    }

    /**
     * @return array
     */
    public function basicServerVariables()
    {
        return $this->basicServerVariables;
    }

    /**
     * @return static
     */
    public static function getInstance()
    {

        if (self::$instance === null) {
            self::$instance = new TerminalData();
        }

        return self::$instance;

    }

}
