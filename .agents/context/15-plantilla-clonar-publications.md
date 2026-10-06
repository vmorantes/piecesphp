# 15 — Plantilla: crear un módulo clonando `Publications`

Procedimiento de búsqueda y reemplazo para derivar un módulo nuevo desde
`src/app/classes/Publications`, que es la [referencia canónica](./13-recetas.md)
del proyecto.

Es la vía rápida cuando el módulo nuevo se parece a Publications. Si es muy
distinto, sale más barato el esqueleto desde cero de
[13-recetas.md](./13-recetas.md).

> Basado en la plantilla de trabajo del autor, verificada contra el código real y
> ampliada con los huecos detectados. Los tokens y rutas de archivo de aquí se
> comprobaron uno a uno sobre el módulo.

---

## Paso 0 — Copiar y decidir el alcance

```bash
cp -r src/app/classes/Publications src/app/classes/MiModulo
```

Antes de tocar nada, decide qué conservas. De ello dependen los barridos del paso 1:

| Pieza | Qué aporta | Archivos implicados |
| :-- | :-- | :-- |
| Categorías | Sub-entidad con CRUD propio | `PublicationsCategoryController`, `PublicationCategoryMapper`, `Views/categories/`, `Statics/js/categories/` |
| Adjuntos | Relación 1-N de archivos | `AttachmentPublicationMapper`, `Util/AttachmentPackage.php` |
| Zona pública | Listado y detalle públicos | `PublicationsPublicController`, `Views/publications/public/`, `Statics/js/publications/public/`, `lang/lang-public/` |
| Aprobaciones | Flujo de `SystemApprovals` | Referencias en `PublicationsController` y `PublicationMapper` |
| Caché | `CacheControllersManager` | `PublicationsController` (hoy `ENABLE_CACHE = false`) |

---

## Paso 1 — Eliminaciones estructurales

Expresiones regulares, **sin distinguir mayúsculas**. Barre todo el módulo,
incluido `Statics/sass` y `Statics/js` (varios tokens viven solo ahí).

**Quitar categorías y adjuntos, sin zona pública:**

```
(attachment|category|categorie|attach|PublicationsPublicController|getURLAlternatives)
```

**Quitar categorías y adjuntos, conservando zona pública:**

```
(attachment|category|categorie|attach)
```

> Cuidado: `attachment` también aparece en contextos ajenos a adjuntos de
> publicaciones. Revisa cada coincidencia antes de borrar.

**Conservar adjuntos, quitar solo categorías:**

```
(category|categorie)
```

**Quitar el adaptador JS del módulo** — acotado, no uses `adapter` a secas:

```
(PublicationsAdapter|pathFrontPublicationsAdapter)
```

> ⚠️ `adapter` sin acotar coincide con `UploadedFileAdapter`,
> `CropperAdapterComponent`, `RichEditorAdapterComponent`, `SimpleCropperAdapter` y
> `richEditorAdapter`, que son del núcleo y no deben tocarse.

**Si no se usan, eliminar también:**

```
(import_simple_upload_placeholder|SimpleUploadPlaceholder)
(import_default_rich_editor|RichEditorAdapterComponent)
(SystemApproval|systemApprovalStatus)
(CacheControllers|ENABLE_CACHE)
```

---

## Paso 2 — Limpieza del mapper y de los campos

Sigue antes del renombrado: así no renombras lo que vas a borrar.

**Imágenes, dejando solo `mainImage`:**

```
(thumbImage|ogImage|cropper-image-thumb|cropper-image-og|OpenGraph|og-image|thumb-image)
```

**Quitar también `mainImage`:**

```
(mainImage|cropper-image-main|main-image)
```

**Si no se usa recorte de imagen:**

```
(CropperAdapterComponent|SimpleCropperAdapter|import_cropper|CropperBehavior)
```

**Borrador, visibilidad, programación y etiquetas:**

```
(VISIBILITY|visibiliti|draf|previe|scheduled|publicated|\$tag)
```

> `publicated` es además clase CSS (`.container-standard-table.publicated`,
> `.mirror-scroll-x.publicated`) y valor de `data-tab` en
> `Views/publications/list.php`. Por eso el barrido debe incluir SASS y vistas.

**Destacados:**

```
(featured|featur)
```

**Fecha pública, SEO, visitas y autor:**

```
(seoDescription|publicDate|(?<!@)author|visit|searchUsersURL|autor)
```

> `(?<!@)author` es más exacto que `[^@]author`: este último falla cuando `author`
> abre línea.

**Rangos de fechas:**

```
(startDate|endDate|ignoreDateLimit|isActiveByDates|isActiveByDate|activesByDateIDs)
```

**Si no se usará la vista SQL** (`VIEW_ACTIVE_DATE = 'publications_active_date_elements'`,
declarada en `PublicationMapper.php:217` y usada en las líneas 747 y 1116):

```
VIEW_ACTIVE_DATE
```

**Contenido** — ojo con `excerptTitle`:

```
\$content|excerpt\(
'content'|"content"|content:|richEditorAdapter
```

**Título:**

```
('title',|name=("|')title("|')|title: \{)
('Nombre')
(@var string \$title|getValue\('title'\)|\$title = \$ex|\$mapper->setLangData\(\$lang.*\$title\);|, \$title,|string \$title = null|string \$title =null|\(\$title !== null\)|UPPER\('%\{\$title\}%'\)|fieldCurrentLangForSQL\(("|')title("|')|mb_strlen\(\$e->title\)|\$columns\[\] = \$title|\$titleField|\$mapper->title|currentLangData\(("|')title("|')\)|\$title = StringManipulate|@property string \$title|'title' => \[|excerptTitle|getLangData\(\$lang, 'title'\)|"\{\$title\}-\{\$uniqid\}"|'`title` ASC')
```

Terminar validando a mano una búsqueda simple de `title`.

---

## Paso 3 — Renombrado

Búsqueda **distinguiendo mayúsculas**, **sin expresiones regulares**, y
**de más específico a menos específico** — ese orden es lo que evita reemplazos
parciales.

### 3.1 Identificadores compuestos (primero)

```
appPublicationsLang           ← grupo de idioma en el front (JS)
publications/statics
publications-category-admin   ← baseRouteName del controlador de categorías
publications-admin            ← baseRouteName del controlador admin
publications-lang             ← PublicationsLang::LANG_GROUP
publications_active_date_elements
publications_attachments
publications_categories
publications_elements
publications.css
.ui.form.publications-categories
.ui.form.publications
ui form publications
delete-publication-category-button
delete-publication-button
canDeletePublicationCategory
canDeletePublication
pathFrontPublicationsAdapter
PUBLICATION_CODE              ← Exceptions/DuplicateException.php:19
PUBLICATIONS_MODULE
```

### 3.2 Clases y namespaces — renombra clase **y** archivo

```
namespace Publications
Publications\
@package Publications
```

Archivos y directorios a renombrar físicamente:

```
PublicationsRoutes.php
PublicationsLang.php
Controllers/PublicationsController.php
Controllers/PublicationsCategoryController.php
Controllers/PublicationsPublicController.php
Mappers/PublicationMapper.php
Mappers/PublicationCategoryMapper.php
Mappers/AttachmentPublicationMapper.php
Statics/js/PublicationsAdapter.js
Statics/js/publications/          (directorio)
Statics/sass/publications.scss
Statics/css/publications.css      (+ .css.map — regenerados por gulp)
Views/publications/               (directorio)
```

### 3.3 Tokens genéricos (al final)

```
$publication
Publications
Publication
publications
publication
```

### 3.4 Español

```
PUBLICACIONES        ← mayúsculas: TODAS_LAS_PUBLICACIONES, PUBLICACIONES_BORRADOR…
Publicaciones
publicaciones
Publicación
publicación
Publicad             ← 'Publicado' vive en lang/{en,it,pt,fr,de}.php línea ~85
```

> `PUBLICACIONES` y `Publicad` faltan si solo se buscan `Publicaciones` y
> `publicaciones`: la búsqueda distingue mayúsculas.
> `TODAS_LAS_PUBLICACIONES` (en `Views/publications/list.php:46` y en los 6 archivos
> de idioma) sobrevive a todos los borrados del paso 2.

---

## Paso 4 — Género gramatical

```
\b(La|la|creada|eliminada)\b
```

> Los límites de palabra no son opcionales. Sin ellos, `La` produce **484
> coincidencias en Publications, 439 de ellas dentro de `Lang`, `LangData`,
> `LANG_GROUP` y `Label`** — justo lo que no se debe tocar.

---

## Paso 5 — Metadatos

**Año de copyright** (regexp):

```
buscar:    (@copyright Copyright \(c\)) \d{4}
remplazar: $1 AÑO
```

**Correo del autor** (regexp, evita el `mailto:` de las plantillas):

```
[^<]sir\.vamb
```

---

## Paso 6 — Dependencias de otros módulos

El clon arrastra estos `use`. Decide qué hacer con cada uno:

```php
use Organizations\Mappers\OrganizationMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\SystemApprovalsRoutes;
use API\APIRoutes;
```

La integración con aprobaciones está en `PublicationsController` alrededor de la
línea 1388:

```php
$validateSystemApprovals = SystemApprovalsRoutes::ENABLE
    && !empty(array_filter($fields, fn($e) => mb_strpos($e, 'systemApprovalStatus')));
```

Si el módulo nuevo no entra al flujo de aprobaciones, elimínalo aquí y registra un
handler propio en `SystemApprovals\Util\Packages\` si algún día sí entra.

---

## Paso 7 — Conexión con el sistema

El renombrado no basta: el módulo no existe hasta que se registra.

1. Constante de activación en `src/app/config/constants.php`
   (`define('MI_MODULO_ENABLE', true);`) y leerla como `const ENABLE = MI_MODULO_ENABLE;`
   en `MiModuloRoutes`.
2. Registro en `src/app/config/routes.php`, **de más específico a menos específico**;
   el grupo público siempre al final.
3. Arrays de roles permitidos en `routes()` del controlador
   (`$list`, `$creation`, `$edition`, `$deletion`, `$queries`).
4. Entrada de sidebar en `MiModuloRoutes::init()`, con `position` que no choque con
   las existentes.
5. Generar la tabla: `bin/cli scheme-create module=MiModulo`, revisar el `CREATE TABLE`
   que emite, ejecutarlo, y añadir el SQL a `databases/piecesphp_structure.sql`.
6. Compilar estáticos: `cd src && gulp sass-modules`.

---

## Paso 8 — Verificación

**Barrido de restos.** Es el paso que más errores atrapa:

```bash
grep -rniE "public(ation|acion|ación|ated|ad[oa]|aciones)" src/app/classes/MiModulo/
```

Cualquier coincidencia es basura del clonado.

**Comprobaciones adicionales:**

```bash
grep -rn "sir\.vamb" src/app/classes/MiModulo/          # correo del autor
grep -rn "Copyright (c) 20" src/app/classes/MiModulo/   # año sin actualizar
bin/phpstan                                              # comparar con PHPStanResult.Summary.txt
```

Y a mano: entrar al panel, comprobar que la entrada de sidebar aparece solo para los
roles previstos, y que `MiModuloController::routeName('list')` devuelve URL para esos
roles y cadena vacía para el resto.
