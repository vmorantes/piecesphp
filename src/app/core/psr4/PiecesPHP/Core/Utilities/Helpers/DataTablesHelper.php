<?php

/**
 * DataTablesHelper.php
 */
namespace PiecesPHP\Core\Utilities\Helpers;

use PiecesPHP\Core\Database\EntityMapper;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use \Slim\Http\Request as Request;

/**
 * DataTablesHelper
 *
 * Clase de ayuda para procesamiento de consultas de DataTablesJS del lado del servidor
 *
 * @category    Helpers
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class DataTablesHelper
{

    const INGNORE = self::class . '::INGNORE';
    const ONLY_ORDER = self::class . '::ONLY_ORDER';

    /**
     * $tableOnOrder
     *
     * @var bool
     */
    private static $tableOnOrder = true;
    /**
     * $tableOnSearch
     *
     * @var bool
     */
    private static $tableOnSearch = true;

    /**
     * process
     *
     * @param array $options
     * @var $options[request] \Slim\Http\Request, required
     * @var $options[mapper] \PiecesPHP\Core\Database\EntityMapper, required
     * @var $options[columns_order] array, required
     * @var $options[where_string] string
     * @var $options[having_string] string
     * @var $options[on_set_data] callable Recibe por parámetro el elemento actual y debe devolver el valor que corresponderá a la fila
     * @var $options[as_mapper] bool
     * @var $options[on_set_model] callable
     * @var $options[config_result_model] callable
     * @var $options[select_fields] array|string
     * @var $options[custom_order] array
     * @var $options[group_string] string
     * @return ResultOperations
     */
    public static function process(array $options)
    {

        //──── INICIO ────────────────────────────────────────────────────────────────────────────
        //Variables de configuración
        /**
         * $request
         * @var \Slim\Http\Request
         */
        $request = null;
        /**
         * $mapper
         * @var \PiecesPHP\Core\Database\EntityMapper
         */
        $mapper = null;
        /**
         * $columns_order
         * @var array
         */
        $columns_order = [];
        /**
         * $where_string
         * @var string
         */
        $where_string = null;
        /**
         * $having_string
         * @var string
         */
        $having_string = null;
        /**
         * $on_set_data
         * @var callable
         */
        $on_set_data = null;
        /**
         * $as_mapper
         * @var bool
         */
        $as_mapper = null;
        /**
         * $on_set_model
         * @var callable
         */
        $on_set_model = null;
        /**
         * $config_result_model
         * @var callable
         */
        $config_result_model = null;
        /**
         * $select_fields
         * @var array|string
         */
        $select_fields = null;
        /**
         * $custom_order
         * @var array
         */
        $custom_order = [];
        /**
         * $group_string
         * @var string
         */
        $group_string = '';
        /**
         * $ignore_table_in_order
         * @var bool
         */
        $ignore_table_in_order = false;
        /**
         * $ignore_fields_in_where
         * @var array
         */
        $ignore_fields_in_where = [];
        /**
         * $ignore_table_on_fields_in_where
         * @var array
         */
        $ignore_table_on_fields_in_where = [];
        //──── FIN ───────────────────────────────────────────────────────────────────────────────

        //──── INICIO ────────────────────────────────────────────────────────────────────────────
        //Analizar parámetros entrantes
        $parameters_expected = new Parameters([
            new Parameter('request', null, function ($value) {
                return $value instanceof Request;
            }),
            new Parameter('mapper', null, function ($value) {
                return $value instanceof EntityMapper || is_subclass_of($value, '\\PiecesPHP\\Core\\Database\\EntityMapper');
            }),
            new Parameter('columns_order', null, function ($value) {
                return is_array($value);
            }),
            new Parameter('where_string', null, function ($value) {
                return is_string($value);
            }, true),
            new Parameter('having_string', null, function ($value) {
                return is_string($value);
            }, true),
            new Parameter('on_set_data', null, function ($value) {
                return is_callable($value);
            }, true),
            new Parameter('as_mapper', false, function ($value) {
                return is_bool($value);
            }, true),
            new Parameter('on_set_model', null, function ($value) {
                return is_callable($value);
            }, true),
            new Parameter('config_result_model', null, function ($value) {
                return is_callable($value);
            }, true),
            new Parameter('select_fields', null, function ($value) {
                return is_array($value) || is_string($value);
            }, true),
            new Parameter('custom_order', null, function ($value) {
                return is_array($value);
            }, true),
            new Parameter('group_string', null, function ($value) {
                return is_string($value);
            }, true),
            new Parameter('ignore_table_in_order', false, function ($value) {
                return is_bool($value);
            }, true),
            new Parameter(
                'ignore_fields_in_where',
                [],
                function ($value) {
                    return is_array($value);
                },
                true,
                function ($value) {
                    foreach ($value as $i => $v) {
                        if (!is_string($v) || !ctype_digit((string) $i)) {
                            unset($value[$i]);
                        }
                    }
                    return $value;
                }
            ),
            new Parameter(
                'ignore_table_on_fields_in_where',
                [],
                function ($value) {
                    return is_array($value);
                },
                true,
                function ($value) {
                    foreach ($value as $i => $v) {
                        if (!is_string($v) || !ctype_digit((string) $i)) {
                            unset($value[$i]);
                        }
                    }
                    return $value;
                }
            ),
        ]);
        $parameters_expected->setInputValues($options);
        extract($parameters_expected->getValues());
        //──── FIN ───────────────────────────────────────────────────────────────────────────────

        //Objecto de resultado
        $result = new ResultOperations();

        //Parámetros recibidos desde datatables

        /**
         * @var int $draw
         */
        $draw = (int) $request->getQueryParam('draw', null);
        /**
         * @var int $start
         */
        $start = $request->getQueryParam('start', 0);
        /**
         * @var int $length
         */
        $length = $request->getQueryParam('length', 10);
        /**
         * @var array $search
         */
        $search = $request->getQueryParam('search', null);
        /**
         * @var array $order
         */
        $order = $request->getQueryParam('order', null);
        /**
         * @var array $columns
         */
        $columns = $request->getQueryParam('columns', null);

        /**
         * @var string $tableName
         */
        $tableName = $mapper->getModel()->getTable();

        /**
         * @var int $page
         */
        $page = self::generatePage((int) $start, (int) $length);

        /**
         * @var string $where Criterios de filtro
         */
        $where = '';

        /**
         * @var string $having Criterios del input de búsqueda de datatables
         */
        $having = self::generateHaving(
            array_filter(
                $columns_order,
                function ($v) use ($ignore_fields_in_where) {
                    return !in_array($v, $ignore_fields_in_where);
                }
            ),
            $columns,
            $search,
            $tableName,
            $ignore_table_on_fields_in_where
        );

        //Mezclar búsqueda de datatables con los criterios por defecto (funcionando actualmente)
        $having_string = trim($having_string);
        if (strlen($having_string) > 0) {
            if (strlen($having) > 0) {
                $having = "($having_string) AND $having";
            } else {
                $having = "($having_string)";
            }
        }

        //Mezclar búsqueda de datatables con los criterios por defecto (inútil, ahora la búsqueda es por HAVING)
        $where_string = trim($where_string);
        if (strlen($where_string) > 0) {
            if (strlen($where) > 0) {
                $where = "($where_string) AND $where";
            } else {
                $where = "($where_string)";
            }
        }

        //=============================================================

        //Mezclar ordenamiento de datatables con los criterios por defecto
        $order_by = self::generateOrderBy(
            $columns_order,
            $order,
            $custom_order,
            $ignore_table_in_order ? '' : $tableName
        );
        //=============================================================

        //Definir los valores de paginación
        $result->setValue('draw', $draw);
        $result->setValue('start', $start);
        $result->setValue('length', $length);
        $result->setValue('page', $page);
        //=============================================================

        //──── Ejecutar consultas ────────────────────────────────────────────────────────────────

        /**
         * @var \PiecesPHP\Core\BaseModel $model Modelo base de la tabla
         */
        $model = $mapper->getModel();

        //Ejecutar callable $on_set_model sobre el modelo
        if (!is_null($on_set_model)) {

            //Se espera que se devuelva un objeto de la clase \PiecesPHP\Core\BaseModel o que la herede
            $set_model_value = ($on_set_model)($model);

            if (is_subclass_of($set_model_value, '\PiecesPHP\Core\BaseModel')) {
                $model = $set_model_value;
            }
        }
        //=============================================================

        /*
         * Configuración de la consulta principal
         */

        /**
         * @var \PiecesPHP\Core\BaseModel $limit
         * Clon del modelo para los resultados sobre los que se aplicarán los filtros
         */
        $limit = clone $model;

        //Definir los campos seleccionados en la consulta
        if ($select_fields !== null) {
            $limit->select($select_fields);
        } else {
            $select_fields = "$tableName.*";
            $limit->select($select_fields);
        }

        /* Aplicar las diferentes cláusulas SQL y otras configuraciones*/

        //WHERE
        if (strlen($where) > 0) {
            $limit->where($where);
        }

        //HAVING
        if (strlen($having) > 0) {
            $limit->having($having);
        }

        //ORDER BY
        if (strlen($order_by) > 0) {
            $limit->orderBy($order_by);
        }

        //GROUP BY
        if (!is_null($group_string) && strlen($group_string) > 0) {
            $limit->groupBy($group_string);
        }

        //Ejecutar callable $config_result_model sobre el modelo
        if (is_callable($config_result_model)) {

            //Se espera que se devuelva un objeto de la clase \PiecesPHP\Core\BaseModel o que la herede
            $config_result_model_return = ($config_result_model)($select_fields, $order_by, $where, $limit);

            if (is_subclass_of($config_result_model_return, '\PiecesPHP\Core\BaseModel')) {
                $limit = $config_result_model_return;
            }

        }
        //=============================================================

        /*
         * Ejecutar consulta principal y configuraciones sobre el resultado
         */

        //Ejecutar consulta
        $limit->execute(false, (int) $page, $length);
        $result->setValue('SQL_MAIN_EXECUTED', str_replace(["\r", "\n"], '', $limit->getLastSQLExecuted()));

        /**
         * @var array $limitResult Resultado de la consulta principal
         */
        $limitResult = $limit->result();

        /**
         * @var array $data Array con los elementos resultantes
         */
        $data = [];

        //Iterar sobre la consulta para aplicar configuraciones
        foreach ($limitResult as $element) {

            //Verificar si cada elemento sera instanciado con su mapeador
            if ($as_mapper) {
                $class_mapper = get_class($mapper);
                $primary_key = $mapper->getPrimaryKey();
                $element = new $class_mapper($element->$primary_key, $primary_key);
            }

            if (!is_null($on_set_data)) {
                //Aplicar callable $on_set_data para procesar las filas

                //Espera un array que corresponde a una fila en datatables
                $data_process = ($on_set_data)($element);

                //Si no es un array se ignora el resultado (lo que perjudica las cuentas de los resultados)
                if (!is_array($data_process)) {
                    continue;
                }

                $data[] = $data_process;

            } else {
                //Procesamiento de filas integrado

                /**
                 * @var string[] $poperties Las propiedades (columnas) del modelo
                 */
                $properties = [];

                if (!$as_mapper) {
                    //Si no fue instanciado con el mapeador

                    $is_object = is_object($element);
                    $is_array = is_array($element);
                    $is_iterable = $is_object || $is_array;

                    if ($is_iterable) {

                        foreach ($element as $name => $property) {

                            $properties[] = $is_object ? $element->$name : $element[$name];

                        }

                    }

                } else {
                    //Si fue instanciado con el mapeador

                    $fields = $mapper->getFields();

                    $element = (object) $element->humanReadable();

                    foreach ($fields as $name => $field) {

                        $value = '';
                        $reference_table = $field['reference_table'];
                        $is_foreign = is_string($reference_table) && strlen($reference_table) > 0;

                        if ($is_foreign) {

                            $human_readable_reference_field = $field['human_readable_reference_field'];
                            $reference_field = $field['reference_field'];
                            $has_human_readable = is_string($human_readable_reference_field) && strlen($human_readable_reference_field) > 0;

                            if ($has_human_readable) {

                                if (is_object($element->$name)) {
                                    $value = $element->$name->$human_readable_reference_field;
                                } else {
                                    $value = null;
                                }

                            } else {

                                $value = $element->$name->$reference_field;
                            }

                        } else {

                            $value = $element->$name;
                        }

                        $properties[] = $value;
                    }
                }

                $data[] = $properties;
            }
        }

        //Ordenamiento de los resultados procesados con $on_set_data
        if (!is_null($on_set_data)) {

            $order_information = [];

            $order = is_array($order) ? $order : [];

            foreach ($order as $value) {

                $column_index = isset($value['column']) ? $value['column'] : null;
                $direction_ordering = isset($value['dir']) ? $value['dir'] : null;

                if (!is_null($columns_order) && !is_null($direction_ordering)) {

                    $direction_ordering = trim(strtoupper($direction_ordering)) == 'ASC' ? 'ASC' : 'DESC';
                    $column_name = isset($columns_order[$column_index]) ? $columns_order[$column_index] : null;

                    if (!is_null($column_name) && $column_name == self::INGNORE) {

                        $order_information[] = [
                            'direction' => $direction_ordering,
                            'index' => $column_index,
                        ];
                    }
                }
            }

            foreach ($order_information as $i) {

                $index = $i['index'];
                $direction = $i['direction'];

                usort($data, function ($a, $b) use ($index, $direction) {

                    $a = $a[$index];
                    $b = $b[$index];
                    $compare_result = 0;

                    if (is_string($a)) {

                        $compare_result = strnatcmp($a, $b);
                    } elseif (ctype_digit($a) || is_integer($a)) {

                        if ($a < $b) {
                            $compare_result = -1;
                        } elseif ($a > $b) {
                            $compare_result = 1;
                        }
                    }

                    if ($direction == 'DESC') {

                        if ($compare_result < 0) {
                            $compare_result = 1;
                        } elseif ($compare_result > 0) {
                            $compare_result = -1;
                        }
                    }

                    return $compare_result;
                });
            }
        }

        //Agregar al resultado los elementos procesados
        $result->setValue('data', $data);
        //Agregar al resultado los elementos crudos
        $result->setValue('rawData', $limitResult);
        //=============================================================

        //Realizar consulta para configurar los datos necesarios para la paginación

        /**
         * @var \PiecesPHP\Core\BaseModel $filterCount
         * Clon del modelo para la paginación
         */
        $filterCount = clone $model;

        $primary_key = $mapper->getPrimaryKey();

        //Definir los campos seleccionados en la consulta
        if ($select_fields !== null) {
            $filterCount->select($select_fields);
        } else {
            $select_fields = "$tableName.*";
            $filterCount->select($select_fields);
        }

        //WHERE
        if (strlen($where) > 0) {
            $filterCount->where($where);
        }

        //HAVING
        if (strlen($having) > 0) {
            $filterCount->having($having);
        }

        if (!is_null($group_string) && strlen($group_string) > 0) {

            //Con GROUP BY
            $filterCount->groupBy($group_string);

        }

        $filterCountSQLGenerated = $filterCount->getCompiledSQL();

        $filterCountSQL = "SELECT COUNT(*) AS total FROM (" . $filterCountSQLGenerated . ") AS table_derivate";

        $filterCountPrepared = $filterCount->prepare("$filterCountSQL");
        $filterCountPrepared->execute();
        $filterCountResult = $filterCountPrepared->fetchAll(\PDO::FETCH_OBJ);

        $filterCountTotal = count($filterCountResult) > 0 ? (int) $filterCountResult[0]->total : 0;
        $result->setValue('SQL_FILTER_COUNT_EXECUTED', str_replace(["\r", "\n"], '', $filterCountSQL));
        $result->setValue('recordsFiltered', $filterCountTotal);

        /**
         * @var \PiecesPHP\Core\BaseModel $totalCount
         * Clon del modelo para el conteo del total de elementos en la base de datos
         */
        $totalCount = clone $model;

        $totalCount->select("COUNT({$tableName}.{$primary_key}) AS total");
        $totalCount->execute();

        $result->setValue('SQL_TOTAL_COUNT_EXECUTED', str_replace(["\r", "\n"], '', $totalCount->getLastSQLExecuted()));
        $totalCount = $totalCount->result();

        $result->setValue('recordsTotal', count($totalCount) > 0 ? $totalCount[0]->total : 0);
        //=============================================================

        return $result;
    }

    /**
     * generateHaving
     *
     * Devuelve un array con la estructura de un HAVING para un EntityMapper
     *
     * @param array $columns_order
     * @param array $columns
     * @param mixed $search
     * @param string $table
     * @param string[] $ignore_table_on_fields
     * @return string
     */
    protected static function generateHaving(array $columns_order, array $columns, $search, string $table = '', array $ignore_table_on_fields = []): string
    {
        $having = [];

        //Verificar criterios de búsqueda
        $search_value = is_array($search) && isset($search['value']) ? trim($search['value']) : '';
        $has_search = strlen($search_value) > 0;

        $table = strlen(trim($table)) > 0 ? "$table." : '';

        if ($has_search) {

            foreach ($columns_order as $index => $column_name) {

                $column = isset($columns[$index]) ? $columns[$index] : null;

                if (!is_null($column)) {

                    $searchable = true;

                    if (isset($column['searchable'])) {

                        $searchable_value = $column['searchable'];

                        if ($searchable_value === 'true' || $searchable_value === '1' || $searchable_value === 'yes' || $searchable_value === 'on') {
                            $searchable_value = true;
                        }

                        if ($searchable_value === 'false' || $searchable_value === '0' || $searchable_value === 'no' || $searchable_value === 'off') {
                            $searchable_value = false;
                        }

                        $searchable = $searchable_value === true;

                    }

                    if ($searchable) {

                        $column_name = is_array($column_name) ? $column_name : [$column_name];

                        foreach ($column_name as $name) {

                            $skip_values = [
                                self::INGNORE,
                                self::ONLY_ORDER,
                            ];

                            if (in_array($name, $skip_values)) {
                                continue;
                            }

                            if (is_string($search_value)) {
                                $search_value = mb_strtoupper($search_value);
                            }

                            $_having_string = '(UPPER({FIELD_NAME}) LIKE "%{SEARCH_VALUE}%")';

                            if (in_array($name, $ignore_table_on_fields) || !self::$tableOnSearch) {
                                $_having_string = str_replace(
                                    [
                                        '{FIELD_NAME}',
                                        '{SEARCH_VALUE}',
                                    ],
                                    [
                                        $name,
                                        \addslashes($search_value),
                                    ],
                                    $_having_string
                                );
                            } else {
                                $_having_string = str_replace(
                                    [
                                        '{FIELD_NAME}',
                                        '{SEARCH_VALUE}',
                                    ],
                                    [
                                        $table . $name,
                                        \addslashes($search_value),
                                    ],
                                    $_having_string
                                );
                            }

                            $having[] = $_having_string;
                        }
                    }
                }
            }
        }

        $having = trim(implode(' OR ', $having));
        $having = strlen($having) > 0 ? "($having)" : '';

        return $having;
    }

    /**
     * generateOrderBy
     *
     * Devuelve un string con la estructura de un orderBy para un EntityMapper
     *
     * @param array $columns_order
     * @param mixed $order
     * @param array $custom_order
     * @param string $table
     * @return string
     */
    protected static function generateOrderBy(array $columns_order, $order, array $custom_order = null, string $table = ''): string
    {
        $order_by = [];

        $table = strlen(trim($table)) > 0 ? "$table." : '';

        if (!self::$tableOnOrder) {
            $table = '';
        }

        //Verificar criterios de ordenación
        if (is_array($order)) {

            foreach ($order as $value) {

                $column_index = isset($value['column']) ? $value['column'] : null;
                $direction_ordering = isset($value['dir']) ? $value['dir'] : null;

                if (!is_null($columns_order) && !is_null($direction_ordering)) {

                    $direction_ordering = trim(strtoupper($direction_ordering)) == 'ASC' ? 'ASC' : 'DESC';
                    $column_name = isset($columns_order[$column_index]) ? $columns_order[$column_index] : null;

                    if (!is_null($column_name)) {
                        $column_name = is_array($column_name) ? $column_name : [$column_name];
                        foreach ($column_name as $name) {

                            $skip_values = [
                                self::INGNORE,
                                self::ONLY_ORDER,
                            ];
                            if (in_array($name, $skip_values)) {
                                continue;
                            }
                            $order_by[] = $table . "$name $direction_ordering";
                        }
                    }
                }
            }
        }

        $custom_order = is_array($custom_order) ? $custom_order : [];

        foreach ($custom_order as $column => $direction) {
            $exists_order = false;
            foreach ($order_by as $index => $order_item) {
                if (strpos($order_item, $column) !== false) {
                    $exists_order = true;
                    break;
                }
            }
            if (!$exists_order) {
                $order_by[] = $table . "$column $direction";
            }
        }

        return count($order_by) > 0 ? implode(',', $order_by) : '';
    }

    /**
     * setTablePrefixOnOrder
     *
     * @param bool $value
     * @return void
     */
    public static function setTablePrefixOnOrder(bool $value)
    {
        self::$tableOnOrder = $value;
    }

    /**
     * setTablePrefixOnSearch
     *
     * @param bool $value
     * @return void
     */
    public static function setTablePrefixOnSearch(bool $value)
    {
        self::$tableOnSearch = $value;
    }

    /**
     * generatePage
     *
     * Devuelve un string con la estructura de un orderBy para un EntityMapper
     *
     * @param int $start
     * @param int $length
     * @return int
     */
    protected static function generatePage(int $start, int $length): int
    {
        //Calcular página actual
        $page = $start;
        if ($start == 0) {
            $page = 1;
        } elseif ($start == $length) {
            $page = 2;
        } else {
            $page = ceil((($start - $length) + 1) / $length) + 1;
        }
        return (int) $page;
    }
}
