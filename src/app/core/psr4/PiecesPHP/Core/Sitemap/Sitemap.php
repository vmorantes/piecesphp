<?php

/**
 * Sitemap.php
 */
namespace PiecesPHP\Core\Sitemap;

use SimpleXMLElement;

/**
 * Sitemap
 *
 * @package     PiecesPHP\Core\Sitemap
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class Sitemap
{

    /**
     * $file
     *
     * @var string
     */
    protected $file = 'sitemap.xml';

    /**
     * $locations
     *
     * @var string[]
     */
    protected $locations = [];

    /**
     * $items
     *
     * @var SitemapItem[]
     */
    protected $items = [];

    /**
     * __construct
     *
     * @param string $file
     * @param bool $load
     * @return static
     */
    public function __construct(string $file = 'sitemap.xml', bool $load = false)
    {

        $this->file = strlen($file) > 0 ? $file : $this->file;

        if ($load) {

            if (file_exists($this->file)) {

                $data = @file_get_contents($this->file);

                if (is_string($data)) {

                    $xml = new SimpleXMLElement($data);

                    foreach ($xml as $element) {

                        $location = '';
                        $lastMod = null;
                        $changeFreq = null;
                        $priority = 0.5;

                        foreach ($element as $tag) {

                            $name = $tag->getName();

                            if ($name == 'loc') {
                                $location = (string) $tag;
                            }
                            if ($name == 'lastmod') {
                                try {
                                    $lastMod = new \DateTime((string) $tag);
                                } catch (\Exception $e) {
                                    $lastMod = null;
                                }
                            }
                            if ($name == 'changefreq') {
                                $changeFreq = (string) $tag;
                            }
                            if ($name == 'priority') {
                                $priority = (double) ((string) $tag);
                            }

                        }

                        $this->addItem(new SitemapItem($location, $lastMod, $changeFreq, $priority));

                    }

                }

            }

        }

    }

    /**
     * save
     *
     * @return bool
     */
    public function save()
    {

        return @file_put_contents($this->file, $this->getXML()) !== false;

    }

    /**
     * addItems
     *
     * @param SitemapItem[] $items
     * @return static
     */
    public function addItems(array $items)
    {

        foreach ($items as $item) {

            $this->addItem($item);

        }

        return $this;

    }

    /**
     * addItem
     *
     * @param SitemapItem $item
     * @return static
     */
    public function addItem(SitemapItem $item)
    {

        if (!in_array($item->getLocation(), $this->locations)) {

            $this->items[] = $item;
            $this->locations[] = $item->getLocation();

        }

        return $this;

    }

    /**
     * getLocations
     *
     * @return string[]
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
     * getXML
     *
     * @return string
     */
    public function getXML()
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";

        $xml .= "\r\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\r\n";

        foreach ($this->items as $item) {

            $xml .= $item->getXML();

        }

        $xml .= "\r\n</urlset>\r\n";

        return $xml;
    }
}
