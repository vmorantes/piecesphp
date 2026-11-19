<?php

/**
 * SvgWhitelistValidator.php
 */

namespace PiecesPHP\Settings\Uploads;

/**
 * Un SVG subido (la animación de carga, H345) se acepta solo si todo lo que trae está en la lista blanca, y se RECHAZA
 * entero si no: nunca se repara. Sin red ni DTD: el DOCTYPE, las entidades y las instrucciones de procesamiento se
 * buscan en el texto ANTES de analizarlo. La cabecera `sandbox` de las subidas (ProtectedUploads) es la segunda capa y
 * solo existe con Apache y mod_headers: sin ellos, esto es todo lo que hay.
 *
 * @package     PiecesPHP\Settings\Uploads
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SvgWhitelistValidator
{

    const SVG_NAMESPACE = 'http://www.w3.org/2000/svg';
    const XLINK_NAMESPACE = 'http://www.w3.org/1999/xlink';
    const XML_NAMESPACE = 'http://www.w3.org/XML/1998/namespace';

    /**
     * Los elementos permitidos, todos en el espacio de nombres de SVG y con su nombre exacto. Sin `mask` ni `clipPath`:
     * anidados (y con `inherit`, `all` o una variable) multiplican el coste por caminos que el recuento no ve.
     */
    const ALLOWED_ELEMENTS = [
        'svg', 'g', 'defs', 'title', 'desc', 'path', 'circle', 'ellipse', 'rect', 'line', 'polyline', 'polygon',
        'linearGradient', 'radialGradient', 'stop', 'use', 'style', 'animate', 'animateTransform', 'animateMotion', 'set',
    ];

    /**
     * Los nombres de atributo o propiedad que enmascaran o recortan (`mask`, `mask-*`, `clip-path`, con o sin prefijo de
     * fabricante) y `all`, que los hereda: se rechazan con CUALQUIER valor, también `none` o `inherit`.
     */
    const MASKING_NAME_PATTERN = '/^(?:-[a-z]+-)?(?:mask(?:-[a-z-]*)?|clip-path|all)$/';

    /**
     * Los atributos `xml:` permitidos; `xml:base` cambia contra qué se resuelven las referencias y se rechaza.
     */
    const ALLOWED_XML_ATTRIBUTES = ['space', 'lang'];

    /**
     * Los elementos de animación: su `attributeName` no puede apuntar a una referencia ni a un manejador.
     */
    const ANIMATION_ELEMENTS = ['animate', 'animateTransform', 'animateMotion', 'set'];

    /**
     * Lo que se rechaza en cualquier valor, una vez quitados espacios y caracteres de control y en minúsculas.
     */
    const FORBIDDEN_FRAGMENTS = ['javascript:', 'vbscript:', 'expression(', 'behavior:', '-moz-binding'];

    const REASON_EMPTY = 'empty';
    const REASON_DOCTYPE = 'doctype';
    const REASON_ENTITY = 'entity';
    const REASON_PROCESSING_INSTRUCTION = 'processing-instruction';
    const REASON_PARSE = 'parse';
    const REASON_ROOT = 'root';
    const REASON_ELEMENT = 'element';
    const REASON_NODE = 'node';
    const REASON_EVENT_HANDLER = 'event-handler';
    const REASON_ATTRIBUTE_NAMESPACE = 'attribute-namespace';
    const REASON_EXTERNAL_HREF = 'external-href';
    const REASON_EXTERNAL_URL = 'external-url';
    const REASON_IMPORT = 'import';
    const REASON_CSS_ESCAPE = 'css-escape';
    const REASON_FORBIDDEN = 'forbidden';
    const REASON_ANIMATED_ATTRIBUTE = 'animated-attribute';
    const REASON_ENCODING = 'encoding';
    const REASON_TOO_MANY_ELEMENTS = 'too-many-elements';
    const REASON_NESTED_USE = 'nested-use';
    const REASON_CSS_FUNCTION = 'css-function';
    const REASON_CSS_STRING = 'css-string';
    const REASON_USE_TARGET = 'use-target';
    const REASON_TOO_MANY_INSTANCES = 'too-many-instances';
    const REASON_ENCODED_HREF = 'encoded-href';
    const REASON_REGEX = 'regex';
    const REASON_REFERENCE_TARGET = 'reference-target';
    const REASON_REFERENCE_CYCLE = 'reference-cycle';
    const REASON_CSS_MASKING = 'css-masking';
    const REASON_ANIMATED_REFERENCE = 'animated-reference';
    const REASON_CSS_VARIABLE = 'css-variable';
    const REASON_CSS_COMMENT = 'css-comment';

    /**
     * En un `<style>` las propiedades que pintan otro elemento se rechazan aunque no lleven `url(`: el valor puede
     * llegar por otro camino (una variable, un comentario que corta la declaración) y el coste no se puede atribuir.
     */
    const STYLE_MASKING_PROPERTY_PATTERN = '/(?<![a-z0-9_-])(?:-[a-z]+-)?(?:mask(?:-[a-z-]+)?|clip-path|all|filter|marker(?:-start|-mid|-end)?):/';

    /**
     * Las propiedades que un elemento de animación no puede animar: la animación cambia a qué apunta después de validar.
     */
    const NON_ANIMATABLE_ATTRIBUTES = ['href', 'style', 'id', 'mask', 'clip-path', 'filter', 'marker', 'marker-start', 'marker-mid', 'marker-end'];

    /**
     * Los atributos con los valores de una animación: ninguno puede llevar `url(`.
     */
    const ANIMATION_VALUE_ATTRIBUTES = ['values', 'to', 'from', 'by'];

    /**
     * En `style=`: las de máscara, recorte y `all` con cualquier valor, y `filter` y `marker-*` cuando pintan otro
     * elemento por `url(`.
     */
    const CSS_MASKING_PATTERN = '/(?<![a-z0-9_-])(?:-[a-z]+-)?(?:(?:mask(?:-[a-z-]+)?|clip-path|all):|(?:filter|marker(?:-start|-mid|-end)?):[^;}]*url\\()/';

    /**
     * Los atributos que no son valores de CSS (texto, geometría, identificadores): no pasan por la comprobación de
     * funciones y cadenas de CSS. `values`, `to`, `from`, `by` y los de presentación, sí.
     */
    const NON_CSS_ATTRIBUTES = ['id', 'class', 'role', 'd', 'points', 'viewBox', 'preserveAspectRatio', 'transform', 'path', 'keyTimes', 'keySplines', 'keyPoints'];

    /**
     * El tope de lo que se pinta: los elementos más, por cada `use`, los de su destino (él incluido). Sin anidar, 2.500
     * `use` a un grupo de 2.400 son 6 millones de instancias en 110 KB.
     */
    const MAX_RENDERED_ELEMENTS = 10000;

    /**
     * El tope de elementos: la animación de fábrica lleva 10. Con `use` anidados, unos pocos KB bastan para pedirle al
     * navegador miles de millones de instancias; con `use` sobre `use` prohibido, el tope acota lo que queda.
     */
    const MAX_ELEMENTS = 5000;

    /**
     * Las funciones de CSS que cargan un recurso sin pasar por `url(`.
     */
    const FORBIDDEN_CSS_FUNCTIONS_PATTERN = '/(?:image-set|(?<![a-z0-9_-])image|cross-fade|(?<![a-z0-9_-])src)\(/';

    /**
     * @param string $content El archivo entero
     * @return string|null null si se acepta; si no, el motivo (una de las constantes REASON_*)
     */
    public static function rejectionReason(string $content): ?string
    {
        if (trim($content) === '') {
            return self::REASON_EMPTY;
        }
        //Un nulo es UTF-16 o UCS-4 sin BOM: el analizador lo leería de otra forma que estas expresiones.
        if (str_contains($content, "\0")) {
            return self::REASON_ENCODING;
        }
        //Sin UTF-8 válido, las expresiones de abajo fallarían en silencio y no verían nada.
        if (!mb_check_encoding($content, 'UTF-8')) {
            return self::REASON_PARSE;
        }

        //Antes de analizar: el analizador ya habría resuelto lo que estas reglas quieren impedir.
        //Toda expresión regular de esta clase falla en CERRADO: un false o un null (límite de retroceso) rechaza.
        if (stripos($content, '<!DOCTYPE') !== false) {
            return self::REASON_DOCTYPE;
        }
        if (stripos($content, '<!ENTITY') !== false) {
            return self::REASON_ENTITY;
        }
        //Solo la declaración XML, y solo al principio: `<?xml-stylesheet` y cualquier otra instrucción se rechazan.
        $withoutDeclaration = preg_replace('/^(?:\xEF\xBB\xBF)?\s*<\?xml\s[^?]*\?>/', '', $content, 1);
        if ($withoutDeclaration === null) {
            return self::REASON_REGEX;
        }
        if (str_contains($withoutDeclaration, '<?')) {
            return self::REASON_PROCESSING_INSTRUCTION;
        }
        if (self::hasForbiddenFragment($content)) {
            return self::REASON_FORBIDDEN;
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $document = new \DOMDocument();
        //Sin LIBXML_NOENT (no sustituye entidades) ni LIBXML_DTDLOAD: sin red ni DTD.
        $loaded = $document->loadXML($content, \LIBXML_NONET);
        //CUALQUIER aviso del analizador rechaza, también los que no son fatales: un prefijo sin declarar (`x:onload`)
        //deja un atributo que el validador ve sin espacio de nombres y el navegador ejecuta.
        $parseErrors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($loaded !== true || $parseErrors !== [] || !($document->documentElement instanceof \DOMElement)) {
            return self::REASON_PARSE;
        }
        //Solo UTF-8: con `encoding="UTF-7"` libxml lee `+ADwAIQAtAC0-` como `<!--` y esconde un <script> que el navegador,
        //al abrir el archivo, sí ejecuta.
        if ($document->xmlEncoding !== null && mb_strtoupper($document->xmlEncoding) !== 'UTF-8') {
            return self::REASON_ENCODING;
        }
        if ($document->doctype !== null) {
            return self::REASON_DOCTYPE;
        }

        $root = $document->documentElement;
        if ($root->localName !== 'svg' || $root->namespaceURI !== self::SVG_NAMESPACE) {
            return self::REASON_ROOT;
        }

        foreach ($document->childNodes as $node) {
            if ($node instanceof \DOMProcessingInstruction) {
                return self::REASON_PROCESSING_INSTRUCTION;
            }
        }

        if ($document->getElementsByTagName('*')->length > self::MAX_ELEMENTS) {
            return self::REASON_TOO_MANY_ELEMENTS;
        }
        //Primero la lista blanca, y después los `use`: así cada regla da su propio motivo.
        return self::nodeRejection($root) ?? self::referenceRejection($root);
    }

    /**
     * Las referencias internas, que multiplican lo que se pinta: el `href` de un `use` (o de otro elemento que no sea de
     * animación, como un degradado que hereda de otro) y todo `url(#id)` de cualquier atributo (mask, clip-path, fill,
     * stroke, marker-*…). Falla en CERRADO: el destino tiene que existir con su `id` EXACTO (sin minúsculas, sin
     * %-decodificar, sin espacios dentro). Un `use` no puede apuntar a un `use` ni a algo que lo contenga. Y el COSTE del
     * documento no pasa de MAX_RENDERED_ELEMENTS: el de un elemento es 1, más el de sus hijos, más el del destino de cada
     * referencia que lleve; memoizado por elemento, y un ciclo rechaza. Así caen las bombas de `use`; las de máscaras y
     * clipPath anidados ya las para la lista blanca, y esto queda como segunda capa si algún día vuelven.
     *
     * @param \DOMElement $root
     * @return string|null
     */
    private static function referenceRejection(\DOMElement $root): ?string
    {
        $byId = [];
        $uses = [];
        $containsUse = new \SplObjectStorage();
        $index = function (\DOMElement $element) use (&$index, &$byId, &$uses, $containsUse): void {
            $id = $element->getAttribute('id');
            if ($id !== '' && !array_key_exists($id, $byId)) {
                $byId[$id] = $element;
            }
            $hasUse = $element->localName === 'use';
            if ($hasUse) {
                $uses[] = $element;
            }
            foreach ($element->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $index($child);
                    $hasUse = $hasUse || $containsUse[$child] === true;
                }
            }
            $containsUse[$element] = $hasUse;
        };
        $index($root);

        foreach ($uses as $use) {
            $fragment = self::hrefFragment($use);
            if ($fragment === null) {
                return self::REASON_USE_TARGET;
            }
            $target = $byId[$fragment] ?? null;
            if (!($target instanceof \DOMElement)) {
                return self::REASON_USE_TARGET;
            }
            if ($containsUse[$target] === true) {
                return self::REASON_NESTED_USE;
            }
        }

        $cost = new \SplObjectStorage();
        $visiting = new \SplObjectStorage();
        $reason = null;
        $over = self::MAX_RENDERED_ELEMENTS + 1;
        $measure = function (\DOMElement $element) use (&$measure, &$reason, $byId, $cost, $visiting, $over): int {
            if (isset($cost[$element])) {
                return (int) $cost[$element];
            }
            if (isset($visiting[$element])) {
                $reason ??= self::REASON_REFERENCE_CYCLE;
                return $over;
            }
            $visiting[$element] = true;
            $total = 1;
            $references = self::referencesOf($element);
            if (is_string($references)) {
                $reason ??= $references;
                return $over;
            }
            foreach ($references as $id) {
                $target = $byId[$id] ?? null;
                if (!($target instanceof \DOMElement)) {
                    $reason ??= self::REASON_REFERENCE_TARGET;
                    return $over;
                }
                $total += $measure($target);
                if ($reason !== null || $total > self::MAX_RENDERED_ELEMENTS) {
                    return $over;
                }
            }
            foreach ($element->childNodes as $child) {
                if ($child instanceof \DOMElement) {
                    $total += $measure($child);
                    if ($reason !== null || $total > self::MAX_RENDERED_ELEMENTS) {
                        return $over;
                    }
                }
            }
            unset($visiting[$element]);
            $cost[$element] = $total;
            return $total;
        };
        $total = $measure($root);
        return $reason ?? ($total > self::MAX_RENDERED_ELEMENTS ? self::REASON_TOO_MANY_INSTANCES : null);
    }

    /**
     * El `id` al que apunta el `href` (o `xlink:href`) de un elemento: sin espacios por fuera, `#` y el `id` exacto.
     *
     * @param \DOMElement $element
     * @return string|null null si no es una referencia interna bien formada (o no tiene `href`)
     */
    private static function hrefFragment(\DOMElement $element): ?string
    {
        $href = trim($element->hasAttribute('href') ? $element->getAttribute('href') : $element->getAttributeNS(self::XLINK_NAMESPACE, 'href'), " \t\n\r\f");
        $fragment = (string) substr($href, 1);
        return str_starts_with($href, '#') && $fragment !== '' && strpbrk($fragment, " \t\n\r\f%") === false ? $fragment : null;
    }

    /**
     * Los `id` a los que hace referencia un elemento: su `href`, salvo en los de animación (ahí nombra a quién se anima,
     * no algo que se pinte), y cada `url(#id)` de cualquiera de sus atributos.
     *
     * @param \DOMElement $element
     * @return string[]|string Los `id`, o el motivo del rechazo
     */
    private static function referencesOf(\DOMElement $element): array|string
    {
        $ids = [];
        $hasHref = $element->hasAttribute('href') || $element->hasAttributeNS(self::XLINK_NAMESPACE, 'href');
        if ($hasHref && !in_array($element->localName, self::ANIMATION_ELEMENTS, true)) {
            $fragment = self::hrefFragment($element);
            if ($fragment === null) {
                return self::REASON_REFERENCE_TARGET;
            }
            $ids[] = $fragment;
        }
        foreach ($element->attributes ?? [] as $attribute) {
            $value = (string) $attribute->value;
            if (stripos($value, 'url') === false) {
                continue;
            }
            $found = preg_match_all('/url\(\s*[\'"]?\s*#([^\'")\s]*)/i', $value, $matches);
            if ($found === false) {
                return self::REASON_REGEX;
            }
            foreach ($matches[1] as $id) {
                if ($id === '' || str_contains($id, '%')) {
                    return self::REASON_REFERENCE_TARGET;
                }
                $ids[] = $id;
            }
        }
        return $ids;
    }

    /**
     * @param string $content
     * @return bool
     */
    public static function isAllowed(string $content): bool
    {
        return self::rejectionReason($content) === null;
    }

    /**
     * @param \DOMElement $element
     * @return string|null
     */
    private static function nodeRejection(\DOMElement $element): ?string
    {
        if ($element->namespaceURI !== self::SVG_NAMESPACE || !in_array($element->localName, self::ALLOWED_ELEMENTS, true)) {
            return self::REASON_ELEMENT;
        }

        foreach ($element->attributes ?? [] as $attribute) {
            if ($attribute instanceof \DOMAttr) {
                $reason = self::attributeRejection($element, $attribute);
                if ($reason !== null) {
                    return $reason;
                }
            }
        }

        if ($element->localName === 'style') {
            $reason = self::cssRejection((string) $element->textContent, true);
            if ($reason !== null) {
                return $reason;
            }
        }

        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $reason = self::nodeRejection($child);
                if ($reason !== null) {
                    return $reason;
                }
            } elseif ($child instanceof \DOMText || $child instanceof \DOMComment) {
                //DOMCdataSection es un DOMText: el CDATA de un <style> ya lo miró cssRejection(), y aquí, el texto decodificado.
                if (self::hasForbiddenFragment((string) $child->nodeValue)) {
                    return self::REASON_FORBIDDEN;
                }
            } else {
                //Una referencia a entidad, una instrucción de procesamiento o cualquier otro nodo.
                return self::REASON_NODE;
            }
        }

        return null;
    }

    /**
     * @param \DOMElement $element
     * @param \DOMAttr $attribute
     * @return string|null
     */
    private static function attributeRejection(\DOMElement $element, \DOMAttr $attribute): ?string
    {
        $name = (string) $attribute->localName;
        $namespace = $attribute->namespaceURI;
        $value = (string) $attribute->value;

        if (str_starts_with(mb_strtolower($name), 'on')) {
            return self::REASON_EVENT_HANDLER;
        }
        if ($namespace === self::XML_NAMESPACE) {
            if (!in_array($name, self::ALLOWED_XML_ATTRIBUTES, true)) {
                return self::REASON_ATTRIBUTE_NAMESPACE;
            }
        } elseif ($namespace === self::XLINK_NAMESPACE) {
            if ($name !== 'href') {
                return self::REASON_ATTRIBUTE_NAMESPACE;
            }
        } elseif ($namespace !== null && $namespace !== '') {
            return self::REASON_ATTRIBUTE_NAMESPACE;
        } else {
            $masking = preg_match(self::MASKING_NAME_PATTERN, mb_strtolower($name));
            if ($masking !== 0) {
                return $masking === false ? self::REASON_REGEX : self::REASON_CSS_MASKING;
            }
        }

        if (self::hasForbiddenFragment($value)) {
            return self::REASON_FORBIDDEN;
        }
        //Un escape de CSS en CUALQUIER atributo: en los de presentación (mask, fill, clip-path, values…) `\75 rl(`
        //es `url(` para el navegador y no para esta clase.
        if (str_contains($value, '\\')) {
            return self::REASON_CSS_ESCAPE;
        }
        if ($name === 'href' && !str_starts_with(self::normalized($value), '#')) {
            return self::REASON_EXTERNAL_HREF;
        }
        //`#%6C0` es `#l0` para el navegador: ninguna referencia codificada.
        if ($name === 'href' && str_contains($value, '%')) {
            return self::REASON_ENCODED_HREF;
        }
        if (in_array($element->localName, self::ANIMATION_ELEMENTS, true) && $name === 'attributeName') {
            $animated = self::normalized($value);
            $animatedName = str_contains($animated, ':') ? substr($animated, (int) strrpos($animated, ':') + 1) : $animated;
            //`id` también: animarlo cambia a qué apunta un `use` después de validarlo.
            $maskingName = preg_match(self::MASKING_NAME_PATTERN, $animatedName);
            if ($maskingName === false) {
                return self::REASON_REGEX;
            }
            if ($maskingName === 1 || in_array($animatedName, self::NON_ANIMATABLE_ATTRIBUTES, true) || str_starts_with($animatedName, 'on')) {
                return self::REASON_ANIMATED_ATTRIBUTE;
            }
        }
        if (in_array($element->localName, self::ANIMATION_ELEMENTS, true) && in_array($name, self::ANIMATION_VALUE_ATTRIBUTES, true) && str_contains(self::normalized($value), 'url(')) {
            return self::REASON_ANIMATED_REFERENCE;
        }
        if ($name === 'style') {
            return self::cssRejection($value, false);
        }
        //Los atributos de presentación son valores de CSS: `mask="image-set('x.png' 1x)"` carga igual que en un <style>.
        if (($namespace === null || $namespace === '') && !in_array($name, self::NON_CSS_ATTRIBUTES, true) && !str_starts_with($name, 'aria-')) {
            return self::cssValueRejection($value);
        }
        return self::urlRejection($value);
    }

    /**
     * El CSS de un `<style>` o de un atributo `style`: sin escapes (con ellos, `url` o `@import` se escriben sin sus
     * letras), sin `@import` y con cada `url()` a una referencia interna.
     *
     * @param string $css
     * @param bool $styleElement Es un `<style>`: también `filter` y `marker-*` se rechazan aunque no lleven `url(`
     * @return string|null
     */
    private static function cssRejection(string $css, bool $styleElement): ?string
    {
        if (str_contains($css, '\\')) {
            return self::REASON_CSS_ESCAPE;
        }
        if (self::hasForbiddenFragment($css)) {
            return self::REASON_FORBIDDEN;
        }
        $compact = self::normalized($css);
        if (str_contains($compact, '@import')) {
            return self::REASON_IMPORT;
        }
        if (str_contains($compact, '/*')) {
            return self::REASON_CSS_COMMENT;
        }
        $masking = preg_match($styleElement ? self::STYLE_MASKING_PROPERTY_PATTERN : self::CSS_MASKING_PATTERN, $compact);
        if ($masking !== 0) {
            return $masking === false ? self::REASON_REGEX : self::REASON_CSS_MASKING;
        }
        return self::cssValueRejection($css);
    }

    /**
     * Lo que vale para cualquier valor de CSS, en `<style>`, en `style=` o en un atributo de presentación (mask, cursor,
     * fill, values…): sin funciones que carguen recursos, cada `url(` interno y ninguna cadena entre comillas fuera
     * del `#…` de un `url(`.
     *
     * @param string $css
     * @return string|null
     */
    private static function cssValueRejection(string $css): ?string
    {
        $compact = self::normalized($css);
        //Un comentario corta una declaración donde esta clase no la corta (`mask:/*;*/url(#m)`): no hace falta en una animación.
        if (str_contains($compact, '/*')) {
            return self::REASON_CSS_COMMENT;
        }
        //Una variable lleva una referencia a donde no se puede medir su coste.
        if (str_contains($compact, 'var(')) {
            return self::REASON_CSS_VARIABLE;
        }
        if (str_contains($compact, '--')) {
            $custom = preg_match('/(?<![a-z0-9_-])--[a-z0-9_-]*:/', $compact);
            if ($custom !== 0) {
                return $custom === false ? self::REASON_REGEX : self::REASON_CSS_VARIABLE;
            }
        }
        $function = preg_match(self::FORBIDDEN_CSS_FUNCTIONS_PATTERN, $compact);
        if ($function !== 0) {
            return $function === false ? self::REASON_REGEX : self::REASON_CSS_FUNCTION;
        }
        $urlReason = self::urlRejection($css);
        if ($urlReason !== null) {
            return $urlReason;
        }
        //Una cadena entre comillas solo vale como argumento `#…` de un `url(`: image-set("x.png" 1x) y similares no. A
        //mano y no con una expresión: con un millón de caracteres, la expresión se pasaba del límite y no miraba nada.
        $length = strlen($compact);
        $offset = strcspn($compact, '"\'');
        while ($offset < $length) {
            $end = strpos($compact, $compact[$offset], $offset + 1);
            if ($end === false || $offset < 4 || substr($compact, $offset - 4, 4) !== 'url(' || ($compact[$offset + 1] ?? '') !== '#') {
                return self::REASON_CSS_STRING;
            }
            $offset = $end + 1 + strcspn($compact, '"\'', $end + 1);
        }
        return null;
    }

    /**
     * Cada `url(` del valor, con o sin comillas y con espacios, tiene que apuntar a `#…`.
     *
     * @param string $value
     * @return string|null
     */
    private static function urlRejection(string $value): ?string
    {
        //Un solo recorrido: copiar el resto del texto por cada `url(` era cuadrático (2 MB, 10 s).
        $found = preg_match_all('/url\([\'"]*(.?)/', self::normalized($value), $targets);
        if ($found === false) {
            return self::REASON_REGEX;
        }
        if ($found > 0) {
            foreach ($targets[1] as $first) {
                if ($first !== '#') {
                    return self::REASON_EXTERNAL_URL;
                }
            }
        }
        return null;
    }

    /**
     * @param string $value
     * @return bool
     */
    private static function hasForbiddenFragment(string $value): bool
    {
        $compact = self::normalized($value);
        foreach (self::FORBIDDEN_FRAGMENTS as $fragment) {
            if (str_contains($compact, $fragment)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Sin espacios ni caracteres de control y en minúsculas: «JaVa script :» y «url ( "x" )» se ven como son.
     *
     * @param string $value
     * @return string
     */
    private static function normalized(string $value): string
    {
        //Sin expresión: un fallo suyo devolvía vacío y lo escondía todo.
        static $controls = null;
        if ($controls === null) {
            $controls = ["\x7F" => ''];
            for ($code = 0; $code <= 0x20; $code++) {
                $controls[chr($code)] = '';
            }
        }
        return mb_strtolower(strtr($value, $controls));
    }

}
