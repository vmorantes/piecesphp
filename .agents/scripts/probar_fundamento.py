"""Prueba de `fundamento.py`: una referencia buena y otra inventada de cada tipo.

Una referencia inventada es peor que ninguna, así que la herramienta tiene que verse cazar cada
forma antes de creerla (LEY 24).

Uso: python3 -B .agents/scripts/probar_fundamento.py
"""
import os
import subprocess
import sys
import tempfile

AQUI = os.path.dirname(os.path.abspath(__file__))
GUARDA = os.path.join(AQUI, "fundamento.py")
RAIZ = os.path.dirname(os.path.dirname(AQUI))


def corre(texto):
    with tempfile.NamedTemporaryFile("w", suffix=".txt", encoding="utf-8", delete=False) as f:
        f.write(texto)
        ruta = f.name
    try:
        r = subprocess.run([sys.executable, "-B", GUARDA, "--archivo", ruta],
                           capture_output=True, text=True, cwd=RAIZ)
    finally:
        os.unlink(ruta)
    return r.returncode, r.stdout + r.stderr


def main() -> int:
    #El hash bueno se toma del repositorio: escribir uno a mano lo haría caducar.
    hash_bueno = subprocess.run(["git", "rev-parse", "--short", "HEAD"], capture_output=True,
                                text=True, cwd=RAIZ, check=True).stdout.strip()
    #Y un archivo bueno con una línea que existe seguro, más su número de líneas para pasarse.
    ruta_buena = ".agents/scripts/fundamento.py"
    with open(os.path.join(RAIZ, ruta_buena), encoding="utf-8") as f:
        lineas = sum(1 for _ in f)

    casos = [
        ("las cuatro formas buenas",
         "[[FUNDAMENTO]] pendientes.md#210 · PO 2026-09-26 · %s · %s:1" % (hash_bueno, ruta_buena),
         0, ["0 sin existencia comprobable"]),
        ("punto de pendientes.md inventado",
         "[[FUNDAMENTO]] pendientes.md#99999", 1, ["NO EXISTE", "no hay un punto 99999"]),
        ("fecha del PO inventada",
         "[[FUNDAMENTO]] PO 1999-01-01", 1, ["NO EXISTE", "no hay ninguna «PO, 1999-01-01»"]),
        ("hash inventado",
         "[[FUNDAMENTO]] deadbeefcafe123", 1, ["NO EXISTE", "git no conoce ese objeto"]),
        ("archivo inventado",
         "[[FUNDAMENTO]] no-existe-zz-prueba.php:1", 1, ["NO EXISTE", "no existe ningún archivo"]),
        ("archivo real con una línea que no tiene",
         "[[FUNDAMENTO]] %s:%d" % (ruta_buena, lineas + 500), 1, ["NO EXISTE", "y se cita la"]),
        ("prosa libre: se cuenta como prosa y no se juzga",
         "[[FUNDAMENTO]] esto no es una referencia", 0, ["1 segmento(s) de prosa"]),
        ("NUEVO con su prosa detrás: vale, y la prosa no se juzga",
         "[[FUNDAMENTO]] NUEVO · decisión del arquitecto, sin precedente", 0,
         ["NUEVO", "1 declarada(s) NUEVO", "1 segmento(s) de prosa"]),
        ("glosas entre paréntesis: no se juzgan",
         "[[FUNDAMENTO]] pendientes.md#185 (lo de prueba se retira) · pendientes.md#208.5 (autoriza)",
         0, ["0 sin existencia comprobable"]),
        ("un archivo sin número de línea",
         "[[FUNDAMENTO]] 00-core.md (la regla)", 0, ["existe", "00-core.md"]),
        ("una referencia MAL ESCRITA no se cuela como prosa",
         "[[FUNDAMENTO]] pendiente.md#210", 1, ["NO EXISTE", "parece una referencia"]),
        ("un #NNN de la cadena: se dice que no es comprobable",
         "[[FUNDAMENTO]] tu `#635` §C (el reporte)", 0,
         ["NO COMPROBABLE", "1 no comprobable(s)"]),
        ("una buena y una inventada juntas: falla y nombra la mala",
         "[[FUNDAMENTO]] pendientes.md#210 · pendientes.md#99999", 1,
         ["1 sin existencia comprobable", "99999"]),
        ("varias referencias en un mismo segmento con prosa",
         "[[FUNDAMENTO]] nace de pendientes.md#216 y de pendientes.md#210", 0,
         ["pendientes.md#216", "pendientes.md#210", "0 sin existencia comprobable"]),
        ("una LEY que existe",
         "[[FUNDAMENTO]] LEY 24 (una puerta se ve fallar)", 0, ["existe", "LEY 24"]),
        ("una LEY inventada",
         "[[FUNDAMENTO]] LEY 999", 1, ["NO EXISTE", "no hay ninguna LEY 999"]),
        ("un archivo que empieza por punto, con su línea",
         "[[FUNDAMENTO]] .gitignore:1", 0, ["existe", ".gitignore:1"]),
        ("sin marca: AVISA y NO falla",
         "una instrucción cualquiera sin marca", 0, ["AVISA", "no trae ninguna marca"]),
    ]

    malos = 0
    for nombre, texto, esperado, agujas in casos:
        codigo, salida = corre(texto)
        faltan = [a for a in agujas if a not in salida]
        if codigo != esperado or faltan:
            malos += 1
            print("MAL  %-52s salida %d (se esperaba %d)%s" %
                  (nombre, codigo, esperado, (" · falta %s" % faltan) if faltan else ""))
            print("     " + salida.strip().replace("\n", "\n     "))
        else:
            print("bien %-52s salida %d" % (nombre, codigo))
    print("fundamento: %d/%d casos correctos" % (len(casos) - malos, len(casos)))
    return 1 if malos else 0


if __name__ == "__main__":
    sys.exit(main())
