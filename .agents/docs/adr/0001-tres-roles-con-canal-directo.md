# 0001 — Tres roles con canal directo, sobre el registro existente

- **Estado:** Aceptada
- **Fecha:** 2026-09-14
- **Decide:** Product Owner (adoptar el modelo) · Arquitecto (cómo encaja en este repositorio)
- **Estructural:** sí (cómo se trabaja)

## En cristiano

Desde hoy el trabajo lo llevan dos sesiones de agente con papeles separados: una decide y
escribe toda la documentación (el arquitecto) y otra programa, mide, prueba y commitea (el
coder). Se hablan directamente, sin que el PO copie y pegue mensajes, y encadenan tareas
mientras haya trabajo que el PO haya nombrado. El PO sigue lo que pasa en
`.agents/estado/AHORA.md` y solo se le interrumpe para decisiones suyas o para lo que las
reglas exigen autorizar. Todo lo que la campaña anterior aprendió —las leyes, el contrato, los
instrumentos— sigue valiendo.

## Contexto

- Del 2026-08-19 al 2026-09-14 la campaña de calidad (migración a PHP 8.5 y camino a la
  MAJOR) se trabajó con un arquitecto en otra herramienta, que leía el repositorio por un
  puente, y un coder en Claude Code. **El PO era el mensajero**: copiaba cada recuadro de un
  chat al otro (`20-contrato-de-trabajo.md` §1: «No hay canal directo»).
- De esa campaña quedan 33 leyes (`19-leyes.md`), el contrato de trabajo (`20`), el registro
  de tareas (`18`, entradas T hasta T168 = bloque BC), `files/dev/PENDIENTES.md` (LEY 33), 16
  documentos de roadmap posterior a la MAJOR (`files/dev/roadmap/`) y los instrumentos
  (`bin/guarda-add`, `bin/censo-*`, `bin/cli verify-integrity`, `bin/cli gates`).
- En ese modelo **el coder escribía documentación**: las entradas T del 18 y el CHANGELOG (el
  bloque BC le ordenaba ambas cosas en su paso 3 y en su cierre). El CHANGELOG pasó cinco
  bloques sin tocarse porque dejó de figurar en las instrucciones (`PENDIENTES.md` §2).
- El estado vivo del proyecto estaba en §7 del 20, actualizado por última vez el 2026-09-01.
  El mapa de lotes hasta la MAJOR que el arquitecto anterior dio el 2026-09-13 **solo existió
  en el chat** (ADR 0002).
- El PO creó la skill `arquitecto-coder` y el 2026-09-14 ordenó adoptarla con un arquitecto
  nuevo: *«Creé el skill arquitecto coder para dejar una mejor base fundada.»*
- Arquitecto y coder son ahora sesiones de Claude Code en la misma máquina, con mensajería
  entre sesiones.

## Decisión

Se trabaja con los tres roles de `.agents/rules/30-protocolo-coder.md`, con canal directo entre
sesiones y contador de mensajes único, **conservando como reglas de oficio las leyes (`19`) y
el contrato (`20` §3 y §5)**; el arquitecto pasa a escribir toda la documentación, incluidos el
CHANGELOG y la bitácora.

## Qué se conserva y qué cambia

| Se conserva | Cambia |
| --- | --- |
| Las 33 leyes, íntegras y con su caso | El PO deja de transportar: canal directo entre sesiones |
| `20` §3 (oficio de las instrucciones) y §5 (defectos recurrentes del arquitecto) | Primera línea de todo mensaje: `[#NNN · ARQ · fecha]` / `[#NNN · COD · fecha · herramienta / modelo]`. La línea `VIGENTE · BLOQUE XX` del 20 §2 pasa a ser la segunda |
| Los «puntos serios» del 20 §2: con ellos se habla con el PO antes y no se instruye | El estado vivo sale de §7 del 20 a `.agents/estado/AHORA.md` (ADR 0002) |
| Nada de push, ninguna etiqueta, `git add` con rutas explícitas y `bin/guarda-add` | El coder deja de escribir documentación: ni entradas T ni CHANGELOG. Los escribe el arquitecto con el reporte en mano; el coder los commitea sin editar, en commits `docs:` |
| Los nombres de bloque por letras (… BB, BC, BD): nombran tareas | Las entradas T del 18 se congelan en T168; lo nuevo va a la bitácora (ADR 0002) |
| La regla de los diez archivos (18 T0bis): se enseña al PO antes de commitear | Salvaguardas forzadas por máquina (ADR 0003) |
| LEY 33: todo encargo del PO produce su línea en `PENDIENTES.md` en el mismo turno | Tramos autónomos: el arquitecto encadena rondas mientras haya trabajo nombrado por el PO |

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| Seguir con el PO de mensajero | Es un cuello de botella que no aporta: no lee los recuadros (20 §2, en sus palabras). Y cada copia a mano es un sitio donde se pierde uno o se pega el equivocado (20 §2, «tres recuadros con el mismo nombre») |
| Reemplazar el 19 y el 20 por las reglas nuevas | Se perderían 33 leyes con su caso y reglas de oficio que ya costaron fallos. La skill manda completar, no reemplazar |
| Que el coder siga escribiendo las entradas T y el CHANGELOG | Deja al agente cuyo trabajo hay que comprobar escribiendo el registro con el que se comprueba (20 §5, «delegar en el CODER la sección que es la memoria de ARQUITECTO»). Y así se perdieron cinco bloques de CHANGELOG |
| Coder como subagente del arquitecto | Un subagente no persiste entre tandas ni puede ser de otro proveedor, y el arquitecto acabaría viendo y corrigiendo código en su propio contexto |

## Consecuencias

Mejora:

- Ningún recuadro depende de que el PO lo copie bien. El contador ordena la conversación sin
  que nadie tenga que recordar cuál es el vigente.
- Quien comprueba el trabajo escribe el registro; quien lo hace, no.

Empeora o queda pendiente:

- El PO deja de ver cada recuadro. Lo compensan `AHORA.md`, los tramos y el resumen de tramo.
- Más trabajo documental para el arquitecto: cada bloque cierra con bitácora y CHANGELOG
  escritos por él, y su commit `docs:` llega en la ronda siguiente.
- Conviven dos numeraciones: el contador de mensajes (`#NNN`) y los nombres de bloque
  (letras). La bitácora cruza las dos.
- El 20 queda con partes superadas (§1, el formato de §2, §4, §7). Se marcan en el propio
  documento; hasta que E6 lo reescriba, quien lo lea tiene que saltarlas.
- Arquitecto y coder de Claude Code en esta máquina comparten la misma carpeta de memoria
  nativa: dos escritores sobre un mismo índice (`10-memory-contract.md`).

## Reversión

1. Volver al PO mensajero: quitar de `30-protocolo-coder.md` la sección «Canal directo». El
   formato de las instrucciones no cambia.
2. Devolver al coder las entradas T y el CHANGELOG: quitar esa fila de la tabla de roles de
   `30-protocolo-coder.md` y reabrir el 18 a entradas nuevas (su cabecera).
3. Quitar las notas de superación del 20 (§1, §2, §4, §7).
4. `.agents/estado/`, la bitácora y los ADR pueden quedarse: sirven igual a cualquier modelo.

Comprobar después que ninguna regla nombra el canal directo. Reversión completa y no
destructiva.

## Verificación

Los mensajes de la cadena abren con `[#NNN · ARQ|COD · …]`; `.agents/estado/AHORA.md` lleva el
último número; los commits de documentación de cada bloque son `docs:` y el coder no cambia
su texto.
