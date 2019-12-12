<?php

/**
 * MetaTags.php
 */
namespace PiecesPHP\Core\Utilities\Helpers;

/**
 * MetaTags
 *
 * Clase para generar algunos meta tags
 *
 * @category    Helpers
 * @package     PiecesPHP\Core\Utilities\Helpers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class MetaTags
{

    /**
     * $owner
     *
     * @var string
     */
    protected static $owner = null;
    /**
     * $sitename
     *
     * @var string
     */
    protected static $sitename = null;
    /**
     * $title
     *
     * @var string
     */
    protected static $title = null;
    /**
     * $description
     *
     * @var string
     */
    protected static $description = null;
    /**
     * $keywords
     *
     * @var string
     */
    protected static $keywords = null;
    /**
     * $image
     *
     * @var string
     */
    protected static $image = null;
    /**
     * $url
     *
     * @var string
     */
    protected static $url = null;
    /**
     * $themeColor
     *
     * @var string
     */
    protected static $themeColor = null;
    /**
     * $locale
     *
     * @var string
     */
    protected static $locale = null;
    /**
     * $localeAlternate
     *
     * @var string
     */
    protected static $localeAlternate = null;
    /**
     * $type
     *
     * @var string
     */
    protected static $type = null;

    /**
     * initialValues
     *
     * @return void
     */
    private static function initialValues()
    {

        if (is_null(self::$owner)) {

            $owner = get_config('owner');

            if ($owner !== false) {
                self::$owner = $owner;
            } else {
                self::$owner = '';
            }

        }

        if (is_null(self::$sitename)) {

            $sitename = get_config('title_app');

            if ($sitename !== false) {
                self::$sitename = $sitename;
            } else {
                self::$sitename = '';
            }

        }

        if (is_null(self::$title)) {

            $title = get_title(true);

            if ($title !== false) {
                self::$title = $title;
            } else {
                self::$title = '';
            }

        }

        if (is_null(self::$description)) {

            $description = get_config('description');

            if ($description !== false) {
                self::$description = $description;
            } else {
                self::$description = '';
            }

        }

        if (is_null(self::$keywords)) {

            $keywords = get_config('keywords');

            if ($keywords !== false) {
                self::$keywords = implode(',', $keywords);
            } else {
                self::$keywords = '';
            }

        }

        if (is_null(self::$image)) {

            $image = get_config('open_graph_image');

            if ($image !== false) {
                self::$image = baseurl($image);
            } else {
                self::$image = '';
            }

        }

        if (is_null(self::$url)) {
            self::$url = get_current_url();
        }

        if (is_null(self::$themeColor)) {

            $themeColor = get_config('meta_theme_color');
            $themeColor = $themeColor !== false ? $themeColor : '';
            $themeColor = is_string($themeColor) && strlen(trim($themeColor)) > 0 ? trim($themeColor) : null;

            if ($themeColor !== null) {
                self::$themeColor = $themeColor;
            } else {
                self::$themeColor = null;
            }

        }

        if (is_null(self::$locale)) {
            self::$locale = 'es_CO';
        }

        if (is_null(self::$localeAlternate)) {
            self::$localeAlternate = 'es_CO';
        }

        if (is_null(self::$type)) {
            self::$type = 'website';
        }

    }

    /**
     * setOwner
     *
     * @param string $value
     * @return void
     */
    public static function setOwner(string $value)
    {
        self::$owner = $value;
    }

    /**
     * setSitename
     *
     * @param string $value
     * @return void
     */
    public static function setSitename(string $value)
    {
        self::$sitename = $value;
    }

    /**
     * setTitle
     *
     * @param string $value
     * @return void
     */
    public static function setTitle(string $value)
    {
        self::$title = $value;
    }

    /**
     * setDescription
     *
     * @param string $value
     * @param int $maxLength
     * @param int $fromIndex
     * @return void
     */
    public static function setDescription(string $value, int $maxLength = 150, int $fromIndex = 0)
    {
        $value = strip_tags($value);
        $value = trim($value);
        $valueLength = strlen($value);
        $fromIndex = $fromIndex >= $valueLength ? 0 : $fromIndex;
        $value = $valueLength > $maxLength ? trim(mb_substr($value, $fromIndex, $maxLength)) . '...' : $value;
        self::$description = $value;
    }

    /**
     * setKeywords
     *
     * @param string $value
     * @return void
     */
    public static function setKeywords(string $value)
    {
        self::$keywords = $value;
    }

    /**
     * setImage
     *
     * @param string $value
     * @return void
     */
    public static function setImage(string $value)
    {
        self::$image = $value;
    }

    /**
     * setURL
     *
     * @param string $value
     * @return void
     */
    public static function setURL(string $value)
    {
        self::$url = $value;
    }

    /**
     * setThemeColor
     *
     * @param string $value
     * @return void
     */
    public static function setThemeColor(string $value)
    {
        self::$themeColor = $value;
    }

    /**
     * setLocale
     *
     * @param string $value
     * @return void
     */
    public static function setLocale(string $value)
    {
        self::$locale = $value;
    }

    /**
     * setLocaleAlternate
     *
     * @param string $value
     * @return void
     */
    public static function setLocaleAlternate(string $value)
    {
        self::$localeAlternate = $value;
    }

    /**
     * setType
     *
     * @param string $value
     * @return void
     */
    public static function setType(string $value)
    {
        self::$type = $value;
    }

    /**
     * getMetaTagsGeneric
     *
     * @return string
     */
    public static function getMetaTagsGeneric()
    {
        self::initialValues();
        $html = [];
        $ogProperties = [
            [
                'property' => 'author',
                'content' => htmlentities(self::$owner),
            ],
            [
                'property' => 'description',
                'content' => htmlentities(self::$description),
            ],
            [
                'property' => 'keywords',
                'content' => htmlentities(self::$keywords),
            ],
            [
                'property' => 'theme-color',
                'content' => self::$themeColor,
            ],
        ];

        $html[] = "<!-- Meta tags basic -->\r\n";

        $html[] = "<title>" . self::$title . "</title>\r\n";

        foreach ($ogProperties as $tag) {
            $name = $tag['property'];
            $content = $tag['content'];
            if (!is_null($content)) {
                $html[] = "<meta name='{$name}' content='{$content}' />";
            }
        }

        $html[] = '<!-- Close Meta tags basic -->';

        $html = implode("\r\n", $html);

        return $html;
    }

    /**
     * getMetaTagsOpenGraph
     *
     * @return string
     */
    public static function getMetaTagsOpenGraph()
    {
        self::initialValues();
        $html = [];
        $ogProperties = [
            [
                'property' => 'site_name',
                'content' => self::$sitename,
            ],
            [
                'property' => 'title',
                'content' => self::$title,
            ],
            [
                'property' => 'description',
                'content' => self::$description,
            ],
            [
                'property' => 'locale',
                'content' => self::$locale,
            ],
            [
                'property' => 'locale:alternate',
                'content' => self::$localeAlternate,
            ],
            [
                'property' => 'type',
                'content' => self::$type,
            ],
            [
                'property' => 'image',
                'content' => self::$image,
            ],
            [
                'property' => 'url',
                'content' => self::$url,
            ],
        ];

        $html[] = "<!-- Open Graph Tags -->\r\n";

        foreach ($ogProperties as $tag) {
            $name = $tag['property'];
            $content = $tag['content'];
            $html[] = "<meta property='og:{$name}' content='{$content}' />";
        }

        $html[] = '<!-- Close Open Graph Tags -->';

        $html = implode("\r\n", $html);

        return $html;
    }
}
