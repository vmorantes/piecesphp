<?php

/**
 * GenericContentPseudoMapper.php
 */

namespace PiecesPHP\BuiltIn\Helpers\Mappers;

use PiecesPHP\BuiltIn\Helpers\Exceptions\SafeException;
use PiecesPHP\BuiltIn\Helpers\HelpersSystemLang;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\Settings\ORM\SettingsModel;

/**
 * GenericContentPseudoMapper.
 *
 * @package     PiecesPHP\BuiltIn\Helpers;\Mappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 * @property int $tokensLimit
 * @property array<string,int> $tokensUsed
 * @property array<string,array<string,array<string,string>>> $dynamicTranslations
 * @property string|\DateTime|null $dynamicTranslationsUpdatedAt
 */
class GenericContentPseudoMapper
{

    const LANG_GROUP = HelpersSystemLang::LANG_GROUP;
    const CONTENT_TOKENS_LIMIT = 'tokensLimit';
    const CONTENT_TOKENS_USED = 'tokensUsed';
    const CONTENT_DYNAMIC_TRANSLATIONS = 'DYNAMIC_TRANSLATIONS';
    /**
     * El nombre con la errata que se usó hasta la 9.0. Solo se lee, si la fila nueva no existe
     * (databases/actualizaciones/2026-10-26-errata-traducciones-dinamicas.sql la renombra).
     */
    const LEGACY_CONTENT_DYNAMIC_TRANSLATIONS = 'DYNAMIC_TRANSCALTIONS';
    const CONTENT_DYNAMIC_TRANSLATIONS_UPDATED_AT = 'DYNAMIC_TRANSLATIONS_UPDATED_AT';
    const CONTENT_MAPBOX_KEYS = 'MAPBOX_KEYS';
    const CONTENT_MAP_DEFAULT_CENTER = 'MAP_DEFAULT_CENTER';

    /**
     * Donde se abre un mapa sin otra posición, y su acercamiento: Barranquilla, el de la página vieja del mapa.
     */
    const MAP_DEFAULT_CENTER = [
        'longitude' => -74.8065913846496,
        'latitude' => 11.0021516003209,
        'zoom' => 11,
    ];
    const CONTENT_HOME_IMAGE = 'homeImage';

    /**
     * Define las propiedades del mapper
     *
     * @var array
     */
    protected $properties = [
        self::CONTENT_TOKENS_LIMIT => 50000000,
        self::CONTENT_TOKENS_USED => [
            AI_OPENAI => 0,
            AI_MISTRAL => 0,
        ],
        self::CONTENT_DYNAMIC_TRANSLATIONS => [],
        self::CONTENT_DYNAMIC_TRANSLATIONS_UPDATED_AT => null,
        self::CONTENT_MAPBOX_KEYS => [
            'keyLocal' => '',
            'keyDomain' => '',
        ],
        self::CONTENT_HOME_IMAGE => null,
        self::CONTENT_MAP_DEFAULT_CENTER => self::MAP_DEFAULT_CENTER,
    ];

    /**
     * Define la estrategia de parseo de datos en el intercambio con la base de datos
     * @example
     * "property" => "datetime",
     * @var array
     */
    protected $propertiesParseData = [
        self::CONTENT_DYNAMIC_TRANSLATIONS_UPDATED_AT => 'datetime',
        self::CONTENT_DYNAMIC_TRANSLATIONS => 'associativeArray',
        self::CONTENT_MAPBOX_KEYS => 'associativeArray',
        self::CONTENT_MAP_DEFAULT_CENTER => 'associativeArray',
    ];

    /**
     * Propiedades necesitan multi-idioma
     *
     * @var string[]
     */
    protected $translatableProperties = [
        //self::CONTENT_TOKENS_LIMIT,
    ];

    /**
     * Idioma base
     *
     * @var string
     */
    protected string $baseLang;

    /**
     * Valores según cada idioma
     *
     * @var \stdClass
     */
    protected \stdClass $langData;

    /**
     * Nombre del contenido
     *
     * @var string
     */
    protected string $contentName;

    /**
     * Nombre del contenido establecido por el usuario
     *
     * @var string
     */
    protected string $userSetContentName;

    /**
     * ORM del contenido
     *
     * @var SettingsModel
     */
    protected SettingsModel $orm;

    /**
     * NO ESCRIBE. La fila se crea al guardar, que es cuando alguien decide que existe.
     *
     * Antes el constructor la creaba si no estaba, así que abrir un formulario y no enviarlo
     * dejaba rastro — una creación en un camino de lectura. Nada dependía de que existiera: los
     * valores por defecto viven en las propiedades de esta clase, y `save()` ya inserta cuando
     * no hay fila. Ver T69.
     *
     * @param string $contentName
     * @return static
     */
    public function __construct(string $contentName = 'default')
    {
        $this->userSetContentName = $contentName;
        $this->contentName = self::nameFor($this->userSetContentName);
        $this->baseLang = Config::get_default_lang();
        $this->langData = new \stdClass;
        $this->orm = new SettingsModel($this->contentName);
        if ($this->orm->id === null) {
            //Solo se prepara el nombre: quien guarde encontrará la fila lista para insertarse.
            $this->orm->name = $this->contentName;
            //Sin migrar, las traducciones pendientes siguen en la fila con la errata: se leen de ahí, sin escribir en ella;
            //el primer guardado ya va a la fila nueva.
            if ($this->userSetContentName === self::CONTENT_DYNAMIC_TRANSLATIONS) {
                $legacy = new SettingsModel(self::nameFor(self::LEGACY_CONTENT_DYNAMIC_TRANSLATIONS));
                $legacyValue = $legacy->id !== null ? (array) $legacy->value : [];
                if (array_key_exists(self::LEGACY_CONTENT_DYNAMIC_TRANSLATIONS, $legacyValue)) {
                    $this->fromArray([self::CONTENT_DYNAMIC_TRANSLATIONS => $legacyValue[self::LEGACY_CONTENT_DYNAMIC_TRANSLATIONS]], true);
                }
            }
        } else {
            $this->fromArray((array) $this->orm->value, true);
        }
    }

    /**
     * El nombre de la fila de configuración de un contenido. Lo usan el constructor y la declaración del catálogo
     * (ConfigOptions): una sola fórmula, para que lo declarado sea lo que se guarda.
     *
     * @param string $content
     * @return string
     */
    public static function nameFor(string $content): string
    {
        $name = sha1(GenericContentPseudoMapper::class) . "|{$content}";
        return mb_strlen($name) > 255 ? sha1($name) : $name;
    }

    /**
     * @param string $baseLang
     * @return static
     */
    public function setBaseLang(string $baseLang)
    {
        $this->baseLang = $baseLang;
        return $this;
    }

    /**
     * @return string
     */
    public function contentName()
    {
        return $this->contentName;
    }

    /**
     * @return string
     */
    public function userSetContentName()
    {
        return $this->userSetContentName;
    }

    /**
     * @return bool
     */
    public function save()
    {
        $saveResult = false;
        $this->orm->value = $this->toArray(true);
        if ($this->orm->id === null) {
            $saveResult = $this->orm->save();
            $idInserted = $this->orm->getLastInsertID();
            if ($idInserted !== null) {
                $this->orm->id = $idInserted;
            }
        } else {
            $saveResult = $this->orm->update();
        }
        return $saveResult;

    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $classname = get_class($this);

        //Comprobar si la propiedad es privada
        $reflection = new \ReflectionClass(self::class);
        $privateProperties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);
        foreach ($privateProperties as $property) {
            if ($property->name == $name) {
                throw new SafeException("La propiedad $classname::$name es privada");
            }
        }

        //Comprobar si existe la propiedad
        if (array_key_exists($name, $this->properties)) {
            return $this->properties[$name];
        } else if (property_exists($this, $name)) {
            return $this->$name;
        } else {
            $notDefinedPropertyException = new SafeException("La propiedad $classname::$name no está definida.");
            log_exception(new SafeException("La propiedad $classname::$name no está definida."));
            throw $notDefinedPropertyException;
        }
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $classname = get_class($this);

        //Comprobar si la propiedad es privada/protegida
        $reflection = new \ReflectionClass($classname);
        $privateProperties = $reflection->getProperties(\ReflectionProperty::IS_PRIVATE);
        foreach ($privateProperties as $property) {
            if ($property->name == $name) {
                throw new SafeException("La propiedad $classname::$name es privada");
            }
        }
        $protectedProperties = $reflection->getProperties(\ReflectionProperty::IS_PROTECTED);
        foreach ($protectedProperties as $property) {
            if ($property->name == $name) {
                throw new SafeException("La propiedad $classname::$name es protegida");
            }
        }

        $propertyExists = false;

        if (array_key_exists($name, $this->properties)) {
            $this->properties[$name] = $value;
            $propertyExists = true;
        } else if (property_exists($this, $name)) {
            $this->$name = $value;
            $propertyExists = true;
        }

        if (!$propertyExists) {
            $notDefinedPropertyException = new SafeException("La propiedad $classname::$name no está definida.");
            log_exception(new SafeException("La propiedad $classname::$name no está definida."));
            throw $notDefinedPropertyException;
        }
    }

    /**
     * Asigna datos a una propiedad para múltiples idiomas.
     *
     * Este método itera sobre un arreglo de idiomas y llama a setLangData para cada idioma,
     * permitiendo la asignación de datos específicos para cada uno.
     * Se asegura de que solo se asignen datos si el idioma está presente en el arreglo de datos.
     *
     * @param string $name El nombre de la propiedad a la que se asignarán los datos.
     * @param array<string,mixed> $data Un arreglo asociativo donde la clave es el idioma y el valor es el dato a asignar.
     * @param string[] $langs Un arreglo de idiomas para los cuales se asignarán los datos.
     */
    public function addDataManyLangs(string $name, array $data, array $langs)
    {
        foreach ($langs as $lang) {
            if (is_string($lang) && array_key_exists($lang, $data)) {
                $this->setLangData($lang, $name, $data[$lang]);
            }
        }
    }

    /**
     * Define una propiedad que está habilitada en multi-idioma en un idioma en específico
     * @param string $lang
     * @param string $property
     * @param mixed $data
     * @return static
     */
    public function setLangData(string $lang, string $property, $data)
    {

        $translatables = $this->translatableProperties;
        $baseLang = $this->baseLang;

        if (in_array($property, $translatables)) {

            if ($lang !== $baseLang) {

                if (!isset($this->langData->$lang)) {
                    $this->langData->$lang = new \stdClass;
                }

                $this->langData->$lang->$property = $data;

            } else {
                $this->$property = $data;
            }

        } else {
            $this->$property = $data;
        }

        return $this;
    }

    /**
     * Obtiene una propiedad que está habilitada en multi-idioma en un idioma en específico o
     * el valor de la propiedad por defecto
     * @param string $lang
     * @param string $property
     * @param bool $defaultOnEmpty
     * @param mixed $returnOnEmpty
     * @return mixed
     * Si la propiedad no existe se devolerá null
     * Si $defaultOnEmpty está en true, cuando no exista en el idioma seleccionado se tomará de las propiedades principales
     * Si $defaultOnEmpty está en false, cuando no exista en el idioma seleccionado se devolverá $returnOnEmpty
     */
    public function getLangData(string $lang, string $property, bool $defaultOnEmpty = true, $returnOnEmpty = '')
    {

        $baseLang = $this->baseLang;

        if (isset($this->langData->$lang) && isset($this->langData->$lang->$property)) {

            $value = $this->langData->$lang->$property;

            //Si para una propiedad se quiere un compartamiento particular en su obtención
            //puede definírsele aquí
            $specialBehaviour = [
                'SAMPLE_FIELD' => function ($value) {
                    return $value;
                },
            ];

            return array_key_exists($property, $specialBehaviour) ? ($specialBehaviour[$property])($value) : $value;

        } elseif ($defaultOnEmpty || $lang === $baseLang) {

            $propertyExists = property_exists($this, $property) || array_key_exists($property, $this->properties);

            if ($propertyExists) {

                $value = $this->$property;

                //Si para una propiedad se quiere un compartamiento particular en su obtención
                //puede definírsele aquí
                $specialBehaviour = [
                    'SAMPLE_FIELD' => function ($value) {
                        return $value;
                    },
                ];

                return array_key_exists($property, $specialBehaviour) ? ($specialBehaviour[$property])($value) : $value;

            }

            return null;

        } else {

            return $returnOnEmpty;

        }

    }

    /**
     * @param string $property
     * @return mixed
     */
    public function currentLangData(string $property)
    {
        $lang = Config::get_lang();
        return $this->getLangData($lang, $property);
    }

    /**
     * Verifica si existe un idioma en los datos del elemento
     * @param string $lang
     * @return bool
     */
    public function hasLang(string $lang)
    {
        $baseLang = $this->baseLang;
        $langData = $this->langData;
        $hasLangData = isset($langData->$lang);
        return $baseLang === $lang || $hasLangData;
    }

    /**
     * Verifica si una propiedad es traducible
     * @param string $propertyName
     * @return bool
     */
    public function isTranslatable(string $propertyName)
    {
        return in_array($propertyName, $this->translatableProperties);
    }

    /**
     * Devuelve los campos que son traducibles
     *
     * @return string[]
     */
    public function getTranslatableProperties()
    {
        return $this->translatableProperties;
    }

    /**
     * @param bool $parseFromSQL
     * @param array $data
     * @return static
     */
    public function fromArray(array $data, bool $parseFromSQL = false)
    {
        foreach ($data as $property => $value) {
            $parseType = array_key_exists($property, $this->propertiesParseData) ? $this->propertiesParseData[$property] : 'NONE';
            $this->$property = GenericContentPseudoMapper::parseData($parseFromSQL ? 'FROM_SQL' : 'NONE', $parseType, $value);
        }
        return $this;
    }

    /**
     * @param bool $parseToSQL
     * @return array
     */
    public function toArray(bool $parseToSQL = false)
    {
        $data = [];
        foreach ($this->properties as $property => $value) {
            $parseType = array_key_exists($property, $this->propertiesParseData) ? $this->propertiesParseData[$property] : 'NONE';
            $data[$property] = GenericContentPseudoMapper::parseData($parseToSQL ? 'TO_SQL' : 'NONE', $parseType, $value);
        }
        $data['langData'] = $this->langData;
        //Eliminar cualquier propiedad que no sean del elemento actual
        foreach ($data as $property => $value) {
            if ($property !== $this->userSetContentName && $property !== 'langData') {
                unset($data[$property]);
            }
        }
        //Reiniciar la propiedad langData si no es traducible
        if (!in_array($this->userSetContentName, $this->translatableProperties)) {
            $data['langData'] = new \stdClass;
        }
        return $data;
    }

    /**
     * El centro por omisión de los mapas, como números; si lo guardado no vale (o no hay nada), el de MAP_DEFAULT_CENTER.
     *
     * @return array{longitude: float, latitude: float, zoom: float}
     */
    public static function mapDefaultCenter(): array
    {
        $saved = self::getContentData(self::CONTENT_MAP_DEFAULT_CENTER, self::MAP_DEFAULT_CENTER);
        $center = is_array($saved) && count(self::mapDefaultCenterErrors($saved)) === 0 ? $saved : self::MAP_DEFAULT_CENTER;
        return [
            'longitude' => (float) $center['longitude'],
            'latitude' => (float) $center['latitude'],
            'zoom' => (float) $center['zoom'],
        ];
    }

    /**
     * Lo que no vale de un centro por omisión, en mensajes para el usuario; vacío si vale entero.
     *
     * @param array<mixed> $center Con longitude, latitude y zoom
     * @return string[]
     */
    public static function mapDefaultCenterErrors(array $center): array
    {
        $ranges = [
            'longitude' => [-180, 180, __(self::LANG_GROUP, 'La longitud debe ser un número entre -180 y 180.')],
            'latitude' => [-90, 90, __(self::LANG_GROUP, 'La latitud debe ser un número entre -90 y 90.')],
            'zoom' => [0, 22, __(self::LANG_GROUP, 'El acercamiento debe ser un número entre 0 y 22.')],
        ];
        $errors = [];
        foreach ($ranges as $name => [$min, $max, $message]) {
            $value = $center[$name] ?? null;
            $number = is_int($value) || is_float($value) || (is_string($value) && is_numeric(trim($value))) ? (float) $value : null;
            if ($number === null || !is_finite($number) || $number < $min || $number > $max) {
                $errors[] = $message;
            }
        }
        return $errors;
    }

    /**
     * Obtiene los datos del contenido
     * @param string $contentName
     * @param string|null $lang
     * @param mixed $defaultValue
     * @return mixed
     */
    public static function getContentData(string $contentName, $defaultValue = null, ?string $lang = null)
    {
        $contentHandler = new GenericContentPseudoMapper($contentName);
        if ($lang !== null) {
            return $contentHandler->getLangData($lang, $contentName, false, $defaultValue);
        } else {
            return $contentHandler->$contentName;
        }
    }

    /**
     * Establece los datos del contenido
     * @param string $contentName
     * @param mixed $data
     * @param string|null $lang
     * @return void
     */
    public static function setContentData(string $contentName, $data, ?string $lang = null)
    {
        $contentHandler = new GenericContentPseudoMapper($contentName);
        if ($lang !== null) {
            $contentHandler->setLangData($lang, $contentName, $data);
        } else {
            $contentHandler->$contentName = $data;
        }
        $contentHandler->save();
    }

    /**
     * Convierte los datos según el modo y el tipo de parseo
     * @param string $mode FROM_SQL | TO_SQL
     * @param string $parseType datetime | NONE
     * @param mixed $value
     * @return mixed
     */
    public static function parseData(string $mode, string $parseType, $value)
    {
        $fromSQL = 'FROM_SQL';
        $toSQL = 'TO_SQL';
        if ($parseType === 'datetime') {
            if ($mode === $fromSQL) {
                if (Validator::isDate($value, 'Y-m-d H:i:s')) {
                    $value = $value instanceof \DateTime ? $value : new \DateTime($value);
                }
            } elseif ($mode === $toSQL) {
                if ($value instanceof \DateTime) {
                    $value = $value->format('Y-m-d H:i:s');
                }
            }
        } else if ($parseType === 'associativeArray') {
            if ($mode === $fromSQL) {
                if (is_string($value)) {
                    $value = json_decode($value, true);
                }
            } elseif ($mode === $toSQL) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
            }
        }
        return $value;
    }

}
