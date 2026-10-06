---
name: cerrar-jornada
description: >
  Ritual obligatorio para CERRAR un día de trabajo como arquitecto de PiecesPHP, o para cerrar un
  tramo antes de parar. Deja el árbol limpio, reescribe `.agents/estado/AHORA.md` con las nueve
  respuestas que `abrir-jornada` va a exigir, poda lo que ya no sirve y entrega al PO el resumen con
  su forma fija. Invócala cuando se diga «cerramos», «cerrar jornada», «paramos por hoy», «cierra el
  tramo», cuando el PO pida parar, y antes de proponer un relevo de sesión.
---

# Cerrar jornada

La pareja de `abrir-jornada`, y la que hace el trabajo de verdad: **abrir solo puede leer lo que
cerrar escribió**. Nace de la misma petición del Product Owner del 2026-10-03, y de su exigencia
anterior: un sistema de herencia *«volátil, pero que nunca falte ni mienta»*.

Las dos palabras importan y son las dos mitades de esta skill. **Volátil**: lo que ya no sirve se
borra, porque documentación muerta la lee un agente como verdad. **Que nunca falte ni mienta**: lo
que queda tiene que bastar para que una sesión recién nacida trabaje, y tiene que ser verdad en el
momento en que se escribió.

## 1. Cuándo se ejecuta

- **Al final de un tramo**, siempre.
- **Cuando el PO pide parar.** La ronda en vuelo **se termina** primero: nunca se deja un árbol a
  medio commitear (regla 30).
- **Antes de proponer un relevo** de cualquiera de las dos sesiones.
- **Cuando la sesión note que va a compactarse** y haya algo sin depositar.

**Ninguna jornada termina con el árbol sucio** (PO, 2026-09-15): un árbol sucio no le deja empujar.
Si el arquitecto escribió documentación después de la última ronda, **abre otra ronda corta** para
que el coder la commitee. Esa ronda es parte del cierre, no del día siguiente.

## 2. El orden del cierre

### 2.1. Primero el árbol, que es lo único que no se puede arreglar mañana

```bash
git -C . --no-optional-locks status --short
```

Todo lo del arquitecto que esté sin commitear se reparte en su ronda: el código en commits propios,
la documentación en `docs:` y `.agents/estado/` en su `docs(estado):` aparte. Lo que quede sin
commitear se **nombra** en el resumen al PO, con el motivo.

### 2.2. Después `AHORA.md`, contra el contrato de las nueve

`AHORA.md` se reescribe **entero**, no se le añade al final. Tiene que contestar, en este orden y
sin que haga falta otro archivo, **las nueve preguntas de `abrir-jornada` §3**:

| Sección obligatoria | Contesta |
| :-- | :-- |
| **Sesiones** (con las dos órdenes de `/rename`) | 1 |
| **En vuelo** | 2 |
| **Los contadores** (`#NNN` y `A-NNN`) | 3 |
| **Espera al PO** (cada punto con su `P<n>` y su predeterminado) | 4 |
| **Mandato vigente**, con quién lo nombró | 5 |
| **Qué falta**, como condiciones comprobables | 6 |
| **Decidido y sin implementar**, con su ADR | 7 |
| **Autorizaciones vigentes**, con su ADR | 8 |
| **Trampas del área en curso**, con su puntero a `.agents/context/` | 9 |

**El tope: 200 líneas.** Más generoso que las 120 de antes, porque el PO pidió expresamente que si
`AHORA.md` es tan importante tenga límites más amplios. Pero sigue siendo un tope, y por un motivo
medible: un archivo que una sesión nueva no lee entero no es estado, es archivo. El tope se comprueba
en el cierre y se vigila por máquina (§4).

**Si no cabe, no se sube el tope: se poda.** Lo que sobra casi siempre es historia, y la historia
tiene su sitio.

### 2.3. La poda, con sus reglas

Tres reglas, y son las que se vigilan por máquina:

1. **Un solo bloque «al retomar».** Hoy `AHORA.md` tiene dos («lo primero del andamiaje», «lo
   primero del producto») y uno de ellos siempre está rancio. Uno, con su lista en orden.
2. **Ninguna decisión vive en `AHORA.md`.** Una decisión va a un ADR y `AHORA.md` la **apunta**. Si
   la decisión solo está aquí, el día que se poda se pierde; y si está en los dos sitios, el día que
   cambia miente uno de los dos.
3. **Lo cerrado sale en la ronda que lo cierra**, no «cuando se limpie». Un plan cumplido se borra;
   lo que valga la pena de él ya está en la bitácora, el ADR o el `CHANGELOG`.

Y lo demás, por su sitio (regla 60):

- **`tramos/`**: se conservan los 10 más recientes; uno más viejo se borra cuando lo que importaba
  está en la bitácora, un ADR o el `CHANGELOG`.
- **`pendientes.md`**: lo resuelto se tacha con el número de la ronda que lo resolvió.
- **`roadmap.md`**: lo cerrado sale.
- **`context/`**: lo que el código ya no hace se borra; una trampa cubierta por una puerta se queda
  en una línea con el nombre de la puerta.
- **ADR**: nunca se borran; se reemplazan.
- **Las «correcciones al registro»**: una fila que lleva días diciendo «pendiente» **miente sobre sí
  misma**. En el cierre se resuelve o se convierte en entrada de `pendientes.md` con su número, y la
  fila se va.

El subagente `context-curator` propone; el arquitecto decide y ejecuta.

### 2.4. El archivo del tramo

Se cierra el de `.agents/estado/tramos/` con la duración, las rondas, los hallazgos, lo que falló y
cómo se resolvió. Es lo que `abrir-jornada` lee en tercer lugar para saber **por qué** se cortó donde
se cortó.

### 2.5. Y la memoria nativa, al final

Se guarda el cierre, y **solo lo que ya vive en el registro, con su puntero** (`AGENTS.md` regla 9).
Si al escribirlo aparece algo que el registro no tiene, **eso es el hallazgo**: se sube al registro
primero y luego se guarda el puntero.

## 3. El resumen al PO, con su forma fija

**No en prosa suelta** (PO, 2026-09-15). Identificador `A-NNN` en la primera línea, **hora con
minuto y segundo** (PO, 2026-10-03), secciones numeradas, y estos cinco puntos:

1. **Rondas y duración**: `#NNN`–`#NNN`, de hh:mm a hh:mm.
2. **Cerrado**: qué, con sus commits.
3. **Encontrado y decidido**: con los ADR, si los hay.
4. **Falló por el camino**: y cómo se resolvió. **Incluidos los fallos propios**, con esas palabras.
5. **Espera al PO**: lista numerada que pueda contestar punto por punto, **cada punto con su
   contexto y su predeterminado**. Él no ha visto el trabajo: se le contextualiza.

Y las dos órdenes de renombrado otra vez, al cierre, para que las tenga a mano la próxima vez
(regla 30).

**Y el mismo resumen, corto, al correo del PO, con `mail-notify`** (PO, 2026-10-02 y 2026-10-05: «repórtame al
correo y acá»). Es una herramienta suya de esta máquina (`~/.local/bin/mail-notify`), que envía con el SMTP de su
propio `.env`:

```bash
mail-notify -s "PiecesPHP · <fecha>: <titular>" -f <resumen>.md --json
```

- **Sin `--to`**: el destinatario es el que él configuró. **Nunca se abre su `.env`**: tiene sus credenciales.
- **El resumen va en un `.md` del scratchpad**, que fija el formato. **Mini**: lo hecho, lo que necesita de él y dónde
  está el detalle. El largo vive en el chat y en `.agents/estado/`.
- **Se da por enviado solo si responde `"ok": true` con salida 0.** Si falla, se le dice en el chat.
- **No es un «archivo a su otro dispositivo»**: el 2026-10-05 el arquitecto le mandó el resumen así, en vez de por
  correo, y el PO no sabía de qué le hablaba. El canal es el correo.

## 4. Las puertas, para que esto no dependa de la disciplina

Un ritual que se cumple porque alguien se acuerda es un ritual que un día no se cumple. Estas tres
comprobaciones van a `.agents/scripts/verificar.sh`, y mientras no estén se hacen a mano **y se dice
que se hicieron a mano**:

| Puerta | Qué comprueba | Falla si |
| :-- | :-- | :-- |
| **El tope** | `AHORA.md` ≤ 200 líneas | Lo pasa |
| **Un solo «al retomar»** | Cuenta los bloques de arranque de `AHORA.md` | Hay más de uno |
| **Los contadores** | El `A-NNN` de `AHORA.md` contra el último del buzón de `PO.md`; el `#NNN` contra el último mensaje de la cadena | No cuadran |

Son del arquitecto —`.agents/` es suyo— y las implementa el coder en una ronda, con su prueba de
rechazo: **una puerta que no se ha visto fallar no se ha visto funcionar**.

## 5. Lo que este cierre NO es

- **No es un volcado de la conversación.** La bitácora es una entrada por tarea cerrada, no una
  transcripción. Lo que no cambie lo que alguien hará mañana, no entra.
- **No es un sitio para decidir.** Si en el cierre aparece una decisión, se escribe su ADR y
  `AHORA.md` lo apunta.
- **No es opcional cuando el día acabó mal.** Un día que termina con un fallo propio se cierra
  igual, y el fallo entra en el punto 4 del resumen. Un cierre que omite un fallo es peor que el
  fallo.
