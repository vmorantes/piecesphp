<?php

/**
 * AppConfigModel.php
 */
namespace App\Model;

use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\Database\ActiveRecordModel;

/**
 * AppConfigModel.
 *
 * @package     App\Model
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c)
 * @property int|null $id
 * @property string $name
 * @property string|array|\stdClass $value
 */
class AppConfigModel extends BaseEntityMapper
{
    protected $table = 'pcsphp_app_config';

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'name' => [
            'type' => 'varchar',
        ],
        'value' => [
            'type' => 'json',
            'null' => true,
        ],
    ];

    /**
     * @param mixed $value
     * @param string $column
     * @return static
     */
    public function __construct($value = null, string $column = 'name')
    {
        parent::__construct($value, $column);
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        if (!self::optionExists($this->name)) {
            return parent::save();
        } else {
            return false;
        }
    }

    /**
     * Agrega las opciones definidas si no hay información en la tabla o si se selecciona $overwrite en true,
     * en tal caso se aplica TRUNCATE
     *
     * @param array $data
     * @return array Resultado de la operación ['saved' => [...], 'error'=> [...]]
     */
    public static function initializateConfigurations(array $data, bool $overwrite = false)
    {
        $model = self::model();

        $model->select()->execute();

        $hasConfigurations = !empty($model->result());

        $result = [
            'saved' => [],
            'error' => [],
        ];

        if ($overwrite) {
            self::cleanAll();
        }

        if (!$hasConfigurations || $overwrite) {

            foreach ($data as $name => $value) {

                if (is_scalar($name)) {

                    $instance = new AppConfigModel;
                    $instance->name = $name;
                    $instance->value = $value;

                    $saved = $instance->save();

                    if ($saved) {
                        $result['saved'][] = $name;
                    } else {
                        $result['error'][] = $name;
                    }

                }

            }

        }
        return $result;
    }

    /**
     * @return array
     */
    public static function getConfigurations()
    {
        $model = self::model();
        $model->select()->execute();
        $rows = $model->result();
        $rows = is_array($rows) ? $rows : [];
        $data = [];

        foreach ($rows as $row) {
            $mapper = new AppConfigModel($row->name);
            $data[$mapper->name] = $mapper->value;
        }

        return $data;
    }

    /**
     * @param string $name
     * @param bool $staticConfig Si no existe en la base de datos intenta buscar en la configuración estática
     * @return mixed
     */
    public static function getConfigValue(string $name, bool $staticConfig = false)
    {

        if (self::optionExists($name)) {

            $option = new AppConfigModel($name);

            if ($option->id !== null) {
                return $option->value;
            } else {
                return null;
            }

        } else if ($staticConfig) {
            return get_config($name);
        }

        return null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function optionExists(string $name)
    {
        $model = self::model();
        $row = $model->select()->where(['name' => $name])->row();
        return $row !== false && $row !== -1;
    }

    /**
     * Aplica TRUNCATE a la tabla
     *
     * @return bool
     */
    public static function cleanAll()
    {
        $model = self::model();
        $table = $model->getTable();

        $preparedStmt = $model->prepare("TRUNCATE $table");

        return $preparedStmt->execute();
    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new AppConfigModel())->getModel();
    }

}
