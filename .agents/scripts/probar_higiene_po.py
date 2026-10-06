"""Prueba de `higiene_po.py` con archivos de juguete.

Una puerta que no se ha visto fallar no se ha visto funcionar (LEY 24). Aquí se le da un `PO.md`
correcto, que tiene que pasar, y uno por cada cosa que debe cazar.

Uso: python3 -B .agents/scripts/probar_higiene_po.py
"""
import os
import subprocess
import sys
import tempfile

GUARDA = os.path.join(os.path.dirname(os.path.abspath(__file__)), "higiene_po.py")
HOY = "2026-09-26"

LIMPIO = """# Lo que espera al PO

## 1. Buzón

- [ ] **2026-09-26 · A-400.** Un aviso sin leer, que es lo que debe haber aquí.

## 2. Preguntas abiertas

### P80 · Una pregunta de verdad (2026-09-26)

*Predeterminado:* nada.

## 3. Acciones tuyas

- Ninguna abierta.

## 4. Sin prisa (tienen predeterminado y no frenan nada)

- **S2 · Algo que dura.**
"""

CASOS = [
    ("limpio", LIMPIO, 0, None),
    ("un aviso del buzón ya marcado",
     LIMPIO.replace("- [ ] **2026-09-26 · A-400.**", "- [X] **2026-09-26 · A-400.**"),
     1, "aviso ya marcado"),
    ("una pregunta cerrada entre las abiertas",
     LIMPIO.replace("### P80 · Una pregunta de verdad (2026-09-26)",
                    "### P80 · Una pregunta de verdad (2026-09-26)\n\n- P79 contestada el 2026-09-25."),
     1, "pregunta cerrada"),
    ("sin las secciones que dice mirar",
     LIMPIO.replace("## 1. Buzón", "## Buzón").replace("## 2. Preguntas abiertas", "## Preguntas"),
     1, "no tiene las secciones"),
    ("una pregunta vieja: AVISA y NO falla",
     LIMPIO.replace("(2026-09-26)", "(2026-09-20)"), 0, "lleva 6 día(s)"),
    ("una pregunta sin fecha: AVISA y NO falla",
     LIMPIO.replace(" (2026-09-26)", ""), 0, "no lleva fecha"),
]


def main() -> int:
    malos = 0
    for nombre, contenido, esperado, aguja in CASOS:
        with tempfile.NamedTemporaryFile("w", suffix=".md", encoding="utf-8", delete=False) as f:
            f.write(contenido)
            ruta = f.name
        try:
            r = subprocess.run([sys.executable, "-B", GUARDA, "--archivo", ruta, "--hoy", HOY],
                               capture_output=True, text=True)
        finally:
            os.unlink(ruta)
        salida = r.stdout + r.stderr
        bien = r.returncode == esperado and (aguja is None or aguja in salida)
        if not bien:
            malos += 1
            print("MAL  %-44s salida %d (se esperaba %d)%s" %
                  (nombre, r.returncode, esperado,
                   "" if aguja is None or aguja in salida else " · sin «%s»" % aguja))
            print("     " + salida.strip().replace("\n", "\n     "))
        else:
            print("bien %-44s salida %d%s" % (nombre, r.returncode,
                                              "" if aguja is None else " · dice «%s»" % aguja))
    print("higiene_po: %d/%d casos correctos" % (len(CASOS) - malos, len(CASOS)))
    return 1 if malos else 0


if __name__ == "__main__":
    sys.exit(main())
