<?php

/**
 * McpLang.php
 */

namespace PiecesPHP\Mcp;

use PiecesPHP\Core\Config;
use PiecesPHP\Core\Mcp\McpProtocol;
use PiecesPHP\LangInjector;

/**
 * McpLang - El grupo de traducción del servidor MCP del panel y de sus herramientas del panel.
 *
 * @package     PiecesPHP\Mcp
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class McpLang extends LangInjector
{
    const LANG_GROUP = McpProtocol::LANG_GROUP;

    /**
     * @return void
     */
    public static function injectLang()
    {
        $injector = new LangInjector(__DIR__ . '/lang', Config::get_allowed_langs());
        $injector->injectGroup(self::LANG_GROUP);
    }
}
