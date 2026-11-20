<?php

/**
 * MarkContext.php
 */

namespace PiecesPHP\PublicSite;

/**
 * MarkContext - Lo que recibe cada marca al pintarse: el sitio, la página y quién pinta las partes.
 *
 * @package     PiecesPHP\PublicSite
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class MarkContext
{

    /**
     * @param string $site
     * @param string $page
     * @param SiteRenderer|null $renderer Sin él, una pcs-part se pinta vacía
     */
    public function __construct(
        public readonly string $site,
        public readonly string $page,
        public readonly ?SiteRenderer $renderer = null,
    ) {
    }

    /**
     * @return string
     */
    public function langGroup(): string
    {
        return PublicSite::langGroup($this->site);
    }

    /**
     * Una parte del sitio, compilada y pintada.
     *
     * @param string $name
     * @return string
     */
    public function part(string $name): string
    {
        return $this->renderer !== null ? $this->renderer->renderPart($name, $this) : '';
    }
}
