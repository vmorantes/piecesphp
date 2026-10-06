"""Comprueba la marca `[[FUNDAMENTO]]` de una instrucción: que cada referencia EXISTA.

El arquitecto se compromete a cerrar toda instrucción que dicte una decisión con una línea así:

    [[FUNDAMENTO]] pendientes.md#210 · PO 2026-09-26 · 6fa95e83 · UsersController.php:186
    [[FUNDAMENTO]] NUEVO · decisión del arquitecto, sin precedente en el registro

Nace de un fallo medido: se le presentaron al PO como suyas cuatro decisiones que no eran suyas, y
se le volvió a preguntar algo ya contestado. La marca hace ese origen comprobable. Lo corre el CODER
al recibir la instrucción, no el arquitecto al escribirla: si dependiera de quien la escribe,
volvería a depender de su disciplina.

EL LÍMITE, y va escrito aquí a propósito: esto comprueba que la referencia EXISTE, **no que
SOSTENGA** la decisión. Se puede citar un punto real que no venga a cuento. «Fundamento verificado»
no significa «decisión correcta», y esta herramienta no opina de lo segundo.

QUÉ RECONOCE
  pendientes.md#NNN   el punto NNN existe en .agents/docs/pendientes.md (una línea «NNN. »); se
                      admite un subpunto, «#208.5», comprobando su punto entero
  <hash>              de 7 a 40 hexadecimales: `git cat-file -e` dice que el objeto está
  archivo:LÍNEA       el archivo existe y tiene al menos esa línea; se busca por nombre si la ruta
                      no es completa, y si hay varios candidatos se dice
  PO AAAA-MM-DD       hay alguna «PO, AAAA-MM-DD» registrada en .agents/ con esa fecha
  archivo             sin número de línea: basta que el archivo exista (por ejemplo «00-core.md»)
  LEY N               la ley N está enunciada en .agents/context/19-leyes.md
  NUEVO               válido siempre, y SE DICE en la salida, porque es información: una decisión
                      sin precedente

DE CADA SEGMENTO se extraen TODAS las referencias que contenga, no solo la que va sola: el
arquitecto escribe cosas como «nace de pendientes.md#216 y #215», y juzgar el segmento entero como
una sola referencia perdía la segunda. El resto del segmento es prosa y no se juzga.

CADA REFERENCIA ADMITE UNA GLOSA entre paréntesis, que no se juzga: «pendientes.md#185 (lo de prueba
se retira)». Y un segmento que no tiene forma de referencia se cuenta como PROSA y no se juzga —pero
si PARECE una referencia y no valida, falla: un «pendiente.md#210» mal escrito no puede colarse como
prosa. Un «#NNN» de la cadena de mensajes se dice NO COMPROBABLE: no hay registro contra el que
comprobarlo.

FALLA (salida 1) si alguna referencia no existe, y las nombra una por una.
AVISA, sin fallar, si la instrucción no trae ninguna marca: la costumbre se está haciendo y fallar
bloquearía el trabajo por un defecto ajeno.

Uso: python3 -B .agents/scripts/fundamento.py --archivo <instrucción.txt>
     python3 -B .agents/scripts/fundamento.py < instrucción.txt
"""
import os
import re
import subprocess
import sys

PENDIENTES = ".agents/docs/pendientes.md"
RE_MARCA = re.compile(r"\[\[FUNDAMENTO\]\](.*)$", re.M)
RE_PENDIENTE = re.compile(r"^pendientes\.md#(\d+)(?:\.\d+)?$", re.I)
RE_HASH = re.compile(r"^[0-9a-f]{7,40}$")
#Se admite un archivo sin extensión que empiece por punto: «.gitignore:23» es una referencia buena.
RE_ARCHIVO = re.compile(r"^((?:[\w./\\-]+\.\w+|\.\w[\w-]*)):(\d+)$")
RE_PO = re.compile(r"^PO,?\s+(\d{4}-\d{2}-\d{2})$", re.I)
RE_ARCHIVO_SOLO = re.compile(r"^([\w./\\-]+\.(?:md|php|json|js|ts|py|sh|neon|sql|txt|lock)|\.\w[\w-]*)$", re.I)
RE_LEY = re.compile(r"^LEY\s+(\d+)$", re.I)
LEYES = ".agents/context/19-leyes.md"
#Las formas que se BUSCAN dentro de un segmento con prosa. Cada una se juzga por separado.
RE_DENTRO = re.compile(
    r"pendientes\.md#\d+(?:\.\d+)?"
    r"|(?:[\w./\\-]+\.\w+|\.\w[\w-]*):\d+"
    r"|\bPO,?\s+\d{4}-\d{2}-\d{2}"
    r"|\bLEY\s+\d+"
    r"|\b[0-9a-f]{7,40}\b"
    r"|(?<![\w.])#\d+\b", re.I)
RE_MENSAJE = re.compile(r"(?<![\w.])#\d+\b")
RE_GLOSA = re.compile(r"\s*\(([^()]*)\)\s*$")
#Lo que DELATA un intento de referencia: si lo lleva y no valida, es una referencia mal escrita.
RE_PARECE = re.compile(r"\.md#|\.\w+:\d+|\b[0-9a-f]{7,40}\b|\bPO,?\s+\d{4}-", re.I)


def punto_existe(numero: str) -> bool:
    try:
        texto = open(PENDIENTES, encoding="utf-8").read()
    except OSError:
        return False
    return re.search(r"^%s\.\s" % re.escape(numero), texto, re.M) is not None


def objeto_existe(hash_corto: str) -> bool:
    r = subprocess.run(["git", "cat-file", "-e", hash_corto + "^{object}"],
                       capture_output=True, text=True)
    return r.returncode == 0


def ley_existe(numero: str) -> bool:
    try:
        texto = open(LEYES, encoding="utf-8").read()
    except OSError:
        return False
    return re.search(r"^#+\s*LEY\s+%s\b" % re.escape(numero), texto, re.M | re.I) is not None


def fecha_del_po(fecha: str) -> bool:
    r = subprocess.run(["git", "grep", "-l", "-E", "PO,? %s" % fecha, "--", ".agents"],
                       capture_output=True, text=True)
    return r.returncode == 0 and r.stdout.strip() != ""


def archivo_con_linea(ruta: str, linea: int):
    """(existe, detalle). La ruta puede venir a medias: se busca por nombre entre los versionados."""
    candidatos = [ruta] if os.path.isfile(ruta) else []
    if not candidatos:
        r = subprocess.run(["git", "ls-files"], capture_output=True, text=True, check=True)
        base = os.path.basename(ruta)
        candidatos = [a for a in r.stdout.splitlines() if a.endswith("/" + base) or a == base]
        #Si la referencia trae parte de la ruta, se respeta como filtro.
        if ruta != base:
            conParte = [a for a in candidatos if ruta in a]
            candidatos = conParte or candidatos
    if not candidatos:
        return False, "no existe ningún archivo con ese nombre"
    distintos = {}
    for c in candidatos:
        distintos.setdefault(os.path.realpath(c), c)
    if len(distintos) > 1:
        return False, "ambiguo: %d archivos distintos con ese nombre (%s)" % (
            len(distintos), ", ".join(sorted(distintos.values())[:3]))
    candidatos = [next(iter(distintos.values()))]
    try:
        with open(candidatos[0], encoding="utf-8", errors="replace") as f:
            total = sum(1 for _ in f)
    except OSError as e:
        return False, "no se pudo leer %s (%s)" % (candidatos[0], e)
    if linea > total:
        return False, "%s tiene %d línea(s) y se cita la %d" % (candidatos[0], total, linea)
    return True, candidatos[0]


def juzga(referencia: str):
    """(veredicto, tipo, detalle). veredicto: existe | no existe | nuevo | prosa | no comprobable."""
    referencia = referencia.strip().strip("`")
    glosa = ""
    m = RE_GLOSA.search(referencia)
    if m is not None:
        glosa = m.group(1)
        referencia = RE_GLOSA.sub("", referencia).strip()
    referencia = referencia.strip().strip("`")
    if not referencia:
        return None
    if referencia.upper() == "NUEVO":
        return "nuevo", "NUEVO", "decisión sin precedente: la herramienta lo dice, no lo juzga"
    m = RE_PENDIENTE.match(referencia)
    if m is not None:
        existe = punto_existe(m.group(1))
        return ("existe" if existe else "no existe"), "punto de pendientes.md", \
            ("%s punto %s" % (PENDIENTES, m.group(1)) if existe else
             "no hay un punto %s en %s" % (m.group(1), PENDIENTES))
    m = RE_PO.match(referencia)
    if m is not None:
        existe = fecha_del_po(m.group(1))
        return ("existe" if existe else "no existe"), "fecha del PO", \
            ("hay entradas del PO del %s en .agents/" % m.group(1) if existe else
             "no hay ninguna «PO, %s» registrada en .agents/" % m.group(1))
    m = RE_ARCHIVO.match(referencia)
    if m is not None:
        existe, detalle = archivo_con_linea(m.group(1), int(m.group(2)))
        return ("existe" if existe else "no existe"), "archivo:línea", detalle
    if RE_HASH.match(referencia):
        existe = objeto_existe(referencia)
        return ("existe" if existe else "no existe"), "objeto de git", \
            ("el objeto está en el repositorio" if existe else "git no conoce ese objeto")
    m = RE_LEY.match(referencia)
    if m is not None:
        existe = ley_existe(m.group(1))
        return ("existe" if existe else "no existe"), "ley", \
            ("enunciada en %s" % LEYES if existe else "no hay ninguna LEY %s en %s" % (m.group(1), LEYES))
    m = RE_ARCHIVO_SOLO.match(referencia)
    if m is not None:
        existe, detalle = archivo_con_linea(m.group(1), 1)
        return ("existe" if existe else "no existe"), "archivo", detalle
    if RE_MENSAJE.search(referencia):
        return "no comprobable", "mensaje de la cadena", \
            "no hay registro de mensajes contra el que comprobarlo"
    if RE_PARECE.search(referencia):
        return "no existe", "referencia mal escrita", \
            "parece una referencia y no valida con ninguna forma"
    return "prosa", "prosa", "no tiene forma de referencia: no se juzga"


def main() -> int:
    if "--archivo" in sys.argv:
        ruta = sys.argv[sys.argv.index("--archivo") + 1]
        try:
            texto = open(ruta, encoding="utf-8", errors="replace").read()
        except OSError as e:
            print("FALLA: no se pudo leer %s (%s)" % (ruta, e))
            return 1
    else:
        texto = sys.stdin.read()

    marcas = RE_MARCA.findall(texto)
    if not marcas:
        print("AVISA: la instrucción no trae ninguna marca [[FUNDAMENTO]]. No se juzga su origen.")
        print("       (avisa y no falla: la costumbre se está haciendo)")
        return 0

    fallos = 0
    nuevas = 0
    total = 0
    prosa = 0
    sin_comprobar = 0
    for marca in marcas:
        #El separador es « · » y SOLO ése. Partir tambien por comas rompia la prosa que las lleva.
        referencias = [r for r in marca.split("·") if r.strip()]
        print("[[FUNDAMENTO]] con %d referencia(s):" % len(referencias))
        expandidas = []
        for segmento in referencias:
            dentro = RE_DENTRO.findall(segmento)
            sinGlosa = RE_GLOSA.sub("", segmento.strip()).strip().strip("`")
            if len(dentro) > 1 or (dentro and sinGlosa.lower() not in [d.lower() for d in dentro]):
                #El segmento lleva prosa y una o varias referencias: se juzga cada una.
                expandidas.extend(dentro)
                expandidas.append("(prosa)")
            else:
                expandidas.append(segmento)
        for referencia in expandidas:
            if referencia == "(prosa)":
                prosa += 1
                continue
            juicio = juzga(referencia)
            if juicio is None:
                continue
            veredicto, tipo, detalle = juicio
            #Se enseña sin la glosa: con ella, el recorte de la columna tapaba el veredicto.
            referencia = RE_GLOSA.sub("", referencia.strip()).strip()
            total += 1
            if veredicto == "prosa":
                prosa += 1
                total -= 1
                continue
            if veredicto == "no comprobable":
                sin_comprobar += 1
                print("   NO COMPROBABLE %-46s %s · %s" % (referencia.strip()[:46], tipo, detalle))
            elif veredicto == "nuevo":
                nuevas += 1
                print("   NUEVO          %-46s %s" % (referencia.strip()[:46], detalle))
            elif veredicto == "existe":
                print("   existe         %-46s %s · %s" % (referencia.strip()[:46], tipo, detalle))
            else:
                fallos += 1
                print("   NO EXISTE      %-46s %s · %s" % (referencia.strip()[:46], tipo, detalle))

    print("")
    print("%d referencia(s) · %d sin existencia comprobable · %d declarada(s) NUEVO · "
          "%d no comprobable(s) · %d segmento(s) de prosa" % (total, fallos, nuevas, sin_comprobar, prosa))
    print("LÍMITE: se comprueba que la referencia EXISTE, no que SOSTENGA la decisión.")
    return 1 if fallos else 0


if __name__ == "__main__":
    sys.exit(main())
