# Los silencios de Sass

> **NOTA DE ROADMAP. NO SE EJECUTA.** Está aquí para cuando el PROPIETARIO la saque del tintero.
> Todo lo de abajo está **medido**, no es impresión.

## El estado

`src/gulpfile.js`, en `sassCompileAdapter()`, apaga **cinco deprecaciones** más el ruido de las
dependencias:

```js
silenceDeprecations: [
    'legacy-js-api',
    'color-functions',
    'mixed-decls',
    'import',
    'global-builtin',
],
quietDeps: true,
```

**Ninguna de las cinco lleva motivo escrito.** Ni una línea de comentario dentro del bloque.

**Es exactamente el estado en que estaba `phpstan.neon` antes de esta campaña**: una lista de
silencios acumulada, sin razón junto a cada entrada, que nadie podía auditar porque no había nada
que auditar — solo nombres.

## La deriva de versión, que es lo que lo vuelve urgente

| | |
| :-- | :-- |
| `package.json` declara | `"sass": "^1.77.4"` |
| Instalado en esta máquina | **1.99.0** |

**Veintidós versiones menores de deriva.** El acento `^` deja subir toda la rama 1.x, así que un
clon nuevo instala lo último que haya salido ese día. **El clon nuevo se rompe antes que la máquina
del PROPIETARIO**, y se rompe con un compilador que aquí nadie ha probado.

## El aviso que atraviesa el filtro, y lo que significa

Un único aviso sobrevive a los cinco silencios:

> `mixed-decls deprecation is obsolete`

Dice **dos cosas a la vez**, y las dos importan:

1. **El silencio está caducado**: esa deprecación ya no existe como tal.
2. **El comportamiento ya cambió** — y cambió sin que nadie lo mirara, porque el silencio impedía
   verlo mientras cambiaba.

## El plan, para cuando toque

1. **Fijar `sass` a versión exacta.** Sin `^`. La deriva se elige, no se hereda.
2. **Guardar el CSS actual como referencia.** Todo el que produce `gulp init-project`.
3. **Quitar los silencios de uno en uno**, comparando el CSS **byte a byte** después de cada uno.

Cada silencio acaba en uno de tres sitios, y no hay cuarto:

| Resultado | Qué se hace |
| :-- | :-- |
| **Un defecto** | Se arregla |
| **Un falso positivo** | Se conserva el silencio **con su motivo escrito al lado** |
| **Una entrada caducada** | Se borra |

Es el mismo procedimiento que la auditoría de los 67 `ignoreErrors`, y por la misma razón: **un
silencio sin motivo escrito no es una decisión, es una deuda anónima.**
