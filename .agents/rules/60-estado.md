# Estado y continuidad

**Ningún trabajo depende de una sesión.** Una sesión nueva, de cualquier proveedor, debe saber
desde el repositorio qué hay detrás, qué está en curso y qué viene. Si no puede, el fallo es de
la documentación, y se corrige ahí.

## Dónde vive cada cosa (ADR 0002)

| Pregunta | Dónde | Vida |
| --- | --- | --- |
| ¿Qué pasa ahora? ¿Qué número de mensaje toca? ¿Qué espera al PO? | `.agents/estado/AHORA.md` | Se reescribe en cada ronda |
| ¿Qué se hizo en este tramo? | `.agents/estado/tramos/AAAA-MM-DD-HHMM-<tema>.md` | Volátil: se poda |
| ¿Qué falta hasta la MAJOR, y en qué orden? | `.agents/docs/roadmap.md` | Lo cerrado sale |
| ¿Qué está abierto, y adónde va cada cosa? | `.agents/docs/abierto.md` (2026-10-08) | Lo cerrado sale en la ronda que lo cierra |
| ¿Qué decidió el PO? (índice) | `.agents/docs/decisiones-del-po.md` (LEY 33) | Lo revocado se tacha |
| ¿Qué pasó, día a día? (diario) | El archivo del tramo, en `.agents/estado/tramos/` | Volátil: se poda; git lo guarda |
| ¿Qué viene después de la MAJOR? | `.agents/docs/roadmap-posterior/` | Hasta que se decida |
| ¿Por qué se decidió así? | `.agents/docs/adr/`; antes del 2026-09-14, `19`, `20` y el `18` (en git: «Citas al pasado») | Inmutable |
| ¿Cómo se llegó aquí? | `.agents/docs/bitacora/`; hasta BC, las entradas T del `18`, legibles en git («Citas al pasado») | Crece |
| ¿Qué me muerde si toco esto? | `.agents/context/` | Verdad hoy; se corrige y se poda |
| ¿Qué cambió para quien clona? | `CHANGELOG.md` | Crece |

El mapa (`roadmap.md`) ordena y apunta: una línea por lote y el puntero a donde está descrito.
No copia descripciones. Si un lote aparece en dos documentos, uno describe y el otro apunta.

## `.agents/estado/` — obligaciones del arquitecto

- **`AHORA.md` se actualiza en cada ronda**, antes de enviar la instrucción y al recibir el
  reporte. Lo que diga debe ser verdad en ese momento: una sesión puede morir en cualquier punto
  y la siguiente arranca de ahí.
- **Cada tramo tiene su archivo** en `tramos/`, creado al empezar y ampliado en cada ronda: hora
  de inicio, rondas (número, qué, commits), hallazgos, decisiones, fallos y cómo se resolvieron,
  lo que espera al PO y, al cerrar, la duración.
- **Lo que necesite al PO** va arriba en `AHORA.md`, bajo «Espera al PO», con su número `P<n>` y
  su predeterminado, además de en el chat.
- **Todo encargo, decisión o «recuérdame» del PO** produce, en el mismo turno (LEY 33): su línea en
  `.agents/docs/abierto.md` si es trabajo, su fila en `.agents/docs/decisiones-del-po.md` si es una decisión, y el
  detalle —sus palabras, el contexto, lo medido— en el archivo del tramo. Si es estructural, además su ADR.
- `.agents/estado/` es la excepción a «no escribir con tanda en vuelo»: solo lo escribe el
  arquitecto y el coder la excluye de sus criterios de `git status`. Se commitea en commits
  `docs(estado):` propios, nunca mezclados.

## Poda — la muerte de lo inservible

Documentación que ya no sirve es ruido que un agente leerá como verdad. Se poda:

- **Tramos**: se conservan los 10 más recientes. Uno más viejo se borra cuando lo que importaba
  de él ya está en la bitácora, un ADR o el CHANGELOG.
- **`context/`**: lo que el código ya no hace se borra en el mismo commit que lo cambia. Una
  trampa cubierta por una puerta se reduce a una línea con el nombre de la puerta.
- **Roadmap**: lo cerrado sale; su historia queda en la bitácora.
- **ADR**: nunca se borran; se reemplazan.

**El principio, del PO (2026-10-08):** «Nuestro sistema de trabajo debe garantizar que nada importante se olvide nunca.
Pero las cosas, en algún punto, deben olvidarse.» Lo vivo tiene una línea en un índice que se lee siempre; lo cerrado se
borra dejando su referencia corta (commit, `CHANGELOG`, bitácora). **Un índice que crece sin parar está guardando lo
muerto.**

## Citas al pasado

`pendientes.md` (el diario hasta el 2026-10-08), `context/18-siguientes-ventanas.md` (las entradas `T###`) y
`HERENCIA.md` **se borraron del árbol el 2026-10-08** (E9). Sus citas —«`pendientes.md` 482.3», «Ver T120», «`context/18`,
:5588»— siguen valiendo y se leen en git, en el último commit en que existían:

```bash
git show ecd2407f:.agents/docs/pendientes.md | grep -n '^482\.'
git show ecd2407f:.agents/context/18-siguientes-ventanas.md | grep -n '^## T120 '
```

**Una cita al pasado es una referencia, no una fuente de trabajo**: lo vivo está en `abierto.md` y lo decidido en
`decisiones-del-po.md`. Si algo vivo solo aparece en el pasado, **eso es el hallazgo** y se sube al índice.

El subagente `context-curator` audita esto; el arquitecto decide y ejecuta.

## Arranque de una sesión nueva

1. `.agents/estado/AHORA.md` — dónde estamos.
2. El último archivo de `.agents/estado/tramos/`.
3. `.agents/README.md` — el orden de lectura del resto.
