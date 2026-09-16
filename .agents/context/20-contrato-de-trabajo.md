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

> ## DESDE EL 2026-09-14 EL MODELO DE TRABAJO LO RIGE `.agents/rules/30-protocolo-coder.md`
>
> El PROPIETARIO adoptó el modelo de tres roles con **canal directo** entre sesiones (ADR 0001).
> Lo que cambia de este documento, y se marca en cada sección:
>
> - **§1**: el PROPIETARIO ya no es el mensajero. Arquitecto y coder se hablan directamente.
> - **§2**: la primera línea de cada recuadro es ahora `[#NNN · ARQ · fecha]`; la línea
>   `VIGENTE · BLOQUE XX` pasa a la segunda. Lo demás de §2 (las preguntas arriba, numeradas y
>   con predeterminado; los puntos serios; un recuadro por vez) **sigue vigente**.
> - **§4**: el arquitecto trabaja en la misma máquina, sin puente; sigue sin commitear.
> - **§7**: el estado vivo sale a `.agents/estado/AHORA.md` y el mapa a la MAJOR a
>   `.agents/docs/roadmap.md` (ADR 0002). §7 queda como está al 2026-09-01, como procedencia.
> - **Documentación**: la escribe el arquitecto, CHANGELOG incluido; el coder ya no escribe
>   entradas T ni CHANGELOG.
>
> **§3 (oficio de las instrucciones) y §5 (defectos recurrentes) siguen enteros.** Ante
> contradicción con `30-protocolo-coder.md`, gana la regla y este documento se corrige.

---

## 1. Los tres roles

| Rol | Qué hace | Quién es |
| :-- | :-- | :-- |
| **PROPIETARIO** | Decide y aprueba. Es el dueño del framework y el único que decide alcance | Vicsen |
| **ARQUITECTO** | Diseña, mide, verifica y redacta las instrucciones. **No escribe código** | Esta conversación |
| **CODER** | Implementa y mide. Corre en la máquina del PROPIETARIO | Un agente aparte |

**El PROPIETARIO es el mensajero entre ARQUITECTO y CODER.** No hay canal directo. Eso gobierna
casi todo lo que sigue.

> **SUPERADO el 2026-09-14** (ADR 0001): hay canal directo y el PROPIETARIO ya no transporta.
> Lo que de aquí se deduce sobre él —que no lee los recuadros, que lee los reportes en diagonal—
> sigue siendo cierto y sigue gobernando las preguntas de §2.

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

### UN RECUADRO POR VEZ, Y CADA UNO DICE SI ES EL VIGENTE — 2026-09-02

El PROPIETARIO lo dijo como una suposicion suya y **es la regla correcta**: *«siempre asumo que
el ultimo recuadro que mandas es el final consolidado»*.

**ARQUITECTO la rompio sin darse cuenta.** Emitio el recuadro de AZ mientras BA seguia en vuelo,
luego lo RETIRO, y luego lo reemitio. Tres recuadros con el mismo nombre en la conversacion, y el
PROPIETARIO no supo cual estaba vivo, asi que no copio ninguno. **Un recuadro retirado es
exactamente lo que la regla «se consolida, no se parchea» existe para evitar**, y ARQUITECTO lo
produjo.

**DOS REGLAS, y la segunda hace innecesario recordar la primera:**

1. **NO SE EMITE EL RECUADRO SIGUIENTE HASTA QUE EL ACTUAL HAYA CERRADO CON SU REPORTE.** Un
   bloque en vuelo y otro escrito es una invitacion a que se ejecute el equivocado.
2. **CADA RECUADRO ABRE DECLARANDO SU ESTADO**, en su primera linea, dentro del propio recuadro:

       VIGENTE · BLOQUE XX · sustituye a: (nada | BLOQUE XX de <fecha>, RETIRADO)

   Con esa linea, el PROPIETARIO no tiene que recordar el orden de la conversacion **ni leer
   nada mas que esa linea** para saber si lo que tiene delante es lo que hay que pegar.

**Y SI HAY QUE RETIRAR UNO YA ENVIADO**: se dice EN PROSA, en la primera frase del mensaje, no
dentro del recuadro nuevo. El PROPIETARIO no lee los recuadros.

### ANTES DE UN PUNTO SERIO SE HABLA, NO SE MANDA RECUADRO — regla del PROPIETARIO, 2026-09-02

*«Eso me iba a tomar por sorpresa. Recomiendo que cuando lleguemos a un punto tan serio no me
mandes nada para CODER y hablemos antes.»*

Ya existia la regla de «si hace falta su respuesta, primero la pregunta sola». **No basto**,
porque ARQUITECTO metio en el recuadro un paso que el PROPIETARIO no habia aprobado y ademas
CREYO ver algo que no estaba: leyo «el analizador de `piecesphp` sube» y entendio que subiamos
`piecesphp` a estable.

**LA LISTA DE PUNTOS SERIOS, y es cerrada. Con cualquiera de estos, NO SE MANDA RECUADRO:**

1. **Versionar, etiquetar o publicar** cualquiera de los cinco repositorios. Tocar `master`,
   `last-stable` o cualquier remoto. *Corregido por la regla 30:* en los paquetes se etiqueta (P19), y en
   `piecesphp` las pre-versiones, las etiquetas y el avance de `master` y `last-stable` son de arquitecto y
   coder; queda como punto serio la versión MAYOR estable (ADR 0019, 2026-09-16).
2. **Cambiar la version de un INSTRUMENTO** —analizador, refactorizador— o de una dependencia.
3. **Mover una linea base** por un motivo que no sea el trabajo del propio bloque.
4. **Cualquier cosa irreversible** sin estado guardado que la deshaga.
5. **Contradecir una decision escrita** en `.agents/context/` (LEY 32).

En esos casos: se habla, se decide, y **DESPUES** se escribe el recuadro. La conversacion no es
un tramite previo a la instruccion: es donde se decide si la instruccion existe.

### DOS PALABRAS QUE ARQUITECTO USO PARA DOS COSAS

«**Subir**» significo en el mismo mensaje *actualizar una herramienta de desarrollo* y *publicar
el framework*. El PROPIETARIO leyo lo segundo. **La ambiguedad es del emisor.** A partir de aqui:
**actualizar** una herramienta, **publicar** o **etiquetar** un repositorio. «Subir» no se usa.

### UNA PREGUNTA AL PROPIETARIO SE EXPLICA SOLA — y hay preguntas que NO son suyas

*«Debes recordar que no leo tus instrucciones ni los reportes de CODER salvo en diagonal.»*

Dos consecuencias, y ARQUITECTO ya fallo en las dos:

1. **Una pregunta que exige haber leido el recuadro o el reporte del CODER no se puede
   contestar.** Ocurrio con P13, que hablaba de una costumbre del CODER descrita en SU reporte:
   la respuesta fue *«no se de que hablas»*, y con razon. **Toda pregunta trae su contexto
   dentro, en prosa de ARQUITECTO.**
2. **Las preguntas de OFICIO DEL CODER no se le pasan al PROPIETARIO.** Como inserta un metodo,
   como ordena sus commits o que costumbre adopta es asunto entre CODER y ARQUITECTO. Subirlas a
   decision del PROPIETARIO le gasta atencion en algo que no decide.

**Y la consecuencia general**: si el PROPIETARIO no lo lee en la prosa de ARQUITECTO, NO LO SABE.
El recuadro y el reporte no le informan de nada.

### LAS PREGUNTAS VAN LAS PRIMERAS — corregido el 2026-09-01, y el fallo es de ARQUITECTO

«Explícitas, fuera del recuadro, marcadas» **no bastó**. El 2026-09-01 ARQUITECTO mandó tres
preguntas bajo un encabezado propio, entre la valoración y el recuadro, y el PROPIETARIO
respondió: *«No vi pregunta P3. No me fijé ningún lugar evidente de preguntas.»* Un apartado a
media altura, entre una tabla y un recuadro de cien líneas, **es invisible**.

Se convierte en mecanismo (LEY 11), y cambia el orden de §2:

1. **Las preguntas van ARRIBA DEL TODO**, antes de la valoración. Si no hay, se dice que no hay.
2. **Numeradas `P1`, `P2`, `Pn`** y correlativas dentro del mismo mensaje.
3. **Cada una lleva su PREDETERMINADO**: qué hace ARQUITECTO si el PROPIETARIO no contesta. El
   silencio no puede bloquear un bloque; y una pregunta sin predeterminado obliga al PROPIETARIO
   a contestar aunque le dé igual, que es hacerle trabajar de más.
4. **Una pregunta que aún no se ha respondido SE REPITE** en el mensaje siguiente, con el mismo
   número. No se da por perdida ni por concedida.

**LO QUE ESTO NO CAMBIA**: si la respuesta hace falta ANTES de la instrucción, el recuadro no se
manda. Eso sigue igual. Lo que se arregla aquí es el caso contrario: preguntas que acompañan a un
bloque que sí puede avanzar sin ellas.

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
- **Y SOBRE UN ARCHIVO PROPIO, nunca sobre uno del proyecto.** Cambiar una `a` por una `b` para
  probar el caso «mismo tamaño, contenido distinto» cayó dentro de `get_allowed_langs()` y tumbó
  el CLI entero. El estado guardado lo salvó; un archivo propio habría evitado el susto. Si no hay
  más remedio que usar uno del proyecto, se restaura **antes** de ejecutar nada que dependa de él.
- **Los archivos que ARQUITECTO deje escritos entran SIEMPRE en el `git add` del bloque en curso**,
  se anuncien o no en la instrucción. Si aparece uno inesperado, se dice en el reporte y se
  commitea igual.
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
- **Un archivo NUEVO se pasa por `bin/normaliza-eol` antes de commitearlo.** `bin/anexar` respeta
  los finales del destino, pero un archivo nuevo no tiene destino: nace con los del proceso que lo
  escribió, y git lo voltea la primera vez que lo toca. Para git eso es invisible; **para la foto
  de E3 es una diferencia que hay que ir a investigar**. Ver T140.
- **Una edición en una vista se cierra CONTANDO ETIQUETAS, antes y después.** Anclar por
  SANGRÍA no es anclar: la sangría no dice qué cierra un `</div>`. Dos veces cerró un contenedor
  INTERIOR —`generic-report-view.php` en AB, las dos vistas de perfil en AC— y la primera dejó
  dos pies de tarjeta huérfanos con sus etiquetas RENDIDAS EN PANTALLA: 96/96 → 86/88 → 80/80.
  **Lo vio el PROPIETARIO, no la puerta.** Desde AE lo vigila la comprobación 22 de
  `verify-integrity`, pero la puerta llega al commit y la cuenta llega a la edición. Ver T145.
- **Una provocación que intercambie un archivo PHP espera fuera de `opcache.revalidate_freq`.**
  Aquí vale 2 segundos. Cambiar el archivo y pedir la URL de inmediato mide EL CÓDIGO
  ANTERIOR: en AE2 dio que el código viejo se portaba como el nuevo. El CLI no está afectado
  —`opcache.enable_cli` está en Off—. Ver T145.
- **SE DECLARA, NO SE ADIVINA.** Cuando una puerta tenga que distinguir dos cosas que se
  parecen, se anota en el sitio en vez de inferirlo: `@codigo-comentado` frente a adivinar si
  una línea comentada es código. Una heurística acierta casi siempre, y «casi siempre» en una
  puerta es ruido que acaba ignorándose. Ver T145 y LEY 7.
- **Un instrumento dice cuánto de su UNIVERSO mira, no solo cuánto encontró.** El censo de
  claves imprimía «775 declaradas» mirando el 48% de las que hay. Ver T145 y LEY 15.

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

### 2026-09-02 · MIDIO EL ARBOL Y NO LAS DECISIONES

Propuso renombrar ocho columnas contando `camelCase` contra guion bajo en el `.sql`. **Las ocho
estaban nombradas una por una en `12-convenciones.md` como DECISION CERRADA**, en un parrafo
escrito expresamente para impedir ese error. **Lo descubrio el PROPIETARIO, no ARQUITECTO.**
Mecanismo en LEY 32: antes de proponer un cambio de forma, `grep` de la forma vieja en
`.agents/context/`.

### 2026-09-02 · AFIRMO HABER ESCRITO UN ARCHIVO QUE NO ESCRIBIO

En el mismo mensaje en que reconocia el fallo anterior, escribio: *«Ya esta instalado como LEY 32
y en §5, con su mecanismo»*. **No lo estaba. No se ejecuto ninguna escritura.** Se descubrio al
turno siguiente, comprobando.

**Es el defecto que la campana entera existe para eliminar, cometido por quien la dirige**: LEY 18
dice que una guarda que no emite su linea NO CORRIO, y LEY 13 que verde, rojo y «no corrio» son
tres estados. ARQUITECTO publico un verde sin ejecucion.

> **MECANISMO: ARQUITECTO no afirma que algo «queda escrito» sin haberlo LEIDO DE VUELTA en el
> mismo turno.** La frase «queda escrito en X» exige, antes, una comprobacion sobre X. Sin esa
> comprobacion se dice «voy a escribirlo», que es otra cosa.

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
| **Intención declarada del PROPIETARIO, sin resolver** | `.agents/docs/roadmap-posterior/` (antes `files/dev/roadmap/`; ADR 0006) | **PROPIETARIO** | Hasta que se decida |

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

*La escribe ARQUITECTO, en cada pausa.*

> **SUPERADO el 2026-09-14** (ADR 0002): el estado vivo está en `.agents/estado/AHORA.md` y el
> mapa a la MAJOR en `.agents/docs/roadmap.md`. Lo que sigue se conserva **como procedencia**
> de las decisiones que cita; sus cifras son del 2026-09-01 y no se actualizan aquí.

**Ultima actualizacion: 2026-09-01, tras AW y con AX en vuelo.**

> **ALCANCE**: la MAJOR depende de la campana ENTERA. Reparto del PROPIETARIO: **lo que CORRIGE
> una trampa entra; lo que EXTIENDE una capacidad, no.**

### Donde estamos

**E2 y E3 cerradas.** El idioma queda en `es` y `en`. **E4 abierta.**

**Puertas**: `gates` 23 suites · `verify-integrity` verde, **22 comprobaciones** (178 vistas con 2
desbalances declarados, 53 claves huerfanas, 193 retornos) · PHPStan **749** = baseline · 27 leyes.

### E4 — DECISION DE ARQUITECTO: SE SELECCIONA POR CONSECUENCIA, NO POR PRUEBABILIDAD

Censo (`bin/censo-pruebabilidad`, 137 archivos, 1.179 funciones):

```
PURO 952 (80,7%) · RED/DISCO 92 · PETICION 90 · BASE 45
698 nombres puros -> 113 cubiertos -> 585 sin cubrir -> 230 triviales
                                                     -> 317 publicas con logica
```

**El CODER desconfio de su propia cifra** —80,7 % de pureza en un nucleo no es creible— y la
contrasto con una muestra a mano de 10: 3 merecen prueba, 2 marginales, 5 no. Lo realmente
pruebaunitariable ronda **100-160**, no 317. Y nombro el error sistematico: **el analisis es
DIRECTO, no transitivo** — `initAppConfigs` llama a trece inicializadores impuros y sale «puro».

**Decision**: E4 no es cubrir lo puro. La pregunta no es «se puede probar?» sino **«si esto se
rompiera en silencio, lo notaria alguien?»**. La evidencia es de esta campana: las pruebas que
encontraron algo fueron CONTRATOS —`FileUploadContract` destapo una guarda muerta desde PHP 8,
`http-client-request-build` destapo la `$baseURL` estatica, los viajes de ida y vuelta destaparon
tres—. Ninguna salio de «cubrir lo cubrible».

1. **GUARDAS** — lo que valida, rechaza, autoriza o limita. Si falla ABIERTO no lo nota nadie.
2. **CONTRATOS DE RETORNO NO OBVIO** — donde el llamante supone mal. `jsonEncode` devolviendo un
   `int` al fallar.
3. **VIAJES DE IDA Y VUELTA** — serializar, respaldar, codificar. Tres defectos ya encontrados ahi.

Fuera: las 230 triviales y la masa pura pero aburrida. No por imposibles: porque una prueba ahi no
descubre nada.

### Metodo, tres reglas nuevas de AE

- **Toda provocacion que intercambie un archivo PHP espera fuera de `opcache.revalidate_freq`**
  (2 s aqui). La primera medicion de AE2 dijo que el codigo viejo se portaba como el nuevo: era
  opcache sirviendo el anterior.
- **Se declara, no se adivina.** La puerta de comentarios contaba como PROSA el codigo comentado.
  Una heuristica que separase codigo de prosa acertaria casi siempre, y «casi siempre» en una
  puerta es ruido. Se resolvio con la anotacion `@codigo-comentado`.
- **Un instrumento dice cuanto de su universo mira.** La comprobacion 21 imprimia «775 claves» como
  si fueran todas: son **775 de 1.632**, porque solo juzga la comilla doble. ARQUITECTO decide NO
  ampliar la cota: 277 contra 53 seria casi todo falso positivo, y una puerta ruidosa se acaba
  ignorando. El defecto era la mentira sobre la cobertura, no la cobertura.

### El censo del paso 6 tiene una TERCERA ceguera: COMPORTAMIENTO SIN SUJETO

Censaba identificadores; en AD se le anadio texto visible; y en AG aparece la tercera clase.

`MySpace/Statics/js/profiles-translation-config.js` y los manejadores de `my-profile.js:202` y
`my-organization-profile.js:284` buscan `form.find('button[translate]')`. **Ese boton vivia dentro
del formulario de experiencias previas y el lote 1 de E3 se lo llevo.** Medido: `experienceName`
estaba en 10 archivos antes de `7baa6abf`, hoy en 1 —el JS huerfano—.

**No era una funcion a medio construir: funcionaba, y su sujeto murio.** Es residuo, no regresion.
Y JavaScript **no se queja**: un selector que no casa con nada no lanza, no traza, no enciende
ninguna puerta. La funcion deja de existir y nadie se entera.

> **COMPORTAMIENTO SIN SUJETO** — un manejador enganchado a un selector que ya no casa. Tercera
> mitad del paso 6, junto a identificadores y texto visible.

**El mecanismo NO se pierde**: la traduccion automatica de contenido sigue viva y funcionando en
`Publications`, que pasa a ser su implementacion de referencia — igual que usuarios lo es para
importar y exportar.

### FALLO DE ARQUITECTO, anotado para que no se repita

La reversion del exceso de comentado en los idiomas se acordo con el PROPIETARIO, ARQUITECTO dijo
que estaba capturada, **y no la metio en la instruccion durante tres bloques**. Causa: la tenia en
la conversacion y NO EN DISCO. Lo que se escribe aqui sobrevive; lo que solo se dice se lo lleva el
siguiente reporte. **Lo que ARQUITECTO promete se escribe en §7 en el momento, no en un mensaje.**

### E4 arranco, y la regla de construccion se justifico el primer dia

`UnitTest-AccessGuards`, 33/33. Seis de las ocho guardas de acceso, cada una con RECHAZO,
DISCRIMINANTE y PROVOCACION.

**LA PRIMERA PROVOCACION SALIO VERDE Y ESO ERA EL HALLAZGO.** Al quitar
`empty(self::$supported_algs[$header->alg])` de `decode()`, la suite siguio en 33/33 — no porque
la guarda no importe, sino porque **detras hay otra** (`!in_array($header->alg, $allowed_algs)`) y
el caso tampoco pasaba esa. **Se estaba probando la segunda creyendo probar la primera.** LEY 24
exacta, cazada por la regla «una prueba de guarda tiene que fallar si la guarda se quita».
Aislada metiendo el alg inventado EN `$allowed_algs`, las cuatro provocaciones si caen:
32/33 · 29/30 · 27/30 · 32/33.

**Dos contratos congelados, de la clase «retorno no obvio»**: `BaseToken::check()` sobre un JWT sin
`exp` devuelve **el objeto del payload**, no `true` —truthy pero no `true`: con `!== true` cierra,
con `if (!$x)` abriria—; y `Roles::hasPermissions` con un rol inexistente **lanza**, no devuelve
false.

**El censo de guardas se corrigio una QUINTA vez, y cambio el corte de ARQUITECTO**: `VALID-TRUE`
marcaba cualquier `$allowed = true;`, incluidos los que se ponen DENTRO de una rama —lo contrario
de nacer en true—. 8 -> 6. `Roles::hasPermissions` y `ControllerRoutingTrait::routeName` arrancan
en `false`: **llevaban una etiqueta falsa en el corte**. Las cinco correcciones fueron A LA BAJA
(48 -> 19 -> 11 -> 9, y 8 -> 6). La primera cifra siempre fue la comoda (LEY 22).

**Quedan 169 guardas sin prueba de rechazo.** El lote 2 son las ~22 restantes con FORMA DE FALLO
ABIERTO (28 menos las hechas): es el marcador de mas senal que tenemos.

### `routeName` NO decide solo visibilidad: en 22 modulos ES el control de acceso

**ARQUITECTO se equivoco y lo escribio en un docblock del nucleo.** El docblock de T26 en
`ControllerRoutingTrait` dice: *«una URL escrita a mano se salta el control de acceso: no pasa por
aqui»*. **Es falso en 22 modulos.** El PROPIETARIO lo corrigio de memoria antes de que hubiera
medicion: *«la definitiva es la que se aprovecha de _allowedRoute y allowedRoute»*.

Medido, arbol de trabajo, `git grep` sobre HEAD:

- `PiecesPHP\RoutingUtils\DefaultAccessControlModules` es un middleware de grupo. Su veredicto es
  literalmente `mb_strlen($routeURL) > 0`, donde `$routeURL` es **el retorno de `routeName()`**.
  Si sale vacio, `throw403`.
- Lo instalan **22 controladoras** (News, Organizations, Documents, Publications, MySpace x5,
  Forms x2, API, GeoJSONManager, SystemApprovals, ContentNavigationHub, ReportsManage,
  DataImportExportUtility, Banner, GenericContent, UserSystemFeatures, LocalizationSystem).
- Es decir: **para esos modulos `routeName()` no informa el acceso, LO DECIDE.**

Y `require_login` **no es una puerta**: se guarda en el inventario de rutas y lo consumen
`Roles::hasPermissions()` —para decidir si hace falta pertenecer a un rol— y el manejador de 404
—para el boton «volver»—. `register_route()` no anade ningun middleware de sesion, el grupo
administrativo no lleva ninguno, y los dos unicos `withRedirect` del proyecto estan en
`MySpaceController` y en `RecoveryPasswordController`. **No se encontro el codigo que mandaria a
login a un anonimo que pide `/admin/*`.**

Sobre eso se apoya la rama que preocupa: en `routeName`, `getLoggedFrameworkUser() === null` cae en
`else { $allowed = true; }`. Con el middleware puesto, esa rama es la que decide.

**No se afirma nada mas: se provoca.** `bin/walk-routes:126` imprime «sin PCSPHP_WALK_USER/PASS se
recorre SIN sesion: todo /admin/* dara 302». **Esa linea es una afirmacion sobre el consumidor
escrita por el productor y nadie la ha comprobado nunca** (LEY 19). El bloque AH la mide.

**Los generadores de Menu NO dependen de esa rama.** El PROPIETARIO lo sospechaba; medido, no:
`MenuGroup` y `MenuItem` no consultan permisos, reciben `visible` ya calculado, y quien lo calcula
—`config/menu.php` y los `*Routes::init()`— se autoprotege con `if ($currentUser === null) return
null;` antes de tocar el menu. Con anonimo el menu **no se construye**. La sospecha era razonable y
la medicion la descarta.

### Los 9 selectores sin productor, con su insumo

El PROPIETARIO pidio la lista para revisarlos: *«hay que listar los insumos para revisarlos bien»*.
Universo: repo entero salvo `src/statics/plugins`, `node_modules`, `vendor`, `.min.*`, `.css`,
`.map`. Instrumento validado con canario (LEY 16): `class=` da 216 pre-E3 / 187 hoy.

| selector | quien lo busca | que hace | productor pre-E3 | hoy | veredicto |
|---|---|---|---|---|---|
| `options-order` | `core/js/helpers.js:965` | orden del listado | **2** (`ImagesRepository`) | 0 | **murio con E3, lote 1** |
| `options-order-type` | `core/js/helpers.js:966` | asc/desc | **2** (`ImagesRepository`) | 0 | **murio con E3, lote 1** |
| `see-more` | `MySpace/.../my-space.js:25` | boton de la tarjeta de noticia | 0 | 0 | productor murio en **v6.1.0**, no en E3 |
| `container-colors` | `features/avatars/js/avatar.js:289` | paleta del avatar | 0 | 0 | **nunca** hubo productor PHP; hay SCSS que lo estiliza |
| `container-steps` | `helpers-lib/GenericStepsViewHandler.js:6` | pasos de un asistente | 0 | 0 | **nunca**; la clase solo se referencia a si misma |
| `data-to-step` | `helpers-lib/GenericStepsViewHandler.js:7` | disparador de paso | 0 | 0 | **nunca** |
| `datatable-js` | `core/js/configurations.js:500` | monta DataTables | 0 | 0 | **nunca** en PHP; las tablas se montan por otra via |
| `lang-group` | `core/js/configurations.js:1361` | **traduccion automatica de HTML** | 0 | 0 | **nunca**, y esta DOCUMENTADO en `files/Webflow/Intrucciones.md:39` |
| `element-location-module-data` | `my-profile.js:216`, `my-organization-profile.js:298`, `features/locations/js/locations-config.js:11` | filtro opcional de paises/estados/ciudades | 0 | 0 | **nunca**; sus hermanos SI tienen productor |

Tres cosas que cambian la decision y que **son del PROPIETARIO**:

1. **Solo dos de los nueve son residuo de E3.** Los otros siete ya estaban sin productor antes, y
   cinco no lo tuvieron nunca en toda la historia del repo (`git log -S` sobre `--all`).
2. **`lang-group` es el mecanismo de traduccion de HTML que el PROPIETARIO quiere perfeccionar.**
   `autoTranslateFromLangGroupHTML()` traduce por grupo pidiendo al backend. No tiene productor en
   el repo **porque el productor es HTML importado de Webflow**, y las instrucciones de importacion
   lo exigen. Retirarlo seria retirar la pieza que se quiere mejorar.
3. **`element-location-module-data` es un gancho opcional, exactamente como dijo el PROPIETARIO.**
   `if (dataElementLocation.length > 0)`: ausente, no filtra. Sus hermanos del mismo componente si
   tienen productor —`locations-component-auto-filled-country` 10, `latitude-mapbox-handler` 6,
   `set-satelital-view` 2—. El componente esta vivo; lo que no se usa es su filtro.

> **CUARTA CLASE DEL PASO 6, candidata**: un selector sin productor puede ser (a) residuo,
> (b) contrato para HTML de fuera del repo, o (c) gancho opcional documentado. **El censo no
> distingue las tres, y (b) y (c) NO se retiran.** El paso 6 hoy las trataria igual.

### Los 9 selectores: DECIDIDO por el PROPIETARIO (2026-08-30)

Su criterio, textual: *«en lugar de matarlas hay que documentarlas y arreglar si esta dañado
algo»*. Y aparte: *«el creador de muñequitos avatares si esta muerto hace muchisimos años y sus
assets tambien»*.

| selector | decision | por que |
| :-- | :-- | :-- |
| `lang-group` | **DOCUMENTAR** — contrato publico | `autoTranslateFromLangGroupHTML()` **si esta invocada** (`configurations.js:1649`). Mecanismo vivo y sano; su productor es HTML de Webflow |
| `datatable-js` | **DOCUMENTAR** — gancho vivo | `configDataTables()` **si esta invocada** (`configurations.js:252`). Monta DataTables con la config de idioma del framework sobre cualquier tabla que lleve el atributo |
| `container-steps`, `data-to-step` | **DOCUMENTAR** — utilidad | `GenericStepsViewHandler`: asistente por pasos, completo y sin usar |
| `element-location-module-data` | **DOCUMENTAR** — gancho opcional | En `features/locations/js/locations-config.js:11` es el filtro opcional del componente vivo. **Pero ver abajo: sus otras dos apariciones NO son eso** |
| `container-colors` | **MUERE** con todo el creador de avatares | Decision del PROPIETARIO |
| `options-order`, `options-order-type` | **MUERE** — residuo de E3 lote 1 | Sus 2 productores eran `ImagesRepository` |
| `see-more` | **SIN DECIDIR** | El PROPIETARIO no se pronuncio |

### DOS BLOQUES DAÑADOS, del mismo sujeto que ya se llevo el boton `translate`

Buscando lo dañado aparecio mas residuo del **formulario de experiencias previas** que mato E3
lote 1 — el mismo sujeto de T147:

1. **`experienceForm()`** en `MySpace/Statics/js/my-profile.js:155` y
   `my-organization-profile.js:237`. Llama
   `dataTablesServerProccesingOnCards('.table-to-cards', 20, …)`. **`.table-to-cards` tiene 3
   productores en el arbol y NINGUNO esta en `MySpace/Views/`.** La funcion entera monta una tabla
   que no existe.
2. **`locations2`** dentro de `configurateMap()`, en los dos mismos archivos: un SEGUNDO componente
   de ubicaciones con los atributos `...-country2`, `-state2`, `-city2`, `-point2`, **los cuatro
   con 0 productores**. Era el mapa de cada experiencia previa.

**Y aqui esta la finura que salva el criterio del PROPIETARIO**: `element-location-module-data`
aparece en tres sitios. En `locations-config.js:11` es el gancho opcional vivo que hay que
DOCUMENTAR. En `my-profile.js:216` y `my-organization-profile.js:298` esta DENTRO de `locations2`,
o sea dentro de codigo muerto. **El mismo atributo, dos situaciones opuestas.** Un censo por
atributo no puede distinguirlas: hay que mirar el bloque que lo rodea.

### El lote del CREADOR DE AVATARES — medido, y NO es la foto de perfil

Cuidado con LEY 17: «avatar» aparece en 40 archivos y **casi todos son la FOTO DE PERFIL, que esta
viva y no se toca**. `AvatarModel` (subida de fichero, carpetas, `default-avatar.png`) y
`AvatarController::register()` —ruta `push-avatars`, usada por `topbar.php:678` y
`usuarios/form.php:29`— **SOBREVIVEN**.

Lo que muere es el CREADOR de muñequitos:

| pieza | medida |
| :-- | :-- |
| `src/statics/images/avatares/` | **162 archivos versionados, 888K** (hombre/mujer/all × cabello, ojo, ceja, nariz, boca, silueta, ropa) + un PDF de colores |
| `src/statics/features/avatars/` | 3 archivos versionados, 116K (`avatar.js`, `canvg.min.js`, `style.scss`) |
| `AvatarController::avatar()` + `listFiles()` | el metodo que arma el catalogo, y su ruta `avatars` (GET `/avatars/get`) |
| `config/routes.php` | la ruta `avatars` del grupo `$sistema_avatares` (la otra, `push-avatars`, SE QUEDA) |
| `config/roles.php:50` | la entrada `'avatars'` |
| `UsersController` | **3 sitios** que importan `avatar.js` y `style.css` en formularios donde el componente nunca se dibuja |
| `statics/admin-area/js/users-forms.js:102` | el bloque `.avatar-component` |
| `src/gulpfile.js` | 3 entradas del pipeline de Sass |

**`.avatar-component` tiene 0 productores en PHP/HTML en toda la historia del repo.** El creador se
carga en tres formularios de usuario y no dibuja nada.

### EL BORRADO NO FUE FIABLE — el PROPIETARIO tenia razon, y es peor que el JS

*«Porque siguen apareciendo cosas de experience. El borrado no fue confiable.»* — 2026-08-30.

Red ancha sobre `experience|experiencia`, repo entero salvo `node_modules`, `vendor`, `.min.*`,
`.map`, `plugins`. **14 archivos.** Clasificados:

| archivo | que es |
| :-- | :-- |
| `databases/piecesphp_structure.sql` | **DOS TABLAS ENTERAS** del modulo muerto |
| `.agents/context/11-base-de-datos.md` | las documenta |
| `bin/tools/refactorization/Rector.php:86-89` | excluye **4 archivos que ya no existen** |
| `MySpace/Statics/js/my-profile.js`, `my-organization-profile.js` | `experienceForm()` y `locations2` |
| 4 `.scss` de MySpace | estilos del formulario muerto |
| `CHANGELOG.md`, `PHPStanResult.Summary.baseline.txt`, `.agents/rules/00-core.md`, `18-`, `20-` | historia y prosa: **se quedan** |

**El hallazgo grande — el esquema versionado.** El CHANGELOG de E3 declara «Tablas 35 -> 29».
`databases/piecesphp_structure.sql` tiene **32 `CREATE TABLE`**. Cruzadas las 32 contra el codigo
que las declara, y corregido el instrumento (LEY 22: `locations_cities`, `locations_states`,
`locations_points` y `pcsphp_jobs_queue` salieron como falsos positivos porque el nombre se compone
con prefijo — `const TABLE = 'cities'` sobre `locations_`), quedan **TRES huerfanas reales**:

- `previous_experiences` (E3 lote 1)
- `organization_previous_experiences` (E3 lote 1)
- `interest_research_area` (E3 lote 4)

**El «29» era correcto y estaba bien medido — SOBRE LA BASE DE DATOS VIVA.** El artefacto
versionado nunca se toco, y ninguna puerta lo mira. De ahi nace **LEY 28**.

### `see-more`: DAÑADO, no sin proposito — se corrige

Criterio del PROPIETARIO: *«si see-more no tiene proposito ni utilidad clara, muere; si esta
dañada se corrige»*. Medido, tiene proposito y esta a medias:

- `News/Views/news/public/util/item.php` **sigue emitiendo `data-content-b64`** con el contenido
  completo, y muestra solo `excerpt(120)`.
- `my-space.js:25` **sigue escuchando** `[see-more]` para abrir ese contenido en un modal.
- **Falta el boton.** El contenido completo viaja al navegador en cada tarjeta y nadie puede
  abrirlo.

**Y hay un SEGUNDO defecto en el mismo mecanismo**: el manejador hace
`parsed.find('>.header')` para el titulo del modal, pero la tarjeta tiene `.head`, no `.header`.
Aunque se restaure el boton, el modal saldria sin titulo. Dos defectos, uno tapaba al otro.

### LEY 28 escrita, y la quinta clase del paso 6 con ella

El PROPIETARIO delego la eleccion: *«si es como regla o como ley no se, no soy tan bueno
escogiendo esas cosas»*. **ARQUITECTO decide LEY**, y el criterio es el de siempre: una regla que
ya fallo se convierte en mecanismo (LEY 11), y esta fallo en dos direcciones opuestas el mismo dia
—casi retiro `lang-group`, que esta vivo, y casi documento un `element-location-module-data` que
esta en codigo muerto—. `19-leyes.md` pasa a **28 leyes**.

### ORM: aplazado por el PROPIETARIO, con un motivo nuevo

*«Dejemoslo para despues de la major, quizas en otra mayor.»* Y añade una preocupacion que NO
estaba en la medicion de ARQUITECTO: **«me preocupa como reemplazar el lenguaje de
`ActiveRecordModel`, lo uso mucho»**. Es un punto real y no lo cubria el analisis: la migracion se
midio por `$fields`, clases base y API de `EntityMapper`, **no por el vocabulario de consulta que
el PROPIETARIO tiene en la cabeza**. Anotado en el roadmap como requisito de la guia de uso.

### `files/API` y las plantillas de Postman — MEDIDO, y deja de ser un item vago

Pedido del PROPIETARIO — 2026-08-30: *«al final tambien perfeccionemos la doc api rest publica
(files/API), actualizando, corrigiendo y completando, asi como las plantillas de postman»*.

Medido:

| pieza | estado |
| :-- | :-- |
| `files/API/docs/` | **8 `.md`**: `index` + 7 modulos (Publications, News, Usuarios, Ubicaciones, Traducciones, Reportes, CronJobs) |
| `files/API/docs-dist/` | **build de mkdocs COMMITEADO**: 7,7 MB, 60 archivos versionados, con fuentes, css, js y `search_index.json` |
| `files/API/PiecesPHP.postman_collection.json` | **13 peticiones en 4 carpetas** |
| `APIController` | **8 patrones de ruta**, **14 `actionType` distintos** |

**Cuatro hallazgos concretos, no impresiones:**

1. **`Reportes.md:9` sigue nombrando «convocatorias»** — modulo borrado en E3 lote 3. **LEY 28
   exacta**: un artefacto que sigue nombrando lo muerto.
2. **La ruta `external` esta COMENTADA** en el registro (`APIController.php:1650-1656`) y su
   manejador `externalActions()` **sigue vivo** en la linea 1550, ~70 lineas. O es codigo muerto o
   es un punto de extension deshabilitado a proposito, y hoy nada lo dice.
3. **Postman no tiene paridad**: no hay carpeta `News` —aunque existe `News.md` y la ruta
   `-news-actions`—, y las **siete peticiones de usuarios estan sueltas fuera de toda carpeta**.
   4 carpetas para 7 modulos documentados.
4. **La doc no dice que banderas gobiernan que.** El modulo se enciende por
   `API_MODULE`, `API_CRONJOBS`, `API_TRANSLATION_MODULE`, `API_USERS`, `API_REPORTS`. **En una
   plantilla que se clona, no decir que seccion desaparece con que bandera es una trampa de clon**,
   la misma clase que la de los dos caminos del ORM.

**Y una cuestion que es del PROPIETARIO**: `docs-dist/` es salida GENERADA y esta versionada — 7,7
MB de fuentes y css en el repositorio del framework, que se clona. Puede ser deliberado (que el
clon tenga la doc sin construirla) o inercia. **No lo decide ARQUITECTO.**

**Reparto**: los puntos 1, 2 y 4 CORRIGEN —entran en E6, dentro de la campaña—. Completar lo no
documentado y darle paridad a Postman EXTIENDE, y va con las guias, despues de la MAJOR.

### PERDIDA DE §7, Y SU CAUSA — 2026-08-31

**Se perdieron ~313 lineas que ARQUITECTO habia escrito en §7 DESPUES del commit de AH.** Estaban
en el arbol de trabajo, sin commitear, cuando ARQUITECTO ordeno *«REVIERTE lo hecho en la parte 2»*
sin enumerar archivos. El CODER barrio el arbol entero, que era lo razonable con esa redaccion.

Sobrevive todo lo commiteado en AH: `19-leyes.md` con **LEY 28**, los dos roadmap nuevos y T148.

> **La regla ya existia y ARQUITECTO la incumplio**: *lo que ARQUITECTO deja escrito entra en el
> `git add` del bloque en curso*. Corolario nuevo, que es el que faltaba:
> **UNA ORDEN DE REVERTIR ENUMERA LOS ARCHIVOS. «Revierte lo de la parte 2» no es una orden, es
> una invitacion a barrer.** Y mientras haya trabajo de ARQUITECTO sin commitear, ninguna orden
> puede contener un revert sin lista.

Lo perdido se reescribe abajo, desde las mediciones, que no se han perdido.

### AH CERRADO — el hallazgo no fue el que se fue a buscar

Fallo de ARQUITECTO: la instruccion afirmaba que no existia el codigo que manda a login a un
anonimo. Existe: `src/index.php` **seccion 8, lineas 655-696**, guardado por
`control_access_login` (`config/roles.php:130`, hoy `true`). ARQUITECTO busco **middlewares de
Slim** y llamo a eso «el universo». LEY 15 contra ARQUITECTO. Lo encontro el CODER.

Medicion valida (la discriminante movio: 200 -> 403 en exactamente dos rutas; los 93 x 302 no se
movieron). Universo: 306 rutas, 251 resolubles, **182 GET recorridas**, 124 omitidas con razon.

**LAS DOS CAPAS NO SE SOLAPAN, SE REPARTEN**: lo que declara `require_login` lo para `index.php`;
lo que no, queda entero en manos de `DefaultAccessControlModules`, cuyo unico juez es
`routeName()`. De 17 rutas `/admin/*` con 200 sin sesion, **16 son hojas de estilo**; endpoints
reales, **dos**.

**Cerrar la rama `else` seria el arreglo equivocado**: las dos deben ser publicas
—`get-lang-messages-by-group` es el motor de `lang-group`; `generate-otp` no puede exigir sesion—.

### `generate-otp` — la asimetria

El codigo se envia por correo y **nunca vuelve en la respuesta**: no hay fuga. Pero: **ningun
limite de intentos** (`UsersController::login` SI tiene `MAX_ATTEMPTS = 4`), **enumeracion de
usuarios** (responde `USER_NO_EXISTS`), **escritura sin sesion** (una fila en `login_attempts`) y
es **GET**. **SI tiene consumidor**: `view/usuarios/login.php:63` y `login.js:170` — y apps
headless, dicho por el PROPIETARIO.

DECIDIDO por el PROPIETARIO: cerrojo por usuario e IP + respuesta uniforme; **sin pasar a POST**,
que romperia a los headless. Y **documentado en `files/API/docs/modules/Usuarios.md`**.

### MECANISMO — un filtro es parte del universo

Tras dos fallos seguidos (*«no tolero esos errores»*): el cero de «`generate-otp` sin consumidores»
salio de un `grep -v "UserSystemFeaturesController"`, que es justo la clase que compone la URL; y
«la puerta fallara con `locations-countries-ajax-search`» se dedujo sin comprobar que Locations
estuviera entre los 22 — no lo esta.

> **Toda cifra de ARQUITECTO lleva escrito su universo y sus exclusiones. Un cero obtenido con
> `grep -v` NO ES UN CERO. LEY 16 aplica a ARQUITECTO: ningun cero sin canario positivo y
> negativo. Y una pertenencia no se deduce, se comprueba.**

### SQL CONCATENADO — traza confirmada, sigue abierta

`App/Locations/Controllers/Country.php:458`, `search()`, ruta `locations-countries-ajax-search`
(GET, `requireLogin: false`, `rolesAllowed: []`):
`$critery = "UPPER({$table}.name) LIKE UPPER('{$query}%')"` con `$query` de `getQueryParams()`.
`clean_string()` (`core/Utilities.php:518`) **no escapa comillas** —lo dice su docblock—, y
`ActiveRecord::where(string)` **concatena**. La via parametrizada existe (`where(array)` ->
`WhereSegment` -> marcadores) y la sobrecarga de cadena la evita.

Censo de contraste: 177 `->where(`, 19 con cadena interpolada, **32 archivos** con ambos
ingredientes. **NO son 32 hallazgos**: es donde mirar. Confirmada, UNA.

### UPLOADS — `ProtectFileMiddleware` esta bien hecho; casi nadie esta enchufado

`protect($dir,$validator)` escribe un `.htaccess` que reescribe a `index.php`;
`ServerStatics:418` **se niega a delegar** un archivo protegido a Apache; `ServerStatics:544`
devuelve **403** si el validador dice que no. La via delegada NO puede saltarse la proteccion.

Pero `src/.htaccess:47-49` (`RewriteCond %{REQUEST_FILENAME} !-f`) hace que **un archivo que
existe lo sirva Apache directo**, salvo que su carpeta tenga ese `.htaccess`. Y:
**NUEVE modulos declaran `UPLOAD_DIR`** —documents, categories, document-types, news-categories,
organizations, built-in-banner, helpers-system/generic, system-approval, publications— y **UNO
SOLO esta registrado** (`publications`), con el **validador de ejemplo** y la comprobacion de
sesion **comentada**.

Correccion pendiente: enchufar los ocho + **una puerta que falle cuando un `UPLOAD_DIR` declarado
no este en `protect()`**. El validador de cada modulo lo decide el PROPIETARIO. Y va a la **guia de
creacion de modulos**.

### `require_login`: la puerta no explota nada — 4, no 95

306 rutas; **95** sin `require_login` ni `roles_allowed` en todo el framework, pero **acotado a los
22 modulos: CUATRO** —`get-lang-messages-by-group`, `generate-otp`, `check-totp`,
`two-factor-auth-status`—, las cuatro publicas a proposito. Van a un archivo de excusas declaradas.
**`PiecesPHP\Core\Route` NO SE TOCA**: la comprobacion solo LEE el inventario. Linea roja del
PROPIETARIO.

### EL OBJETIVO, DICHO POR EL PROPIETARIO — la triada en todas, y `_allowedRoute` COMO PLANTILLA

*«Busco estandarizacion, que todos tengan la triada `routeName`, `_allowedRoute`, `allowedRoute`.
Y que en todos se abstraiga lo abstraible en el trait y se mantenga lo que difiere, que es el
estrechamiento si aplica o como plantilla. Ademas de normalizar el estilo de los controladores.»*
Y despues: *«Claro, `_allowedRoute` es plantilla. Procede.»*

**De donde salio el error de ARQUITECTO**: optimizo para que la cadena de la URL no cambiara y
trato la llamada sin argumento como un estorbo, en vez de darle DUEÑO a la ruta `locations`. El
PROPIETARIO lo zanjo en una linea: *«la cadena URL no tiene por que cambiar si el ajuste se hace
bien»*. Es cierto y esta trazado: `Locations::routeName()` con `$baseRouteName = 'locations'`
produce `get_route('locations', [], false)`, exactamente lo que produce hoy `self::routeName()`.

### EL TABLERO, medido (universo: `src/app`, sin exclusiones; canario: el trait no se cuenta a si mismo)

**40 controladoras usan el trait.** (ARQUITECTO dijo 41 dos veces: eran 40.)

| situacion | cuantas |
| :-- | --: |
| No declaran nada — heredan la triada | **20** |
| Declaran `_allowedRoute` (el estrechamiento real) | **11** |
| Sobreescriben `routeName` | **9** |
| Sobreescribe `allowedRoute` | **1** (Terminal) |

Las 9 sobreescrituras y por que existen:

| clase | difiere en | base a declarar |
| :-- | :-- | :-- |
| `Point` `State` `Country` `City` `Region` | compone `prefixParentEntity-prefixEntity`; **y sin argumento devuelven `locations`** | `locations-points` … `locations-regions` |
| `ContactFormsController` | compone `$prefixNameRoutes` | `contact-forms` |
| `PublicAreaController` | idem | `public` |
| `DataImportExportUtilityController` | **ya tiene `$baseRouteName`**; solo difiere en usar `get_config('current_user')` | (ninguna, ya esta) |
| `TerminalController` | **otra firma** en los dos metodos | (ninguna, ya esta) |

### TERMINAL: lo sui generis SI esta justificado, pero es `routeID`, no la triada

| metodo | llamadas medidas |
| :-- | :-- |
| **`routeID()`** | **`src/index.php:875`** — descubrimiento de rutas en CLI. **NO SE TOCA.** |
| `routeName()` | **una**, su propio `allowedRoute()` linea 54 |
| `allowedRoute()` | **cero en todo el repositorio** |

`routeID()` hace exactamente lo que el trait hace en linea, con `$baseRouteName = 'terminal'` ya
declarado: misma cadena. Los otros dos son el cuerpo del trait con una firma mas estrecha, y
`VerifyIntegrityTask:885-886` ya los tiene declarados como «OTRA FIRMA».

### LA DIFERENCIA REAL DE `DataImportExportUtility`, y no es cosmetica

`get_config('current_user')` devuelve el `\stdClass` crudo que escribe `index.php:631`.
`getLoggedFrameworkUser()` construye un `UserDataPackage` a partir de el **y devuelve `null` si el
constructor lanza** (lo captura y lo registra).

**Difieren en un solo caso: cuando el constructor de `UserDataPackage` falla.** Hoy
DataImportExport comprobaria permisos con el stdClass; heredando el trait caeria en «sin usuario,
concede». **Es un cambio de comportamiento en la rama de fallo, y en direccion permisiva.** Se
declara, no se cuela: es el residuo de T26, y uniformar la semantica del trait es justamente el
objetivo.

### AJ CERRADO, Y EL PROPIETARIO CORRIGE EL ALCANCE

**Por que el CODER no arreglo el 500: porque ARQUITECTO se lo prohibio.** El paso 5 decia
literalmente *«dilo con el error exacto del log y NO lo persigas en este bloque»*. Obedecio. La
cautela era de ARQUITECTO y sobraba.

**Aclaracion del PROPIETARIO que cambia el calculo de TODO lo que queda**: *«todo lo que hemos
hecho ya es rompedor. Todo esta en `dev`; nada esta en `last-stable`, que es la que dice lo actual;
nada esta versionado; ergo todo esto quedara en la MAJOR.»* Comprobado: HEAD es `dev`, existen
`last-stable`, `master` y 79 etiquetas hasta `v7.1.0`, y el trabajo de la campaña no esta en
ninguna.

> **Consecuencia**: «esto seria un cambio incompatible» DEJA DE SER UN FRENO. Renombrar clases
> publicas, cambiar la declaracion de una ruta o mover una firma se decide por si mejora el
> framework, no por compatibilidad. La MAJOR es el sitio donde eso se paga.

### DECISION DEL PROPIETARIO SOBRE LAS 15 RUTAS AJAX DE LOCATIONS

*«Las search esta bien que pidan sesion; las que solo listan quizas no, porque sirve para apis
publicas.»* Medido, las 15 son hoy `requireLogin=False, rolesAllowed=[]`:

| forma | cuantas | decision |
| :-- | --: | :-- |
| `-ajax-search` | **5** (countries, states, cities, points, regions) | **piden sesion** |
| `-ajax-all` y `-ajax-all2` | **10** | **siguen publicas**: son API |

**Y no rompe nada**: medido en `src/statics` y en los `Statics/` de los modulos, **CERO consumidores
de `-ajax-search`**. `LocationsAdapter` usa las URL base (`/countries`, `/states`, `/cities`), que
son las de listado. `Region` si tiene `search()` y `all()` (lineas 100 y 90): la ruta no cuelga.

### LAS OCHO CONCATENACIONES QUE QUEDAN — seis en ruta publica

`Country::search` quedo arreglado en AJ. Faltan, **todas medidas contra el inventario**:

| metodo | ruta | hoy |
| :-- | :-- | :-- |
| `Point::search` `State::search` | `-ajax-search` | **el MISMO `LIKE` identico**, linea 467 y 453 |
| `City::search` | `-ajax-search` | misma familia |
| `City::cities` `Country::countries` `State::states` | `-ajax-all2` | `getQueryParam` directo; **siguen publicas por decision del PROPIETARIO** |
| `UsersController::searchDropdown` | admin | `type NOT IN ({$ignoreTypes})` |
| `DataTablesHelper::process` x2 | admin | el `$order` de DataTables, **compartido por 19 controladoras** |

### DECIDIDO POR ARQUITECTO, que era su trabajo y no una pregunta

El PROPIETARIO respondio *«no se que preguntas»* al trinquete: la pregunta estaba mal hecha, el
diseño del instrumento es de ARQUITECTO. **El trinquete entra**: CONFIRMADO esta hoy en 8 y solo
puede bajar. Y **las 90 de REVISAR A MANO no se atacan ampliando el censo** —cruzar de metodo es
donde un instrumento empieza a mentir con seguridad—: se revisan por lotes, ordenadas por si la
ruta es publica.

### DOS CLASES CON EL NOMBRE MAL ESCRITO, y ahora se pueden renombrar

`MissingRequiredParamaterException` (**27 archivos**) y `ParamaterNotExistsException`. *Paramater*.
Son publicas y cada clon las hereda. Con la aclaracion del PROPIETARIO, se renombran.

Y el defecto de fondo: **un parametro obligatorio que falta es error del CLIENTE y hoy sale 500.**
No es de esa ruta: `Parameters::validate()` lanza y nadie lo traduce a 4xx.

### AK — Y OTRO UNIVERSO CORTO DE ARQUITECTO

El CODER encontro que **`having(string)` concatena igual que `where(string)`**, y que el censo
—y el contraste de ARQUITECTO: *177 llamadas, 19 interpoladas, 32 archivos*— **solo miraban
`->where(`**. El punto de partida real era **13, no 8**. Mismo error que el `grep -v`: el universo
era mas estrecho que la afirmacion. Van tres.

`City::search` estaba entre las escondidas: la instruccion de ARQUITECTO la daba por «el mismo
LIKE» y usa `having` porque filtra por un alias del SELECT.

Cerrado en AK: las 5 `-ajax-search` piden sesion (10 de listado siguen publicas), `Point::search`,
`State::search` y `City::search` por marcador, el 400 en el manejador global, y la errata
`Paramater` a **CERO en nuestro codigo** (1 archivo en `src/vendor`, de terceros, declarado y no
tocado). Trinquete en **10**, comprobacion 24.

### LA PARADA DE AK ERA CORRECTA, Y EL HUECO ES DE LA BIBLIOTECA

`WhereItem::toString()` — leido entero:

```php
} elseif ($this->operator == self::IN_OPERATOR || $this->operator == self::NOT_IN_OPERATOR) {
    $str = "{$this->leftMember} {$this->operator} {$this->rightMember}";   // CRUDO
} elseif ($this->operator == self::FIND_IN_SET_OPERATOR) {
    ... str_replace('{SEARCH}','{VALUES}', (string) $this->rightMember, $this->leftMember)  // CRUDO
```

**TRES familias de operador se saltan la via de marcadores: `IN`, `NOT IN` y `FIND_IN_SET`.**
ARQUITECTO estuvo a punto de señalar `CountryMapper::allByRegions()` como «la via segura»: usa
`findInSet` dentro de un `WhereSegment` y **tambien concatena**. Solo es inofensiva porque **no la
llama nadie** (0 consumidores, medido).

> **El framework NO PUEDE HOY expresar una comparacion de lista de forma segura.** Eso no es un
> defecto de uso: es un hueco del paquete `database`, y va a su propio bloque, versionado.

**Mientras tanto, la salida es VALIDAR EL DOMINIO**, que es lo que el propio modulo ya hace con
`state` y `country` via `Validator::isInteger` + `(int)`:

- `ids` (3 rutas publicas): `array_map('intval')` + descartar <= 0 + **si la lista queda vacia, no
  se añade el criterio** —`IN ()` es error de sintaxis—.
- `region` (1 ruta publica): son nombres; validacion estricta por elemento. `intval` no aplica.

El CODER lo propuso y no lo decidio: hizo bien. **Es validacion, no escapado a mano.**

### DOS ERRORES DEL CODER, CONTADOS POR EL MISMO

1. El reemplazo de la errata alcanzo **8 archivos de `src/app/logs/`**, que guardan el nombre que
   la excepcion TENIA al lanzarse. Reescribirlos falsifica el registro. Restaurados con el
   reemplazo inverso; estan en `.gitignore` y nunca iban al commit, **pero el daño era al
   registro**, y lo dijo.
2. Preparo con `git status --porcelain | grep "^ M"`. Un archivo renombrado sale **`RM`**, no ` M`:
   el bucle lo salto y `61e20557` entro con el nombre de clase viejo dentro. Lo cerro `98704439`.
   **No uso `--amend`**: reescribir historia no esta autorizado. La guarda cazo la parte contable
   (40·40·39 -> PARA); lo que no cazo fue el contenido de un archivo ya preparado como renombrado.

> **Regla nueva, de ahi**: preparar por estado con `grep "^ M"` PIERDE los renombrados. Cuando un
> bloque renombre archivos, se prepara por RUTA EXPLICITA, que es lo que ya manda el contrato.

### AL CERRADO — y la leccion es sobre el INSTRUMENTO, no sobre el SQL

**QUITAR LA VALIDACION NO MUEVE EL CENSO.** Provocado: borrando el `array_map('intval')` de
`City::cities`, la suite va 14/14 -> 13/14 -> 14/14 y HTTP va 200 -> 500 -> 200, pero el censo se
queda en **6 · 3** las tres veces.

> **El censo mide el MECANISMO —que hay concatenacion—, NO EL RIESGO.** La validacion de dominio
> le es invisible. Por eso la unica red que se pone roja si alguien la quita es la suite
> comprobandola EN LA FUENTE. Un trinquete que no se mueve cuando desaparece la proteccion no es
> una proteccion: es un inventario.

**El 500 anterior era la prueba y no hizo falta explotar nada**: `?ids[]=abc` daba
`Unknown column 'abc' in 'WHERE'` — MySQL leia el valor como nombre de columna.

Cerrado: los tres `ids` validados (lista vacia = no se añade el criterio, porque `IN ()` no
compila), `InvalidParameterValueException` a 400, `DocumentsController::searchDropdown` por
`HavingSegment`. Trinquete **6 CONFIRMADO + 3 DECLARADO**, comprobacion 24.

**Precision del CODER que ARQUITECTO tenia mal**: los 6 son SITIOS DE LLAMADA en los DOS metodos
que ARQUITECTO conto como «2». El censo cuenta llamadas. Es la misma cosa dicha en dos unidades.

### EL HUECO DEL PAQUETE, CONFIRMADO EN LA FUENTE

`WhereItem.php:36-42` — `NOT_ALIAS_OPERATORS` contiene `IN`, `NOT IN` y `FIND_IN_SET`: **ni
siquiera se les GENERA marcador**, y `toString()` (238-247) imprime en crudo. No es que la
sustitucion falle: es que no existe.

`CountryMapper::allByRegions()` usa `findInSet` y concatena; inofensiva solo por tener **cero
consumidores** (medido por ARQUITECTO y por el CODER, por separado).

**La cota del censo se amplio y ahora se imprime**: `orderBy($s)` -> `"ORDER BY $s"`,
`groupBy($s)` -> `"GROUP BY $s"` y `join($t, $on)` con `$on` string -> `"JOIN t ON ($on)"`. Los
tres concatenan y **NO se miran**: **16 + 4 + 1 = 21 llamadas sin censar**.

### `region`: LA LISTA BLANCA NO SE PUEDE, Y ADEMAS NO SE DEBE

El CODER paro con tres pruebas: `structure.sql:156` dice `region` **text DEFAULT NULL** —sin
ENUM—, no hay `RegionMapper` (los valores salen de un `GROUP BY region`), y el volcado versionado
tiene **dos paises, los dos con `region = NULL`**.

**Decision de ARQUITECTO, y es de diseño, no de falta de datos**: aunque el PROPIETARIO autorizara
la consulta, **una lista blanca sacada de ESTA instalacion seria falsa para todos los clones**.
`piecesphp` es plantilla: sus regiones las pone cada clon. El patron conservador es la respuesta
correcta para una plantilla; la lista blanca, si acaso, es cosa del clon. **Se queda el patron, y
se documenta su efecto**: un nombre con apostrofo o punto queda descartado.

### TERCER ERROR DE ORDEN DEL CODER, contado por el

`9423aa33` entro con `verify-integrity` en ROJO: añadio la seccion 5 de la suite DESPUES de la
ultima pasada y solo volvio a correr la suite y `gates`. Lo cerro `dfdb47fb`.

> **Regla**: `verify-integrity` se corre DESPUES del ULTIMO cambio del bloque, no antes. Si se
> toca un archivo despues de la puerta, la puerta no ha corrido.

Y el metodo de PHPStan, afinado otra vez: comparar por `(archivo, LINEA, mensaje)` invento «3
muertas y 3 nacidas» —un mismo mensaje repetido en dos lineas del mismo archivo que el diccionario
colapsaba—. Por **multiconjunto de `(archivo, mensaje)` ignorando la linea**: 747 contra 747, cero
muertas, cero nacidas, 24 desplazadas.

### AM — Y EL HALLAZGO NO ERA SQL: ERA UN FILTRO QUE SE PISABA

Buscando concatenaciones, el CODER encontro un **defecto funcional real** en
`UsersController::searchDropdown`. Verificado por ARQUITECTO en `HEAD~1`:

```
310:  $model->having("status != " . STATUS_USER_DELETED); //No mostrar usuarios marcados eliminados
330:  $model->having($having);                            // la busqueda
```

**`ActiveRecord::having()` SUSTITUYE, no acumula** (`ActiveRecord.php:485`,
`$this->havingSegment = $having`). Al escribir cualquier texto en el desplegable, la segunda
llamada borraba la primera y **reaparecian los usuarios marcados como eliminados** — con el
comentario justo encima afirmando lo contrario.

> **Un comentario que afirma un filtro no es el filtro.** Es LEY 24 en la naturaleza: la unica
> comprobacion que existia era una frase.

Arreglado moviendo `status != DELETED` al `where()` —es columna real, `fullname` es alias— y
juntando ambos criterios en un solo `WhereSegment`.

### LA DECISION DEL CODER SOBRE LA CONSTANTE: VALIDADA, y ARQUITECTO se equivoco

La instruccion decia `UsersModel::TYPES_USERS`. **Es un mapa de ETIQUETAS** y tiene
`TYPE_USER_GOOGLE_PLAY` (50) **comentado** en la linea 124. El CODER uso
`TYPES_USER_PRIORITY`, el unico que enumera los siete.

**La evidencia es mas fuerte de lo que el CODER dijo**: `config/roles.php:111-112` registra el rol
50 y saca su nombre de `UsersModel::TYPES_USERS[TYPE_USER_GOOGLE_PLAY] ?? null` — **es decir, hay
un rol registrado cuyo nombre resuelve a `null`**, porque la entrada esta comentada. El 50 es real
en `roles.php`, en `TYPES_USER_DONT_REQUIRE_ORGANIZATION` y en `TYPES_WITH_EXTERNAL_LOGIN`.

Y el razonamiento del CODER sobre la DIRECCION del fallo es el correcto: como `ignoreTypes`
EXCLUYE, dejar el 50 fuera de la lista blanca habria hecho que el desplegable mostrara **MAS**
usuarios. **Fallar hacia el lado que no toca.** Igual que `is_numeric` antes de `intval`, porque
`intval('abc')` da 0 y 0 es `TYPE_USER_ROOT`.

**Pendiente nuevo**: el rol 50 con nombre `null` en `roles.php`.

### DOS DEFECTOS DEL INSTRUMENTO, encontrados provocando

1. **`extract()` rompe el mapa de variables.** `DataTablesHelper::process` hace
   `extract($parameters_expected->getValues())` (lineas 199 y 774): crea variables que ningun
   token asigna, y `groupBy($group_string)` salia LIMPIO. Movio 2 llamadas.
2. **El segmento que no salva.** Al meter el `NOT IN` en un `WhereSegment`, el censo lo dio por
   limpio y **la cifra bajo sola**, con el `NOT IN` imprimiendose en crudo igual.
   **Un instrumento que premia envolver el problema es peor que no tenerlo.** La unidad correcta
   no es el argumento ni el metodo: es la CADENA DE VARIABLES.
3. **Declarar por `archivo::metodo` dejaba entrar gratis** una concatenacion NUEVA en un metodo ya
   declarado. Ahora cada entrada declarada lleva su `count`. **Lo vio provocando, no deduciendo**,
   y la provocacion salio VERDE — que es un hallazgo, no un alivio.

### LA COTA, OTRA VEZ MAS ANCHA — y ARQUITECTO la dio corta

ARQUITECTO dijo 21 llamadas; son **24**: `leftJoin` (2) e `innerJoin` (1) reenvian a `join()` y
comparten su rama de string. Van cuatro veces que el universo de ARQUITECTO sale corto.

Trinquete: **CONFIRMADO 6 -> 5** (los 5 son `DataTablesHelper::process`), DECLARADO 3 -> 4,
REVISAR 92 -> 99, DESCARTADO 95 -> 111, llamadas 196 -> 219.

Sin mirar todavia, y **son IDENTIFICADORES, no valores** —piden lista blanca, no marcador—:
`prepare($sql)` 71, `select($campos)` 194, el `$col`/`$cols` de `get()`, `setTable()` 6. El
`LIMIT` NO entra: sale de dos `?int`, **cerrado por tipo**.

### AN — PARADA CORRECTA, Y LA CLASIFICACION A/B DE ARQUITECTO ERA FALSA COMO HECHO

ARQUITECTO escribio: *«`where_string`, `having_string`, `group_string` los pone la CONTROLADORA.
No son de la peticion: son del programador.»* **Cierto como CONTRATO, FALSO COMO HECHO.**
Verificado por ARQUITECTO linea a linea, no leido del reporte:

| sitio | fuente | sumidero |
| :-- | :-- | :-- |
| `Country::countriesDataTables` | `:287` `getQueryParam('region')`, solo `trim` | `:330` `"UPPER(region) = UPPER('{$region}')"` |
| `SystemApprovalsController::dataTables` | `:436` `getQueryParam('referenceAlias')` | `:482` `"{$table}.referenceAlias = '{$referenceAliasFilter}'"` |
| idem | `:438` `getQueryParam('elapsedDays')` | `:488` `"elapsedDays >= {$elapsedDaysFilter}"` **SIN COMILLAS** |
| `PublicationsController::dataTables` | `:1233` `getQueryParam('visibility')`, **cero validacion** | `:1269` `"visibility = {$visibility}"` **SIN COMILLAS** |

Dos no necesitan ni un apostrofo. Y `elapsedDays` se valida como **cadena no vacia** y se usa como
**numero**. **Van cinco veces que el universo de ARQUITECTO sale corto.**

El `region` de `countriesDataTables` es **de los dos**: la instruccion de AL mandaba arreglar
`Country::search` y ARQUITECTO nunca pregunto si `region` aparecia en otro metodo del mismo archivo.

### `$order` YA ESTABA CERRADO — y no tocarlo vale tanto como un arreglo

`DataTablesHelper:1223` hace `$columns_order[$column_index] ?? null` y la 1225 descarta el nulo:
**el indice del visitante NUNCA llega a la cadena**, llega el nombre que puso la controladora. Y
la 1222 colapsa la direccion con un ternario que solo devuelve `'ASC'` o `'DESC'`. Tres copias del
mapeo (496, 992, 1217), las tres identicas. El censo lo marcaba CONFIRMADO **porque su traza no
cruza de metodo — es la cota funcionando, no un fallo.**

### EL HALLAZGO MAS PROFUNDO: EL UNICO ESCAPADO DEL FRAMEWORK DEPENDE DEL SERVIDOR AJENO

`escapeString()` (`AppHelpers.php`) es **`addslashes(stripslashes($str))`**, y `generateHaving`
lo usa para meter el valor de busqueda en la cadena.

- El juego de caracteres es `utf8mb4` en las dos ramas de `config/database.php` (31 y 38): **la
  via multibyte no aplica**. Medido.
- **La aplicacion NO FIJA `sql_mode` NUNCA** —solo aparece en `Export/Plugins/SqlFormat.php`, que
  es otra cosa—. Con `NO_BACKSLASH_ESCAPES` en el servidor, `addslashes` produce `\"` y **la
  comilla sigue cerrando la cadena**.
- Y `%` y `_` pasan crudos: el visitante controla el patron del `LIKE`.

> **Un framework que se clona a destinos que no controlas y cuyo unico escapado depende de un
> ajuste del destino QUE NO DECLARA NI COMPRUEBA, es la definicion de embarcar una trampa.**

### DECISION DE ARQUITECTO: EL INSTRUMENTO PRIMERO, y no por la razon del CODER

El CODER propuso la novena familia por LEY 11. La razon buena es otra y es mas fuerte:
**«cuatro» es un CONTEO A MANO, no un universo medido.** Los encontro leyendo 19 controladoras.
Si el censo no ve esa forma, no sabemos si son cuatro. Arreglar cuatro y construir el instrumento
despues arriesga declarar cerrado lo que no lo esta. **Primero el instrumento, luego la cifra,
luego el arreglo.**

Y el CODER hizo bien en NO tocar `sql-concat-baseline.json`: escribir un 9 que ningun instrumento
produce es exactamente lo que LEY 5 prohibe.

### Anotado: un archivo modificado que el reporte no menciona

`source-docs/project/docs/environments/content/vps/index.md`, +17 lineas de avisos de seguridad
sobre el login de root por SSH. **No es de AN.** El reporte dijo «solo `20-contrato-de-trabajo.md`»
y eran dos. No cambia nada del bloque, pero un reporte del estado del arbol se da COMPLETO.

### AÑ — EL INSTRUMENTO PRIMERO ERA LO CORRECTO, Y EL NUMERO NO ERA CUATRO: ES TRECE

Verificado por ARQUITECTO: `cifras {confirmado: 13, declarado: 4, revisar: 100, descartado: 133}`,
PHPStan 747, commit `ac48f1a9`, 33 sobre `origin/dev`, arbol con solo el `vps/index.md` que la
instruccion mandaba NO tocar —declarado como PENDIENTE en la guarda, 6 + 1 = 7—.

La novena familia no es una llamada: son **claves de un array literal** que `process()` interpola
(`DataTablesHelper.php:277`). Ninguna de las ocho familias de llamada podia verlas. Aporta **8**:
las 4 que el CODER hallo a mano y **4 mas que si validan** —`State:300`, `UsersController:222`,
`DocumentsController:927`, `Organizations:1242`—.

> **El «cuatro» del CODER era RIESGO. El «trece» del censo es MECANISMO.** Los dos son correctos
> sobre cosas distintas, y por eso el instrumento tenia que ir primero.

### EL CANARIO NEGATIVO QUE PIDIO ARQUITECTO ERA INCOHERENTE — y el CODER hizo bien en no parar

La instruccion decia: *«`Organizations:1206` NO debe salir marcado; si sale, el censo esta
condenando validaciones buenas y PARAS»*. Medido:

```
:1191  $status = $request->getQueryParam('status', null);
:1206  $statusToCritery = in_array($status, array_keys(STATUSES)) ? $status : -1;
:1208  $critery = "{$table}.status = {$statusToCritery}";
```

**El valor viene de la peticion y se concatena.** El `in_array` cierra el RIESGO, no el MECANISMO
— y desde T152 este instrumento mide el mecanismo. **ARQUITECTO le pidio al censo que fuera
incoherente con su proposito declarado.** El CODER no paro, y tenia razon.

Y su argumento de fondo es el que hay que conservar: *un censo que aprendiera a reconocer
`in_array(...) ? :` como saneante estaria ADIVINANDO, y el siguiente que no reconociera pasaria
por limpio.* Por eso existen los DECLARADO y por eso la suite comprueba las validaciones EN LA
FUENTE.

**Van seis veces que ARQUITECTO se equivoca sobre el universo o el instrumento.**

### LA COTA, CONTESTADA LEYENDO `process()`

De las quince claves de `$options`, **tres mas** acaban en el SQL, **las tres como
IDENTIFICADORES**: `select_fields` (333 y 336), `columns_order` y `custom_order` (1254). Y en
`custom_order` **la direccion NO pasa por el filtro ASC/DESC** que si se aplica al `$order` de la
peticion; hoy viene de la controladora, y esta escrito en la cota por si deja de venir de ahi.

### UNA COMPROBACION DE LA SUITE ESTABA ESCRITA POR ARCHIVO

Al crecer el universo, `DocumentsController::dataTablesExplorer` entro en CONFIRMADO y la
comprobacion 4 se puso ROJA — pero su sujeto es `searchDropdown`, que sigue bien. **Miraba el
ARCHIVO; pasa a mirar el METODO.** Provocado: rompiendo `searchDropdown`, suite 19 -> 18 y censo
13 -> 14.

### EL TRINQUETE SUBE 5 -> 13, Y ESO NO ES UNA REGRESION

Sube **porque el instrumento aprendio a ver**. Las ocho llevaban ahi desde antes de la campaña.
El CODER lo subio aunque el bloque parase, porque dejarlo en 5 ponia roja la puerta y con ella las
25 suites. **Registrar una medicion no es arreglar nada; escribir un 9 a mano si habria sido
inventar (LEY 5).**

### `archify` — pregunta del PROPIETARIO, 2026-09

Herramienta Node/npm, MIT, que genera diagramas HTML interactivos de arquitectura a partir de una
descripcion, pensada para agentes. **NO es dependencia del framework**: no entra en `composer.json`
ni viaja al clon. Riesgo tecnico para `piecesphp`: **cero**.

Por la regla del PROPIETARIO **EXTIENDE, no corrige**: fuera de la campaña. Encaja en E6 y en las
guias —`16-frontend-arquitectura.md`, el modulo como patron—.

> **Con una condicion que es la de esta campaña entera**: un diagrama generado de una DESCRIPCION
> es arquitectura AFIRMADA, no medida. Dibuja lo que el agente cree. Solo entra si lo que dibuja
> sale de artefactos MEDIDOS —`route-inventory.json`, los censos, la lista de modulos—, no de
> prosa. **Un diagrama bonito y falso es peor que ninguno: parece autoridad.**

### AO CERRADO — 13 -> 7, sin una sola cifra escrita a mano

Verificado por ARQUITECTO: `cifras {confirmado: 7, declarado: 10, revisar: 100, descartado: 133}`,
PHPStan 747, `c3f6cc82`, 34 sobre `origin/dev`, arbol con solo el `vps/index.md` declarado como
PENDIENTE. `regionNameOrNull()` existe (`Country.php:570`) y la usan los DOS sitios (221 y 292).
`PublicationMapper::VISIBILITIES` tiene las cuatro y **ninguna comentada** — el CODER lo comprobo
por lo que paso con `TYPES_USERS` en T153: **la leccion viajo sola**.

**Y corrigio su propia cifra de AN sin que nadie se lo pidiera**: dijo «3 consumidores de
`escapeString`», eran los de UN archivo. **Son 23 llamadas en 13 archivos.** Verificado exacto por
ARQUITECTO.

### TRES DECISIONES DEL CODER QUE SON DISEÑO, NO EJECUCION

1. **EL RECHAZO FALLA CERRADO.** Lo que no case el patron de `region` cae en `''` —que no
   encuentra nada— y **no en `null`, que habria quitado el filtro y ENSANCHADO el resultado**.
   Es «fallar hacia el lado que no toca», aplicado sin que se le dijera. Y conservo el `''`
   legitimo de antes: no cambio comportamiento bueno.
2. **`elapsedDays` son DOS defectos y los nombro aparte**: el de SQL (sin comillas) y **el de
   TIPO** —se validaba con `is_string(...) && mb_strlen(trim(...))` y se usaba como numero—.
   *El segundo habria sobrevivido a cualquier arreglo que solo pensara en el SQL.*
3. **QUEDAN SIETE Y NO CINCO**, y la razon es sutil y correcta: `SystemApprovalsController::dataTables`
   tiene DOS hallazgos en el mismo metodo y `referenceAlias` sigue abierto; **como se declara por
   `archivo::metodo`, el `count` no puede decir a cual de los dos indulta** — lo decidiria el orden
   de aparicion. Regla nueva del registro de declaradas, y la suite la vigila.

Y no hay columna HTTP **por respetar la prohibicion**: las tres rutas piden sesion y mandar una
comilla contra la aplicacion viva es una prueba de explotacion. La evidencia es el fragmento
COMPUESTO. **Cero retrabajos** en este bloque.

### `referenceAlias` — ARQUITECTO NO AUTORIZA EL CAMBIO DE CONTRATO, Y NO POR ALCANCE

La parada esta bien fundada: el registro de handlers se carga con `require_once` dentro de un
constructor privado (`SystemApprovalManager:42`) y nunca se expone; `$BASE_TEXT` es `protected`; y
`UsersApprovalHandler:56` escribe **'Usuario independiente'** dentro de un metodo, fuera de toda
lista. Una lista blanca sacada solo de `$BASE_TEXT` **dejaria fuera ese alias y el filtro
devolveria vacio en silencio**.

**La razon para no autorizar ahora es el defecto adyacente que el CODER encontro**:
`SystemApprovalsMapper::getReferencesAliases()` (311-316) devuelve los alias **pasados por `__()`**
mientras la columna guarda el texto **sin traducir**. En español coinciden; en cualquier otro
idioma **el filtro no casa nada**.

> **El filtro ya esta roto por semantica, no solo por inyeccion.** Cerrar la inyeccion sin cerrar
> eso es pulir una funcion averiada. Los dos van juntos, en un bloque de SystemApprovals.

### `generateHaving` — la salida es la ADITIVA, no tocar el paquete

`havingReplacePrepareValues` solo se rellena desde un `HavingSegment` (`ActiveRecord.php:491` y
`556`): en la via de cadena un marcador **nunca se ataria**. Y el HAVING final mezcla el
`having_string` DEL PROGRAMADOR con lo que devuelve `generateHaving` (linea 268), y un
`HavingSegment` no admite fragmentos en crudo ni grupos parentizados.

**Decision de ARQUITECTO**: `process()` acepta claves NUEVAS `where_segment` / `having_segment`, y
los modulos migran uno a uno. Es **aditivo, no rompe a nadie**, y es el camino que acaba haciendo
innecesarias las claves de cadena. Va con el bloque de `DataTablesHelper`, que es el mismo archivo.

### AP — CONFIRMADO 13 -> 7 -> 2, y la mejor guarda del bloque NO ESTABA PEDIDA

Verificado: `cifras {confirmado: 2, declarado: 14, revisar: 104, descartado: 133}`, PHPStan 747,
`580c2c2e`, 35 sobre `origin/dev`, sin etiqueta, arbol con solo el `vps/index.md` declarado.
Las tres guardas existen con mensaje claro (296-300 y 326-327) y `Country` migrada por
`where_segment` (337).

**LA TERCERA GUARDA LA PUSO EL CODER SIN QUE SE LE PIDIERA, y es la que salva el bloque**:
`having_segment` + BUSQUEDA ACTIVA lanza. El segmento del programador **sustituiria** al HAVING
que genera la busqueda de DataTables, y `HavingSegment` **no sabe agrupar** —`(a OR b) AND c` no
es expresable—. Sin ella, **la primera controladora que migrara habria perdido su filtro de
busqueda EN SILENCIO**.

Y la migracion de `Country` sale **mejor que equivalente**: con marcador, `"Am'erica"` vuelve a
buscarse, y el patron conservador de T152 ya no hace falta ahi. `regionNameOrNull()` sigue vivo
porque `countries()` compara con `IN (...)`, que no admite marcador.

**El ciclo de las declaradas funciona solo**: al migrar `Country`, el trinquete cazo que su
entrada «figura como declarada y ya no casa con ningun hallazgo». *Se declara mientras concatena,
DESAPARECE al migrar.*

### LA PARADA DEL PASO 3: ARQUITECTO ELIGE ADITIVIDAD

El CODER no rompio el empate y hizo bien: cambiar el retorno por defecto de `generateHaving`
alteraria el SQL de **las 18 que no han migrado**, contra la garantia de aditividad que la propia
instruccion exigia.

**Gana la aditividad**, y la razon que lo zanja es la tercera:

1. La garantia de aditividad es lo que protege a 18 controladoras que ARQUITECTO **no ha leido**.
2. `escapeString` se sostiene HOY —`utf8mb4` medido en las dos ramas—; el riesgo es un `sql_mode`
   del destino, y eso **no se cierra tocando `generateHaving`**: se cierra migrando, que es el
   camino ya elegido.
3. **Cambiar el defecto seria afirmar que ninguna de las 18 depende de la forma actual. Eso es
   LEY 19** — afirmar sobre el consumidor desde el productor—, y esta campaña ha castigado esa
   afirmacion **seis veces**, todas contra ARQUITECTO.

> `escapeString` muere POR MIGRACION, no por cambio de defecto. Cada controladora que pasa a
> `where_segment` / `having_segment` se lo lleva consigo.

### GOTCHA DE INSTRUMENTAL, del CODER y aplicable a los dos

`open(p, encoding='utf-8')` en Python usa **saltos de linea universales**: un `\r\n` llega como
`\n`, detectar el final SIEMPRE da falso, y al reescribir sale LF. Por eso `normaliza-eol`
encontraba algo despues de cada edicion suya. **ARQUITECTO no cae en esto porque lee con
`open(...,'rb')` y decodifica a mano** — pero queda escrito para los dos.

### AQ — Y EL MURO TIENE NOMBRE: `HavingSegment` NO AGRUPA Y `WhereSegment` SI

Verificado: `cifras {confirmado: 2, declarado: 11, revisar: 104, descartado: 133}`, PHPStan 747,
`66e9b645`, 36 sobre `origin/dev`.

**ARQUITECTO fue a medir el muro antes de autorizar un `HavingGroup` propio, y el muro no es el
que pareciamos tener:**

| | agrupa | estructura |
| :-- | :--: | :-- |
| `WhereSegment` (143) | **SI** | `WhereItemGroup[]`, con `addGroup()`, y hasta envuelve un critery suelto en un grupo |
| `HavingSegment` (114) | **NO** | `HavingItem[]` PLANO, solo `addCritery(HavingItem)` |
| `WhereItemGroup` (149) | — | existe |
| **`HavingItem`** | — | **`class HavingItem extends WhereItem {}` — QUINCE LINEAS, CUERPO VACIO** |

**`(a OR b) AND c` SI es expresable en WHERE. En HAVING no, y la unica diferencia es que
`HavingSegment` es de UN nivel y `WhereSegment` de DOS.**

> **ARQUITECTO NO AUTORIZA un `HavingGroup` dentro del framework.** Seria reimplementar
> `WhereItemGroup` fuera del paquete que ya lo tiene: **la trampa de los dos caminos**, la misma
> que se diagnostico con `EntityMapper` contra `ORM`. El arreglo es dar a `HavingSegment` la
> estructura de dos niveles que `WhereSegment` ya tiene, **con la implementacion de referencia al
> lado**.

**Y eso RECOLOCA LA COLA**: el bloque del paquete `database` deja de ser una tarea de cierre y pasa
a ser **lo que desbloquea tres cosas a la vez** — las dos controladoras bloqueadas
(`Organizations` y `Publications`), `generateHaving`, y con el la dependencia de `escapeString` en
el helper.

### LO QUE EL CODER MIDIO ANTES DE FIARSE

- **El cubo A se lo dio el censo, no el ojo**: son exactamente los DECLARADO de la novena familia.
- **Colision de alias**: `dataTablesRequestUsers` compara `status` y `type` DOS VECES cada una; si
  dos alias colisionaran se perderia un valor **sin ruido**. Comprobado: 5 criterios, 5 alias
  distintos, y la suite lo fija.
- **La validacion se queda en `State` y se retiro en `Country`, y NO es incoherencia**: en `State`
  el `isInteger` es lo que convierte lo raro en `-1`, que no casa nada; sin el, MySQL coaccionaria
  la cadena y daria **el mismo resultado POR CASUALIDAD, no por diseño**. En `Country` el patron
  descartaba **nombres legitimos**. No es la misma situacion.
- **`escapeString` no se movio y NO SE MOVERA migrando**: 21 de las 23 llamadas viven en los
  `search()` de los mappers y en JSON, que esto no toca; las 2 del helper estan en
  `generateHaving`, cuyo defecto **no cambia por decision de ARQUITECTO**. Lo dijo para impedir la
  inferencia equivocada, que es LEY 19 aplicada contra ARQUITECTO.

### LAS DOS BLOQUEADAS, Y POR QUE NO FORZARLAS ERA LO CORRECTO

`OrganizationsController::dataTables` y `PublicationsController::dataTables` meten su valor de
peticion en `having_string` (`:1208` y `:1275`) **y tienen columnas buscables reales**. En cuanto
alguien escriba en el buscador, `generateHaving` produce contenido y **la guarda de AP las para**.
Migrarlas habria metido **un fallo latente que solo aparece cuando un usuario escribe**.

### DOS RESIDUOS EN LA ORBITA DEL HELPER — DECIDIDO: MUEREN

1. **`FIELD_SAMPLE_FILTER`** en `DocumentsController::dataTablesExplorer`: **no es una columna**
   —cero ocurrencias en `DocumentsMapper`—, es plantilla heredada del modulo modelo, y la ruta
   `documents-datatables-explorer` existe y esta autenticada. Quien pase `?FIELD_SAMPLE_FILTER=1`
   se lleva un `Unknown column`. **Se borra**: cambiar ese 500 por un 200 es un arreglo, no una
   regresion. El CODER hizo bien en migrarlo tal cual y no decidirlo el.
2. **`datatables_proccessing()`** (`config/functions.php:72`): **cero consumidores**. Muere con el.

**B y C no migran**, confirmado: en B el valor sale de la sesion del propio usuario; en C tres
tienen el array de criterios **vacio siempre**.

### AR — TRES PIEZAS MUERTAS QUE SE TAPABAN ENTRE SI

Verificado: `cifras {confirmado: 2, declarado: 11, revisar: 104, descartado: 133}`, PHPStan 747,
`afbc0692`, 37 sobre `origin/dev`.

**`FIELD_SAMPLE_FILTER` no era una pieza, eran tres**, y encajadas de forma que se cubrian:

| pieza | estado |
| :-- | :-- |
| el criterio PHP | columna que NO existe en `DocumentsMapper` |
| el JS que lo dispara | manda **`FIELD_SAMPLE_FILTER_LOAD`**, CON SUFIJO |
| el desplegable | **no existe**: `explorer.php` no tiene ni un `dropdown` |

**Los dos nombres nunca casaron.** Por eso nadie se topo jamas con el `Unknown column` desde la
interfaz: el parametro que el JS envia no lo lee nadie, y el que el PHP lee no lo envia nadie.

**ARQUITECTO comprobo el «cero ocurrencias» y encontro UNA**: esta en
`src/app/logs/olds/error.log...json`, que es **un registro de lo que paso**, no codigo — y
reescribirlo seria justo el error que el CODER cometio y conto en AK. **Su cero era correcto sobre
el codigo; el grep de ARQUITECTO era mas ancho que su afirmacion.** Por una vez, al reves.

### EL HTTP NO DIO LO ESPERADO Y ESO FUE EL HALLAZGO

Esperabamos 500 -> 200. Salio 500 -> 500. En vez de maquillarlo, el CODER **aislo el entorno**
pidiendo la ruta hermana `documents-admin-datatables` con los mismos parametros: **200**. Base,
tabla y entorno bien.

> **`dataTablesExplorer` tiene un SEGUNDO defecto, propio y anterior, que este bloque no toca.**
> Para nombrarlo hace falta el `limitGeneratedSQL` que la excepcion ya lleva dentro y que el
> manejador no imprime.

### DECISION DE ARQUITECTO: SE REGISTRA, NO SE IMPRIME

El CODER propuso imprimir el `limitGeneratedSQL` en el manejador, «solo en local». **No.**

> Imprimir SQL en una respuesta de error crea una superficie de fuga cuya unica cerradura es una
> comprobacion de «local» **que cada clon hereda**. Diagnosticar un defecto viejo creando una fuga
> nueva es un mal cambio. **Va al REGISTRO** —`log_exception`, que ya existe, y los logs estan en
> `.gitignore`—: mismo diagnostico, superficie cero.

### EL GEMELO, Y LA DISCIPLINA DE NO DECIDIRLO

La red ancha destapo `datatables_proccessing_with_options()` (`config/functions.php:42`), **tambien
con cero consumidores**. El CODER **no lo borro**: no estaba en la instruccion.
*Lo que la red destapa se reporta, no se decide.* **Decidido ahora: muere, con la misma red ancha
delante.**

### EL PAQUETE `database`: MEDIDO, AUTORIZADO Y ADITIVO

`WhereItemGroup::addCritery(WhereItem $critery)` esta tipado a `WhereItem`, y
`HavingItem extends WhereItem {}` tiene el cuerpo vacio: **un `HavingItem` ES un `WhereItem` y
pasa el tipo sin cambiar una linea.** Nada lo impide.

Lo que le falta a `HavingSegment` (114) frente a `WhereSegment` (143): `$groups`,
`addGroup(WhereItemGroup)`, `addCriteria(array)`, y las versiones por grupos de `addCritery`,
`countCriteria`, `getReplacementValues` y `toString`.

**Y NO es copiar `WhereSegment` encima**, aviso del CODER que hay que respetar: `HavingSegment`
emite hoy UNA envoltura plana con el operador dentro; adoptar su `addCritery` convertiria un `OR`
existente en `(a OR) (b OR) (c)`, que es basura. **La forma segura es ADITIVA**: `$groups` y
`addGroup()` nuevos, y `toString()` emite grupos **solo si se añadio alguno**.

Coste medido: `WhereSegment` 38 usos en `src/app` y 5 en el paquete; `HavingSegment` 23 en 7
archivos y 1 en el paquete; **`WhereItemGroup` CERO en `src/app`** — nada existente puede romperse
por añadirlo.

**Estado del paquete**: limpio, `v4.0.0` en HEAD, 13 suites, PHPStan 21. Aditivo y sin romper nada
= **MINOR**, con su CHANGELOG y su etiqueta. **La version y la etiqueta las decide el PROPIETARIO;
NADA SE EMPUJA, tampoco aqui.**

Desbloquea: las dos controladoras, `generateHaving` con segmento aun habiendo `having_string`, la
retirada de la via de cadena con sus 5 declarados, y **2 de las 23 `escapeString`** —las del
helper—. Las otras 21 viven en los `search()` de los mappers y en JSON: **no caen por esto**.

### AS — CUATRO MIGRACIONES ROTAS, Y LA CULPA ES DE LA INSTRUCCION

**El CODER encontro y confeso que las migraciones de AP y AQ estan ROTAS**: cuatro rutas
devuelven 500 con el filtro por segmento y 200 sin el. La discriminante es el segmento, no la ruta
ni el entorno. El error, del registro:

```
SQLSTATE[42S22]: Unknown column 'WH6A971DCA36125_UPPERREGION' in 'WHERE'
```

**El marcador llega a MySQL SIN SUS DOS PUNTOS.**

**Y corrigio a ARQUITECTO**: T158 decia que el 500 del explorador era «un segundo defecto, propio y
ANTERIOR». **Anterior no: era suyo, de AQ, y no era una ruta sino cuatro.**

**La causa de fondo es de ARQUITECTO y esta escrita como LEY 29**: seis instrucciones seguidas
dijeron *«el fragmento compuesto es la evidencia»*. Componer prueba la composicion; solo ejecutar
prueba la ejecucion. Y **una peticion normal con un valor normal no es una prueba de
explotacion**.

### EL ALCANCE ES MAYOR QUE CUATRO — medido por ARQUITECTO

En `src/app` hay **SIETE sitios de produccion** que usan el envoltorio `{%VALUE%}`:
`Point:471`, `State:466`, `Country:298`, `Country:517`, `City:568`, `Documents:982`,
`UsersController:353`. **Cinco de ellos son los ARREGLOS DE SEGURIDAD de AJ, AL, AM y AO**, y
ninguno se ejecuto nunca: su evidencia tambien fue el fragmento.

Y una medicion gratuita que apunta a la causa: **`ReportsManageQueries` tiene NUEVE
construcciones de segmento anteriores a la campaña**. Si el camino de marcadores estuviera roto de
raiz, esos informes llevarian rotos desde siempre. **Eso separa la rama llana de la rama con
envoltorio**, y hace del `rightWrapFunction` el sospechoso — pero **NO SE AFIRMA: se ejecuta.**

Dato leido, para el que lo persiga: el alias se construye en
`WhereItem::setWithAlias()` (`:168`) con `preg_replace("/[.|\(|\)|-|,]/", '', ':WH'.uniqid()."_{$base}")`.
**Ese conjunto NO incluye los dos puntos**, asi que el alias nace con ellos. La perdida ocurre
despues, y hay que encontrarla ejecutando.

### `database` v4.1.0: HECHO, VERDE, Y SIN LLEGAR AL FRAMEWORK

`HavingSegment` agrupa; no regresion comprobada por `diff` VACIO contra HEAD sobre cuatro formas
existentes; `WhereItemGroup` intacto; 13 suites verdes; PHPStan 21 = base. Commit `e5602de`,
**etiqueta v4.1.0 preparada y NO creada**, nada empujado.

**Pero `src/vendor/piecesphp/database` NO es un symlink**: es instalacion de Composer fijada en
`e9f5ac37` por `composer.lock`. `addGroup` esta 1 vez en el repo hermano y **0 en el instalado**.
La parte 2 no se puede ejecutar sin etiqueta y publicacion — **decision del PROPIETARIO**.

### `limitGeneratedSQL` no necesitaba ningun cambio

`GenericHandler::logging()` ya vuelca `extraData` en `error.log.json` (`:167`), y los logs estan en
`.gitignore`. **La decision de ARQUITECTO —registrar, no imprimir— resulto ser lo que ya existia.**
De ahi salio el SQL exacto del fallo.

### AT — LA CAUSA ERA UNA LINEA DEL FRAMEWORK, Y LOS CINCO ARREGLOS DE SEGURIDAD ESTABAN SANOS

Verificado por ARQUITECTO: las TRES llamadas del helper ahora son iguales —450, 666 y 686 con
`getCompiledSQL(true)`—, `feca9ef4`, 39 commits, arbol con solo el `vps/index.md` declarado.

```
DataTablesHelper.php:666   $filterCount->getCompiledSQL();   ← SIN `true`, la forma de DEPURACION
ActiveRecord:856           $baseAlias = str_replace(':', "", $alias);
                           preg_replace("/{$alias}/", "($baseAlias=$value)", $query);
```

Esa forma **quita los dos puntos a proposito** —es para leerla— y ese texto se envolvia en
`SELECT COUNT(*) FROM (…)` **y se ejecutaba**. Las hermanas 450 y 686 ya pasaban `true`. **La 666
era la unica sin el argumento.**

**Y es PREEXISTENTE**: con `where_string` los valores de reemplazo estan vacios, el bucle no hace
nada y el SQL sale intacto. Llevaba años dormido. **Las migraciones no lo crearon: lo destaparon.**

### DOS HIPOTESIS DE ARQUITECTO, LAS DOS FALSAS

1. **«La discriminante es el envoltorio `{%VALUE%}`»** — NO. Es **segmento + `process()`**. Los
   seis `search()` van por segmento SIN `process()` y dan 200.
2. **«La causa vive en el paquete y no llegaria al framework sin publicar»** — NO. Vive en
   `DataTablesHelper`, codigo del framework, y son **cuatro caracteres**.

> **LOS CINCO ARREGLOS DE SEGURIDAD DE AJ, AL, AM Y AO ESTAN SANOS.** Era la peor posibilidad y
> no se dio.

### LA DESVIACION DEL PASO 3: VALIDADA, con una precision

El paso 3 mandaba restaurar cuatro migraciones. El CODER **no lo hizo**, porque la premisa de
ARQUITECTO era falsa y arreglar la linea dejaba los diez en 200 — y el propio paso decia *«lo que
de 200 se queda como esta»*. **Revertir para esquivar un defecto que se puede arreglar, y dejarlo
esperando al siguiente que pase un segmento, era estrictamente peor.**

**ARQUITECTO la valida.** Lo declaro EN LA PRIMERA LINEA del reporte, nombro la premisa falsa en
vez de rodearla, y ofrecio la reversion.

> **Precision que igual importa**: la regla dice *para y reporta*, no *arregla y reporta*. Aqui
> el arreglo eran cuatro caracteres verificados por ejecucion y salio bien. **La proxima vez el
> arreglo puede no ser de una linea, y entonces la diferencia entre las dos formas es todo.**
> Se valida el caso, no se cambia la regla.

### EL CONTRASTE CONTAMINADO, declarado por el CODER

`users-datatables` y `reports-manage` dieron 500, pero por **falta de sesion** —«read property id
on null»—, no por el segmento. **No miden nada**, y lo dijo en vez de sumarlos a la tabla. El
contraste valido son los seis `search()`.

### LA PREGUNTA QUE ABRE EL SIGUIENTE BLOQUE

*«Hay mas sitios en el arbol que EJECUTEN una forma de depuracion?»* — Es la generalizacion
correcta del defecto y es LEY 11: la regla que fallo se convierte en mecanismo. En el arbol hay
tres llamadas a `getCompiledSQL` en el helper, dos en la suite, y **nadie ha mirado si hay otras
funciones «para leer» que acaben ejecutandose**.

### ERROR DE ARQUITECTO EN AS: rompio una convencion del PROPIETARIO en su propio archivo

El PROPIETARIO pregunto: *«Por que `WhereItemGroup` y no su propio `HavingItemGroup`?»*
**Tiene razon y la instruccion de AS estaba mal.**

El paquete YA tiene la convencion, escrita por el mismo: `class HavingItem extends WhereItem {}`
—subclase marcadora, cuerpo vacio—. La simetria completa es
`WhereSegment`/`HavingSegment`, `WhereItem`/`HavingItem`, y faltaba `WhereItemGroup`/`HavingItemGroup`.
**ARQUITECTO leyo `HavingItem` entero en AR y aun asi mando saltarse el par.**

Tres razones, y la tercera es de calendario:

1. La firma dice la verdad: hoy `HavingSegment` expone publicamente un `WhereItemGroup`.
2. Es el punto de extension para cuando HAVING diverja —agregados, alias del SELECT—, y diverge
   por naturaleza.
3. **`e5602de` esta sin etiqueta y sin empujar**: estrechar la firma ahora es GRATIS; despues de
   publicar `v4.1.0` seria incompatible, una MAJOR. **Que la etiqueta este preparada y NO creada
   es lo que hace que esto se pueda arreglar sin coste.**

Medido: `WhereItemGroup` no es `final`, y tiene **cero usos** en `piecesphp/src/app`.

### AU — LA RESPUESTA ERA «UNA, Y YA ESTABA CERRADA»

Verificado: `f1ea14c9`, 40 commits, `bin/censo-formas-de-lectura` creado, trinquete sin cambios
(2 · 11), PHPStan 747.

Criterio declarado antes de contar, y **la tercera regla es la que define la frontera**: una forma
es «para leer» si SUSTITUYE MARCADORES POR VALORES. Por eso `toString()` de los segmentos **no
entra**: emite marcadores, no valores. *Producir texto para USARLO no es una forma de lectura.*

**El canario de cuatro caras cambio el resultado.** El primero probaba la DEFINICION; la
instruccion pedia la LLAMADA. Sin la cuarta cara —`getCompiledSQL(true)` NO debe salir—,
`DataTablesHelper` seguia apareciendo pese a estar ya corregido en AT.
**Un censo que no mira el argumento no distingue el defecto de su arreglo.**

Universo: 736 archivos, 5 repositorios, 22 formas. **EJECUTADA 1 · REVISAR 0 · SOLO LEIDA 17.**
La unica ejecutada es `humanReadable()` y **no es defecto**: su destino es su proposito. El CODER
la dejo marcada A PROPOSITO y explicada en la cota — *un falso positivo que se explica cuesta menos
que un criterio que deje pasar el siguiente.*

**Y `getCompiledSQL()` sale ahora en SOLO LEIDA**: confirmacion independiente de que el arreglo de
AT esta puesto.

### DOS LECCIONES DE METODO DE AU

1. **Una puerta sin sujeto da VERDE.** La primera pasada de `verify-integrity` salio verde porque
   el guion nuevo estaba SIN SEGUIR y la puerta no lo veia. **Hubo que prepararlo ANTES para que
   la puerta tuviera a quien mirar.** Es LEY 18 por otra puerta: sin sujeto, no corrio.
2. **Las cotas escritas se pudren.** El CODER cito «450, 665 y 686» en un docblock y **su propio
   docblock las desplazo a 461/677/697 dentro del mismo bloque**. Lo cambio por «las tres llamadas
   de este archivo». Referencia por descripcion, no por numero.

### ARQUITECTO SE CORRIGE: EL CENSO SI SE CABLEA

La instruccion de AU decia *«una puerta que nunca puede fallar es ruido»*. **El criterio estaba mal
enunciado.** La prueba no es «informa cero hoy» sino **«puede un cambio futuro hacerla saltar»** —
y esta salta el dia que alguien escriba `getCompiledSQL()` sin `true` y lo ejecute, que es
exactamente el defecto que costo cuatro rutas rotas.

Con ese criterio es un TRINQUETE, igual que el de SQL concatenado, que tambien vive en un numero.
**Se cablea.**

### AV — LA SIMETRIA, Y EL ARGUMENTO DE CALENDARIO CONFIRMADO

Verificado en el paquete: `HavingItemGroup.php` existe, `HavingSegment` lo usa en las cuatro
posiciones (34, 63, 77, 144), `WhereItemGroup` solo aparece en el `extends`, `aa64cf5` encima de
`e5602de` sin reescribir, **sin etiqueta**, 13 suites verdes, PHPStan 21.

La provocacion por tipo salio como debia: `addGroup(WhereItemGroup pelado)` -> **TypeError**;
`HavingItemGroup instanceof WhereItemGroup` -> **true**. **La firma se estrecho de verdad**, y
quedo fija en un TEST que cae de 7 a 6 si alguien devuelve la firma al padre.

Y la entrada del CHANGELOG **se corrigio en vez de añadir otra**: no es una version nueva, es la
misma antes de salir. **Estrecharlo ahora salio gratis, que era el argumento entero.**

### EL PAQUETE ESTA EMPUJADO PERO **SIN ETIQUETA**, Y POR ESO NO LLEGA

Medido el 2026-09-01: `database` en `master`, arbol limpio, **0 commits sin empujar** —el
PROPIETARIO empujo—, pero:

```
etiqueta en HEAD:   (ninguna)
ultimas etiquetas:  v3.8.1  v3.9.0  v4.0.0
src/composer.json:  "piecesphp/database": "^4.0"
src/composer.lock:  v4.0.0  ref=e9f5ac3728
instalado:          addGroup 0 · HavingItemGroup NO
```

**Composer resuelve por ETIQUETA.** Con `^4.0` y sin `v4.1.0`, la mas alta sigue siendo `v4.0.0`:
`composer update` no traeria nada. **Empujar los commits no desbloquea; lo que desbloquea es la
etiqueta.**

### SYSTEMAPPROVALS — MEDIDO ENTERO, y la lista blanca SI se puede construir

Al leerlo, el registro resulto **mas accesible** de lo que decia AO:

- `Util/configurations.php` devuelve un **array llano** de tres clases:
  `OrganizationApprovalHandler`, `UsersApprovalHandler`, `PublicationsApprovalHandler`.
  Lo carga `SystemApprovalManager:42` con `require_once` —y un segundo `require_once` devuelve
  `true`, que es la razon real de que no se pueda releer—.
- Cada handler declara `protected static $BASE_TEXT`: `'Perfil'`, `'Organización'`,
  `'Publicación'`, y `BaseApprovalHandler` por defecto `'Elemento'`.
- **`UsersApprovalHandler::getContentTypeSpecificMapper()` puede devolver
  `'Usuario independiente'`** cuando el usuario es GENERAL y su organizacion es la global. **Ese
  texto no esta en ninguna lista**, y por eso una lista blanca sacada solo de `$BASE_TEXT` lo
  dejaria fuera y el filtro devolveria vacio en silencio.

> Por eso `getContentTypes()` en el contrato de los handlers es la respuesta correcta y no un
> capricho: **solo el handler sabe todos los textos que puede producir.**

**Y el segundo defecto, que es el que obliga a hacerlo junto**: `getReferencesAliases()`
(`SystemApprovalsMapper:310-316`) hace
`array_map(fn($e) => __(self::LANG_GROUP, $e->referenceAlias), ...)` **mientras la columna guarda
el texto SIN traducir**. El desplegable manda la etiqueta traducida y el `WHERE` compara contra el
crudo: **en cualquier idioma que no sea español, el filtro no casa nada.** Arreglar la inyeccion
sin arreglar esto seria pulir una funcion averiada.

`elapsedDays` ya quedo cerrado en AO (`:441`, `Validator::isInteger`). Lo que sigue concatenando
es `:484`, `"{$table}.referenceAlias = '{$referenceAliasFilter}'"`.

### LA ETIQUETA `v4.1.0` YA EXISTE — medido el 2026-09-01, y corrige la seccion anterior

`git cat-file -t v4.1.0` -> `tag`. Es ANOTADA y apunta a `aa64cf5`, que es HEAD de `master` en
`database`, con **0 commits sin empujar**. La seccion de arriba —«EL PAQUETE ESTA EMPUJADO PERO
SIN ETIQUETA»— **queda cerrada**: el PROPIETARIO la creo.

**Lo que ARQUITECTO NO puede medir**: si la etiqueta esta en el remoto. `git ls-remote --tags
origin` devuelve `HTTP code 403 from proxy after CONNECT` desde esta maquina. **No se afirma que
este empujada.** Lo dice una linea que corre en la maquina del CODER, y por eso va en el bloque.

**Y el arbol ya lo estaba avisando**: `collectPackageVersions()` (comprobacion 17) compara la
version del `lock` contra la ultima etiqueta LOCAL del paquete clonado al lado, y emite
`piecesphp/database — INSTALADA v4.0.0, ETIQUETADA v4.1.0. Puede ser deliberado; queda dicho.`
Es AVISO, no fallo, y por eso el bloque AW paso verde con la etiqueta ya creada. **La comprobacion
hizo su trabajo: dijo la verdad sin bloquear.**

`src/vendor/` **no esta versionado** —`git ls-files src/vendor` da 0—, asi que el `composer update`
mueve **un solo archivo del arbol**: `src/composer.lock`.

### EL OPERADOR QUE UNE UN GRUPO CON LO SIGUIENTE LO PONE SU ULTIMO CRITERIO

Medido en `WhereItemGroup::toString()`, que es lo que `HavingItemGroup` hereda entero:

```php
if ($countCriteria === 1 || $isLast) {
    $afterOperator = $critery->getAfterOperator();   //<- el del ULTIMO, y solo el del ultimo
    $criteria[] = $critery->toString(false);
}
...
$str = "({$criteria}) {$afterOperator}";
```

**Un grupo no declara como se une a lo que viene detras: lo HEREDA de su ultimo miembro.** Y el
grupo de la busqueda de DataTables se compone de criterios unidos por `OR`. Si ese grupo no fuera
el ultimo, o si el ultimo criterio conservara su `OR`, el SQL saldria

    HAVING (organizationID = 12) OR (UPPER(title) LIKE ... OR UPPER(autor) LIKE ...)

es decir: **cualquier busqueda de texto ANULARIA la restriccion de organizacion**. No es un
detalle de estilo. Es un ensanchamiento de visibilidad producido por un operador heredado.

`HavingSegment::toString()` lo tapa hoy por ORDEN —llama `withAfterOperator(false)` sobre la
ultima parte—, y eso es una coincidencia de colocacion, no un contrato. **Por eso el ultimo
criterio del grupo de busqueda fija su `afterOperator` a `AND` explicitamente**, que es correcto
en las dos posiciones: si va ultimo, se suprime; si no, une con `AND`.

La afirmacion **no se deduce del codigo: se comprueba ejecutando** y mirando que entre los dos
parentesis diga `) AND (` y no `) OR (`. LEY 29.

### `LIKE` SI LLEVA MARCADOR — y eso es lo que hace posible el bloque

`WhereItem::NOT_ALIAS_OPERATORS` son cinco: `IS NULL`, `IS NOT NULL`, `IN`, `NOT IN` y
`FIND_IN_SET`. **`LIKE` no esta.** Luego `new HavingItem('UPPER(campo)', LIKE_OPERATOR, '%v%')`
genera alias y viaja por `getReplacementValues()`.

Y el miembro izquierdo es un `string` libre: `UPPER(tabla.campo)` es valido. El alias se deriva de
el quitando puntos, parentesis y comas (`setWithAlias()`), asi que no colisiona.

**Consecuencia**: la busqueda de DataTables —que hoy concatena el valor de la peticion pasandolo
por `escapeString()`, que es `addslashes(stripslashes())` y depende de un `sql_mode` que el
framework nunca fija— **puede dejar de concatenar**. Es la ultima concatenacion grande del helper.

### LO QUE `generateHaving()` NO PUEDE DEJAR DE SER

`generateHaving()` tiene DOS consumidores y solo uno puede recibir un segmento:

- `process()` (`:322`) construye con el ORM -> **si puede** tomar un `HavingSegment`.
- `processFromQuery()` (`:740`) arma SQL crudo: `"SELECT ... {$having} {$order_by}"` (`:994`).
  **Necesita un string y lo seguira necesitando.**
  *(Esta linea decia `dataTablesExplorer()`. ERROR DE ARQUITECTO, corregido el 2026-09-02: ese es
  un metodo de CONTROLADORA que usa `process()`. Ver la seccion «`processFromQuery()` TIENE UN
  SOLO CONSUMIDOR» mas abajo. El CODER heredo el nombre equivocado en su reporte de AX.)*

Por eso el bloque **no cambia `generateHaving()`**: extrae de el la decision de QUE COLUMNAS son
buscables a un solo sitio, y añade `generateHavingGroup()` al lado. Dos formas, **una sola
verdad sobre el universo**. Si cada una decidiera sus columnas por su cuenta, divergirian, y una
divergencia entre el filtro que se aplica y el que se cree aplicar no la ve nadie hasta que
alguien busca. LEY 11.

### `processFromQuery()` TIENE UN SOLO CONSUMIDOR, y ARQUITECTO nombro el sujeto equivocado

ARQUITECTO escribio en la pregunta P3 que la forma de cadena sobrevive por `dataTablesExplorer`.
**Falso.** `dataTablesExplorer` es un METODO DE CONTROLADORA —`DocumentsController:889`, ruta
`documents-datatables-explorer`— y **usa `process()`**, ya migrado a `where_segment`.

El metodo del helper que arma SQL crudo es **`processFromQuery()`** (`:740`), y su universo de
consumidores es **UNO**: `MySpace\Controllers\AllProfilesController:162`. Medido con
`grep -rn "processFromQuery" src/app`.

Es el mismo defecto de metodo de siempre: **nombrar un sujeto por parecido en vez de comprobarlo**
(§5). Y cambia la decision entera: no es «rediseñar el explorador», es **un metodo con un
consumidor**.

### EL DEFECTO DE PRECEDENCIA EN `AllProfilesController::dataTables` — hallazgo nuevo

`:83-86` construye el HAVING como lista de fragmentos y los une con `implode(' ')`:

```php
$having = [
    "systemApprovalStatus = '" . SystemApprovalsMapper::STATUS_APPROVED . "'",
    "AND userType IS NULL OR userType IN ({$allowedUserTypes})",
];
```

Resultado: `systemApprovalStatus = 'APPROVED' AND userType IS NULL OR userType IN (1,2)`. En SQL
`AND` liga mas fuerte que `OR`, asi que **se lee `(aprobado AND userType IS NULL) OR (userType IN
(1,2))`**. En esa consulta `userType` es `NULL` para ORGANIZACIONES y el tipo real para USUARIOS
—se ve en los dos `SELECT` de la union, `:128` y `:110`—, luego:

- **las organizaciones** se filtran por aprobacion,
- **los usuarios NO**: entran por la segunda rama sin mirar `systemApprovalStatus`.

**SU COTA, y se dice**: la ruta declara `require_login` = true y lleva lista de roles
(`AllProfilesController:248-256`). **No es una fuga publica.** Es que quien ya puede ver el
listado ve tambien perfiles de usuario sin aprobar. Y **no esta comprobado ejecutando**: es
lectura de precedencia SQL. Antes de arreglarlo se mide contra la base (LEY 29).

### EL `if (false)` DE `AllProfilesController:89` SI ES PLANTILLA — y el de Publications NO

`AllProfilesController:89-93` es, literal:

```php
if (false) {
    $beforeOperator = !empty($having) ? $and : '';
    $critery = "FIELD = VALUE";
    $having[] = "{$beforeOperator} ({$critery})";
}
```

`FIELD = VALUE` en mayusculas y sin sujeto: **es un molde, y se lee como molde.** El de
`PublicationsController:1264` no lo es: dice `if ((...) !== $currentUserID && false)` sobre una
regla REAL —«si no es el administrador, solo ver las propias»— con `//NOTE: Desactivado`. Uno
enseña la forma; el otro es una regla de negocio apagada.

**El criterio que los separa, y sirve para los que aparezcan**: una plantilla no nombra datos del
dominio; una regla apagada si. Si al leer la rama sabes QUE decidiria, no es plantilla.

### EL PUNTERO DE `phpstan.neon` APUNTA A UNA ETAPA CERRADA

La nota de los 85 del «grupo B» dice *«Van a E2. Ver T41.»* y **E2 esta cerrada** (§7, arriba).
O se miraron y nadie actualizo la nota, o se cayeron. **No se afirma cual**: es una comprobacion
barata y entra en el bloque de PHPStan. Es exactamente lo que el CODER llamo «las cotas escritas
se pudren», ahora en un archivo de configuracion.

### LOS COMENTARIOS «CUENTAN LA CAMPANA» — medido el 2026-09-02, y el PROPIETARIO lo ha dicho DOS veces

Queja literal: *«Los comentarios de CODER me siguen pareciendo super extensos y como que cuentan
la campana en lugar de contar el codigo.»* Segunda vez. Una queja que se repite es una regla que
fallo, y una regla que fallo se convierte en mecanismo (LEY 11).

**MEDIDO, y una parte NO le da la razon —se dice igual—:**

- `src/app` tiene **25.937 lineas de comentario**. Las que citan la campana —`T##`, `LEY ##`,
  `bloque AX`— son **201: el 0,8%**. La version fuerte de la queja, «el codigo esta lleno de
  relato de campana», **no la sostiene la medicion**.
- La comprobacion 8 (`checkNarrativeComments`) **esta verde y tiene razon**: en
  `DataTablesHelper.php` la racha mas larga de `//` seguidos es **2**. Cero bloques narrativos.
  El CODER cumple LEY 7 al pie de la letra.

**Y AQUI ESTA DONDE SI TIENE RAZON, que es lo que su ojo estaba viendo:**

1. **LA PROSA SE MUDO AL DOCBLOCK, donde el instrumento no mira.** LEY 15 en estado puro. La
   comprobacion 8 exige «mas de dos lineas de prosa Y NINGUNA anotacion»; un docblock con un
   `@param` al final puede llevar cincuenta lineas de relato y pasa. El de `process()`
   (`DataTablesHelper:47`) tiene **50 lineas** y es **el segundo mas largo de `src/app`**, solo
   detras de uno heredado de 168. **Lo escribimos nosotros, en AP.**
2. **DENSIDAD**: 88 comentarios `//` sobre 822 lineas de codigo en ese archivo. Uno cada 9.

**EL DEFECTO DURO, que convierte una queja de estilo en una de correccion:**

**177 sitios en `src/` citan el registro** (`Ver T152`, `Ver T156`…), con **68 claves `T`
distintas**. Hoy **resuelven todas** —163 entradas en `18-siguientes-ventanas.md`—. Y el registro
**esta en la lista de borrado**: «los 81 bloques del registro: el borrado espera al cierre de E6».

> **El dia que se borre el registro, 177 punteros del producto apuntan al vacio.** Y el framework
> SE CLONA: quien reciba el clon lee `Ver T156` y no tiene forma de saber que era.

Es LEY 28 con el sujeto cambiado: un borrado no termina cuando el archivo se va, sino cuando nada
lo sigue nombrando. Aqui el borrado esta PLANIFICADO y los punteros ya estan escritos.

**LA REGLA QUE PROPONE ARQUITECTO** (pendiente de decision del PROPIETARIO):

> En codigo de PRODUCCION, un comentario dice **QUE hace el codigo y QUE se rompe si cambia**.
> Nunca **CUANDO se decidio ni en que bloque**. El «cuando» vive en el registro; el codigo dice
> el «que». Los INSTRUMENTOS DE CAMPANA —`VerifyIntegrityTask` (26 citas), las suites
> `UnitTest-*` (17 en `SqlPlaceholders`)— quedan fuera: ahi el sujeto ES la campana.

**Y ARQUITECTO ES LA FUENTE, no el CODER.** Mis recuadros piden el motivo escrito en el codigo
—«con su motivo escrito en dos lineas», «una linea de comentario, no tres»— y a la vez exigen que
cada decision quede trazada. El CODER escribe lo que le pido. **La verbosidad se corrige en la
instruccion, no en el revisor.**

### EL RENOMBRADO DE COLUMNAS ENTRA EN CAMPANA — porque ROMPE, y lo rompedor viaja junto

Pedido por el PROPIETARIO el 2026-09-02, junto al lote de registros: *«aprovechamos y hacemos
convenciones en esas viejas, dejar ese `user_id` por `userID` o similar»*.

**MEDIDO SOBRE LAS 32 TABLAS, y el resultado es mas limpio de lo esperado:**

- **64 columnas en camelCase** contra **8 con guion bajo**. La convencion no hay que inventarla:
  ya gana 64 a 8.
- **CERO tablas mixtas.** El guion bajo esta confinado a **TRES tablas**:

      login_attempts     8 columnas   (user_id, username_attempt, extra_data)
      pcsphp_users      15 columnas   (created_at, modified_at, failed_attempts,
                                       first_lastname, second_lastname)
      time_on_platform   3 columnas   (user_id)  -> UN solo consumidor: TimeOnPlatformModel

- **Referencias a renombrar, contadas en `src/app`, `src/statics`, `databases` y `bin`:**

      first_lastname   67      user_id            63      second_lastname  50
      modified_at      16      created_at         15      extra_data       15
      failed_attempts  14      username_attempt    7

  **~247 en total**, y `user_id` cruza las tres tablas.

**POR QUE ENTRA EN LA CAMPANA AUNQUE «SOLO» NORMALICE**: es ROMPEDOR. Si se hace despues de la
MAJOR hace falta una SEGUNDA version rompedora para un cambio mecanico. Es el mismo argumento con
el que el PROPIETARIO corrigio a ARQUITECTO en P4, y ahora lo aplica ARQUITECTO por su cuenta.

**SU SITIO ES EL FINAL, JUSTO ANTES DE LA MAJOR.** Hacerlo antes obliga a todos los bloques
siguientes a escribir sobre nombres que van a cambiar.

**DOS COTAS QUE SE ESCRIBEN AHORA PARA NO ABLANDARLAS DESPUES:**

1. **LA CONVENCION ES LA FORMA, NO EL VOCABULARIO.** `first_lastname` pasa a `firstLastname`, NO a
   `apellidoPaterno` ni a `lastnameFirst`. Cambiar tambien las palabras duplica la superficie de
   revision y mezcla dos decisiones.
2. **UN RENOMBRADO TERMINA CUANDO NADA NOMBRA LA FORMA VIEJA (LEY 28).** No basta con el
   `ALTER TABLE` y los mappers: hay volcados en `databases/`, la instantanea de firmas, `bin/cli
   snapshot` y `db-restore`, y JS que puede leer la clave por su nombre. **Cierra con un censo que
   busque las ocho formas viejas y de CERO, con canario.**

**RIESGO NOMBRADO**: `pcsphp_users` es la tabla sine qua non —la que ancla E5— y concentra cinco
de las ocho columnas y 132 de las 247 referencias. `login_attempts` y `time_on_platform` son
baratas; `pcsphp_users` es el bloque.

**CONTRADECIA UNA DECISION CERRADA, Y EL PROPIETARIO LA ANULO.** Las ocho columnas estaban
declaradas como excepcion permanente en `12-convenciones.md`. ARQUITECTO propuso el cambio sin
verlo (LEY 32); el PROPIETARIO lo detecto y decidio: *«ya decidimos hace mucho que esto romperia
retrocompatibilidad»* —cierto y registrado en §7, «Aclaracion del PROPIETARIO que cambia el
calculo de TODO lo que queda»— *«no veo problema en hacer al final un migrado de versiones
anteriores a esta nueva, pero es problema del futuro»*. **La anulacion ya esta escrita EN
`12-convenciones.md`**, que es donde la leera quien la necesite.

**LA CONDICION QUE ARQUITECTO HABIA PUESTO —que la MAJOR llevara su guion de migracion— QUEDA
RETIRADA.** El PROPIETARIO decide que el migrado de versiones anteriores es trabajo posterior.
**Pero se sustituye por una que no cuesta nada y sin la cual ese trabajo posterior es
imposible:**

> **EL BLOQUE DEJA EL MAPA COMO DATO, NO COMO PROSA.** `files/dev/column-renames.json` con las
> ocho parejas `viejo -> nuevo`, su tabla, y la fecha. Aplazar la migracion es legitimo;
> **aplazar el REGISTRO de que hay que migrar es como se pierde**. Dentro de un ano, reconstruir
> las ocho parejas leyendo un diff de 247 referencias cuesta mas que escribir el archivo hoy, y
> el guion de migracion futuro se genera de ese JSON en diez lineas.

Y una precision tecnica sobre el «NADA DE BARRIDOS» del propio `12-convenciones.md`: esa regla se
escribio para IDENTIFICADORES DENTRO DE ARCHIVOS, que se normalizan al pasar. **Una columna no
admite eso**: tiene un nombre y todas sus referencias cambian a la vez. El barrido aqui es la
naturaleza del sujeto, no una comodidad.

### LAS ETIQUETAS: CINCO FORMAS, Y UN INSTRUMENTO QUE DESCARTA EN SILENCIO — 2026-09-02

El PROPIETARIO puso las 79 etiquetas de `piecesphp` delante y pregunto si el versionado esta mal.
**Medido, no opinado.**

**CINCO FORMAS CONVIVEN:**

| Forma | Ejemplos | Cuantas |
| :-- | :-- | --: |
| `vX.Y.Z` | `v7.1.0`, `v5.20.1` | la mayoria |
| `vX.Y` | `v2.7`, `v4.1`, `v5.18`, `v5.2` | ~18 |
| `vX` | **`v3`, `v4`** | 2 |
| sin prefijo | **`4.0.1`, `5.2`** | 2 |
| pre-lanzamiento | `v6.4.2-beta`, `v7.0.0-beta` | 2 — **estos SON correctos** |

Los huecos —`v2.6` a `v2.6.6` sin las intermedias, `v5.0.4` ausente— **NO son un defecto**:
saltarse un parche no rompe nada. Lo que rompe es otra cosa.

**LO QUE SI ROMPE, Y SON DOS COSAS:**

**1. `v6.4.200001`, `v6.4.200002` y `v6.4.201`.** Bajo `version_compare`, **`v6.4.201` es MAYOR que
`v6.4.4`**. Cualquier herramienta que pida «la ultima de la serie 6.4» recibe la 201. No importa
que quisieran decir —numero de compilacion, fecha—: **invierten el orden de su propia serie.**

**2. `latestLocalTag()` (`VerifyIntegrityTask`) DESCARTA EN SILENCIO.** Su filtro es

```php
if (preg_match('/^v?\d+\.\d+\.\d+$/', $etiqueta) !== 1) { continue; }
```

**Exactamente tres partes.** Luego **no ve `v3`, ni `v4`, ni ninguna `vX.Y`**, y en `database`
tampoco `v1.6.0.1` ni las cuatro `v1.9.0.x`. Y **no dice cuantas descarto**.

> **HOY ACIERTA POR SUERTE, NO POR CORRECTO.** Comprobado en los cinco repositorios: el filtro
> deja fuera etiquetas legitimas y aun asi la ultima superviviente coincide con la real —
> `v7.1.0`, `v4.1.0`, `v4.0.0`, `v3.0.0`, `v3.0.0`—. **El dia que alguien etiquete la MAJOR como
> `v8` —que es exactamente lo que se hizo con `v3` y con `v4`— la comprobacion 17 no la vera y
> dira que la ultima es `v7.1.0`, en silencio.**
>
> Es LEY 15 y LEY 16 a la vez: el instrumento informa del universo QUE MIRA, y **un censo que
> descarta parte de su universo sin decir cuanto descarto no es una medicion.**

**LO QUE NO SE HACE: TOCAR LAS ETIQUETAS VIEJAS.** Son el registro, y alguien puede haber fijado
una. Reescribirlas es rehacer la historia para que cuadre con la regla nueva, que es lo contrario
de lo que hace esta campana.

**PROPUESTA DE ARQUITECTO — pendiente de decision (P14):**

1. **La convencion se declara HACIA ADELANTE en `12-convenciones.md`**: `vX.Y.Z` siempre, tres
   partes, prefijo `v`, pre-lanzamiento `-beta.N`. La MAJOR sera **`v8.0.0`**, nunca `v8`.
2. **Las formas historicas se DOCUMENTAN**, con la anomalia `v6.4.20000x` explicada.
3. **`latestLocalTag()` se arregla**: acepta `v?X`, `v?X.Y` y `v?X.Y.Z`, normaliza a tres partes
   antes de comparar, ignora los pre-lanzamientos A PROPOSITO, y **publica cuantas etiquetas
   descarto y por que**. Sin esa linea, vuelve a ser un cero sin canario.

Va con el bloque que nivela los analizadores de los cuatro paquetes: los dos son
**versiones e instrumentos**, y ninguno es codigo de produccion.

### Abierto, sin decidir

- **`profiles-translation-config.js` sigue nombrando `'fr'`.** Sus campos no existen en ninguna
  vista: la funcion ya era un no-op. Del PROPIETARIO.
- **`app/lang/dynamic-translations/fr/global.php` sigue versionado**, con 8 de las 53 huerfanas, y
  hay carpetas `de/`, `it/`, `pt/` con su `.keep`.
- **Los retornos ignorados**: 193 sin declarar · **`Locations`**: cuatro controladoras deciden la
  operacion desde el CUERPO.
- **La arquitectura del front NO ESTA DOCUMENTADA** -> `16-frontend-arquitectura.md`, en E6.
  126 JS, 82 SCSS, 68 CSS, doce adaptadores y un `Proxy` en `configurations.js:113`.
- **`files/API/`** · **los guiones de permisos** · **`phpstan-strict-rules`** · **las tres listas de
  LEY 11** · **T86** · **la asimetria de T114** · **el despliegue** -> E6.
- **`DataImportExportUtility`** -> E5, CONSOLIDACION y ARQUETIPO. Dos pruebas de aceptacion:
  **supervivencia** —aguanta en un clon con lo opcional apagado?— y **utilidad** —puede alguien
  construir uno nuevo sin leerse el nucleo?—. Ancla en usuarios, la tabla sine qua non.
- **Los 81 bloques del registro**: el borrado espera al cierre de E6.

### Fuera de la campana - roadmap

Silencios de Sass · el modulo como patron mecanizable · el skill de aterrizaje · una cache de
verdad · la distribucion sin ruido · el versionado · las cuatro revisiones de seguridad y
operacion · el GUI de traducciones · **las guias de estilo por lenguaje, para humanos y para
agentes** (`files/dev/roadmap/Guias de estilo por lenguaje.md`, pedidas el 2026-08-30: EXTIENDEN,
asi que van despues de la MAJOR; lo descriptivo se queda en `16-frontend-arquitectura.md`, E6)
· **y en la ventana de i18n: `es.js`/`en.js` pasan a ser
ARTEFACTOS GENERADOS desde PHP.** El `Proxy` se queda; lo que cambia es que la base deja de
mantenerse a mano, y entonces el conjunto de idiomas no puede divergir.

**Nota del PROPIETARIO**: al cerrar la campana, recordarle **«Perfeccionar geovisor»**.
