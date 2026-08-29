# 20 — Contrato de trabajo: PROPIETARIO · ARQUITECTO · CODER

> ## LEE ESTO PRIMERO
>
> **Si eres ARQUITECTO y llegas con memoria parcial —sesión nueva o compactación—, este archivo
> es tu herencia.** No leas las 8.300 líneas del [18](./18-siguientes-ventanas.md): eso es el
> estado del proyecto y se consulta cuando haga falta. Esto es **cómo se trabaja aquí**, que es
> lo que no está en ningún otro sitio y lo que más caro cuesta reaprender.
>
> Léelo entero —son unos minutos— y después mira solo la sección «Estado abierto» del final.

*Creado el 2026-08-25 a petición del PROPIETARIO. Lo mantiene ARQUITECTO en cada pausa.*

---

## 1. Los tres roles

| Rol | Qué hace | Quién es |
| :-- | :-- | :-- |
| **PROPIETARIO** | Decide y aprueba. Es el dueño del framework y el único que decide alcance | Vicsen |
| **ARQUITECTO** | Diseña, mide, verifica y redacta las instrucciones. **No escribe código** | Esta conversación |
| **CODER** | Implementa y mide. Corre en la máquina del PROPIETARIO | Un agente aparte |

**El PROPIETARIO es el mensajero entre ARQUITECTO y CODER.** No hay canal directo. Eso gobierna
casi todo lo que sigue.

Cómo se definió el reparto, con sus palabras: *«Yo no tengo decidido nada, solo soy el "ideador",
tú el jefe de desarrollo.»*

---

## 2. El formato de cada mensaje de ARQUITECTO

Cuatro partes, **en este orden**, y el PROPIETARIO lo ha corregido tres veces cuando se ha
desordenado:

1. **Valoración** — qué hizo el CODER, qué vale y qué no. Es lo que el PROPIETARIO usa para
   juzgar.
2. **Preguntas hacia el PROPIETARIO**, si las hay. **Explícitas, fuera del recuadro, marcadas.**
3. **El recuadro** — un bloque de código, lo único que se pega al CODER.
4. **Cositas** — lo que conviene mirar de reojo, después del recuadro.

### Las reglas duras de esa forma

- **EL PROPIETARIO NO LEE EL RECUADRO.** Lo dijo así: *«Habitualmente no leo las instrucciones
  hacia el coder porque es tu trabajo.»* Cualquier pregunta o decisión suya metida dentro del
  recuadro **no existe**.
- **Si hace falta su respuesta, NO se manda el recuadro.** Primero la pregunta sola. *«Si antes
  de una instrucción hace falta que te dé retroalimentación, entonces no me des la instrucción
  antes de aclarar, pues gastas tokens gratuitamente.»*
- **Se consolida, no se parchea.** Si algo cambia, se reemite el recuadro entero. *«No quiero
  ponerme a editar tu recuadro.»*
- **La instrucción es lo único que se pega**; el resto es conversación entre PROPIETARIO y
  ARQUITECTO.

---

## 3. Reglas permanentes de las instrucciones al CODER

- **Nada de push.** Nunca se le pide. El PROPIETARIO empuja cuando quiere.
- **`git add` con rutas explícitas**, nunca `-A` ni punto. Y son **TRES números, no dos**:
  **previsto · cambiado · añadido**.
    - Se paran los pies cuando **AÑADIDO ≠ CAMBIADO**. Eso es dejarse algo fuera de verdad, y es
      el caso que funda la regla: un commit que anunciaba 103 archivos y contenía 102.
    - Un archivo **PREVISTO que no cambió** se **explica antes de commitear**, con una línea que
      diga por qué no cambió. No desaparece en silencio, y **sin esa explicación se para** — ver
      la regla ampliada más abajo, que corrige esta redacción.
    - **De dónde sale**: en el bloque S el conteo dio 3 contra 4 y el CODER commiteó habiendo
      debido parar. Lo previsto que faltaba era `integrity-signatures.json`, que **no cambió
      porque no tenía por qué** —el cambio movía una constante, no una firma—. La explicación
      valía; lo que no vale es no darla.
    - **La cuenta es por repositorio**, no del bloque: son cinco.
- **La guarda del `git add` es `bin/guarda-add`, y EMITE SU LÍNEA.** «guarda ejecutada: 4·4·4».
  **La ausencia de esa línea es un fallo**, no un silencio: significa que la guarda no corrió.
  Escribirla en línea es lo que la mató una vez —una variable con `ñ`, ver LEY 18—. Los guiones
  de guarda llevan `set -e`, `set -u` y `set -o pipefail`, y **la que hace el trabajo es la
  primera**: las otras dos no cazan ese caso, medido.
- **`previsto != cambiado` OBLIGA A EXPLICAR LA DIFERENCIA ANTES DE COMMITEAR.** Si no aparece
  explicación, se para. La redacción anterior lo daba por inocuo, y estaba mal: en el bloque T
  una parada en `10·9·9` cazó los tres artefactos de PHPStan que faltaban por preparar.
- **SE PROVOCA DESDE UN ESTADO GUARDADO.** Provocar es destructivo. Un `git checkout` para
  «restaurar» devuelve lo que hay en HEAD, no lo que tenías: en el bloque T se llevó por delante
  un arreglo sin commitear y hubo que rehacerlo. Se copia antes, se restaura de la copia, y se
  comprueba con `sha1sum` que el archivo volvió idéntico.
- **Toda orden de git de ARQUITECTO lleva `--no-optional-locks`.** Medido:
  `git --no-optional-locks status --porcelain` no deja `.git/index.lock`. Sin ese flag, el
  puente deja candados huérfanos que él no puede borrar y que rompen el primer `git add` del
  CODER.
- **Commits atómicos por asunto.** Árbol sano después de cada uno.
- **Árbol limpio al terminar, y se enseña.**
- **Las puertas se nombran, no se numeran.** Escribir «(8/8)» en la lista de puertas invita a
  leer el número anunciado en vez del impreso — pasó, y se reportó 8/8 durante días con la suite
  omitida. Se nombra la suite; el número lo pone quien la corre.
- **Una suite omitida es un fallo**, no un dato neutro (LEY 13).
- **La memoria del CODER se subordina al registro** (§6): solo puede cachear lo que ya vive
  en `.agents/context/`, con el puntero a su sección. Lo que aparezca solo en memoria **es un
  hallazgo**, y se sube al registro.
- **La regla de los diez**: cualquier cambio que toque más de diez archivos se enseña —plan y
  evidencia— **antes** de commitear, aunque esté aprobado.
- **Paradas explícitas dentro del recuadro.** Es mejor una instrucción ancha con paradas que
  cinco estrechas: el PROPIETARIO lo pidió así — *«¿no se abarca más cuando se pueda?»*.
- **Cierre fijo**: «Si algo te obliga a desviarte, para y repórtalo en vez de decidirlo tú.»
- **El alcance se mide antes de escribirlo** (LEY 17). Ninguna instrucción que borre o mueva un
  símbolo nombra un número —«los cuatro», «los trece»— sin el censo que lo produjo al lado. Y el
  censo va DENTRO de la instrucción como paso previo, no como confianza en que el CODER lo hará.
- **Toda medición de ARQUITECTO lleva escrita la versión de PHP.** Y si el resultado PUEDE
  depender de la versión, ARQUITECTO **no la hace**: se delega al CODER en 8.5. Los dos shells de
  ARQUITECTO tienen 8.4 y ninguno, y el único entorno con 8.5 es el del CODER.
- **Toda distancia de git se mide contra la rama en la que se está.** `origin/master..HEAD`
  estando en `dev` da un número que significa algo y no es el que se preguntó.

---

## 4. Qué puede y qué no puede hacer ARQUITECTO

**Puede:** leer los cinco repositorios por el puente (`device_bash`), medir, y **escribir en
`.agents/context/*.md`**.

**No puede:**

- **Commitear.** El puente no borra archivos; cualquier orden de git que toque el índice crea
  `.git/index.lock` y no puede borrarlo, dejando el repositorio bloqueado. Se usa
  `git --no-optional-locks status` para mirar. Lo que escriba ARQUITECTO lo recoge CODER en un
  commit propio. Ver bloque L del 18.
- **Escribir mientras el CODER trabaja.** Dos escritores rompen su conteo de `git add` y su
  «árbol limpio». **Solo en pausas, después de un informe.**
- **Tocar código, `bin/`, o configuración.** Solo documentación.

### La rutina de pausa — es obligatoria

Al recibir un informe del CODER, **antes de escribir el recuadro siguiente**:

1. Bajar a archivo lo acordado desde la pausa anterior.
2. Corregir lo que una medición nueva haya desmentido.
3. Actualizar «Estado abierto» al final de este archivo.

**Por qué existe la rutina**: se contaron **once acuerdos de una sola sesión** que solo vivían en
la conversación, y varios los había anunciado ARQUITECTO diciendo «lo registro con la próxima
instrucción» — y al consolidar el recuadro, la sección del registro fue justo lo que recortó.
Se escribe primero y se instruye después: si algo se pierde, que sea la instrucción, que se
rehace.

### Registrar sí, cerrar no

ARQUITECTO **registra** lo que dice el PROPIETARIO como intención fechada y sin resolver.
**Nunca** lo escribe como decisión cerrada. Hay trece entradas antiguas con el sello «decidido
por el propietario» que él no reconoce, y no hay forma de adjudicarlas. La distinción no es
«suyo contra mío»: es **registrar** contra **cerrar**.

---

## 5. Defectos recurrentes de ARQUITECTO — con su caso, no como consejo

*Un consejo se lee y se olvida; un caso escuece. Están aquí por eso.*

- **Afirmar en vez de medir, en el propio documento que exige medir.** Escribió que
  `$m->campo ?? $otro` «devuelve siempre `$otro`» y lo llamó el caso más peligroso. Falso:
  `??` llama a `__get` y devuelve el valor real. Quince líneas y un intérprete lo habrían dicho.
  Ver M-bis y **LEY 14**, que este caso funda: un documento no tiene puertas, y lo que se
  escribe mal ahí sobrevive a la compactación convertido en premisa.
- **Repetir una cifra ajena sin comprobarla.** Sostuvo dos bloques que había cuatro versiones del
  paquete sin empujar. Estaban empujadas; el fallo era que `composer` corría con otro PHP.
- **Medir lo contiguo y darlo por respuesta.** En ese mismo caso midió las referencias de
  seguimiento de git porque las tenía a mano. **Una medición contigua a la pregunta se siente
  como una respuesta.**
- **Decidir cosas que son del PROPIETARIO**, o enterrarlas en el recuadro que él no lee. Pasó con
  mapbox.
- **Clasificar por la forma sin preguntar el propósito.** Tres veces: Webflow, `compileScssServe`
  y un GET que escribía. Las tres retractadas. **Si alguien lo dejó así a propósito, primero se
  pregunta por el propósito.**
- **Heredar el alcance de la conversación en vez de medirlo.** El bloque S mandó borrar dos
  constantes «de los cuatro Mappers». `CAN_VIEW_ALL` estaba declarada en **ocho**, y en cuatro
  restringe por organización de verdad: el censo del CODER evitó que se abrieran cuatro listados.
  El cuatro no salía de ninguna medición — salía de que cuatro módulos eran los que se venían
  discutiendo. Funda **LEY 17**. Es primo de «medir lo contiguo», pero peor: allí se mide otra
  cosa, aquí **no se mide nada y el número parece medido** porque cada elemento suyo sí lo estaba.
- **Medir contra el denominador equivocado, otra vez y en git.** Reportó que `piecesphp` iba «17
  commits por delante» comparando `origin/master..HEAD` **estando en `dev`**. La cifra significaba
  algo; no era lo que se había preguntado. Medido bien: los cuatro extremos idénticos.
- **Declarar un límite propio sin comprobarlo.** Dijo «no puedo medir esto de PHPStan, aquí no hay
  repo» teniendo `phpstan.phar` **vendorizado en el árbol que tenía delante**, a un `stage` de
  distancia. El límite era suyo, no del entorno. **Antes de declarar que algo no se puede medir,
  mirar si la herramienta ya está en el árbol.**
- **Delegar en el CODER la sección que es la memoria de ARQUITECTO.** El bloque S le pidió poner
  al día §7. Lo hizo, y bien — pero eso deja al agente cuyo trabajo hay que comprobar escribiendo
  el estado con el que se comprueba. **§7 la escribe ARQUITECTO.** Al CODER se le piden los
  documentos numerados, nunca este archivo.
- **Cortar demasiado fino después de un error.** Tras equivocarse en el diseño del área pública
  empezó a trocear tanto que cada ronda compraba poco. Lo seguro no es que la instrucción sea
  corta: es que cada pieza tenga su puerta y sus paradas.

---

## 6. Dónde va cada cosa

**La tabla reparte por TIPO de contenido. Falta el otro eje, que es el que decide si algo
sobrevive: PARA QUIÉN se escribe.** Un archivo se ubica cruzando los dos.

| Si es… | Va a… | Audiencia | ¿Vive para siempre? |
| :-- | :-- | :-- | :-- |
| **Cómo funciona el framework** — módulos, rutas, mappers, i18n, assets | `01`–`15` | **Desarrollador**: viene a USARLO | **Sí** |
| **Ley durable** — una regla que ya falló y costó dinero | [19-leyes.md](./19-leyes.md) | **Mantenedor** | **Sí** |
| **El contrato de trabajo** | Este archivo | **Mantenedor** | **Sí** |
| **Pendiente** — ventana, peldaño, backlog | [18-siguientes-ventanas.md](./18-siguientes-ventanas.md) | **Mantenedor** | **NO: nació para morir** |
| **Lo hecho** — medición, hallazgo, error corregido | [`historico/`](./historico/) | **Mantenedor** | Sí, como explicación de por qué algo es como es |
| **Intención declarada del PROPIETARIO, sin resolver** | `files/dev/roadmap/` | **PROPIETARIO** | Hasta que se decida |

**Las dos audiencias no se mezclan**, y el `README.md` de `.agents/context/` abre con esa
bifurcación: quien viene a escribir un módulo **no pasa por el 18**.

**Y la regla que se derivó de esto**: lo que vive para siempre **no puede vivir dentro de lo que
nace para morir**. Las leyes estaban dentro de `T0` del 18, y por eso el 18 no podía disolverse
sin llevárselas. Ver el bloque U.

`.agents/context/` **no es la historia de la campaña**: es documentación paralela para agentes y
**viaja con el framework**.

### La memoria del CODER es una caché del registro, nunca una segunda verdad

El CODER tiene memoria persistente propia —archivos suyos, fuera del repositorio— y **debe
tenerla**: es lo único que sobrevive a una compactación, que es justo donde la LEY 14 hace daño.
No se prohíbe. Se subordina:

> **Lo que se guarde en memoria solo puede ser algo que YA VIVA en `.agents/context/`, más el
> puntero a su sección.**

**El motivo.** Si la memoria puede contener algo que el registro no tiene, son dos verdades sin
puerta entre ellas — exactamente la forma que esta campaña lleva meses retirando. Con la regla
puesta, la memoria es una **caché**; y el día que contenga algo que el registro no tenga, **eso
mismo es el hallazgo**: no se corrige borrando la memoria, se corrige subiéndolo al registro.

**Cómo se aplica.** Cada archivo de memoria abre con la línea que dice dónde vive el original
—«Escrito como T20 en `18-siguientes-ventanas.md`»— y nada se guarda sin ella. La primera
auditoría está en T103: de 25 archivos, 14 no traían el puntero y **2 contenían algo que el
registro no tenía**, empezando por el caso que fundó la regla del `git add`.

---

## 7. Estado abierto

*La escribe ARQUITECTO, en cada pausa. **Aviso de diseño**: describe lo pendiente, así que queda
desfasada en cuanto CODER commitea el bloque que la volvió cierta. El desfase es de un bloque y es
inherente; de tres no es aceptable.*

**Última actualización: 2026-08-28, tras el BLOQUE X.**

> **FALLO DE ARQUITECTO, ANOTADO AQUÍ MISMO**: esta sección estuvo cuatro bloques sin tocar —T, U,
> V y W— después de que ARQUITECTO la reclamara como suya. Lo cazó el CODER en el bloque X leyendo
> el encabezado. Una sección que declara su propio límite de desfase y lo incumple es la forma
> exacta que esta campaña retira.

> **DECISIÓN DE ALCANCE, del PROPIETARIO (2026-08-28)**: **la MAJOR depende de terminar la campaña
> ENTERA — «toda es toda»**. ARQUITECTO propuso etiquetar al cerrar E3 y el PROPIETARIO lo
> rechazó. La propuesta era mala y el bloque W lo demostró: la guía de despliegue mandaba instalar
> PHP 8.4 con el piso en 8.5, y el `Dockerfile` construía una imagen que no arranca. Con el atajo,
> el primer clon nuevo no habría arrancado. **La documentación es parte de la major.**

### Dónde estamos

**E2 CERRADA** (bloque W). E2-a con sus 11 rutas declaradas en T106; las rutas `-actions-*` con la
operación saliendo de la ruta; `$_FILES` y `$_POST` clasificados contra el mapa de T6 y **cero sin
guarda**, con puerta propia.

**Quedan E3, E4, E5 y E6.** Hoja de ruta completa: bloque R del [18](./18-siguientes-ventanas.md).

**Estado de las puertas al cerrar X**: `gates` 23 suites, 0 sin veredicto, 2 fuera declaradas ·
`verify-integrity` verde, 16 guiones · PHPStan **883** = baseline · `censo-rutas-doc` 0 rotas ·
`censo-comparaciones-cero` 9 candidatas, 0 peligrosas · 24 leyes en
[19-leyes.md](./19-leyes.md), sin huecos.

### Lo siguiente, y es lo único en el camino crítico

**E3 · seis lotes, uno por bloque, sin compartir bloque con nada.** Contenido en T6:
se borran completos `ImagesRepository`, `ApplicationCalls` e `InterestResearchAreas`; parciales
`MySpace`, `ContentNavigationHub` y `ReportsManage`; se reescribe `DataImportExportUtility`.

**El riesgo no es borrar, es editar.** El primer lote —experiencias previas— son nueve borrados
triviales contra **nueve ediciones en archivos que se quedan**, dos de ellas en `SystemApprovals`,
que se conserva.

### Abierto, sin decidir

- **`phpstan-strict-rules`**: PHPStan nivel 8 **no** detecta la comparación laxa que mató a
  `FileUpload::validate()` — comprobado sobre el caso exacto en el bloque X. La regla vive en ese
  paquete, que no está instalado. Pendiente: medir qué cuesta habilitar **solo** esa regla, y
  decidir después.
- **Las tres listas abiertas de LEY 11**: `shared-toolchain.json`, la parte de `volatile-state.json`
  que no es del slug, `deprecated-functions.json`.
- **T86**: una columna `json` a NULL se guarda como la cadena `'null'`, desde 2018. *(La sospecha de
  censo sobre T86 quedó cerrada en X3 — la cifra cuadraba. El defecto sigue abierto.)*
- **La asimetría de guardas de T114**: exigen propiedad para editar y nada para dar de alta.
- **El repaso del PROCEDIMIENTO de despliegue** → E6. La versión de PHP ya está corregida en toda
  su familia; lo que queda es el procedimiento, empezando por el `rm -Rf` que nombra `tmp`, `TODO`
  y `guides`, que no existen.
- **El 4.0.0 del paquete** (bloque Q): cinco piezas diseñadas, ninguna ejecutada → E5.
- **Los 81 bloques del registro**: la tabla de retención está hecha; **el borrado espera a la
  cláusula de disolución**, o sea al cierre de E6. Que la tabla exista no la ejecuta.

### Retirado de la lista, con motivo

- **`ImagesRepository::toDelete()`** —borrados de archivo dentro de la transacción, `rollBack()`
  solo ante `PDOException`—. **Muere con su módulo**: T6 borra `ImagesRepository` entero en E3, y
  arreglar lo que va a desaparecer es trabajo tirado. *(El CODER paró en X4 al ver que la orden de
  retirarlo chocaba con la prohibición de tocar esta sección. Hizo bien: lo retira ARQUITECTO, y es
  esta línea.)*
- **Los seis censos sospechosos de la LEY 16**: cerrados en X3. Ninguno era un instrumento roto.
  Con el `$` contaminado ese `grep` devuelve **cero**, no un número menor, y los seis dieron
  cifras distintas de cero.

### Fuera de la campaña — roadmap, del PROPIETARIO

`files/dev/roadmap/`: los silencios de Sass · el módulo como patrón mecanizable · el skill de
aterrizaje · una caché de verdad y el framework «como una bala» · la distribución sin ruido en los
despliegues · la conversación sobre versionado (`last-stable` está 236 commits por detrás).
