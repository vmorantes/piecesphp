"""Prueba de `destinos.py` con archivos de juguete.

Una puerta que no se ha visto fallar no se ha visto funcionar (LEY 24). Aquí se le da un juego correcto, que tiene que
pasar, y uno por cada cosa que debe cazar. Los juguetes viven en un temporal que hace de raíz; los commits se buscan
en el repositorio real (`--git`), porque el temporal no es un repositorio.

Uso: python3 -B .agents/scripts/probar_destinos.py
"""
import os
import shutil
import subprocess
import sys
import tempfile

GUARDA = os.path.join(os.path.dirname(os.path.abspath(__file__)), "destinos.py")
REPO = subprocess.run(["git", "rev-parse", "--show-toplevel"], capture_output=True, text=True).stdout.strip()
COMMIT = subprocess.run(["git", "-C", REPO, "rev-parse", "--short=8", "HEAD"], capture_output=True,
                        text=True).stdout.strip()

DECISIONES = """# Decisiones de juguete

| Fecha | Decisión | Dónde vive el detalle | Destino |
| :-- | :-- | :-- | :-- |
| 2026-10-09 | Una regla | aquí | criterio |
| 2026-10-09 | Pide trabajo | aquí | A1 |
| 2026-10-09 | Pide dos trabajos | aquí | A1, B2 |
| 2026-10-09 | Hecha con commit | aquí | hecho · COMMIT |
| 2026-10-09 | Hecha con ADR | aquí | hecho · ADR 0001 |
| 2026-10-09 | Hecha con ruta | aquí | hecho, sin verificar del todo · `docs/existe.md` |
| 2026-10-09 | Hecha con CHANGELOG | aquí | hecho · CHANGELOG 1.2.3 |
| 2026-10-09 | ~~Revocada~~ | aquí | tachada |

## Otra tabla

| Qué quedó esperando | Dónde | Destino |
| :-- | :-- | :-- |
| Algo de agosto | aquí | criterio |
""".replace("COMMIT", COMMIT)

ABIERTO = """# Abierto de juguete

| ID | Qué | Estado | Fuente |
| :-- | :-- | :-- | :-- |
| A1 | Un trabajo | PEND | x |
| B2 | Otro trabajo | PEND | x |
"""


def destino(viejo, nuevo):
    """El juego bueno con un destino cambiado."""
    return DECISIONES.replace(viejo, nuevo, 1), ABIERTO


CASOS = [
    ("bueno", (DECISIONES, ABIERTO), 0, "destinos: 9 decisiones en 2 tablas; criterio 2, abierto 2, hecho 4 (1 sin verificar del todo), tachada 1; 2 IDs"),
    ("tabla sin Destino",
     (DECISIONES.replace("| Qué quedó esperando | Dónde | Destino |", "| Qué quedó esperando | Dónde | Notas |"), ABIERTO),
     1, "tabla sin Destino"),
    ("celda vacía", destino("| aquí | criterio |", "| aquí |  |"), 1, "destino vacío"),
    ("forma desconocida", destino("| aquí | criterio |", "| aquí | pendiente |"), 1, "destino de forma desconocida: «pendiente»"),
    ("ID que no está en abierto", destino("| aquí | A1 |", "| aquí | C9 |"), 1, "ID que no está en abierto.md: C9"),
    ("commit inexistente", destino("hecho · " + COMMIT, "hecho · deadbeef"), 1, "el commit deadbeef no existe"),
    ("ADR inexistente", destino("hecho · ADR 0001", "hecho · ADR 9999"), 1, "no hay ADR 9999"),
    ("ruta inexistente", destino("`docs/existe.md`", "`docs/no-existe.md`"), 1, "la ruta docs/no-existe.md no existe"),
    ("CHANGELOG inexistente", destino("CHANGELOG 1.2.3", "CHANGELOG 0.0.1"), 1, "el CHANGELOG no tiene la sección # 0.0.1"),
    ("ID duplicado en abierto", (DECISIONES, ABIERTO + "| A1 | Repetido | PEND | x |\n"), 1, "ID duplicado A1"),
    #La sección H de abierto.md (P129: lo nuevo, congelado) también es destino; una letra más allá, no.
    ("H1 con su línea en abierto", (DECISIONES.replace("| aquí | A1 |", "| aquí | H1 |", 1), ABIERTO + "| H1 | Congelado | PEND | x |\n"),
     0, "destinos: 9 decisiones"),
    ("I1 no es una sección", destino("| aquí | A1 |", "| aquí | I1 |"), 1, "destino de forma desconocida: «I1»"),
]


def main() -> int:
    if len(COMMIT) != 8:
        print("FALLO: no se pudo leer el commit de HEAD del repositorio real: la prueba del commit bueno no mediría nada")
        return 1
    malos = 0
    for nombre, (decisiones, abierto), esperado, aguja in CASOS:
        raiz = tempfile.mkdtemp(prefix="zz-destinos-")
        try:
            os.makedirs(os.path.join(raiz, ".agents", "docs", "adr"))
            os.makedirs(os.path.join(raiz, "docs"))
            open(os.path.join(raiz, ".agents", "docs", "adr", "0001-juguete.md"), "w", encoding="utf-8").write("# ADR\n")
            open(os.path.join(raiz, "docs", "existe.md"), "w", encoding="utf-8").write("x\n")
            open(os.path.join(raiz, "CHANGELOG.md"), "w", encoding="utf-8").write("# 1.2.3 (09-10-2026)\n")
            ruta_d = os.path.join(raiz, "decisiones.md")
            ruta_a = os.path.join(raiz, "abierto.md")
            open(ruta_d, "w", encoding="utf-8").write(decisiones)
            open(ruta_a, "w", encoding="utf-8").write(abierto)
            r = subprocess.run([sys.executable, "-B", GUARDA, "--decisiones", ruta_d, "--abierto", ruta_a,
                                "--raiz", raiz, "--git", REPO], capture_output=True, text=True)
        finally:
            shutil.rmtree(raiz)
        salida = r.stdout + r.stderr
        #LEY 18: la línea de resumen sale siempre, también cuando falla.
        bien = r.returncode == esperado and aguja in salida and "destinos: " in salida
        if not bien:
            malos += 1
            print("MAL  %-28s salida %d (se esperaba %d)%s" %
                  (nombre, r.returncode, esperado, "" if aguja in salida else " · sin «%s»" % aguja))
            print("     " + salida.strip().replace("\n", "\n     "))
        else:
            print("bien %-28s salida %d · dice «%s»" % (nombre, r.returncode, aguja))
    print("destinos: %d/%d casos correctos" % (len(CASOS) - malos, len(CASOS)))
    return 1 if malos else 0


if __name__ == "__main__":
    sys.exit(main())
