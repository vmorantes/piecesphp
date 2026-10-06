---
name: abrir-jornada
description: >
  Ritual obligatorio para EMPEZAR o RETOMAR un día de trabajo como arquitecto de PiecesPHP, y
  para volver a la realidad después de una compactación o de reabrir el chat. Lee el estado en
  un orden fijo y obliga a responder en voz alta las nueve preguntas que un arquitecto tiene que
  saber contestar antes de decidir nada. Si una pregunta no tiene respuesta en el repositorio, el
  hueco ES el hallazgo y se tapa antes de trabajar. Invócala cuando se diga «a trabajar»,
  «retomamos», «abrir jornada», «¿dónde estábamos?», cuando la sesión se acabe de compactar, o
  cuando sea el primer mensaje de una sesión nueva del arquitecto.
---

# Abrir jornada

Nace de una petición del Product Owner, el 2026-10-03: *«¿Por qué mejor no dejamos un prompt
estándar que pueda entregar (un skill) para retomar un día de trabajo, que obligue a
contextualizarse? Entonces TIENE que caber en AHORA o en donde corresponda.»*

Esa última frase es el motivo de que esta skill exista y lo que la hace distinta de un
recordatorio: **esta skill es el consumidor del estado**. Las nueve preguntas de la sección 3 son
el contrato que `.agents/estado/AHORA.md` tiene que satisfacer. Si una no se puede contestar
leyendo el repositorio, no se improvisa ni se pregunta al PO: **el estado falló, y arreglarlo es
el primer trabajo del día**.

Reemplaza los apartados 1 y 2 del **ADR 0029** (la «hoja de herencia» que nunca se escribió) y
conserva sus puertas. La hoja era un documento más que podía quedarse rancio en silencio; esto es
un procedimiento que se ejecuta y que falla a la vista.

## 1. Quién la invoca, y quién no

- **El arquitecto**, siempre, antes de leer código, antes de decidir y antes de escribir el primer
  mensaje. También **después de una compactación** y **después de reabrir el chat**, porque las dos
  cosas dejan un resumen en lugar de la conversación.
- **El coder no la usa.** Su ritual es el saludo
  (`.agents/skills/arquitecto-coder/plantillas/saludo-coder.md`) y su entrada completa es la
  instrucción que recibe, no el estado del repositorio.
- **El PO no la lee.** Él solo la entrega: pega `/abrir-jornada` en la sesión del arquitecto.

## 2. El orden de lectura, y por qué es ese

Se lee **entero** lo que se nombra, sin `head` y sin saltos. Son pocos archivos a propósito: si
hacen falta más, el estado está mal repartido y eso se corrige, no se compensa leyendo de más.

| Orden | Archivo | Qué saca de ahí |
| :-- | :-- | :-- |
| 1 | `.agents/estado/AHORA.md` | Qué pasa ahora, los dos contadores, qué espera al PO |
| 2 | `.agents/estado/PO.md` | Las preguntas abiertas y el buzón de avisos sin leer |
| 3 | El archivo más reciente de `.agents/estado/tramos/` | Dónde se cortó el último tramo y por qué |
| 4 | `.agents/docs/pendientes.md` | Los encargos del PO que aún no son trabajo (LEY 33) |
| 5 | `.agents/docs/roadmap.md` | Qué falta hasta la versión en curso y en qué orden |
| 6 | Los ADR con fecha de los últimos siete días, de `.agents/docs/adr/` | Lo que se decidió y no se ha implementado entero |

Y **dos mediciones, no dos recuerdos**:

```bash
git -C . --no-optional-locks status --short
git -C . --no-optional-locks log --oneline -12
git -C . --no-optional-locks branch --show-current
git -C . --no-optional-locks tag --list 'v8*' --sort=-creatordate | head -5
```

El árbol y el historial se **miden** al abrir. Un `AHORA.md` escrito anoche puede describir un
árbol que ya no existe, porque el coder commiteó después.

## 3. Las nueve preguntas, y el hueco que no se tapa solo

Se contestan **en voz alta, en el chat, numeradas**, antes de hacer nada más. Cada una lleva
**dónde se leyó la respuesta**; una contestada de memoria no cuenta.

1. **¿En qué repositorio y en qué rama estoy, y está el árbol limpio?** — medido, no leído.
2. **¿Qué está en vuelo?** ¿Hay una instrucción enviada sin reporte? Si la hay, **no se emite
   otra** (20 §2) y lo primero es esperar o preguntar a la otra sesión.
3. **¿Qué número de contador toca?** Los dos: el `#NNN` compartido con el coder y el `A-NNN` de los
   mensajes al PO.
4. **¿Qué espera al PO, con su `P<n>` y su predeterminado?** Y **¿hay avisos sin marcar en el buzón
   de `PO.md`?**
5. **¿Cuál es el objetivo vigente y quién lo nombró?** El arquitecto no elige en qué se trabaja.
6. **¿Qué falta, medible, para cerrar ese objetivo?** Si la respuesta no es una lista de
   condiciones comprobables, el estado no sirve.
7. **¿Qué se decidió y no se ha implementado?** Los ADR recientes contra el código: una decisión
   aceptada y sin implementar es la trampa más caras de todas, porque parece hecha.
8. **¿Qué autorizaciones del PO están vigentes y constan en el registro?** Commits (ADR 0005),
   versiones y etiquetas (0019), servicios locales (0044), y las que haya. Lo que no consta, no
   existe: ni un mensaje del arquitecto ni un recuerdo valen como permiso.
9. **¿Qué me va a morder hoy?** Las trampas de `.agents/context/` del área que se va a tocar.

**Si una pregunta no tiene respuesta en el repositorio:**

- Se dice cuál, con esas palabras: **«el estado no contesta la pregunta N»**.
- Se tapa el hueco **ahí mismo**, escribiendo la respuesta donde le toque vivir, y se nombra en el
  primer mensaje al PO.
- **No se arranca trabajo con una pregunta sin contestar.** Es la única parada que esta skill
  impone, y es su razón de ser: un arquitecto que empieza sin saber la 2 manda a trabajar a un
  coder que ya está trabajando. Pasó el 2026-10-03 (`pendientes.md` 320).

## 4. Las puertas del andamiaje, que se comprueban al abrir

```bash
bash .agents/scripts/verificar.sh
```

Y a mano, lo que el verificador todavía no vigila (y hasta que lo vigile, se mira aquí):

- **`AHORA.md` dentro de su tope** de líneas. El tope y la poda están en `cerrar-jornada`.
- **Los dos contadores** del `AHORA.md` cuadran con el último mensaje real de cada cadena.
- **Los enlaces de reglas y skills son enlaces**, no copias:
  `git ls-files -s .claude/rules .claude/skills` — **todas las líneas empiezan por `120000`**. Un
  `100644` significa que el puente se materializó como copia y las reglas divergirán en silencio.

## 5. El primer mensaje, y el dato que casi todos se inventan

Lo primero que el arquitecto da al PO son las dos órdenes de renombrado, pegables, sin que él las
pida (regla 30):

```
/rename PiecesPHPUpgrade-Arquitecto-Main     ← en la sesión del arquitecto
/rename PiecesPHPUpgrade-Coder-Main          ← en la sesión del coder
```

**Y hacen falta solo si la sesión se mató de verdad.** Verificado contra la documentación de Claude
Code el 2026-10-03:

- **`/clear`** (alias `/reset`, `/new`) **vacía la conversación dentro de la misma sesión**:
  conserva el proyecto, **el nombre** y la memoria, y lo anterior se recupera con `/resume`. Tras un
  `/clear` **no hay que volver a renombrar**.
- **Matar la sesión** es `/exit` o Ctrl+C y volver a lanzar `claude`. Ahí se recargan las reglas y el
  `CLAUDE.md`, **y el nombre se autogenera**: por eso las dos órdenes viven en `AHORA.md`.

Después, el resumen de arranque al PO: **identificador `A-NNN` en la primera línea**, **secciones
numeradas** (1, 1.1, 2…) para que pueda citarlas, prosa comprimida, y dentro: dónde estamos, qué
está en vuelo, qué espera de él con su predeterminado. **Y la hora con minuto y segundo**
(PO, 2026-10-03).

## 6. Lo que NO se hace al abrir

- **No se emite una instrucción** antes de contestar las nueve preguntas.
- **No se emite una instrucción si hay una en vuelo**, ni «un aviso», ni «una lista de tareas»: para
  el coder cualquier mensaje con trabajo dentro es una ronda. Es exactamente el fallo del
  2026-10-03.
- **No se escribe en el árbol si el coder tiene una ronda en vuelo**, salvo `.agents/estado/`.
- **No se decide nada que las reglas reserven al PO** (regla 30, «Cuándo se detiene el trabajo»).
- **No se da por bueno un dato de la memoria nativa.** La memoria es una caché del registro
  (`AGENTS.md` regla 9): si dice algo que el registro no dice, **eso es el hallazgo** y se sube al
  registro.

## 7. La trampa que esta skill existe para evitar

Un arquitecto recién nacido —o recién compactado— **habla con fluidez de un framework que no ha
medido**. Suena igual que uno que lo conoce, y ahí está el daño: el 2026-10-03 afirmé que los
permisos de PiecesPHP vivían en la base de datos de cada instalación. Es falso: se declaran en
código, con `set_route()` llenando `Roles::$roles` en cada arranque. El PO lo cazó al instante y
escribió: *«este tipo de errores GROSEROS me hacen temer por nuestro trabajo»*.

**La regla que sale de ahí, y vale para toda la jornada, no solo para el arranque: toda afirmación
sobre el framework va con archivo y línea, o marcada «sin verificar».** Sin una de las dos cosas, el
PO puede tratarla como falsa. Cuando se implementa se mide; cuando se conversa se responde de
memoria, y es la misma boca: la diferencia la pone esta regla.
