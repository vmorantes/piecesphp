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
# La marca que deja bin/make-distribution: en un clon, lo que solo existe en el repositorio de desarrollo no aplica.
en_clon=no
[ -f .piecesphp-distribution ] && en_clon=si

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
if [ "$ruta_hooks" != ".agents/scripts/git-hooks" ] && [ "$en_clon" = si ]; then
    echo "AVISO: core.hooksPath vale '${ruta_hooks:-sin definir}'. Para activar el control de los commits: git config core.hooksPath .agents/scripts/git-hooks"
elif [ "$ruta_hooks" != ".agents/scripts/git-hooks" ]; then
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
# Se cuentan aquí, en el bucle que los revisa: con `ls -1` sobre dos carpetas, la línea en
# blanco entre los dos listados entraba en la cuenta y daba uno de más.
enlaces=0
for enlace in .claude/rules/* .claude/skills/*; do
    enlaces=$((enlaces + 1))
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
echo "enlaces revisados: $enlaces"

paso "Guarda de hooks"
python3 -B .agents/scripts/guardas/proyecto.py || fallo "el archivo de proyecto no vale"
python3 -B .agents/scripts/guardas/probar_proyecto.py >/dev/null 2>&1 || fallo "el validador del archivo de proyecto no se comporta como se espera"
python3 -B .agents/scripts/guardas/probar_guardia.py || fallo "la guarda no se comporta como se espera"

paso "Capas del andamiaje (A, B, C)"
python3 -B .agents/scripts/capas.py || fallo "un archivo del andamiaje sin capa, en dos capas, o un patrón muerto en .agents/capas.json"

paso "Higiene de estado/PO.md"
if [ "$en_clon" = si ]; then
    echo "[NO APLICA EN LA DISTRIBUCIÓN] higiene de estado/PO.md: el estado viaja vacío, sin PO.md (ADR 0049 §3)"
else
    python3 -B .agents/scripts/probar_higiene_po.py || fallo "higiene_po.py no caza lo que debe cazar"
    python3 -B .agents/scripts/higiene_po.py || fallo "PO.md guarda algo cerrado o un aviso ya leído"
fi

paso "Fundamento de las instrucciones (su prueba)"
if [ "$en_clon" = si ]; then
    echo "[NO APLICA EN LA DISTRIBUCIÓN] la prueba de fundamento.py solo se corre en el repositorio de desarrollo"
else
    python3 -B .agents/scripts/probar_fundamento.py || fallo "fundamento.py no caza lo que debe cazar"
fi

paso "Destinos de las decisiones del PO (E1)"
if [ "$en_clon" = si ]; then
    echo "[NO APLICA EN LA DISTRIBUCIÓN] destinos: abierto.md y decisiones-del-po.md son de la capa C, no viajan"
else
    python3 -B .agents/scripts/probar_destinos.py || fallo "destinos.py no caza lo que debe cazar"
    python3 -B .agents/scripts/destinos.py || fallo "una decisión del PO sin destino válido"
fi

paso "Contadores y tope de AHORA.md (E5)"
if [ "$en_clon" = si ]; then
    echo "[NO APLICA EN LA DISTRIBUCIÓN] cadena: el estado es de la capa C y no viaja"
else
    python3 -B .agents/scripts/probar_cadena.py || fallo "cadena.py no caza lo que debe cazar"
    python3 -B .agents/scripts/cadena.py || fallo "los contadores o el tope de AHORA.md no cuadran"
fi

paso "Cada archivo del andamiaje, en su índice (E11)"
if [ "$en_clon" = si ]; then
    echo "[NO APLICA EN LA DISTRIBUCIÓN] índices: las carpetas de la capa C no viajan"
else
    # Un hijo DIRECTO versionado de cada carpeta (archivo o carpeta, sin el README) tiene que salir nombrado en su
    # README.md: en un enlace o entre comillas invertidas, por su nombre, en una ruta o con un patrón que lo case.
    python3 -B - <<'PY' || fallo "un archivo del andamiaje no sale en su índice"
import fnmatch, re, subprocess, sys

CARPETAS = ['.agents', '.agents/docs', '.agents/estado', '.agents/context']
faltan = []
hijos_total = 0
for carpeta in CARPETAS:
    listado = subprocess.run(['git', 'ls-files', '-z', '--', carpeta + '/'], capture_output=True, text=True, check=True).stdout
    hijos = sorted({ruta[len(carpeta) + 1:].split('/')[0] for ruta in listado.split('\0') if ruta} - {'README.md'})
    if not hijos:
        faltan.append(f'{carpeta}/: git no lista nada; la comprobación no miró (canario)')
        continue
    texto = open(f'{carpeta}/README.md', encoding='utf-8').read()
    menciones = re.findall(r'`([^`\n]+)`', texto) + [m.split('#')[0] for m in re.findall(r'\]\(([^)\s]+)\)', texto)]
    rutas = [m.strip().strip('/').split('/') for m in menciones]
    literales = {s for r in rutas for s in r if s and not any(c in s for c in '*?[')}
    # Un patrón vale solo como último segmento y con algo más que comodines: `src/app/config/*` no nombra a nadie.
    patrones = {r[-1] for r in rutas if r and any(c in r[-1] for c in '*?') and re.sub(r'[*?]', '', r[-1])}
    for hijo in hijos:
        hijos_total += 1
        if hijo not in literales and not any(fnmatch.fnmatchcase(hijo, p) for p in patrones):
            faltan.append(f'{carpeta}/{hijo}: no sale en {carpeta}/README.md')
for linea in faltan:
    print(f'  {linea}')
print(f'índices ejecutado: {len(CARPETAS)} índices, {hijos_total} hijos directos versionados; {len(faltan)} sin nombrar')
sys.exit(1 if faltan else 0)
PY
fi

paso "Atribución a IA en entregables y commits"
python3 -B .agents/scripts/menciones_ia.py || fallo "hay atribuciones a IA"

printf '\n'
if [ "$fallos" -eq 0 ]; then
    echo "ANDAMIAJE OK"
else
    echo "ANDAMIAJE CON FALLOS: $fallos"
    exit 1
fi
