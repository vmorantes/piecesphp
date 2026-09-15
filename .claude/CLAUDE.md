# Claude Code — instrucciones del proyecto

**PiecesPHP**: framework PHP modular propio sobre Slim 4, y una plantilla que se clona. Las
reglas del proyecto están en `AGENTS.md`, que el `CLAUDE.md` de la raíz importa (ADR 0014). Aquí
va solo lo que no se puede espejar: lo propio de Claude Code.

## Antes de tocar nada

1. `.agents/estado/AHORA.md` — qué pasa ahora, qué número de mensaje toca, qué espera al PO.
2. `.agents/README.md` — el mapa de todo lo demás y el orden de lectura.

Las reglas de `.agents/rules/` ya están cargadas (ver abajo). Las que no se negocian:
`00-core.md` y `40-salvaguardas.md`. El reparto de papeles: `30-protocolo-coder.md`.

## Lo imprescindible

Está en `AGENTS.md`, que ya viene cargado por la importación del `CLAUDE.md` de la raíz. No se
repite aquí: dos copias divergen.

---

## Cómo está montado

| Ruta | Qué es | Se edita |
| --- | --- | --- |
| `.claude/rules/*.md` | Symlinks a `.agents/rules/*.md` | En `.agents/rules/` |
| `.claude/skills/*` | Symlinks a `.agents/skills/*` | En `.agents/skills/` |
| `.claude/agents/*.md` | **Generados** desde `.agents/personas/` | Nunca a mano: `python3 -B .agents/scripts/generar_agentes.py` |
| `.claude/settings.json` | Guarda de hooks, denegaciones, atribución vacía | Aquí (ADR 0003) |
| `.claude/settings.local.json` | Ajustes personales de cada máquina | Ignorado por git |

Una regla nueva se crea en `.agents/rules/` y se enlaza con
`ln -s ../../.agents/rules/<archivo> .claude/rules/<archivo>`. Una skill, igual en
`.claude/skills/`. `verificar.sh` falla si falta un enlace.

Reglas con alcance por ruta: frontmatter `paths:` (lista de globs) para que solo carguen al
tocar esos archivos. No se mezclan en las reglas núcleo.

## Subagentes

Ocho, con modelo y esfuerzo por coste del error (ADR 0004). **Nunca `fable`**. Se disparan
solos según su `description`; los que deben usarse sin que se pidan dicen `PROACTIVELY`:

| Agente | Modelo / esfuerzo | Cuándo |
| --- | --- | --- |
| `explorer` | haiku / low | Antes de cambios de más de un archivo |
| `architect` | opus / high | Antes de un cambio estructural o de contrato |
| `code-reviewer` | opus / high | Tras modificar `src/` o `bin/`, antes de commitear |
| `security-auditor` | opus / xhigh | Si el cambio toca acceso, SQL, subidas, autenticación o rutas públicas |
| `debugger` | opus / high | Ante un fallo concreto |
| `test-writer` | sonnet / medium | Tras implementar una guarda o un arreglo sin prueba |
| `doc-writer` | sonnet / medium | Solo para el arquitecto |
| `context-curator` | sonnet / medium | Al cerrar un tramo |

## Guarda de hooks

`.agents/scripts/guardas/guardia.py` corre antes de cada `Bash`, `Write` y `Edit` y bloquea lo
que viola `40-salvaguardas.md`: remotos, etiquetas, credenciales de los remotos, git
destructivo, servidores, bases de datos, `sudo`, dependencias, escrituras fuera del repositorio
y atribución a IA en entregables. Sus pruebas:
`python3 -B .agents/scripts/guardas/probar_guardia.py`. Si bloquea algo legítimo, no se
esquiva: se dice, y el arquitecto ajusta la guarda con su prueba.

## Verificar el puente de reglas

```
git ls-files -s .claude/rules .claude/skills   # todas las líneas deben empezar por 120000
ls -la .claude/rules/ .claude/skills/          # SIN truncar con head
```

Un `100644` significa que el symlink se materializó como copia (clon con
`core.symlinks=false`) y las reglas divergirán en silencio.
