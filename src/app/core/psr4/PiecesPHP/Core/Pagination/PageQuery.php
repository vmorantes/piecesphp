<?php

/**
 * PageQuery.php
 */

namespace PiecesPHP\Core\Pagination;

use PiecesPHP\Core\BaseModel;

/**
 * PageQuery.
 *
 * @package     PiecesPHP\Core\Pagination
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class PageQuery
{

    /**
     * Consulta para obtener los elementos
     *
     * @var string
     */
    protected $dataQuery = '';
    /**
     * Consulta para obtener el total de elementos
     *
     * @var string
     */
    protected $totalQuery = '';
    /**
     * Nombre del campo en $totalQuery que representa el conteo
     *
     * @var string
     */
    protected $fieldTotalName = 'total';
    /**
     * @var BaseModel
     */
    protected $activeRecord = null;
    /**
     * Página solicitada
     *
     * @var int
     */
    protected $page = 1;
    /**
     * Cantidad de elementos por página
     *
     * @var int
     */
    protected $perPage = 10;
    /**
     * Cantidad total de elementos obtenido en la última consulta realizada
     *
     * @var int
     */
    protected $lastTotal = 0;
    /**
     * Valores ligados por marcador con nombre, `:nombre` => valor
     *
     * @var array<string,mixed>
     */
    protected $values = [];

    /**
     * @param string $selectQuery Consulta para obtener los elementos
     * @param string $countQuery Consulta para obtener el total de elementos
     * @param string $fieldTotalName Nombre del campo en $countQuery que representa el conteo
     * @param array<string,mixed> $values Valores de los marcadores con nombre (`:nombre`) de las dos
     * consultas. No se admiten marcadores posicionales `?`. Sin valores, el SQL tiene que ser del servidor.
     */
    public function __construct(string $selectQuery, string $countQuery, int $page = 1, int $perPage = 10, string $fieldTotalName = 'total', array $values = [])
    {
        $this->dataQuery = $selectQuery;
        $this->totalQuery = $countQuery;
        $this->fieldTotalName = $fieldTotalName;
        //Sin valores, el SQL tiene que ser del servidor: lo que venga de la petición va por marcador.
        $this->values = $values;
        $this->activeRecord = new BaseModel();

        $this->setPage($page);
        $this->setPerPage($perPage);
    }

    /**
     * Devuelve los resultados de una página específica
     *
     * @return \stdClass[]
     */
    public function getPageResult(int $page, int $perPage)
    {

        $this->lastTotal = $this->getTotal();

        $this->activeRecord->resetAll();

        $from = $this->from($page, $perPage);

        $query = "($this->dataQuery) LIMIT {$from}, {$perPage}";

        $prepared = $this->activeRecord->prepare($query);

        $prepared->execute($this->valuesFor($query));

        return $prepared->fetchAll(\PDO::FETCH_OBJ);

    }

    /**
     * Devuelve los resultados
     *
     * @return \stdClass[]
     */
    public function getResult()
    {
        $this->lastTotal = $this->getTotal();

        $this->activeRecord->resetAll();

        $perPage = $this->perPage;
        $from = $this->from();

        $query = "($this->dataQuery) LIMIT {$from}, {$perPage}";

        $prepared = $this->activeRecord->prepare($query);

        $prepared->execute($this->valuesFor($query));

        return $prepared->fetchAll(\PDO::FETCH_OBJ);

    }

    /**
     * @param callable $parser
     * @param callable $each
     * @return PaginationResult
     */
    public function getPagination(?callable $parser = null, ?callable $each = null)
    {
        return new PaginationResult($this, $parser, $each);
    }

    /**
     * Devuelve la cantidad de registros totales
     *
     * @return int
     */
    public function getTotal()
    {

        $this->activeRecord->resetAll();
        $query = "($this->totalQuery)";
        $prepared = $this->activeRecord->prepare($query);
        $prepared->execute($this->valuesFor($query));
        $result = $prepared->fetchAll(\PDO::FETCH_OBJ);
        $totalName = $this->fieldTotalName;
        $total = !empty($result) ? (int) $result[0]->$totalName : 0;

        return $total;
    }

    /**
     * Devuelve la cantidad total de elementos obtenido en la última consulta realizada
     *
     * @return int
     */
    public function getLastTotal()
    {
        return $this->lastTotal;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * @param int $page
     * @return static
     */
    public function setPage(int $page)
    {
        $this->page = $page > 0 ? $page : 1;
        return $this;
    }

    /**
     * @param int $perPage
     * @return static
     */
    public function setPerPage(int $perPage)
    {
        $this->perPage = $perPage > 0 ? $perPage : 1;
        return $this;
    }

    /**
     * Valor de from en LIMIT de la consulta SQL
     *
     * @param int $page
     * @param int $perPage
     * @return int
     */
    public function from(?int $page = null, ?int $perPage = null)
    {
        $page = $page === null ? $this->page : $page;
        $perPage = $perPage === null ? $this->perPage : $perPage;

        return ($page - 1) * $perPage;
    }

    /**
     * Los valores cuyo marcador aparece en la consulta. Con PDO una clave de más da HY093, y la
     * consulta de conteo no siempre lleva los mismos marcadores que la de datos.
     *
     * @return array<string,mixed>
     */
    protected function valuesFor(string $query): array
    {
        $values = [];
        foreach ($this->values as $name => $value) {
            $marker = ':' . ltrim((string) $name, ':');
            //PALABRA COMPLETA: `:slug1` no puede casar dentro de `:slug10`.
            if (preg_match('/' . preg_quote($marker, '/') . '(?![A-Za-z0-9_])/', $query) === 1) {
                $values[$marker] = $value;
            }
        }
        return $values;
    }

}
