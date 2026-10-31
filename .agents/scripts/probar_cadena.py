"""Prueba de `cadena.py` con archivos de juguete.

Una puerta que no se ha visto fallar no se ha visto funcionar (LEY 24). Un juego correcto, que tiene que pasar, y uno por
cada cosa que debe cazar. Los juguetes viven en un temporal.

Uso: python3 -B .agents/scripts/probar_cadena.py
"""
import os
import shutil
import subprocess
import sys
import tempfile

GUARDA = os.path.join(os.path.dirname(os.path.abspath(__file__)), "cadena.py")

AHORA = """# Ahora

Tope: **TOPE líneas**.

- **Último mensaje de la cadena: `#ULTIMO · ARQ`** (algo). El siguiente libre, **`#SIGUIENTE`** (su reporte).
- **Último mensaje al PO: `A-ULTIMOPO`** (por chat). El siguiente, **`A-SIGUIENTEPO`**.
- Sin prisa: PUNTOS.

## Al retomar

1. Algo.
"""

PO = """# Lo que espera al PO

## 4. Sin prisa

- **S2 · Algo.** *Predeterminado:* nada.
- **S5 · Otra cosa.** *Predeterminado:* nada.
"""

TRAMO = """# Tramo

| Mensaje | Qué | Commits |
| :-- | :-- | :-- |
| `#10` | instruido | — |
| `#ANOTADO` | reporte | — |
"""


def caso(nombre, debe_fallar, ultimo=11, siguiente=12, ultimo_po=5, siguiente_po=6, anotado=11, tope=50, sin_tramo=False,
         sin_po=False, puntos="S2, S5", retomar=1):
    tmp = tempfile.mkdtemp()
    try:
        texto = (AHORA.replace("TOPE", str(tope)).replace("#ULTIMO", f"#{ultimo}").replace("#SIGUIENTE", f"#{siguiente}")
                 .replace("A-ULTIMOPO", f"A-{ultimo_po}").replace("A-SIGUIENTEPO", f"A-{siguiente_po}")
                 .replace("PUNTOS", puntos))
        if retomar != 1:
            texto = texto.replace("## Al retomar\n\n1. Algo.\n", "## Al retomar\n\n1. Algo.\n" * retomar)
        if sin_po:
            texto = "\n".join(l for l in texto.split("\n") if "al PO" not in l)
        with open(os.path.join(tmp, "AHORA.md"), "w", encoding="utf-8") as f:
            f.write(texto)
        with open(os.path.join(tmp, "PO.md"), "w", encoding="utf-8") as f:
            f.write(PO)
        os.mkdir(os.path.join(tmp, "tramos"))
        if not sin_tramo:
            with open(os.path.join(tmp, "tramos", "2026-01-01-0000-a.md"), "w", encoding="utf-8") as f:
                f.write(TRAMO.replace("#ANOTADO", f"#{anotado}"))
        r = subprocess.run([sys.executable, "-B", GUARDA, "--ahora", os.path.join(tmp, "AHORA.md"), "--tramos",
                            os.path.join(tmp, "tramos"), "--po", os.path.join(tmp, "PO.md")], capture_output=True, text=True)
        fallo = r.returncode != 0
        bien = fallo == debe_fallar
        print(("bien" if bien else "MAL ") + f" {nombre}: {'falla' if fallo else 'no falla'} salida {r.returncode}")
        return bien
    finally:
        shutil.rmtree(tmp)


casos = [
    caso("un juego correcto: NO falla", False),
    caso("el siguiente libre a dos (reporte de cierre sin anotar): NO falla", False, siguiente=13),
    caso("AHORA.md un mensaje por delante del tramo: NO falla", False, ultimo=12, siguiente=13),
    caso("el siguiente libre salta tres: falla", True, siguiente=14),
    caso("el siguiente libre por detrás: falla", True, siguiente=11),
    caso("el siguiente al PO salta: falla", True, siguiente_po=7),
    caso("AHORA.md dos mensajes por delante del tramo: falla", True, ultimo=13, siguiente=14),
    caso("AHORA.md por detrás del tramo: falla", True, ultimo=10, siguiente=11),
    caso("AHORA.md pasa de su tope: falla", True, tope=3),
    caso("sin tramos: falla", True, sin_tramo=True),
    caso("sin el contador del PO: falla", True, sin_po=True),
    caso("dos bloques «Al retomar»: falla", True, retomar=2),
    caso("ningún bloque «Al retomar»: falla", True, retomar=0),
    caso("un punto S que PO.md no tiene: falla", True, puntos="S2, S7"),
    caso("sin puntos S: NO falla", False, puntos="nada"),
]
if not all(casos):
    print("probar_cadena: la puerta NO caza lo que debe")
    sys.exit(1)
print(f"probar_cadena ejecutada: {len(casos)} casos, todos bien")
