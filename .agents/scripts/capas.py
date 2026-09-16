"""Comprueba .agents/capas.json contra los archivos versionados.

Falla (salida 1) si:
  - un archivo del universo no pertenece a ninguna capa (un archivo nuevo sin clasificar);
  - un archivo pertenece a más de una capa;
  - un patrón no casa con ningún archivo (un patrón muerto miente sobre lo que hay);
  - un archivo versionado fuera del universo casa con algún patrón.

Patrones: `fnmatch` sobre la ruta relativa, donde `*` cruza `/`. Se ancla escribiendo el prefijo entero.

Uso: python3 -B .agents/scripts/capas.py [--manifiesto <ruta>]   (la opción existe para provocarla con una copia)
"""
import fnmatch
import json
import subprocess
import sys


def main() -> int:
    ruta = ".agents/capas.json"
    if "--manifiesto" in sys.argv:
        ruta = sys.argv[sys.argv.index("--manifiesto") + 1]
    manifiesto = json.load(open(ruta, encoding="utf-8"))
    universo = manifiesto["universo"]
    capas = manifiesto["capas"]

    salida = subprocess.run(["git", "ls-files", "-z"], capture_output=True, check=True).stdout
    archivos = [a for a in salida.decode("utf-8").split("\0") if a]

    def en_universo(a: str) -> bool:
        return any(a == u or (u.endswith("/") and a.startswith(u)) for u in universo)

    fallos = []
    usos = {(c, p): 0 for c, d in capas.items() for p in d["patrones"]}
    conteo = {c: 0 for c in capas}
    total = 0
    for a in archivos:
        dentro = en_universo(a)
        suyas = []
        for c, d in capas.items():
            for p in d["patrones"]:
                if fnmatch.fnmatchcase(a, p):
                    usos[(c, p)] += 1
                    if c not in suyas:
                        suyas.append(c)
        if not dentro:
            if suyas:
                fallos.append(f"fuera del universo pero clasificado en {','.join(suyas)}: {a}")
            continue
        total += 1
        if not suyas:
            fallos.append(f"sin capa: {a}")
        elif len(suyas) > 1:
            fallos.append(f"en varias capas ({','.join(suyas)}): {a}")
        else:
            conteo[suyas[0]] += 1

    for (c, p), n in usos.items():
        if n == 0:
            fallos.append(f"patrón que no casa con nada en {c}: {p}")

    reparto = ", ".join(f"{c} {n}" for c, n in conteo.items())
    print(f"capas: {total} archivos del universo; {reparto}; {len(usos)} patrones")
    for f in fallos:
        print(f"  FALLO {f}")
    return 1 if fallos else 0


if __name__ == "__main__":
    sys.exit(main())
