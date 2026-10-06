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
     * @var string
     */
    protected $file = 'sitemap.xml';

    /**
     * @var string[]
     */
    protected $locations = [];

    /**
     * @var SitemapItem[]
     */
    protected $items = [];

    /**
     * Los proveedores de URL, por nombre: cada módulo registra el suyo (ADR 0032 §3).
     * @var array<string,callable>
     */
    protected static $providers = [];

    /**
     * Registra un proveedor: una función que devuelve SitemapItem[]. Un nombre repetido falla aquí, diciendo cuál:
     * que un registro se pierda en silencio es el defecto de los grupos de rutas (pendientes 277.1).
     *
     * @param string $name
     * @param callable $provider
     * @return void
     * @throws \InvalidArgumentException
     */
    public static function registerProvider(string $name, callable $provider)
    {
        if (array_key_exists($name, self::$providers)) {
            throw new \InvalidArgumentException("El proveedor de sitemap «{$name}» ya está registrado.");
        }
        self::$providers[$name] = $provider;
    }

    /**
     * @param string $name
     * @return void
     */
    public static function unregisterProvider(string $name)
    {
        unset(self::$providers[$name]);
    }

    /**
     * @return string[]
     */
    public static function providerNames(): array
    {
        return array_keys(self::$providers);
    }

    /**
     * El sitemap de todos los proveedores. Uno que lanza, o que no devuelve SitemapItem, se registra y se salta: no tumba
     * a los demás.
     *
     * @return array{sitemap:Sitemap,failed:string[]}
     */
    public static function fromProviders(): array
    {
        $sitemap = new Sitemap('', false);
        $failed = [];
        foreach (self::$providers as $name => $provider) {
            try {
                $items = ($provider)();
                if (!is_array($items)) {
                    throw new \UnexpectedValueException("El proveedor de sitemap «{$name}» no devolvió una lista.");
                }
                //Se comprueba la lista entera antes de añadir: un proveedor que falla no deja la mitad de sus URL.
                foreach ($items as $item) {
                    if (!$item instanceof SitemapItem) {
                        throw new \UnexpectedValueException("El proveedor de sitemap «{$name}» devolvió algo que no es SitemapItem.");
                    }
                }
                $sitemap->addItems($items);
            } catch (\Throwable $e) {
                log_exception($e);
                $failed[] = $name;
            }
        }
        return ['sitemap' => $sitemap, 'failed' => $failed];
    }

    /**
     * @param string $file
     * @param bool $load
     * @return static
     */
    public function __construct(string $file = 'sitemap.xml', bool $load = false)
    {

        $this->file = mb_strlen($file) > 0 ? $file : $this->file;

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
                                $priority = (float) ((string) $tag);
                            }

                        }

                        $this->addItem(new SitemapItem($location, $lastMod, $changeFreq, $priority));

                    }

                }

            }

        }

    }

    /**
     * @return bool
     */
    public function save()
    {

        return @file_put_contents($this->file, $this->getXML()) !== false;

    }

    /**
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
     * @return string[]
     */
    public function getLocations()
    {
        return $this->locations;
    }

    /**
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
