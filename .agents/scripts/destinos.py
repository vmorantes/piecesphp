"""Destinos de las decisiones del PO: que ninguna se quede sin decir adónde fue (E1).

Nace de un fallo concreto: el 2026-10-08 se perdió la P51. Era una decisión del PO del 2026-09-19 que pedía trabajo,
estaba escrita en `decisiones-del-po.md` y en el diario, y ninguna lista de trabajo la llevaba. Lo que dependía de
acordarse, falló; esto lo mide.

FALLA (salida 1) si:
  - una tabla de decisiones (cabecera «| Fecha |», «| Decisión vieja |» o «| Qué quedó esperando |») no acaba en la
    columna «Destino»;
  - la última celda de una fila no es una de las formas de la regla 5 de `decisiones-del-po.md`:
      `criterio` · `tachada` · IDs de `abierto.md` («C11», «D12, G9») que existan allí ·
      `hecho · <ref>` o `hecho, sin verificar del todo · <ref>`, con <ref> un commit de 8 caracteres que exista,
      `ADR NNNN` con su archivo, una ruta del repositorio entre comillas invertidas que exista, o `CHANGELOG X.Y.Z`
      con su sección `# X.Y.Z`;
  - un ID de `abierto.md` aparece en dos filas.

NO MIRA, y hay que decirlo:
  - si el destino es el CORRECTO para esa decisión: solo que existe y que se puede seguir;
  - si una línea de `abierto.md` sigue viva o ya se cerró;
  - las tablas de `decisiones-del-po.md` con otra cabecera.

Uso: python3 -B .agents/scripts/destinos.py [--decisiones <ruta>] [--abierto <ruta>] [--raiz <dir>] [--git <dir>]
     (las opciones existen para poder provocarla con archivos de juguete; --git es el repositorio donde se buscan los
     commits, por omisión --raiz)
"""
import glob
import os
import re
import subprocess
import sys

CABECERAS = ("| Fecha |", "| Decisión vieja |", "| Qué quedó esperando |")
RE_IDS = re.compile(r"^[A-H][0-9]+(, [A-H][0-9]+)*$")
RE_HECHO = re.compile(r"^hecho(, sin verificar del todo)? · (.+)$")
RE_ID_ABIERTO = re.compile(r"^\| ([A-H][0-9]+) \|")


def opcion(nombre, por_omision):
    if nombre in sys.argv:
        return sys.argv[sys.argv.index(nombre) + 1]
    return por_omision


def celdas(linea):
    """Las celdas de una fila de tabla, recortadas. La última es la que manda."""
    return [c.strip() for c in linea.strip().strip("|").split("|")]


def tablas(lineas):
    """[(línea de cabecera, cabecera, [(número de línea, fila)])] de las tablas de decisiones; números desde 1."""
    salida = []
    i = 0
    while i < len(lineas):
        if lineas[i].startswith(CABECERAS):
            cabecera = (i + 1, lineas[i])
            filas = []
            j = i + 2  #la cabecera y su línea de alineación
            while j < len(lineas) and lineas[j].startswith("|"):
                filas.append((j + 1, lineas[j]))
                j += 1
            salida.append((cabecera[0], cabecera[1], filas))
            i = j
            continue
        i += 1
    return salida


def ids_de_abierto(ruta):
    """{ID: [números de línea]} de las filas de `abierto.md`."""
    vistos = {}
    for n, linea in enumerate(open(ruta, encoding="utf-8").read().splitlines(), start=1):
        m = RE_ID_ABIERTO.match(linea)
        if m is not None:
            vistos.setdefault(m.group(1), []).append(n)
    return vistos


def referencia_valida(ref, raiz, repo, changelog):
    """None si la referencia demuestra algo que existe; si no, por qué no."""
    if re.fullmatch(r"[0-9a-f]{8}", ref):
        r = subprocess.run(["git", "-C", repo, "cat-file", "-e", ref + "^{commit}"], capture_output=True)
        return None if r.returncode == 0 else "el commit %s no existe" % ref
    m = re.fullmatch(r"ADR ([0-9]{4})", ref)
    if m:
        return None if glob.glob(os.path.join(raiz, ".agents", "docs", "adr", m.group(1) + "-*.md")) else "no hay %s" % ref
    m = re.fullmatch(r"`([^`]+)`", ref)
    if m:
        return None if os.path.exists(os.path.join(raiz, m.group(1))) else "la ruta %s no existe" % m.group(1)
    m = re.fullmatch(r"CHANGELOG ([0-9]+\.[0-9]+\.[0-9]+)", ref)
    if m:
        cabecera = "# " + m.group(1)
        return None if any(l.startswith(cabecera) for l in changelog) else "el CHANGELOG no tiene la sección %s" % cabecera
    return "referencia de forma desconocida «%s»" % ref


def main() -> int:
    raiz = opcion("--raiz", subprocess.run(["git", "rev-parse", "--show-toplevel"], capture_output=True,
                                           text=True).stdout.strip() or ".")
    repo = opcion("--git", raiz)
    decisiones = opcion("--decisiones", os.path.join(raiz, ".agents", "docs", "decisiones-del-po.md"))
    abierto = opcion("--abierto", os.path.join(raiz, ".agents", "docs", "abierto.md"))
    try:
        lineas = open(decisiones, encoding="utf-8").read().splitlines()
        ids = ids_de_abierto(abierto)
        ruta_changelog = os.path.join(raiz, "CHANGELOG.md")
        changelog = open(ruta_changelog, encoding="utf-8").read().splitlines() if os.path.exists(ruta_changelog) else []
    except OSError as e:
        print("FALLO: no se pudo leer (%s)" % e)
        return 1

    fallos = []
    cuenta = {"criterio": 0, "abierto": 0, "hecho": 0, "sin verificar": 0, "tachada": 0}
    for id_, numeros in sorted(ids.items()):
        if len(numeros) > 1:
            fallos.append("%s: ID duplicado %s en las líneas %s" % (abierto, id_, ", ".join(map(str, numeros))))

    encontradas = tablas(lineas)
    total = 0
    for n_cabecera, cabecera, filas in encontradas:
        if celdas(cabecera)[-1] != "Destino":
            fallos.append("%s:%d tabla sin Destino: «%s»" % (decisiones, n_cabecera, cabecera.strip()))
            continue
        for n, fila in filas:
            total += 1
            destino = celdas(fila)[-1]
            if destino in ("criterio", "tachada"):
                cuenta[destino] += 1
                continue
            if RE_IDS.match(destino):
                faltan = [i for i in destino.split(", ") if i not in ids]
                if faltan:
                    fallos.append("%s:%d ID que no está en abierto.md: %s" % (decisiones, n, ", ".join(faltan)))
                else:
                    cuenta["abierto"] += 1
                continue
            m = RE_HECHO.match(destino)
            if m:
                motivo = referencia_valida(m.group(2), raiz, repo, changelog)
                if motivo is not None:
                    fallos.append("%s:%d %s: «%s»" % (decisiones, n, motivo, destino))
                else:
                    cuenta["hecho"] += 1
                    cuenta["sin verificar"] += 1 if m.group(1) else 0
                continue
            fallos.append("%s:%d destino %s: «%s»" % (decisiones, n, "vacío" if destino == "" else "de forma desconocida",
                                                       destino))

    for f in fallos:
        print("FALLO " + f)
    #LEY 18: la línea sale SIEMPRE, también cuando falla; sin ella, la puerta no corrió.
    print("destinos: %d decisiones en %d tablas; criterio %d, abierto %d, hecho %d (%d sin verificar del todo), "
          "tachada %d; %d IDs en abierto.md" % (total, len(encontradas), cuenta["criterio"], cuenta["abierto"],
                                                cuenta["hecho"], cuenta["sin verificar"], cuenta["tachada"], len(ids)))
    return 1 if fallos else 0


if __name__ == "__main__":
    sys.exit(main())
