#!/bin/bash
# Verificación del ANDAMIAJE DE AGENTES. La corre el coder antes de cada reporte.
#
# No verifica el producto: eso lo hacen bin/phpstan, bin/cli verify-integrity y
# bin/cli gates, que nombra cada instrucción (.agents/rules/30-protocolo-coder.md).
# No escribe en el repositorio ni fuera de él. Sale con 1 si algo falla.
#
# Uso: bash .agents/scripts/verificar.sh

set -u
RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$RAIZ" || exit 1
export PYTHONDONTWRITEBYTECODE=1

fallos=0
paso() { printf '\n== %s\n' "$1"; }
fallo() { printf 'FALLO: %s\n' "$1"; fallos=$((fallos + 1)); }

paso "Sintaxis bash de los guiones de agentes (bash -n)"
n=0
for f in .agents/scripts/*.sh .agents/scripts/git-hooks/*; do
    [ -f "$f" ] || continue
    n=$((n + 1))
    bash -n "$f" || fallo "bash -n $f"
done
echo "guiones bash revisados: $n"

paso "Sintaxis Python de los guiones de agentes"
n=0
for f in .agents/scripts/*.py .agents/scripts/guardas/*.py; do
    [ -f "$f" ] || continue
    n=$((n + 1))
    python3 -c 'import ast,sys; ast.parse(open(sys.argv[1], encoding="utf-8").read(), sys.argv[1])' "$f" || fallo "python $f"
done
echo "guiones Python revisados: $n"

paso "Finales de línea del hook de git (LF: con CRLF el shebang no arranca)"
for f in .agents/scripts/git-hooks/*; do
    [ -f "$f" ] || continue
    [ "$(tr -d '\r' < "$f" | wc -c)" = "$(wc -c < "$f")" ] || fallo "$f tiene CRLF"
done
echo "hooks revisados: $(ls -1 .agents/scripts/git-hooks | wc -l)"

paso "Hook de git activo y ejecutable (40-salvaguardas.md §5; A-011)"
# core.hooksPath vive en .git/config, que no se versiona: cada clon y cada máquina lo activan.
ruta_hooks="$(git config --get core.hooksPath || true)"
if [ "$ruta_hooks" != ".agents/scripts/git-hooks" ]; then
    fallo "core.hooksPath vale '${ruta_hooks:-sin definir}'. En este clon: git config core.hooksPath .agents/scripts/git-hooks"
fi
for f in .agents/scripts/git-hooks/*; do
    [ -f "$f" ] || continue
    [ -x "$f" ] || fallo "$f no es ejecutable: git lo ignora en silencio"
    [ "$(git ls-files -s -- "$f" | cut -c1-6)" = "100755" ] || fallo "$f no está versionado como 100755"
done
echo "core.hooksPath: ${ruta_hooks:-sin definir}"

paso "CLAUDE.md espeja AGENTS.md (ADR 0014)"
# La fuente es AGENTS.md: CLAUDE.md solo la importa. Si crece, vuelve a haber dos verdades.
if ! grep -qx '@AGENTS.md' CLAUDE.md; then
    fallo "CLAUDE.md no importa AGENTS.md (falta la línea @AGENTS.md)"
fi
lineas_claude="$(grep -c -v '^[[:space:]]*$' CLAUDE.md)"
[ "$lineas_claude" -le 4 ] || fallo "CLAUDE.md tiene $lineas_claude líneas con texto: lo normativo va en AGENTS.md"
echo "CLAUDE.md: $lineas_claude líneas con texto"

paso "Subagentes generados al día"
python3 -B .agents/scripts/generar_agentes.py --check || fallo "agentes desfasados"

paso "Puente de reglas y skills (.claude -> .agents)"
for enlace in .claude/rules/* .claude/skills/*; do
    if [ ! -L "$enlace" ]; then
        fallo "$enlace no es un symlink (se materializó como copia)"
    elif [ ! -e "$enlace" ]; then
        fallo "$enlace apunta a algo que no existe"
    fi
done
for regla in .agents/rules/*.md; do
    [ -L ".claude/rules/$(basename "$regla")" ] || fallo "falta el symlink .claude/rules/$(basename "$regla")"
done
# Una skill es una carpeta con SKILL.md; una carpeta vacía no es una skill.
for skill in .agents/skills/*/; do
    [ -f "${skill}SKILL.md" ] || continue
    [ -L ".claude/skills/$(basename "$skill")" ] || fallo "falta el symlink .claude/skills/$(basename "$skill")"
done
modos=$(git ls-files -s .claude/rules .claude/skills | awk '$1 != "120000" {print $4}')
[ -z "$modos" ] || fallo "en el índice sin modo 120000: $modos"
echo "enlaces revisados: $(ls -1 .claude/rules .claude/skills | grep -vc ':$')"

paso "Guarda de hooks"
python3 -B .agents/scripts/guardas/probar_guardia.py || fallo "la guarda no se comporta como se espera"

paso "Capas del andamiaje (A, B, C)"
python3 -B .agents/scripts/capas.py || fallo "un archivo del andamiaje sin capa, en dos capas, o un patrón muerto en .agents/capas.json"

paso "Atribución a IA en entregables y commits"
python3 -B .agents/scripts/menciones_ia.py || fallo "hay atribuciones a IA"

printf '\n'
if [ "$fallos" -eq 0 ]; then
    echo "ANDAMIAJE OK"
else
    echo "ANDAMIAJE CON FALLOS: $fallos"
    exit 1
fi
