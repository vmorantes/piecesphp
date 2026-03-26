<?php
/**
 * FilesIgnore.php
 */

namespace PiecesPHP\Core\Helpers\Directories;

/**
 * FilesIgnore - Define reglas para excluir archivos y directorios.
 *
 * Permite gestionar expresiones regulares para ignorar elementos durante el escaneo de directorios.
 * También soporta reglas de inclusión forzada mediante el prefijo 'INCLUDE_EXPR::'.
 *
 * @package     PiecesPHP\Core\Helpers\Directories
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class FilesIgnore
{
    /**
     * @var string[] Listado de expresiones regulares para exclusión/inclusión.
     */
    protected $exclude_regexprs = [];

    /**
     * Constructor de FilesIgnore.
     *
     * @param string[] $regexprs Arreglo inicial de expresiones regulares.
     */
    public function __construct(array $regexprs)
    {
        $this->exclude_regexprs = array_filter($regexprs, function ($regexpr) {
            return is_string($regexpr);
        });

    }

    /**
     * Añade una expresión regular al listado de exclusión.
     *
     * @param string $regexpr Expresión regular.
     * @return $this
     */
    public function addRegExpr(string $regexpr)
    {
        $this->exclude_regexprs[] = $regexpr;
        return $this;
    }

    /**
     * Añade múltiples expresiones regulares al listado.
     *
     * @param string[] $regexprs Arreglo de expresiones regulares.
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
     * Sobrescribe el listado actual de expresiones regulares.
     *
     * @param string[] $regexprs Arreglo de expresiones regulares.
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
     * Determina si una ruta debe ser ignorada basándose en las reglas definidas.
     *
     * El método procesa primero las reglas de exclusión y luego las de inclusión
     * (las que empiezan con 'INCLUDE_EXPR::'). Si una ruta coincide con una regla
     * de inclusión, será procesada incluso si coincide con una de exclusión.
     *
     * @param string $path Ruta a evaluar.
     * @return bool True si debe ser ignorada, false en caso contrario.
     */
    public function ignore(string $path)
    {
        $ignore_file = false;
        $include_file = false;

        $include_string_control = 'INCLUDE_EXPR::';

        $include_regexprs = array_filter($this->exclude_regexprs, function ($regexpr) use ($include_string_control) {
            return mb_strpos($regexpr, $include_string_control) !== false;
        });

        $normal_regexprs = array_filter($this->exclude_regexprs, function ($regexpr) use ($include_regexprs) {
            return !in_array($regexpr, $include_regexprs);
        });

        $include_regexprs = array_map(function ($regexpr) use ($include_string_control) {
            $pos_regexpr = mb_strpos($regexpr, $include_string_control) + mb_strlen($include_string_control);
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
