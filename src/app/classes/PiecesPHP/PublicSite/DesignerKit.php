<?php

/**
 * DesignerKit.php
 */

namespace PiecesPHP\PublicSite;

/**
 * DesignerKit - Lo generado de la carpeta del diseñador, con la misma regla que kit/pcs/check.js (CHECK.html):
 * parts/parts.js, pcs/rules.js, data/<recurso>.js y la huella de pcs.json. Mismos bytes en PHP y en el navegador, para
 * que exportar e importar sin cambios no cambie nada.
 *
 * @package     PiecesPHP\PublicSite
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class DesignerKit
{

    const KIT_DIRECTORY = __DIR__ . '/kit';

    /** Lo oculto (también site/.pcs/): ni se importa ni entra en `files` de pcs.json (HIDDEN de check.js). */
    const HIDDEN_PATTERN = '#(^|/)(\.|Thumbs\.db$|desktop\.ini$)#i';

    /** En el paquete, el sitio del diseñador; todo lo demás es del kit o de su agente. */
    const SITE_DIRECTORY = 'site';

    /** El lector de la vista previa, dentro de site/. */
    const READER_DIRECTORY = '.pcs';

    /** Relativas a site/. */
    const PARTS_SCRIPT = 'parts/parts.js';
    const RULES_SCRIPT = '.pcs/rules.js';

    const JSON_FLAGS = \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_LINE_TERMINATORS | \JSON_THROW_ON_ERROR;

    /**
     * Un valor como lo escribe literal() de check.js: listas en una línea, objetos con tabuladores.
     *
     * @param mixed $value
     * @param int $depth
     * @return string
     */
    public static function literal(mixed $value, int $depth = 0): string
    {
        if (is_array($value) && array_is_list($value) && $value !== []) {
            return '[' . implode(', ', array_map(fn (mixed $item): string => self::literal($item, $depth), $value)) . ']';
        }
        if (is_array($value)) {
            if ($value === []) {
                return '{}';
            }
            $inner = str_repeat("\t", $depth + 1);
            $lines = [];
            foreach ($value as $key => $item) {
                $lines[] = $inner . json_encode((string) $key, self::JSON_FLAGS) . ': ' . self::literal($item, $depth + 1);
            }
            return "{\n" . implode(",\n", $lines) . "\n" . str_repeat("\t", $depth) . '}';
        }
        return json_encode($value, self::JSON_FLAGS);
    }

    /**
     * pcs/rules.js, desde rules.json.
     *
     * @return string
     */
    public static function rulesScript(): string
    {
        return "/* Generado desde pcs/rules.json por CHECK.html (o por la exportación). No se edita a mano. */\nwindow.PCS_RULES = " . self::literal(MarkRules::rules()) . "\n";
    }

    /**
     * parts/parts.js, desde el HTML de cada parte y los nombres de las páginas.
     *
     * @param array<string,string> $parts Nombre => HTML
     * @param string[] $pages
     * @return string
     */
    public static function partsScript(array $parts, array $pages): string
    {
        ksort($parts, \SORT_STRING);
        sort($pages, \SORT_STRING);
        $lines = ['/* Generado por CHECK.html desde la carpeta parts/. No se edita a mano: se cambia parts/<nombre>.html y se vuelve a comprobar. */', 'window.PCS_PARTS = {'];
        $names = array_keys($parts);
        foreach ($names as $index => $name) {
            $lines[] = "\t" . json_encode((string) $name, self::JSON_FLAGS) . ': ' . json_encode($parts[$name], self::JSON_FLAGS) . ($index < count($names) - 1 ? ',' : '');
        }
        $lines[] = '}';
        $lines[] = 'window.PCS_PAGES = ' . ($pages === [] ? '[]' : self::literal($pages));
        $lines[] = 'window.PCS_PARTS_INFO = { "formatVersion": ' . json_encode(MarkRules::formatVersion()) . ' }';
        return implode("\n", $lines) . "\n";
    }

    /**
     * Los data/<recurso>.js de todos los recursos registrados.
     *
     * @return array<string,string> Ruta relativa => contenido
     */
    public static function dataScripts(): array
    {
        $files = [];
        foreach (ResourceRegistry::all() as $name => $contract) {
            $files["data/{$name}.js"] = $contract->exampleScript();
        }
        return $files;
    }

    /**
     * pcs.json (formato común con check.js): `files`, la huella sha256 de lo del diseñador en site/ (sin lo oculto ni
     * site/.pcs/), y `protected`, la del lector y los archivos de su agente; rutas relativas a la raíz del paquete.
     * Sin $protected (en el proyecto, que lo ignora), no se escribe.
     *
     * @param string $site
     * @param array<string,string> $siteHashes Ruta relativa a site/ => sha256
     * @param array<string,string>|null $protected Ruta relativa a la raíz del paquete => sha256
     * @return string
     */
    public static function manifest(string $site, array $siteHashes, ?array $protected = null): string
    {
        $files = [];
        foreach ($siteHashes as $path => $hash) {
            if (preg_match(self::HIDDEN_PATTERN, (string) $path) !== 1) {
                $files[self::SITE_DIRECTORY . '/' . $path] = $hash;
            }
        }
        ksort($files, \SORT_STRING);
        $manifest = ['name' => $site, 'formatVersion' => MarkRules::formatVersion(), 'files' => $files === [] ? new \stdClass() : $files];
        if ($protected !== null) {
            ksort($protected, \SORT_STRING);
            $manifest['protected'] = $protected === [] ? new \stdClass() : $protected;
        }
        return json_encode($manifest, \JSON_PRETTY_PRINT | self::JSON_FLAGS) . "\n";
    }
}
