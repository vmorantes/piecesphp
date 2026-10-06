<?php

/**
 * MetaTags.php
 */
namespace PiecesPHP\Core\Utilities\Helpers;

use PiecesPHP\Core\Config;

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
     * @var string
     */
    protected static $owner = null;
    /**
     * @var string
     */
    protected static $sitename = null;
    /**
     * @var string
     */
    protected static $title = null;
    /**
     * @var string
     */
    protected static $description = null;
    /**
     * @var string
     */
    protected static $keywords = null;
    /**
     * @var string
     */
    protected static $image = null;
    /**
     * @var string
     */
    protected static $url = null;
    /**
     * @var string
     */
    protected static $themeColor = null;
    /**
     * @var string
     */
    protected static $locale = null;
    /**
     * @var string
     */
    protected static $localeAlternate = null;
    /**
     * @var string
     */
    protected static $type = null;
    /**
     * @var string|null
     */
    protected static $robots = null;
    /**
     * @var string|null
     */
    protected static $imageAlt = null;
    /**
     * @var string|null
     */
    protected static $shareTitle = null;
    /**
     * Lo fijado con setTitle() o setDescription() manda sobre los textos para compartir de «Identidad y SEO».
     * @var bool
     */
    protected static $titleSetByPage = false;
    /**
     * @var bool
     */
    protected static $descriptionSetByPage = false;

    /**
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

            $title = get_title(true, null, false);

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
            self::$url = self::canonicalURL();
        }

        if (is_null(self::$themeColor)) {

            $themeColor = get_config('meta_theme_color');
            $themeColor = $themeColor !== false ? $themeColor : '';
            $themeColor = is_string($themeColor) && mb_strlen(trim($themeColor)) > 0 ? trim($themeColor) : null;

            if ($themeColor !== null) {
                self::$themeColor = $themeColor;
            } else {
                self::$themeColor = null;
            }

        }

        if (is_null(self::$locale)) {
            self::$locale = self::ogLocale(Config::get_lang());
        }

        if (is_null(self::$type)) {
            self::$type = 'website';
        }

    }

    /**
     * @param string $value
     * @return void
     */
    public static function setOwner(string $value)
    {
        self::$owner = $value;
    }

    /**
     * @param string $value
     * @return void
     */
    public static function setSitename(string $value)
    {
        self::$sitename = $value;
    }

    /**
     * @param string $value
     * @return void
     */
    public static function setTitle(string $value)
    {
        self::$title = $value;
        self::$titleSetByPage = true;
    }

    /**
     * @param string $value
     * @param int $maxLength
     * @param int $fromIndex
     * @return void
     */
    public static function setDescription(string $value, int $maxLength = 150, int $fromIndex = 0)
    {
        $value = strip_tags($value);
        $value = trim($value);
        $valueLength = mb_strlen($value);
        $fromIndex = $fromIndex >= $valueLength ? 0 : $fromIndex;
        $value = $valueLength > $maxLength ? trim(mb_substr($value, $fromIndex, $maxLength)) . '...' : $value;
        self::$description = $value;
        self::$descriptionSetByPage = true;
    }

    /**
     * @param string $value
     * @return void
     */
    public static function setKeywords(string $value)
    {
        self::$keywords = $value;
    }

    /**
     * @param string $value
     * @return void
     */
    public static function setImage(string $value)
    {
        self::$image = $value;
    }

    /**
     * @param string $value
     * @return void
     */
    public static function setURL(string $value)
    {
        self::$url = $value;
    }

    /**
     * @param string $value
     * @return void
     */
    public static function setThemeColor(string $value)
    {
        self::$themeColor = $value;
    }

    /**
     * @param string $value
     * @return void
     */
    public static function setLocale(string $value)
    {
        self::$locale = $value;
    }

    /**
     * @param string $value
     * @return void
     */
    public static function setLocaleAlternate(string $value)
    {
        self::$localeAlternate = $value;
    }

    /**
     * @param string $value
     * @return void
     */
    public static function setType(string $value)
    {
        self::$type = $value;
    }

    /**
     * El contenido de `<meta name='robots'>`, por ejemplo `noindex, nofollow`. Sin llamarla no se emite.
     *
     * @param string $value
     * @return void
     */
    public static function setRobots(string $value)
    {
        self::$robots = $value;
    }

    /**
     * El título de og:title y twitter:title, sin tocar el `<title>`. La portada fija con él el «Título para compartir».
     *
     * @param string $value
     * @return void
     */
    public static function setShareTitle(string $value)
    {
        self::$shareTitle = $value;
    }

    /**
     * El texto alternativo de la imagen para compartir. Por omisión, el título.
     *
     * @param string $value
     * @return void
     */
    public static function setImageAlt(string $value)
    {
        self::$imageAlt = $value;
    }

    /**
     * La URL canónica de la página actual: sin consulta ni fragmento, y con `?i18n=<idioma>` si el idioma viaja ahí
     * (`lang_by_cookie`). Sin eso, la página en inglés se declararía copia de la española, que comparte su ruta.
     *
     * @return string
     */
    public static function canonicalURL(): string
    {
        return self::canonicalFor(get_current_url(), Config::get_lang());
    }

    /**
     * La canónica de una URL en un idioma, con el mismo cálculo que la de la página actual: la usa también el sitemap,
     * para que cada URL que anuncia sea exactamente la que la página declara.
     *
     * @param string $url
     * @param string $lang
     * @return string
     */
    public static function canonicalFor(string $url, string $lang): string
    {
        $url = remove_url_params($url, []);
        if (get_config('lang_by_cookie') === true) {
            return convert_lang_url($url, $lang, $lang);
        }
        //Con el idioma en la ruta, convert_lang_url() devuelve '' para el idioma actual: la URL ya es la suya.
        $converted = $lang === Config::get_lang() ? '' : convert_lang_url($url, Config::get_lang(), $lang);
        return $converted !== '' ? remove_url_params($converted, []) : $url;
    }

    /**
     * Las URL de la página por idioma: las alternativas que dejó el sistema de idiomas (o la página, en
     * `alternatives_url`) más la canónica en el idioma actual. Solo los idiomas en que la página existe.
     *
     * @return array<string,string>
     */
    public static function languageURLs(): array
    {
        self::initialValues();
        $urls = [];
        $alternatives = Config::get_config('alternatives_url');
        foreach (is_array($alternatives) ? $alternatives : [] as $lang => $url) {
            if (is_string($lang) && is_string($url) && $url !== '') {
                $urls[$lang] = $url;
            }
        }
        $urls[Config::get_lang()] = self::$url;
        return $urls;
    }

    /**
     * @param string $lang
     * @return string
     */
    private static function ogLocale(string $lang): string
    {
        $locales = get_config('og_locales');
        return is_array($locales) && is_string($locales[$lang] ?? null) ? $locales[$lang] : $lang;
    }

    /**
     * Ancho y alto de la imagen si es un archivo de esta instalación. Nunca por red.
     *
     * @param string $imageURL
     * @return array{0:int,1:int}|null
     */
    private static function localImageSize(string $imageURL): ?array
    {
        $base = baseurl();
        $path = (string) parse_url($imageURL, \PHP_URL_PATH);
        if ($imageURL === '' || !str_starts_with($imageURL, $base) || $path === '') {
            return null;
        }
        $file = basepath(mb_substr((string) strtok(mb_substr($imageURL, mb_strlen($base)), '?#'), 0));
        if (!is_file($file)) {
            return null;
        }
        $size = @getimagesize($file);
        return is_array($size) && $size[0] > 0 && $size[1] > 0 ? [$size[0], $size[1]] : null;
    }

    /**
     * El título para compartir: el de setShareTitle(); si no, en una página sin título propio, el «Título para
     * compartir» del sitio; si no, el título de siempre.
     *
     * @return string
     */
    private static function shareTitle(): string
    {
        if (self::$shareTitle !== null) {
            return self::$shareTitle;
        }
        $share = get_config('share_title');
        $withoutOwnTitle = !self::$titleSetByPage && get_config('title') === false;
        return $withoutOwnTitle && is_string($share) && trim($share) !== '' ? trim($share) : self::$title;
    }

    /**
     * La descripción para compartir: la de la página si la fijó; si no, la «Descripción para compartir» o la del sitio.
     *
     * @return string
     */
    private static function shareDescription(): string
    {
        $share = get_config('share_description');
        return !self::$descriptionSetByPage && is_string($share) && trim($share) !== '' ? trim($share) : self::$description;
    }

    /**
     * Lo que se escribe en un atributo o en el `<title>`. ÚNICA PUERTA de salida de esta clase.
     *
     * Los atributos van entre comillas SIMPLES: por eso `ENT_QUOTES`. Antes solo tres valores pasaban por
     * `htmlentities` y el título y el Open Graph salían crudos: una comilla rompía la etiqueta en el `<head>`.
     * Los valores se guardan TAL CUAL; si alguien los escapa antes de pasarlos, saldrán escapados dos veces.
     *
     * @param mixed $value
     * @return string
     */
    private static function escape($value): string
    {
        return htmlspecialchars(is_scalar($value) ? (string) $value : '', \ENT_QUOTES | \ENT_SUBSTITUTE | \ENT_HTML5, 'UTF-8');
    }

    /**
     * @return string
     */
    public static function getMetaTagsGeneric()
    {
        self::initialValues();
        $html = [];
        $ogProperties = [
            [
                'property' => 'author',
                'content' => self::$owner,
            ],
            [
                'property' => 'description',
                'content' => self::$description,
            ],
            [
                'property' => 'keywords',
                'content' => self::$keywords,
            ],
            [
                'property' => 'theme-color',
                'content' => self::$themeColor,
            ],
        ];

        $htmlResult = "\r\n\t<!-- Meta tags basic -->\r\n";

        $html[] = "\t<title>" . self::escape(self::$title) . "</title>\r\n";

        foreach ($ogProperties as $tag) {
            $name = $tag['property'];
            if (!is_null($tag['content'])) {
                $content = self::escape($tag['content']);
                $html[] = "\t<meta name='{$name}' content='{$content}' />";
            }
        }

        if (self::$robots !== null) {
            $html[] = "\t<meta name='robots' content='" . self::escape(self::$robots) . "' />";
        }

        //Una página que no se indexa no anuncia canónica ni idiomas.
        $indexable = self::$robots === null || !str_contains(mb_strtolower(self::$robots), 'noindex');
        $languageURLs = $indexable ? self::languageURLs() : [];
        if ($indexable) {
            $html[] = "\t<link rel='canonical' href='" . self::escape(self::$url) . "' />";
        }
        foreach ($languageURLs as $lang => $url) {
            $html[] = "\t<link rel='alternate' hreflang='" . self::escape($lang) . "' href='" . self::escape($url) . "' />";
        }
        //x-default es la URL que decide por cookie o por navegador: la del idioma por omisión, sin `?i18n=`.
        $defaultURL = $languageURLs[Config::get_default_lang()] ?? null;
        if ($defaultURL !== null) {
            $xDefault = get_config('lang_by_cookie') === true ? remove_url_params($defaultURL, []) : $defaultURL;
            $html[] = "\t<link rel='alternate' hreflang='x-default' href='" . self::escape($xDefault) . "' />";
        }

        $html = implode("\r\n", $html);

        $html .= "\r\n\t<!-- Close Meta tags basic -->\r\n";

        $htmlResult .= $html;

        return $htmlResult;
    }

    /**
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
                'content' => self::shareTitle(),
            ],
            [
                'property' => 'description',
                'content' => self::shareDescription(),
            ],
            [
                'property' => 'locale',
                'content' => self::$locale,
            ],
        ];
        //Una alternativa por cada otro idioma en que la página existe; setLocaleAlternate() fija una sola.
        $alternateLocales = self::$localeAlternate !== null ? [self::$localeAlternate] : array_map(
            fn(string $lang): string => self::ogLocale($lang),
            array_values(array_filter(array_keys(self::languageURLs()), fn($lang) => $lang !== Config::get_lang()))
        );
        foreach ($alternateLocales as $alternateLocale) {
            $ogProperties[] = ['property' => 'locale:alternate', 'content' => $alternateLocale];
        }
        $ogProperties[] = ['property' => 'type', 'content' => self::$type];
        $ogProperties[] = ['property' => 'image', 'content' => self::$image];
        $imageSize = self::localImageSize((string) self::$image);
        if ($imageSize !== null) {
            $ogProperties[] = ['property' => 'image:width', 'content' => $imageSize[0]];
            $ogProperties[] = ['property' => 'image:height', 'content' => $imageSize[1]];
        }
        if (self::$image !== '') {
            $ogProperties[] = ['property' => 'image:alt', 'content' => self::$imageAlt ?? self::shareTitle()];
        }
        $ogProperties[] = ['property' => 'url', 'content' => self::$url];

        $htmlResult = "\r\n\t<!-- Open Graph Tags -->\r\n";

        foreach ($ogProperties as $tag) {
            $name = $tag['property'];
            $content = self::escape($tag['content']);
            $html[] = "\t<meta property='og:{$name}' content='{$content}' />";
        }

        $xAccount = get_config('x_account');
        $twitter = [
            'card' => self::$image !== '' ? 'summary_large_image' : 'summary',
            'title' => self::shareTitle(),
            'description' => self::shareDescription(),
        ];
        if (self::$image !== '') {
            $twitter['image'] = self::$image;
        }
        if (is_string($xAccount) && trim($xAccount) !== '') {
            $twitter['site'] = trim($xAccount);
        }
        foreach ($twitter as $name => $content) {
            $html[] = "\t<meta name='twitter:{$name}' content='" . self::escape($content) . "' />";
        }

        $html = implode("\r\n", $html);

        $html .= "\r\n\t<!-- Close Open Graph Tags -->\r\n";

        $htmlResult .= $html;

        return $htmlResult;
    }
}
