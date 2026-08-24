# 17 — Ruta de ejecución: PHP 8.1 → 8.5 (ejecutada: piso final 8.4.1)

Plan de ejecución para [16-plan-php85.md](./16-plan-php85.md).
Estado: **ejecutado**. Última revisión: 2026-08-20.

> ## ✅ EJECUTADO — 2026-08-20
>
> | Fase | Estado |
> | :-- | :-- |
> | **A** — piso y sondeo de 8.5 | ✅ |
> | **B** — `piecesphp/database` v3.1.0 | ✅ publicada |
> | **C** — los otros tres paquetes y el techo | ✅ `datastructures` v3.1.0, `html` v2.1.0, `geojson` v2.1.0; techo a `<8.6` |
> | **D** — barrido de deprecaciones | ✅ 13 sitios, un commit por familia |
> | **E** — Azure, `AppHelpers`, herramientas | ⛔ **pendiente, fuera de esta rama** |
> | **F** — validación en runtime | ✅ panel y CLI en 8.4 y 8.5, cero deprecaciones |
> | **G** — cierre | ✅ |
>
> La fase E se dejó fuera a propósito: Azure no bloquea el rango de PHP, y alargar la
> rama por eso era peor que abrir otra. El barrido manual de `AppHelpers` y `Utilities`
> se da por cubierto por el lint de los 778 archivos más el recorrido completo.
>
> **El punto único de fallo que este documento anticipaba —la fase F— no se materializó**,
> y la razón es que la fase A abrió sondeando 8.5. Lo que sí apareció fue lo contrario de
> lo esperado: el riesgo no estaba en elFinder ni en mPDF, sino en el manejador de errores
> del propio `bootstrap.php`.

Objetivo final: **`dev` corriendo y validado en el rango 8.4.1 – 8.5**, con los cuatro
paquetes `piecesphp/*` publicados y alineados. *(El objetivo original decía 8.3; el piso
se fijó en 8.4.1 al decidirse la fase A.)*

El plan está ordenado por dependencias, no por calendario. Cada fase termina en una
**puerta** verificable: mientras no cierre, la siguiente no empieza.

---

## Reparto del trabajo

`piecesphp/database` **no tiene dependencias de librerías en runtime** — solo PHP y
extensiones —, así que sus 10 suites se pueden ejecutar en un entorno sin `vendor/`.
Eso permite verificar buena parte del trabajo antes de que llegue a tu máquina.

| Verificable fuera de tu máquina | Solo en tu máquina |
| :-- | :-- |
| Redacción y aplicación de cambios en los 5 repos | Publicar paquetes (git tag + Packagist) |
| Suites de los paquetes en 8.3 y 8.4 | Ejecución en 8.5 |
| PHPStan, Rector, `php -l`, revisión de diffs | `composer install/update` |
| `.agents/context` de los 4 paquetes | Recorrido manual del panel |
| | Pruebas contra la cuenta real de Azure |

---

## Alcance

Queda fuera de esta ruta:

| Fuera | Por qué |
| :-- | :-- |
| **Guzzle 8** | Topado por `hubspot/api-client`. No depende de PHP; se aborda cuando aparezcan errores |
| **Limpieza de módulos** | No tiene fecha. Rama aparte, ver [14-deuda-y-limpieza.md](./14-deuda-y-limpieza.md) |
| **Suites nuevas del framework** | Solo se añade lo mínimo que cubra lo que se toca |

**Azure sí entra** (fase E). Al revisar el código resultó ser mucho menor de lo estimado:
el SDK abandonado se usa para **una sola operación de lectura**, repartida en 183 líneas
entre `BlobStorageAzureAdapter` y `BlobStorageFileAzurePackage`.

---

## Fases

### Fase A — Piso 8.4 y sondeo de 8.5

> **Piso decidido el 2026-08-20: 8.4**, no 8.3. Se controlan todos los despliegues, así
> que la matriz baja a dos versiones y el margen llega a dic-2028. El techo sigue en
> `<8.5` hasta que exista `piecesphp/database` v3.1.0 (fase C).

| | |
| :-- | :-- |
| **Se prepara** | Diffs de `composer.json` para los 5 repos · script de arranque multiversión sobre los PHP ya instalados · checklist de humo |
| **Se ejecuta** | **Primero: cargar el panel bajo `php8.5` y guardar la salida, aunque reviente** · aplicar diffs · `composer update` · probar exportación a Excel y `gulp sass-all` |
| **Puerta** | La aplicación arranca en 8.4 · Symfony 6.4 → 7.4 aplicado · hay registro de cómo se comporta hoy bajo 8.5 |

> **Todo `composer` de este repo corre con `php8.4`.** El requisito raíz es `>=8.4 <8.5`
> y Composer lo valida contra el binario que lo ejecuta: con `php8.5` se niega a
> resolver. `php8.5` solo entra donde no hay Composer de por medio (sondeo y `php -l`).

El sondeo contra 8.5 es lo más valioso de esta fase: convierte el riesgo de la fase F en
información de la fase A, y puede reordenar las fases D y E.

#### Resultado del sondeo — 2026-08-20

Y en efecto, las reordena. **El hallazgo que manda sobre todo lo demás no es una
deprecación concreta, sino cómo las trata el framework.**

##### Toda deprecación es fatal

[`bootstrap.php:108-133`](../../src/app/core/bootstrap.php) mete `E_DEPRECATED` en la
tabla de niveles y luego hace `$stopExcutionErrors = array_keys(...)`, así que **cada
deprecación se promueve a `ErrorException` y se lanza**. No es un aviso ignorable:
mata la petición.

El primer sondeo (`php8.5 index.php cli help`) murió en la **primera** deprecación que
encontró —`Config.php:712`, `ReflectionProperty::setAccessible()`— dentro de
`set_config`, antes de registrar una sola ruta. Para obtener el inventario hubo que
degradar temporalmente esa promoción.

Agravante: la deprecación de cast no canónico se emite **en tiempo de compilación**, al
cargar el archivo, sin ejecutar la función que lo contiene. Comprobado. Es decir, bajo
8.5 **autocargar cualquiera de los 9 archivos con `(double)` mata la petición**.

**Consecuencia para el plan**: la fase D deja de ser «limpieza mecánica» y pasa a ser
**bloqueante duro**. No se puede validar nada en 8.5 —fase F— hasta que esté hecha.

##### Inventario (11 sitios, 49 ocurrencias, solo con `cli --local help`)

| Dónde | Qué | Fase |
| :-- | :-- | :-- |
| `Config.php:712` | `ReflectionProperty::setAccessible()` | D |
| 8 controladores (ver lint) | Cast no canónico `(double)` | D |
| `piecesphp/database` `EntityMapper.php:1406` | `DateTime::__construct()`: null a `$datetime` | **B** |
| `piecesphp/database` `SchemeCreator.php:88` | `setAccessible()` | **B** |

`EntityMapper.php:1406` **no estaba en el diagnóstico** de
[16](./16-plan-php85.md): son 3 puntos en `database`, no 2. Va al camino crítico.

##### Lint de los 778 archivos propios

`php8.5 -l` **con `-d error_reporting=E_ALL -d display_errors=1`** — sin esos flags
descarta las deprecaciones en silencio y da un falso «limpio»:

- **Cero errores de sintaxis.** El código propio parsea bien en 8.5.
- **10 deprecaciones de compilación**: los 9 `(double)` —incluido
  `Sitemap/Sitemap.php:80`, que el sondeo en runtime no ve porque nunca se carga— y
  `$http_response_header` en `Http/HttpClient.php:186`.

El lint cubre los 778 archivos; el sondeo solo los que se ejecutan. **Son
complementarios, no redundantes**: el sondeo aporta las deprecaciones de runtime
(`setAccessible`, `DateTime`), que el lint no puede ver.

### Fase B — `piecesphp/database` v3.1.0

| | |
| :-- | :-- |
| **Se prepara** | Correcciones de deprecaciones · restricción canónica `">=8.3 <9.0"` · Rector y PHPStan al rango nuevo · CHANGELOG · las 10 suites verificadas en 8.3 y 8.4 |
| **Se ejecuta** | Revisar el diff · correr las suites en 8.5 · `git tag v3.1.0` · publicar |
| **Puerta** | v3.1.0 disponible en Packagist |

Camino crítico: hasta que exista, el framework no puede subir el techo.

### Fase C — Los otros tres paquetes y el techo del framework

| | |
| :-- | :-- |
| **Se prepara** | Restricción canónica en `datastructures`, `geojson` y `html` · `phpunit.xml` para `html` · suite mínima para `geojson` (hoy no tiene) · los cuatro `.agents/context/` |
| **Se ejecuta** | Publicar los tres (orden: `datastructures` → `html`; `geojson` independiente) · en el framework: `composer require piecesphp/database:^3.1`, techo a `<8.6`, `composer update` en 8.5 |
| **Puerta** | `composer why-not php 8.5` no devuelve nada |

### Fase D — Barrido de deprecaciones del framework

| | |
| :-- | :-- |
| **Se prepara** | Las ~25 correcciones aplicadas **en commits separados por familia** (casts, `setAccessible`, cierres de recurso, nullables, `$http_response_header`) · Rector actualizado al piso nuevo |
| **Se ejecuta** | Revisar commit por commit · `bin/phpstan` · smoke del panel |
| **Puerta** | PHPStan no sube respecto a la línea base |

Commits por familia, no un diff único: si algo rompe, se revierte solo esa familia.

#### Ejecutada el 2026-08-20 — 13 sitios, no 11

El diagnóstico de [16](./16-plan-php85.md) contaba 2 `setAccessible`. Son **3**:
`src/index.php:338` faltaba, dentro del closure que resuelve el prefijo de nombres de
ruta. No apareció en el sondeo porque su `if (property_exists(...))` no se cumplió.

| Familia | Sitios | Commit |
| :-- | --: | :-- |
| `(double)` → `(float)` | 9 en 7 archivos | `43a75317` |
| Borrar `Reflection*::setAccessible()` | 3 | `631f096b` |
| `$http_response_header` → `http_get_last_response_headers()` | 1 | `72cf4ae4` |

Notas de ejecución:

- **Las tres familias no se comportan igual.** Los casts se emiten en **compilación**
  (basta autocargar el archivo); `setAccessible` y `$http_response_header` en **runtime**
  (solo si el camino se ejecuta). Por eso el lint ve los primeros y no los segundos, y el
  sondeo al revés.
- `http_get_last_response_headers()` **no es un reemplazo idéntico**: devuelve `null` si
  la petición no llegó a hacerse, donde la variable mágica simplemente no se creaba. Se
  comprobó además que no arrastra estado entre peticiones, así que no hace falta
  `http_clear_last_response_headers()`.
- Validado con la suite integrada en el piso:
  `php8.4 index.php cli --local unit-tests:core/http-client` → 5/5.

#### Estado de la puerta

| | Comprobación | Resultado |
| :-- | :-- | :-- |
| a | `grep -rnE '\((double\|integer\|boolean\|binary\|real)\)' src/app src/index.php` | ✅ vacío |
| b | `php8.5 -d error_reporting=E_ALL -d display_errors=1 -l` × 778 archivos | ✅ vacío |
| c | Sondeo en runtime con la promoción activa | ⛔ **bloqueada por la fase B** |

**(c) no puede cerrarse todavía, y no por culpa del código propio.** Bajo 8.5 la
aplicación muere en `vendor/piecesphp/database/src/Core/Database/SchemeCreator.php:88`,
alcanzado desde `OrganizationsRoutes.php:50` → `routes.php:137` → `index.php:806`.

Dato nuevo: **`SchemeCreator` se instancia durante el registro de rutas**, así que
dispara en *toda* petición, no solo en operaciones de esquema. Ningún recorrido en 8.5
es posible hasta que exista v3.1.0.

Lo que sí demuestra la fase D: antes, el primer fallo en 8.5 estaba en código propio
(`Config.php:712`); ahora está en `vendor`. La aguja salió de nuestro código.

##### Inventario para la fase B — barrido de los 4 paquetes propios

`datastructures`, `geojson` y `html` están **limpios**. Todo está en `database`, y son
**4 sitios, no los 3** que contaba [16](./16-plan-php85.md):

| Sitio | Familia | Cuándo dispara |
| :-- | :-- | :-- |
| `SchemeCreator.php:88` | `setAccessible` | runtime, en registro de rutas |
| `ORM/Fields/DataProcess.php:205` | `(double)` | compilación, al autocargar |
| `ORM/Fields/DataProcess.php:209` | `(double)` | compilación, al autocargar |
| `EntityMapper.php:1406` | `DateTime` con `null` | runtime |

### Fase E — `AppHelpers`, `Utilities`, herramientas y Azure

| | |
| :-- | :-- |
| **Se prepara** | Barrido manual de los 100 KB que Rector tiene en `skip` · ~~PHPStan con `phpVersion: {min, max}`~~ **hecho en fase A** · ~~`cast.*` reactivados~~ **refutado, ver [16](./16-plan-php85.md)** · ~~`phpstan/phpstan-deprecation-rules`~~ **hecho en fase A** · `BlobStorageAzureAdapter` y `BlobStorageFileAzurePackage` reescritos sobre `azure-oss/storage-blob`, con `read()` como `getBlob` directo y la recursión de `blob()` corregida |
| **Se ejecuta** | ~~`composer require --dev` de la regla nueva en `bin/tools/`~~ **hecho en fase A** · `composer require azure-oss/storage-blob` y `composer remove microsoft/azure-storage-blob` · `bin/phpstan` · probar la lectura contra la cuenta real de Azure |
| **Puerta** | PHPStan verde en el rango completo con deprecaciones activadas · lectura de blob funcionando · cero paquetes abandonados en `composer audit` |

Aquí aparecerán deprecaciones que hoy nadie ve, porque PHPStan no las reporta.

### Fase F — Validación en runtime

| | |
| :-- | :-- |
| **Se prepara** | Script de humo del CLI (`help`, `clean-all`, `db-backup`, `run-cronjobs`, `process-queue`, `scan-missing-lang`) · checklist de recorrido del panel · configuración que vuelca deprecaciones a un log aparte |
| **Se ejecuta** | Recorrido en **8.3 y 8.5**: login, panel, CRUD de Publications admin y público, subida con recorte, Excel, PDF, correo, gestor de archivos · CLI completo |
| **Puerta** | El log de deprecaciones queda vacío en las dos versiones |

**Es la fase que puede desbordarse**: la primera en que la aplicación corre de verdad
sobre 8.5 de punta a punta.

### Fase G — Cierre

| | |
| :-- | :-- |
| **Se prepara** | Correcciones de lo que salga de la fase F · entrada de `CHANGELOG.md` · `APP_VERSION` · docs de despliegue actualizadas · `.agents/context` al día |
| **Se ejecuta** | Merge a `dev` · tag · sincronizar ramas |
| **Puerta** | `dev` corriendo y validado en **8.4.1 – 8.5** |

---

## Riesgos de la ruta

| Riesgo | Mitigación |
| :-- | :-- |
| La fase F destapa algo grave | Si es elFinder, la salida es eliminar `FileManager` en vez de arreglarlo (ya es candidato en [14](./14-deuda-y-limpieza.md)) |
| `azure-oss/storage-blob` no cubre el caso de uso | Es una sola lectura; la alternativa es REST con SAS |
| Publicar un paquete se atasca | Bloquea la fase C y todo lo que sigue: conviene empezar por la fase B |
| Aparecen deprecaciones en `AppHelpers.php` | Está fuera de Rector por diseño; el barrido es manual y dirigido por patrones |

**El punto único de fallo es la fase F.** Todo lo anterior es preparación sobre una
versión que aún no se ha ejercitado a fondo. Por eso la fase A abre con el sondeo contra
8.5: descubrir cómo falla al principio vale más que descubrirlo al final.
