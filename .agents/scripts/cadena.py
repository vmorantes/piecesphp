"""Los contadores y el tope de `AHORA.md`, por máquina (E5).

Nace de lo que se repetía al abrir la jornada: el contador de la cadena de `AHORA.md` no casaba con el último mensaje
anotado en el tramo, el «siguiente libre» saltaba números, y el tope de líneas se miraba a mano (`abrir-jornada` §4).
Tomado del andamiaje genérico (su `cadena.py`, VER.txt §1.1, 2026-10-15).

FALLA (salida 1) si:
  - `AHORA.md` no dice su «Último mensaje de la cadena: `#N`», su «siguiente libre, **`#M`**», su «Último mensaje al PO:
    `A-N`» o su «siguiente, **`A-M`**»;
  - el siguiente libre de la cadena no es N+1 ni N+2 (N+2 cuando el reporte de cierre no se anota);
  - el siguiente al PO no es N+1;
  - el último de la cadena no es el último `#N` anotado en una fila del tramo más reciente, ni el siguiente a ese (el
    reporte que cierra la jornada declara de antemano que no se anota);
  - `AHORA.md` pasa de su tope («Tope: **N líneas**»);
  - `AHORA.md` no tiene exactamente UN bloque «## Al retomar» (ADR 0029: dos bloques se contradicen en silencio);
  - `AHORA.md` nombra un punto «sin prisa» `S<n>` que `PO.md` no tiene como entrada («- **S<n> ·»): el único sitio
    donde viven las cosas del PO es `PO.md` (regla 30); nace de S7, que vivió solo en `AHORA.md` (`pendientes.md` 425.4).

NO MIRA, y hay que decirlo:
  - si el último mensaje REAL de la otra sesión es ese: solo lo que está escrito en el disco (la apertura lo contrasta con
    la otra sesión y con git);
  - lo que dice cada punto `S` ni si sigue vigente: solo que el de `AHORA.md` existe en `PO.md` (la higiene de `PO.md`
    la mira `higiene_po.py`);
  - los tramos que no son el más reciente.

Uso: python3 -B .agents/scripts/cadena.py [--ahora <ruta>] [--tramos <dir>] [--po <ruta>]
     (las opciones existen para poder provocarla con archivos de juguete)
"""
import glob
import os
import re
import sys

RAIZ = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))
RE_ULTIMO = re.compile(r"Último mensaje de la cadena: `#(\d+)")
RE_SIGUIENTE = re.compile(r"siguiente libre, \*\*`#(\d+)`\*\*")
RE_ULTIMO_PO = re.compile(r"Último mensaje al PO: `A-(\d+)`")
RE_SIGUIENTE_PO = re.compile(r"siguiente, \*\*`A-(\d+)`\*\*")
RE_TOPE = re.compile(r"Tope: \*\*(\d+) líneas\*\*")
RE_FILA_TRAMO = re.compile(r"^\| `#(\d+)` \|", re.MULTILINE)
RE_AL_RETOMAR = re.compile(r"^## Al retomar\b", re.MULTILINE)
RE_PUNTO_S = re.compile(r"\bS(\d+)\b")
RE_ENTRADA_S = re.compile(r"^- \*\*S(\d+) ·", re.MULTILINE)


def opcion(nombre, por_omision):
    if nombre in sys.argv:
        return sys.argv[sys.argv.index(nombre) + 1]
    return por_omision


def primero(regex, texto):
    m = regex.search(texto)
    return int(m.group(1)) if m else None


def main():
    ahora = opcion("--ahora", os.path.join(RAIZ, ".agents/estado/AHORA.md"))
    tramos = opcion("--tramos", os.path.join(RAIZ, ".agents/estado/tramos"))
    po = opcion("--po", os.path.join(RAIZ, ".agents/estado/PO.md"))
    fallos = []

    with open(ahora, encoding="utf-8") as f:
        texto = f.read()
    lineas = texto.count("\n") + (0 if texto.endswith("\n") else 1)

    ultimo = primero(RE_ULTIMO, texto)
    siguiente = primero(RE_SIGUIENTE, texto)
    ultimo_po = primero(RE_ULTIMO_PO, texto)
    siguiente_po = primero(RE_SIGUIENTE_PO, texto)
    tope = primero(RE_TOPE, texto)

    for nombre, valor in (("el último de la cadena", ultimo), ("el siguiente libre", siguiente),
                          ("el último al PO", ultimo_po), ("el siguiente al PO", siguiente_po), ("el tope", tope)):
        if valor is None:
            fallos.append(f"AHORA.md no dice {nombre} con la forma que esta puerta lee")

    if ultimo is not None and siguiente is not None and siguiente not in (ultimo + 1, ultimo + 2):
        fallos.append(f"cadena: el siguiente libre es #{siguiente} y el último #{ultimo}: tiene que ser #{ultimo + 1} o #{ultimo + 2}")
    if ultimo_po is not None and siguiente_po is not None and siguiente_po != ultimo_po + 1:
        fallos.append(f"PO: el siguiente es A-{siguiente_po} y el último A-{ultimo_po}: tiene que ser A-{ultimo_po + 1}")
    if tope is not None and lineas > tope:
        fallos.append(f"AHORA.md tiene {lineas} líneas y su tope es {tope}: se poda (cerrar-jornada)")

    bloques = len(RE_AL_RETOMAR.findall(texto))
    if bloques != 1:
        fallos.append(f"AHORA.md tiene {bloques} bloques «## Al retomar»: tiene que tener uno")

    puntos = sorted(set(int(n) for n in RE_PUNTO_S.findall(texto)))
    if puntos:
        if not os.path.exists(po):
            fallos.append(f"AHORA.md nombra S{puntos[0]} y no existe {po}")
        else:
            with open(po, encoding="utf-8") as f:
                entradas = set(int(n) for n in RE_ENTRADA_S.findall(f.read()))
            for n in puntos:
                if n not in entradas:
                    fallos.append(f"AHORA.md nombra S{n} y PO.md no lo tiene como entrada («- **S{n} ·»): vive solo aquí")

    archivos = sorted(glob.glob(os.path.join(tramos, "*.md")))
    anotado = None
    if not archivos:
        fallos.append(f"no hay tramos en {tramos}")
    else:
        with open(archivos[-1], encoding="utf-8") as f:
            numeros = [int(n) for n in RE_FILA_TRAMO.findall(f.read())]
        if not numeros:
            fallos.append(f"el tramo {os.path.basename(archivos[-1])} no anota ningún mensaje («| `#N` |»)")
        else:
            anotado = max(numeros)
            if ultimo is not None and ultimo not in (anotado, anotado + 1):
                fallos.append(f"cadena: AHORA.md dice #{ultimo} y el último anotado en {os.path.basename(archivos[-1])} es "
                              f"#{anotado}: o falta su fila en el tramo, o AHORA.md se quedó atrás")

    if fallos:
        for f in fallos:
            print("CADENA: " + f)
        sys.exit(1)
    print(f"cadena ejecutada: último #{ultimo} (anotado #{anotado}), siguiente #{siguiente}; PO A-{ultimo_po} → "
          f"A-{siguiente_po}; AHORA.md {lineas} de {tope} líneas; un «Al retomar»; puntos S en PO.md: {len(puntos)}")


if __name__ == "__main__":
    main()
