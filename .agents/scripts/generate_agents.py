#!/usr/bin/env python3
"""
Genera los subagentes finales de Claude Code y Antigravity a partir de
las personas compartidas en .agents/personas/.

Corré esto cada vez que edites una persona o este config — nunca edites
a mano los archivos generados en .claude/agents/ ni en .agents/agents/,
se van a sobrescribir.

Uso: python3 .agents/scripts/generate_agents.py
"""
import pathlib

ROOT = pathlib.Path(__file__).resolve().parents[2]
PERSONAS = ROOT / ".agents" / "personas"
CLAUDE_OUT = ROOT / ".claude" / "agents"
ANTIGRAVITY_OUT = ROOT / ".agents" / "agents"

# Config por subagente. "description" es lo que cada planificador usa
# para decidir cuándo delegar — mismo texto en ambas herramientas.
AGENTS = {
    "explorer": {
        "description": "Búsqueda y navegación de código, exploración estructural. Use PROACTIVELY antes de cambios grandes para ubicar el código relevante. Solo lectura.",
        "claude": {"tools": ["Read", "Grep", "Glob"], "model": "haiku"},
        "antigravity": {"tools": ["view_file", "grep_search"], "model": "flash"},
    },
    "doc-writer": {
        "description": "Escribe y actualiza documentación, comentarios y changelog. Use PROACTIVELY después de cambios que dejan la documentación desactualizada.",
        "claude": {"tools": ["Read", "Grep", "Glob", "Write", "Edit"], "model": "haiku"},
        "antigravity": {"tools": ["view_file", "grep_search", "replace_file_content"], "model": "flash"},
    },
    "code-reviewer": {
        "description": "Revisa diffs en busca de bugs, seguridad y estilo. Use PROACTIVELY después de escribir o modificar código, antes de considerar la tarea terminada.",
        "claude": {"tools": ["Read", "Grep", "Glob", "Bash"], "model": "sonnet"},
        "antigravity": {"tools": ["view_file", "grep_search", "run_command"], "model": "flash"},
    },
    "test-writer": {
        "description": "Escribe tests para código ya implementado y los corre. Use PROACTIVELY después de implementar una feature o fix sin tests.",
        "claude": {"tools": ["Read", "Grep", "Glob", "Write", "Edit", "Bash"], "model": "sonnet"},
        "antigravity": {"tools": ["view_file", "grep_search", "replace_file_content", "run_command"], "model": "flash"},
    },
    "debugger": {
        "description": "Investiga un bug puntual: reproduce y encuentra la causa raíz. Use cuando hay un fallo concreto que investigar, no para exploración general.",
        "claude": {"tools": ["Read", "Grep", "Glob", "Bash"], "model": "sonnet"},
        "antigravity": {"tools": ["view_file", "grep_search", "run_command"], "model": "flash"},
    },
    "architect": {
        "description": "Decisiones de diseño y arquitectura para cambios sustanciales (fases propose/design de SDD). Use para cambios grandes antes de escribir cualquier código.",
        "claude": {"tools": ["Read", "Grep", "Glob"], "model": "opus"},
        "antigravity": {"tools": ["view_file", "grep_search"], "model": "pro"},
    },
}


def claude_frontmatter(name: str, cfg: dict) -> str:
    tools = ", ".join(cfg["claude"]["tools"])
    return (
        "---\n"
        f"name: {name}\n"
        f"description: {cfg['description']}\n"
        f"tools: {tools}\n"
        f"model: {cfg['claude']['model']}\n"
        "---\n\n"
    )


def antigravity_frontmatter(name: str, cfg: dict) -> str:
    tools_yaml = "\n".join(f"  - {t}" for t in cfg["antigravity"]["tools"])
    return (
        "---\n"
        f"name: {name}\n"
        f"description: {cfg['description']}\n"
        f"tools:\n{tools_yaml}\n"
        "subagent: true\n"
        "mainAgent: false\n"
        f"model: {cfg['antigravity']['model']}\n"
        "commandExecutionPolicy: sandbox\n"
        "---\n\n"
    )


def main():
    CLAUDE_OUT.mkdir(parents=True, exist_ok=True)
    ANTIGRAVITY_OUT.mkdir(parents=True, exist_ok=True)

    for name, cfg in AGENTS.items():
        persona_path = PERSONAS / f"{name}.md"
        body = persona_path.read_text(encoding="utf-8")

        (CLAUDE_OUT / f"{name}.md").write_text(
            claude_frontmatter(name, cfg) + body, encoding="utf-8"
        )
        (ANTIGRAVITY_OUT / f"{name}.md").write_text(
            antigravity_frontmatter(name, cfg) + body, encoding="utf-8"
        )
        print(f"generado: {name}")


if __name__ == "__main__":
    main()
