# Estado del proyecto — 2026-09-14

Informe para el PO antes de empezar a trabajar sobre el framework. Qué es la campaña, qué se
lleva, qué falta hasta la MAJOR, qué va después y qué son solo ideas.

**Fuentes y método:**
- La escalera de fases: `.agents/context/18-siguientes-ventanas.md`, T34.
- El estado de cada fase: el 18 y el 20 §7.
- El mapa: `.agents/docs/roadmap.md`.
- Los encargos y las decisiones: `.agents/docs/pendientes.md`.
- Lo que va después de la MAJOR: `.agents/docs/roadmap-posterior/`.
- Las cifras de git, medidas hoy con `git log --since=2026-08-19`.

**Límites:**
- **Actualizado al cierre de la jornada, 2026-09-14 16:26.** Las cifras de las puertas son del
  último reporte del coder (`#027`).
- Nada del mapa está re-medido: cada lote se mide al instruirlo (LEY 17).

> **LO PRIMERO, LEE ESTO:** hay una **inyección SQL confirmada por lectura en dos rutas públicas**
> del framework (`publications-ajax-all` y `built-in-banner-ajax-all`, parámetro `title`). Viaja
> a cada clon. Su arreglo es lo primero de la próxima jornada (lote 3a). Detalle en la sección 8.

---

## 1. En una página

- **Qué es.** La campaña de calidad de PiecesPHP, que empezó el 2026-08-19. Su meta es una
  **MAJOR, `v8.0.0`**, que rompe compatibilidad a propósito y deja el framework «sin trampas
  embarcadas» para quien lo clone.
- **Tu criterio para la MAJOR.** Entra lo que **corrige** una trampa; lo que **extiende** una
  capacidad va después. Y *«la major depende de que terminemos toda la campaña, toda es toda»*.
- **Dónde estamos.**
  - Terminadas **E0, E1, E2 y E3**.
  - **E4** (pruebas) está abierta: tiene hecho su primer lote y dos trabajos pendientes.
  - Por el camino se hizo una larga serie de seguridad de acceso y de SQL (bloques AH a BC).
  - Faltan **E5** (refactorizaciones), **E6** (documentación) y los lotes rompedores del final.
- **Lo que queda hasta la MAJOR: 16 lotes** (entraron el 3a y el 5b; salieron el 1 y el 2),
  de unos 20 a 25 bloques de trabajo según las estimaciones del mapa; sin fechas, por tu regla.
- **Hoy:**
  - empezó el modelo de tres roles;
  - se cerraron los lotes 1 (BD) y 2 (identificadores de SQL);
  - se hizo el primer bloque del 3 (la auditoría de las subidas).

---

## 2. Las fases (la escalera de T34)

| Fase | Qué es | Estado |
| --- | --- | --- |
| **E0** | Cierre de la migración a PHP 8.5 y del instrumental | **Cerrada**. La última etiqueta es `v7.1.0` |
| **E1** | Cierre de calidad: PHPStan, supresiones con motivo | **Cerrada** (`18` T41) |
| **E2** | La foto: recorrido de rutas y ciclo de altas, ediciones y borrados por módulo, contra una base restaurable | **Cerrada** (`18` T135) |
| **E3** | Limpieza en seis lotes, con base restaurada y foto antes y después de cada uno | **Cerrada** (`18` T143; de 35 tablas a 29). Dejó residuos: lote 6 del mapa |
| **E4** | Pruebas, elegidas por consecuencia: guardas, contratos de retorno no obvios, idas y vueltas. Y la ventana de correo | **Abierta.** Lote 1 hecho: 33/33 guardas de acceso (`18` T147). **Faltan** el lote 2 de guardas y la ventana de correo (lotes 7b y 7c del mapa) |
| **E5** | Tus refactorizaciones | **Pendiente.** La principal: unificar los importadores (lote 8) |
| **E6** | Documentación: `source-docs/` completo, la API, el frontend, las guías | **Pendiente** (lote 9) |
| **MAJOR** | `v8.0.0` en `master` y `last-stable` | Al final. Es un punto serio: se habla antes |

Fuera de la escalera, pero dentro de la campaña, están los bloques **AH a BC** (2026-08-31 a
2026-09-14). Tratan el acceso, la tríada de permisos y el SQL concatenado, y dejaron cero
concatenaciones con valor de la petición bajo trinquete. Lo que queda de esa línea son los
lotes 2 a 5 del mapa.

---

## 3. Lo que llevamos

**En números** (medidos hoy):

| Qué | Cifra |
| --- | --- |
| Commits en `piecesphp` desde el 2026-08-19 | 401, 54 de ellos hoy |
| Commits en los paquetes, en el mismo periodo | 82: `database` 33, `datastructures` 16, `geojson` 16 y `html` 17 |
| Leyes escritas, cada una con el fallo que la fundó | 33 (`context/19-leyes.md`) |
| Comprobaciones de `bin/cli verify-integrity` | **28**, en verde. Hoy entraron la 27 (identificadores de SQL) y la 28 (interpolación) |
| Suites de `bin/cli gates` | 25 (2 no corren porque declaran efectos externos) |
| Errores de PHPStan (nivel 8) | 744, igual a la línea base. Eran 877 el 2026-08-21 (`18` T0). Los cuatro paquetes miden ya con la misma versión, la 2.2.12 |
| SQL con valor de la petición, en las formas que miden los censos | 0 concatenaciones, 0 identificadores y 6 interpolaciones declaradas, las tres cifras bajo trinquete. **Pero los censos no siguen un valor de un método a otro, y así se escapó la inyección de la sección 8** |
| Rupturas registradas para la MAJOR | 14 (`CHANGELOG.md`, «CAMBIOS INCOMPATIBLES») |

**Lo más importante que se hizo:**
- **Migración a PHP 8.5**, cerrada: el piso sube a 8.5 y Composer deja de depender del binario.
- **Instrumentos**: `verify-integrity`, `gates`, 10 censos con canario, la foto de E2 y los
  trinquetes. Son la red que impide que un cambio rompa algo sin avisar.
- **Limpieza de E3**: módulos muertos fuera, de 35 tablas a 29.
- **Seguridad**:
  - las dos capas de acceso, medidas;
  - la tríada de permisos en las 41 controladoras;
  - las guardas de acceso con prueba;
  - el SQL de los listados por marcador;
  - las correcciones de `SystemApprovals` y `MySpace`.
- **Hoy**:
  - el traspaso a este modelo de trabajo;
  - la herencia destilada (el cruce de tus 466 mensajes);
  - una razón de ser por carpeta;
  - `dev` en los cuatro paquetes.
  - **BD** (bitácora 0005): los cuatro paquetes miden con phpstan 2.2.12. `database` baja de 21
    a 18 y `html` de 3 a 1 porque el analizador deja de ver algunos errores, y queda registrado
    así, no como arreglo. `html` estaba anclado a una versión vieja de `datastructures`.
  - **Lote 2** (bitácora 0006): un censo nuevo de identificadores de SQL da 0; la dirección de
    `custom_order` se normaliza; los censos dejan de contar dos veces; y dos comprobaciones
    nuevas, la 27 y la 28, las dos vistas fallar.
  - **Lote 3, bloque 1**: la auditoría de los datos de las nueve carpetas de subidas, en solo
    lectura. De ahí sale P24.
  - **P22 y P23**, resueltas por delegación tuya: el ADR 0008 y el lote 5b.

---

## 4. Lo que falta hasta la MAJOR (el mapa, en orden)

Descrito en `.agents/docs/roadmap.md`, con el puntero de cada lote. Las estimaciones de bloques
son del mapa del 2026-09-13.

| # | Lote | Qué es | Bloques | Pide algo al PO |
| --: | --- | --- | --: | --- |
| ~~1~~ | ~~BD~~ | **Cerrado hoy** (bitácora 0005) | — | — |
| ~~2~~ | ~~Identificadores~~ | **Cerrado hoy** (bitácora 0006). No hizo falta lista blanca: la cifra es 0 y la vigila la comprobación 27 | — | — |
| **3a** | **⚠ Búsquedas concatenadas** | **Nuevo y urgente.** La inyección de la sección 8: `PageQuery` pasa a admitir valores ligados, y las cinco búsquedas van por marcador | 1 | No |
| 3 | **Subidas** | Bloque 1 hecho (la auditoría). Falta el bloque 2: enchufar el control de acceso de cada carpeta y una puerta que falle si una queda sin declarar | 1 | **Sí: P24** |
| 4 | **`escapeString`** | El único escapado del framework depende de un `sql_mode` que nadie fija | 1 | No |
| 5 | **OTP** | Límite de intentos por usuario e IP y respuesta uniforme, sin pasar a POST | 1 | No |
| **5b** | **Tokens genéricos** | **Nuevo** (de P22). Los enlaces de `GenericTokenController` llevan un número calculable; la ruta es pública y borra tokens de cualquier tipo (SOSPECHA fuerte) | 1 | No |
| 6 | **Residuos de E3** | Tablas y código del módulo de experiencias que el borrado dejó | 1 | No |
| 7 | **Avatares y `see-more`** | Muere el creador de avatares; `see-more` se arregla | 1 | No |
| 7b | **E4 · guardas, lote 2** | Pruebas de rechazo de las ~22 guardas que fallarían abiertas | 1-2 | No |
| 7c | **E4 · correo** | Pruebas de los 10 envíos en tres capas | 1-2 | **Sí**: Mailpit o MailHog y los buzones de prueba |
| 8 | **E5 · importadores** | Unificar `Importers` y `DataImportExportUtility` como base de la que se parte, con ejemplos que funcionan | 1-2 | No |
| 9 | **E6 · documentación** | `source-docs/` completo, la API y Postman, la arquitectura del frontend, los 9 selectores, la guía de módulos, el cierre de PHPStan en dos listas | 2-3 | No |
| 10 | **Residuos con nombre** | Barrido final, con `SOLO_PROPIAS` en Publications | 1-2 | No |
| 11 | **Usuarios a `classes/`** | El núcleo pasa a la disposición de módulos. **Rompe** | sin medir | No |
| 12 | **Renombrado de columnas** | 8 columnas y 247 referencias, con el registro de renombrados. **Rompe** | 1 | No |
| 13 | **Borrado del registro 18** | Lo que solo vive ahí sube a los documentos; el 18 se disuelve | 1 | No |
| 14 | **La MAJOR** | `v8.0.0` | 1 | **Sí**: punto serio |

**Tareas pequeñas ya anotadas**, que entran en el lote que toque el sitio (`pendientes.md`):

- El trinquete de declaradas del censo de SQL concatenado solo falla por exceso. El del
  interpolado ya es exacto desde hoy.
- **Rutas públicas que devuelven lo borrado**: `publications-ajax-all` y
  `built-in-banner-ajax-all` aceptan `status=ANY` sin mirar quién pregunta (H3 de `#027`).
- En `custom_order`, una columna se descarta si es subcadena de otra. No es un fallo de
  seguridad: el orden por defecto no se aplica.
- La comprobación 26 no debe dar verde si falta la sección que lee.
- El mecanismo de la LEY 33 como comprobación.
- El bloque de rupturas del CHANGELOG está partido en dos.
- `21-pruebas-y-puertas.md` está desfasado.
- La lista de «Correcciones al registro».
- Retirar la librería de vídeo `php-ffmpeg-video-streaming`, que aprobaste el 2026-08-20.
  Tocar Composer lo autorizas tú en su bloque.

---

## 5. Decisiones que esperan de ti (no bloquean nada hoy)

| Qué | Desde | Qué hago si no contestas |
| --- | --- | --- |
| **P24 · Qué control de acceso lleva cada carpeta de subidas.** Hoy Apache sirve directamente, a quien tenga la URL, los archivos de `documents` (cualquier tipo, con el nombre original), `organizations` (el RUT y el logo) y `news-categories`. `publications` está «protegida» con un validador que deja pasar a todos | 2026-09-14 | Sesión activa para `documents`, `organizations` y `news-categories`. En `publications`, el archivo se sirve si su publicación es visible al público o hay sesión. El banner, sin proteger, porque lo muestra la portada, pero sin devolver borrados. La imagen de inicio de `generic`, sin proteger. Se retiran las tres carpetas que no guardan nada, y entra una puerta que falle si una carpeta queda sin declarar |
| **El geovisor**: ya sé cuál es (`espacio-publico-backend`); falta qué quieres perfeccionar de él | 2026-08-29 | Nada: sin tu descripción no se puede medir |
| **El francés**: restos en `profiles-translation-config.js` y en `dynamic-translations/fr/` | 2026-08-30 | Quedan como están |
| **El rol 50 con nombre `null`** (`roles.php`) | — | Queda como está |
| **`Components`**: te inclinas por conservarlo; el 14 lo lista como «eliminar o promover» | 2026-08-21 | Se conserva |
| **La dirección de la unificación de importadores**: el 18 dice una y tú dijiste la otra | 2026-08-29 | Hacia `DataImportExportUtility`, como dijiste el 29 |
| **El versionado**: dijiste que aún no estabas listo para versionar y que lo hablaríamos | 2026-08-26 | Se habla antes de la MAJOR |

---

## 6. Después de la MAJOR (lo que extiende, no corrige)

**Documentos en `.agents/docs/roadmap-posterior/`:**

| Documento | Tipo |
| --- | --- |
| Los registros: cuatro, y por qué | **Decisión del arquitecto**, delegada por ti. 5 bloques. Empieza por el inventario de todo lo que merece registro |
| Correo: log, configuración y salida profesional | Tu pedido, medido |
| EventsLog: robustecerlo | Tu pedido. Se solapa con los registros |
| El migrador: una herramienta | Tu pedido: llevar una instalación anterior a la versión nueva |
| `EntityMapper` contra `ORM` | Medición. El ORM va después de la MAJOR, «quizás en otra mayor» |
| Seguridad y operación: cuatro revisiones | Tus pedidos: errores (con aviso visible y bandera de tres modos), cifrado, autenticación y tokens de API |
| Actualización de DataTables (v1.13.5 → 2.3.7) | Plan técnico, sin estado |
| El framework como paquete y su despliegue | **Idea**. Incluye la herramienta en Packagist que despliega sin lo de desarrollo |
| El módulo como patrón mecanizable | **Idea** |
| Guías de estilo por lenguaje | **Idea** |
| Mejorar el CLI y sus ayudas | **Idea** |
| Requisitos de la guía personal | **Idea**: que recuperes el gobierno del framework |
| Vista «Sistema» en el panel | **Idea** |
| Una caché de verdad («una bala») | **Meta**, no una tarea |
| Los silencios de Sass | Nota medida, en el tintero |
| Cronjobs y colas: dos ideas | **Ideas** |

**Recuperado en el cruce y aún sin documento propio** (`pendientes.md`):
- un flujo guiado para dar de alta traducciones, más fácil para los agentes, y el GUI de
  traducciones;
- la skill de aterrizaje («descubrir proyectos PiecesPHP» sin quemar tokens);
- la revisión del ORM en funcionamiento y en estética;
- las guías de migración y de uso del ORM, que vivirían en el repositorio `database`.

**Del andamiaje, a nombrar cuando quieras:**
- llevar el modelo nuevo a los cuatro paquetes;
- adaptar la skill `full-stack-php-senior`: que sea 100 % tuya y que pierda la regla que sobra.

---

## 7. Solo ideas o fuera del repositorio

- **La evaluación personal que pediste**: no entra en el registro, por tu orden. Incluye
  explicarte los instrumentos.
- **«Perfeccionar geovisor»**: un recordatorio sin contenido todavía (sección 5).
- **Tu `TODO.md`**: PayU, rehacer los módulos de imágenes, de noticias internas y del
  temporizador, un módulo de encuestas con app, y un archivo de opciones JSON para el front.
  Es tuyo y no está planificado.

---

## 8. Riesgos y deuda que conviene tener presentes

- **⚠ Inyección SQL en rutas públicas: CONFIRMADA POR LECTURA.** Sin provocar, porque no hay
  permiso de peticiones HTTP ni de base de datos.
  - `publications-ajax-all` y `built-in-banner-ajax-all` no piden sesión.
  - Meten el parámetro `title` sin escapar en `LIKE UPPER('%…%')`, y `PageQuery` lo ejecuta sin
    marcadores (`PublicationsController.php:1438`, `BuiltInBannerController.php:996`,
    `PageQuery.php:92-96`).
  - **SOSPECHA razonable, sin provocar:** un visitante sin cuenta podría leer datos de la base
    de un despliegue a través de esas rutas, por ejemplo con `UNION`, y lo mismo en cada clon
    que no las haya cambiado.
  - Tras sesión, el mismo patrón en tres sitios: News, Organizations y GeoJSON.
  - **Si tienes un despliegue en producción con estas rutas, conviene saberlo ya.** El arreglo
    es lo primero de la próxima jornada (lote 3a).
- **Se sube cuando tú quieras.** Hay 24 commits sin empujar en `piecesphp`, según la referencia
  local de `origin/dev`.
  - `database` tiene 2 y `geojson` tiene 1.
  - En `datastructures` y `html`, la rama `dev` aún no existe en el remoto.
- **Los lotes 11 y 12 rompen compatibilidad.** Van al final y con su entrada de CHANGELOG.
- **La capa 2 de la ventana de correo** necesita un servicio nuevo (Mailpit o MailHog), y la
  capa 3 buzones públicos: nunca con credenciales vivas (`18` T7).
- **El registro 18** todavía guarda cosas vivas, como el propósito de `processFromQuery`. Antes
  de disolverlo hay que subirlas a los documentos (lote 13).

---

## 9. Cómo se sigue

1. **Próxima jornada, lo primero, el lote 3a**: `PageQuery` admite valores ligados y las cinco
   búsquedas van por marcador, cada una con su prueba de rechazo vista fallar. Al empezar, las
   dos órdenes `/rename`.
2. Después, **el bloque 2 de subidas** con tu respuesta a P24, o con el predeterminado si no la
   hay. Y el mapa en su orden.
3. Me detengo solo para lo tuyo: push, versionar `piecesphp`, dependencias y servicios (el
   correo), bases de datos, ramas y puntos serios.
4. En cada cierre de tramo te dejo el resumen en `.agents/estado/tramos/`.

---

## 10. Lecciones de proyectos derivados (añadido el 2026-09-14)

Leídos en solo lectura, a petición tuya, para aprender de la experiencia y no para copiar:
el geovisor de `espacio-publico-backend`, el backoffice de `stc-website-2026` y el log de tokens
de `localizometro-stc`. El detalle está en `.agents/docs/pendientes.md`, «Lecturas de proyectos
derivados». Lo esencial:

- **P22, resuelto por delegación:** la constante no es la frontera, porque el JWT se lee
  siempre de la base de datos. Pero al medirla apareció el defecto de `GenericTokenController`,
  que es el lote 5b.
- **Geovisor:** la lección es de cliente. Clústeres, marcadores solo para lo visible y datos
  sin HTML. En el servidor tampoco allí está resuelto (carga por encuadre, paginación, caché).
- **Registros:** registrar el ciclo de vida de cada credencial es una idea que el plan de cuatro
  registros no tenía.
- **Correo:** vista previa con el mismo código que envía, envío de prueba registrado y escáner
  de correos sin catalogar.
- **Tres sospechas sobre nuestro código**, por verificar en sus lotes: rutas con `{x:regex}`,
  decodificar imágenes por su extensión y `withPersonsProfiles`.
