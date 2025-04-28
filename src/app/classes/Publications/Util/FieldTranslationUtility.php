<?php

/**
 * FieldTranslationUtility.php
 */

namespace Publications\Util;

use Publications\Mappers\PublicationMapper;

/**
 * FieldTranslationUtility.
 *
 * @package     Publications\Util
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class FieldTranslationUtility
{

    /**
     * @var PublicationMapper
     */
    protected $mapper = null;

    /**
     * @var string
     */
    protected $fieldName = '';

    /**
     * @var string
     */
    protected $defaultLang = '';

    /**
     * @var string
     */
    protected $currentLang = '';

    /**
     * @var bool
     */
    protected $translatable = false;

    /**
     * @param string $fieldName
     * @param string $defaultLang
     * @param string $currentLang
     * @param bool $translatable
     */
    public function __construct(PublicationMapper $mapper, string $fieldName, string $defaultLang, string $currentLang, bool $translatable = false)
    {
        $this->mapper = $mapper;
        $this->fieldName = $fieldName;
        $this->defaultLang = $defaultLang;
        $this->currentLang = $currentLang;
        $this->translatable = $translatable;
    }

    /**
     * @return string yes|no|current
     */
    public function isTranslatable()
    {
        return $this->defaultLang == $this->currentLang ? 'current' : ($this->translatable ? 'yes' : 'no');
    }

    /**
     * @param ?PublicationMapper $value
     * @return static|string
     */
    public function mapper(?PublicationMapper $value = null)
    {
        $propertyName = 'mapper';
        if ($value !== null) {
            $this->$propertyName = $value;
            return $this;
        } else {
            return $this->$propertyName;
        }
    }

    /**
     * @param ?string $value
     * @return static|string
     */
    public function fieldName(?string $value = null)
    {
        $propertyName = 'fieldName';
        if ($value !== null) {
            $this->$propertyName = $value;
            return $this;
        } else {
            return $this->$propertyName;
        }
    }

    /**
     * @param ?string $value
     * @return static|string
     */
    public function defaultLang(?string $value = null)
    {
        $propertyName = 'defaultLang';
        if ($value !== null) {
            $this->$propertyName = $value;
            return $this;
        } else {
            return $this->$propertyName;
        }
    }

    /**
     * @param ?string $value
     * @return static|string
     */
    public function currentLang(?string $value = null)
    {
        $propertyName = 'currentLang';
        if ($value !== null) {
            $this->$propertyName = $value;
            return $this;
        } else {
            return $this->$propertyName;
        }
    }

    /**
     * @param ?bool $value
     * @return static|bool
     */
    public function translatable(?bool $value = null)
    {
        $propertyName = 'translatable';
        if ($value !== null) {
            $this->$propertyName = $value;
            return $this;
        } else {
            return $this->$propertyName;
        }
    }

}
