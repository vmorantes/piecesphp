<?php

/**
 * AttachmentPackage.php
 */

namespace Publications\Util;

use PiecesPHP\Core\Config;
use PiecesPHP\Core\Forms\FileValidator;
use Publications\Mappers\AttachmentPublicationMapper;

/**
 * AttachmentPackage.
 *
 * @package     Publications\Util
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2022
 */
class AttachmentPackage
{

    /**
     * @var int
     */
    protected $publicationID = -1;

    /**
     * @var string
     */
    protected $baseName = '';

    /**
     * @var string
     */
    protected $type = '';

    /**
     * @var bool
     */
    protected $required = false;

    /**
     * @var bool
     */
    protected $multiple = false;

    /**
     * @var string
     */
    protected $lang = '';

    /**
     * @var string[]
     */
    protected $validTypes = [
        FileValidator::TYPE_JPEG,
        FileValidator::TYPE_JPG,
        FileValidator::TYPE_PDF,
    ];

    /**
     * @var string[]
     */
    protected $extensions = [
        '.jpg',
        '.jpeg',
        '.pdf',
    ];

    /**
     * @var AttachmentPublicationMapper|null
     */
    protected $mapper = null;

    /**
     * @param int $publicationID
     * @param string $baseName
     * @param string $type
     * @param string[]|string $validTypes
     * @param string[]|string $extensions
     * @param bool $required
     * @param bool $multiple
     */
    public function __construct(int $publicationID = null, string $baseName, string $type, $validTypes = null, $extensions = null, bool $required = false, bool $multiple = false, string $lang = null)
    {
        $this->publicationID = $publicationID;
        $this->baseName = $baseName;
        $this->type = $type;
        if ($validTypes !== null) {
            $validTypes = is_array($validTypes) ? $validTypes : [$validTypes];
            $this->setValidTypes($validTypes);
        }
        if ($extensions !== null) {
            $extensions = is_array($extensions) ? $extensions : [$extensions];
            $this->setExtensions($extensions);
        }
        $this->required = $required;
        $this->multiple = $multiple;
        if ($lang !== null) {
            $this->lang = $lang;
        } else {
            $this->lang = Config::get_default_lang();
        }
    }

    /**
     * @param string $str
     * @return string
     */
    public function baseNameAppend(string $str)
    {
        return $this->baseName . trim($str);
    }

    /**
     * @return bool
     */
    public function hasAttachment()
    {
        return $this->getMapper() !== null;
    }

    /**
     * @param string $type
     * @return static
     */
    public function addValidType(string $type)
    {
        $this->validTypes[] = $type;
        return $this;
    }

    /**
     * @param string[] $types
     * @return static
     */
    public function setValidTypes(array $types)
    {
        $this->validTypes = [];
        foreach ($types as $type) {
            $this->addValidType($type);
        }
        return $this;
    }

    /**
     * @return string[]
     */
    public function getValidTypes()
    {
        return $this->validTypes;
    }

    /**
     * @param string $extension
     * @return static
     */
    public function addExtension(string $extension)
    {
        $this->extensions[] = ".{$extension}";
        return $this;
    }

    /**
     * @param string[] $extensions
     * @return static
     */
    public function setExtensions(array $extensions)
    {
        $this->extensions = [];
        foreach ($extensions as $extension) {
            $this->addExtension($extension);
        }
        return $this;
    }

    /**
     * @return string[]
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * @param bool $required
     * @return static
     */
    public function setRequired(bool $required)
    {
        $this->required = $required;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param bool $multiple
     * @return static
     */
    public function setMultiple(bool $multiple)
    {
        $this->required = $multiple;
        return $this;
    }

    /**
     * @return bool
     */
    public function isMultiple()
    {
        return $this->multiple;
    }

    /**
     * @param string $lang
     * @return static
     */
    public function setLang(string $lang)
    {
        $this->lang = $lang;
        return $this;
    }

    /**
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @param int $publicationID
     * @return static
     */
    public function setPublicationID(int $publicationID)
    {
        $this->publicationID = $publicationID;
        return $this;
    }

    /**
     * @return int
     */
    public function getPublicationID()
    {
        return $this->publicationID;
    }

    /**
     * @return string
     */
    public function getBaseName()
    {
        return $this->baseName;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTypeText()
    {
        return AttachmentPublicationMapper::attachmentTypeText($this->type);
    }

    /**
     * @return string
     */
    public function getTypeFilename()
    {
        return AttachmentPublicationMapper::attachmentTypesFilenames()[$this->type];
    }

    /**
     * @return AttachmentPublicationMapper|null
     */
    public function getMapper()
    {
        if (($this->mapper !== null && (is_object($this->mapper->publication) ? $this->mapper->publication->id : $this->mapper->publication) != $this->publicationID) || $this->mapper == null) {
            $this->mapper = AttachmentPublicationMapper::getByTypeAndPublication($this->publicationID, $this->type, $this->lang, true);
        }
        return $this->mapper;
    }

}
