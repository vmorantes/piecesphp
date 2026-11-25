# AGENTS.md — carpeta de diseño de un sitio PiecesPHP

Esta carpeta es el sitio público de un proyecto, en el formato que PiecesPHP sirve tal cual: lo que cambies se ve igual
en el sitio real. Antes de trabajar, lee `DESIGN.md` (las reglas del diseño, si existe) y `PROJECT.md` (las reglas del
proyecto), y después la skill `designer` (`.agents/skills/designer/SKILL.md`, también copiada
en `.claude/skills/designer/`) y la guía (`GUIDE.html`). En Cursor, las reglas de `.cursor/rules/` resumen lo mismo.

## Dónde está cada cosa

- `site/`: el sitio. `site/index.html` es la portada; cada página, un `.html` en la raíz de `site/`.
- `site/parts/`: lo que se repite (menú, cabecera, pie). `site/assets/`: CSS, JS, imágenes y fuentes.
- `site/data/`: los datos de ejemplo de la vista previa (los genera el proyecto: no se tocan). `site/.pcs/`: el motor de
  la vista previa (no se toca). `site/parts/parts.js`: el paquete de partes (y la lista de recursos) que lee la vista previa con
  doble clic; lo rehace `CHECK.html` («Actualizar el paquete») al cambiar una parte.
- `CHECK.html`: el comprobador. `GUIDE.html`: la guía. `pcs.json`: el manifiesto (no se toca).
- `DESIGN.md`: las reglas del diseño, del diseñador (se cambian como el resto del diseño). `PROJECT.md`: las reglas del
  proyecto, con lo que queda pendiente (no se toca).

## Las normas

- El diseño es libre: maquetación, CSS, JS visual, imágenes, fuentes, páginas nuevas.
- Las marcas `<pcs-…>` se respetan: no se renombran, no se inventan, y una lista solo usa los `{{campo}}` de
  `site/data/`. En un `<pcs-form>`, los `name` de los campos no cambian.
- El contenido del sistema (listas, textos traducibles, metadatos) va siempre por marca, nunca escrito a mano.
- Nunca: PHP, Shadow DOM, dependencias nuevas para la vista previa, ni cambios en `site/.pcs/`, `pcs.json`,
  `PROJECT.md`, `AGENTS.md`, `CLAUDE.md`, `.agents/`, `.claude/` o `.cursor/`.
- Dentro de la `<template>` de una lista no van otras marcas: en el sitio real no se resuelven.
- Cada página lleva `<script src=".pcs/pcs.js"></script>` en su `<head>`, antes de cualquier otro script y sin
  `async` ni `defer`: no se mueve.
- Nada se da por terminado sin `CHECK.html` sin avisos (salvo los que `PROJECT.md` anota como pendientes) y cada página
  revisada en escritorio y en móvil.

## Delegación

Trabaja con subagentes cuando la herramienta lo permita (están en `.claude/agents/`):

| Para | Lanza | Cuándo |
| :-- | :-- | :-- |
| Saber dónde está algo (qué página usa una parte, qué CSS afecta a un elemento, qué campos tiene una lista) | `design-explorer` | Antes de tocar más de un archivo |
| Adaptar un diseño NUEVO que aún no está en el formato | `design-adapter` | Una sola vez por diseño |
| Revisar antes de dar algo por terminado | `design-reviewer` | Siempre, al final de cada cambio |

Sin subagentes (por ejemplo en Cursor), sigue los mismos pasos tú: localiza, cambia, revisa con la lista de
`design-reviewer` y pasa `CHECK.html`.
