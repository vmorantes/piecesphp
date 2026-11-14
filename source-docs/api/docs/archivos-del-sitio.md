<!-- generada por bin/api-docs desde openapi.json: no se edita -->

# Archivos del sitio

robots.txt, humans.txt, llms.txt y sitemap.xml, en la raíz del sitio.

## robots.txt

`GET /robots.txt`

Ruta interna: `site-files-robots`.

Autenticación: sin sesión.

Las reglas para buscadores: cierra lo privado de la instalación (panel, usuarios, terminal y otros), apunta al sitemap con una dirección absoluta y añade las líneas que el administrador guardó en la configuración. Se sirve con Cache-Control: public, max-age=3600. No usa sesión.

### Respuestas

#### 200

El archivo.

`text/plain`

Tipo: string.

## humans.txt

`GET /humans.txt`

Ruta interna: `site-files-humans`.

Autenticación: sin sesión.

Quién está detrás del sitio, con el formato de humanstxt.org: el propietario, la fecha de última actualización y los idiomas, más las líneas que el administrador guardó. Se sirve con Cache-Control: public, max-age=3600. No usa sesión.

### Respuestas

#### 200

El archivo.

`text/plain`

Tipo: string.

## llms.txt

`GET /llms.txt`

Ruta interna: `site-files-llms`.

Autenticación: sin sesión.

Una guía del sitio para agentes de IA, con el formato de llmstxt.org: título, descripción y secciones, en el idioma por omisión, más las líneas que el administrador guardó. Se sirve con Cache-Control: public, max-age=3600. No usa sesión.

### Respuestas

#### 200

El archivo.

`text/markdown`

Tipo: string.

## sitemap.xml

`GET /sitemap.xml`

Ruta interna: `site-files-sitemap`.

Autenticación: sin sesión.

El mapa del sitio de todos los proveedores registrados (la portada, las páginas públicas y los módulos que aportan las suyas, en cada idioma). Se guarda una hora en el servidor y se sirve con Cache-Control: public, max-age=3600. No usa sesión.

### Respuestas

#### 200

El archivo.

`application/xml`

Tipo: string.
