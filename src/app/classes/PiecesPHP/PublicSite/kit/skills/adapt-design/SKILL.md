---
name: adapt-design
description: Adapta UNA SOLA VEZ un sitio web hecho con tecnologías de navegador (HTML, CSS y JavaScript; un export de Webflow, una plantilla comprada, un prototipo a mano) al formato de sitio público de PiecesPHP, con partes y marcas `<pcs-…>`, para que el diseñador siga trabajando sobre él sin programar y PiecesPHP lo sirva tal cual. Úsala cuando llegue un diseño nuevo para la zona pública de un proyecto PiecesPHP, o cuando se diga «adaptar el diseño», «pasar la plantilla al formato», «preparar el sitio del diseñador».
---

# Adaptar un diseño al formato de sitio público de PiecesPHP

La especificación del formato es el ADR 0084 del framework. Esta skill es el procedimiento. El resultado es una carpeta
que el diseñador abre con doble clic y que el proyecto importa con `bin/cli public-site-import`.

## 1. Lo que entra y lo que sale

- **Entra** cualquier sitio que funcione en un navegador: páginas `.html`, su CSS, su JavaScript, imágenes y fuentes. Si
  la herramienta del diseñador compila (React, Vue, un generador estático), se adapta su **salida estática**, no su
  código fuente.
- **Sale** la carpeta del sitio:

  ```
  site/            el sitio, como cualquier web
    index.html     la portada
    <página>.html  cada página en la raíz de site/ (MPA: enlaces normales)
    parts/         lo que se repite (menu.html, header.html, footer.html…)
    assets/        el CSS, el JS, las imágenes y las fuentes del diseñador, tal cual
    data/          datos de ejemplo (.js) para la vista previa
    .pcs/          el lector de la vista previa (NO se toca)
  CHECK.html  GUIDE.html  pcs.json  AGENTS.md  CLAUDE.md  .agents/  .claude/  .cursor/
  ```

  Lo más rápido para empezar es pedirle al proyecto una carpeta vacía con `bin/cli public-site-export` y llenarla.

## 2. El procedimiento

1. **Inventario.** Lista las páginas, y en cada una lo que se repite (cabecera, menú, pie, banners comunes) y lo que es
   dato del sistema (listas de noticias o publicaciones, título y descripción para buscadores, logo, textos que hay que
   traducir, formularios). Escríbelo antes de tocar nada.
2. **Estáticos tal cual.** Copia CSS, JS, imágenes y fuentes a `site/assets/`, conservando su estructura, y corrige las rutas
   de las páginas. No reescribas el CSS ni el JS del diseñador: si funcionaba, sigue funcionando.
3. **Partes.** Lo que se repite pasa a `site/parts/<nombre>.html`, y en cada página se sustituye por
   `<pcs-part name="<nombre>"></pcs-part>`. Una parte puede contener marcas y otras partes.
4. **Marcas, solo donde hay dato del sistema** (nombres y atributos exactos):

   | Si en el diseño hay… | Pon |
   | :-- | :-- |
   | un enlace a otra página del sitio | `<pcs-link page="contact">texto</pcs-link>` (el nombre del archivo de la página en `site/`, sin `.html`) |
   | título, descripción y vista previa para redes | `<pcs-meta></pcs-meta>` en el `<head>` |
   | el logo u otra imagen de la marca | `<pcs-image brand="logo"></pcs-image>` |
   | un texto que se traduce | `<pcs-text key="Texto en español">Texto en español</pcs-text>` |
   | una lista de cosas del sistema | `<pcs-list resource="publications" count="3"><template>…{{title}}…</template></pcs-list>` |
   | un formulario que el sistema recibe | `<pcs-form action="contact">…</pcs-form>` |

   En una lista, la `<template>` es UN elemento repetido del diseño, con `{{campo}}` donde va cada dato. Los campos
   disponibles de cada recurso están en `site/data/` (los genera el proyecto desde su contrato): no inventes campos.
5. **Lo que NO lleva marca**: el diseño. Colores, tipografías, animaciones, rejillas, textos que no se traducen, imágenes
   decorativas. Cuantas menos marcas, más libre queda el diseñador.
6. **Comprueba**: abre `CHECK.html`, elige la carpeta y corrige hasta que no quede ningún aviso. Abre `site/index.html` y
   compara página a página con el diseño original, en escritorio y en móvil.
7. **Importa en el proyecto**: `bin/cli public-site-import folder=<carpeta> site=<nombre>`. Si falla, no toca nada y dice
   por qué. Mira cada página servida por el proyecto contra la vista previa.

## 3. Lo que no se hace nunca

- **Reimplementar lo que el framework ya hace**: el menú con permisos e idioma, los metadatos, las imágenes de marca,
  los formularios con su protección. Para eso están las marcas.
- **PHP en el HTML**: el proyecto lo neutraliza y lo rechaza.
- **Shadow DOM, frameworks de JavaScript o dependencias nuevas** para que funcione la vista previa.
- **Cambiar nombres de marcas o atributos**: el juego es cerrado y lo valida el comprobador.

## 4. Cuando algo no encaja

- **Un dato que ninguna marca cubre** (un recurso que el sistema no tiene): no lo simules con HTML fijo. Anótalo para
  quien programa el proyecto: hace falta un contrato nuevo, y es el único trabajo de programación que pide un diseño.
- **Una página que depende de JavaScript del diseñador para pintar contenido del sistema**: deja que el JS pinte lo
  visual y que el dato venga de una marca. Si no se puede, anótalo y no lo fuerces.
- **Dudas sobre una marca**: la guía (`GUIDE.html`) y el ADR 0084 mandan.
