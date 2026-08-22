# PiecesPHP Framework

[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/vmorantes/piecesphp)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](./LICENSE)

Framework PHP modular para aplicaciones web administrativas multi-idioma sobre stack
LAMP. Construido sobre [Slim 4](https://www.slimframework.com/) con un núcleo propio que
añade ORM, sistema de permisos por ruta, internacionalización, gestión de estáticos y una
capa de tareas de terminal.

No es solo un framework: incluye un conjunto de módulos de negocio listos para usar
(usuarios, publicaciones, documentos, formularios, organizaciones, reportes y más), cada
uno activable o desactivable con una constante.

---

## Características

- **Enrutado con permisos integrados** — las rutas se declaran con `Route` y `RouteGroup`;
  el nombre de la ruta *es* el identificador de permiso, así que autorizar una acción y
  publicarla son el mismo acto.
- **ORM propio de entidades** — mapeo por `$fields`, relaciones con carga automática de
  mappers, meta-propiedades sobre columnas JSON y generación del `CREATE TABLE` desde el
  propio modelo.
- **Multi-idioma de punta a punta** — detección por URL, cookie o navegador; grupos de
  traducción por módulo; traducciones editables desde el panel; formatos de fecha y
  locales de base de datos por idioma.
- **Arquitectura modular** — cada módulo es una carpeta autocontenida con sus rutas,
  controladores, mappers, vistas, estáticos y traducciones.
- **Terminal y tareas** — CLI con autocompletado, cronjobs con interfaz fluida, colas de
  procesamiento asíncrono y acciones personalizadas.
- **Servidor de estáticos propio** — compilación de SASS al vuelo, versionado de caché y
  protección de directorios por sesión.
- **Sistema de eventos** — desacoplamiento por contextos y escuchadores.

## Requisitos

- **PHP** — ver el rango declarado en [`src/composer.json`](./src/composer.json)
- **Extensiones**: `openssl`, `pcre`, `hash`, `session`, `json`, `pdo`, `pdo_mysql`,
  `mysqli`, `pdo_sqlite`, `sqlite3`, `xml`, `xsl`, `xmlwriter`, `xmlreader`, `ctype`,
  `mbstring`, `fileinfo`, `gd`, `zip`, `curl`
- **MySQL / MariaDB**
- **Apache** con `rewrite`, `headers` y `ssl` habilitados
- **Composer**, **Node.js 22.x LTS** y **Gulp CLI** para el entorno de desarrollo

## Instalación rápida

```bash
# Dependencias PHP
cd src && composer install

# Dependencias y compilación de front
cd .. && npm install
cd src && gulp init-project

# Permisos y propiedad
./permissions-and-property.sh
```

Configura la conexión en `src/app/config/database.php` y carga los scripts SQL de
[`databases/`](./databases). Activa o desactiva módulos en
`src/app/config/constants.php`.

La guía completa de despliegue está en
[`source-docs/project/docs/piecesphp/content/general.md`](./source-docs/project/docs/piecesphp/content/general.md).

## Estructura

```
bin/            Ejecutables y herramientas de desarrollo (CLI, PHPStan, Rector)
databases/      Scripts SQL: estructura, datos, vistas y funciones
files/          Recursos auxiliares y documentación de la API
source-docs/    Fuentes de la documentación (MkDocs)
src/            Raíz de la aplicación web
├── index.php     Front controller único, para web y terminal
├── app/
│   ├── classes/    Módulos (PSR-4)
│   ├── config/     Configuración de la instancia
│   ├── core/       Núcleo del framework
│   ├── lang/       Traducciones globales
│   └── view/       Vistas y layouts de sistema
└── statics/      Recursos públicos
tasks/          Tareas automatizadas de Composer
```

## Terminal

```bash
bin/cli <acción> [parámetros]        # atajo local
php index.php cli <acción> [...]     # forma explícita
```

| Acción | Descripción |
| :-- | :-- |
| `help` | Lista las acciones disponibles |
| `db-backup` | Respalda la base de datos (SQL, JSON, CSV, PHP o XML, con compresión opcional) |
| `bundle` | Empaqueta la aplicación y/o los estáticos |
| `clean-cache`, `clean-logs`, `clean-all` | Limpieza de caché y registros |
| `run-cronjobs` | Ejecuta las tareas programadas que corresponda |
| `process-queue` | Procesa la cola de trabajos en segundo plano |
| `scan-missing-lang` | Informe de traducciones faltantes |

Autocompletado disponible con `source bin/pieces-completion.bash` (o `.zsh`).

## Configuración de git, una vez por copia

El repositorio fija los finales de línea con `.gitattributes` —CRLF por defecto, LF en
`*.sh` y en los ejecutables de `bin/`—, así que **no hay que configurar nada para que
funcione**. Pero hay dos ajustes locales que conviene poner una sola vez:

```bash
# 1. Que `git blame` se salte la renormalización de finales de línea.
#    Sin esto, los archivos grandes atribuyen TODAS sus líneas a ese commit.
git config blame.ignoreRevsFile .git-blame-ignore-revs

# 2. Que las fusiones ignoren las diferencias de finales de línea.
#    Imprescindible al ACTUALIZAR UN DESPLIEGUE que venga de antes de la renormalización:
#    sin esto salen conflictos en cada archivo que el despliegue haya tocado.
git config merge.renormalize true
```

El segundo también se puede dar por fusión suelta, sin configurarlo:

```bash
git merge -X renormalize <rama>
```

**Comprobado en una fusión de prueba**: un despliegue con cambios propios sobre archivos
afectados da **2 conflictos sin la opción y 0 con ella**, conservando sus cambios locales.

## Cómo se empuja en este repositorio

**Hay TRES remotos** —`origin` en GitHub, `origin2` en GitLab y `origin3` en Bitbucket—, y
`git push` a secas solo alcanza a uno. Para que no diverjan:

```bash
bin/push-all              # la rama actual, a los tres
bin/push-all --dry-run    # enseña qué haría, sin empujar
```

No lleva la lista escrita dentro: pregunta a `git remote`, así que un remoto nuevo entra
solo. Y si uno falla, sigue con los demás y avisa al final de cuáles quedaron fuera.

Además, **`dev` tiene que rastrear a `origin/dev`**. Sin eso `git status` no dice «adelante
N» y es fácil dar por empujado lo que no lo está:

```bash
git branch --set-upstream-to=origin/dev dev
```

> Las dos cosas salen del mismo incidente: se dio por empujado un trabajo que no lo estaba, y
> ocho commits se quedaron atrás **sin que nada lo dijera** — ni el `status`, porque no había
> upstream, ni el push, porque solo había ido a un remoto.
## Documentación

| Recurso | Contenido |
| :-- | :-- |
| [`source-docs/project/docs/piecesphp/`](./source-docs/project/docs/piecesphp/) | Guías del framework: estructura, rutas, mappers, terminal, permisos, Gulp |
| [DeepWiki](https://deepwiki.com/vmorantes/piecesphp) | Recorrido del código generado automáticamente |
| [`CHANGELOG.md`](./CHANGELOG.md) | Historial de versiones |
| [`files/dev/tests.md`](./files/dev/tests.md) | Pruebas unitarias y de desarrollo |
| [`files/API/`](./files/API) | Documentación de la API y colección de Postman |

La documentación se publica como sitio estático con MkDocs a partir de
`source-docs/project`.

## Paquetes relacionados

El framework se apoya en librerías propias publicadas por separado:

| Paquete | Función |
| :-- | :-- |
| [`piecesphp/database`](https://packagist.org/packages/piecesphp/database) | ORM, ActiveRecord y mapeo de entidades |
| [`piecesphp/datastructures`](https://packagist.org/packages/piecesphp/datastructures) | Colecciones tipadas |
| [`piecesphp/geojson`](https://packagist.org/packages/piecesphp/geojson) | Manipulación de GeoJSON |
| [`piecesphp/html`](https://packagist.org/packages/piecesphp/html) | Generación y formateo de HTML |

## Licencia

[MIT](./LICENSE) — Vicsen Morantes
