<?php

/**
 * SitemapItem.php
 */
namespace PiecesPHP\Core\Sitemap;

/**
 * SitemapItem
 *
 * @package     PiecesPHP\Core\Sitemap
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class SitemapItem
{

    const FREQ_ALWAYS = 'always';
    const FREQ_HOUR = 'hourly';
    const FREQ_DAY = 'daily';
    const FREQ_WEEK = 'weekly';
    const FREQ_MONTH = 'monthly';
    const FREQ_YEAR = 'yearly';
    const FREQ_NEVER = 'never';

    const FREQS = [
        self::FREQ_ALWAYS,
        self::FREQ_HOUR,
        self::FREQ_DAY,
        self::FREQ_WEEK,
        self::FREQ_MONTH,
        self::FREQ_YEAR,
        self::FREQ_NEVER,
    ];

    /**
     * $location
     *
     * @var string
     */
    protected $location = "";
    /**
     * $lastModification
     *
     * @var \DateTime|null
     */
    protected $lastModification = null;
    /**
     * $changeFrequency
     *
     * @var string|null
     */
    protected $changeFrequency = null;
    /**
     * $priority
     *
     * @var double|null
     */
    protected $priority = null;

    /**
     * __construct
     *
     * @param string $location
     * @param \DateTime $lastMod
     * @param string $changeFreq
     * @param float $priority
     * @return static
     */
    public function __construct(string $location, \DateTime $lastMod = null, string $changeFreq = null, float $priority = 0.5)
    {

        $this->location = $location;
        $this->lastModification = $lastMod;
        $this->changeFrequency = in_array($changeFreq, self::FREQS) ? $changeFreq : null;
        $this->priority = $priority < 0 || $priority > 1 ? 0.5 : $priority;

    }

    /**
     * getLocation
     *
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * getXML
     *
     * @return string
     */
    public function getXML()
    {
        $xml = "\r\n\t<url>\r\n";
        $xml .= "\t\t<loc>{$this->location}</loc>\r\n";
        if ($this->lastModification !== null) {
            $xml .= "\t\t<lastmod>" . $this->lastModification->format('c') . "</lastmod>\r\n";
        }
        if ($this->changeFrequency !== null) {
            $xml .= "\t\t<changefreq>" . $this->changeFrequency . "</changefreq>\r\n";
        }
        $xml .= "\t\t<priority>{$this->priority}</priority>\r\n";
        $xml .= "\t</url>\r\n";

        return $xml;
    }
}
