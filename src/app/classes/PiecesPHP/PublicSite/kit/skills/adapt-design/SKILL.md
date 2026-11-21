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

## 2. Reglas que no se negocian

1. **Es una refactorización, no un rediseño.** El sitio adaptado se ve y se lee exactamente igual que el original. Si
   al terminar algo se ve distinto, la adaptación está mal.
2. **Todo el sitio.** Cada página del original tiene la suya en `site/`, con el mismo nombre. No se adaptan «algunas de
   ejemplo» ni se crean páginas que no existían.
3. **Mover, no reescribir.** El marcado se copia tal cual (clases, atributos, texto, orden). Solo se tocan las rutas,
   los bloques comunes (que pasan a `<pcs-part>`) y los datos del sistema (que pasan a marcas).
4. **No inventar.** Ningún texto, cifra, imagen, clase, estilo, página o archivo que no esté en el original.
5. **No mejorar.** Accesibilidad, CSS, erratas: no en esta etapa. Se anota como pendiente para el equipo.
6. **Sin archivos extra.** Nada de README, resúmenes ni copias de seguridad dentro de la carpeta.

## 3. El procedimiento

1. **El mapa, antes de tocar nada.** Escríbelo primero, en tu respuesta. Si trabajas con una persona delante, enséñaselo
   y espera su visto bueno; si te lanzaron como subagente, no esperes: escríbelo y sigue.
   - **Origen**: de qué herramienta viene y qué trae (jQuery, `webflow.js`, atributos propios por página como
     `data-wf-page`). Eso va a `site/assets/`, intacto y en el mismo orden.
   - **Páginas**: cada `.html` del original y su página en `site/`.
   - **Partes**: los bloques idénticos en todas las páginas (cabecera, menú, pie), comparados sin contar espacios ni
     saltos de línea. Un bloque «casi» igual sigue siendo parte.
   - **Datos del sistema**: por cada uno, qué marca, en qué páginas y con qué campos. Es lista lo que un gestor de
     contenidos generaría: **tres o más hermanos con la misma estructura** (noticias, testimonios, vacantes, productos),
     aunque en el prototipo sean idénticos. **No** lo es el contenido único (un titular, una sección editorial, las
     cifras de la portada), los menús ni los carruseles de logos: eso se queda en la página.
2. **Estáticos tal cual.** Copia CSS, JS, imágenes y fuentes a `site/assets/`, conservando su estructura, y corrige las rutas
   de las páginas. No reescribas el CSS ni el JS del diseñador: si funcionaba, sigue funcionando.
3. **El lector.** Cada página lleva `<script src=".pcs/pcs.js"></script>` en su `<head>`, sin `async` ni `defer` y
   antes de cualquier otro `<script>`: así, en la vista previa, las partes están pintadas cuando corre el JavaScript del
   diseño (menús, carruseles). `CHECK.html` avisa si no está así.
4. **Partes.** Lo que se repite pasa a `site/parts/<nombre>.html`, y en cada página se sustituye por
   `<pcs-part name="<nombre>"></pcs-part>`. Una parte puede contener marcas y otras partes.
5. **Marcas, solo donde hay dato del sistema** (nombres y atributos exactos):

   | Si en el diseño hay… | Pon |
   | :-- | :-- |
   | un enlace a otra página del sitio | `<pcs-link page="contact">texto</pcs-link>` (el nombre del archivo de la página en `site/`, sin `.html`); `anchor="seccion"` para ir a una sección; `current-class="w--current"` (la clase que el original usa para la página activa) en los enlaces del menú |
   | título, descripción y vista previa para redes | `<pcs-meta></pcs-meta>` en el `<head>`; `structured-data="off"` si el original trae su propio JSON-LD |
   | el logo u otra imagen de la marca | `<pcs-image brand="logo" width="…" height="…"></pcs-image>`, con las dimensiones y el `loading` del original (en el sitio real solo pasan `class`, `alt`, `width`, `height`, `loading`, `decoding` y `sizes`) |
   | un texto que se traduce | `<pcs-text key="Texto en español">Texto en español</pcs-text>` |
   | una lista de cosas del sistema | `<pcs-list resource="publications" count="3"><template>…{{title}}…</template></pcs-list>` |
   | un formulario que el sistema recibe | `<pcs-form action="contact">…</pcs-form>`, conservando los `name` del original (los campos que la acción no espera llegan igual al correo) |

   En una lista, la `<template>` es UN elemento repetido del diseño, con `{{campo}}` donde va cada dato. Los campos
   disponibles de cada recurso están en `site/data/` (los genera el proyecto desde su contrato): no inventes campos.
6. **Lo que NO lleva marca**: el diseño. Colores, tipografías, animaciones, rejillas, textos que no se traducen, imágenes
   decorativas. Cuantas menos marcas, más libre queda el diseñador.
7. **Comprueba**: abre `CHECK.html`, elige la carpeta y corrige hasta que no quede ningún aviso. Abre `site/index.html` y
   compara página a página con el diseño original, en escritorio y en móvil.
8. **Importa en el proyecto**: `bin/cli public-site-import folder=<carpeta> site=<nombre>`. Si falla, no toca nada y dice
   por qué. Mira cada página servida por el proyecto contra la vista previa.

## 4. Terminado cuando

- Hay una página en `site/` por cada página del original, y ningún archivo que esta skill no pida.
- `CHECK.html` no da ningún aviso.
- **Fidelidad**: fuera de las marcas, el texto y el marcado de cada página son los del original (sin contar espacios,
  saltos de línea ni rutas), y abierta con doble clic se ve igual en escritorio y en móvil.
- Ninguna ruta local (`src`, `href`, `srcset`, `url()`) está rota.
- Lo que no encajó está anotado para el equipo, con la página y el motivo.

Nota sobre las listas: en la vista previa salen los datos de ejemplo del proyecto (`site/data/`, que genera el
proyecto), no las tarjetas originales del diseño. Al adaptar, la primera unidad del original es la `<template>`; las
demás desaparecen.

## 5. Lo que no se hace nunca

- **Reimplementar lo que el framework ya hace**: el menú con permisos e idioma, los metadatos, las imágenes de marca,
  los formularios con su protección. Para eso están las marcas.
- **PHP en el HTML**: el proyecto lo neutraliza y lo rechaza.
- **Shadow DOM, frameworks de JavaScript o dependencias nuevas** para que funcione la vista previa.
- **Cambiar nombres de marcas o atributos**: el juego es cerrado y lo valida el comprobador.

## 6. Cuando algo no encaja

- **Un dato que ninguna marca cubre** (un recurso que el sistema no tiene): no lo simules con HTML fijo. Anótalo para
  quien programa el proyecto: hace falta un contrato nuevo, y es el único trabajo de programación que pide un diseño.
- **Una página que depende de JavaScript del diseñador para pintar contenido del sistema**: deja que el JS pinte lo
  visual y que el dato venga de una marca. Si no se puede, anótalo y no lo fuerces.
- **Algo que esta skill no decide**: la opción que menos cambie el resultado visible, y se anota.
- **Dudas sobre una marca**: la guía (`GUIDE.html`) manda.
