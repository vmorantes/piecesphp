---
name: asentar-estado
description: >
  Deja en disco, VERDADERO, el estado del trabajo de PiecesPHP sin cerrar la jornada ni tocar al coder: lo que está en
  vuelo, los contadores, lo que espera al PO y lo que el arquitecto tiene a medio depositar. Es el punto intermedio entre
  `abrir-jornada` y `cerrar-jornada`. Invócala cuando el PO la mande, antes de compactar la sesión o de un `/clear`, y
  cuando la propia sesión note que va a compactarse. Para retomar después se usa `abrir-jornada`.
---

# Asentar estado

Nace de una petición del Product Owner, el 2026-10-16: *«debería haber un intermedio entre abrir y cerrar jornada, como
asentar-estado, que no requiera que cierres junto al coder, sino que yo lo mande antes de compactar o incluso de un
`clear`»*.

**El hueco que tapa.** Hasta ese día, la única forma de dejar el estado bien escrito era `cerrar-jornada`, que arrastra
al coder (su ronda tiene que terminar), el correo al PO y la poda. Una compactación o un `/clear` a mitad de jornada no
necesitan nada de eso: necesitan que **lo que solo vive en la conversación pase al disco**, porque lo que no esté escrito
se pierde con ella.

**Lo que NO es.** No es un cierre: no se poda, no se manda correo, no se reescribe `AHORA.md` entero, no se toca al
coder ni se espera su ronda, y no se instruye nada.

## 1. Cuándo

- **Cuando el PO la mande**, normalmente antes de compactar o de un `/clear` de la sesión del arquitecto.
- **Cuando la sesión note que va a compactarse** con algo sin depositar.
- **Antes de un `/clear` del coder**, si el arquitecto tiene algo que el coder deba saber y todavía no está en disco.

## 2. Qué se hace, en este orden

### 2.1. La conversación, al tramo

Todo lo que el PO dijo y todavía no está escrito, al archivo del tramo en curso (`.agents/estado/tramos/`): sus palabras
formalizadas, la hora y a qué contestan. Y lo mismo con cada reporte o pregunta del coder recibidos y no anotados: su
línea con el número, la hora, el estado, los commits y los cuatro números (`cerrar-jornada` §2.1bis).

### 2.2. Lo pendiente de depositar, nombrado

Lo que el arquitecto decidió y todavía no pasó a su sitio —una fila de `decisiones-del-po.md`, una línea de
`abierto.md`, documentación por escribir— se apunta en el tramo con **«A DEPOSITAR»**, dónde va y por qué no está
todavía (casi siempre: el coder tiene una ronda en vuelo y el árbol es suyo).

**Si no hay ronda en vuelo, no se apunta: se deposita.** `abierto.md` y `decisiones-del-po.md` se escriben ahí mismo.

### 2.3. `AHORA.md`, verdadero en cuatro secciones

No se reescribe entero; se corrigen las cuatro que una sesión nueva lee primero:

| Sección | Tiene que decir |
| :-- | :-- |
| §2 · En vuelo | La ronda enviada sin reporte, con su número y qué hace; o «nada» y por qué |
| §3 · Contadores | El último `#NNN` REAL y el último `A-NNN` REAL |
| §4 · Espera al PO | Las preguntas abiertas, que también estén en `PO.md` |
| «Al retomar» | El primer paso concreto al volver |

### 2.4. `PO.md`, al día

Toda pregunta abierta, con su predeterminado. Lo contestado sale de ahí y su decisión queda en el tramo (2.1) o en
`decisiones-del-po.md` (2.2).

### 2.5. La comprobación

```bash
bash .agents/scripts/verificar.sh
wc -l .agents/estado/AHORA.md   # tope: 200
```

`.agents/estado/` **no se commitea aquí**: lo commitea el coder en su próxima ronda (`docs(estado):`), como siempre. Lo
que importa es que esté en disco; git no se pierde con una compactación.

## 3. Lo que se le dice al PO

Corto, con su `A-NNN` y la hora con segundos:

1. **Asentado**: qué quedó escrito y dónde.
2. **En vuelo**: lo que está a medias, en una línea.
3. **A depositar**: lo que espera a que el coder termine, si hay algo.
4. **Para retomar**: «`/abrir-jornada`».

## 4. Retomar

**No hay una skill «reanudar»: es `abrir-jornada`**, que ya existe para después de una compactación o de reabrir el chat
(su §1) y que lee justo lo que esta skill deja escrito. Dos skills que hacen lo mismo divergen.
