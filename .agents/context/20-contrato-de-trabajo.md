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
- **`git add` con rutas explícitas**, nunca `-A` ni punto. Se cuadra el conteo y se reportan los
  dos números. Si no cuadran, el CODER **para**.
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
- **Cortar demasiado fino después de un error.** Tras equivocarse en el diseño del área pública
  empezó a trocear tanto que cada ronda compraba poco. Lo seguro no es que la instrucción sea
  corta: es que cada pieza tenga su puerta y sus paradas.

---

## 6. Dónde va cada cosa

| Si es… | Va a… |
| :-- | :-- |
| **Ley durable** — cómo se trabaja, una convención | El documento numerado que corresponda |
| **Pendiente** — ventana, peldaño, backlog | [18-siguientes-ventanas.md](./18-siguientes-ventanas.md) |
| **Lo hecho** — medición, hallazgo, error corregido | [`historico/`](./historico/) |
| **Intención declarada del PROPIETARIO, sin resolver** | `files/dev/roadmap/` |
| **El contrato de trabajo** | Este archivo |

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

*Se actualiza en cada pausa. **Aviso de diseño**: esta sección describe lo pendiente, así que
queda desfasada en cuanto CODER commitea el bloque que la volvió cierta. El desfase es de un
bloque y es inherente; lo que no es aceptable es que sea de tres.*

**Última actualización: 2026-08-26, tras el bloque de la memoria y `server-delegated`.**

### Dónde estamos

**E2-a cerrada** salvo 11 rutas declaradas NO comprobadas por falta de datos (T39). Cerró
encontrando lo que E2 existía para encontrar: `news-category-admin-forms-edit`, una ruta de puro
leer, **crea un archivo**, y la máquina lo atribuyó a la ruta correcta.

**E2-b sin empezar**, partida en dos: las **59** rutas `-actions-*` —el 50 era de otra medición, ver
T114—, y `$_FILES` (44 accesos en 18
archivos, solo 3 con guarda) más los 119 POST sueltos.

**Hoja de ruta completa: ver el bloque R del [18](./18-siguientes-ventanas.md).** Resumen: 26–36
bloques hasta E6, y el hito que importa —E3 cerrado, framework limpio y con red— a 11–15 bloques.

### Cerrado en el último bloque

- **La memoria del CODER se subordina al registro** (§6 de este archivo, regla 9 de `CLAUDE.md`).
  La primera auditoría encontró **dos huecos**, que es exactamente lo que la regla predecía: el
  caso que fundó la regla del `git add` explícito vivía solo en su memoria —incluido un
  `PHPStanResult.json` versionado generado con un archivo ya modificado—, y el truco de
  `systemApprovalStatus` como prueba de ejecución del ORM.
- **`server-delegated/` declarado** en `volatile-state.json` y **regla 3 enmendada**: la lista
  puede crecer si la entrada trae medición, propósito y la fecha del hallazgo.
- **LEY 15**: un instrumento informa sobre el universo que MIRA, no sobre el que dice cubrir. Tres
  casos. Se distingue de la 13 en que aquí la puerta sí corre entera, sobre menos de lo que
  promete.
- **LEY 12 ampliada al árbol de archivos**, con las dos cegueras separadas.
- **Comprobación 17**: versión instalada contra última etiquetada.

### Abierto, sin decidir

- **La carrera de `createDynamicSymlink()`**: entre `unlink` y `symlink` el enlace no existe. Una
  petición concurrente recibe **404**. No se reproduce en local; **el framework se clona**.
- **El 4.0.0 del paquete** (bloque Q): cinco piezas diseñadas, ninguna ejecutada.
- **Listas abiertas de LEY 11**: `shared-toolchain.json`, la parte de `volatile-state.json` que no
  es del slug, `deprecated-functions.json`.
- **La puerta de `HttpClient`**, sin cobertura desde el 2026-08-25.
- **T86**: una columna `json` a NULL se guarda como la cadena `'null'`, desde 2018.
- **PHPStan en 888 y no baja porque nadie se lo ha pedido**: el trinquete es un tope, no un motor.
  El trabajo está medido —514 errores con `null`, el 79 % un solo patrón— y depende de la
  cobertura de pruebas, o sea de E4.

### Bloqueado en el PROPIETARIO

- **v3.8.1 y v3.9.0 de `database` etiquetadas y sin instalar.** `composer.lock` sigue en v3.8.0 y
  resuelve desde bitbucket, así que hace falta empujar **las etiquetas**: `git push origin --all`
  y `git push origin --tags` son dos cosas distintas, y `--all` no lleva etiquetas.
- Si el push se cuelga: `GIT_TERMINAL_PROMPT=0 git push origin master` convierte el cuelgue en un
  error legible, y `git ls-remote origin` separa autenticación de empuje.
