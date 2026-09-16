# .agents

Todo lo que un agente necesita para trabajar en este repositorio sin depender de ninguna sesión
anterior. Compartido por todas las herramientas; Claude Code lo ve además a través de `.claude/`
(symlinks y generados).

| Ruta | Contenido |
| :-- | :-- |
| `estado/` | **Qué pasa ahora**: `AHORA.md` y los tramos. Para el PO y para la siguiente sesión |
| `rules/` | Reglas de comportamiento. Todas se aplican siempre |
| `context/` | Qué hay y qué muerde. Denso, verdad **hoy**. Tiene dos puertas: usar el framework o mantenerlo |
| `docs/adr/` | Por qué se decidió así. Inmutables. **Lo más importante para un agente** |
| `docs/bitacora/` | Cómo se llegó hasta ahí, una entrada por tarea cerrada |
| `docs/roadmap.md` | El mapa de lo que falta hasta la MAJOR, en orden |
| `docs/pendientes.md` | Encargos y decisiones del PO que aún no son trabajo (LEY 33) |
| `docs/roadmap-posterior/` | Lo que va después de la MAJOR: 16 documentos |
| `HERENCIA.md` | **Borrable.** El traspaso de la campaña anterior al modelo de tres roles |
| `personas/` | **Fuente** de los subagentes: cuerpo del prompt, sin frontmatter |
| `agents/` | Subagentes para Antigravity, **generados** (no editar) |
| `skills/` | Skills compartidas; `.claude/skills/` enlaza aquí |
| `scripts/` | Generador de agentes, verificación del andamiaje, guarda de hooks, hook de git |

Fuera de aquí: `AGENTS.md` (entrada genérica y **fuente de las reglas del proyecto**, ADR 0014),
`CLAUDE.md` (espejo: solo importa `AGENTS.md`) y `.claude/CLAUDE.md` (lo propio de Claude Code).

## Orden de lectura para una sesión nueva

1. `estado/AHORA.md` y el último archivo de `estado/tramos/` — dónde estamos.
2. `rules/` — lo que no se hace nunca y cómo se colabora.
3. `HERENCIA.md` — mientras exista: qué cambió de modelo, qué exige el PO y qué quedó abierto.
4. `context/README.md` — y por la puerta que te toque:
   - **usar el framework** (un módulo, rutas, mappers, vistas): `context/01`–`15`;
   - **mantenerlo** (la campaña hacia la MAJOR): `context/20` (cómo se trabaja, §3 y §5),
     `context/19` (las leyes) y, cuando haga falta el detalle de una tarea, `context/18`.
5. `docs/adr/README.md` — el índice; los ADR que toque tu tarea, enteros.
6. `docs/roadmap.md` y `docs/pendientes.md` — si toca elegir, proponer o instruir.

## Documentación para agentes y para personas

| Para | Dónde | Qué |
| :-- | :-- | :-- |
| Quien usa el framework | `README.md`, `source-docs/` (la API en `source-docs/api/`), `context/01`–`16` y `21` | Qué es, cómo se instala y se usa |
| Quien clona y actualiza | `CHANGELOG.md` | Qué cambió para él, rupturas incluidas |
| Quien lo mantiene | `context/18`, `19`, `20`, `historico/` | La campaña, sus leyes y su contrato |
| El PO | `estado/`, `docs/pendientes.md` | Qué se hizo y qué espera de él |
| Agentes | `docs/adr/`, `docs/bitacora/`, `docs/roadmap.md` | Por qué, cómo se llegó, qué falta |

Una sola regla los mantiene sanos: **ninguno puede mentir.** Si un cambio de código invalida un
documento, se corrige en el mismo commit. Si dos se contradicen, gana el código y se arreglan
los dos. Y lo que ya no sirve se poda (`rules/60-estado.md`).

### Las tres capas: qué viaja a un clon

Todo archivo versionado de `.agents/`, `.claude/`, `AGENTS.md` y `CLAUDE.md` pertenece a **una** capa, declarada en
[`capas.json`](./capas.json) (PO, A-015 a A-017):

- **A, la metodología**: reglas, personas, guiones, las skills de trabajo, `.claude/` y los ADR de cómo se trabaja.
- **B, desarrollar sobre PiecesPHP**: `AGENTS.md`, `context/01`–`16` y `21`, la skill de PHP y los ADR que
  condicionan el código (0006, 0009, 0012, 0018).
- **C, mantener el framework**: la campaña (estado, tramos, bitácora, mapa, `pendientes`, `18`–`20`, `historico/`,
  `HERENCIA.md` y los ADR de campaña).

Un clon recibe A y B con el estado vacío; C se queda aquí. **Un archivo nuevo se clasifica en el mismo commit**:
`verificar.sh` corre `scripts/capas.py`, que falla si un archivo no tiene capa, si tiene dos o si un patrón no casa
con nada. **Límite conocido:** un documento de A o B puede enlazar a uno de C (el índice de ADR, por ejemplo); en un
clon ese enlace queda roto. La orden de clonado, después de la MAJOR, lo resolverá.

## Cambiar un agente, una regla o una skill

- **Agente**: edita `personas/<nombre>.md` (o `AGENTES` en `scripts/generar_agentes.py` para
  modelo, esfuerzo, herramientas y descripción) y regenera con
  `python3 -B .agents/scripts/generar_agentes.py`. `verificar.sh` falla si quedó desfasado.
- **Regla**: archivo en `rules/` + symlink en `.claude/rules/`.
- **Skill**: carpeta con `SKILL.md` en `skills/` + symlink en `.claude/skills/`.
- Si cambia cómo se trabaja, es estructural: ADR.
