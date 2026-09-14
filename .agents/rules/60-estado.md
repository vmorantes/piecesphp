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
| ¿Qué pidió o decidió el PO que aún no es trabajo? | `.agents/docs/pendientes.md` (LEY 33) | Se tacha al resolverse |
| ¿Qué viene después de la MAJOR? | `.agents/docs/roadmap-posterior/` | Hasta que se decida |
| ¿Por qué se decidió así? | `.agents/docs/adr/`; antes del 2026-09-14, `18`, `19` y `20` | Inmutable |
| ¿Cómo se llegó aquí? | `.agents/docs/bitacora/`; hasta BC, las entradas T del `18` | Crece |
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
- **Todo encargo, decisión o «recuérdame» del PO** produce su línea en `.agents/docs/pendientes.md`
  en el mismo turno (LEY 33).
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
- **`18-siguientes-ventanas.md`**: se disuelve al cerrar E6 según su propia cláusula. No recibe
  entradas nuevas desde T168.
- **`.agents/HERENCIA.md`**: se borra cuando se cumplan sus propias condiciones, con entrada de
  bitácora.
- **ADR**: nunca se borran; se reemplazan.

El subagente `context-curator` audita esto; el arquitecto decide y ejecuta.

## Arranque de una sesión nueva

1. `.agents/estado/AHORA.md` — dónde estamos.
2. El último archivo de `.agents/estado/tramos/`.
3. `.agents/README.md` — el orden de lectura del resto.
