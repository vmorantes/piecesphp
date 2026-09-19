<?php
/**
 * SessionDataHandler.php
 */
namespace PiecesPHP\Core;

/**
 * SessionDataHandler
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class SessionDataHandler
{
    const NAME_SESSION = 'pcs_php_session_data_handler';

    /**
     * @var string
     */
    protected $context = 'general';

    /**
     * @param string $contextName Debe ser único (si ya existe se tomarán los datos existentes)
     */
    public function __construct(string $contextName)
    {
        $this->context = $contextName;
        if (!self::sessionExists()) {
            self::initSession();
        }
        if (!array_key_exists($this->context, $_SESSION[self::NAME_SESSION])) {
            $_SESSION[self::NAME_SESSION][$this->context] = [];
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function addData(string $name, $value)
    {
        $_SESSION[self::NAME_SESSION][$this->context][$name] = $value;
    }

    /**
     * @return array
     */
    public function getAllData()
    {
        if (!empty($_SESSION[self::NAME_SESSION][$this->context])) {
            $allData = $_SESSION[self::NAME_SESSION][$this->context];
            return $allData;
        } else {
            return [];
        }
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getData(string $name)
    {
        $data = null;
        $allData = self::getAllData();
        if (array_key_exists($name, $allData)) {
            $data = $allData[$name];
        }
        return $data;
    }

    /**
     * @param string $name
     * @return void
     */
    public function removeData(string $name)
    {
        if (self::hasData() && array_key_exists($name, $_SESSION[self::NAME_SESSION][$this->context])) {
            unset($_SESSION[self::NAME_SESSION][$this->context][$name]);
        }
    }

    /**
     * @return void
     */
    public function removeAllData()
    {
        unset($_SESSION[self::NAME_SESSION][$this->context]);
    }

    /**
     * @return bool
     */
    public function hasData()
    {
        if (!empty($_SESSION[self::NAME_SESSION][$this->context])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string[] $keys
     * @return bool
     */
    public function existsKeys(array $keys)
    {

        foreach ($keys as $k => $i) {
            if (!is_scalar($i)) {
                unset($keys[$i]);
            }
        }

        $result = true;
        $allDataKeys = array_keys($this->getAllData());

        foreach ($keys as $key) {
            $result = $result && in_array($key, $allDataKeys);
        }

        return $result;
    }

    /**
     * @return void
     */
    private static function initSession()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION[self::NAME_SESSION] = [];
    }

    /**
     * @return bool
     */
    private static function sessionExists()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION[self::NAME_SESSION]) ? true : false;
    }
}
