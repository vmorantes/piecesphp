# Tramo 2026-09-14 11:05 — Traspaso y andamiaje

- **Inicio:** 2026-09-14 11:05
- **Fin:** abierto. Espera el reporte de BC y las respuestas P15 y P17–P20.
- **Mensajes:** desde `#001`.
- **Mandato del PO.** Montar el modelo arquitecto-coder con la skill que creó, y heredar al
  arquitecto anterior usando su conversación entera (el archivo «muere tras tu uso»). Tomar lo
  mejor de lo copiado de otro proyecto y adaptarlo, decidir si hace falta un coder nuevo, sin
  commits en el arranque. Informar de lo hecho y de cómo se trabajará. En mitad del turno añadió:
  borrar lo que no aplique, recibir el reporte del coder saliente, dejar claro lo pedido, lo
  hecho y lo que queda, y proponer un orden de directorios («es solo un comentario»).

## Rondas

| # | Qué | Resultado | Commits |
| --- | --- | --- | --- |
| #001→#002 | Saludo, identificación del coder y entrega del reporte de BC | completado. El coder leyó las reglas del disco, declaró seis contradicciones con el modelo nuevo y entregó BC con salidas reales. Evaluado en la bitácora 0002 | `0c1af05a` (BC, anterior al contador) |

| #003→#004 | Saludo al coder nuevo (`PiecesPHPUpgrade-Coder-Main`) | Completado: identificado, reglas leídas del disco. Seis dudas; dos eran defectos de la regla 30, ya corregidos: quién autoriza los commits y el «ninguna etiqueta» sin matiz | — |
| #005 | Respuestas a sus dudas | Enviado; no es una instrucción de trabajo | — |

**Añadido después del primer informe, a raíz de mensajes del PO:**

- **Los cuatro paquetes hermanos**: sección nueva en la regla 30 sobre cómo se trabaja en ellos,
  desde aquí.
- **Política de ramas del PO**: nada se crea sin su permiso. Va a las reglas 30 y 40, al ADR 0003
  y a la guarda, que bloquea crear ramas y queda con 143 casos. Corregido también
  `01-overview.md`.
- **Respuestas del PO a P15, P17, P19 y P20**:
  - P17: el PO abrió la sesión `PiecesPHPUpgrade-Coder-Main`.
  - P19: en los paquetes se etiqueta, todos llevan `dev` y la guarda deja crearla ahí.
  - P20: contraseña retirada del 18 por higiene.
  - P15: `vps/index.md` adoptado como documentación del arquitecto, corregida su remisión a una
    sección que no existe.
- **Aceptada la propuesta de directorios**, con eliminar los builds innecesarios: lote 0b del
  mapa.
- **`Importers` no se perdió.** Queda registrada la contradicción de dirección entre el 18 y el
  PO.
- **P18** la contesta el PO al empezar. **No se trabaja sobre el framework hasta cerrar el
  acuerdo.**

## Hecho (sin commits, por orden del PO)

**Reconocimiento**
- Estructura, `git log`, `git status`, reglas y documentación.
- La conversación del arquitecto saliente: 13.407 eventos y 466 turnos del PO.

**El cruce**
- Seis subagentes leyeron los 466 turnos por tramos y los cruzaron contra el registro.
- Resultado en `.agents/HERENCIA.md`, en `files/dev/PENDIENTES.md` y en la regla 30.

**Andamiaje adaptado a PiecesPHP**
- Reglas 10, 30, 40, 50 y 60.
- Personas y los 8 subagentes generados.
- Guarda de hooks con 134 casos y verificador del andamiaje.
- `menciones_ia.py`, que ahora persigue la atribución.
- `.claude/settings.json`, `.claude/CLAUDE.md`, `AGENTS.md` y `.agents/README.md`.
- Una línea en `CLAUDE.md`, `.gitattributes` y `.gitignore`.

**Documentación nueva**
- ADR 0001–0004 con su índice.
- `.agents/estado/`, la bitácora y su entrada 0001.
- `.agents/docs/roadmap.md`: el mapa a la MAJOR que solo existía en el chat.
- `.agents/HERENCIA.md`.

**Correcciones al registro**
- Notas de superación en el 18 y el 20, y la puerta «mantenerlo» de `context/README.md`.
- `Importers` en el 14.
- La autoría de T60 en el 18.
- La tabla de decisiones de `PENDIENTES.md`.

**Retirado**
- Al scratchpad: el subagente `hestia-verifier` y el JSON de la conversación.
- Borrados, con permiso del PO: `generate_agents.py`, el `.claude/.gitignore` duplicado, tres
  carpetas de skills vacías y el `__pycache__` de la guarda.
- `.vscode/`, devuelto a HEAD.

**Verificación**
- `bash .agents/scripts/verificar.sh` da `ANDAMIAJE OK`: guarda 134/134, 8 agentes al día,
  0 atribuciones en 1.470 archivos entregables y 1.241 commits.

## Encontrado y decidido

**Lo copiado describía otro proyecto, CustomPluginsHestiaCP**
- Permitía `git push` por una excepción de aquel repositorio.
- Bloqueaba el vocabulario de IA: aquí eso da 120 falsos positivos, porque el producto tiene
  funciones de IA.
- Contaba con HestiaCP. Se adaptó todo (ADR 0003).

**Dónde vive cada cosa**
- El mapa a la MAJOR del 2026-09-13 no estaba en ningún archivo. Ahora está en
  `docs/roadmap.md` (ADR 0002).
- El estado va a `.agents/estado/` y no a la raíz: la plantilla se clona (ADR 0002).
- El coder escribía documentación (entradas T, CHANGELOG). Desde hoy la escribe el arquitecto
  (ADR 0001).

**Decisiones del PO recuperadas que cambian reglas**
- La instrumentación de análisis la decide el arquitecto (2026-09-02). BD deja de esperar al PO.
- Los paquetes se versionan con soltura (2026-08-27). Choca con los puntos serios: queda como
  P19.

**Registro y entorno**
- El registro tiene contradicciones y mentiras: la tabla está en `PENDIENTES.md`. Las tres
  peligrosas se corrigieron en el acto.
- Un secreto versionado en el 18 (P20).
- `php` a secas es 8.1.34; `bin/cli` elige 8.5.

## Falló por el camino

- **Una orden combinada, denegada.** Restaurar `.vscode`, mover el JSON y el agente, y borrar, en
  una sola orden: el clasificador de la herramienta la denegó y no se ejecutó nada. Se hizo por
  partes, y los borrados después del permiso explícito del PO.
- **La máscara no tapó el hash.** Al comprobar el secreto del 18, el hash quedó en la salida de
  la herramienta. No se copió a ningún archivo ni mensaje.
- **Otro actor tocó el árbol durante la lectura.** A las 11:07 alguien vació tres skills copiadas
  y borró la `HERENCIA.md` del otro proyecto. No lo ordenó el arquitecto. Se trató como decisión
  del PO.
- **Colisión en el cruce.** Dos subagentes compartieron un guion del scratchpad y uno lo
  sobrescribió. El afectado usó una copia propia y lo declaró en su informe.

## Propuesta: orden de directorios

El PO lo pidió como comentario («siento que ese `files/*` y demás se está enredando»). Es solo
una propuesta: no se ejecuta sin que la nombre, y sería estructural, con su ADR.

**Qué hay hoy**, medido el 2026-09-14 con `du -sh` y `git ls-files`:

| Carpeta | Tamaño | Archivos versionados | Qué mezcla |
| --- | --: | --: | --- |
| `files/dev/` | 20 MB | 32 | Datos de instrumentos (líneas base, inventarios, firmas, `snapshots/`) **y** documentos para el PO y el mantenedor (`PENDIENTES.md`, `tests.md`, `roadmap/`) |
| `files/API/` | 7,7 MB | 60 | Documentación de la API para humanos (`docs/`), **su build de mkdocs versionado** (`docs-dist/`) y la colección de Postman |
| `files/Webflow/`, `files/CliScripts/` | 60 KB | 12 | Guías y guiones para quien clona |
| `source-docs/` | 328 KB | 41 | La otra documentación para humanos, también mkdocs |
| Raíz | — | — | Seis `PHPStanResult.*` (tres versionados), `permissions-and-property.sh`, `IGNORE.md`, `TODO.md`, `skills-lock.json` y dos lockfiles de Node |

**El criterio que propongo**: una sola razón de ser por carpeta de primer nivel.

| Carpeta | Solo contiene |
| --- | --- |
| `src/` | El producto |
| `bin/` | Los instrumentos |
| `databases/` | El esquema |
| `source-docs/` | Documentación para humanos, **incluida la de la API** (`files/API/docs/` → `source-docs/api/`) |
| `files/dev/` | Solo datos que escriben y leen máquinas: líneas base, inventarios, fotos. Y ahí también los `PHPStanResult.*` de la raíz |
| `.agents/` | Todo lo de agentes y lo que el PO sigue: `PENDIENTES.md` y el roadmap posterior pasan a `.agents/docs/` |
| Raíz | Solo archivos de entrada: `README.md`, `CHANGELOG.md`, `LICENSE`, `CLAUDE.md`, `AGENTS.md`, configuración |

**Lo que cuesta, y por qué no es hoy.**
- Las rutas están escritas dentro de los instrumentos: las comprobaciones de
  `verify-integrity`, los censos, `bin/phpstan` y la comprobación 26 leen
  `PHPStanResult.Summary.baseline.txt`.
- También las nombran cientos de referencias del registro y la memoria de los agentes.
- Mover es un borrado más una creación: aplica la LEY 28, y el alcance se mide con un censo
  (LEY 17).
- `docs-dist/` es una pregunta abierta del PO desde el 2026-08-31 (20 §7): ¿se versiona el build
  o se genera?

**Encaja en E6**, junto a la documentación, o después de la MAJOR. Para quien clona no rompe
nada, salvo la documentación de la API.

## Espera al PO

P15, P17, P18, P19 y P20: ver `../AHORA.md`.

## Resumen

Se montó el modelo de tres roles adaptado a PiecesPHP, sin commits. Se destiló la herencia del
arquitecto anterior: sus 466 turnos se cruzaron contra el registro, y lo que faltaba (sobre todo
preferencias de trabajo del PO y encargos a futuro) quedó escrito. El mapa hasta la MAJOR existe
por primera vez en un archivo. Queda recibir el reporte de BC y que el PO decida sobre el coder
nuevo, el commit, las etiquetas de los paquetes y el secreto.
