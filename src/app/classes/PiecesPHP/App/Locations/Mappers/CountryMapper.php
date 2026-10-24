<?php

/**
 * CountryMapper.php
 */

namespace PiecesPHP\App\Locations\Mappers;

use PiecesPHP\App\Locations\LocationsLang;
use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\Database\ActiveRecordModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;

/**
 * CountryMapper.
 *
 * Mapper de países
 *
 * @package     PiecesPHP\App\Locations\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 * @property int|null $id
 * @property string|null $code
 * @property string|null $phoneCode
 * @property string|null $demonym
 * @property string $name
 * @property string|null $region
 * @property int $active
 */
class CountryMapper extends BaseEntityMapper
{
    const PREFIX_TABLE = 'locations_';
    const TABLE = 'countries';

    const ACTIVE = 1;
    const INACTIVE = 0;

    const STATUS = [
        self::ACTIVE => 'Activo',
        self::INACTIVE => 'Inactivo',
    ];

    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'code' => [
            'type' => 'varchar',
            'null' => true,
        ],
        'phoneCode' => [
            'type' => 'varchar',
            'length' => 10,
            'null' => true,
        ],
        'demonym' => [
            'type' => 'varchar',
            'null' => true,
        ],
        'name' => [
            'type' => 'varchar',
        ],
        'region' => [
            'type' => 'text',
            'null' => true,
        ],
        'active' => [
            'type' => 'int',
            'default' => self::ACTIVE,
        ],
    ];

    /**
     * @var string
     */
    protected $table = self::PREFIX_TABLE . self::TABLE;

    /**
     * @param int $value
     * @param string $field_compare
     * @return static
     */
    public function __construct(?int $value = null, string $field_compare = 'id')
    {
        parent::__construct($value, $field_compare);
    }

    /**
     * La forma de un prefijo telefónico: `+` y de una a cuatro cifras, sin cero delante (`+57`, `+1`).
     * Es el contrato con el que se valida el prefijo de un país y el que se guarda en perfiles y organizaciones.
     *
     * @param string $phoneCode
     * @return bool
     */
    public static function isValidPhoneCode(string $phoneCode): bool
    {
        //\z y no $: con $, «+57» seguido de un salto de línea también casaba.
        return preg_match('/^\+[1-9][0-9]{0,3}\z/', $phoneCode) === 1;
    }

    /**
     * Las opciones del desplegable de prefijo, listas para `array_to_html_options()`, que NO escapa: clave y texto ya
     * pasan por `escape_html()`, y el seleccionado se le pasa escapado también. Son los países ACTIVOS con prefijo.
     * Clave, el prefijo (lo que se guarda); texto, «<prefijo> · <país>», con los países del mismo prefijo juntos y en
     * orden alfabético. Ordenadas por el número del prefijo.
     *
     * @param string|null $current El valor guardado: si ningún país lo ofrece, se añade al final tal cual, para no perderlo.
     * @param bool $withEmptyOption Con la opción vacía primero.
     * @return array<string,string>
     */
    public static function phoneCodeOptions(?string $current = null, bool $withEmptyOption = false): array
    {
        $options = $withEmptyOption ? ['' => __(GLOBAL_LANG_GROUP, 'Código de área')] : [];
        return self::escapedOptions($options + self::rawPhoneCodeOptions(), $current);
    }

    /**
     * Las opciones del desplegable de nacionalidad, listas para `array_to_html_options()`, que NO escapa: clave y texto
     * ya pasan por `escape_html()`, y el seleccionado se le pasa escapado también. Son los gentilicios de los países
     * ACTIVOS. Clave, el gentilicio (lo que se guarda); texto, el gentilicio traducido con el grupo de nombres de
     * Ubicaciones. Sin repetidos y en orden alfabético del texto.
     *
     * @param string|null $current El valor guardado: si ningún país lo ofrece, se añade al final tal cual, para no perderlo.
     * @param bool $withEmptyOption Con la opción vacía primero.
     * @return array<string,string>
     */
    public static function demonymOptions(?string $current = null, bool $withEmptyOption = false): array
    {
        $options = $withEmptyOption ? ['' => __(GLOBAL_LANG_GROUP, 'Seleccione una nacionalidad')] : [];
        return self::escapedOptions($options + self::rawDemonymOptions(), $current);
    }

    /**
     * Si un prefijo se puede guardar en un registro: vacío, el que ese registro ya tenía, o uno que ofrece un país activo.
     *
     * @param string|null $value Lo recibido.
     * @param string|null $stored Lo que ese registro tiene guardado (null en un alta).
     * @return bool
     */
    public static function acceptsPhoneCode(?string $value, ?string $stored): bool
    {
        return self::acceptsOption($value, $stored, array_keys(self::rawPhoneCodeOptions()));
    }

    /**
     * Si una nacionalidad se puede guardar en un registro: vacía, la que ese registro ya tenía, o el gentilicio de un país
     * activo.
     *
     * @param string|null $value Lo recibido.
     * @param string|null $stored Lo que ese registro tiene guardado (null en un alta).
     * @return bool
     */
    public static function acceptsDemonym(?string $value, ?string $stored): bool
    {
        return self::acceptsOption($value, $stored, array_keys(self::rawDemonymOptions()));
    }

    /**
     * @return array<string,string> Prefijo => texto, en crudo.
     */
    private static function rawPhoneCodeOptions(): array
    {
        $namesByCode = [];
        foreach (self::all(false, true) as $country) {
            $code = is_string($country->phoneCode) ? trim($country->phoneCode) : '';
            if ($code !== '') {
                $namesByCode[$code][] = __(LocationsLang::LANG_GROUP_NAMES, (string) $country->name);
            }
        }
        uksort($namesByCode, fn ($a, $b) => (int) substr((string) $a, 1) <=> (int) substr((string) $b, 1));

        $options = [];
        foreach ($namesByCode as $code => $names) {
            sort($names, \SORT_STRING | \SORT_FLAG_CASE);
            $options[(string) $code] = $code . ' · ' . implode(', ', $names);
        }
        return $options;
    }

    /**
     * @return array<string,string> Gentilicio => texto, en crudo.
     */
    private static function rawDemonymOptions(): array
    {
        $options = [];
        foreach (self::all(false, true) as $country) {
            $demonym = is_string($country->demonym) ? trim($country->demonym) : '';
            if ($demonym !== '') {
                $options[$demonym] = __(LocationsLang::LANG_GROUP_NAMES, $demonym);
            }
        }
        asort($options, \SORT_STRING | \SORT_FLAG_CASE);
        return $options;
    }

    /**
     * El valor guardado que ningún país ofrece se añade al final; y todo sale escapado.
     *
     * @param array<int|string,string> $options En crudo.
     * @param string|null $current
     * @return array<string,string>
     */
    private static function escapedOptions(array $options, ?string $current): array
    {
        if ($current !== null && $current !== '' && !in_array($current, array_map('strval', array_keys($options)), true)) {
            $options[$current] = $current;
        }
        $escaped = [];
        foreach ($options as $value => $text) {
            $escaped[escape_html((string) $value)] = escape_html($text);
        }
        return $escaped;
    }

    /**
     * Se compara en PHP y no en la consulta: el marcador de un WhereItem sale del texto y el «+» lo revienta (D11).
     *
     * @param string|null $value
     * @param string|null $stored
     * @param array<int|string> $offered En crudo.
     * @return bool
     */
    private static function acceptsOption(?string $value, ?string $stored, array $offered): bool
    {
        $value = $value !== null ? trim($value) : '';
        if ($value === '' || $value === $stored) {
            return true;
        }
        return in_array($value, array_map('strval', $offered), true);
    }

    /**
     * Campos extra:
     *  - idPadding
     * @return string[]
     */
    protected static function fieldsToSelect()
    {

        $mapper = new CountryMapper();

        $table = self::PREFIX_TABLE . self::TABLE;

        $fields = [
            "LPAD({$table}.id, GREATEST(5, CHAR_LENGTH({$table}.id)), '0') AS idPadding",
        ];

        $allFields = array_keys($mapper->getFields());

        foreach ($allFields as $field) {
            $fields[] = "{$table}.{$field}";
        }

        return $fields;

    }

    /**
     * @param mixed $value
     * @param string $column
     * @param boolean $as_mapper
     * @return ($as_mapper is true ? static : \stdClass)|null
     */
    public static function getBy($value, string $column = 'id', bool $as_mapper = false)
    {
        $model = self::model();

        $where = [
            $column => $value,
        ];

        $model->select()->where($where);

        $model->execute();

        $result = $model->result();

        $result = !empty($result) ? $result[0] : null;

        if (!is_null($result) && $as_mapper) {
            $result = new CountryMapper($result->id);
        }

        return $result;
    }

    /**
     * @param bool $as_mapper
     * @param bool $onlyActives
     * @return static[]|array
     */
    public static function all(bool $as_mapper = false, bool $onlyActives = false)
    {
        $model = self::model();

        $model->select(self::fieldsToSelect());

        if ($onlyActives) {
            $model->where([
                'active' => self::ACTIVE,
            ]);
        }

        $model->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        if ($as_mapper) {
            $result = array_map(function ($e) {
                return new CountryMapper($e->id);
            }, $result);
        }

        return $result;
    }

    /**
     * @param string $column
     * @param string $critery
     * @param bool $asMapper
     * @param bool $onlyActives
     * @return static[]|array
     */
    public static function allBy(string $column, string $critery, bool $asMapper = false, bool $onlyActives = false)
    {
        $model = self::model();

        $model->select(self::fieldsToSelect());
        $where = [
            $column => $critery,
        ];

        if ($onlyActives) {
            $where['active'] = self::ACTIVE;
        }

        $model->where($where);
        $model->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        if ($asMapper) {
            $result = array_map(function ($e) {
                return new CountryMapper($e->id);
            }, $result);
        }

        return $result;
    }

    /**
     * @param string[] $regions
     * @param bool $asMapper
     * @param bool $onlyActives
     * @return static[]|array
     */
    public static function allByRegions(array $regions = [], bool $asMapper = false, bool $onlyActives = false)
    {
        $model = self::model();

        $model->select(self::fieldsToSelect());
        $regions[] = 'NONE';
        $regionsForFindInSet = implode(',', $regions);
        $whereSegment = new WhereSegment([
            WhereItem::findInSet($regionsForFindInSet, 'region', true),
        ]);

        if ($onlyActives) {
            $whereSegment->addCritery(new WhereItem('active', WhereItem::EQUAL_OPERATOR, self::ACTIVE, WhereItem::AND_OPERATOR));
        }

        $model->where($whereSegment);
        $model->execute();

        $result = $model->result();
        $result = is_array($result) ? $result : [];

        if ($asMapper) {
            $result = array_map(function ($e) {
                return new CountryMapper($e->id);
            }, $result);
        }

        return $result;
    }

    /**
     * @return \stdClass[]
     */
    public static function allRegions()
    {
        $model = self::model();
        $prepared = $model->prepare("SELECT region AS name FROM locations_countries GROUP BY region ORDER BY region ASC");
        $prepared->execute();
        return $prepared->fetchAll(\PDO::FETCH_OBJ);
    }

    /**
     * @param string $defaultLabel
     * @param string $defaultValue
     * @return array
     */
    public static function allForSelect(string $defaultLabel = '', string $defaultValue = '')
    {
        $defaultLabel = mb_strlen($defaultLabel) > 0 ? $defaultLabel : __(LOCATIONS_LANG_GROUP, 'Países');
        $options = [];
        $options[$defaultValue] = $defaultLabel;

        array_map(function ($e) use (&$options) {
            $options[(string) $e->id] = $e->name;
        }, self::all(false, true));

        return $options;
    }

    /**
     * @param string $defaultLabel
     * @param string $defaultValue
     * @return array
     */
    public static function allRegionsForSelect(string $defaultLabel = '', string $defaultValue = '')
    {
        $defaultLabel = mb_strlen($defaultLabel) > 0 ? $defaultLabel : __(LOCATIONS_LANG_GROUP, 'Regiones');
        $options = [];
        $options[$defaultValue] = $defaultLabel;

        array_map(function ($e) use (&$options) {
            //Un país sin región llega aquí con `name` a NULL, y de índice ABORTA. Ver T97.
            $name = is_string($e->name) ? trim($e->name) : '';
            if ($name === '') {
                return;
            }
            $options[$name] = $name;
        }, self::allRegions());

        return $options;
    }

    /**
     * @param string $name
     * @param int $ignore_id
     * @return bool
     */
    public static function isDuplicateName(string $name, int $ignore_id)
    {
        $model = self::model();

        //Por marcador: el valor viaja como dato y no depende de sql_mode (ADR 0009).
        $model->select()->where(new WhereSegment([
            WhereItem::isEqual('name', $name, WhereItem::AND_OPERATOR),
            WhereItem::isNotEqual('id', $ignore_id),
        ]))->execute();

        $result = $model->result();

        return !empty($result);
    }

    /**
     * @param string $code
     * @param int $ignore_id
     * @return bool
     */
    public static function isDuplicateCode(?string $code = null, int $ignore_id = 0)
    {

        if ($code !== null) {

            $model = self::model();

            //Por marcador: el valor viaja como dato y no depende de sql_mode (ADR 0009).
            $model->select()->where(new WhereSegment([
                WhereItem::isEqual('code', $code, WhereItem::AND_OPERATOR),
                WhereItem::isNotEqual('id', $ignore_id),
            ]))->execute();

            $result = $model->result();

            return !empty($result);

        } else {
            return false;
        }

    }

    /**
     * @return ActiveRecordModel
     */
    public static function model()
    {
        return (new CountryMapper)->getModel();
    }

}
