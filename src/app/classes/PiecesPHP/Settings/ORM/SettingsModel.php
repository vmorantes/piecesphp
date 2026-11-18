<?php

/**
 * SettingsModel.php
 */
namespace PiecesPHP\Settings\ORM;

use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\Database\ActiveRecordModel;

/**
 * SettingsModel.
 *
 * @package     PiecesPHP\Settings\ORM
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c)
 * @property int|null $id
 * @property string $name
 * @property string|array|\stdClass $value
 */
class SettingsModel extends BaseEntityMapper
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
     * La foto de la tabla en una petición web (ADR 0080): rtrim(nombre, ' ') ⇒ [nombre guardado, valor guardado], tal como
     * vienen de la base. Se decodifica en cada lectura; nunca sale de esta clase.
     *
     * @var array<string,array{0:string,1:?string}>|null
     */
    private static ?array $snapshot = null;

    /**
     * @var bool|null Null: la foto solo en la web
     */
    private static ?bool $cacheOverrideForTesting = null;

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
        try {
            if (!self::optionExists($this->name)) {
                return parent::save();
            } else {
                return false;
            }
        } finally {
            self::forgetCache();
        }
    }

    /**
     * @inheritDoc
     */
    public function update()
    {
        try {
            return parent::update();
        } finally {
            self::forgetCache();
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
        $model = self::queryModel();

        $model->select()->execute();

        $rows = $model->result();
        $hasConfigurations = !empty($rows);
        //La misma lectura llena la foto: la primera petición no paga una consulta más (ADR 0080).
        if (self::cacheEnabled() && is_array($rows)) {
            self::$snapshot = self::snapshotFromRows($rows);
        }

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

                    $instance = new SettingsModel;
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
        $snapshot = self::cacheEnabled() ? self::loadSnapshot() : self::snapshotFromRows(self::selectAll());
        $data = [];
        foreach ($snapshot as [$rawName, $rawValue]) {
            //La fila que no se puede leer por su propio nombre (espacio delante, tabulador detrás) se salta: la de antes lanzaba.
            if (rtrim($rawName, ' ') !== trim($rawName)) {
                continue;
            }
            $data[$rawName] = self::decode($rawValue);
        }
        return $data;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param bool $staticConfig Sobreescribe la configuración local
     * @return bool Resultado de la operación
     */
    public static function setConfigValue(string $name, $value, bool $staticConfig = false)
    {
        $saved = false;
        $mapper = new SettingsModel($name);

        if ($mapper->id !== null) {
            $mapper->value = $value;
            $saved = $mapper->update();
        } else {
            $mapper->name = $name;
            $mapper->value = $value;
            $saved = $mapper->save();
        }

        if ($staticConfig) {
            set_config($name, $value);
        }

        return $saved;
    }

    /**
     * @param string $name
     * @param bool $staticConfig Si no existe en la base de datos intenta buscar en la configuración estática
     * @return mixed
     */
    public static function getConfigValue(string $name, bool $staticConfig = false)
    {
        if (!self::cacheEnabled()) {
            return self::readFromDatabase($name, $staticConfig);
        }
        $snapshot = self::loadSnapshot();
        //Como hoy: WhereItem::setRightMember() hace trim() completo del nombre buscado, y la base ignora los espacios finales.
        $key = trim($name);
        if (array_key_exists($key, $snapshot)) {
            return self::decode($snapshot[$key][1]);
        }
        return $staticConfig ? get_config($name) : null;
    }

    /**
     * Lee la opción de la base, sin la foto: para leer, comparar y escribir.
     *
     * @param string $name
     * @param bool $staticConfig Si no existe en la base de datos intenta buscar en la configuración estática
     * @return mixed
     */
    public static function readFromDatabase(string $name, bool $staticConfig = false)
    {

        if (self::optionExists($name)) {

            $option = new SettingsModel($name);

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
        $model = self::queryModel();
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
        $model = self::queryModel();
        $table = $model->getTable();

        $preparedStmt = $model->prepare("TRUNCATE $table");

        try {
            return $preparedStmt->execute();
        } finally {
            self::forgetCache();
        }
    }

    /**
     * Borra la opción y olvida la foto.
     *
     * @param string $name
     * @return bool
     */
    public static function deleteConfigValue(string $name): bool
    {
        try {
            return self::queryModel()->delete(['name' => $name])->execute() === true;
        } finally {
            self::forgetCache();
        }
    }

    /**
     * El modelo de la tabla. Quien lo pide puede escribir: olvida la foto. Las lecturas de esta clase usan queryModel().
     *
     * @return ActiveRecordModel
     */
    public static function model()
    {
        self::forgetCache();
        return (new SettingsModel())->getModel();
    }

    /**
     * Olvida la foto: la siguiente lectura va a la base. Quien escriba la tabla fuera de esta clase lo llama.
     *
     * @return void
     */
    public static function forgetCache(): void
    {
        self::$snapshot = null;
    }

    /**
     * Enciende (true), apaga (false) o devuelve a lo de siempre (null) la foto, la olvida y devuelve lo que había.
     *
     * @param bool|null $enabled
     * @return bool|null
     */
    public static function swapCacheForTesting(?bool $enabled): ?bool
    {
        $previous = self::$cacheOverrideForTesting;
        self::$cacheOverrideForTesting = $enabled;
        self::forgetCache();
        return $previous;
    }

    /**
     * Solo en la web: la terminal tiene procesos largos (colas, cron) en los que una foto se pondría rancia. PHP_SAPI, que
     * ninguna suite cambia, y no TerminalData.
     *
     * @return bool
     */
    private static function cacheEnabled(): bool
    {
        return self::$cacheOverrideForTesting ?? (PHP_SAPI !== 'cli');
    }

    /**
     * Construye un SettingsModel como hasta ahora: el primer mapper del proceso fija lc_time_names.
     *
     * @return ActiveRecordModel
     */
    private static function queryModel(): ActiveRecordModel
    {
        return (new SettingsModel())->getModel();
    }

    /**
     * @return array<int,object>
     */
    private static function selectAll(): array
    {
        $model = self::queryModel();
        $model->select()->execute();
        $rows = $model->result();
        return is_array($rows) ? $rows : [];
    }

    /**
     * @return array<string,array{0:string,1:?string}>
     */
    private static function loadSnapshot(): array
    {
        if (self::$snapshot === null) {
            self::$snapshot = self::snapshotFromRows(self::selectAll());
        }
        return self::$snapshot;
    }

    /**
     * La tabla es utf8mb4_bin: distingue mayúsculas e ignora solo los espacios del final. rtrim() a secas quitaría también
     * tabuladores y saltos, que la base distingue.
     *
     * @param array<int|string,mixed> $rows
     * @return array<string,array{0:string,1:?string}>
     */
    private static function snapshotFromRows(array $rows): array
    {
        $snapshot = [];
        foreach ($rows as $row) {
            $rawName = (string) $row->name;
            $key = rtrim($rawName, ' ');
            if (!array_key_exists($key, $snapshot)) {
                $snapshot[$key] = [$rawName, $row->value === null ? null : (string) $row->value];
            }
        }
        return $snapshot;
    }

    /**
     * Como el __get del mapper: una columna NULL da null, y lo demás pasa por la decodificación de json.
     *
     * @param string|null $rawValue
     * @return mixed
     */
    private static function decode(?string $rawValue)
    {
        return $rawValue === null ? null : self::castPHPToSQLTypes('json', $rawValue, true);
    }

}
