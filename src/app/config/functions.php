<?php

/**
 * functions.php
 */

/**
 * Funciones adicionales.
 * En este este archivo se puede añadir cualquier función adicional.
 * Puede hacerse uso de todas las funciones del sistema.
 */

/**
 * menu_header_items
 *
 * Devuelve un string con los ítems del menú desplegable del header
 *
 * @param \stdClass $user
 * @return string
 */
function menu_header_items(\stdClass $user): string
{
    $items = get_config('menus')['header_dropdown'];
    return $items->getHtml();
}

/**
 * menu_sidebar_items
 *
 * Devuelve un string con los ítems del menú lateral
 *
 * @param \stdClass $user
 * @return string
 */
function menu_sidebar_items(\stdClass $user): string
{
    $groups = get_config('menus')['sidebar'];
    return $groups->getHtml();
}

/**
 * datatables_proccessing_with_options
 *
 * @param array $options
 *
 * @var $options[request] \Slim\Http\Request, required
 * @var $options[mapper] \PiecesPHP\Core\Database\EntityMapper, required
 * @var $options[columns_order] array, required
 * @var $options[where_string] string
 * @var $options[on_set_data] callable Recibe por parámetro el elemento actual y debe devolver el valor que corresponderá a la fila
 * @var $options[as_mapper] bool
 * @var $options[on_set_model] callable
 * @return \PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations
 */
function datatables_proccessing_with_options(array $options)
{
    return \PiecesPHP\Core\Utilities\Helpers\DataTablesHelper::process($options);
}

/**
 * datatables_proccessing
 *
 * Devuelve un string con la estructura de un orderBy para un EntityMapper
 *
 * @param \Slim\Http\Request $request
 * @param \PiecesPHP\Core\Database\EntityMapper $mapper
 * @param array $columns_order
 * @param string $where_string
 * @param callable $on_set_data Recibe por parámetro el elemento actual y debe devolver el valor que corresponderá a la fila
 * @param bool $as_mapper
 * @param callable $on_set_model
 * @return \PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations
 */
function datatables_proccessing(
    \Slim\Http\Request $request,
    \PiecesPHP\Core\Database\EntityMapper $mapper,
    array $columns_order,
    string $where_string = null,
    callable $on_set_data = null,
    bool $as_mapper = false,
    callable $on_set_model = null
): \PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations {
    return \PiecesPHP\Core\Utilities\Helpers\DataTablesHelper::process([
        'request' => $request,
        'mapper' => $mapper,
        'columns_order' => $columns_order,
        'where_string' => $where_string,
        'on_set_data' => $on_set_data,
        'as_mapper' => $as_mapper,
        'on_set_model' => $on_set_model,
    ]);
}

/**
 * array_to_html_options
 *
 * Devuelve un string con la estructura de un orderBy para un EntityMapper
 *
 * @param array $values
 * @param mixed $selected_values
 * @param bool $multiple
 * @param bool $key_as_value
 * @return string
 */
function array_to_html_options(array $values, $selected_values = null, bool $multiple = false, bool $key_as_value = true)
{
    foreach ($values as $key => $value) {
        if (!is_scalar($key) || !is_scalar($value)) {
            unset($values[$key]);
        }
    }

    if (!$key_as_value) {
        $values = array_flip($value);
    }

    $selected_values = is_array($selected_values) && !is_null($selected_values) ? $selected_values : [$selected_values];
    $has_selected_values = !is_null($selected_values);

    foreach ($selected_values as $key => $value) {
        if (!is_scalar($key) || !is_scalar($value)) {
            unset($selected_values[$key]);
        }
    }

    $options = [];

    $selected_setted = false;

    foreach ($values as $value => $display) {

        $option = new \PiecesPHP\Core\HTML\HtmlElement('option', $display);

        $option->setAttribute('value', (string) $value);

        if ($has_selected_values && in_array($value, $selected_values)) {

            if (!$selected_setted || $multiple) {

                $option->setAttribute('selected', '');
                $selected_setted = true;

            }

        }

        $options[] = (string) $option;
    }

    return trim(implode("\r\n", $options));
}
