# Las imágenes de Configuración

Cómo se suben, dónde se guardan y quién lee las imágenes de Configuración › Apariencia: los fondos del inicio de
sesión, el fondo de la ayuda para entrar, las imágenes de marca (favicons, logo, patrocinador, logo de los correos) y
la imagen para compartir (Open Graph). Lo que ve quien administra está en el manual del panel, «Configuración ›
Apariencia».

## La regla

**Lo de fábrica no se escribe ni se borra.** Las imágenes que trae el framework están versionadas en
`src/statics/images/` y `src/statics/login-and-recovery/images/login/`. Todo lo que se sube va a
`uploads/settings/<tipo>/`, dentro de `src/statics/` (`SettingsController::SETTINGS_UPLOADS_FOLDER`), que git ignora, y solo ahí se
borra: `SettingsController::deleteSettingsImage()` comprueba con `realpath` que el archivo esté dentro de esa carpeta.
Una opción que apunte a una carpeta de fábrica (lo subido antes de la 10.11.0) se sigue leyendo tal cual; al
sustituirla, el archivo viejo no se borra.

**Las de fábrica salen del logo de PiecesPHP** (desde la 10.42.0): `src/statics/images/framework/main_logo.svg` (el
rótulo) y `main_mark.svg` (la «P» sola). Las cuadradas (`logo.png`, `white-logo.png`, `loader-logo.png`, las favicons)
llevan la marca; las anchas (`navbar-logo.png`, `mailing-logo.png`), el rótulo. **El logo de fábrica se reconoce por su
contenido**, no por su ruta: `SettingsController::FACTORY_BRAND_IMAGES` guarda, por imagen, la lista de sha1 de fábrica
(el de hoy y los anteriores), y `isFactoryBrandImage()` la usa para que el de fábrica se invierta en modo oscuro sin
opción guardada. **Quien cambie una imagen de fábrica añade su sha1 nuevo a esa lista** y conserva los viejos.

**Las ocho PNG de fábrica se regeneran con `bin/imagenes-de-fabrica`** (en el repositorio de desarrollo; no viaja): las
renderiza con Chromium desde los dos SVG, con un 8 % de margen y fondo transparente, a su tamaño de hoy. La marca
(`main_mark.svg`) da `logo.png`, `loader-logo.png` y `white-logo.png` (esta, la versión de modo oscuro: el marino en blanco y el celeste igual; la instalada se dibujó aparte, y regenerarla da una equivalente, no idéntica, así que cambia su sha1) a 400 × 400,
`favicon.png` y `favicon-back.png` a 500 × 500, y la favicon de la documentación
(`source-docs/starlight/public/favicon.png`), a 500 × 500; el rótulo (`main_logo.svg`) da `navbar-logo.png` a 400 × 62
y `mailing-logo.png` a 500 × 172. Sin cambiar los SVG, regenerar no cambia ni un píxel. Tras regenerar con un SVG nuevo, el
sha1 de las de `FACTORY_BRAND_IMAGES` se añade a su lista. `bin/imagenes-de-fabrica --medir <svg>` lista la caja de cada
trazado, para separar la marca del rótulo.

## Del navegador a la base, en orden

1. **La tarjeta** (`panel/built-in/content/image-card.php`) lleva en sus `data-*` el ancho, el alto, el formato, la
   calidad, el color de fondo y adónde se envía.
2. **El editor** (`ContentImageEditor.js`) recorta con una proporción fija y entrega **el tamaño exacto** de la tarjeta
   en ese formato. Con `data-action`, envía el archivo y deja el editor abierto hasta que el servidor responde; sin él,
   se lo entrega a la pantalla (`onApply()`), que lo envía con su formulario. Al terminar emite `image-card-replaced`
   (no burbujea).
3. **El servidor** (`SettingsController::storeSettingsUpload()`) valida el tipo, **saca la extensión del contenido**
   (no del nombre que manda el navegador), recomprime (JPG y WEBP a calidad 70, PNG sin pérdida) y lo escribe con un
   nombre temporal. No redimensiona: el tamaño lo fijó el editor.
4. `SettingsController::settleSettingsImage()` le da su nombre final, **`<nombre>-<huella>.<ext>`** (los diez primeros
   caracteres del sha1). Si ya existe un archivo con esa huella, reutiliza ese. La huella hace que el navegador no
   enseñe la imagen vieja de su caché.
5. Guarda la ruta en la opción (tabla de Configuración) y borra la subida anterior, si era una subida.

## Cada imagen

| Imagen | Ruta | Opción | Archivo | Tamaño de salida | La lee |
| :-- | :-- | :-- | :-- | :-- | :-- |
| Fondos del acceso (1 a 5) | `configurations-appearance-backgrounds` | `backgrounds` (lista) | `login-backgrounds/bg<n>-<huella>` | 1600 × 1800, JPEG | El acceso (`login.js` elige uno al azar) |
| Fondo de la ayuda para entrar | la misma | `backgoundProblems` (así, con la errata) | `login-backgrounds/problems-background-<huella>` | 1920 × 1080 | La ayuda para entrar |
| Favicon del sitio / del panel | `configurations-appearance-brand-images` | `favicon` / `favicon-back` | `brand/favicon[-back]-<huella>` | 400 × 400, PNG | Las cabeceras pública y del panel |
| Logo | la misma | `logo` | `brand/logo-<huella>` | 400 × 400, PNG | El menú, el acceso, las páginas de error, los datos estructurados |
| Modo oscuro de una imagen de marca | la misma | `<imagen>DarkMode` (`same`, `invert`, `image`) y `<imagen>Dark` | `brand/<nombre>-dark-<huella>` | la de su imagen | `SettingsController::brandImageTag()` y `brandImageDarkView()`; sin modo guardado, sale de las `*InvertOnDark` de antes; el logo de fábrica, `white-logo.png` |
| Animación de carga | la misma | `loaderAnimation` (+ `loaderAnimationDarkMode`, `loaderAnimationDark`) | `brand/loader-animation[-dark]-<huella>` | cualquier tamaño, GIF; SVG (validado) solo el usuario principal (`SettingsController::ROLES_SVG_BRAND_IMAGES`), hasta 2 MB | El cargador, por los atributos `data-loader-*` del `<html>`; de fábrica, `loader-animation.svg` y `-dark.svg` |
| Logo del cargador | la misma | `loaderLogo` (+ `loaderLogoDarkMode`, `loaderLogoDark`) | `brand/loader-logo[-dark]-<huella>` | 400 × 400, PNG | El cargador. De fábrica `loader-logo.png` (la «P») y, en oscuro, `white-logo.png`; sin opción guardada, la de fábrica; **guardada vacía, apagado** (`BRAND_IMAGES_EMPTY_IS_OFF`: «Eliminar» guarda `''`, y `brandImagePath()` devuelve vacío) |
| Patrocinador, horizontal / vertical | la misma | `partners` / `partnersVertical` | `brand/partners[-vertical]-<huella>` | 280 × 50 / 50 × 280 | El pie del menú y el acceso |
| Logo de los correos | la misma | `mailingLogo` | `brand/mailing-logo-<huella>` | 500 × 172 | `mailing_logo()` (abajo) |
| Imagen para compartir | `configurations-appearance-seo` | `open_graph_image`, y `open_graph_image_<idioma>` fuera del idioma base | `open-graph/open_graph[_<idioma>]-<huella>` | 1200 × 630, JPEG | `og:image` y `twitter:image` de la cabecera pública |

Las tres rutas aceptan GET y POST, exigen sesión y solo las usan root y el administrador general.

### Fondos

- La lista tiene **de uno a cinco** fondos (`LOGIN_BACKGROUNDS_MAX`). Subir uno sustituye su `bg<n>` o lo añade al
  final; pasar de cinco se rechaza.
- **Eliminar** es `configurations-appearance-backgrounds-delete` (POST, parámetro `background`): quita la entrada,
  compacta la lista y borra el archivo **solo si era una subida**. Si la lista quedaría vacía, se rechaza.
- Tras subir, la pantalla recarga: solo el servidor conoce la huella.

### La imagen para compartir, por idioma

La cabecera pide `open_graph_image`; si el idioma actual no es el base, busca `open_graph_image_<idioma>` y, si no
existe, usa la del idioma base. Al abrir Identidad y SEO, un idioma sin imagen propia recibe una **copia** de la del
idioma base en las subidas (`open-graph/open_graph_<idioma>-<huella>`), para que se pueda cambiar por separado.

### El logo de los correos: `mailing_logo()`

`mailing_logo()` (`src/app/core/extensions/mailing.php`) devuelve, en este orden:

1. la opción `mailingLogo`, si su archivo existe;
2. `statics/images/mailing-logo.png`, el de fábrica;
3. uno generado desde `logo` a 500 × 172 en `brand/`;
4. si nada de eso vale, `logo`.

**Resuelve al llamarse**, no al arrancar: las extensiones del núcleo se cargan antes de que entren las opciones de la
base, así que una función que se evalúa al pintar ve el valor guardado. `get_config('mailing_logo')` sigue existiendo,
fijado al arrancar, para las plantillas propias que lo lean; las del framework usan la función.

## Dónde empezar si algo falla

- **Una imagen no cambia**: lee con `get_config()` su opción (la columna «Opción» de «Cada imagen») y mira si el archivo existe en
  `uploads/settings/<tipo>/`, dentro de `src/statics/`. Si la opción apunta a `statics/images/`, es de fábrica o de antes de la 10.11.0.
- **Un fondo no se puede eliminar**: es el último de la lista.
- **Una imagen de fábrica cambió en git**: eso no lo hace la aplicación desde la 10.11.0; lo hizo una versión anterior o
  una persona.
