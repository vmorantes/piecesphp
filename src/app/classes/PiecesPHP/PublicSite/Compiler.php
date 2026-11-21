<?php

/**
 * Compiler.php
 */

namespace PiecesPHP\PublicSite;

/**
 * Compiler - Convierte el HTML del diseñador (una página o una parte) con marcas en una vista PHP. No lee ni escribe
 * disco: recibe el HTML y lo que existe (páginas, partes), y devuelve la vista, sus estáticos y los problemas.
 *
 * SEGURIDAD: lo único que es PHP en la salida lo escribe este archivo. Todo `<?` o `<%` del diseñador sale como texto
 * (lo imprime un `echo` del motor) y un `<script language="php">` se quita; nada del diseñador se evalúa ni se incluye.
 *
 * @package     PiecesPHP\PublicSite
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class Compiler
{

    const KIND_PAGE = 'page';
    const KIND_PART = 'part';

    /** Los atributos donde un {{campo}} pasa por el filtro de URL sea cual sea su tipo ({{@campo}} en lo compilado). */
    const URL_FIELD_ATTRIBUTES = ['href', 'src', 'action', 'formaction', 'poster', 'srcset', 'xlink:href', 'data-src', 'data-srcset'];

    /** Los atributos con una URL que se reescribe. */
    const URL_ATTRIBUTES = ['src', 'href', 'poster', 'data-src', 'srcset', 'data-srcset'];

    /**
     * @var string
     */
    private $nonce = '';

    /**
     * @var string
     */
    private $kind = self::KIND_PAGE;

    /**
     * @var array<int,string> Marca de sustitución => PHP que la reemplaza
     */
    private $slots = [];

    /**
     * @var array<int,array{line:int,message:string}>
     */
    private $problems = [];

    /**
     * @var array{css:string[],js:string[],parts:string[],formActions:string[],usesImage:bool,title:string,description:string}
     */
    private $meta = ['css' => [], 'js' => [], 'parts' => [], 'formActions' => [], 'usesImage' => false, 'title' => '', 'description' => ''];

    /**
     * @param string $site
     * @param string[] $pages Las páginas que existen
     * @param string[] $parts Las partes que existen
     */
    public function __construct(
        private readonly string $site,
        private readonly array $pages,
        private readonly array $parts,
    ) {
    }

    /**
     * @param string $html
     * @param string $kind KIND_PAGE (un documento entero) o KIND_PART (un fragmento)
     * @param string $name El nombre de la página o de la parte
     * @return array{php:string,problems:array<int,array{line:int,message:string}>,css:string[],js:string[],parts:string[],formActions:string[],usesImage:bool,title:string,description:string}
     */
    public function compile(string $html, string $kind, string $name): array
    {
        $this->nonce = bin2hex(random_bytes(6));
        $this->kind = $kind;
        $this->slots = [];
        $this->problems = [];
        $this->meta = ['css' => [], 'js' => [], 'parts' => [], 'formActions' => [], 'usesImage' => false, 'title' => '', 'description' => ''];

        foreach (self::phpTagLines($html) as $line) {
            $this->problem($line, 'hay código de servidor (<?, <% o <script language="php">): se sale como texto y no se ejecuta');
        }

        $source = self::voidMarks($html);
        if ($source === null) {
            $this->problem(0, 'el HTML es demasiado complejo para leer sus marcas sin contenido: revisa que cada etiqueta se cierre con «>»');
            $source = $html;
        }
        if ($kind === self::KIND_PAGE) {
            $document = \Dom\HTMLDocument::createFromString($source, \LIBXML_NOERROR);
            $root = $document->documentElement;
        } else {
            $document = \Dom\HTMLDocument::createFromString('<!DOCTYPE html><html><head></head><body></body></html>', \LIBXML_NOERROR);
            $root = $document->body;
            if ($root !== null) {
                $root->innerHTML = $source;
            }
        }
        if ($root === null) {
            $this->problem(0, 'el HTML no se pudo leer');
            return $this->result('');
        }

        foreach (iterator_to_array($root->getElementsByTagName('script')) as $script) {
            if (mb_strtolower(trim((string) $script->getAttribute('language'))) === 'php') {
                $script->remove();
            }
        }
        $this->removeBases($root, 0);

        if ($kind === self::KIND_PAGE) {
            $this->preparePage($document);
        }
        $this->walk($root, $kind, $name);
        if ($kind === self::KIND_PAGE) {
            $this->finishPage($document);
        }

        $serialized = $kind === self::KIND_PAGE ? $document->saveHtml() : $root->innerHTML;
        return $this->result($serialized);
    }

    /**
     * El framework pone su <base> (finishPage): uno del diseñador cambiaría a dónde van todas las URL relativas. Se quita
     * también del contenido de cada <template>, que getElementsByTagName() no ve; se lee aparte y, si lo llevaba, se reescribe.
     *
     * @param \Dom\Element $root
     * @param int $lineOffset La línea (desde 0) donde empieza $root en el HTML del diseñador
     * @return void
     */
    private function removeBases(\Dom\Element $root, int $lineOffset): void
    {
        foreach (iterator_to_array($root->getElementsByTagName('base')) as $base) {
            $this->problem($lineOffset + $base->getLineNo() + 1, 'tu página trae su propio <base>: se quita');
            $base->remove();
        }
        foreach (iterator_to_array($root->getElementsByTagName('template')) as $template) {
            $inner = (string) $template->innerHTML;
            if (stripos($inner, '<base') === false) {
                continue;
            }
            $body = \Dom\HTMLDocument::createFromString('<!DOCTYPE html><html><head></head><body>' . $inner . '</body></html>', \LIBXML_NOERROR)->body;
            if ($body !== null) {
                $this->removeBases($body, $lineOffset + $template->getLineNo());
                $template->innerHTML = $body->innerHTML;
            }
        }
    }

    /**
     * Las líneas (desde 1) con `<?`, `<%` o `<script language=php>`.
     *
     * @param string $html
     * @return int[]
     */
    public static function phpTagLines(string $html): array
    {
        $lines = [];
        foreach (preg_split('/\R/', $html) ?: [] as $index => $line) {
            if (str_contains($line, '<?') || str_contains($line, '<%') || preg_match('/<script\b[^>]*\blanguage\s*=\s*["\']?php/i', $line) === 1) {
                $lines[] = $index + 1;
            }
        }
        return $lines;
    }

    /**
     * Las marcas sin contenido pasan a <meta data-pcs-mark="…">: un elemento desconocido en <head> lo cierra, y el
     * analizador mandaría al <body> todo lo que sigue. Se conservan los saltos de línea para no mover los números de línea.
     *
     * @param string $html
     * @return string|null Null si la expresión falla (preg_last_error)
     */
    public static function voidMarks(string $html): ?string
    {
        $names = array_values(array_filter(MarkRules::markNames(), fn (string $mark): bool => MarkRules::content($mark) === MarkRules::CONTENT_EMPTY));
        if ($names === []) {
            return $html;
        }
        //Posesivos: con «[^>]*?» y «\s*» solapados, «<pcs-meta» y 300.000 espacios tardaban 12 s y vaciaban la página.
        $pattern = '/<(' . implode('|', array_map(fn (string $n): string => preg_quote($n, '/'), $names)) . ')((?:\s[^>]*+)?)>(?:\s*+<\/\1\s*+>)?/i';
        $result = preg_replace_callback($pattern, function (array $match): string {
            $newLines = str_repeat("\n", substr_count($match[0], "\n"));
            return '<meta data-pcs-mark="' . mb_strtolower($match[1]) . '"' . rtrim($match[2], " \t\n\r\f/") . $newLines . '>';
        }, $html);
        return is_string($result) && preg_last_error() === \PREG_NO_ERROR ? $result : null;
    }

    /**
     * Título y descripción del diseñador; con pcs-meta, se quitan los suyos para que no se repitan.
     *
     * @param \Dom\HTMLDocument $document
     * @return void
     */
    private function preparePage(\Dom\HTMLDocument $document): void
    {
        $head = $document->head;
        if ($head === null) {
            return;
        }
        $title = $head->getElementsByTagName('title')->item(0);
        $this->meta['title'] = $title !== null ? trim((string) $title->textContent) : '';
        $hasMeta = false;
        foreach (iterator_to_array($document->getElementsByTagName('meta')) as $meta) {
            if ($meta->getAttribute('data-pcs-mark') === 'pcs-meta') {
                $hasMeta = true;
            }
            if (mb_strtolower((string) $meta->getAttribute('name')) === 'description') {
                $this->meta['description'] = trim((string) $meta->getAttribute('content'));
            }
        }
        if (!$hasMeta) {
            return;
        }
        $title?->remove();
        foreach (iterator_to_array($head->childNodes) as $node) {
            if (!$node instanceof \Dom\Element) {
                continue;
            }
            $property = mb_strtolower((string) $node->getAttribute('property'));
            $metaName = mb_strtolower((string) $node->getAttribute('name'));
            $rel = mb_strtolower((string) $node->getAttribute('rel'));
            if (($node->localName === 'meta' && ($metaName === 'description' || str_starts_with($property, 'og:') || str_starts_with($metaName, 'twitter:'))) || ($node->localName === 'link' && $rel === 'canonical')) {
                $node->remove();
            }
        }
    }

    /**
     * Lo que el framework pone en toda página: el <base> de su cabecera (sin él, los estáticos relativos no cargan en una
     * página interior), idioma, lo de su <head> y su final de <body>, y los estáticos si el diseñador no tenía dónde.
     *
     * @param \Dom\HTMLDocument $document
     * @return void
     */
    private function finishPage(\Dom\HTMLDocument $document): void
    {
        $document->documentElement?->setAttribute('lang', $this->slot('<?php echo escape_html((string) get_config(\'app_lang\')); ?>'));
        $head = $document->head;
        $body = $document->body;
        if ($head !== null) {
            $base = $document->createElement('base');
            $base->setAttribute('href', $this->slot('<?php echo escape_html(baseurl()); ?>'));
            $head->prepend($base);
            if ($this->meta['css'] === []) {
                $head->append($document->createComment($this->commentSlot('<?php load_css(); ?>')));
            }
            $head->append($document->createComment($this->commentSlot('<?php echo \\PiecesPHP\\PublicSite\\SiteRenderer::headFragment($pcs); ?>')));
        }
        if ($body !== null) {
            if ($this->meta['js'] === []) {
                $body->append($document->createComment($this->commentSlot('<?php load_js(); ?>')));
            }
            $body->append($document->createComment($this->commentSlot('<?php echo \\PiecesPHP\\PublicSite\\SiteRenderer::bodyEndFragment($pcs); ?>')));
        }
    }

    /**
     * Recorre los hijos: reescribe URLs, saca los estáticos del diseñador y convierte cada marca.
     *
     * @param \Dom\Element $parent
     * @param string $kind
     * @param string $name
     * @return void
     */
    private function walk(\Dom\Element $parent, string $kind, string $name): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if (!$node instanceof \Dom\Element) {
                if ($node instanceof \Dom\Text && $parent->localName === 'style') {
                    $node->data = $this->rewriteCssUrls($node->data);
                }
                continue;
            }
            $markName = $node->localName === 'meta' && $node->hasAttribute('data-pcs-mark') ? (string) $node->getAttribute('data-pcs-mark') : $node->localName;
            if (str_starts_with($markName, 'pcs-')) {
                $this->mark($node, $markName, $kind, $name);
                continue;
            }
            if ($this->extractAsset($node)) {
                continue;
            }
            $this->rewriteUrls($node);
            if ($node->localName !== 'template') {
                $this->walk($node, $kind, $name);
            }
        }
    }

    /**
     * Una marca: si está mal, se quita y queda el problema; si está bien, se cambia por la llamada a su pintor.
     *
     * @param \Dom\Element $element
     * @param string $markName
     * @param string $kind
     * @param string $name
     * @return void
     */
    private function mark(\Dom\Element $element, string $markName, string $kind, string $name): void
    {
        $line = $element->getLineNo() + 1;
        $attributes = [];
        foreach ($element->attributes as $attribute) {
            if ($attribute->name !== 'data-pcs-mark') {
                $attributes[$attribute->name] = (string) $attribute->value;
            }
        }
        $problems = [];
        if (!MarkRules::isKnown($markName) && !MarkRegistry::has($markName)) {
            $problems[] = "<{$markName}> no es una marca conocida (" . implode(', ', MarkRules::markNames()) . ')';
        }
        $problems = array_merge($problems, MarkRules::attributeProblems($markName, $attributes));
        $content = '';
        $template = null;
        if ($problems === []) {
            $problems = $this->markReferenceProblems($markName, $attributes, $kind, $name);
        }
        $contentType = MarkRules::content($markName);
        if ($problems === [] && $contentType === MarkRules::CONTENT_TEMPLATE) {
            foreach ($element->children as $child) {
                if ($child->localName === 'template') {
                    $template = $child;
                    break;
                }
            }
            if ($template === null) {
                $problems[] = "<{$markName}> necesita dentro una <template> con lo que se repite";
            } else {
                $templatePhp = $this->templateContent($template, $attributes['resource'] ?? '', $line);
                if ($templatePhp === null) {
                    $problems[] = "<{$markName}> tiene un {{campo}} donde no se puede poner";
                } else {
                    $content = $templatePhp;
                }
            }
        }
        if ($problems !== []) {
            foreach ($problems as $problem) {
                $this->problem($line, $problem . ': se pinta vacía');
            }
            $element->remove();
            return;
        }

        $call = fn (string $contentPhp): string => '<?php echo \\PiecesPHP\\PublicSite\\MarkRegistry::render(' . var_export($markName, true) . ', ' . var_export($attributes, true) . ', ' . $contentPhp . ', $pcs); ?>';

        if ($contentType === MarkRules::CONTENT_INLINE || $contentType === MarkRules::CONTENT_ANY) {
            $this->walk($element, $kind, $name);
            $start = $element->ownerDocument?->createComment($this->commentSlot('<?php ob_start(); ?>'));
            $end = $element->ownerDocument?->createComment($this->commentSlot($call('(string) ob_get_clean()')));
            if ($start !== null && $end !== null) {
                $element->before($start, ...iterator_to_array($element->childNodes));
                $element->replaceWith($end);
            }
            return;
        }
        if ($contentType === MarkRules::CONTENT_TEXT) {
            $content = var_export(trim((string) $element->textContent), true);
        } elseif ($contentType !== MarkRules::CONTENT_TEMPLATE) {
            $content = "''";
        }
        $comment = $element->ownerDocument?->createComment($this->commentSlot($call($content)));
        if ($comment !== null) {
            $element->replaceWith($comment);
        }
    }

    /**
     * Lo que una marca nombra y tiene que existir: la parte, la página, el recurso, la imagen de marca, la acción.
     *
     * @param string $markName
     * @param array<string,string> $attributes
     * @param string $kind
     * @param string $name
     * @return string[]
     */
    private function markReferenceProblems(string $markName, array $attributes, string $kind, string $name): array
    {
        switch ($markName) {
            case 'pcs-part':
                $part = $attributes['name'] ?? '';
                if (!in_array($part, $this->parts, true)) {
                    return ["<pcs-part name=\"{$part}\"> pide parts/{$part}.html, que no existe"];
                }
                if ($kind === self::KIND_PART && $part === $name) {
                    return ["la parte «{$name}» se incluye a sí misma"];
                }
                $this->meta['parts'][] = $part;
                return [];
            case 'pcs-link':
                $page = $attributes['page'] ?? '';
                return in_array($page, $this->pages, true) ? [] : ["<pcs-link page=\"{$page}\"> apunta a pages/{$page}.html, que no existe"];
            case 'pcs-list':
                $resource = $attributes['resource'] ?? '';
                return ResourceRegistry::get($resource) !== null ? [] : ["<pcs-list resource=\"{$resource}\"> pide un recurso que no existe (" . implode(', ', array_keys(ResourceRegistry::all())) . ')'];
            case 'pcs-image':
                $brand = $attributes['brand'] ?? '';
                $this->meta['usesImage'] = true;
                return in_array($brand, MarkRegistry::brandNames(), true) ? [] : ["<pcs-image brand=\"{$brand}\"> no es una imagen de marca (" . implode(', ', MarkRegistry::brandNames()) . ')'];
            case 'pcs-form':
                $action = $attributes['action'] ?? '';
                if (!FormActionRegistry::has($action)) {
                    return ["<pcs-form action=\"{$action}\"> pide una acción que no existe (" . implode(', ', FormActionRegistry::names()) . ')'];
                }
                $this->meta['formActions'][] = $action;
                return [];
        }
        return [];
    }

    /**
     * La <template> de un pcs-list como expresión PHP: sus URLs reescritas, cada {{campo}} comprobado contra el recurso,
     * sin marcas dentro (no se pintarían) y nunca en un sitio donde el escapado HTML no basta.
     *
     * @param \Dom\Element $template
     * @param string $resource
     * @param int $line
     * @return string|null Null si un {{campo}} está en on*, style, <script> o <style>: la marca no se pinta
     */
    private function templateContent(\Dom\Element $template, string $resource, int $line): ?string
    {
        $document = \Dom\HTMLDocument::createFromString('<!DOCTYPE html><html><head></head><body></body></html>', \LIBXML_NOERROR);
        $body = $document->body;
        if ($body === null) {
            return "''";
        }
        $body->innerHTML = $template->innerHTML;
        foreach (iterator_to_array($body->getElementsByTagName('*')) as $element) {
            if (str_starts_with($element->localName, 'pcs-')) {
                $this->problem($line, "<{$element->localName}> dentro de la <template> de pcs-list no se admite: se quita");
                $element->remove();
                continue;
            }
            $this->rewriteUrls($element);
        }
        $unsafe = false;
        foreach (iterator_to_array($body->getElementsByTagName('*')) as $element) {
            if (in_array($element->localName, ['script', 'style'], true) && str_contains((string) $element->textContent, '{{')) {
                $this->problem($line, "{{campo}} dentro de <{$element->localName}> no se admite: ahí el escapado no protege");
                $unsafe = true;
            }
            foreach (iterator_to_array($element->attributes) as $attribute) {
                $name = mb_strtolower($attribute->name);
                $value = (string) $attribute->value;
                if (!str_contains($value, '{{')) {
                    continue;
                }
                if (str_starts_with($name, 'on') || $name === 'style' || $name === 'srcdoc' || ($name === 'data' && $element->localName === 'object')) {
                    $this->problem($line, "{{campo}} en el atributo «{$attribute->name}» no se admite: ahí el escapado no protege");
                    $unsafe = true;
                } elseif (in_array($name, self::URL_FIELD_ATTRIBUTES, true)) {
                    $element->setAttribute($attribute->name, (string) preg_replace('/\{\{\s*+@?/', '{{@', $value));
                }
            }
        }
        if ($unsafe) {
            return null;
        }
        $html = $body->innerHTML;
        $contract = ResourceRegistry::get($resource);
        $fields = preg_match_all('/\{\{\s*+@?([^}\s]*+)\s*+\}\}/', $html, $matches) > 0 ? array_unique($matches[1]) : [];
        foreach ($fields as $field) {
            if ($contract === null || !array_key_exists($field, $contract->fields)) {
                $this->problem($line, "{{{$field}}} no es un campo del recurso «{$resource}» (" . implode(', ', $contract !== null ? array_keys($contract->fields) : []) . '): sale vacío');
            }
        }
        return $this->phpStringWithSlots($html);
    }

    /**
     * Un <link rel=stylesheet> o un <script src> del diseñador: los de assets/ pasan a set_custom_assets (en su orden; el
     * primero de una página deja el sitio donde se cargan) y los de la vista previa (.pcs/, data/, parts.js) se quitan.
     *
     * @param \Dom\Element $element
     * @return bool Si el elemento se quitó o se sustituyó
     */
    private function extractAsset(\Dom\Element $element): bool
    {
        $isStylesheet = $element->localName === 'link' && in_array('stylesheet', preg_split('/\s+/', mb_strtolower((string) $element->getAttribute('rel'))) ?: [], true);
        $isScript = $element->localName === 'script' && $element->hasAttribute('src');
        if (!$isStylesheet && !$isScript) {
            return false;
        }
        $url = trim((string) $element->getAttribute($isStylesheet ? 'href' : 'src'));
        if (preg_match('#^(\./)?(\.\./)?(\.?pcs/|data/|parts/parts\.js)#', $url) === 1) {
            $element->remove();
            return true;
        }
        $asset = self::assetPath($url);
        if ($asset === null || ($isScript && $element->hasAttribute('type') && mb_strtolower((string) $element->getAttribute('type')) === 'module')) {
            return false;
        }
        $list = $isStylesheet ? 'css' : 'js';
        $first = $this->meta[$list] === [];
        $this->meta[$list][] = PublicSite::staticPath($this->site, $asset);
        if ($first && $this->kind === self::KIND_PAGE) {
            $comment = $element->ownerDocument?->createComment($this->commentSlot($isStylesheet ? '<?php load_css(); ?>' : '<?php load_js(); ?>'));
            if ($comment !== null) {
                $element->replaceWith($comment);
                return true;
            }
        }
        $element->remove();
        return true;
    }

    /**
     * Las URLs de un elemento: las de assets/ van a statics/<sitio>/, y los enlaces a una página .html, a su ruta.
     *
     * @param \Dom\Element $element
     * @return void
     */
    private function rewriteUrls(\Dom\Element $element): void
    {
        foreach (self::URL_ATTRIBUTES as $attribute) {
            if (!$element->hasAttribute($attribute)) {
                continue;
            }
            $value = (string) $element->getAttribute($attribute);
            if (str_contains($attribute, 'srcset')) {
                $candidates = array_map(function (string $candidate): string {
                    $parts = preg_split('/\s+/', trim($candidate), 2) ?: [''];
                    return $this->rewriteUrl($parts[0], false) . (isset($parts[1]) ? ' ' . $parts[1] : '');
                }, explode(',', $value));
                $element->setAttribute($attribute, implode(', ', $candidates));
                continue;
            }
            $element->setAttribute($attribute, $this->rewriteUrl($value, $attribute === 'href', $element->getLineNo() + 1));
        }
        if ($element->hasAttribute('style')) {
            $element->setAttribute('style', $this->rewriteCssUrls((string) $element->getAttribute('style')));
        }
    }

    /**
     * @param string $url
     * @param bool $mayBePage
     * @param int $line
     * @return string
     */
    private function rewriteUrl(string $url, bool $mayBePage, int $line = 0): string
    {
        $asset = self::assetPath(trim($url));
        if ($asset !== null) {
            return $this->slot('<?php echo escape_html(baseurl(' . var_export(PublicSite::staticPath($this->site, $asset), true) . ')); ?>');
        }
        //Con el <base> del framework, «#x» iría a la portada: va a la página que se pinta, también desde una parte.
        if ($mayBePage && str_starts_with(trim($url), '#')) {
            return $this->slot('<?php echo escape_html(\\PiecesPHP\\PublicSite\\PublicSite::pageURL($pcs->page) . ' . var_export(trim($url), true) . '); ?>');
        }
        if ($mayBePage && preg_match('#^(?:\./)?(?:\.\./pages/)?([^/?\#]+)\.html(\#[^\s]*)?$#', trim($url), $match) === 1) {
            if (!in_array($match[1], $this->pages, true)) {
                $this->problem($line, "el enlace a «{$url}» apunta a una página que no existe");
                return $this->rewriteUrl('#', true);
            }
            return $this->slot('<?php echo escape_html(\\PiecesPHP\\PublicSite\\PublicSite::pageURL(' . var_export($match[1], true) . ') . ' . var_export($match[2] ?? '', true) . '); ?>');
        }
        return $url;
    }

    /**
     * @param string $css
     * @return string
     */
    private function rewriteCssUrls(string $css): string
    {
        $result = preg_replace_callback('/url\(\s*+([\'"]?)([^\'")]++)\1\s*+\)/i', fn (array $match): string => 'url(' . $match[1] . $this->rewriteUrl($match[2], false) . $match[1] . ')', $css);
        if (!is_string($result) || preg_last_error() !== \PREG_NO_ERROR) {
            $this->problem(0, 'un CSS en línea es demasiado complejo para reescribir sus url(): se deja como está');
            return $css;
        }
        return $result;
    }

    /**
     * La ruta dentro de assets/ de una URL del diseñador (../assets/x, ./assets/x o assets/x), o null si no es de assets/.
     *
     * @param string $url
     * @return string|null
     */
    public static function assetPath(string $url): ?string
    {
        if (preg_match('#^(?:\./)?(?:\.\./)?assets/(.+)$#', $url, $match) !== 1 || str_contains($match[1], '../')) {
            return null;
        }
        return $match[1];
    }

    /**
     * Una marca de sustitución en texto o en un atributo: solo letras y números, para que el serializador no la toque.
     *
     * @param string $php
     * @return string
     */
    private function slot(string $php): string
    {
        $id = count($this->slots);
        $this->slots[$id] = $php;
        return "PCSSLOT{$this->nonce}x{$id}x";
    }

    /**
     * Lo mismo, como texto de un comentario.
     *
     * @param string $php
     * @return string
     */
    private function commentSlot(string $php): string
    {
        return 'PCS' . $this->slot($php);
    }

    /**
     * Un HTML con marcas de sustitución como expresión PHP de cadena: cada marca, la expresión que imprime su código.
     *
     * @param string $html
     * @return string
     */
    private function phpStringWithSlots(string $html): string
    {
        $parts = preg_split('/PCSSLOT' . $this->nonce . 'x(\d+)x/', $html, -1, \PREG_SPLIT_DELIM_CAPTURE) ?: [$html];
        $expression = [];
        foreach ($parts as $index => $part) {
            if ($index % 2 === 0) {
                $expression[] = var_export($part, true);
                continue;
            }
            $php = $this->slots[(int) $part] ?? '';
            $expression[] = preg_match('/^<\?php echo (.+); \?>$/s', $php, $match) === 1 ? '(string) (' . $match[1] . ')' : "''";
        }
        return implode(' . ', $expression);
    }

    /**
     * @param int $line
     * @param string $message
     * @return void
     */
    private function problem(int $line, string $message): void
    {
        $this->problems[] = ['line' => $line, 'message' => MarkRules::printable($message)];
    }

    /**
     * La vista PHP final: el HTML del diseñador, con cada `<?` y `<%` impreso como texto, y las marcas, en PHP del motor.
     *
     * @param string $serialized
     * @return array{php:string,problems:array<int,array{line:int,message:string}>,css:string[],js:string[],parts:string[],formActions:string[],usesImage:bool,title:string,description:string}
     */
    private function result(string $serialized): array
    {
        $chunks = preg_split('/(?:<!--PCS)?PCSSLOT' . $this->nonce . 'x(\d+)x(?:-->)?/', $serialized, -1, \PREG_SPLIT_DELIM_CAPTURE) ?: [$serialized];
        $php = "<?php defined('BASEPATH') or die(); /* Vista compilada por PiecesPHP\\PublicSite " . PublicSite::ENGINE_VERSION . ": no se edita. */ ?>\n";
        foreach ($chunks as $index => $chunk) {
            $php .= $index % 2 === 0 ? self::neutralize($chunk) : ($this->slots[(int) $chunk] ?? '');
        }
        $this->meta['parts'] = array_values(array_unique($this->meta['parts']));
        $this->meta['formActions'] = array_values(array_unique($this->meta['formActions']));
        return ['php' => $php, 'problems' => $this->problems] + $this->meta;
    }

    /**
     * El HTML del diseñador nunca abre PHP: cada `<?` y `<%` lo imprime un echo del motor, con los mismos bytes.
     *
     * @param string $html
     * @return string
     */
    public static function neutralize(string $html): string
    {
        return strtr($html, ['<?' => "<?php echo '<?'; ?>", '<%' => "<?php echo '<%'; ?>"]);
    }
}
