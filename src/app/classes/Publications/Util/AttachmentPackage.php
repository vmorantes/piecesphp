<?php

/**
 * AttachmentPackage.php
 */

namespace Publications\Util;

use PiecesPHP\Core\Config;
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
     * @var int
     */
    protected $attachmentID = -1;

    /**
     * @var string
     */
    protected $displayName = '';

    /**
     * @var bool
     */
    protected $required = false;

    /**
     * @var string
     */
    protected $lang = '';

    /**
     * @var AttachmentPublicationMapper|null
     */
    protected $mapper = null;

    /**
     * @param int $publicationID
     * @param int $attachmentID
     * @param string $displayName
     * @param string $type
     * @param string[]|string $extensions
     * @param bool $required
     */
    public function __construct(int $publicationID = null, int $attachmentID = null, string $displayName, bool $required = false, string $lang = null)
    {
        $this->publicationID = $publicationID;
        $this->attachmentID = $attachmentID;
        $this->displayName = $displayName;
        $this->required = $required;
        if ($lang !== null) {
            $this->lang = $lang;
        } else {
            $this->lang = Config::get_default_lang();
        }
    }

    /**
     * @return static
     */
    public function forceEvaluationMapper()
    {
        $this->mapper = null;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasAttachment()
    {
        return $this->getMapper() !== null;
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
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * @return AttachmentPublicationMapper|null
     */
    public function getMapper()
    {

        $mapperIsNull = $this->mapper === null;
        $mapperIsNotNull = !$mapperIsNull;

        $publication = $mapperIsNotNull ? $this->mapper->publication : null;
        $publicationIsNull = $publication === null;
        $publicationIsObject = !$publicationIsNull && is_object($publication);
        $mapperPublicationID = $publicationIsObject ? $publication->id : (
            !$publicationIsNull ? $publication : null
        );

        $publicationIDEqualsMapperPublicationID = $mapperPublicationID == $this->publicationID;

        if (!$publicationIDEqualsMapperPublicationID || $mapperIsNull) {
            $this->mapper = AttachmentPublicationMapper::getExactAttachment($this->publicationID, $this->attachmentID, $this->lang, true);
        }

        return $this->mapper;
    }

}
