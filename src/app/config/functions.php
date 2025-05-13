<?php

/**
 * functions.php
 */

use App\Controller\PublicAreaController;
use App\Model\UsersModel;
use PiecesPHP\Core\Exceptions\RouteNotExistsException;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * Funciones adicionales.
 * En este este archivo se puede añadir cualquier función adicional.
 * Puede hacerse uso de todas las funciones del sistema.
 */

/**
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
 * @return MenuGroupCollection
 */
function sidebar_menu()
{
    return get_config('menus')['sidebar'];
}

/**
 * @param array $options
 * @var \PiecesPHP\Core\Routing\RequestRoute $options['request'], required
 * @var \PiecesPHP\Core\Database\EntityMapper $options['mapper'], required
 * @var array $options['columns_order'], required
 * @var string $options['where_string']
 * @var callable $options['on_set_data'] Recibe por parámetro el elemento actual y debe devolver el valor que corresponderá a la fila
 * @var bool $options['as_mapper']
 * @var callable $options['on_set_model']
 * @return \PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations
 */
function datatables_proccessing_with_options(array $options)
{
    return \PiecesPHP\Core\Utilities\Helpers\DataTablesHelper::process($options);
}

/**
 * Devuelve un string con la estructura de un orderBy para un EntityMapper
 *
 * @param \PiecesPHP\Core\Routing\RequestRoute $request
 * @param \PiecesPHP\Core\Database\EntityMapper $mapper
 * @param array $columns_order
 * @param string $where_string
 * @param callable $on_set_data Recibe por parámetro el elemento actual y debe devolver el valor que corresponderá a la fila
 * @param bool $as_mapper
 * @param callable $on_set_model
 * @return \PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations
 */
function datatables_proccessing(
    \PiecesPHP\Core\Routing\RequestRoute $request,
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
        $values = array_flip($values);
    }

    $selected_values = is_array($selected_values) ? $selected_values : [$selected_values];

    foreach ($selected_values as $key => $value) {
        if (!is_scalar($key) || !is_scalar($value)) {
            unset($selected_values[$key]);
        }
    }

    $has_selected_values = !empty($selected_values);

    $options = [];

    $selected_setted = false;

    foreach ($values as $value => $display) {

        $optionValue = (string) $value;
        $optionText = $display;
        $optionTag = "<option%selected%value='{$optionValue}'>{$optionText}</option>";

        if ($has_selected_values && in_array($value, $selected_values)) {

            if (!$selected_setted || $multiple) {
                $optionTag = strReplaceTemplate($optionTag, [
                    '%selected%' => ' selected ',
                ]);
                $selected_setted = true;

            }

        }

        $optionTag = strReplaceTemplate($optionTag, [
            '%selected%' => ' ',
        ]);

        $options[] = $optionTag;
    }

    return trim(implode("\r\n", $options));
}

/**
 * Agrega un elemento en la posición indicada (solo asociativos)
 *
 * @param array $array
 * @param string|int $key Si el valor no es escalar se generará una llave aleatoriamente, si ya existe será sobreescrito
 * @param mixed $element
 * @param int $position Empezando desde 0
 * @return array
 */
function addElementInPosition(array $array, $key = null, $element = null, int $position = 0)
{
    $currentPosition = 0;
    $position = $position < 0 ? 0 : $position;

    $added = false;
    $result = [];

    if ($key === null || !is_scalar($key)) {
        $key = uniqid();
    }

    foreach ($array as $k => $i) {

        $add = $currentPosition == $position;
        $currentPosition++;

        if ($add && !$added) {
            $result[$key] = $element;
            $added = true;
        }

        $result[$k] = $i;

    }

    if (!$added) {
        $result[$key] = $element;
        $added = true;
    }

    return $result;
}

/**
 * Genera una ruta para una vista genérica
 *
 * @param string $name Nombre de la vista
 * @param string|null $folder Carpeta opcional donde buscar la vista
 * @param bool $silentOnNotExists Si es true, no lanzará excepción si la ruta no existe
 * @return string Retorna la URL de la ruta generada
 */
function genericViewRoute(string $name, ?string $folder = null, bool $silentOnNotExists = false)
{
    $exists = PublicAreaController::genericViewExists($name, $folder);
    $parameters = [
        'name' => $name,
    ];
    $route = 'generic';
    if ($folder !== null) {
        $parameters['folder'] = $folder;
        $route = 'generic-2';
    }
    $routeURL = '';

    if ($exists) {
        $routeURL = PublicAreaController::routeName($route, $parameters, $silentOnNotExists);
    } elseif (!$silentOnNotExists) {
        $fullName = $folder !== null ? "{$folder}/{$name}" : $name;
        throw new RouteNotExistsException(0, null, "{$route} ({$fullName})");
    }

    return $routeURL;
}

/**
 * Verifica si existe una vista genérica
 *
 * @param string $name Nombre de la vista a verificar
 * @param string|null $folder Carpeta opcional donde buscar la vista
 * @return bool Retorna true si la vista existe, false en caso contrario
 */
function genericViewRouteExists(string $name, ?string $folder = null)
{
    $exists = PublicAreaController::genericViewExists($name, $folder);
    return $exists;
}

/**
 * @param int[] $ignoreTypes
 * @return array
 */
function getAllUsers(array $ignoreTypes = [])
{

    $model = UsersModel::model();

    $model->select(UsersModel::fieldsToSelect());

    if (!empty($ignoreTypes)) {
        $ignoreTypes = implode(', ', $ignoreTypes);
        $model->where("type NOT IN ({$ignoreTypes})");
    }

    $model->execute();

    return $model->result();
}

/**
 * Un array listo para ser usado en array_to_html_options
 * @param string $defaultLabel
 * @param string $defaultValue
 * @param int[] $ignoreTypes
 * @param callable $elementStrategy
 * @return array
 */
function getAllUsersForSelect(string $defaultLabel = '', string $defaultValue = '', array $ignoreTypes = [], $elementStrategy = null)
{
    $defaultLabel = strlen($defaultLabel) > 0 ? $defaultLabel : __(LANG_GROUP, 'Usuarios');
    $options = [];
    $options[$defaultValue] = $defaultLabel;

    /**
     * @param ProductMapper $e
     */
    array_map(function ($e) use (&$options, $elementStrategy) {
        $e = is_callable($elementStrategy) ? ($elementStrategy)($e) : (object) [
            'value' => $e->id,
            'text' => "{$e->fullname} ({$e->username})",
        ];
        $options[$e->value] = $e->text;
    }, getAllUsers($ignoreTypes));

    return $options;
}
