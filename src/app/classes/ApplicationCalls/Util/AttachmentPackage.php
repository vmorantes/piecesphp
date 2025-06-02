<?php

/**
 * AttachmentPackage.php
 */

namespace ApplicationCalls\Util;

use PiecesPHP\Core\Config;
use ApplicationCalls\Mappers\AttachmentApplicationCallsMapper;

/**
 * AttachmentPackage.
 *
 * @package     ApplicationCalls\Util
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class AttachmentPackage
{

    /**
     * @var int
     */
    protected $applicationCallID = -1;

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
     * @var AttachmentApplicationCallsMapper|null
     */
    protected $mapper = null;

    /**
     * @param int $applicationCallID
     * @param int $attachmentID
     * @param string $displayName
     * @param string $type
     * @param string[]|string $extensions
     * @param bool $required
     */
    public function __construct(int $applicationCallID = null, int $attachmentID = null, string $displayName, bool $required = false, string $lang = null)
    {
        $this->applicationCallID = $applicationCallID;
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
     * @param int $applicationCallID
     * @return static
     */
    public function setApplicationCallID(int $applicationCallID)
    {
        $this->applicationCallID = $applicationCallID;
        return $this;
    }

    /**
     * @return int
     */
    public function getApplicationCallID()
    {
        return $this->applicationCallID;
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return $this->displayName;
    }

    /**
     * @return AttachmentApplicationCallsMapper|null
     */
    public function getMapper()
    {

        $mapperIsNull = $this->mapper === null;
        $mapperIsNotNull = !$mapperIsNull;

        $applicationCall = $mapperIsNotNull ? $this->mapper->applicationCall : null;
        $applicationCallIsNull = $applicationCall === null;
        $applicationCallIsObject = !$applicationCallIsNull && is_object($applicationCall);
        $mapperApplicationCallID = $applicationCallIsObject ? $applicationCall->id : (
            !$applicationCallIsNull ? $applicationCall : null
        );

        $applicationCallIDEqualsMapperApplicationCallID = $mapperApplicationCallID == $this->applicationCallID;

        if (!$applicationCallIDEqualsMapperApplicationCallID || $mapperIsNull) {
            $this->mapper = AttachmentApplicationCallsMapper::getExactAttachment($this->applicationCallID, $this->attachmentID, $this->lang, true);
        }

        return $this->mapper;
    }

}
