"""Higiene de `.agents/estado/PO.md`: que ahí solo viva lo ABIERTO.

Nace de un fallo concreto: las respuestas del PO del 25 se aplicaron, pero P71 y P72 se quedaron en
`PO.md`, así que al repasarlo se le volvieron a preguntar. Re-preguntar lo decidido es señal de
relevo (`.agents/rules/30-protocolo-coder.md`). Lo que dependía de acordarse, falló; esto lo mide.

FALLA (salida 1) si:
  - una entrada del buzón sigue en el archivo marcada `[X]`: lo leído se borra (PO, 2026-09-26);
  - en la sección de preguntas, una línea nombra una pregunta `P<n>` junto a una palabra de cierre
    —contestada, cerrada, resuelta, respondida, aplicada—: eso ya no es lo abierto;
  - no encuentra las secciones del buzón y de las preguntas: si el formato cambia, esta puerta
    dejaría de mirar lo que cree mirar, y callarse sería peor que fallar.

AVISA, sin fallar:
  - una pregunta abierta cuya fecha más reciente tiene más de DOS días: que el arquitecto la mire,
    no que se pare el trabajo;
  - una pregunta abierta SIN ninguna fecha: no se puede envejecer, y decirlo es parte de medir.

NO MIRA, y hay que decirlo:
  - si la respuesta del PO se APLICÓ: eso vive en `../docs/pendientes.md` y no se deduce de aquí;
  - si una pregunta está bien redactada o lleva su predeterminado;
  - ni las secciones 3 y 4 (acciones y «sin prisa»), que por su naturaleza duran.

Uso: python3 -B .agents/scripts/higiene_po.py [--archivo <ruta>] [--hoy AAAA-MM-DD]
     (las dos opciones existen para poder provocarla con un archivo de juguete)
"""
import datetime
import re
import sys

POR_OMISION = ".agents/estado/PO.md"
DIAS_DE_GRACIA = 2

RE_SECCION = re.compile(r"^##\s+\d+\.\s*(.+?)\s*$")
RE_MARCADA = re.compile(r"^\s*[-*]\s*\[[Xx]\]")
RE_PREGUNTA = re.compile(r"\bP\d+\b")
RE_CIERRE = re.compile(r"\b(contestad[ao]s?|cerrad[ao]s?|resuelt[ao]s?|respondid[ao]s?|aplicad[ao]s?)\b", re.I)
RE_FECHA = re.compile(r"\b(\d{4})-(\d{2})-(\d{2})\b")


def secciones(lineas):
    """{título: [(número de línea, texto)]}, con el número de línea empezando en 1."""
    actual = None
    salida = {}
    for i, linea in enumerate(lineas, start=1):
        m = RE_SECCION.match(linea)
        if m is not None:
            actual = m.group(1)
            salida[actual] = []
            continue
        if actual is not None:
            salida[actual].append((i, linea))
    return salida


def una_que_contenga(mapa, palabra):
    for titulo in mapa:
        if palabra.lower() in titulo.lower():
            return titulo
    return None


def main() -> int:
    ruta = POR_OMISION
    if "--archivo" in sys.argv:
        ruta = sys.argv[sys.argv.index("--archivo") + 1]
    hoy = datetime.date.today()
    if "--hoy" in sys.argv:
        hoy = datetime.date.fromisoformat(sys.argv[sys.argv.index("--hoy") + 1])

    try:
        lineas = open(ruta, encoding="utf-8").read().splitlines()
    except OSError as e:
        print("FALLA: no se pudo leer %s (%s)" % (ruta, e))
        return 1

    mapa = secciones(lineas)
    buzon = una_que_contenga(mapa, "buz")
    preguntas = una_que_contenga(mapa, "pregunta")
    if buzon is None or preguntas is None:
        print("FALLA: %s no tiene las secciones de buzón y de preguntas con la forma «## N. Título»."
              % ruta)
        print("       Secciones encontradas: %s" % (", ".join(mapa) or "ninguna"))
        print("       Esta puerta no adivina: si el formato cambia, se dice.")
        return 1

    fallos = []
    avisos = []

    for numero, linea in mapa[buzon]:
        if RE_MARCADA.match(linea):
            fallos.append("%s:%d el buzón guarda un aviso ya marcado [X]: se borra al verlo marcado "
                          "(PO, 2026-09-26). «%s»" % (ruta, numero, linea.strip()[:96]))

    for numero, linea in mapa[preguntas]:
        if RE_PREGUNTA.search(linea) and RE_CIERRE.search(linea):
            fallos.append("%s:%d una pregunta cerrada sigue en la sección de abiertas; su decisión va "
                          "a ../docs/pendientes.md. «%s»" % (ruta, numero, linea.strip()[:96]))

    #El envejecimiento se mide sobre la fecha ESCRITA: no hay otra por pregunta. Se dice cuando falta.
    for numero, linea in mapa[preguntas]:
        if not RE_PREGUNTA.search(linea):
            continue
        fechas = [datetime.date(int(a), int(m), int(d)) for a, m, d in RE_FECHA.findall(linea)]
        etiqueta = RE_PREGUNTA.search(linea).group(0)
        if not fechas:
            avisos.append("%s:%d %s no lleva fecha: no se puede envejecer." % (ruta, numero, etiqueta))
            continue
        dias = (hoy - max(fechas)).days
        if dias > DIAS_DE_GRACIA:
            avisos.append("%s:%d %s lleva %d día(s) sin tocarse (más de %d)."
                          % (ruta, numero, etiqueta, dias, DIAS_DE_GRACIA))

    print("higiene de PO.md: %d sección(es); buzón «%s» con %d línea(s), preguntas «%s» con %d."
          % (len(mapa), buzon, len(mapa[buzon]), preguntas, len(mapa[preguntas])))
    for a in avisos:
        print("AVISA: " + a)
    for f in fallos:
        print("FALLA: " + f)
    if fallos:
        print("Lo que esta puerta NO mira: si la respuesta se aplicó, ni las secciones de acciones y «sin prisa».")
        return 1
    print("PO.md limpio: ningún aviso marcado sin borrar y ninguna pregunta cerrada entre las abiertas.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
