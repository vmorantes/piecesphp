<?php

/**
 * @pcsphp-config ambos
 * Qué conviene editar aquí: tus funciones, al final, en la sección del clon. Las de arriba son del framework.
 */

/**
 * functions.php
 */

use App\Controller\PublicAreaController;
use PiecesPHP\Core\Exceptions\RouteNotExistsException;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * Funciones globales de ayuda; las tuyas, al final. Formato: source-docs/project/docs/piecesphp/content/configuration.md, «functions.php».
 */

//── Del framework: no lo edites ──

/**
 * Devuelve las etiquetas <option> de un arreglo valor => texto, con el valor y el texto escapados
 *
 * @param array $values valor => texto; lo que no sea escalar se descarta
 * @param mixed $selected_values El valor seleccionado o una lista de ellos; se comparan contra el valor sin escapar
 * @param bool $multiple Si marca todos los seleccionados o solo el primero
 * @param bool $key_as_value Con false el arreglo se lee al revés: texto => valor
 * @return string Las opciones, separadas por saltos de línea
 */
function array_to_html_options(array $values, $selected_values = null, bool $multiple = false, bool $key_as_value = true): string
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

        //`false` en double_encode: lo que quien llama ya escapó no sale como `&amp;amp;` y una entidad puesta a propósito se conserva; `<`, `>` y las comillas se escapan igual.
        $optionValue = htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
        $optionText = htmlspecialchars((string) $display, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
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
function addElementInPosition(array $array, $key = null, $element = null, int $position = 0): array
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

    //Un usuario Eliminado deja de existir en la práctica: no se lista ni se elige.
    $where = 'status != ' . UsersModel::STATUS_USER_DELETED;
    if (!empty($ignoreTypes)) {
        $ignoreTypes = implode(', ', $ignoreTypes);
        $where .= " AND type NOT IN ({$ignoreTypes})";
    }
    $model->where($where);

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
function getAllUsersForSelect(string $defaultLabel = '', string $defaultValue = '', array $ignoreTypes = [], $elementStrategy = null): array
{
    $defaultLabel = $defaultLabel !== '' ? $defaultLabel : __(LANG_GROUP, 'Usuarios');
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

//── Del clon ──
//Tus funciones, aquí.
