---
name: adapt-design
description: Adapta un sitio web hecho con tecnologías de navegador (HTML, CSS y JavaScript; un export de Webflow, una plantilla comprada, un prototipo a mano) al formato de sitio público de PiecesPHP, con partes y marcas `<pcs-…>`, lo antes posible y tocando lo mínimo, para que el diseñador siga trabajando sobre el adaptado y el proyecto lo integre después. Úsala cuando llegue un diseño nuevo o una entrega nueva del diseñador para la zona pública de un proyecto PiecesPHP, o cuando se diga «adaptar el diseño», «pasar la plantilla al formato», «preparar el sitio del diseñador».
---

# Adaptar un diseño al formato de sitio público de PiecesPHP

La especificación del formato está en la documentación de PiecesPHP («El sitio público por marcas»). Esta skill es el
procedimiento de adaptar.

## 0. Un adaptado y dos sitios que funcionan

| | Versión del diseñador (el kit) | Versión integrada (el proyecto) |
| :-- | :-- | :-- |
| Dónde | la carpeta adaptada (en el proyecto, `files/design/adaptado/`), se abre con doble clic | servida por el proyecto, tras importar |
| Datos | de ejemplo, que simulan al detalle lo real | los reales del proyecto |
| Lo que funciona | TODO: enlaces, menús, formularios (sin enviar), listas con «cargar más» y búsqueda | TODO, de verdad |
| Quién lo hace | esta skill | la integración del proyecto |
| Terminada cuando | su censo funcional no tiene ningún «falso» sin decisión del responsable del proyecto | su censo sobre el sitio servido, igual |

**Manda el reloj.** Al recibir un diseño, lo primero es la versión del diseñador funcionando, y se le devuelve enseguida
con el aviso de que siga trabajando desde el adaptado, no desde su versión vieja. Del diseño se toca **solo lo
imprescindible** para dos cosas: que esté adaptado (las partes y las marcas) y que funcione (lo que finge, regla 5).
Nada más: ni reordenar, ni renombrar, ni limpiar, ni partir archivos.

## 1. Lo que entra y lo que sale

- **Entra** cualquier sitio que funcione en un navegador: páginas `.html`, su CSS, su JavaScript, imágenes y fuentes. Si
  la herramienta del diseñador compila (React, Vue, un generador estático), se adapta su **salida estática**, no su
  código fuente.
- **Se parte siempre de una carpeta exportada por el proyecto**: trae `site/.pcs/` (el lector) y `pcs.json`, que no se
  escriben a mano. Fuera de un proyecto, pídesela a quien lo lleva.
- **Sale** la carpeta del sitio:

  ```
  site/            el sitio, como cualquier web
    index.html     la portada
    <página>.html  cada página en la raíz de site/ (MPA: se pasa de una página a otra con `<pcs-link>`)
    parts/         lo que se repite (menu.html, header.html, footer.html…)
    assets/        el CSS, el JS, las imágenes y las fuentes del diseñador, tal cual
    data/          datos de ejemplo (.js) para la vista previa (los genera el proyecto)
    .pcs/          el lector de la vista previa (NO se toca)
  pcs.json         el manifiesto
  DESIGN.md        las reglas del diseño, si el diseñador las trae
  ```

  En el paquete que recibe el diseñador van además `CHECK.html`, `GUIDE.html`, `PROJECT.md` (las reglas del proyecto, que
  ahí no se editan) y las instrucciones de su asistente.

- **En un proyecto**, los tres lugares:
  - `files/design/base/`: el export del diseñador, tal cual llegó. No se toca; cada entrega nueva es un commit.
  - `files/design/adaptado/`: el sitio adaptado (`site/`, `pcs.json` y `DESIGN.md`), versionado. La carpeta de partida
    la da `bin/cli public-site-export site=<nombre> destination=files/design/adaptado`: con un sitio que no está
    importado sale del sitio de ejemplo del kit, y se sustituye por el diseño.
  - `src/app/view/<sitio>/` y `src/statics/<sitio>/`: los genera `bin/cli public-site-import`. Nunca se editan a mano.
  - `files/design/PROJECT.md`: las reglas del proyecto para el diseñador, con dos secciones, «Censo funcional» y
    «Pendientes conocidos» (esta la exige la exportación del paquete).

## 2. Reglas que no se negocian

1. **Lo que se ve, igual.** El sitio adaptado se ve y se lee exactamente como el original. Si al terminar algo se ve
   distinto, la adaptación está mal.
2. **Todo el sitio.** Cada página del original tiene la suya en `site/`, con el mismo nombre. No se adaptan «algunas de
   ejemplo» ni se crean páginas que no existían.
3. **Mover, no reescribir.** El marcado se copia tal cual (clases, atributos, texto, orden). Se tocan las rutas, los
   bloques comunes (que pasan a `<pcs-part>`), los datos del sistema (que pasan a marcas) y lo que finge (regla 5).
4. **No inventar.** Ningún texto, cifra, imagen, página o destino que no esté en el original. **Un destino de enlace que
   no se sabe se deja como está y se anota** para «Pendientes conocidos»: así deja de ser «falso» y pasa a «pendiente»,
   con su pregunta.
5. **Lo que finge se corrige, con la misma apariencia.** Un campo que es un dibujo, un botón que es un `<div>`, un
   `href="#"`, una imagen sin `alt`, un formulario que no envía: se sustituye por el elemento verdadero (`<input>`,
   `<button>`, `<a>` con su destino, `alt`, `<pcs-form>`). Lo que lleva a otra página o sección es `<a>`; lo que hace
   algo en la misma página es `<button type="button">`. Si no se sabe qué hace o adónde lleva, el elemento verdadero
   con la misma apariencia y, sin inventar, su destino o su acción a «Pendientes conocidos» (regla 4). Si hace falta CSS para que se vea igual, va en
   `site/assets/css/adaptation-fixes.css`, nunca en el CSS del diseñador. Lo que es de gusto no se toca. Cada corrección
   se anota para el diseñador.
6. **Sin archivos extra.** Nada de README, resúmenes ni copias de seguridad dentro de la carpeta. Lo que haya que anotar
   va en tu respuesta; el proyecto lo lleva a su `PROJECT.md` (en el paquete del diseñador, `PROJECT.md` no se edita).
7. **El logo es una imagen del diseño**: se queda como `<img>`. Pasa a `<pcs-image brand="logo">` solo si el proyecto lo
   decide y lo dice en `PROJECT.md`.

## 3. El procedimiento

1. **Antes de tocar nada**, lee `DESIGN.md` y `PROJECT.md` si existen (una entrega nueva puede no traerlos). Los
   dominios del diseñador (pruebas, staging, Webflow) son los de las direcciones absolutas que apuntan a archivos de la
   entrega; si `PROJECT.md` los nombra, mejor. Con una persona delante, pregunta lo que no esté claro; si te lanzaron
   como subagente, no esperes: anótalo en tu respuesta y sigue.
2. **El mapa.** Escríbelo primero, en tu respuesta. Si trabajas con una persona delante, enséñaselo y espera su visto
   bueno; si te lanzaron como subagente, no esperes: escríbelo y sigue.
   - **Origen**: de qué herramienta viene y qué trae (jQuery, `webflow.js`, atributos propios por página como
     `data-wf-page`). Eso va a `site/assets/`, intacto y en el mismo orden.
   - **Páginas**: cada `.html` del original y su página en `site/`.
   - **Partes**: los bloques idénticos en todas las páginas (cabecera, menú, pie, un modal), comparados sin contar
     espacios ni saltos de línea. Un bloque «casi» igual sigue siendo parte. Un bloque copiado en tres páginas o más es
     parte: `CHECK.html` avisa si se queda copiado.
   - **Datos del sistema**: por cada uno, qué marca, en qué páginas y con qué campos. Es lista lo que un gestor de
     contenidos generaría: **tres o más hermanos con la misma estructura** (noticias, testimonios, vacantes, productos),
     aunque en el prototipo sean idénticos. **No** lo es el contenido único (un titular, una sección editorial, las
     cifras de la portada), los menús ni los carruseles de logos: eso se queda en la página.
   - **Lo que finge** (regla 5) y **los recursos externos**: cada dirección absoluta y si es de un servicio legítimo
     (fuentes, vídeo, mapas) o un archivo de la entrega servido desde el dominio del diseñador.
3. **Estáticos tal cual.** Copia CSS, JS, imágenes y fuentes a `site/assets/`, conservando su estructura, y corrige las
   rutas de las páginas. No reescribas el CSS ni el JS del diseñador: si funcionaba, sigue funcionando. **Una dirección
   absoluta a un archivo que viene en la entrega pasa a ruta local**: relativa en HTML y CSS; en JS, con la ayuda de
   rutas (§4). Si el archivo no viene en la entrega, no lo descargues ni lo sustituyas: déjalo, anótalo como pendiente
   y dilo en tu respuesta.
4. **El lector.** Cada página lleva `<script src=".pcs/pcs.js"></script>` en su `<head>`, sin `async` ni `defer` y
   antes de cualquier otro `<script>`: así, en la vista previa, las partes están pintadas cuando corre el JavaScript del
   diseño (menús, carruseles). `CHECK.html` avisa si no está así.
5. **Partes.** Lo que se repite pasa a `site/parts/<nombre>.html`, y en cada página se sustituye por
   `<pcs-part name="<nombre>"></pcs-part>`. Una parte puede contener marcas y otras partes.
6. **Marcas, solo donde hay dato del sistema** (nombres y atributos exactos):

   | Si en el diseño hay… | Pon |
   | :-- | :-- |
   | un enlace a otra página del sitio | `<pcs-link page="contact">texto</pcs-link>` (el nombre del archivo de la página en `site/`, sin `.html`); `anchor="seccion"` para ir a una sección; `current-class="w--current"` (la clase que el original usa para la página activa) en los enlaces del menú |
   | título, descripción y vista previa para redes | `<pcs-meta></pcs-meta>` en el `<head>` (un `<title>` no la exige: al servir, el proyecto pone sus metadatos igual); `structured-data="off"` si el original trae su propio JSON-LD |
   | una imagen de la marca **que el proyecto haya decidido** (regla 7) | `<pcs-image brand="logo" width="…" height="…"></pcs-image>`, con las dimensiones y el `loading` del original (en el sitio real solo pasan `class`, `alt`, `width`, `height`, `loading`, `decoding` y `sizes`) |
   | un texto que se traduce (solo si el sitio tiene más de un idioma; cuáles, lo dice `PROJECT.md`) | `<pcs-text key="Texto en español">Texto en español</pcs-text>` |
   | una lista de cosas del sistema | `<pcs-list resource="publications" count="3"><template>…{{title}}…</template></pcs-list>` |
   | un formulario que el sistema recibe | `<pcs-form action="contact">…</pcs-form>`, conservando los `name` del original (los campos que la acción no espera llegan igual al correo) |

   En una lista, la `<template>` es UN elemento repetido del diseño, con `{{campo}}` donde va cada dato, y dentro de ella
   no van otras marcas. Los campos disponibles de cada recurso están en `site/data/` (los genera el proyecto desde su
   contrato): no inventes campos.
7. **Las listas funcionan en la vista previa**, sin marcas nuevas:
   - `count` es el máximo de filas que pinta la lista. Si el diseño trae «cargar más», paginación o búsqueda, el
     JavaScript del diseño (en `site.<área>.js`, §4) los hace sobre las filas ya pintadas: igual en la vista previa y
     servido. Más allá de `count`, lo resuelve el proyecto en su integración.
   - Para ver la lista vacía, abre la página con `?pcs-vacio=<recurso>` (varios, separados por coma; `?pcs-vacio` sin
     valor, todas) y comprueba que el diseño la resuelve.
   - Si un recurso aún no existe en el proyecto, el proyecto registra un contrato estimado (con sus campos y al menos
     `count` ejemplos distintos, para que «cargar más» no repita): no lo simules con HTML fijo.
8. **Lo que NO lleva marca**: el diseño. Colores, tipografías, animaciones, rejillas, textos que no se traducen, imágenes
   decorativas, el logo (regla 7). Cuantas menos marcas, más libre queda el diseñador.
9. **Comprueba**: abre `CHECK.html`, elige la carpeta y corrige hasta que no quede ningún aviso: también avisa de lo que
   finge, de los recursos de dominios desconocidos (y es un error una ruta local disfrazada, cuando el final de la ruta
   coincide con un archivo del paquete) y de los bloques copiados. En el proyecto, `files/design/adaptado/` no lleva
   `CHECK.html`: se exporta el paquete a una carpeta temporal fuera del proyecto y se abre el suyo. Un agente sin
   ventana no puede usar su selector de carpetas: en el proyecto, `bin/cli public-site-import folder=<carpeta>
   site=<nombre> check=yes` hace las comprobaciones de la importación sin colocar nada (marcas, partes, recursos ajenos y
   bloques copiados); lo que finge lo mira el motor de `CHECK.html`, `site/.pcs/check.js`, que también se puede llamar
   desde node (exporta su API); si no, queda para el censo del paso 10.
   Abre `site/index.html` y compara página a página con el diseño original, en escritorio y en móvil.
10. **El censo funcional, por un agente que NO hizo la adaptación**: cada elemento interactivo de cada página
    (enlace, botón, campo, formulario, lista, menú, vídeo) y cada recurso externo, con su estado (funciona / falso /
    pendiente) y su plan. En el proyecto va en «Censo funcional» de `files/design/PROJECT.md`, y lo que quede, en
    «Pendientes conocidos». Si te encargaron adaptar y censar a la vez, adapta y di en tu respuesta que el censo lo
    tiene que hacer otro agente: no firmes el tuyo.
11. **Devuelve el adaptado al diseñador** (en el proyecto: `bin/cli public-site-export from=files/design/adaptado
    destination=<carpeta fuera del proyecto> zip=yes`; si el sitio ya está importado con ese nombre, `from=` no vale:
    importa antes el adaptado y exporta con `site=<nombre>`), con el aviso de que siga trabajando desde ahí.
12. **Integrar, después y sin prisa**: los contratos reales, `bin/cli public-site-import folder=<carpeta> site=<nombre>`,
    activar el sitio, y el censo funcional repetido sobre el sitio servido, con cero «falso» sin decisión del responsable del proyecto, antes
    de darlo por listo para producción. «Todas las páginas responden» no es una verificación.

## 4. La ayuda de rutas en el JavaScript del diseño

Una ruta que el JavaScript del diseño construye tiene que valer en la vista previa (doble clic) y servida. Se calcula
desde el propio archivo, arriba del todo, de forma síncrona y nunca en un módulo:

```js
var ROOT = new URL('../', document.currentScript.src); // la carpeta de encima de la de este archivo
var logo = new URL('img/logo.png', ROOT).href;
```

El JavaScript en línea no tiene `currentScript.src`: deja la ruta en un `data-src` de un elemento (el proyecto lo
reescribe al servir) y léela con `new URL(el.getAttribute('data-src'), document.baseURI)`.

Los archivos: `site.js`, y `site.<área>.js` cuando crece (`site.noticias.js`, `site.contacto.js`), sin partir lo que ya
se entregó.

## 5. Terminado cuando

- Hay una página en `site/` por cada página del original, y ningún archivo que esta skill no pida.
- `CHECK.html` no da ningún aviso, salvo los anotados en «Pendientes conocidos»: un servicio externo legítimo que no
  está en su lista, o un destino o una acción que no se saben (regla 4).
- **Fidelidad**: fuera de las marcas y de lo que fingía, el texto y el marcado de cada página son los del original (sin
  contar espacios, saltos de línea ni rutas), y abierta con doble clic se ve igual en escritorio y en móvil.
- Ninguna ruta local (`src`, `href`, `srcset`, `url()`) está rota y ninguna apunta al dominio del diseñador.
- El censo funcional no tiene ningún «falso» sin decisión del responsable del proyecto, y lo que no encajó está en «Pendientes conocidos», con la página y el
  motivo.

Nota sobre las listas: en la vista previa salen los datos de ejemplo del proyecto (`site/data/`), no las tarjetas
originales del diseño. Al adaptar, la primera unidad del original es la `<template>`; las demás desaparecen.

## 6. Lo que no se hace nunca

- **Reimplementar lo que el framework ya hace**: el menú con permisos e idioma, los metadatos, los formularios con su
  protección. Para eso están las marcas.
- **PHP en el HTML**: el proyecto lo neutraliza y lo rechaza.
- **Shadow DOM, frameworks de JavaScript o dependencias nuevas** para que funcione la vista previa.
- **Cambiar nombres de marcas o atributos**: el juego es cerrado y lo valida el comprobador.
- **Responder «eso es del framework»**: lo que el sitio necesita lo construye la adaptación o la integración del
  proyecto; lo que le falte al framework se anota para el equipo y no se espera.

## 7. Cuando algo no encaja

- **Un dato que ninguna marca cubre** (un recurso que el sistema no tiene): no lo simules con HTML fijo. Anótalo para
  quien programa el proyecto: hace falta un contrato (estimado, mientras no exista el módulo).
- **Una página que depende de JavaScript del diseñador para pintar contenido del sistema**: deja que el JS pinte lo
  visual y que el dato venga de una marca. Si no se puede, anótalo y no lo fuerces.
- **Algo que esta skill no decide**: la opción que menos cambie el resultado visible, y se anota.
- **Dudas sobre una marca**: la guía (`GUIDE.html`) manda.
