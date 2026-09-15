# Protocolo de colaboración Arquitecto ↔ Coder

Este proyecto se trabaja con tres roles separados (ADR 0001). Esta regla define el contrato
entre ellos. **Complementa `00-core.md`, `40-salvaguardas.md`, el `CLAUDE.md` de la raíz y las
reglas de oficio de `.agents/context/20-contrato-de-trabajo.md` §3; no los reemplaza.** Si esta
regla y el 20 se contradicen, gana esta y el 20 se corrige.

## Roles

| Rol | Hace | NO hace |
| --- | --- | --- |
| **Product Owner** | Decide qué se construye, en qué orden y con qué alcance. Autoriza lo irreversible. | No lee instrucciones ni reportes. |
| **Arquitecto** | Explora y mide en solo lectura, decide, escribe TODA la documentación y la configuración de agentes, emite instrucciones, mantiene `.agents/estado/`. | No edita código ni instrumentos. No ejecuta nada que cambie estado. No commitea. |
| **Coder** | Edita código e instrumentos, ejecuta, mide, verifica y commitea (también la documentación del arquitecto, sin editarla). | No decide arquitectura. No escribe documentación. |

Qué es de quién:

| Del arquitecto | Del coder |
| --- | --- |
| `.agents/` entero, `.claude/`, `AGENTS.md`, `CLAUDE.md` | `src/` (nunca `src/vendor/`), `bin/`, `databases/` |
| `CHANGELOG.md`, `README.md`, `source-docs/` (con la API en `source-docs/api/`) | Líneas base y artefactos de instrumentos: `files/dev/`, que solo guarda datos de máquina (ADR 0006), y `PHPStanResult.*` |
| Los textos de `files/` (por ejemplo `files/Webflow/Intrucciones.md`) | Configuración del repositorio (`.gitignore`, `.gitattributes`, `.editorconfig`), cuando la instrucción lo diga |

La configuración del repositorio que solo sirve al andamiaje de agentes (el `.gitignore` de
la guarda, el final de línea del hook de git) la escribe el arquitecto con su ADR.

### Los cuatro paquetes hermanos

`database`, `datastructures`, `geojson` y `html`, en `/var/www/html/vicsen/`. Ojo: `database`,
en singular; `databases/` es la carpeta del esquema SQL de este repositorio. Son repositorios
aparte que mantiene el PO, y el framework los consume por Composer (`piecesphp/*` en
`src/composer.json`). **Un cambio en un paquete no llega al framework hasta que el paquete se
versiona** (18 T129). En los paquetes se etiqueta con soltura (PO, 2026-08-27, confirmado el
2026-09-14); subir al remoto sigue siendo cosa del PO.

- **Se trabaja en un paquete solo si la instrucción nombra el repositorio**, y desde la sesión
  de este. Su propio `.agents/` y su `.claude/` son del modelo anterior y no tienen guarda
  (ADR 0003): una sesión abierta dentro de un paquete no lleva estas reglas.
- **Todo va por repositorio**:
  - PASO 0 con `git -C <ruta> branch --show-current` y `git -C <ruta> status --short`;
  - los cuatro números, con `bin/guarda-add <previsto> --repo=<ruta>`;
  - un commit en cada repositorio, nunca uno que mezcle dos.
- **En los paquetes, `master` es su estable** (PO, 2026-09-14). Medido ese mismo día:
  - los cuatro tienen `master` en uso, con el árbol limpio, y ahí está su historia reciente;
  - `database` y `geojson` tienen además `dev`, y `database` tiene `branch-v2.0.1`;
  - últimas etiquetas locales: `database` v4.1.0, `datastructures` v4.0.0, `geojson` v3.0.0 y
    `html` v3.0.0.

  **La dinámica, decidida por el PO el 2026-09-14 (P19)**: todos los paquetes llevan `dev`,
  homologada con `master`; se trabaja en `dev`, se fusiona a `master` (su estable) y ahí se
  etiqueta. Homologación pendiente: `datastructures` y `html` no tienen `dev`, que se crea en
  `master`; en `database` y `geojson`, `dev` y `master` ya apuntan al mismo commit (medido:
  `git rev-list --left-right --count master...dev` da 0 y 0). La guarda deja crear `dev` en los
  paquetes y ninguna otra rama.

  **Homologación hecha el 2026-09-14** (bitácora 0004): en los cuatro, `dev` = `master`. Pero
  el árbol activo de cada paquete sigue en `master`. Por eso, **la instrucción que toque un
  paquete empieza con `git -C <ruta> switch dev`** y lo comprueba con
  `git -C <ruta> branch --show-current`. La guarda deja cambiar de rama; lo que no deja es
  crearla.
- **Verificación de cada paquete.** Cada uno tiene su `bin/phpstan` y su línea base, en
  `PHPStanResult.Summary.baseline.txt`, con el mismo trinquete que aquí. Su
  `.agents/context/04-desarrollo.md` **no sirve de fuente**: los cuatro son el mismo archivo,
  el de `database` (medido el 2026-09-14 con `md5sum`; `pendientes.md`, «Hallazgos de BD»).
  - Pruebas: `datastructures` y `html` tienen `phpunit.xml`; `database`, `unit-tests/`;
    `geojson`, ninguna.
  - Desde este repositorio los vigila `bin/cli verify-integrity`: la comprobación 7 (el
    instrumental común no se desvía) y la 17 (versión instalada contra la última etiquetada).
- **La documentación de los paquetes** (su `README.md`, su `CHANGELOG.md` y su
  `.agents/context/`) es del arquitecto, igual que la de aquí.
- **Límite conocido**: `menciones_ia.py` solo mira este repositorio. Los commits de los paquetes
  no se revisan, y el hook `commit-msg` no está instalado en ellos.

### El PO no lee los recuadros

- Todo lo que el PO deba saber va **fuera** del recuadro y, además, en `.agents/estado/`.
- Un recuadro **nunca** contiene preguntas dirigidas al PO.
- Si el coder necesita una decisión que la instrucción no cubre, **se detiene** y lo dice en
  su reporte. No improvisa ni «asume lo razonable».

### Preguntas al PO (20 §2)

Van **arriba del todo** del mensaje al PO, **numeradas `P<n>`** siguiendo la numeración del 20
(la última usada antes del 2026-09-14 es P16), cada una con su **predeterminado** (qué hace el
arquitecto si no contesta) y con su contexto dentro: el PO no lee recuadros ni reportes, así
que una pregunta que exige haberlos leído no se puede contestar. Una pregunta sin respuesta se
repite con el mismo número en el mensaje siguiente. Las preguntas de oficio del coder no se le
pasan al PO.

### El PO, en sus palabras

Recuperado del cruce de sus 466 turnos (bitácora 0001). Cada punto lleva la fecha en que lo
dijo; formalizado, porque así lo pidió: *«tú estás precisamente para formalizar, no para dejar
la ligereza de mi lenguaje»* (2026-08-24). Su frase textual solo se cita como fuente.

- **Dirige; no revisa.** La verificación completa es de arquitecto y coder: él no es la red de
  seguridad (2026-08-22).
- **No se le piden pruebas ni mediciones**: lo que él pueda probar, lo prueba el coder
  (2026-09-02).
- **Su memoria no es fuente.** Lo que dijo se busca en el registro; no se le pregunta para
  reconstruirlo (2026-09-13).
- **Todo lo decidido queda documentado**, porque al final él tiene que reaprenderlo
  (2026-08-31), y porque quiere seguir gobernando el framework (2026-08-24).
- **Una pregunta del PO se contesta; no se ejecuta.** «¿Podrías…?» pide una respuesta, no un
  encargo. Nada se lanza (subagentes, lecturas masivas, instrucciones) hasta que él lo pida
  explícitamente. Si hace falta concretar el alcance, se le pregunta y se espera. Nace el
  2026-09-14: preguntó si se podían leer sus proyectos antiguos, pidiendo concisión, y el
  arquitecto lanzó la lectura de 28 sin preguntar cuáles.
- **Respuestas en secciones numeradas** (1, 1.1, 2…), para que pueda citarlas en su respuesta
  («en 2.1…»). Vale para las explicaciones y los resúmenes al PO (2026-09-15).
- **Cada mensaje del arquitecto al PO lleva un identificador `A-NNN`** en su primera línea.
  - Es correlativo y distinto del `#NNN` de la cadena con el coder.
  - Sirve para que el PO sepa y diga a qué mensaje responde: «A-003 §2.1».
  - El último número usado vive en `AHORA.md`.
  Nace del PO, el 2026-09-15.
- **Una pregunta rápida se contesta corta.** Un comentario suyo de seguimiento no obliga a
  reemitir la instrucción (2026-08-26, 2026-08-27).
- **Si un bloque cambia lógica del producto, se le explica en términos de lógica** en el resumen
  (2026-08-26).
- **Puede corregir al coder directamente** a mitad de un bloque. El coder lo hace y lo declara en
  su reporte (2026-08-26).
- **Ritmo**: quiere abarcar más por instrucción (20 §3) y no hay reloj: se puede parar sin perder
  nada (2026-08-28). **Nada de fechas ni plazos en el registro** (2026-08-20).
- **Las optimizaciones de proceso son bienvenidas**: se proponen en prosa (2026-08-27).
- **La base local del framework es desechable**: no contiene datos de un proyecto real
  (2026-08-29).

### Canal directo (ADR 0001)

Arquitecto y coder son sesiones de Claude Code en la misma máquina y se hablan por mensajería
entre sesiones, sin el PO. El formato no cambia: identificador en la primera línea y un único
bloque de código por mensaje.

Antes de la primera instrucción a una sesión, el arquitecto confirma que es el coder de
**este** repositorio: en esta máquina corren sesiones de otros proyectos. La confirmación es
su nombre y su respuesta al saludo (`.agents/skills/arquitecto-coder/plantillas/saludo-coder.md`),
que declara directorio, rama, `git status --short`, herramienta y modelo. Si el coder corre en
otro proveedor o en otra máquina, no hay canal: el PO transporta y el formato no cambia.

**Nombres de sesión.** Una sesión nueva recibe un nombre automático; el de `/rename` solo
vuelve si se reanuda esa misma sesión (desde terminal, `claude -n <nombre>`). Al empezar o
retomar, lo primero que el arquitecto da al PO son las dos órdenes:

```
/rename PiecesPHPUpgrade-Arquitecto-Main     ← en la sesión del arquitecto
/rename PiecesPHPUpgrade-Coder-Main          ← en la sesión del coder
```

y comprueba en la lista de sesiones que están puestos. El nombre no sustituye a la
identificación.

**Una sesión arranca con las reglas que había en disco al abrirse.** Si las reglas cambian con
la sesión abierta, esa sesión las relee del disco (el saludo lo exige) y, ante duda, gana el
disco.

**Reabrir un chat no es la misma sesión técnica.** Conserva la conversación, pero arranca un
proceso nuevo: cambia su identificador interno, pierde el nombre (vuelve a `/rename`) y recarga
las reglas del disco. No hace falta volver a saludar. El PO avisa al arquitecto solo si reabre
el chat del coder con un mensaje sin responder. *Sin verificar*: si un mensaje enviado mientras
el proceso está cerrado se pierde. Visto el 2026-09-14.

### Cuándo se detiene el trabajo y se consulta al PO

Solo en estos casos:

1. **Producto**: qué tarea sigue, funciones nuevas o retiradas, alcance.
2. **Su entorno**: paquetes, servicios, configuración de su sistema o de git.
3. **Lo que `00-core.md` y `40-salvaguardas.md` exigen autorizar una a una**: servidores,
   credenciales, bases de datos, dependencias, builds, despliegues, push, etiquetas, crear
   ramas.
4. **Los puntos serios del 20 §2** (lista cerrada). Con ellos **no se instruye: se habla
   antes**:
   - versionar, etiquetar o publicar `piecesphp`; tocar su `master`, su `last-stable` o
     cualquier remoto. En los cuatro paquetes, en cambio, fusionar `dev` a `master` y etiquetar es
     trabajo normal cuando la instrucción lo dice (PO, 2026-08-27 y 2026-09-14, P19);
   - cambiar la versión de una dependencia del producto. La de un **instrumento de análisis de
     desarrollo, no**: el PO la delegó en el arquitecto el 2026-09-02 («Todo la instrumentación
     de análisis en desarrollo está en tus manos»), que la decide y la avisa en prosa;
   - mover una línea base por un motivo que no sea el trabajo del propio bloque;
   - **cambiar un elemento transversal del núcleo** (PO, 2026-09-15): lo que usan muchos
     módulos a la vez, como `DataTablesHelper`, `Config`, las traducciones (`__()`,
     `LangInjector`), `ServerStatics`, `ProtectFileMiddleware`, `PageQuery`, las rutas o el ORM.
     Se habla antes con el PO, con el plan y sus alternativas delante, aunque el cambio sea
     pequeño y esté dentro del mandato. Un cambio así puede arruinar el trabajo de todo el
     framework. Nace de `#042`, que se retiró por salir sin esa conversación;
   - algo irreversible sin un estado guardado que lo deshaga;
   - contradecir una decisión escrita en `.agents/context/` (LEY 32).
5. **La regla de los diez** (18 T0bis): un cambio que borre o mueva declaraciones en más de
   diez archivos se enseña al PO —plan y evidencia— antes de commitearse, aunque esté
   autorizado.

El canal no autoriza nada de eso. Si queda trabajo que no depende de la decisión pendiente,
se sigue con él y la pregunta queda arriba en `AHORA.md`.

### Tramos y rondas

Una **ronda** es una instrucción y su reporte. Un **tramo** es la serie de rondas que
arquitecto y coder encadenan sin detenerse. Lo corta que haga falta el PO, que no quede
trabajo nombrado, o que el PO pida parar. Si el PO pide parar con una ronda a medias, esa
ronda **se termina** y luego se para: nunca se deja un árbol a medio commitear.

Durante el tramo el arquitecto mantiene `.agents/estado/` al día (`60-estado.md`). Al cerrarlo,
entrega al PO el resumen del tramo en el chat.

**Cada vez que el arquitecto se detiene** (fin de tramo, espera al PO o una pregunta suya),
entrega el resumen con esta forma FIJA (PO, 2026-09-15), no en prosa suelta:
1. **Rondas y duración**: `#NNN`–`#NNN`, de hh:mm a hh:mm.
2. **Cerrado**: qué, con sus commits.
3. **Encontrado y decidido**: con los ADR, si los hay.
4. **Falló por el camino**: y cómo se resolvió.
5. **Espera al PO**: una lista numerada que pueda contestar punto por punto, cada punto con su
   contexto y su predeterminado (el PO no ha visto el trabajo: se le contextualiza).

### Tras una compactación

La sesión compactada envía a la otra, antes de seguir, el resumen con el que se quedó y en qué
paso estaba. La otra lo contrasta con lo que sabe y con `AHORA.md`, y señala lo que falte o
esté mal. El resumen no consume número y nunca lleva secretos.

**Cada instrucción y cada reporte dicen en una línea, tras la cabecera, si su sesión se ha
compactado desde el mensaje anterior**: `compactación: no` o `compactación: sí, resumen
enviado`. Nace el 2026-09-15 (A-008): el arquitecto se compactó, no mandó su resumen y nada lo
delató.

## Quién decide qué se construye

El PO nombra la tarea (o una lista, o «el mapa hasta la MAJOR»). A partir de ahí el arquitecto
instruye sin pedir más permiso, dentro de los límites de arriba. El arquitecto **no** elige en
qué se trabaja: detectar que algo conviene y decirlo en prosa (y en `AHORA.md`) es su trabajo;
convertirlo en un recuadro sin que el PO lo haya nombrado, no. Todo encargo del PO produce su
línea en `.agents/docs/pendientes.md` en el mismo turno (LEY 33).

## Identificación de los mensajes

Todo recuadro del arquitecto y todo reporte del coder abren así:

```
[#007 · ARQ · 2026-09-14]
BLOQUE BD · sustituye a: nada

[#008 · COD · 2026-09-14 · Claude Code / Opus 5]
```

- La segunda línea de la instrucción nombra el bloque (las letras de siempre: … BB, BC, BD) y
  dice si retira otro (`sustituye a: #005, RETIRADO`). Un recuadro retirado se anuncia además
  en prosa al PO.
- El coder declara herramienta y modelo: sin ese dato un relevo es invisible.
- El contador es **único y compartido** y avanza en cada mensaje enviado. El último número vive
  en `AHORA.md`. Un mensaje no enviado no gasta número; los del PO no consumen número.
- **No se emite la instrucción siguiente hasta que la actual haya cerrado con su reporte**
  (20 §2).

## Idioma

| Qué | Idioma |
| --- | --- |
| Identificadores: clases, métodos, variables, tablas, columnas | inglés (`CLAUDE.md`, regla 1) |
| Textos de interfaz, mensajes y validaciones | español, dentro de `__($grupo, 'Texto')` |
| Comentarios y docblocks | español, como el código existente; un comentario frena, no narra (LEY 7) |
| Commits | español, Conventional Commits, con el estilo del historial (`git log --oneline`) |
| Documentación, instrucciones y reportes | español |

## Formato de intercambio

Instrucción y reporte van cada uno en **un único bloque de código**. Nunca repartidos.

## Obligaciones del arquitecto

### Instrucciones autocontenidas

Cada instrucción dice qué leer antes de empezar y no da por sabido nada de tandas anteriores:
el coder puede ser una sesión nueva o de otro proveedor. Lleva, en este orden:

1. Contexto y lecturas previas.
2. Prohibido en esta tarea.
3. Verificación previa (PASO 0) con criterios. Si falla, el coder se detiene.
4. El trabajo, paso a paso, con paradas explícitas.
5. Plan de commits con los mensajes ya redactados y el **previsto** de cada uno.
6. Verificación final: los comandos y su criterio.
7. Qué reportar.

Y se cierra con: «Si algo te obliga a desviarte, para y repórtalo en vez de decidirlo tú.»

### Oficio, heredado del 20 §3 y de las leyes

- **El criterio de arranque no se ancla a un hash**:
  `git merge-base --is-ancestor <hash> HEAD` y el estado de los archivos de la tanda.
- **Los cuatro números**: previsto · cambiado · añadido · pendientes, cuadrados con
  `bin/guarda-add`, que emite su línea. Sin esa línea, la guarda no corrió (LEY 18). La cuenta
  es por repositorio.
- **El alcance se mide, no se hereda** (LEY 17): ninguna instrucción que borre o mueva un
  símbolo nombra un número sin el censo que lo produjo, y el censo va DENTRO de la instrucción
  como paso previo.
- **Antes de proponer un cambio de forma, se busca la forma vieja en `.agents/context/`**
  (LEY 32).
- **Lo ya decidido entra en la instrucción como decisión**, no como algo que el coder tenga que
  redescubrir midiendo (PO, 2026-08-26).
- **Verifica cada helper y API que dictes**: abre el archivo, lee la firma y el `return`.
- **No cuentes con `grep -c` texto que tu propia instrucción inserta**: presencia, no cantidad.
- **Una tarea bloqueada no arrastra a otras**: gate y commit por tarea.
- **Provocar es destructivo**: desde un estado guardado (copia y `sha1sum`), sobre un archivo
  propio y, si se intercambia PHP servido por Apache, esperando más de 2 s
  (`opcache.revalidate_freq`).
- **Exige el camino de fallo**: para lo que reescribe o borra, prueba de que el original queda
  intacto cuando la operación no se completa. Una prueba de guarda debe fallar si se quita la
  guarda (LEY 24).
- **Toda orden de revertir enumera los archivos.** «Revierte lo de la parte 2» es una
  invitación a barrer.
- **Toda orden de git del arquitecto lleva `--no-optional-locks`**: el coder puede estar usando
  el índice.
- **`CHANGELOG.md` entra en el previsto de todo bloque que cambie algo para quien clona**, y
  el bloque «CAMBIOS INCOMPATIBLES» se mantiene al pasar, no el día de la MAJOR.
- **Lee el código del coder**, no solo sus salidas.
- **No midas lo compartido** (un `/tmp`, un puerto, un log): mide lo propio.

### No escribas en el árbol mientras hay una tanda en vuelo

Desde que sale una instrucción hasta que llega su reporte, **el árbol es del coder**. La
documentación nueva se redacta en el scratchpad y se deposita al recibir el reporte.
**Excepción**: `.agents/estado/`, que solo escribe el arquitecto y que el coder excluye de sus
criterios de `git status`. Si el coder encuentra modificado un archivo que no estaba en su
PASO 0 y que él no tocó, **lo reporta y lo deja como está**.

### Proponer el relevo de una sesión

Si el arquitecto cree que una sesión —la suya incluida— debe sustituirse, se lo dice al PO con
sus motivos, entre tandas. Señales: contradecir un ADR, re-preguntar lo decidido, dictar rutas
o APIs sin verificar (arquitecto); reportar resúmenes en vez de salidas, desviarse sin
detenerse, tocar fuera de alcance, actuar por recuerdo y no por la instrucción (coder).

## Obligaciones del coder

### Verificar antes de reportar

```
bash .agents/scripts/verificar.sh
```

verifica el **andamiaje de agentes**. La verificación del **producto** la nombra cada
instrucción; para cambios en `src/` o `bin/`, por defecto: `bin/phpstan` contra
`PHPStanResult.Summary.baseline.txt`, `bin/cli verify-integrity` y `bin/cli gates`, cada una
con su línea de «ejecutada». Una suite omitida es una puerta fallada (LEY 13). Todo con PHP
8.5: `bin/cli` lo elige solo; `php` a secas es 8.1.34.

La salida **real** va en el reporte. Si falta una herramienta del sistema, se detiene y lo
reporta: instalarla es cosa del PO.

**La verificación del producto se corre DESPUÉS del último cambio que entra en el commit**,
también cuando ese cambio es instrumental (`files/dev/`). Una cifra medida antes no vale para lo
commiteado. Nace de `#052`: en `#050` se retiró una entrada de `sql-concat-declared.json` después
de correr `gates`, y el commit entró con una prueba rota.

### Commits

- **Autorización (ADR 0005).** `00-core.md` exige el permiso explícito del PO para preparar y
  commitear, y un mensaje del arquitecto no es ese permiso. En este repositorio el PO lo dio de
  forma **permanente** para el trabajo que él nombra, y consta en `.agents/estado/AHORA.md`.
  - Cada instrucción con commits cita esa línea.
  - Si la línea no está, el coder se detiene antes de preparar nada.
  - Si la herramienta del coder pide confirmación al ejecutar `git add` o `git commit`, la da el
    PO en la sesión del coder.
  - Push y el resto de lo reservado (ADR 0005), nunca.
- Atómicos: un commit = una unidad coherente. Árbol sano después de cada uno.
- Conventional Commits, en español. **Cero atribución a IA** (`40-salvaguardas.md` §5).
- `git add` con rutas explícitas, nunca `.` ni `-A`, y `bin/guarda-add` antes de cada commit.
- Un archivo **nuevo** pasa por `bin/normaliza-eol` antes de añadirse. **Nunca con `--arregla`
  sobre `.agents/estado/`**: el arquitecto puede estar escribiendo ahí, y `normaliza-eol` lee y
  reescribe el archivo entero, así que una escritura suya en medio se perdería (hallazgo H2 de
  `#007`).
- `git push`, **nunca**. Ninguna etiqueta **en este repositorio**; en los paquetes, ver «Los
  cuatro paquetes hermanos».

### Ramas (PO, 2026-09-14)

- **En este repositorio**, `master` es la estable sin versionar y `last-stable` es la estable con
  etiqueta de versión. El resto son ramas de trabajo: hoy `dev`, `limpieza-modulos`,
  `modificacion-docs` y `upgrade-to-php85`. Tocar `master` o `last-stable` es punto serio.
- **En los cuatro paquetes**, `master` es su estable, pueden tener las ramas que quieran y todos
  llevan `dev`, donde se trabaja (P19).
- **Ninguna rama se crea sin permiso del PO**, salvo `dev` en los paquetes. La guarda bloquea
  `git branch <nueva>`, `switch -c`, `checkout -b` y `worktree add`.
- La rama de trabajo la nombra la instrucción y tiene que existir. El PASO 0 comprueba
  `git branch --show-current` en cada repositorio que se toque.

### Barrido de documentación

La documentación del arquitecto se commitea **sin editarla**, en commits `docs:` aparte del
código; `.agents/estado/`, en su propio `docs(estado):`. Si aparece un archivo del arquitecto
que la instrucción no anunciaba, se dice en el reporte y se commitea igual (20 §3).

### Si el PO te corrige directamente

Lo haces y lo declaras en el reporte, en «Desviaciones», con sus palabras. El arquitecto no
estaba en esa conversación y tiene que saberlo.

## Contenido obligatorio del reporte

1. **Estado** — completado / completado con desviaciones / bloqueado.
2. **Archivos tocados**, con ruta relativa.
3. **Commits creados** — hash corto + mensaje.
4. **Verificación** — comandos y su **salida real**, con las líneas de «ejecutada» de cada
   guarda.
5. **Los cuatro números** de cada commit.
6. **Desviaciones** — con su motivo.
7. **Hallazgos** — se **reportan, no se arreglan**.

Un reporte que omite un fallo es peor que el fallo.
