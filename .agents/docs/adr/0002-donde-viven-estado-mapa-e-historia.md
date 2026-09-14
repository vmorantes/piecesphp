# 0002 — Dónde viven el estado, el mapa a la MAJOR y la historia

- **Estado:** Aceptada
- **Fecha:** 2026-09-14
- **Decide:** Arquitecto
- **Estructural:** sí (dónde vive algo)

## En cristiano

Lo que pasa ahora se lee en `.agents/estado/AHORA.md`. Lo que falta hasta la MAJOR, en un solo
mapa, `.agents/docs/roadmap.md`, que no copia descripciones: apunta a donde ya están. Lo que el
PO pide o decide sigue en `files/dev/PENDIENTES.md`, como manda la LEY 33. Y la historia de
cada tarea nueva va a la bitácora, no a más entradas del registro 18, que está hecho para
desaparecer.

## Contexto

- La skill `arquitecto-coder` propone `estado/` en la raíz, un roadmap y una bitácora.
- Este repositorio ya tenía piezas para casi todo, repartidas:
  - `18-siguientes-ventanas.md`: backlog **y** historia (168 entradas T, 17.852 líneas), con
    una cláusula de disolución: se disuelve al cerrar E6.
  - `20-contrato-de-trabajo.md` §7, «Estado abierto»: el estado vivo, actualizado por última
    vez el 2026-09-01, a unas 1.000 líneas de profundidad en un documento de 2.056.
  - `files/dev/PENDIENTES.md`: encargos y decisiones del PO (LEY 33), creado el 2026-09-13.
  - `files/dev/roadmap/`: 16 documentos de lo que va **después** de la MAJOR.
- El mapa de lotes hasta la MAJOR que el arquitecto anterior dio el 2026-09-13 **no se
  escribió en ningún archivo**: vivía en el chat. `PENDIENTES.md` remite a «20 §7 y
  `files/dev/roadmap/`», y 20 §7 es trece días más viejo que ese mapa.
- El framework es una **plantilla que se clona** (18 T0). Cada carpeta de la raíz viaja a los
  clones, y el PO quiere una herramienta de despliegue que deje fuera lo que es de desarrollo
  (`PENDIENTES.md`, 2026-08-24).

## Decisión

El estado vive en `.agents/estado/`, el mapa a la MAJOR en `.agents/docs/roadmap.md` (una
línea por lote y su puntero), la bitácora y los ADR en `.agents/docs/`; el 18 no recibe
entradas nuevas y §7 del 20 queda superado por `AHORA.md` y el mapa.

| Pregunta | Dónde |
| --- | --- |
| ¿Qué pasa ahora? ¿Qué número toca? ¿Qué espera al PO? | `.agents/estado/AHORA.md` |
| ¿Qué se hizo en este tramo? | `.agents/estado/tramos/` |
| ¿Qué falta hasta la MAJOR, y en qué orden? | `.agents/docs/roadmap.md` |
| ¿Qué pidió o decidió el PO que aún no es trabajo? | `files/dev/PENDIENTES.md` |
| ¿Qué viene después de la MAJOR? | `files/dev/roadmap/` |
| ¿Cómo se llegó aquí? | `.agents/docs/bitacora/` desde el 2026-09-14; antes, las entradas T del 18 |

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| `estado/` en la raíz, como trae la skill | Añade una carpeta de raíz a una plantilla que se clona. Con todo lo de agentes bajo `.agents/`, una herramienta de despliegue la deja fuera con una sola regla |
| Un roadmap que copie las descripciones de los lotes | Dos verdades sin puerta entre ellas, justo lo que la campaña pasó un mes retirando (20 §6). El mapa ordena y apunta; describir sigue siendo del sitio donde ya está escrito |
| Mantener el estado en §7 del 20 | Enterrado a mitad de un documento durable, y ya se había quedado trece días viejo |
| Seguir añadiendo entradas T al 18 | El 18 nació para morir: cada entrada nueva habría que moverla otra vez en E6 |
| Meter el mapa a la MAJOR en `PENDIENTES.md` | Mezcla trabajo planificado con decisiones que esperan al PO, las dos categorías que el propio `PENDIENTES.md` dice que un resumen confunde |

## Consecuencias

- Tres documentos contestan «¿qué falta?», cada uno una pregunta distinta (lotes, encargos del
  PO, después de la MAJOR). La tabla de `60-estado.md` dice cuál es cuál; si un lote aparece en
  dos, el mapa apunta y no describe.
- La historia queda en dos sitios: las entradas T (hasta BC) y la bitácora (desde 0001). La
  bitácora 0001 lo dice.
- `.agents/` pesa más en cada clon hasta que exista la herramienta de despliegue.

## Reversión

1. Para llevar el estado a la raíz: mover `.agents/estado/` a `estado/` y cambiar la ruta en
   `60-estado.md`, `30-protocolo-coder.md`, `.agents/README.md`, `.claude/CLAUDE.md`,
   `AGENTS.md` y `CLAUDE.md`.
2. Para volver al estado en el 20: copiar `AHORA.md` a §7 y quitar la nota de superación.
3. Para reabrir el 18: quitar su nota de congelación.

Todo es mover texto: reversión completa y no destructiva.

## Verificación

`AHORA.md` cambia en cada ronda; `roadmap.md` no contiene descripciones de más de una línea
por lote; `grep -c '^## T1[7-9][0-9]' .agents/context/18-siguientes-ventanas.md` no crece
después de T168.
