<?php
/**
 * FilesIgnore.php
 */

namespace PiecesPHP\Core\Helpers\Directories;

/**
 * FilesIgnore - Representa un conjunto de archivos/directorios que serán ignorados por un objeto DirectoryObject
 *
 * @package     PiecesPHP\Core\Helpers\Directories
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class FilesIgnore
{
    /**
     * $exclude_regexprs
     *
     * @var string[]
     */
    protected $exclude_regexprs = [];

    /**
     * __construct
     *
     * @param string[] $regexprs
     * @return static
     */
    public function __construct(array $regexprs)
    {
        $this->exclude_regexprs = array_filter($regexprs, function ($regexpr) {
            return is_string($regexpr);
        });

    }

    /**
     * addRegExpr
     *
     * @param string $regexpr
     * @return $this
     */
    public function addRegExpr(string $regexpr)
    {
        $this->exclude_regexprs[] = $regexpr;
        return $this;
    }

    /**
     * addRegExprs
     *
     * @param string[] $regexprs
     * @return $this
     */
    public function addRegExprs(array $regexprs)
    {
        $regexprs = array_filter($regexprs, function ($regexpr) {
            return is_string($regexpr);
        });

        foreach ($regexprs as $regexpr) {
            $this->addRegExpr($regexpr);
        }

        return $this;
    }

    /**
     * setRegExprs
     *
     * @param string[] $regexprs
     * @return $this
     */
    public function setRegExprs(array $regexprs)
    {

        $regexprs = array_filter($regexprs, function ($regexpr) {
            return is_string($regexpr);
        });

        $this->exclude_regexprs = $regexprs;

        return $this;
    }

    /**
     * ignore
     *
     * @param string $path
     * @return bool
     */
    public function ignore(string $path)
    {
        $ignore_file = false;
        $include_file = false;

        $include_string_control = 'INCLUDE_EXPR::';

        $include_regexprs = array_filter($this->exclude_regexprs, function ($regexpr) use ($include_string_control) {
            return strpos($regexpr, $include_string_control) !== false;
        });

        $normal_regexprs = array_filter($this->exclude_regexprs, function ($regexpr) use ($include_regexprs) {
            return !in_array($regexpr, $include_regexprs);
        });

        $include_regexprs = array_map(function ($regexpr) use ($include_string_control) {
            $pos_regexpr = strpos($regexpr, $include_string_control) + strlen($include_string_control);
            return mb_substr($regexpr, $pos_regexpr);
        }, $include_regexprs);

        foreach ($normal_regexprs as $index => $value) {

            $regexpr = $normal_regexprs[$index];

            $ignore_file = preg_match("|$regexpr|", $path) == 1;

            if ($ignore_file) {
                break;
            }

        }

        foreach ($include_regexprs as $index => $value) {

            $regexpr = $include_regexprs[$index];

            $include_file = preg_match("|$regexpr|", $path) == 1;

            if ($include_file) {
                break;
            }
        }

        $ignore = $ignore_file && !$include_file;

        return $ignore;
    }
}
