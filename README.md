# PiecesPHP Framework

[![Ask DeepWiki](https://deepwiki.com/badge.svg)](https://deepwiki.com/vmorantes/piecesphp)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](./LICENSE)

Framework PHP modular para aplicaciones web administrativas multi-idioma. Construido sobre
[Slim 4](https://www.slimframework.com/) con un núcleo propio que añade ORM, permisos por ruta, internacionalización,
gestión de estáticos y una capa de tareas de terminal.

No es solo un framework: trae módulos de negocio listos para usar (usuarios, publicaciones, documentos, formularios,
organizaciones, reportes y más), cada uno activable o desactivable con una constante.

---

## Características

- **Enrutado con permisos integrados**: las rutas se declaran con `Route` y `RouteGroup`, y el nombre de la ruta *es* el
  identificador de permiso, así que autorizar una acción y publicarla son el mismo acto.
- **ORM propio de entidades**: mapeo por `$fields`, relaciones con carga automática, meta-propiedades sobre columnas
  JSON y el `CREATE TABLE` generado desde el propio modelo.
- **Multi-idioma de punta a punta**: detección por URL, cookie o navegador; grupos de traducción por módulo;
  traducciones editables desde el panel.
- **Arquitectura modular**: cada módulo es una carpeta autocontenida con sus rutas, controladores, mappers, vistas,
  estáticos y traducciones.
- **Terminal y tareas**: CLI con autocompletado, tareas programadas, colas de trabajos y acciones propias.
- **Estáticos propios**: compilación de SASS, versionado de caché y archivos protegidos por sesión.
- **Correo con registro**: envío declarado por entorno, registro de cada correo con su cuerpo cifrado, y diagnóstico
  desde la terminal.

## Requisitos

- **PHP 8.5** (el rango exacto está en [`src/composer.json`](./src/composer.json)). Ubuntu 26.04 LTS lo trae en sus
  repositorios oficiales.
- **Extensiones**: las `ext-*` de `src/composer.json`; la [guía de PHP](./source-docs/project/docs/environments/content/lamp/content/PHP.md)
  dice qué paquete trae cada una.
- **MySQL o MariaDB**.
- **Apache** con `rewrite`, `headers` y `ssl` habilitados.
- **Composer**, **Node.js 22.x LTS** y **Gulp CLI** para compilar los estáticos.

## Instalación

```bash
# La rama last-stable apunta siempre a la última versión estable
git clone --branch last-stable <url-de-este-repositorio> mi-proyecto
cd mi-proyecto

# Dependencias PHP (src/vendor no viene en el repositorio)
cd src && composer install && cd ..

# Dependencias y compilación de los estáticos
npm install
cd src && gulp init-project && cd ..

# Permisos y propiedad
./permissions-and-property.sh

# Declarar el entorno: local o producción
cp src/app/config/environment.example.php src/app/config/environment.php
```

Después, configura la conexión en `src/app/config/database.php`, carga los scripts SQL de
[`databases/`](./databases) y activa o desactiva módulos en `src/app/config/constants.php`.

**Antes de exponer la instalación**, cambia la contraseña de los usuarios de ejemplo que trae
`databases/piecesphp_data.sql` (entre ellos `root` y `admin-general`), o bórralos: sus hashes son públicos, porque viajan
en este repositorio.

La guía completa, con el despliegue en Ubuntu 26.04, está en
[`source-docs/project/docs/piecesphp/content/general.md`](./source-docs/project/docs/piecesphp/content/general.md).

## Comprobar la instalación

```bash
bin/check-routes              # las rutas; funciona aunque la aplicación no arranque
bin/cli verify-integrity      # la integridad estructural
bin/cli gates                 # las suites de prueba, solo en una instalación declarada `local`
```

Algunas comprobaciones dicen **«[NO APLICA EN LA DISTRIBUCIÓN]»**: dependen de herramientas del repositorio donde se
desarrolla el framework, que no viajan con esta copia. El resultado final dice cuántas no se hicieron.

## Estructura

```
bin/            La terminal (bin/cli), la comprobación de rutas y utilidades de estáticos
databases/      Scripts SQL: estructura, datos, vistas y funciones
files/          Recursos auxiliares
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
bin/cli help                      # lista las acciones disponibles
bin/cli help task=<acción>        # la descripción de una
```

`bin/cli` elige PHP 8.5 y trabaja sobre la instalación local. Autocompletado con `source bin/pieces-completion.bash`
(o `.zsh`). La lista de acciones, con sus parámetros, en la
[guía de la terminal](./source-docs/project/docs/piecesphp/content/terminal.md).

## Trabajar con agentes de IA

Este repositorio trae un andamiaje para trabajar con agentes de programación: [`AGENTS.md`](./AGENTS.md) es el punto de
entrada, y [`.agents/context/`](./.agents/context/README.md) explica la arquitectura, las convenciones y las recetas del
framework. Si los usas, activa el control de los commits:

```bash
git config core.hooksPath .agents/scripts/git-hooks
```

## Documentación

| Recurso | Contenido |
| :-- | :-- |
| [`source-docs/project/docs/`](./source-docs/project/docs/) | Guías del framework y de los entornos de despliegue |
| [`source-docs/api/`](./source-docs/api) | La API y su colección de Postman |
| [`CHANGELOG.md`](./CHANGELOG.md) | Qué cambia en cada versión, con los cambios incompatibles marcados |
| [DeepWiki](https://deepwiki.com/vmorantes/piecesphp) | Recorrido del código generado automáticamente |

La documentación se publica como sitio estático con MkDocs a partir de `source-docs/project`.

## Paquetes relacionados

| Paquete | Función |
| :-- | :-- |
| [`piecesphp/database`](https://packagist.org/packages/piecesphp/database) | ORM, ActiveRecord y mapeo de entidades |
| [`piecesphp/datastructures`](https://packagist.org/packages/piecesphp/datastructures) | Colecciones tipadas |
| [`piecesphp/geojson`](https://packagist.org/packages/piecesphp/geojson) | Manipulación de GeoJSON |
| [`piecesphp/html`](https://packagist.org/packages/piecesphp/html) | Generación y formateo de HTML |

## Licencia

[MIT](./LICENSE) — Vicsen Morantes
