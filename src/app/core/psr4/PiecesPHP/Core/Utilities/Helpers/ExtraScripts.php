<?php

/**
 * ExtraScripts.php
 */
namespace PiecesPHP\Core\Utilities\Helpers;

/**
 * ExtraScripts
 *
 * Clase para generar scripts en el header
 *
 * @category    Helpers
 * @package     PiecesPHP\Core\Utilities\Helpers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class ExtraScripts
{

    /**
     * $scripts
     *
     * @var string
     */
    protected static $scripts = null;

    /**
     * initialValues
     *
     * @return void
     */
    private static function initialValues()
    {

        if (is_null(self::$scripts)) {

            $scripts = get_config('extra_scripts');

            if ($scripts !== false) {
                self::$scripts = $scripts;
            } else {
                self::$scripts = '';
            }

        }

        if (!is_string(self::$scripts)) {

            $scripts = '';

        }

    }

    /**
     * setScripts
     *
     * @param string $value
     * @return void
     */
    public static function setScripts(string $value)
    {
        self::$scripts = $value;
    }

    /**
     * getScripts
     *
     * @return string
     */
    public static function getScripts()
    {
        self::initialValues();

        $html = "";

        if (strlen(trim(self::$scripts)) > 0) {
            $html .= "<!-- Extra scripts -->\r\n";
            $html .= self::$scripts;
            $html .= "\r\n<!-- Close Extra scripts -->\r\n";
        }

        return $html;
    }
}
