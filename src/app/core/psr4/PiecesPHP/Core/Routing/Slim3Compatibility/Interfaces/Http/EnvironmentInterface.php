<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace PiecesPHP\Core\Routing\Slim3Compatibility\Interfaces\Http;

interface EnvironmentInterface
{
    /**
     * Create mock environment
     *
     * @param  array $settings Array of custom environment keys and values
     *
     * @return static
     */
    public static function mock(array $settings = []);
}
