<?php

/**
 * GeoSourceSql.php
 */

namespace PiecesPHP\Geo;

use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\HavingItemGroup;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItemGroup;
use PiecesPHP\Core\Database\ORM\Statements\HavingSegment;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;

/**
 * GeoSourceSql - Las piezas de SQL que comparten las fuentes que leen de una tabla: el encuadre, el (0,0), la
 * búsqueda y el recuento con la cota. Todo valor de la petición va por marcador.
 *
 * @package     PiecesPHP\Geo
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class GeoSourceSql
{
    /**
     * Encuadre y coordenada válida, sobre dos columnas del SELECT (por alias): no nulas, dentro del encuadre (que
     * GeoQuery ya dejó dentro del mapa) y no el (0,0).
     *
     * @param HavingSegment $having
     * @param string $lngColumn
     * @param string $latColumn
     * @param GeoQuery $query
     * @return HavingSegment
     */
    public static function addBbox(HavingSegment $having, string $lngColumn, string $latColumn, GeoQuery $query): HavingSegment
    {
        $having->addGroup(new HavingItemGroup([
            new HavingItem($lngColumn, HavingItem::IS_NOT_NULL_OPERATOR, '', HavingItem::AND_OPERATOR),
            new HavingItem($latColumn, HavingItem::IS_NOT_NULL_OPERATOR, '', HavingItem::AND_OPERATOR),
            new HavingItem($lngColumn, HavingItem::GREATER_OR_EQUAL_OPERATOR, $query->west(), HavingItem::AND_OPERATOR),
            new HavingItem($lngColumn, HavingItem::LESS_OR_EQUAL_OPERATOR, $query->east(), HavingItem::AND_OPERATOR),
            new HavingItem($latColumn, HavingItem::GREATER_OR_EQUAL_OPERATOR, $query->south(), HavingItem::AND_OPERATOR),
            new HavingItem($latColumn, HavingItem::LESS_OR_EQUAL_OPERATOR, $query->north(), HavingItem::AND_OPERATOR),
        ]));
        $having->addGroup(new HavingItemGroup([
            new HavingItem($lngColumn, HavingItem::NOT_EQUAL_OPERATOR, 0, HavingItem::OR_OPERATOR),
            new HavingItem($latColumn, HavingItem::NOT_EQUAL_OPERATOR, 0, HavingItem::AND_OPERATOR),
        ]));
        return $having;
    }

    /**
     * Lo mismo que addBbox(), en WHERE y sobre expresiones de la tabla (una columna, o lo que la calcula), no sobre
     * alias: así se filtra ANTES de calcular el SELECT de cada fila, y una columna con índice lo puede usar. addBbox()
     * se queda para la fuente que solo tenga el alias.
     *
     * @param WhereSegment $where
     * @param string $lngExpression Una columna (`tabla.columna`) o una expresión SQL fija, nunca un dato de la petición
     * @param string $latExpression Lo mismo
     * @param GeoQuery $query
     * @return WhereSegment
     */
    public static function addBboxWhere(WhereSegment $where, string $lngExpression, string $latExpression, GeoQuery $query): WhereSegment
    {
        $where->addGroup(new WhereItemGroup([
            new WhereItem($lngExpression, WhereItem::IS_NOT_NULL_OPERATOR, '', WhereItem::AND_OPERATOR),
            new WhereItem($latExpression, WhereItem::IS_NOT_NULL_OPERATOR, '', WhereItem::AND_OPERATOR),
            new WhereItem($lngExpression, WhereItem::GREATER_OR_EQUAL_OPERATOR, $query->west(), WhereItem::AND_OPERATOR),
            new WhereItem($lngExpression, WhereItem::LESS_OR_EQUAL_OPERATOR, $query->east(), WhereItem::AND_OPERATOR),
            new WhereItem($latExpression, WhereItem::GREATER_OR_EQUAL_OPERATOR, $query->south(), WhereItem::AND_OPERATOR),
            new WhereItem($latExpression, WhereItem::LESS_OR_EQUAL_OPERATOR, $query->north(), WhereItem::AND_OPERATOR),
        ]));
        $where->addGroup(new WhereItemGroup([
            new WhereItem($lngExpression, WhereItem::NOT_EQUAL_OPERATOR, 0, WhereItem::OR_OPERATOR),
            new WhereItem($latExpression, WhereItem::NOT_EQUAL_OPERATOR, 0, WhereItem::AND_OPERATOR),
        ]));
        return $where;
    }

    /**
     * `(UPPER(a) LIKE UPPER(:x) OR …)`: el texto por marcador y con `%`, `_` y `\` escapados, para que la búsqueda
     * no sea un patrón.
     *
     * @param HavingSegment $having
     * @param string[] $columns
     * @param string $search
     * @return HavingSegment
     */
    public static function addSearch(HavingSegment $having, array $columns, string $search): HavingSegment
    {
        $literal = '%' . strtr($search, ['\\' => '\\\\', '%' => '\\%', '_' => '\\_']) . '%';
        $criteria = [];
        foreach ($columns as $column) {
            $criteria[] = new HavingItem("UPPER({$column})", HavingItem::LIKE_OPERATOR, $literal, HavingItem::OR_OPERATOR, 'UPPER(' . HavingItem::REPLACEMENT_VALUE_ON_RIGHT_WRAP_FUNCTION . ')');
        }
        $criteria[count($criteria) - 1]->setAfterOperator(HavingItem::AND_OPERATOR);
        $having->addGroup(new HavingItemGroup($criteria));
        return $having;
    }

    /**
     * `(columna = :a OR columna = :b …)` para una lista que ya se validó como enteros.
     *
     * @param HavingSegment $having
     * @param string $column
     * @param int[] $ids
     * @return HavingSegment
     */
    public static function addIds(HavingSegment $having, string $column, array $ids): HavingSegment
    {
        $criteria = [];
        foreach ($ids as $id) {
            $criteria[] = new HavingItem($column, HavingItem::EQUAL_OPERATOR, $id, HavingItem::OR_OPERATOR);
        }
        $criteria[count($criteria) - 1]->setAfterOperator(HavingItem::AND_OPERATOR);
        $having->addGroup(new HavingItemGroup($criteria));
        return $having;
    }

    /**
     * El texto de un filtro de texto, o null si no viene o viene vacío.
     *
     * @param GeoQuery $query
     * @param string $key
     * @return string|null
     * @throws GeoQueryException si no es un texto
     */
    public static function textFilter(GeoQuery $query, string $key): ?string
    {
        $value = $query->filter($key);
        if ($value === null) {
            return null;
        }
        if (!is_scalar($value)) {
            throw new GeoQueryException(sprintf(__(GeoLang::LANG_GROUP, 'El filtro «%s» debe ser un texto.'), $key));
        }
        $value = trim((string) $value);
        return $value !== '' ? $value : null;
    }

    /**
     * Los ids de un filtro de opciones, validados como enteros: la biblioteca no parametriza listas.
     *
     * @param GeoQuery $query
     * @param string $key
     * @return int[]
     * @throws GeoQueryException si alguno no es un entero
     */
    public static function idsFilter(GeoQuery $query, string $key): array
    {
        $value = $query->filter($key);
        if ($value === null || $value === '') {
            return [];
        }
        $values = is_array($value) ? $value : [$value];
        $ids = [];
        foreach ($values as $item) {
            if (!is_int($item) && !(is_string($item) && preg_match('/^-?\d+$/', $item) === 1)) {
                throw new GeoQueryException(sprintf(__(GeoLang::LANG_GROUP, 'El filtro «%s» solo admite números enteros.'), $key));
            }
            $ids[] = (int) $item;
        }
        return array_values(array_unique($ids));
    }

    /**
     * El total (antes de la cota) y la página con la cota, en UNA consulta: `COUNT(*) OVER ()` sobre la consulta del
     * modelo, que se calcula una vez. El modelo llega con su SELECT, su WHERE y su HAVING; se ordena por id para que la
     * cota no corte al azar. Sin filas, el total es 0.
     *
     * @param ActiveRecordModel $model
     * @param string $orderBy
     * @param int $limit
     * @return array{0: int, 1: array<int,array<string,mixed>>}
     */
    public static function countAndFetch(ActiveRecordModel $model, string $orderBy, int $limit): array
    {
        //$orderBy y $limit los pone la fuente, nunca la petición; el límite va como entero porque LIMIT no admite marcador
        //con la emulación de PDO.
        $statement = $model->prepare('SELECT geo_rows.*, COUNT(*) OVER () AS geoSourceTotal FROM (' . $model->getPreparedSQL() . ') AS geo_rows ORDER BY ' . $orderBy . ' LIMIT ' . max(0, $limit));
        $statement->execute($model->getReplaceWhereAndHavingValues());
        $total = 0;
        $rows = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $total = (int) $row['geoSourceTotal'];
            unset($row['geoSourceTotal']);
            $rows[] = $row;
        }
        $model->resetAll();
        return [$total, $rows];
    }
}
