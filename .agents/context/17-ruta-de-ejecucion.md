# 17 — Ruta de ejecución: PHP 8.3 → 8.5

Plan de ejecución para [16-plan-php85.md](./16-plan-php85.md).
Estado: **propuesta**. Última revisión: 2026-08-20.

Objetivo final: **`dev` corriendo y validado en el rango 8.3 – 8.5**, con los cuatro
paquetes `piecesphp/*` publicados y alineados.

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

### Fase A — Piso 8.3 y sondeo de 8.5

| | |
| :-- | :-- |
| **Se prepara** | Diffs de `composer.json` para los 5 repos · script de arranque multiversión sobre los PHP ya instalados · checklist de humo |
| **Se ejecuta** | **Primero: cargar el panel bajo `php8.5` y guardar la salida, aunque reviente** · aplicar diffs · `composer update` · probar exportación a Excel y `gulp sass-all` |
| **Puerta** | La aplicación arranca en 8.3 · Symfony 6.4 → 7.4 aplicado · hay registro de cómo se comporta hoy bajo 8.5 |

El sondeo contra 8.5 es lo más valioso de esta fase: convierte el riesgo de la fase F en
información de la fase A, y puede reordenar las fases D y E.

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

### Fase E — `AppHelpers`, `Utilities`, herramientas y Azure

| | |
| :-- | :-- |
| **Se prepara** | Barrido manual de los 100 KB que Rector tiene en `skip` · PHPStan con `phpVersion: {min, max}` · `cast.*` reactivados en `ignoreErrors` · `phpstan/phpstan-deprecation-rules` · `BlobStorageAzureAdapter` y `BlobStorageFileAzurePackage` reescritos sobre `azure-oss/storage-blob`, con `read()` como `getBlob` directo y la recursión de `blob()` corregida |
| **Se ejecuta** | `composer require --dev` de la regla nueva en `bin/tools/` · `composer require azure-oss/storage-blob` y `composer remove microsoft/azure-storage-blob` · `bin/phpstan` · probar la lectura contra la cuenta real de Azure |
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
| **Puerta** | `dev` corriendo y validado en 8.3 – 8.5 |

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
