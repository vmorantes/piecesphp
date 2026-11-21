# Adaptar una plantilla, de principio a fin

Este manual cuenta el recorrido completo: llega un diseño web de un cliente o de un equipo de diseño, se adapta **una
sola vez**, PiecesPHP lo sirve como sitio público del proyecto, y el equipo de diseño sigue cambiándolo sin programar.
La referencia técnica (las marcas, las órdenes, cómo ampliarlo) está en [El sitio público por marcas](sitio-publico.md).

## 1. Qué entra y qué sale

- **Entra** cualquier sitio que funcione en un navegador: páginas `.html`, su CSS, su JavaScript, imágenes y fuentes.
  Un export de Webflow, una plantilla comprada o un prototipo hecho a mano valen igual. Si la herramienta del diseñador
  compila (React, Vue, un generador estático), se adapta su **salida estática**, no su código fuente.
- **Sale** una carpeta con el formato de PiecesPHP: el sitio tal cual en `site/`, con unas pocas marcas `<pcs-…>` donde
  van datos del sistema, más un comprobador, una guía y las instrucciones para el asistente de IA del diseñador.
- **Lo que no entra nunca**: código de servidor del diseñador. Un `.php` (o cualquier guion de servidor) se rechaza al
  importar. Si el diseño traía un formulario con su propio PHP, ese formulario pasa a `<pcs-form>` y lo recibe el
  framework. Revisa además que ese PHP no lleve credenciales escritas: si las lleva, avisa a quien corresponda para que
  se cambien.

## 2. El recorrido, en ocho pasos

| Paso | Quién | Qué |
| :-- | :-- | :-- |
| 1 | Desarrollo | Pide la carpeta de partida al proyecto |
| 2 | Desarrollo (con su asistente) | Adapta el diseño una vez |
| 3 | Desarrollo | Comprueba con `CHECK.html` |
| 4 | Desarrollo | Importa al proyecto y activa el sitio |
| 5 | Desarrollo | Revisa el sitio servido contra el original |
| 6 | Desarrollo | Exporta la carpeta para el equipo de diseño |
| 7 | Diseño | Cambia lo que quiera, comprueba y devuelve la carpeta |
| 8 | Desarrollo | Importa lo devuelto (y vuelve atrás si hiciera falta) |

### 2.1 La carpeta de partida

```bash
bin/cli public-site-export site=<nombre> destination=/ruta/de/trabajo
```

El nombre del sitio, en minúsculas y sin espacios (por ejemplo `clinica`). Deja la carpeta con su estructura, el
comprobador, la guía, los datos de ejemplo de cada recurso del proyecto y las instrucciones del asistente.

### 2.2 Adaptar

La adaptación es **una refactorización, no un rediseño**: el sitio adaptado se ve y se lee exactamente igual que el
original. Las reglas, en corto:

1. **Todo el sitio**: cada página del original tiene la suya en `site/`, con el mismo nombre; la portada es
   `site/index.html`.
2. **Mover, no reescribir**: el marcado se copia tal cual. El CSS, el JS, las imágenes y las fuentes van a
   `site/assets/` intactos; solo se corrigen las rutas.
3. **Lo repetido** (cabecera, menú, pie) pasa a `site/parts/<nombre>.html`, y en cada página queda
   `<pcs-part name="<nombre>">`.
4. **Marcas solo donde hay dato del sistema**:

   | Si en el diseño hay… | Va |
   | :-- | :-- |
   | un enlace a otra página del sitio (o a una sección suya) | `<pcs-link page="contact">texto</pcs-link>` (`anchor="seccion"` para la sección; `current-class="activo"` para marcar la página actual en el menú) |
   | título, descripción y vista previa para redes | `<pcs-meta></pcs-meta>` en el `<head>` (`structured-data="off"` si el diseño trae su propio JSON-LD) |
   | el logo u otra imagen de la marca | `<pcs-image brand="logo" width="…" height="…"></pcs-image>` (las dimensiones del original) |
   | un texto que se traduce | `<pcs-text key="Texto">Texto</pcs-text>` |
   | una lista (tres o más elementos con la misma estructura: noticias, testimonios…) | `<pcs-list resource="publications" count="3"><template>…{{title}}…</template></pcs-list>` |
   | un formulario que recibe el sistema | `<pcs-form action="contact">…</pcs-form>` |

5. **No inventar ni mejorar**: ni textos, ni clases, ni páginas que no estaban; las mejoras se anotan para después.
6. **El lector de la vista previa**: cada página lleva `<script src=".pcs/pcs.js"></script>` en su `<head>`, sin
   `async` ni `defer` y **antes de cualquier otro `<script>`**, para que las partes estén pintadas cuando corra el
   JavaScript del diseño. El comprobador avisa si no está así.

**Con un asistente de IA**, la carpeta trae lo necesario: la skill `adapt-design` con el procedimiento completo (primero
un mapa de páginas, partes y datos; después la ejecución; y cuándo está terminado) y, en las herramientas que admiten
subagentes, el adaptador `design-adapter` y el revisor `design-reviewer`. Abre el asistente en la carpeta de trabajo, no
dentro de `site/`.

### 2.3 Comprobar

Abre `CHECK.html` con doble clic, elige la carpeta y corrige hasta que no quede ningún aviso. Cada aviso dice el
archivo, la línea y qué hacer. Si cambiaste una parte, pulsa «Actualizar el paquete»: la vista previa de doble clic lee
las partes de un paquete (`site/parts/parts.js`, que también lleva la lista de recursos), porque el navegador no deja
leer archivos sueltos.

Abre `site/index.html` y compara página a página con el original, en escritorio y en móvil.

### 2.4 Importar y activar

```bash
bin/cli public-site-import folder=/ruta/de/trabajo site=<nombre> check=yes   # solo comprueba
bin/cli public-site-import folder=/ruta/de/trabajo site=<nombre>             # comprueba y coloca
```

La importación usa las mismas reglas que `CHECK.html`. Si algo falla, **no toca nada** y dice qué y dónde. Si va bien,
coloca las páginas y las partes en `app/view/<nombre>/` y los estáticos en `statics/<nombre>/`, y guarda lo anterior en
`app/cache/public-site/backups/`.

Para que el sitio se sirva, en `src/app/config/config.php`:

```php
$config['public_site'] = '<nombre>';
```

Desde ese momento, su `index.html` es la portada del proyecto y su `contact.html`, la página de contacto; las demás
páginas se sirven en `/<página>/`. Una página que el sitio no trae sigue siendo la del framework.

### 2.5 Revisar lo servido

Recorre cada página servida por el proyecto contra la vista previa: las listas con datos reales, los enlaces, los
formularios (que llegan a su destino) y las páginas interiores (que cargan su CSS y su JavaScript). La primera vez que
se pide una página, PiecesPHP la compila y la guarda en caché; un cambio en la carpeta se nota al volver a importar.

### 2.6 Entregar al equipo de diseño

```bash
bin/cli public-site-export site=<nombre> destination=/ruta/para/diseño zip=yes
```

Deja la carpeta y, junto a ella, `<nombre>-<fecha>.zip`: es lo que se envía. Lleva su sitio, el comprobador, la guía
(`GUIDE.html`) y las instrucciones para su asistente, también para Cursor: un `AGENTS.md`, dos skills, tres subagentes,
los permisos que impiden tocar lo que no se toca y dos reglas de Cursor. **No lleva nada más**: la orden lo comprueba
con una lista exacta y falla si encuentra otro archivo de agente.

Diles que abran la carpeta (la de arriba, no `site/`) en su herramienta, y que empiecen por `GUIDE.html`.

### 2.7 El equipo de diseño trabaja

Cambian con libertad el diseño de `site/`: maquetación, CSS, JavaScript visual, imágenes, fuentes, páginas nuevas. Lo
único que respetan son las marcas, `site/.pcs/` y `site/data/`. Antes de devolver la carpeta, `CHECK.html` sin avisos.

### 2.8 Recibir lo devuelto

```bash
bin/cli public-site-import folder=/ruta/devuelta site=<nombre>
```

Si algo está mal, no cambia nada y el sitio sigue sirviendo la versión anterior. **Para volver atrás** tras una
importación buena que no gusta, mueve de vuelta las carpetas del respaldo (`app/cache/public-site/backups/<nombre>-<fecha>/`)
a `app/view/<nombre>/` y `statics/<nombre>/`. No hay orden para eso todavía.

## 3. Cuando el diseño pide un dato que el sistema no tiene

Es el único caso que pide programar. Un recurso nuevo para `<pcs-list>` se declara con su contrato (nombre, campos con
tipo y ejemplo, y de dónde salen los datos reales) en el `routes()` de un módulo del proyecto; una acción nueva para
`<pcs-form>`, igual. Después, una exportación nueva lleva sus datos de ejemplo al equipo de diseño. Cómo se registran:
[El sitio público por marcas](sitio-publico.md), «Ampliarlo desde un proyecto».

## 4. Problemas frecuentes

- **`CHECK.html` dice que se cambió una pieza que no se toca**: se editó `site/.pcs/`, `pcs.json` o el andamiaje del
  asistente. Vuelve a pedir la carpeta o restaura ese archivo desde el original.
- **Una parte cambiada no se ve en la vista previa**: falta «Actualizar el paquete» en `CHECK.html`.
- **La importación rechaza un archivo**: es un guion de servidor (`.php`, `.phtml`, `.sh`…), un `.htaccess` o un enlace
  simbólico. Se quita de la carpeta: nada de eso es diseño.
- **Una página choca con una dirección del sistema** (`admin`, `core`, `statics`…): renómbrala.
- **Dentro de una lista no se resuelven otras marcas**: lo que va dentro de su `<template>` es HTML y `{{campo}}`.
