# Herencia: de la campaña de un arquitecto al modelo de tres roles

> **Borrable.** Se borra, con su entrada de bitácora, cuando se cumplan las tres condiciones:
>
> 1. ~~El reporte de BC está recibido, evaluado y con su entrada de bitácora.~~ **Cumplida el
>    2026-09-14**: bitácora 0002. La sección «El bloque en vuelo», abajo, ya no hace falta.
> 2. La tabla «Correcciones al registro» de `files/dev/PENDIENTES.md` no tiene filas
>    pendientes.
> 3. Las preguntas del traspaso están contestadas. P15, P17, P19 y P20 lo están desde el
>    2026-09-14. P18 (el commit del andamiaje) la contesta el PO justo antes de empezar a
>    trabajar.

Escrita el 2026-09-14 por el arquitecto entrante. Tenía delante el repositorio y la conversación
entera del arquitecto saliente: 13.407 eventos del 2026-08-19 al 2026-09-14, con 466 turnos del
PO. Esa conversación **no se versiona ni se conserva**. El PO decidió que el sucesor recibiera un
estado destilado, no los transcritos (2026-09-13), y que el archivo muriera tras su uso
(2026-09-14).

## Qué pasó antes

- **2026-08-19.** El PO pide conocer el proyecto y generar el contexto base. Así nace
  `.agents/context/`.
- **La campaña.**
  - Migración a PHP 8.5, terminada (v7.1.0).
  - Fases E1 a E3 cerradas, E4 abierta.
  - Bloques con letras, de A a BC. BC terminó en el commit `0c1af05a`, el 2026-09-14 a las 10:56.
- **Cómo se trabajaba.** El arquitecto trabajaba desde otra herramienta, con un puente de solo
  lectura a la máquina del PO, y el PO transportaba cada recuadro entre los dos chats.
- **Lo que queda escrito.**
  - 33 leyes (`context/19`).
  - El contrato (`context/20`).
  - 168 entradas T (`context/18`).
  - `files/dev/PENDIENTES.md` (LEY 33).
  - 16 documentos de roadmap posterior (`files/dev/roadmap/`).
  - Los instrumentos. Según el arquitecto saliente, el 2026-09-13: 26 comprobaciones en
    `verify-integrity`, 25 suites en `gates` y 10 censos. **Sin re-medir aquí.**
- **2026-09-13 y 14.** El PO decide jubilar al arquitecto y traspasar a uno nuevo con la skill
  `arquitecto-coder`. El arquitecto saliente propuso escribir un documento de aterrizaje y no
  llegó a hacerlo.

## Qué cambia

Está en los ADR 0001 a 0004. En una línea cada uno:

- **0001.** Canal directo entre sesiones y contador `#NNN`. El arquitecto escribe toda la
  documentación, CHANGELOG incluido.
- **0002.** El estado vivo pasa a `estado/AHORA.md` y el mapa a la MAJOR a `docs/roadmap.md`. El
  18 queda congelado en T168 y lo nuevo va a `docs/bitacora/`.
- **0003.** La guarda de hooks, adaptada. Nada de push, fetch ni etiquetas. Las credenciales de
  los remotos no se imprimen. Se persigue la **atribución** a IA, no el vocabulario: el producto
  tiene funciones de IA.
- **0004.** Ocho subagentes generados desde `personas/`.

Lo que **no** cambia: las leyes, el oficio de las instrucciones (20 §3), los puntos serios, los
cuatro números con `bin/guarda-add`, la LEY 33 y el criterio de alcance del PO para la MAJOR.

## Lo que el arquitecto saliente dijo que no se heredaba, y qué se hizo

El 2026-09-13 nombró tres cosas que solo tenía él:

1. **«La calibración del PO»**: cómo lee, qué no lee, cómo pregunta. El cruce la recuperó en buena
   parte, y está en `rules/30-protocolo-coder.md`, «El PO, en sus palabras».
2. **«El reflejo de sospechar del instrumento».** Las leyes 15, 16, 22 y 26 lo dicen; se aplica,
   no se hereda.
3. **«Qué párrafos del corpus envejecieron».** El cruce encontró los que se ven desde las palabras
   del PO: están en `PENDIENTES.md`, «Correcciones al registro». El resto lo buscará
   `context-curator` en cada cierre de tramo.

## El cruce

- **Método.** Los turnos del PO se sacaron del registro de eventos, se deduplicaron (466) y se
  partieron en seis tramos por fecha. Un subagente leyó entero cada tramo y cruzó cada encargo,
  decisión, restricción o preferencia contra el registro, con búsqueda literal y leyendo cada
  acierto.
- **Universo.** `context/` (con `historico/`), `PENDIENTES.md`, `files/dev/roadmap/`,
  `files/dev/tests.md` y `CHANGELOG.md`.
- **Límite.** «Ausente» significa «no aparece con términos distintivos y sus sinónimos». El
  arquitecto no re-verificó cada ítem uno a uno.

| Tramo | Fechas | Turnos | Ítems | Presentes | Parciales | Ausentes |
| --: | :-- | --: | --: | --: | --: | --: |
| 1 | 08-19 → 08-21 | 77 | 52 | 36 | 7 | 7 |
| 2 | 08-21 → 08-23 | 71 | 56 | 43 | 4 | 4 |
| 3 | 08-23 → 08-25 | 77 | 58 | 45 | 9 | 4 |
| 4 | 08-25 → 08-28 | 83 | 66 | 46 | 9 | 11 |
| 5 | 08-28 → 08-31 | 83 | 73 | 48 | 15 | 8 |
| 6 | 08-31 → 09-14 | 75 | 48 | 33 | 6 | 9 |
| **Total** | | **466** | **353** | **251** | **50** | **43** |

Faltan 9 para llegar a 353: se reemplazaron dentro del propio tramo o son preguntas sin
respuesta visible.

**Lo que enseña.**

- Las decisiones técnicas casi siempre llegaron al registro.
- Lo que se perdía eran las **preferencias de trabajo** del PO: en el tramo 4, 9 de las 11
  ausencias.
- También se perdían los **encargos a futuro sin bloque asignado**, que es justo lo que previó la
  LEY 33.

**A dónde fue el resultado.**

- `PENDIENTES.md`, sección «RECUPERADO EL 2026-09-14 (II)».
- `rules/30-protocolo-coder.md`, «El PO, en sus palabras».
- `docs/roadmap.md`.
- Tres correcciones hechas en el acto: `Importers` en el 14, la autoría de T60 en el 18 y la tabla
  de decisiones de `PENDIENTES.md`.

Los informes brutos del cruce no se versionan: mueren con la sesión, igual que el transcrito.

## El bloque en vuelo: BC

El coder terminó BC (commit `0c1af05a`), y su reporte va al arquitecto entrante (decisión del PO,
2026-09-14). La instrucción de BC solo existió en el chat del saliente, así que su contenido va
aquí para que el reporte se pueda evaluar:

1. **La línea base de PHPStan vuelve a ser una cifra.**
   - Antes había tres: la prosa decía 749, el campo 747 y la realidad era 744.
   - Ahora el número vive solo en el campo y la cabecera dice con qué se midió.
   - Se añade la **comprobación 26**, que falla si las fuentes no concuerdan o si no encuentra el
     archivo, con su provocación.
   - Se pedía el analizador 2.2.12.
2. **`asignaciones()` del censo de SQL deja de contar paréntesis dentro de literales.**
   - DECLARADO pasa de 10 a 8 y **no es una mejora**: era una cifra inflada.
   - Las dos entradas de `process()` pasan a REVISAR, con su razón escrita.
   - CONFIRMADO sigue en 0 y el trinquete no se toca.
3. **El CHANGELOG se pone al día** con las rupturas de AX, AY, BA, AZ y BB:
   - la guarda de AP retirada y `having_segment` con otra semántica;
   - `logMailer` fuera de la respuesta de `contact-forms-general`, que es una ruta pública;
   - el cambio de filas de `AllProfiles`;
   - `TasksManager`.
4. **`bin/censo-sql-interpolado` se declara y no se cablea.**
   - Resultado: A 223 · B 38 · C 6 · indecisas 16 · 126 fuera del método, el 57 %.
   - Las seis de C son una sola cadena en `processFromQuery()`, donde lo que aporta la petición es
     un identificador.
5. **Cierre.**
   - `bin/normaliza-eol` sobre `files/dev/PENDIENTES.md`.
   - 25 suites y 26 comprobaciones.
   - T168 en el 18.
   - Cuatro números: previsto 7 + 3 artefactos de PHPStan + los archivos del arquitecto.
   - Ni push ni etiqueta.

## Secretos vistos durante el traspaso

- **`context/18-siguientes-ventanas.md`, hacia la línea 4790.** Tiene en claro la contraseña de
  prueba del usuario root local y su hash. Está versionado y el repositorio es público. Espera al
  PO (P20). No se ha tocado.
- **El transcrito.** Contenía credenciales de prueba y menciones a tokens. No se copió ninguna y el
  archivo no está en el repositorio.
