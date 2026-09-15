# 12 — Convenciones y reglas de trabajo

## Los tres roles — decidido por el PROPIETARIO

Se usan **desde ahora** en todo lo que se escriba aquí: documentos, mensajes de commit y notas.

| Rol | Qué hace |
| :-- | :-- |
| **PROPIETARIO** | Decide y aprueba |
| **ARQUITECTO** | Diseña, mide, verifica y redacta las instrucciones |
| **CODER** | Implementa y mide |

**Existen para que un documento pueda decir quién hizo algo y quién lo aprobó sin depender de
nombres de herramienta.** Una decisión atribuida a una marca envejece con la marca; atribuida a un
rol, sigue leyéndose dentro de diez años.

> **`CLAUDE.md` no se renombra**: es un nombre de archivo que una herramienta concreta busca, no un
> rol.

## Idioma: la regla de oro

| Qué | Idioma |
| :-- | :-- |
| Clases, métodos, variables, constantes, namespaces | **Inglés** |
| Tablas y columnas de base de datos | **Inglés** |
| Nombres de rutas y permisos | **Inglés** (kebab-case) |
| Textos de UI, mensajes, validaciones | **Español**, envueltos en `__($grupo, 'Texto')` |
| Comentarios y docblocks | **Español** (es lo que hay en todo el código) |
| Mensajes de commit | **Español** |

## Nomenclatura

| Elemento | Convención | Ejemplo |
| :-- | :-- | :-- |
| Módulo (carpeta y namespace) | `PascalCase` | `News`, `SystemApprovals` |
| Clase de rutas | `<Modulo>Routes` | `NewsRoutes` |
| Clase de idiomas | `<Modulo>Lang` | `NewsLang` |
| Controlador | `<Entidad>Controller` | `NewsCategoryController` |
| Mapper | `<Entidad>Mapper` | `NewsReadedMapper` |
| Modelo de sistema | `<Nombre>Model` | `UsersModel` |
| Grupo de idioma de módulo | `kebab-case` | `news-lang` |
| Nombre base de ruta | `kebab-case` | `news-admin` |
| Sufijo de ruta | `kebab-case` | `-forms-edit`, `-actions-delete` |
| Propiedades de BD | `camelCase` | `newsTitle`, `createdAt` |
| Archivos JS de vista | `kebab-case` | `add-form.js`, `delete-config.js` |

> `BaseController` asocia automáticamente `XController` ↔ `App\Model\XModel`.
> En los módulos esto se sobreescribe asignando `$this->model` en el constructor.

## Mayúsculas y minúsculas — decidido por el propietario

**Este documento decía «el código va en inglés» y no decía nada de esto, y eso era
peligroso**: cualquiera que vea 145 funciones globales en `snake_case` puede pensar «rezago»,
lanzarse a normalizarlas y **romper la plantilla para todos los despliegues**. La excepción
tiene que estar escrita **con su razón**, no solo la regla.

| Elemento | Convención | Estado |
| :-- | :-- | :-- |
| **Funciones globales** | **`snake_case`** | **CORRECTO. NO SE TOCA** |
| **Columnas de base de datos** | **`camelCase`** | **Decisión cerrada.** Ocho excepciones, y la lista no crece |
| Clases, interfaces, traits, enums | `PascalCase` | Ya está bien |
| Constantes | `UPPER_SNAKE` | Ya está bien |
| Métodos | `camelCase` | 1 pendiente |
| Propiedades | `camelCase` | 90 pendientes |
| Variables y parámetros | `camelCase` | El grueso |

### Las dos excepciones, con su razón

**Funciones globales en `snake_case`: es lo correcto, no un rezago.** Es la tradición de PHP,
y estas funciones **viven al lado de `array_map` y `file_get_contents`** en el mismo espacio
global. **PSR-1 exige `camelCase` para MÉTODOS y no dice nada de funciones.** Normalizarlas
rompería la API pública que cada despliegue usa en sus vistas.

> ## ⚠ ANULADA EL 2026-09-02 POR EL PROPIETARIO — LÉASE ESTO ANTES QUE LO DE ABAJO
>
> **Las ocho columnas con guion bajo SÍ SE RENOMBRAN**, en un bloque propio al final de la
> campaña, justo antes de la MAJOR.
>
> **La razón que sostenía la decisión de abajo era la compatibilidad** —«una migración contra
> cada despliegue congelado»—, y esa razón **ya estaba muerta cuando se escribió esto**: el
> PROPIETARIO había decidido que *«todo lo que hemos hecho ya es rompedor… ergo todo esto quedará
> en la MAJOR»*, con su consecuencia literal, *«"esto sería un cambio incompatible" DEJA DE SER
> UN FRENO»*. Está en `20-contrato-de-trabajo.md`, sección «Aclaración del PROPIETARIO que cambia
> el cálculo de TODO lo que queda». **Nadie la propagó hasta aquí, y por eso ARQUITECTO propuso
> el renombrado sin saber que contradecía una decisión cerrada. Ver LEY 32.**
>
> **Lo que SOBREVIVE de lo de abajo, y sigue vigente:**
>
> - **Toda columna NUEVA se escribe en `camelCase`.** Sin excepción.
> - **La medición descuenta las tablas de las suites** y dice cuántas descontó.
> - **La lista de ocho no crece.** Ahora, además, encoge a cero.
>
> **Lo que CAMBIA**: la lista deja de ser una excepción permanente y pasa a ser el inventario del
> bloque de renombrado. Migrar los despliegues anteriores a la campaña queda como trabajo
> posterior, decidido así por el PROPIETARIO: *«no veo problema en hacer al final un migrado de
> versiones anteriores a esta nueva, pero es problema del futuro»*.

**Columnas de base de datos: `camelCase`. DECISIÓN CERRADA, sin condición de retirada.**
*(Superada. Se conserva porque su medición y su razonamiento siguen siendo la fuente del bloque
de renombrado — LEY 14: la corrección se escribe aparte y enlazada, no se borra el original.)*

Las ocho con guion bajo son estas, y **la lista está cerrada**:

```
created_at        modified_at       extra_data        failed_attempts
first_lastname    second_lastname   user_id           username_attempt
```

**Medido así**, para que la cifra sea reproducible:

```sql
SELECT DISTINCT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()
```

Da **151 columnas distintas y 10 con guion bajo**. Las dos que sobran —`avatar_blob` y
`secret_key`— son de `pcs_unit_tests_core_database_exporter_v1`, **la tabla que crea la suite
del exportador**, no del esquema real. Descontadas, quedan las ocho de arriba.

> **Y de ahí sale una regla, porque tener que descontar a mano una tabla de prueba es un
> aviso: UNA TABLA DE UNA SUITE VIVIENDO EN LA BASE DE LA APLICACIÓN FALSEA CUALQUIER
> MEDICIÓN DE ESQUEMA.** Dos mitades, las dos obligatorias:
>
> 1. **Toda suite que cree tablas las nombra con un prefijo reconocible** — el mismo criterio
>    que ya se exige para las filas que crea.
> 2. **Toda medición de esquema las descuenta explícitamente**, y dice cuántas descontó.
>
> La segunda no es opcional. Sin ella, la primera solo vuelve el ruido más fácil de reconocer;
> no lo saca de la cifra, y la cifra es lo que se publica.

**Por qué se quedan como están**: renombrar una columna es una **migración contra cada
despliegue congelado**, y el framework es una plantilla que se clona. El precio de la
coherencia no compensa el de una migración que hay que ejecutar en sitios que no controlamos.

**Y la mitad que mira adelante, que es la que importa**: **toda columna NUEVA se escribe en
`camelCase`.** La lista de ocho **no crece**.

> Esto está escrito porque es lo único que impide que dentro de seis meses alguien vea ocho
> columnas con guion bajo, las tome por un rezago y las «arregle» — rompiendo todos los
> despliegues a la vez. **No es deuda: es una excepción cerrada con su razón.**

*(Ojo, son cosas distintas: las PROPIEDADES de los mappers también son `camelCase`, y ahí no
hay excepciones.)*

## Etiquetas de versión

**Aprobado por el PROPIETARIO el 2026-09-02.** Mirando las 79 etiquetas de `piecesphp` preguntó
si el versionado estaba mal. Estaba mezclado, que no es lo mismo.

**HACIA ADELANTE, Y SIN EXCEPCIONES:**

```
vX.Y.Z          siempre TRES partes, siempre con la `v`
vX.Y.Z-beta.N   pre-lanzamiento
```

**La MAJOR de esta campaña será `v8.0.0`. Nunca `v8`** — que es exactamente lo que se hizo con
`v3` y con `v4`, y es lo que ciega a las herramientas.

**LAS 79 ETIQUETAS EXISTENTES NO SE TOCAN.** Son el registro, alguien puede haber fijado una, y
reescribirlas sería rehacer la historia para que cuadre con la regla nueva. Se documentan:

| Forma histórica | Ejemplos | Qué hacer |
| :-- | :-- | :-- |
| `vX.Y.Z` | `v7.1.0`, `v5.20.1` | es la buena |
| `vX.Y` | `v2.7`, `v4.1`, `v5.18`, `v5.2` | se deja; equivale a `X.Y.0` |
| `vX` | `v3`, `v4` | se deja; equivale a `X.0.0` |
| sin prefijo | `4.0.1`, `5.2` | se deja |
| `-beta` | `v6.4.2-beta`, `v7.0.0-beta` | **correctas**, y así se seguirán haciendo |

**LA ANOMALÍA, y se nombra para que nadie la tome por un patrón**: `v6.4.200001`,
`v6.4.200002` y `v6.4.201`. Bajo `version_compare`, **`v6.4.201` es MAYOR que `v6.4.4`**:
invierten el orden de su propia serie. Sean lo que fueran —número de compilación, una fecha—,
**no se repiten**.

**Y LO QUE ESTO OBLIGA EN LAS HERRAMIENTAS** (LEY 15 y LEY 16): cualquier herramienta que elija
«la última etiqueta» **acepta las cinco formas, las normaliza a tres partes antes de comparar,
ignora los pre-lanzamientos a propósito, y DICE CUÁNTAS DESCARTÓ Y POR QUÉ**. Una que filtre en
silencio da la respuesta correcta hasta el día que no.

### CÓMO SE APLICA: al pasar, nunca en barrido

> **NADA DE BARRIDOS.** Un lote dedicado de renombrado produciría el mismo desastre de
> revisión que los CRLF: diffs enormes donde se esconde lo que sí importa. **Cuando un
> archivo se toca por otro motivo, sus identificadores internos se normalizan en un COMMIT
> APARTE pegado a ese trabajo.** Mismo archivo, misma sesión, distinto commit: sin barrido y
> un eje por commit.

Y dentro de un archivo, **todas o ninguna**. Es T17: la aplicación parcial fabrica
divergencia — es exactamente lo que pasó cuando Rector convirtió 25 de 39
`strlen($route) > 0` y dejó 14.

### CUIDADO CON LOS PARÁMETROS: no son variables locales aunque lo parezcan

Renombrar un parámetro **no es un cambio interno**:

1. **Rompe los argumentos con nombre de PHP 8.** `f(as_mapper: true)` deja de compilar.
2. **Desincroniza los phpdoc que PHPStan lee**, y esto ya nos afecta: `as_mapper` aparece
   **142 veces** en `src/app` —`grep -rc "as_mapper" src/app --include=*.php`—, y **25 de
   esas apariciones están DENTRO de docblocks**, incluidas las anotaciones de tipo de retorno
   condicional que escribimos nosotros.

> **Si renombras un parámetro, la anotación cambia EN EL MISMO COMMIT** o el tipo condicional
> deja de funcionar **en silencio** — PHPStan no avisa de que una anotación menciona un
> parámetro que ya no existe: simplemente deja de estrecharse.

### Las cifras, con su método

Medidas por **tokens**, no por `grep`. Criterio de «no es `camelCase`»: contiene `_` fuera de
un prefijo `_` inicial, **o** empieza por mayúscula. Los métodos mágicos (`__construct`…) se
excluyen.

| Cifra | Qué es |
| --: | :-- |
| **145** | Funciones globales en `snake_case` — 81 en `AppHelpers.php`, 37 en `Utilities.php`, 21 en `Config.php`. **Se quedan.** |
| **1** | Método fuera de `camelCase` en todo el proyecto: `Core\Database\Export\Exporter::idf_escape`. **Cero en los cuatro paquetes.** |
| **90** | Propiedades fuera de `camelCase`: **38** son `UPPER_SNAKE` —constantes disfrazadas de propiedad estática, otra discusión— y **52** `snake_case` o `PascalCase`. |

**Estas cifras corrigen las que circulaban** («~143 métodos», «14 propiedades»), que no son
reproducibles con ningún criterio que se haya podido escribir. El método de arriba sí lo es.
## EL FRAMEWORK SE AUTOINICIALIZA

**Principio de arquitectura, decidido por el propietario. Gobierna medio framework y no
estaba escrito en ninguna parte.**

> **La CLI es una caja de herramientas, NO un desplegador obligatorio.** Un despliegue
> clonado tiene que arrancar solo.

Consecuencia práctica, que es la que hay que aplicar al escribir código:

- Una funcionalidad que necesite datos de arranque **los materializa ella misma**, en un
  camino de escritura, **de forma idempotente**: escribe una vez y converge.
- Una tarea de CLI puede ofrecerse como **atajo o para lotes**, nunca como requisito para que
  un despliegue funcione.

Ejemplos vivos, para reconocer el patrón y no confundirlo con un defecto:

| Dónde | Qué materializa |
| :-- | :-- |
| `AppConfigController::seo()` | La configuración SEO de cada idioma **activo** al pintar la vista: es el camino de activación de idiomas |
| `OTPSecretsUsersMapper::setOTP()` | La fila del código de un solo uso, si falta |
| `OTPSecretsUsersMapper::toggle2FA()` | La fila TOTP y su secreto, al activar el segundo factor |

**Y el límite, que es lo que separa esto del defecto D2:** materializar va en los caminos de
**ESCRITURA**, nunca en los de **LECTURA**, y nunca en una ruta alcanzable sin autenticar. Un
buscador devuelve `null` cuando no encuentra; quien lee lo aguanta. Ver
`.agents/context/18-siguientes-ventanas.md`, T3.
## Cuando una supresión se lleva el último ejemplo, el mecanismo se documenta EN EL MISMO COMMIT

**La documentación por ejemplo es documentación.** Un mecanismo del framework que solo se conoce
porque hay un sitio donde se usa se vuelve invisible el día que ese sitio se borra — y borrarlo
suele ser correcto, porque el código que se va sobra.

**La regla**: si un cambio retira el último uso —o el más visible— de un mecanismo, **ese
mecanismo se documenta en el mismo commit**. No en el siguiente, no «cuando toque»: en el mismo,
porque el momento en que se entiende es justo ese.

> **Se aplicó hacia atrás** al borrado de `QueueJobMapper::migrate()`, que era el único oyente vivo
> de `EVENT_INIT_ROUTES` —el que queda en `config.php:131` es un `//Do something` vacío—. El
> despachador se documentó después, en [03-ciclo-de-vida.md](./03-ciclo-de-vida.md); debió ir en
> aquel commit.

## Reglas duras (romperlas rompe el framework)

1. **Rutas**: siempre `PiecesPHP\Core\Route` / `RouteGroup`. Nunca `$app->get(...)`
   directo. El sistema de permisos depende del nombre de ruta.
2. **URLs**: siempre `Controller::routeName()` o `get_route()`. Nunca concatenar
   strings de URL.
3. **Visibilidad en menús**: siempre `Controller::allowedRoute(...)` o
   `Roles::hasPermissions(...)`.
4. **Todo método de ruta devuelve un `Response`.** Si no, el error se reporta como
   `MissingResponseInController`.
5. **Textos visibles siempre por `__()`** con un grupo de idioma, nunca hardcodeados.
6. **Assets**: `add_global_asset` / `set_custom_assets` / `import_*`, nunca
   `<script src>` a pelo en la vista.
7. **Configuración**: `get_config()` / `set_config()`. Recuerda que la BD
   sobrescribe a `config.php`.
8. **Un módulo nuevo se activa con una constante** en `config/constants.php` y se
   lee como `const ENABLE = X_MODULE;` en su clase `Routes`.
9. **Estáticos de módulo**: SCSS en `Statics/sass/`, nunca editar el `.css`
   generado.
10. **No se edita `src/vendor/`** ni los paquetes `piecesphp/*`; viven en repos
    aparte.

## Estilo de código

- **Las propiedades van ARRIBA, antes del primer método.** Todas: públicas, protegidas,
  privadas y estáticas. Una propiedad escondida entre dos métodos no se encuentra leyendo
  la cabecera de la clase, que es donde todo el mundo mira para saber qué guarda.
  **Lo comprueba `bin/cli verify-integrity` (comprobación 15) en los cinco repositorios**;
  las propiedades promovidas del constructor no cuentan, porque no se pueden mover.
- PHP 8.1+ compatible hasta 8.4 (hay una rama `updagre-to-php84` en curso).
- Docblocks en todas las clases con `@package`, `@author`, `@copyright`, y
  `@property` para las propiedades mágicas de los mappers. **Los mappers dependen
  de `@property` para el autocompletado y para PHPStan.**
- Separadores decorativos usados en todo el código:
  ```php
  //──── GET ───────────────────────────────────────────────────────────────
  //========================================================================
  ```
- `.editorconfig`: UTF-8, 4 espacios (tabuladores solo en `.js`) y `end_of_line = lf` en
  todo, igual que `.gitattributes` (ADR 0012). `.yaml` y `.neon` van con espacios: YAML no
  admite tabuladores para sangrar.
  - **Trampa: cambiar la política de finales de línea deja el índice con el `stat` sucio.**
    - `git status` marca todo lo reescrito, pero `git diff` no enseña nada,
      `update-index --refresh` no lo arregla y `--really-refresh` da «needs update».
    - Arreglo: `git add --renormalize` SOLO de las rutas sin cambio de contenido, con el
      criterio de que `git diff --cached` no añada nada. Medido en `#060`-`#062`, en los cinco
      repositorios.
  - **Toda herramienta que lea rutas de git usa `-z`:** git cita las rutas no ASCII
    (`core.quotepath`). `bin/normaliza-eol` fallaba con 10 rutas con tilde hasta `c90c8e51`.
  - **`secure-keys/` se queda en CRLF y fuera de `normaliza-eol`,** porque esa carpeta no se
    toca. Por eso `bin/normaliza-eol` sin argumentos da siempre sus 3 archivos como fuera de
    forma: es esperado.
- Excepciones propias por módulo en `Exceptions/`: convención `SafeException`
  (error controlado, mostrable al usuario) y `DuplicateException`.

## Calidad

- `bin/phpstan` antes de dar por cerrado un cambio grande; revisar
  `PHPStanResult.Summary.txt`.
- `bin/rector` para refactors automatizados.
- Pruebas: `bin/cli unit-tests:<suite>` (ver `.agents/context/21-pruebas-y-puertas.md`).
- Al terminar una funcionalidad, **añadir la entrada en `CHANGELOG.md`** con el
  formato existente (encabezado `# X.Y.Z (DD-MM-AAAA)` y viñetas por área en
  negrita).

## Git

- Trabajo diario en `dev`; ramas temáticas (`limpieza-modulos`, `redesign`,
  `updagre-to-php84`, `modificacion-docs`) se sincronizan con
  `git checkout <rama> && git merge dev`.
- `master` y `last-stable` se actualizan desde `dev` para publicar.
- Tres remotos: `origin`, `origin2`, `origin3`. Se hace push de `--all` y `--tags`.
- Mensajes de commit en español, con prefijos tipo `feat:` / `fix:` cuando aplica
  (el historial es mixto; hay commits con mensaje `-`).

## Despliegue

- `permissions-and-property.sh` (raíz) y `src/permissions.sh` ajustan permisos y
  propiedad. Ver `source-docs/project/docs/piecesphp/content/permissions.md`.
- El paquete de actualización se arma con el `zip -r9 ... UPDATE.zip` documentado en
  `IGNORE.md`, o con `bin/cli bundle all=yes zip=yes`.
- Se excluyen del despliegue: `.git`, `node_modules`, lockfiles, `src/vendor`,
  `src/adminer`, `src/statics/{filemanager,uploads}`, `src/app/{logs,cache}`,
  `source-docs`, `README.md`, `CHANGELOG.md`, `TODO.md`, `IGNORE.md`,
  `PHPStanResult.*`, `secure-keys/`, `bin/tools/vendor`.

## Seguridad

- **Nunca hardcodees secretos.** Usa `getKeyFromSecureKeys()` (lee de `secure-keys/`)
  o las configuraciones de BD.
- `app_key` firma tokens y cifrado: cambiarla invalida sesiones.
- Subir `SessionToken::setMinimumDateCreated(...)` en `roles.php` invalida todas las
  sesiones activas — útil tras un incidente.
- Cabeceras de seguridad (CORS, CSP, HSTS) y bloqueo de archivos sensibles están en
  `src/.htaccess`.
- **Nunca `chmod 777`** en producción; usa SetGID `2775`/`664` en los directorios
  escribibles.
- `IGNORE.md` está en `.gitignore`: es un bloc de notas local, no versionado. No
  muevas su contenido (comandos, credenciales personales) a archivos versionados.

### La interpolación que NO se «arregla» con `prepare()`

`SET lc_time_names = '…'` en `LcTimeNamesTrait` va interpolado y **tiene que ir así**:
`SET` no admite parámetros preparados. Comprobado que no es inyectable: el idioma solo se
usa como CLAVE de búsqueda, y el valor sale siempre de la lista blanca
`lc_time_names_mysql` de `config/lang.php`, que nadie construye en ejecución. Quien lo
convierta a parámetro rompe la sentencia. Ver T145.

Regla general: una interpolación forzada se anota EN EL SITIO con por qué no es
inyectable. Si no se puede escribir esa frase, es que sí lo es.

## Cuando trabajes en este proyecto

1. **Lee primero `Publications`**: es la referencia canónica del proyecto — el
   módulo más completo y el más mantenido. Cubre zona admin y pública,
   sub-entidad, adjuntos, traducción de campos, caché y aprobaciones.
   `News` sirve como ejemplo mínimo solo-admin, pero está marcado «por renovar»:
   no lo uses como referencia de estilo.
2. Copia su estructura literalmente: nombres, sufijos de ruta, orden de métodos.
3. Si las reglas de negocio del encargo son ambiguas, **pregunta antes de escribir
   código** (regla explícita del skill `full-stack-php-senior` del proyecto).
4. Consulta `CHANGELOG.md` para saber qué cambió recientemente, y `TODO.md` /
   `IGNORE.md` para el trabajo pendiente.
