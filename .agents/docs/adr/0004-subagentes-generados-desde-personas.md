# 0004 — Subagentes generados desde personas, con modelo por coste del error

- **Estado:** Aceptada
- **Fecha:** 2026-09-14
- **Decide:** Arquitecto
- **Estructural:** sí (cómo se trabaja)

## En cristiano

Cada subagente se describe una sola vez, en `.agents/personas/`, y un guion genera la versión
de cada herramienta (Claude Code y Antigravity). Si alguien edita un generado a mano, la
verificación lo detecta. Cada agente usa el modelo que justifica el daño que haría si se
equivoca: el más capaz para decidir y revisar, uno intermedio para pruebas y documentación, el
más ligero para buscar.

## Contexto

- Hasta hoy el repositorio tenía `.agents/scripts/generate_agents.py` (commit del
  2026-08-29): seis agentes, sin reglas comunes, sin modo de comprobación, sin esfuerzo por
  agente.
- El proyecto de origen trajo `generar_agentes.py`: modo `--check`, reglas comunes
  (`_comun.md`) anexadas a todos, esfuerzo por agente y nueve agentes, uno de ellos
  (`hestia-verifier`) exclusivo de HestiaCP.
- En el proyecto de origen el PO vetó el modelo `fable` para los agentes.

## Decisión

Un solo generador (`generar_agentes.py`, con `--check` dentro de `verificar.sh`) y ocho agentes:
`architect`, `code-reviewer`, `security-auditor` y `debugger` en opus; `test-writer`,
`doc-writer` y `context-curator` en sonnet; `explorer` en haiku. `hestia-verifier` sale.
`fable`, nunca, mientras el PO no diga lo contrario para este repositorio.

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| Conservar `generate_agents.py` | Sin `--check` un generado editado a mano diverge en silencio; sin reglas comunes cada agente repite (o no) las salvaguardas |
| Agentes escritos a mano por herramienta | Dos copias de cada perfil que acaban divergiendo |
| Todos en el modelo más capaz | Coste sin beneficio en búsquedas y tareas acotadas |

## Consecuencias

- Hay que acordarse de regenerar tras editar una persona. `verificar.sh` falla si no se hizo.
- El campo `effort` en el frontmatter de un subagente se verificó en el proyecto de origen
  (Claude Code 2.1.238). **Aquí no se ha vuelto a verificar.**
- `generate_agents.py` se retira: el coder lo borra con `git rm` en el commit del andamiaje.
- `doc-writer` es un instrumento del arquitecto. El coder no escribe documentación (ADR 0001),
  así que no lo usa.

## Reversión

1. Restaurar `generate_agents.py` desde la historia (`git log --oneline -- .agents/scripts/generate_agents.py`)
   y ejecutarlo.
2. Quitar `generar_agentes.py` y el paso «Subagentes generados al día» de `verificar.sh`.

Reversión completa.

## Verificación

`python3 -B .agents/scripts/generar_agentes.py --check` imprime `agentes al día: 8`.
