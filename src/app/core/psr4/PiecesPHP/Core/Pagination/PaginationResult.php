<?php

/**
 * PaginationResult.php
 */

namespace PiecesPHP\Core\Pagination;

use JsonSerializable;

/**
 * PaginationResult.
 *
 * @package     PiecesPHP\Core\Pagination
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class PaginationResult implements JsonSerializable
{
    /**
     * Página actual
     *
     * @var int
     */
    protected $page = 1;
    /**
     * Página siguiente
     *
     * @var int
     */
    protected $nextPage = 1;
    /**
     * Página anterior
     *
     * @var int
     */
    protected $prevPage = 1;
    /**
     * Cantidad de elementos por página
     *
     * @var int
     */
    protected $perPage = 10;
    /**
     * Cantidad de elementos totales
     *
     * @var int
     */
    protected $totalElements = 0;
    /**
     * Elementos de la página
     *
     * @var \stdClass[]
     */
    protected $elements = [];
    /**
     * Elementos de la página convertidos
     *
     * @var array
     */
    protected $parsedElements = [];
    /**
     * Total de páginas
     *
     * @var int
     */
    protected $totalPages = 1;
    /**
     * Verifica si es la página final
     *
     * @var bool
     */
    protected $isFinal = false;

    public function __construct(PageQuery $pageQuery, callable $elementParser = null, callable $each = null)
    {
        $this->page = $pageQuery->getPage();
        $this->perPage = $pageQuery->getPerPage();
        $this->elements = $pageQuery->getResult();
        $this->totalElements = $pageQuery->getLastTotal();
        $this->totalPages = $this->totalElements > 0 ? ceil($this->totalElements / $this->perPage) : 1;
        $this->isFinal = $this->page == $this->totalPages;

        if ($this->totalPages > 1) {

            if ($this->page < $this->totalPages) {
                $this->nextPage = $this->page + 1;
            } else {
                $this->nextPage = $this->page;
            }

            if ($this->page > 1) {
                $this->prevPage = $this->page - 1;
            } else {
                $this->prevPage = $this->page;
            }

        }

        $this->parsedElements = [];

        $hasParser = $elementParser !== null;
        $hasEach = $each !== null;

        if ($hasParser || $hasEach) {

            foreach ($this->elements as $key => $element) {

                if ($hasParser) {
                    $parsed = ($elementParser)($element);
                    $this->parsedElements[] = $parsed !== null ? $parsed : $element;
                }

                if ($hasEach) {
                    $parsed = ($each)($element);
                    $this->elements[$key] = $parsed !== null ? $parsed : $element;
                }

            }

        }

        if (!$hasParser) {
            $this->parsedElements = $this->elements;
        }

    }

    /**
     * @return int
     */
    public function page()
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function nextPage()
    {
        return $this->nextPage;
    }

    /**
     * @return int
     */
    public function prevPage()
    {
        return $this->prevPage;
    }

    /**
     * @return int
     */
    public function perPage()
    {
        return $this->perPage;
    }

    /**
     * @return int
     */
    public function totalElements()
    {
        return $this->totalElements;
    }

    /**
     * @return bool
     */
    public function isFinal()
    {
        return $this->isFinal;
    }

    /**
     * @return \stdClass[]
     */
    public function elements()
    {
        return $this->elements;
    }

    /**
     * @return array
     */
    public function parsedElements()
    {
        return $this->parsedElements;
    }

    /**
     * @return int
     */
    public function totalPages()
    {
        return $this->totalPages;
    }

    public function jsonSerialize()
    {
        $data = [
            'page' => $this->page(),
            'prevPage' => $this->prevPage(),
            'nextPage' => $this->nextPage(),
            'perPage' => $this->perPage(),
            'totalElements' => $this->totalElements(),
            'elements' => $this->elements(),
            'parsedElements' => $this->parsedElements(),
            'totalPages' => $this->totalPages(),
            'isFinal' => $this->isFinal(),
        ];

        ksort($data);

        return $data;
    }

}
