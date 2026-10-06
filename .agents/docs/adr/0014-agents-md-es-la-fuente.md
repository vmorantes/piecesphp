# 0014 — `AGENTS.md` es la fuente de las reglas; `CLAUDE.md`, un espejo

- **Estado:** Aceptada
- **Fecha:** 2026-09-15
- **Decide:** Product Owner (el principio), arquitecto (la forma)
- **Estructural:** sí (cambia dónde viven las reglas del proyecto)

## En cristiano

Las reglas del proyecto para cualquier agente, de cualquier proveedor, viven en `AGENTS.md` y en
`.agents/`, que es el estándar. `CLAUDE.md` es un nombre que solo busca Claude Code: ahora solo
importa `AGENTS.md`, sin contenido propio. Así hay una sola verdad, y otra herramienta no se
queda con la mitad de las reglas. Lo que solo tiene sentido en Claude Code (su configuración,
su guarda, sus subagentes) sigue en `.claude/CLAUDE.md`.

## Contexto

- **Antes de este ADR** (medido el 2026-09-15), la fuente era `CLAUDE.md`:
  - `AGENTS.md` decía «Las reglas del proyecto están en `CLAUDE.md`»;
  - las reglas agnósticas (idioma, rutas, assets, tablas, `vendor`, memoria) vivían en el
    `CLAUDE.md` de la raíz;
  - el «Lo imprescindible» de `.claude/CLAUDE.md` mezclaba cosas de Claude Code con cosas de
    cualquier agente (PHP 8.5, ugrep, `verificar.sh`).
- **El PO, el 2026-09-15**, formalizado:
  - el estándar es `AGENTS.md` y `.agents/`;
  - `CLAUDE.md` es propio de Anthropic, así que se espeja desde ahí;
  - solo lleva contenido directo lo que no se puede espejar.
- `.agents/rules/` y `.agents/skills/` ya se espejaban en `.claude/` con symlinks, y los
  subagentes ya se generaban desde `.agents/personas/` (ADR 0004). Solo faltaba la entrada.

## Decisión

- `AGENTS.md` contiene todas las reglas y avisos agnósticos del proyecto, con la numeración de
  las «reglas que no se negocian» que se citaba («regla 1», «regla 9»).
- `CLAUDE.md` de la raíz solo importa `AGENTS.md` (`@AGENTS.md`), más una línea que dice que no
  se edita.
- `.claude/CLAUDE.md` se queda solo con lo propio de Claude Code.
- `verificar.sh` falla si `CLAUDE.md` deja de ser un espejo.

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| Symlink `CLAUDE.md` → `AGENTS.md` | Un clon con `core.symlinks=false` lo materializa como copia, que diverge en silencio (ya pasó con las reglas, ver `.claude/CLAUDE.md`) |
| Dejar la fuente en `CLAUDE.md` | Es el nombre de un proveedor. Otra herramienta que lea solo `AGENTS.md` se quedaba sin las reglas |
| Duplicar el contenido en los dos | Dos verdades sin puerta entre ellas |

## Consecuencias

- **Lo bueno:** una sola fuente, legible por cualquier herramienta. Claude Code la carga por la
  importación.
- **Lo malo:** depende de que Claude Code resuelva `@AGENTS.md` en el `CLAUDE.md` de la raíz.
  **SIN VERIFICAR al escribir este ADR:** se comprueba en la primera sesión nueva, viendo las
  instrucciones cargadas. Si no lo resuelve, se pasa al symlink y se revisa este ADR.
- Las referencias a «el `CLAUDE.md` de la raíz» en las reglas, en las personas y en
  `.agents/README.md` pasan a `AGENTS.md`. Los ADR anteriores no se tocan.

## Reversión

1. Devolver al `CLAUDE.md` de la raíz el bloque de reglas que se movió a `AGENTS.md`, desde la
   historia: `git log --oneline -- CLAUDE.md AGENTS.md`.
2. Dejar `AGENTS.md` como entrada que remite a `CLAUDE.md`.
3. Quitar la comprobación del espejo de `verificar.sh`.
4. Devolver las referencias y regenerar los subagentes.

Completa y sin pérdida: solo mueve texto.

## Verificación

- `verificar.sh` → «CLAUDE.md espeja AGENTS.md».
- En una sesión nueva de Claude Code, las instrucciones cargadas incluyen el contenido de
  `AGENTS.md`.
