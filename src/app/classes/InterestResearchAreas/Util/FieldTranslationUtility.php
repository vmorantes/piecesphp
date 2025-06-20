<?php

/**
 * FieldTranslationUtility.php
 */

namespace InterestResearchAreas\Util;

use InterestResearchAreas\Mappers\InterestResearchAreasMapper;

/**
 * FieldTranslationUtility.
 *
 * @package     InterestResearchAreas\Util
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class FieldTranslationUtility
{

    /**
     * @var InterestResearchAreasMapper
     */
    protected $mapper = null;

    /**
     * @var string
     */
    protected $fieldName = '';

    /**
     * @var string
     */
    protected $baseLang = '';

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
     * @param string $baseLang
     * @param string $currentLang
     * @param bool $translatable
     */
    public function __construct(InterestResearchAreasMapper $mapper, string $fieldName, string $baseLang, string $currentLang, bool $translatable = false)
    {
        $this->mapper = $mapper;
        $this->fieldName = $fieldName;
        $this->baseLang = $baseLang;
        $this->currentLang = $currentLang;
        $this->translatable = $translatable;
    }

    /**
     * @return string yes|no|current
     */
    public function isTranslatable()
    {
        return $this->baseLang == $this->currentLang ? 'current' : ($this->translatable ? 'yes' : 'no');
    }

    /**
     * @param ?InterestResearchAreasMapper $value
     * @return static|string
     */
    public function mapper(?InterestResearchAreasMapper $value = null)
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
    public function baseLang(?string $value = null)
    {
        $propertyName = 'baseLang';
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
