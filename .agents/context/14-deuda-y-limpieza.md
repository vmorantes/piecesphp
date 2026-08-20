# 14 — Deuda técnica y candidatos a limpieza

Análisis del contenido de `src/app/classes/` hecho el **2026-08-19** sobre la rama
`limpieza-modulos`. Base: **24 módulos, ~88.200 líneas de PHP**.

Ya eliminados en ramas anteriores: chat interno, presentaciones de capacitación,
`Persons`, `App/Presentations`. **No quedan restos** de esos módulos en el código.

---

## Tabla de módulos

`in` = archivos de otros módulos que lo importan · `out` = módulos que él importa
(sin contar `config/routes.php` ni `config/menu.php`, que referencian a todos).

| Módulo | LOC | in | out | Veredicto |
| :-- | --: | --: | --: | :-- |
| PiecesPHP (UserSystem, Banner, Helpers, Localization, RoutingUtils) | 10.367 | 280 | — | Núcleo funcional. Conservar |
| Publications | 8.751 | 10 | 3 | Activo, referencia de calidad. Conservar |
| MySpace | 7.749 | 10 | 8 | Home del backoffice + perfiles. Desacoplable |
| ApplicationCalls | 6.162 | 12 | 3 | Capa de proyecto |
| App\Locations | 6.030 | 125 | 1 | Catálogo geográfico. Conservar |
| News | 6.024 | 3 | 0 | Marcado «por renovar» |
| Forms | 5.320 | 3 | 1 | Marcado «por renovar» |
| Organizations | 4.668 | 30 | 1 | Base reutilizable (fan-in 30, fan-out 1) |
| ImagesRepository | 4.400 | 2 | 0 | **Candidato fuerte** |
| SystemApprovals | 4.339 | 8 | 3 | Mecanismo agnóstico; **el core depende de él** |
| API | 4.093 | 3 | 5 | Conservar |
| Documents | 3.268 | 3 | 1 | Marcado «por renovar» |
| InterestResearchAreas | 3.008 | 0 | 3 | Capa de proyecto, hoja |
| Terminal | 2.287 | 1 | 1 | Núcleo. Conservar |
| Newsletter | 1.982 | 2 | 0 | Hoja aislada |
| ContentNavigationHub | 1.546 | 3 | 4 | Capa de proyecto. Acople en 8 vistas |
| FileManager | 1.309 | 0 | 0 | **Hoja totalmente aislada** |
| GeoJSONManager | 1.286 | 1 | 4 | Referencia válida (acople en 1 archivo) |
| DataImportExportUtility | 1.226 | 0 | 0 | **Hoja totalmente aislada** |
| EventsLog | 1.210 | 1 | 0 | Hoja |
| ReportsManage | 1.195 | 2 | 5 | Base válida (acople en `Queries/`) |
| Importers | 1.129 | 0 | 0 | **Duplicado. Eliminar** |
| Components | 489 | 1 | 1 | **Stub. Eliminar o promover** |
| GoogleReCaptchaV3 | 386 | 2 | 0 | Pequeño y útil. Conservar |

---

## Los cuatro problemas de fondo

### 1. `Importers` y `DataImportExportUtility` hacen lo mismo

Dos módulos de importación conviviendo, y ambos empiezan por usuarios:

| | `Importers` | `DataImportExportUtility` |
| :-- | :-- | :-- |
| Bandera | `IMPORTS_MODULE_ENABLED` | — |
| Alcance | Solo importar | Importar **y** exportar |
| Implementación | `Managers/ImporterUsers.php` sobre `PiecesPHP\Core\Importer\*` | `Controllers/ExportHandlers/{BaseExportData,UsersExporter}` |
| Vistas | `Views/users.php` | `Views/imported-generated.php` |
| Acoplamiento | 0 in / 0 out | 0 in / 0 out |
| Copyright de cabecera | anterior | 2022 |

`DataImportExportUtility` es el reemplazo y es un superconjunto.

**Acción**: eliminar `Importers` (1.129 LOC) + la constante `IMPORTS_MODULE_ENABLED`
+ su línea en `routes.php` (`ImporterController::routes($importadores)`) + el grupo
`$importadores`. Antes: migrar `ImporterUsers` a un handler de
`DataImportExportUtility` si aún se usa la importación de usuarios.

> Nota: `PiecesPHP\Core\Importer\*` (Schema/Field/Response/Collections) queda
> **huérfano** al borrar `Importers` — es su único consumidor. O se elimina también,
> o se adopta como motor de `DataImportExportUtility`. La segunda opción es mejor:
> es la pieza mejor diseñada de las tres.

### 2. El core depende de un módulo de aplicación

```php
// src/app/core/psr4/PiecesPHP/Core/BaseEntityMapper.php
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\SystemApprovalsRoutes;
use SystemApprovals\Util\SystemApprovalManager;
```

`BaseEntityMapper::__callStatic` intercepta `fieldsToSelect()` e inyecta la columna
`systemApprovalStatus` en **todos los SELECT de todos los mappers del sistema**.

Consecuencias:

- La dependencia va al revés: el núcleo del ORM depende de una carpeta de
  `app/classes/`. El framework no arranca sin ese módulo presente.
- Todo mapper carga una subconsulta correlacionada que la mayoría nunca usa.
- `SystemApprovals` es intocable aunque el proyecto no lo necesite.

**Acción** (la de mayor impacto de esta lista, y no es un borrado):
invertir la dependencia. `SystemApprovals` se registra a sí mismo mediante el
sistema de eventos o un punto de extensión del mapper, en vez de que el mapper lo
importe. `BaseEventDispatcher` ya existe y es exactamente para esto.

### 3. El cluster de 8 módulos: acoplado, pero no todo es descartable

```
Organizations ←→ MySpace ←→ ApplicationCalls ←→ ContentNavigationHub
      ↑             ↑              ↑                     ↑
      └──── SystemApprovals ── ReportsManage ── InterestResearchAreas ── GeoJSONManager
```

**~30.000 LOC, el 34 % de `app/classes/`.** Todos apuntan a `Organizations`, y hoy
no se pueden desactivar por partes: `ORGANIZATIONS_MODULE = false` deja referencias
colgando en los otros siete.

Pero «acoplado» no es lo mismo que «desechable». Medido archivo por archivo, el
acoplamiento está **muy concentrado**, y eso cambia el diagnóstico por módulo:

| Módulo | Salidas | Dónde vive el acoplamiento | Veredicto |
| :-- | --: | :-- | :-- |
| **Organizations** | 1 | Solo `OrganizationsController` → `MySpace` | **Base reutilizable.** Fan-in 30, fan-out 1. Es el cimiento sobre el que se montan sistemas más completos |
| **GeoJSONManager** | 4 | **Un solo archivo**: `Controllers/GeoJsonManagerController.php` | **Referencia válida.** `Util/`, `Enums/`, `Mappers/` están limpios. Aislar el controlador y el módulo queda reutilizable |
| **ReportsManage** | 5 | 4 en `Queries/ReportsManageQueries.php`, 1 en el controlador | **Base válida.** El motor de reportes es genérico; las consultas concretas son del proyecto. Separar `Queries/` |
| **SystemApprovals** | 3 | Ver desglose abajo | **Mecanismo agnóstico**, envoltorio no |
| **MySpace** | 8 | `MySpaceController` (7) + 3 controladores de perfil de organización | **Desacoplable.** Ver abajo |
| **InterestResearchAreas** | 3 | Controlador + mapper | Específico del dominio |
| **ApplicationCalls** | 3 | Controlador + una vista | Específico del dominio |
| **ContentNavigationHub** | 4 | 1 controlador + **8 vistas** | El más enredado: el acoplamiento está en las vistas |

#### SystemApprovals: el mecanismo sí es agnóstico

| Agnóstico (reutilizable tal cual) | Acoplado al proyecto |
| :-- | :-- |
| `Util/SystemApprovalManager.php` | `Util/Packages/*` — 4 handlers (esperado: son los plugins) |
| `Util/ApprovalElementHandlerInterface.php` | `Controllers/SystemApprovalsController.php` |
| `SystemApprovalsMiddleware.php` | `Views/forms/approval-*.php` — 4 vistas |
| `SystemApprovalsRoutes.php` | `Util/Packages/BaseApprovalHandler.php` |
| `Views/list.php`, `Views/mailing/*` | ⚠️ `Mappers/SystemApprovalsMapper.php` |

El diseño es correcto: manager + interfaz + handlers enchufables por tabla. **La
única fuga real está en el mapper base**, líneas 259-271, con SQL que nombra
`OrganizationMapper::TABLE` a pelo:

```php
$referenceOrganizationAdministrator = "(SELECT JSON_UNQUOTE(JSON_EXTRACT({$tableOrganizations}.meta, '$.administrator')) ...)";
```

Eso pertenece a `OrganizationApprovalHandler`, no al mapper. Sacarlo de ahí (junto
con la inversión de dependencia del punto 2) deja el módulo genuinamente agnóstico y
listo para el siguiente proyecto.

#### MySpace: dos módulos en uno

`MySpaceController` son 452 líneas que hacen **una sola cosa**: elegir qué vista es
el home del backoffice según el tipo de usuario.

```php
return (new ReportsManageController())->genericReportView($request, $response);
// ...
return (new PublicationsController())->listView($request, $response);
// ...
return (new ContentNavigationHubController())->applicationCallsListView($request, $response);
```

Es una tabla de despacho, no lógica de negocio. Y el framework **ya tiene el patrón
para invertirla**: igual que `<Modulo>Routes::init()` hace
`get_sidebar_menu()->addItem(new MenuGroup([... 'position' => 130]))`, cada módulo
podría registrar su vista candidata de home con una condición y una prioridad. Eso
elimina los 7 imports de golpe.

El resto del acoplamiento de MySpace **no es de MySpace**: `MyOrganizationProfileController`,
`OrganizationProfileController`, `AllProfilesController` y sus vistas
(`Views/profile-organization/`, `Views/my-organization-profile/`) son perfiles de
organización viviendo en la carpeta equivocada. **Mover esos 3 controladores a
`Organizations`** deja a MySpace como lo que dice ser: el home del backoffice.

#### Acción

No borrar: **separar por capas**, no por módulos.

1. **Base reutilizable** — `Organizations`, `SystemApprovals` (tras sacar el SQL del
   mapper), `GeoJSONManager` (tras aislar su controlador), `ReportsManage` (tras
   separar `Queries/`), `MySpace` como home genérico.
2. **Capa de proyecto** — `ApplicationCalls`, `InterestResearchAreas`,
   `ContentNavigationHub`, los `Util/Packages/*` de aprobaciones, las `Queries/` de
   reportes, los handlers de GeoJSON.

Orden natural: primero mover los 3 controladores de perfil a `Organizations`, luego
invertir el home de MySpace, luego el SQL del mapper de aprobaciones. Cada paso baja
el acoplamiento sin cambiar comportamiento.

`ContentNavigationHub` es el que más resistirá: su acoplamiento vive en 8 vistas, y
las vistas no se refactorizan con inyección de dependencias tan fácilmente.

### 4. ~1.000 líneas de `HelperController` copiado y pegado

**26 archivos `HelperController.php`, agrupados en solo 8 variantes reales:**

| Hash | Líneas | Nº de copias | Contenido |
| :-- | --: | --: | :-- |
| `bb40…` | 40 | 5 | `__construct` |
| `cadf…` | 40 | 4 | `__construct` |
| `ecaf…` | 40 | 4 | `__construct` |
| `2c7a…` | 40 | 2 | `__construct` |
| `516a…` | 57 | 5 | `__construct` + `getLangsForSelect` |
| `709f…` / `e882…` | 57 | 2 | `__construct` + `getLangsForSelect` |
| `4f20…`, `5591…` | 40 | 2 | `__construct` |

Las diferencias entre grupos son cosméticas (espaciado, nombres de variables). Solo
dos tienen lógica propia: `API` (178 líneas) y `App\Locations` (68).

**Acción**: una clase `PiecesPHP\Core\BaseHelperController` con `__construct` y
`getLangsForSelect`; los 24 archivos triviales se borran y los dos con contenido la
extienden. Es la limpieza de mejor relación esfuerzo/beneficio de todo el listado.

---

## Candidatos concretos, por orden de riesgo

### Riesgo bajo — borrar ya

| Qué | LOC | Por qué |
| :-- | --: | :-- |
| **`Importers`** | 1.129 | Duplicado de `DataImportExportUtility`. 0 acoplamiento |
| **24 `HelperController.php` triviales** | ~1.000 | Copia y pega; reemplazables por una clase base |
| **`Components`** | 489 | Stub: 2 vistas, una de ellas `sample/components.php` con *lorem ipsum*. Un solo consumidor (`PublicAreaController`). O se completa o se borra |
| **Restos de Webflow** | — | `src/app/view/webflow/` (4 archivos, solo se referencian entre sí), `src/statics/wf/` (5 archivos), `files/Webflow/`. Ningún controlador los usa |

**Subtotal inmediato: ~2.600 LOC sin tocar nada funcional.**

### Riesgo medio — decisión de producto

| Qué | LOC | Consideración |
| :-- | --: | :-- |
| **`ImagesRepository`** | 4.400 | Marcado «por renovar» en `IGNORE.md` y «rehacer módulo de imágenes» en `TODO.md`. Solapa con `BuiltIn/Banner` (3.389) y `FileManager` (1.309): **tres formas distintas de gestionar imágenes/archivos**. Solo lo usa `MySpace` (2 referencias). Si se va a rehacer, bórralo antes de reescribirlo |
| **`FileManager`** | 1.309 | 0 acoplamiento. Es un envoltorio de elFinder, dependencia pesada (`studio-42/elfinder`). Marcado «por renovar». ¿Lo usa alguien de verdad? |
| **`Newsletter`** | 1.982 | Hoja aislada; solo `ContactFormsController` y `PublicAreaController`. Función muy delgada (tabla `newsletter_sucribers`, con errata en el nombre) |
| **`EventsLog`** | 1.210 | Solo lo usa `APIController`. Útil si se audita de verdad; muerto si nadie mira `actions_log` |
| **Temporizador** | — | `ACTIVE_TIMER` solo se lee en `app/view/panel/layout/header.php`. `TimerController` sigue registrado en `routes.php`. `TODO.md` dice «rehacer». Hoy es un vestigio |

### Riesgo alto — no borrar, reestructurar

El cluster del punto 3 y la inversión de dependencia del punto 2.

---

## Otras deudas detectadas (no son borrados)

- **El namespace `App\` está partido en tres raíces físicas**:
  `App\Controller` → `app/controller` y `App\Model` → `app/model` (mapeados a mano
  en `core/autoload.php`), mientras que `App\Locations` → `app/classes/App/Locations`
  (por el PSR-4 de `config/autoloads.php`). Confunde y complica el análisis
  estático. `Locations` merece ser un módulo de primer nivel
  (`src/app/classes/Locations/`) como los demás.
- **`routeName()` / `allowedRoute()` duplicadas en 44 controladores**: ver el
  análisis en [05-routing-y-permisos.md](./05-routing-y-permisos.md). La solución es
  un trait, no una clase base.
- **`src/statics/plugins/` pesa 40 MB** — el 85 % de los estáticos. Vale la pena
  auditar qué plugins se cargan realmente desde `config/assets.php`.
- **Tres versiones de Mapbox GL en paralelo** en `package.json` (v2.6.0, v3.4.0 y la
  actual v3.19.0). Consolidar en una.

---

## Orden sugerido

1. `HelperController` → clase base *(bajo riesgo, ~1.000 LOC, mejora todos los módulos)*
2. Borrar `Importers` y restos de Webflow *(bajo riesgo, ~1.200 LOC)*
3. Decidir sobre `Components` *(completar o borrar)*
4. Invertir la dependencia `BaseEntityMapper` → `SystemApprovals` *(desbloquea todo lo demás)*
5. Decidir el destino de `ImagesRepository` / `FileManager` / `Banner` — **una sola**
   estrategia de archivos e imágenes
6. Mover `MyOrganizationProfileController`, `OrganizationProfileController` y
   `AllProfilesController` de `MySpace` a `Organizations` *(mover archivos, sin
   cambiar lógica)*
7. Invertir el home del backoffice: cada módulo registra su vista candidata, igual
   que hoy registra su entrada de sidebar *(elimina 7 imports de `MySpaceController`)*
8. Sacar el SQL de `OrganizationMapper` fuera de `SystemApprovalsMapper` (líneas
   259-271) hacia `OrganizationApprovalHandler`
9. Separar la capa de proyecto de la base reutilizable (ver punto 3)
10. Trait para `routeName()` / `allowedRoute()`

> Verificación después de cada paso: `bin/phpstan` (comparar contra
> `PHPStanResult.Summary.txt`) y arranque limpio del panel administrativo.
