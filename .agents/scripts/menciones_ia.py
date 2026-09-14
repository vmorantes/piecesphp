#!/usr/bin/env python3
"""
Busca ATRIBUCIONES a una IA en lo que se entrega: archivos entregables
(src/, bin/, databases/, source-docs/, files/, README.md, CHANGELOG.md...)
y mensajes de commit. Regla: .agents/rules/40-salvaguardas.md §5, ADR 0003.
No persigue el vocabulario: el producto tiene funciones de IA.

Uso:
    python3 -B .agents/scripts/menciones_ia.py                    # árbol + todos los commits
    python3 -B .agents/scripts/menciones_ia.py --mensaje "texto"  # un mensaje suelto
    python3 -B .agents/scripts/menciones_ia.py --archivo RUTA     # mensaje de commit (hook commit-msg)
"""
import os
import subprocess
import sys

AQUI = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.join(AQUI, "guardas"))
from patrones import buscar, es_entregable  # noqa: E402

RAIZ = os.path.realpath(os.path.join(AQUI, "..", ".."))

# Commits anteriores a esta comprobación que casan con un patrón SIN atribuir
# nada. Se declaran uno a uno con su motivo: se declara, no se adivina.
HISTORICOS = {
    "8273fe58": "del PO, «fix(ia): Symlink de skill hacia claude skills»: nombra el directorio .claude/skills, no atribuye",
}


def git(*args):
    return subprocess.run(["git", "-C", RAIZ, *args], capture_output=True, text=True, check=True).stdout


def archivos_entregables():
    # Versionados y nuevos no ignorados: lo que un commit podría llevarse.
    lista = git("ls-files", "-z", "--cached", "--others", "--exclude-standard").split("\0")
    return sorted({r for r in lista if r and es_entregable(r) and os.path.isfile(os.path.join(RAIZ, r))})


def main():
    args = sys.argv[1:]
    problemas = []
    if args[:1] == ["--mensaje"]:
        problemas += [f"mensaje:{n}: {m!r}" for n, m in buscar(" ".join(args[1:]))]
    elif args[:1] == ["--archivo"] and len(args) > 1:
        # Mensaje de commit tal como lo entrega git al hook commit-msg: las
        # líneas que empiezan por '#' son comentarios de git y no se commitean.
        with open(args[1], encoding="utf-8") as f:
            texto = "\n".join(linea for linea in f.read().splitlines() if not linea.startswith("#"))
        problemas += [f"mensaje:{n}: {m!r}" for n, m in buscar(texto)]
    else:
        archivos = archivos_entregables()
        for ruta in archivos:
            try:
                with open(os.path.join(RAIZ, ruta), encoding="utf-8") as f:
                    texto = f.read()
            except (UnicodeDecodeError, OSError):
                continue
            problemas += [f"{ruta}:{n}: {m!r}" for n, m in buscar(texto)]
        declarados = 0
        commits = 0
        for bloque in git("log", "--format=%H%x00%B%x1e").split("\x1e"):
            if "\0" not in bloque:
                continue
            h, cuerpo = bloque.strip().split("\0", 1)
            commits += 1
            hallazgos = buscar(cuerpo)
            if hallazgos and any(h.startswith(k) for k in HISTORICOS):
                declarados += 1
                continue
            problemas += [f"commit {h[:8]}:{n}: {m!r}" for n, m in hallazgos]
        # El universo, no solo el resultado (LEY 15).
        print(f"universo: {len(archivos)} archivos entregables, {commits} commits; históricos declarados: {declarados}/{len(HISTORICOS)}")
    for p in problemas:
        print(p)
    print(f"atribuciones a IA en entregables: {len(problemas)}")
    return 1 if problemas else 0


if __name__ == "__main__":
    sys.exit(main())
