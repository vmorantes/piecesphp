# 16 — Plan: subir el piso de PHP y llegar a 8.5

Ruta para modernizar el rango de PHP soportado por PiecesPHP, combinada con la
limpieza de [14-deuda-y-limpieza.md](./14-deuda-y-limpieza.md).

Documento de planificación. Estado: **propuesta, sin ejecutar**.
Análisis: 2026-08-19. Base: `limpieza-modulos`, `APP_VERSION v7.0.6`,
`composer.json` con `php: >=8.1 <8.5`.

---

## El dato que cambia la decisión

Estado del soporte de PHP a agosto de 2026:

| Versión | Fin de soporte de seguridad | Situación hoy |
| :-- | :-- | :-- |
| **8.1** | 31-dic-2025 | 🔴 **Sin parches desde hace 8 meses** |
| **8.2** | 31-dic-2026 | 🟠 Le quedan ~4 meses |
| **8.3** | 31-dic-2027 | 🟢 |
| **8.4** | 31-dic-2028 | 🟢 |
| **8.5** | 31-dic-2029 | 🟢 Actual (8.5.9) |

Mantener compatibilidad con 8.1 no es prudencia: es sostener soporte para un
runtime que ya no recibe parches de seguridad. Y 8.2 muere antes de que termine el
año, así que fijar el piso ahí obliga a repetir este trabajo en meses.

**El piso mínimo defendible es 8.3.**

---

## Elegir el piso

| Piso | Versiones a probar | Vence | Disponibilidad en servidores | Qué desbloquea |
| :-- | :-- | :-- | :-- | :-- |
| 8.2 | 4 (8.2–8.5) | dic-2026 | Debian 12 por defecto | `readonly` en clases, tipos DNF, constantes en traits |
| **8.3** | **3 (8.3–8.5)** | **dic-2027** | **Ubuntu 24.04 LTS por defecto** | + constantes de clase tipadas, `#[\Override]`, `json_validate()`, acceso dinámico a constantes de clase |
| 8.4 | 2 (8.4–8.5) | dic-2028 | Requiere repositorio externo (ondrej) | + property hooks, visibilidad asimétrica, `new` sin paréntesis, `array_find/any/all` |

### Recomendación

**Piso 8.3, techo 8.5.** Razones:

1. Es el PHP por defecto de **Ubuntu 24.04 LTS**, que es el sistema que documenta
   `source-docs/project/docs/piecesphp/content/general.md`. Instalable sin añadir
   repositorios de terceros, lo que importa cuando el servidor es del cliente.
2. Soporte hasta finales de 2027: da un año y medio de margen antes del próximo salto.
3. Baja la matriz de pruebas de 5 versiones a 3. Ese es el ahorro real, más que
   cualquier característica del lenguaje.

**Sube a 8.4 si controlas todos los despliegues.** Con HestiaCP y el repositorio de
ondrej, 8.4 está disponible, la matriz baja a 2 versiones y el margen llega a 2028.
Es la opción agresiva y es defendible; solo no la elijas si algún cliente tiene un
servidor que no puedes tocar.

> Con cualquiera de los dos pisos, **el código se escribe al nivel del piso**, no al
> del techo. Piso 8.3 significa que no entra sintaxis de 8.4 ni de 8.5 —
> ni property hooks, ni pipe `|>`, ni `clone with`, ni `array_first()`.
> Las funciones nuevas de 8.4/8.5 sí se pueden polirrellenar en `AppHelpers.php`
> con `function_exists()`.

---

## Diagnóstico

### El código propio está casi listo

Barrido contra las deprecaciones reales de 8.2 a 8.5:

| Deprecación | Versión | Ocurrencias | Dónde |
| :-- | :-- | --: | :-- |
| Casts no canónicos `(double)` | 8.5 | **9** | `ImagesRepositoryController:197`, `ApplicationCallsController:473`, `OrganizationsController:481,492`, `MyProfileController:232,243`, `AppConfigController:1993`, `TimerController:52`, `Sitemap.php:80` |
| `Reflection*::setAccessible()` | 8.5 | **2** | `Config.php:712`, `BaseEntityMapper.php:159` |
| `curl_close`, `finfo_close`, `imagedestroy` | 8.5 | **6 archivos** | curl y GD |
| `$http_response_header` | 8.5 | **1 archivo** | — |
| Nullable implícito | 8.4 | **3** signaturas | El resto de coincidencias son docblocks `@param` |
| `${}` en cadenas | 8.2 | **0** reales | La única coincidencia es una plantilla JS en un `.php` |
| `utf8_encode` / `utf8_decode` | 8.2 (eliminadas) | **0** | — |
| `__sleep` / `__wakeup`, backticks, `SplObjectStorage`, `FILTER_DEFAULT`, `DATE_RFC7231`, `mysqli_execute` | 8.5 | **0** | — |

**~25 puntos de corrección**, casi todos mecánicos. El código no es el problema.

### El trabajo real está en las dependencias

**Auditoría ejecutada el 2026-08-20.** Esto ya no es estimación: es la salida del
resolvedor de Composer.

```
$ composer why-not php 8.5
piecesphp/piecesphp dev-master requires php (>=8.1 <8.5)
piecesphp/database  v3.0.4     requires php (>=8.1 <8.5)

$ composer why-not php 8.3
There is no installed package depending on "php" in versions not matching 8.3
```

**Solo hay dos bloqueantes para 8.5, y los dos son tuyos.** Ninguna dependencia de
terceros impide 8.5. Y **para 8.3 no hay ningún bloqueante en absoluto**: basta cambiar
el `>=8.1` de tu propio `composer.json`.

| Paquete | Instalado | Situación |
| :-- | :-- | :-- |
| **`src/composer.json`** | `>=8.1 <8.5` | 🔴 Techo autoimpuesto |
| **`piecesphp/database`** | v3.0.4, `>=8.1 <8.5` | 🔴 Techo autoimpuesto en tu paquete |
| `bin/tools/composer.json` | `>=8.1 <8.5` | 🔴 Igual, para las herramientas |
| `microsoft/azure-storage-blob` | 1.5.4 | 🟠 **Abandonado, sin reemplazo sugerido.** Sí se usa |
| `guzzlehttp/guzzle` | 7.15.3 (hay **8.0.2**) | 🟠 Major disponible, sin evaluar |
| `studio-42/elfinder` | 2.1.70 | 🟠 Código antiguo; solo lo usa `FileManager` |
| `aminyazdanpanah/php-ffmpeg-video-streaming` | v1.3.1 | 🟡 **No bloquea.** Eliminable por limpieza — ver abajo |
| Resto del árbol | — | 🟢 `composer audit`: sin avisos de seguridad |

### Los cuatro paquetes propios: repos, estado y contexto propio

Los cuatro tienen ya su propio `.agents/context/`, con el detalle de arquitectura, API y
trabajo pendiente de cada uno. Este documento solo coordina.

| Repo | LOC | Pruebas | Bloquea 8.5 | Trabajo de código | Contexto |
| :-- | --: | :-- | :-- | :-- | :-- |
| `/var/www/html/vicsen/database` | 12.787 | **10 suites** (`bin/cli`) | 🔴 **Sí** | 3 puntos | `.agents/context/` (6 docs) |
| `/var/www/html/vicsen/html` | 2.449 | 1, **sin `phpunit.xml`** | No | ninguno | `.agents/context/` (3 docs) |
| `/var/www/html/vicsen/geojson` | 1.080 | **ninguna** | No | ninguno | `.agents/context/` (3 docs) |
| `/var/www/html/vicsen/datastructures` | 596 | 3 (PHPUnit) | No | a verificar | `.agents/context/` (3 docs) |

Hallazgos que cambian el plan:

- **`database` tiene diez suites de prueba.** El riesgo de la migración baja bastante:
  el ORM, que es el componente del que todo depende, sí está cubierto. Corren con
  `bin/cli unit-tests --test="all"`, no con `phpunit`.
- **`geojson` no tiene ninguna prueba** y `html` tiene una que no se puede ejecutar sin
  `phpunit.xml`. Son los dos puntos ciegos del ecosistema.
- **El código de los cuatro está casi limpio.** Solo `database` tiene deprecaciones
  reales: 2 casts `(double)` en `ORM/Fields/DataProcess.php:205,209` y un
  `setAccessible()` en `SchemeCreator.php:88`.
- **Un patrón compartido a revisar**: `ArrayOf::__construct($input = [])` en
  `datastructures` y `ArrayObjectExtend::__construct($array = [])` en `database` no
  declaran tipo, y PHP 8.5 deprecó construir `\ArrayObject` a partir de un objeto.
  Hay que decidirlo una vez y aplicarlo en los dos, porque `html` hereda de ambos.
- **`database/bin/refactorization/Rector.php` está desalineado**: el comentario dice
  «compatibilidad de salida a PHP 8.1», el código dice
  `$rectorConfig->phpVersion(PhpVersion::PHP_84)` y `composer.json` declara `>=8.1`.
  Rector puede emitir sintaxis de 8.4 que rompa en 8.1–8.3. Corregir antes de volver a
  ejecutarlo.
- **`geojson` documenta mal su propio namespace**: el `README.md` dice
  `PiecesPHP\GeoJSON`, el real es `PiecesPHP\GeoJson`. En Linux, copiar el `use` del
  README falla al autocargar.

### Los cuatro paquetes propios: dos estilos incompatibles

Todos los `piecesphp/*` los mantienes tú, así que sus restricciones son una decisión de
diseño, no un dato externo. Hoy no son coherentes entre sí:

| Paquete | Restricción declarada | Rango real |
| :-- | :-- | :-- |
| `piecesphp/database` v3.0.4 | `">=8.1 <8.5"` | `>=8.1 <8.5` ← **techo duro** |
| `piecesphp/datastructures` v3.0.0 | `"^8.4.0 \| ^8.1.0"` | `>=8.1 <9.0` |
| `piecesphp/geojson` v2.0.0 | `"^8.4.0 \| ^8.1.0"` | `>=8.1 <9.0` |
| `piecesphp/html` v2.0.0 | `"^8.4.0 \| ^8.1.0"` | `>=8.1 <9.0` |

Dos cosas a corregir:

1. **`"^8.4.0 | ^8.1.0"` es redundante.** `^8.1.0` ya significa `>=8.1.0 <9.0.0`, así que
   subsume por completo a `^8.4.0`; la expresión entera se reduce a `>=8.1 <9.0`. Además
   usa un solo `|` en vez de `||` — Composer lo acepta, pero no es la forma canónica.
   Es exactamente el patrón que hace difícil leer una restricción de un vistazo: es lo
   que provocó el error sobre el wrapper de FFmpeg documentado más abajo.
2. **El techo duro de `database` es fricción autoinfligida.** Los otros tres no lo tienen
   y por eso no bloquean nada; `database` sí, y por eso es el único paquete tuyo que
   aparece en `why-not php 8.5`. Con un techo por minor, cada versión nueva de PHP obliga
   a publicar los cuatro paquetes más la aplicación antes de poder siquiera probar.

**Recomendación**: una sola forma canónica para los cuatro, con piso explícito y sin
techo por minor — por ejemplo `">=8.3 <9.0"` (equivalente a `"^8.3"`) si el piso queda
en 8.3.

Si prefieres conservar un techo como red de seguridad, que sea la **matriz de CI** la que
verifique el rango, no la restricción la que lo prohíba. Un techo declarado bloquea por
defecto; una matriz avisa cuando algo se rompe de verdad.

**Orden de publicación** (hay acoplamiento interno: `html` requiere
`piecesphp/datastructures: ~3`):

```
datastructures  →  html
database            (independiente)
geojson             (independiente)
```

### Subir el piso a 8.3 desbloquea Symfony 7

Simulando la resolución con el piso en 8.3:

```
$ composer config platform.php 8.3.0 && composer update --dry-run
  - Upgrading symfony/cache        (v6.4.43 => v7.4.16)
  - Upgrading symfony/filesystem   (v6.4.43 => v7.4.15)
  - Upgrading symfony/process      (v6.4.41 => v7.4.13)
  - Upgrading symfony/var-exporter (v6.4.42 => v7.4.16)
  - Upgrading maennchen/zipstream-php   (3.1.1 => 3.2.2)
  - Upgrading phpoffice/phpspreadsheet  (5.8.1 => 5.9.0)
  - Upgrading spatie/macroable          (2.0.0 => 2.1.0)
```

Symfony 7 requiere PHP ≥ 8.2, así que con el piso en 8.1 esas cuatro se quedan
congeladas en la rama 6.4. **Subir el piso a 8.3 las mueve a 7.4 solo**, sin tocar nada
más. Es exactamente el efecto que buscabas al preguntar si las dependencias podrían
migrar a versiones más recientes: sí, y el piso es lo único que se lo impide.

Nota menor: `composer outdated --direct --strict` solo listó `guzzle` y `azure`, pero el
`--dry-run` revela que `phpoffice/phpspreadsheet` y `maennchen/zipstream-php` también
tienen versión nueva. Conviene correr `composer outdated` a secas, sin `--direct`, para
ver el cuadro completo.

#### FFmpeg: no era un bloqueante — corrección

Un análisis anterior de este documento clasificó
`aminyazdanpanah/php-ffmpeg-video-streaming` como bloqueante de 8.5 leyendo su
restricción `^7.2 || ^8.0 || ^8.1 || ^8.2 || ^8.3 || ^8.4` como si terminara en 8.4.
**Es incorrecto**: `^8.0` significa `>=8.0 <9.0`, así que ya cubre 8.5 y el resto de la
enumeración es redundante. Por eso el paquete no aparece en `composer why-not php 8.5`.
Lo mismo pasa con su restricción sobre `php-ffmpeg/php-ffmpeg`
(`… || ^1.0 || ^1.1 || ^1.2 || ^1.3`): `^1.0` ya admite 1.4.

Sigue mereciendo la pena quitarlo, pero como **limpieza, no como desbloqueo**:

`aminyazdanpanah/php-ffmpeg-video-streaming` **no lo usa ninguna parte del código de
negocio**. Su única referencia en todo el proyecto es un parche de compatibilidad:

```php
// src/app/config/final-configurations-includes/patches_composer_dependencies.php
/**
 * Parche para librería aminyazdanpanah/php-ffmpeg-video-streaming
 * No declara tipos nullables.
 */
if (!function_exists('ffmpeg')) { /* redefine el helper global */ }
```

Es decir: la única razón de que exista ese archivo es que la librería **ya incumple
la deprecación de nullables implícitos de 8.4**.

Lo que sí se usa es `php-ffmpeg/php-ffmpeg` — directamente en
`API/Adapters/FfmpegAudioAdapter.php` (`use FFMpeg\FFMpeg`, `use FFMpeg\Format\Audio\Wav`)
— y hoy entra al proyecto **solo como dependencia transitiva del wrapper**. De ahí que
el wrapper parezca imprescindible: no lo es, es el acarreador.

Comprobación de que nada más lo usa:

```bash
# El namespace del wrapper no aparece en ningún sitio salvo el parche
grep -rn "Streaming" --include=*.php src/app src/index.php \
  | grep -v final-configurations-includes/patches
# (sin resultados)
```

`FixWebmDurationTask` tampoco lo usa: consume `API\Adapters\FfmpegAudioAdapter`, que a su
vez solo importa de `FFMpeg\`.

**Acción**:

```bash
cd src
composer why php-ffmpeg/php-ffmpeg          # confirma que hoy lo trae el wrapper
composer require php-ffmpeg/php-ffmpeg:^1.4
composer remove aminyazdanpanah/php-ffmpeg-video-streaming
composer why-not php 8.5                     # comprobar que ese bloqueante desapareció
rm app/config/final-configurations-includes/patches_composer_dependencies.php
```

El parche se puede borrar entero: su único contenido es el helper `ffmpeg()` del wrapper,
y `final-configurations.php` carga ese directorio por barrido, así que no hay que
desregistrar nada.

`symfony/filesystem` no se pierde: entra también por `scssphp/scssphp`.

Resultado: se van una dependencia sin uso y un archivo de parche. No es urgente, pero
mientras siga ahí obliga a mantener un parche cuya única razón de ser es que la librería
incumple una deprecación de 8.4.

#### Azure: el único caso realmente feo

`composer audit` lo confirma:

```
Found 1 abandoned package:
| microsoft/azure-storage-blob | none |
```

**«none» quiere decir que Microsoft no propuso reemplazo.** No es descuido del
mantenedor: Microsoft retiró las librerías cliente de Azure Storage para PHP el
17-mar-2024 y archivó `Azure/azure-storage-php` el 8-may-2024. No hay SDK oficial de
Azure Storage para PHP.

Y sí tiene uso real: `API/Adapters/BlobStorageAzureAdapter.php`,
`API/Adapters/Packages/BlobStorageFileAzurePackage.php`,
`API/Adapters/SpeechToTextAzureAdapter.php`, `API/APIRoutes.php`.

Instala sin problema en 8.5 (su restricción es `>=5.6.0`), así que no bloquea el plan.
El problema es de sostenibilidad: código sin mantenimiento en la ruta de lectura de
archivos.

#### La superficie real es mucho menor de lo que parecía

Revisado el código: **el SDK de Azure solo se usa para una operación de lectura.**

| Archivo | Líneas |
| :-- | --: |
| `API/Adapters/BlobStorageAzureAdapter.php` | 109 |
| `API/Adapters/Packages/BlobStorageFileAzurePackage.php` | 74 |

API del SDK realmente consumida:

```php
BlobRestProxy::createBlobService($connectionString)
$blobClient->listBlobs($container, $listBlobsOptions)   // prefix + continuation token
$blobClient->getBlob($container, $name)                  // -> getContentStream()
$blob->getName(); $blob->getProperties()                 // -> getLastModified(), getContentType()
```

No hay subida, ni borrado, ni gestión de contenedores. **Una sola operación: leer un
blob.** El resto de Azure en el proyecto (`SpeechToTextAzureAdapter`) ya va por REST con
Guzzle y no toca el SDK.

#### Decisión: migrar a `azure-oss/storage-blob`

Es el sucesor mantenido por la comunidad (organización **Azure-OSS**, «PHP OSS for
Azure»), nacido justo porque Microsoft archivó su SDK. Construido sobre **PHP 8.1+**, con
pruebas contra infraestructura real de Azure. No es oficial de Microsoft — pero es que ya
no existe uno oficial.

Se prefiere sobre escribir REST a mano porque la autenticación *SharedKey* de Azure exige
firmar cada petición con HMAC, y eso es un sitio malo para improvisar.

**Coste estimado: 0,5 – 1 día**, no los 5 que suponía antes de leer el código. Son ~180
líneas con una única operación que reescribir.

Verificar al empezar, porque no pude comprobarlo desde aquí:

```bash
composer require azure-oss/storage-blob --dry-run   # ¿qué exige de PHP y de Guzzle?
```

Si `azure-oss` también depende de Guzzle 7, el salto a Guzzle 8 no se desbloquea por este
lado — pero HubSpot lo tapa igualmente, así que no cambia nada a corto plazo.

#### Dos defectos encontrados de paso

Conviene arreglarlos en la misma pasada:

1. **Recursión infinita** en `BlobStorageFileAzurePackage::blob()`:

   ```php
   public function blob()
   {
       return $this->blob();   // debería ser $this->blob
   }
   ```

   Hoy nadie lo llama, por eso no ha estallado.

2. **`read()` lista el contenedor entero para encontrar un archivo.** Recorre `listBlobs`
   paginando con continuation token y compara nombres hasta dar con el buscado, cuando el
   nombre completo ya se conoce y bastaría un `getBlob()` directo. Es O(n) llamadas de red
   para leer un fichero. La migración es la ocasión de dejarlo en una sola petición.

#### elFinder

`studio-42/elfinder` solo lo usa el módulo `FileManager`, que
[14-deuda-y-limpieza.md](./14-deuda-y-limpieza.md) ya marca como candidato a
eliminación. Si `FileManager` se va, el riesgo se va con él. Decidir antes de la
fase 2, no después.

#### Guzzle 8: bloqueado, y Azure es parte del bloqueo

Hay major disponible (7.15.3 → 8.0.2), pero dos dependencias lo topan en la rama 7:

| Quien lo requiere | Restricción sobre Guzzle |
| :-- | :-- |
| `hubspot/api-client` v14.1.0 | `^7.3` |
| `microsoft/azure-storage-common` 1.5.2 | `~6.0 \| ^7.0` |

Además es requisito directo en `composer.json` (`^7.10`) y se usa en
`API/Adapters/SpeechToTextGroqAdapter.php` y
`API/Adapters/SpeechToTextAzureAdapter.php`.

Es decir: **el paquete abandonado de Azure es la mitad de lo que impide subir a
Guzzle 8.** Resolver Azure desbloquea la otra mitad, que depende de que HubSpot publique
una versión compatible.

Comprobarlo antes de intentarlo:

```bash
composer require guzzlehttp/guzzle:^8.0 --dry-run
```

Y en cualquier caso, **no mezclar el salto de Guzzle con el cambio de versión de PHP**:
commit aparte, para poder atribuir cualquier regresión.

### Estado de las herramientas

- **PHPStan** ^2.1, nivel 8, `phpVersion: 80400` (escalar). 1.026 errores en 192
  archivos, dominados por `argument.type` (375), `property.nonObject` (259) y
  `method.nonObject` (162) — típico de un ORM con `__get`/`__set`.
- **Rector** ^2.3, **ya configurado exactamente para este escenario**:
  `phpVersion(PhpVersion::PHP_81)` como techo de emisión, `LevelSetList::UP_TO_PHP_85`
  para detección, y reglas explícitas para nullables implícitos, `get_class()` sin
  argumentos, `${}` y `utf8_encode/decode`. Solo hay que subir el `phpVersion` al
  piso nuevo.
- **Rama `updagre-to-php84`: 0 commits por delante de `dev`.** Marcador vacío, no hay
  nada que rescatar.
- **Cobertura de pruebas: 3 suites.** El mayor riesgo del plan.

---

## Comandos de auditoría (para repetir)

La auditoría de este documento salió de estos comandos. Conviene reejecutarlos tras cada
cambio de dependencias, y en `bin/tools/` además de en `src/`:

```bash
cd src
composer why-not php 8.5                 # el decisivo
composer why-not php 8.3
composer outdated                         # sin --direct: muestra también las transitivas
composer config platform.php 8.3.0 && composer update --dry-run
composer config --unset platform.php
composer audit
```

Pendiente de ejecutar: los mismos en **`bin/tools/`**, cuyo `composer.json` también
declara `>=8.1 <8.5` y por tanto también topa las herramientas.

---

## Fases

### Fase 0 — Decidir y preparar

Sin cambios de código.

1. **Decidir el piso: 8.3 u 8.4.** Todo lo demás depende de esto. Criterio: ¿controlas
   todos los servidores donde corre PiecesPHP?
2. Rama `php-modernizacion` desde `dev`. Retirar `updagre-to-php84` (vacía).
3. **Entorno multiversión**: contenedores del piso elegido y de 8.5. Hay base en
   `source-docs/project/docs/environments/content/docker/`.
4. ~~Ejecutar la auditoría de dependencias~~ **Hecha el 2026-08-20.** Falta repetirla
   dentro de `bin/tools/`.
5. Línea base: `bin/phpstan` con la configuración actual; guardar
   `PHPStanResult.Summary.txt` como referencia. Ejecutar las 3 suites y anotar.

**Salida**: piso decidido, entornos que arrancan, y la lista real de bloqueantes.

### Fase 1 — Limpieza barata

Ver [14-deuda-y-limpieza.md](./14-deuda-y-limpieza.md), sección «Riesgo bajo».
Cada línea eliminada es una línea que no hay que auditar ni probar en tres versiones.

1. Borrar `Importers` (1.129 líneas), decidiendo antes el destino de
   `PiecesPHP\Core\Importer\*`.
2. `BaseHelperController` en el núcleo; borrar los 24 `HelperController` triviales
   (~1.000 líneas).
3. Decidir `Components`: completar o borrar (489 líneas).
4. Borrar restos de Webflow.
5. **Decidir `FileManager`.** Si se elimina, desaparece la dependencia de elFinder y
   con ella un riesgo de la fase 2.

**Puerta**: PHPStan no sube respecto a la línea base; el panel arranca.

### Fase 2 — Dependencias

1. **`piecesphp/database`**: en su repo, cambiar a `>=8.3 <8.6` (o el piso elegido),
   correr su suite en piso y techo, publicar **v3.1.0**. Actualizar aquí a `^3.1`.
   *Camino crítico. Empieza por aquí.*
2. Alinear `datastructures`, `geojson` y `html` a la **misma forma canónica**, aunque hoy
   ya resuelvan: hoy declaran `"^8.4.0 | ^8.1.0"`, que es redundante y confuso. Respetar
   el orden `datastructures → html` por el acoplamiento interno.
3. `src/composer.json` y `bin/tools/composer.json`: `"php": ">=8.3 <8.6"`.
4. `composer update` en el piso nuevo: Symfony sube solo de 6.4 a 7.4, más
   `phpspreadsheet`, `zipstream` y `macroable`. Probar exportación a Excel y compilación
   SASS, que es lo que toca esos paquetes.
5. **Azure**: sustituir `microsoft/azure-storage-blob` por `azure-oss/storage-blob`,
   reescribir `BlobStorageAzureAdapter::read()` como un `getBlob` directo, y arreglar la
   recursión de `BlobStorageFileAzurePackage::blob()`.
6. **Guzzle 8**, *en commit aparte*, y solo si `hubspot/api-client` ya lo admite y Azure
   quedó resuelto.
7. **Eliminar `aminyazdanpanah/php-ffmpeg-video-streaming`** (limpieza, no bloqueo),
   requerir `php-ffmpeg/php-ffmpeg` directamente, borrar
   `patches_composer_dependencies.php` y comprobar `FfmpegAudioAdapter` y
   `FixWebmDurationTask`.

**Puerta**: `composer install` termina limpio en piso y en 8.5, `composer why-not php 8.5`
no devuelve nada, y `composer audit` no reporta nada crítico.

### Fase 3 — Deprecaciones en código propio

1. Subir `$rectorConfig->phpVersion()` al piso nuevo.
2. Regenerar `PHPStanResult.txt` (Rector lee de ahí sus rutas).
3. Añadir a `$rectorConfig->rules([...])` las reglas de 8.5 seguras en el piso — como
   mínimo la de casts no canónicos.
4. `--dry-run`, revisar el diff, aplicar por lotes pequeños, un commit por lote.
5. A mano, lo que Rector no cubre:
   - Los 9 `(double)` a `(float)`.
   - Los 2 `setAccessible()`: en PHP ≥ 8.1 la llamada ya es innecesaria; se borran.
   - Los cierres de recurso. **Ojo con `imagedestroy`**: sí libera memoria antes de
     tiempo; en bucles de proceso de imágenes, sustituir por `unset()` del `GdImage`.
   - `$http_response_header`: pasar a `$response->getHeaders()` del `HttpClient` propio.
   - Las 3 signaturas con nullable implícito.
6. **`AppHelpers.php` y `Utilities.php` están en el `skip` de Rector.** Revisión
   manual dirigida: buscar solo los patrones de la tabla de diagnóstico.

**Puerta**: cero deprecaciones recorriendo panel y zona pública en 8.5 con `E_ALL`.

### Fase 4 — Herramientas en modo rango

1. PHPStan debe analizar el rango completo, no una versión:

   ```neon
   parameters:
       phpVersion:
           min: 80300   # el piso elegido
           max: 80500
   ```

   *Verificar la sintaxis contra la versión de PHPStan instalada antes de fijarla.*
2. **Reactivar los `cast.*` de `ignoreErrors`.** Hoy están silenciados `cast.int`,
   `cast.string`, `cast.bool`, `cast.array`, `cast.double` y `cast.float` — que es
   justo la familia de los casts no canónicos deprecados en 8.5.
3. Añadir `phpstan/phpstan-deprecation-rules` a `bin/tools`: hoy PHPStan no reporta
   deprecaciones, que son el objeto de todo este trabajo.
4. Revisar el resto de `ignoreErrors` y documentar qué silencia cada línea y por qué.

**Puerta**: PHPStan verde en el rango completo.

### Fase 5 — Validación

1. Las 3 suites en piso y en 8.5.
2. Recorrido manual con `display_errors` y `E_ALL`: login, panel, CRUD de
   Publications (admin y público), subida de imágenes con recorte, exportación a
   Excel, generación de PDF, envío de correo, y el gestor de archivos si sobrevivió.
3. CLI completo en ambas: `help`, `clean-all`, `db-backup`, `run-cronjobs`,
   `process-queue`, `scan-missing-lang`.
4. Sospechosos uno por uno: elFinder, mPDF (GD y fuentes), PhpSpreadsheet, Azure.
5. Actualizar `src/.htaccess` y la documentación de despliegue con el requisito nuevo.
6. Subir `APP_VERSION` y escribir la entrada de `CHANGELOG.md`.

**Puerta**: piso y techo completan el recorrido sin avisos.

### Fase 6 — Reestructuración (después, rama aparte)

Puntos 2, 3 y 6-9 de [14-deuda-y-limpieza.md](./14-deuda-y-limpieza.md): invertir
`BaseEntityMapper` ↔ `SystemApprovals`, mover los controladores de perfil de `MySpace`
a `Organizations`, invertir el home del backoffice, separar la capa de proyecto.

Aparte a propósito: son cambios de arquitectura y no deben compartir rama ni ventana
de pruebas con un cambio de versión de lenguaje.

---

## Riesgos

| Riesgo | Impacto | Mitigación |
| :-- | :-- | :-- |
| **Cobertura de pruebas mínima en el framework** (3 suites para 88.000 líneas) | Alto. Sigue siendo el riesgo dominante *del framework* | El ORM ya está cubierto en `piecesphp/database` (10 suites). Falta cubrir `ServerStatics` y el ciclo de rutas |
| `geojson` sin ninguna prueba, `html` con una que no se ejecuta | Medio | Escribir la suite de `geojson` y añadir `phpunit.xml` a `html` **antes** de tocarlos. Ambos son fáciles de probar: entra objeto, sale cadena |
| `piecesphp/database` no se libera a tiempo | Bloquea todo | Camino crítico; primera tarea de la fase 2 |
| Subir el piso rompe un despliegue de cliente | Alto si ocurre | Inventariar la versión de PHP de cada servidor **en la fase 0**, antes de decidir el piso |
| Azure abandonado rompe en 8.5 | Medio | Decidir en fase 2, no descubrirlo en producción |
| elFinder rompe en 8.5 | Medio | Se elimina con `FileManager` si esa decisión sale que sí |
| `AppHelpers.php` (100 KB) fuera de Rector | Medio | Revisión manual dirigida por patrones |
| Vistas con `extract()` que PHPStan no analiza | Medio | Solo se detectan en el recorrido manual de la fase 5 |

---

## Camino crítico

```
Decidir piso (8.3 u 8.4)                    fase 0.1
        |
        +--> geojson: escribir pruebas       (paralelo, independiente)
        +--> html: anadir phpunit.xml        (paralelo, tras datastructures)
        |
piecesphp/database                           fase 2.1  ← unico bloqueante externo
   corregir 2 casts + setAccessible
   alinear Rector (PHP_84 -> piso)
   10 suites verdes en piso y en 8.5
   publicar v3.1.0
        |
datastructures -> html                       (orden obligatorio, dependencia interna)
        |
composer.json x2 al rango nuevo              fase 2.3
        |
composer install limpio en 8.5               puerta fase 2
        |
todo lo demas
```

`geojson` es independiente de los otros tres: se puede publicar en cualquier momento.

Las fases 0 y 1 corren en paralelo mientras se libera el paquete. La decisión sobre Azure
puede avanzar en paralelo también: no depende de la versión de PHP.

Vía rápida, si hiciera falta valor inmediato: **subir solo el piso a 8.3** no requiere
liberar nada. Se cambia `>=8.1` por `>=8.3` en los dos `composer.json`, Symfony sube a
7.4, y se gana un runtime con parches. El techo 8.5 puede llegar después.

---

## Por dónde empezar

### Hito 0 — Subir el piso a 8.3 y publicar

**No requiere liberar ningún paquete.** `composer why-not php 8.3` no devuelve nada, y
`piecesphp/database` ya declara `>=8.1 <8.5`, que incluye 8.3.

```bash
# src/composer.json y bin/tools/composer.json
- "php": ">=8.1 <8.5"
+ "php": ">=8.3 <8.5"

composer update            # Symfony sube de 6.4 a 7.4 solo
bin/phpstan                # comparar con la línea base
```

Qué se gana de inmediato:

- Se sale de un runtime **sin parches desde hace 8 meses**.
- Symfony pasa de 6.4 (vence nov-2027) a 7.4, más `phpspreadsheet` y `zipstream`.
- Se reduce la matriz de pruebas futura de 5 versiones a 3.

Qué hay que probar: exportación a Excel y compilación SASS, que es lo que tocan los
paquetes que suben.

Es la única parte del plan con valor inmediato y sin dependencias. **Empezar por aquí.**

### Por qué no empezar borrando módulos

La intuición dice que limpiar primero reduce el trabajo de migración. **En este caso no
es cierto**, y conviene decirlo claro: la superficie de migración del framework son
~25 correcciones mecánicas en 88.000 líneas. Borrar `Importers`, los `HelperController`
y `Components` elimina ~2.600 líneas que **no contienen ninguna deprecación**. No ahorra
trabajo de migración.

Los dos frentes son casi independientes, así que el orden lo decide otra cosa:

- **La migración de PHP tiene fecha**: 8.1 ya no recibe parches y 8.2 muere el
  31-dic-2026.
- **La limpieza no tiene fecha.** Es deuda técnica, no riesgo operativo.

Además, mezclarlos en la misma rama hace ilegible el diff: no se distingue lo que rompió
un borrado de lo que rompió un cambio de versión.

**Ramas separadas, y la migración primero.**

La excepción son tres **decisiones de producto** que sí conviene tomar antes, porque
reducen lo que hay que validar en la fase 5:

| Decisión | Efecto si sale «eliminar» |
| :-- | :-- |
| ¿Se conserva `FileManager`? | Desaparece elFinder, la dependencia más antigua del árbol |
| ¿Se sigue usando Azure? | Desaparece el único paquete abandonado, y se desbloquea media subida a Guzzle 8 |
| ¿`Components` se completa o se borra? | 489 líneas menos |

Decidirlas cuesta una conversación, no trabajo. El borrado en sí puede esperar.

## Esfuerzo relativo

Sin comprometer plazos: el calendario depende de la dedicación disponible, y la variación
real está en los imprevistos de runtime, que no se pueden dimensionar hasta haber corrido
la aplicación sobre 8.5.

| Bloque | Esfuerzo | Nota |
| :-- | :-- | :-- |
| **Hito 0**: piso a 8.3 | Bajo | Sin dependencias, valor inmediato |
| Entorno multiversión | Bajo | Una sola vez |
| `piecesphp/database` v3.1.0 | Bajo-medio | Camino crítico |
| `datastructures`, `geojson`, `html` | Bajo | Restricciones y `phpunit.xml` |
| Framework: ~25 correcciones con Rector | Bajo-medio | Mecánicas, por lotes |
| `AppHelpers.php` y `Utilities.php` a mano | Medio | 100 KB fuera de Rector |
| PHPStan en rango + `cast.*` + deprecaciones | Bajo | |
| Migrar Azure a `azure-oss/storage-blob` | Bajo | Una sola operación de lectura, ~180 líneas |
| Validación en 8.3 y 8.5 | Medio | **Aquí se esconde el tiempo** |
| Imprevistos: elFinder, mPDF/GD, PhpSpreadsheet | **Impredecible** | La única incógnita seria |

Trabajo relacionado, fuera del camino principal:

| Bloque | Esfuerzo | Nota |
| :-- | :-- | :-- |
| Suite de pruebas de `geojson` | Bajo | Recomendable antes de tocarlo |
| Pruebas del framework (`ServerStatics`, rutas) | Medio-alto | Reduce el riesgo dominante |
| Guzzle 8 | Medio | Topado por HubSpot; se aborda al aparecer errores |
| Limpieza de módulos | Medio | Sin fecha, rama aparte |

### Lectura

- Casi todo el camino principal es **trabajo mecánico y acotado**. Lo que no se puede
  acotar de antemano son los imprevistos de runtime en 8.5.
- Por eso conviene **sondear 8.5 el primer día** aunque falle: convierte la incógnita
  grande en información temprana.
- Azure dejó de ser una incógnita al leer el código: una única operación de lectura sobre
  ~180 líneas.

### Sobre el piso: 8.3 o 8.4

Los datos de la auditoría dicen que **el esfuerzo es idéntico**: 8.4 no desbloquea
ninguna dependencia que 8.3 no desbloquee ya (Symfony 7 solo exige 8.2). La elección es
únicamente de disponibilidad en servidores:

- **8.3** si algún despliegue está en un servidor que no controlas — es el PHP por
  defecto de Ubuntu 24.04 LTS.
- **8.4** si controlas todos — una versión menos que probar y margen hasta dic-2028.

Ninguna de las dos cambia la estimación.

## Resumen para decidir

- **8.1 lleva 8 meses sin parches** y 8.2 muere en 4. Mantener 8.1 no aporta nada.
- **Piso recomendado: 8.3.** Por defecto en Ubuntu 24.04 LTS, vence dic-2027, matriz de
  3 versiones, y **sin ningún bloqueante**: `composer why-not php 8.3` no devuelve nada.
  Además mueve Symfony de 6.4 a 7.4 sin trabajo extra. 8.4 solo aporta características
  de lenguaje y una versión menos que probar; en dependencias no desbloquea nada que 8.3
  no desbloquee ya.
- **Para 8.5 hay exactamente dos bloqueantes, y los dos son tuyos**: el `composer.json`
  del proyecto y `piecesphp/database`. Ninguna dependencia de terceros estorba.
- El **código propio** necesita ~25 correcciones mecánicas, y Rector ya está configurado
  para casi todas.
- **Azure**: migrar a `azure-oss/storage-blob`. Mucho menor de lo que parecía: el SDK
  abandonado solo se usa para una operación de lectura.
- El **riesgo real** no es PHP 8.5: es no tener con qué comprobar que nada se rompió.
