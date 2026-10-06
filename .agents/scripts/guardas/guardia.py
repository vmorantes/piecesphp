#!/usr/bin/env python3
"""
Guarda PreToolUse de Claude Code (enganchada en .claude/settings.json).

Lee de stdin el JSON de la llamada a herramienta y la bloquea (código de
salida 2, motivo por stderr) si viola una salvaguarda de
.agents/rules/40-salvaguardas.md. Es una red, no la regla: que algo pase
por aquí no lo autoriza. Decisión y alternativas: ADR 0003.

Criterio de diseño: ante la duda, bloquear. Un falso positivo cuesta una
pregunta; un falso negativo puede costar las credenciales de un remoto o un
repositorio publicado sin permiso. Las pruebas están en probar_guardia.py.
"""
import json
import os
import re
import shlex
import subprocess
import sys

sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from patrones import buscar, es_entregable  # noqa: E402

RAIZ = os.path.realpath(
    os.environ.get("CLAUDE_PROJECT_DIR") or os.path.join(os.path.dirname(os.path.abspath(__file__)), "..", "..", "..")
)
HOME = os.path.expanduser("~")

# Los cuatro paquetes piecesphp/* son repositorios hermanos de este. Se trabaja
# en ellos solo cuando la instrucción los nombra (40-salvaguardas.md §3); la
# guarda no puede saberlo y los deja escribir.
PAQUETES = ("database", "datastructures", "geojson", "html")
HERMANOS = tuple(os.path.join(os.path.dirname(RAIZ), p) + os.sep for p in PAQUETES)

# La guía personal del PO vive en su propio repositorio, fuera de este y fuera de
# los paquetes: no se distribuye con el framework ni se versiona con él. El PO
# autorizó escribir ahí el 2026-09-15, nombrando el repositorio.
GUIA_PO = os.path.join(os.path.dirname(RAIZ), "guia-piecesphp-para-po") + os.sep

# Fuera del repositorio solo se escribe en temporales, en la memoria nativa de
# Claude Code, en los repositorios hermanos y en el de la guía del PO.
ESCRIBIBLES_EXTRA = (
    "/tmp/",
    os.path.join(HOME, ".claude", "projects") + os.sep,
) + HERMANOS + (GUIA_PO,)

# Dentro de estas zonas se borra; la zona ENTERA, no. Sin esto, `rm -rf /tmp` o
# `rm -rf <raíz del repositorio>` pasaban, porque la raíz también es escribible
# (aviso de la plantilla andamiaje-arquitecto-coder, verificado aquí el 2026-09-15).
RAICES_PROTEGIDAS = tuple(
    os.path.realpath(p) for p in (RAIZ, "/tmp", os.path.join(HOME, ".claude", "projects"), HOME)
) + tuple(os.path.realpath(h.rstrip(os.sep)) for h in HERMANOS + (GUIA_PO,))

# Las claves del producto no las lee un agente, tampoco desde Bash (40-salvaguardas.md §7):
# settings.json solo niega la herramienta Read.
SECRETOS = (os.path.join(RAIZ, "secure-keys"),)

PROHIBIDOS = {
    # Escalada de privilegios.
    "sudo", "su", "pkexec", "doas",
    # Servidores remotos y clientes de las forjas (usan las credenciales del PO).
    "ssh", "scp", "sftp", "sshpass", "mosh", "telnet", "ftp", "lftp", "ncftp", "gh", "glab",
    # Bases de datos.
    "mysql", "mariadb", "mysqldump", "psql", "mongo", "mongosh", "redis-cli", "sqlcmd", "sqlite3",
    # Paquetes y sistema.
    "apt", "apt-get", "aptitude", "dpkg", "snap", "flatpak", "yum", "dnf",
    "systemctl", "service", "crontab", "mkfs", "fdisk", "parted", "dd",
    "shutdown", "reboot", "poweroff", "halt", "chown", "useradd", "userdel",
    "usermod", "passwd", "visudo", "iptables", "ufw", "nft",
}
ENVOLTORIOS = {"env", "nohup", "time", "command", "exec", "nice", "timeout", "xargs", "watch"}
# Lo único que un agente puede actualizar con Composer: la instrumentación de análisis que el
# PO delegó en el arquitecto (ADR 0007).
HERRAMIENTAS_DE_ANALISIS = {"phpstan/phpstan", "phpstan/phpstan-deprecation-rules", "rector/rector"}
# Y las dependencias entre paquetes hermanos, solo dentro de uno de ellos (ADR 0008).
PAQUETES_HERMANOS_COMPOSER = {"piecesphp/" + p for p in PAQUETES}
# Guiones del repositorio que no ejecuta un agente: uno sube los cinco
# repositorios, el otro cambia propietarios y permisos del sistema.
GUIONES_VETADOS = re.compile(r"(^|/)(push-all|permissions-and-property\.sh)$")
# Las URL de los remotos llevan credenciales (18 T4).
CONFIG_DE_GIT = re.compile(r"(^|/)\.git/config$")
RUTAS_DEL_SISTEMA = re.compile(r"(^|[\s>=\"'])(/etc|/usr|/root|/boot|/bin|/sbin|/lib|/var/lib|/opt|/srv)(/|\s|$|[\"'])")
REDIRECCION_AL_SISTEMA = re.compile(r"(>>?|\btee\b(\s+-a)?)\s*[\"']?(/etc|/usr|/root|/boot|/bin|/sbin|/lib|/var/lib|/var/log|/opt|/srv)/")


class Bloqueo(Exception):
    pass


def bloquear(motivo):
    raise Bloqueo(motivo)


def dentro_de(ruta, base):
    ruta = os.path.realpath(ruta)
    base = os.path.realpath(base)
    return ruta == base or ruta.startswith(base + os.sep)


def ruta_escribible(ruta):
    absoluta = os.path.realpath(os.path.expanduser(ruta))
    return dentro_de(absoluta, RAIZ) or any((absoluta + os.sep).startswith(p) for p in ESCRIBIBLES_EXTRA)


def _toca_secretos(arg, base):
    """True si el argumento, o lo que va tras un `=`, apunta dentro de SECRETOS desde `base`."""
    candidatos = []
    if "=" in arg:
        candidatos.append(arg.split("=", 1)[1])
    if not arg.startswith("-"):
        candidatos.append(arg)
    for c in candidatos:
        if not c:
            continue
        absoluta = os.path.normpath(os.path.join(base, os.path.expanduser(c)))
        for ruta in (absoluta, os.path.realpath(absoluta)):
            if any(ruta == s or ruta.startswith(s + os.sep) for s in SECRETOS):
                return True
    return False


SEPARADORES = {";", "&&", "||", "|", "&", "|&", ";;", "(", ")"}


def _lineas_con_estado(texto):
    """
    Cada linea del texto con un booleano: si EMPIEZA dentro de una comilla que
    abrio una linea anterior. Ignora las comillas escapadas con barra invertida.
    """
    salida = []
    comilla = None
    for linea in texto.splitlines():
        salida.append((linea, comilla is not None))
        escapado = False
        for caracter in linea:
            if escapado:
                escapado = False
                continue
            if caracter == "\\":
                escapado = True
            elif comilla is None and caracter in "'\"":
                comilla = caracter
            elif caracter == comilla:
                comilla = None
    return salida


#Intérpretes de shell: lo que les llega por heredoc SÍ se ejecuta como comandos.
INTERPRETES_DE_SHELL = ("bash", "sh", "zsh", "ksh", "dash", "csh", "tcsh", "eval", "source", ".")
#Apertura de un heredoc: `<<PALABRA`, `<<-PALABRA`, `<<'PALABRA'`, `<<"PALABRA"`.
APERTURA_HEREDOC = re.compile(r"""<<-?\s*(['"]?)([A-Za-z_][A-Za-z0-9_]*)\1""")


def _cuerpo_de_heredoc(texto):
    """Las líneas que son CUERPO de un heredoc, por número de línea (base 0).

    El cuerpo de un heredoc es DATO —un mensaje de commit, un guion de Python— y no se ejecuta como
    órdenes de shell. Analizarlo como tal da falsos positivos: `su` es la palabra más común de un
    mensaje en español, y bloqueó dos commits legítimos el 2026-09-26 (`#610`).

    La excepción, que es la que mantiene esto seguro: si quien recibe el heredoc es un INTÉRPRETE DE
    SHELL (`bash <<EOF`), su cuerpo sí son órdenes y se sigue analizando entero.
    """
    fuera, abierto, delimitador = set(), False, None
    for numero, linea in enumerate(texto.splitlines()):
        if abierto:
            if linea.strip() == delimitador:
                abierto = False
                delimitador = None
            else:
                fuera.add(numero)
            continue
        m = APERTURA_HEREDOC.search(linea)
        if not m:
            continue
        #El primer token de la línea decide si el cuerpo es dato o son órdenes.
        cabeza = re.split(r"\s+", linea.strip())[0] if linea.strip() else ""
        if os.path.basename(cabeza) in INTERPRETES_DE_SHELL:
            continue
        abierto, delimitador = True, m.group(2)
    return fuera


def segmentos(comando):
    """
    Devuelve una lista de comandos simples, cada uno como lista de tokens.

    Parte por líneas y, dentro de cada una, por los operadores de control
    (; && || | & paréntesis) que estén FUERA de comillas: un '|' dentro del
    patrón de un grep no separa nada. El cuerpo de un heredoc se salta, salvo
    que lo reciba un intérprete de shell: ver `_cuerpo_de_heredoc`.
    """
    resultado = []
    texto = comando.replace("`", "\n")
    cuerpo_heredoc = _cuerpo_de_heredoc(texto)
    for numero, (linea, dentro_de_comillas) in enumerate(_lineas_con_estado(texto)):
        if numero in cuerpo_heredoc:
            continue
        # Una linea que EMPIEZA dentro de una comilla abierta es texto de un
        # argumento (el cuerpo de un -m, por ejemplo), no un comando. Lo que va
        # entre comillas es dato: no se ejecuta.
        if dentro_de_comillas:
            continue
        try:
            lex = shlex.shlex(linea, posix=True, punctuation_chars=True)
            lex.whitespace_split = True
            partes = list(lex)
        except ValueError:
            # Comillas sin cerrar (típico de un heredoc): se trocea a lo bruto.
            partes = re.split(r"\s+", linea.strip())
        actual = []
        for p in partes:
            if p in SEPARADORES:
                if actual:
                    resultado.append(actual)
                actual = []
            else:
                actual.append(p)
        if actual:
            resultado.append(actual)
    return resultado


REDIRECCION_INOFENSIVA = re.compile(r"^(?:\d*|&)>>?/dev/null$|^\d*>&\d+$")


def destinos_de_escritura(args):
    """Los argumentos que pueden ser el destino de una escritura: sin opciones y sin las redirecciones inofensivas.

    El troceo deja `2>/dev/null` como `2`, `>`, `/dev/null` y `2>&1` como `2`, `>&`, `1`. Se quitan el descriptor, el
    operador y el `/dev/null` o el descriptor de destino. Cualquier otra redirección deja su destino como argumento:
    `2> /var/tmp/x` sigue dando `/var/tmp/x`, y la guarda lo juzga.
    """
    destinos, saltar = [], False
    for k, a in enumerate(args):
        if saltar:
            saltar = False
            continue
        siguiente = args[k + 1] if k + 1 < len(args) else None
        if REDIRECCION_INOFENSIVA.match(a):
            continue
        if a.isdigit() and siguiente is not None and re.fullmatch(r"[<>&]+", siguiente):
            continue
        if re.fullmatch(r"[<>&]+", a):
            if siguiente == "/dev/null" or (a.endswith("&") and siguiente is not None and siguiente.isdigit()):
                saltar = True
            continue
        if not a.startswith("-"):
            destinos.append(a)
    return destinos


def sin_redirecciones(args):
    """Quita las redirecciones: shlex deja `2>&1` como `2`, `>&`, `1`, y `> f` como `>`, `f`."""
    limpio, saltar = [], False
    for k, a in enumerate(args):
        if saltar:
            saltar = False
            continue
        if re.fullmatch(r"[<>&]+", a):
            saltar = True
            continue
        if a.isdigit() and k + 1 < len(args) and re.fullmatch(r"[<>&]+", args[k + 1]):
            continue
        limpio.append(a)
    return limpio


def tokens(partes):
    partes = [p for p in partes if p]
    # Quita asignaciones de entorno iniciales y envoltorios (env, nohup...).
    while partes and (re.match(r"^[A-Za-z_][A-Za-z0-9_]*=", partes[0]) or partes[0] in ENVOLTORIOS):
        partes = partes[1:]
        # timeout 10 cmd / nice -n 5 cmd: descarta argumentos numéricos y flags del envoltorio.
        while partes and (partes[0].startswith("-") or partes[0].isdigit()):
            partes = partes[1:]
    return partes


# ADR 0019 y ADR 0031: arquitecto y coder etiquetan las pre-versiones Y la estable. El 0031 (2026-09-30) retiro la
# reserva que el 0019 hacia sobre la version MAYOR estable, con sus siete condiciones; esta guarda siguio escrita
# contra el 0019 hasta el 2026-10-02 y bloqueaba la estable: dos verdades sin puerta entre ellas. Lo ajusto el
# arquitecto con la autorizacion expresa del PO en su sesion (A-498 §2).
# Lo DESTRUCTIVO sigue prohibido y se bloquea mas abajo, en los cinco repositorios: mover o borrar una etiqueta.
PRE_VERSION = re.compile(r"v\d+\.\d+\.\d+-(alpha|beta|rc)\.\d+")
VERSION_ESTABLE = re.compile(r"v\d+\.\d+\.\d+")
TAG_CON_VALOR = ("-m", "-F", "-u", "--message", "--file", "--local-user", "--cleanup", "--trailer")


def posicionales(resto, con_valor):
    """Los argumentos que no son opciones ni el valor de una opción que lo lleva separado."""
    pos, saltar = [], False
    for a in resto:
        if saltar:
            saltar = False
            continue
        if a in con_valor:
            saltar = True
            continue
        if a.startswith("-"):
            continue
        pos.append(a)
    return pos


def revisar_git(args):
    # Sin esto, el `1` de `2>&1` se leía como el nombre de una rama nueva.
    args = sin_redirecciones(args)
    if not args:
        return
    # Salta opciones globales (git -C ruta, git -c k=v, git --no-optional-locks) y recuerda en
    # qué repositorio actúa: en los cuatro paquetes se etiqueta y se crea `dev` (P19).
    repo = RAIZ
    i = 0
    while i < len(args) and args[i].startswith("-"):
        if args[i] == "-C" and i + 1 < len(args):
            repo = os.path.realpath(os.path.join(repo, os.path.expanduser(args[i + 1])))
        i += 2 if args[i] in ("-C", "-c") else 1
    if i >= len(args):
        return
    sub, resto = args[i], args[i + 1:]
    en_paquete = any((repo + os.sep).startswith(p) for p in HERMANOS)

    if sub in ("push", "fetch", "pull", "ls-remote"):
        bloquear(f"git {sub}: los remotos no se tocan; el PO sube y consulta (20 §3, ADR 0003).")
    if sub == "remote" and resto and resto[0] in (
        "-v", "--verbose", "get-url", "show", "add", "set-url", "remove", "rm", "rename", "prune", "update"
    ):
        bloquear("git remote: las URL de los remotos llevan credenciales; no se imprimen ni se tocan (18 T4).")
    if sub == "tag":
        listar = not resto or any(
            a in ("-l", "--list")
            or a.startswith(("--list", "--sort", "--points-at", "--contains", "--no-contains", "--merged", "--no-merged", "--format", "--column", "-n"))
            for a in resto
        )
        escribe = any(
            a in ("-a", "-s", "-u", "-f", "-d", "-m", "-F", "-e", "--annotate", "--sign", "--force", "--delete", "--edit")
            or a.startswith(("--message", "--file", "--local-user"))
            for a in resto
        )
        if any(a in ("-d", "-f", "--delete", "--force") for a in resto):
            bloquear("mover o borrar una etiqueta publicada: prohibido en los cinco repositorios.")
        en_framework = (repo + os.sep).startswith(RAIZ + os.sep)
        nombre = (posicionales(resto, TAG_CON_VALOR) or [""])[0]
        version = en_framework and (
            PRE_VERSION.fullmatch(nombre) is not None or VERSION_ESTABLE.fullmatch(nombre) is not None
        )
        if (escribe or not listar) and not en_paquete and not version:
            bloquear(
                "en este repositorio se etiquetan versiones vX.Y.Z y pre-versiones vX.Y.Z-alpha|beta|rc.N "
                "(ADR 0019 y ADR 0031); cualquier otro nombre de etiqueta, no. En los cuatro paquetes sí se "
                "etiqueta (P19)."
            )
    # ADR 0019: `master` y `last-stable` solo avanzan, y sin tocar el árbol. update-ref con el valor anterior es una
    # comparación atómica: si la rama no está donde se midió, no se mueve.
    if sub == "update-ref":
        if any(a in ("-d", "--delete", "--stdin") for a in resto):
            bloquear("git update-ref -d/--stdin borra o mueve referencias sin control: prohibido.")
        pos = posicionales(resto, ("-m",))
        if pos and pos[0].startswith("refs/tags/"):
            bloquear("mover o borrar una etiqueta publicada: prohibido en los cinco repositorios.")
        if pos and (not pos[0].startswith("refs/heads/") or len(pos) < 3):
            bloquear(
                "git update-ref solo sobre refs/heads/ y con el valor anterior (<ref> <nuevo> <anterior>): sin él, "
                "una rama puede ir hacia atrás (ADR 0019)."
            )
        if pos:
            # «Solo avanzan» se comprueba: sin esto, update-ref reescribía historia por la puerta que `rebase` tiene
            # cerrada. Si git no puede resolver alguno de los dos valores, bloquea: falla cerrado.
            try:
                avanza = subprocess.run(
                    ["git", "-C", repo, "merge-base", "--is-ancestor", pos[2], pos[1]],
                    stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, timeout=10,
                ).returncode == 0
            except (OSError, subprocess.SubprocessError):
                avanza = False
            if not avanza:
                bloquear(
                    "git update-ref: el valor nuevo no desciende del anterior, o no se pudo comprobar. Mover una rama "
                    "hacia atrás o de lado reescribe historia, y eso es del PO (40-salvaguardas.md §4)."
                )
    if sub in ("rebase", "filter-branch", "filter-repo", "replace"):
        bloquear(f"git {sub} reescribe historia: prohibido (40-salvaguardas.md §4).")
    if sub == "reflog" and resto[:1] in (["expire"], ["delete"]):
        bloquear("git reflog expire/delete pierde el rastro para recuperar trabajo: prohibido.")
    if sub == "gc" and any(a.startswith("--prune") for a in resto):
        bloquear("git gc --prune borra objetos sin retorno: prohibido.")
    if sub == "reset" and ("--hard" in resto or "--merge" in resto or "--keep" in resto):
        bloquear("git reset --hard descarta trabajo sin retorno: prohibido.")
    if sub == "clean" and (
        any(a.startswith("-") and not a.startswith("--") and "f" in a.lstrip("-") for a in resto) or "--force" in resto
    ):
        bloquear("git clean -f borra lo no versionado sin retorno: prohibido.")
    if sub == "checkout" and ("--" in resto or "." in resto or "-f" in resto or "--force" in resto):
        bloquear("git checkout -- / . / -f descarta cambios locales: prohibido.")
    if sub == "restore" and "--staged" not in resto:
        bloquear("git restore sobre el árbol descarta cambios: prohibido (solo --staged).")
    if sub == "branch" and any(a in ("-D", "-d", "--delete", "-M", "-m", "--move") for a in resto):
        bloquear("borrar o renombrar ramas requiere permiso del PO.")
    # Ninguna rama se crea sin permiso del PO (2026-09-14). Listar sí.
    if sub == "branch":
        con_valor = ("--contains", "--no-contains", "--merged", "--no-merged", "--points-at", "--sort", "--format")
        listar = any(a in ("-l", "--list") or a.startswith(con_valor) for a in resto)
        nuevas = [a for a in resto if not a.startswith("-")]
        # Única excepción (PO, 2026-09-14): los cuatro paquetes llevan una rama `dev`.
        if not listar and nuevas and not (en_paquete and nuevas[0] == "dev"):
            bloquear("crear ramas requiere permiso del PO (40-salvaguardas.md §4).")
    if sub == "switch" and any(
        a in ("-c", "-C", "--create", "--force-create", "--orphan") or a.startswith(("--create=", "--force-create=")) for a in resto
    ):
        bloquear("crear ramas requiere permiso del PO (40-salvaguardas.md §4).")
    if sub == "checkout" and any(a in ("-b", "-B", "--orphan") for a in resto):
        bloquear("crear ramas requiere permiso del PO (40-salvaguardas.md §4).")
    if sub == "worktree" and resto[:1] == ["add"]:
        bloquear("git worktree add crea un árbol y casi siempre una rama: requiere permiso del PO.")
    if sub == "stash" and resto[:1] in (["drop"], ["clear"]):
        bloquear("git stash drop/clear pierde trabajo: prohibido.")
    if sub == "config":
        lectura = any(a in ("--get", "--get-all") for a in resto)
        sensible = any(
            re.search(r"(^remote\.|url|credential|token|password)", a, re.IGNORECASE) for a in resto if not a.startswith("-")
        )
        if not lectura or sensible:
            bloquear("git config: solo se consulta con --get, y nunca claves de remotos ni credenciales (18 T4).")
    if sub == "add" and any(a in (".", "-A", "--all", "*", ":/") for a in resto):
        bloquear("git add . / -A está prohibido: añade rutas explícitas (30-protocolo-coder.md).")
    if sub == "commit":
        if "--amend" in resto:
            bloquear("git commit --amend reescribe historia: prohibido.")
        mensaje = []
        for j, a in enumerate(resto):
            if a in ("-m", "--message") and j + 1 < len(resto):
                mensaje.append(resto[j + 1])
            elif a.startswith("--message="):
                mensaje.append(a.split("=", 1)[1])
            elif a.startswith("-m") and len(a) > 2:
                mensaje.append(a[2:])
            elif a in ("-F", "--file") and j + 1 < len(resto):
                try:
                    with open(os.path.join(RAIZ, resto[j + 1]), encoding="utf-8") as f:
                        mensaje.append(f.read())
                except OSError:
                    pass
        hallazgos = buscar("\n".join(mensaje))
        if hallazgos:
            bloquear(f"el mensaje de commit atribuye el trabajo a una IA ({hallazgos[0][1]!r}): prohibido (40-salvaguardas.md §5).")


def revisar_rm(args):
    recursivo = any(
        a in ("-r", "-R", "--recursive") or (a.startswith("-") and not a.startswith("--") and ("r" in a or "R" in a))
        for a in args
    )
    objetivos = [a for a in args if not a.startswith("-")]
    for o in objetivos:
        if o in ("/", "~", "*", ".", "..", "/*", "~/*") or o.startswith(("~", "$HOME", "${HOME}")) or ".." in o.split("/"):
            bloquear(f"rm sobre {o!r}: prohibido.")
        if o.startswith("$"):
            bloquear(f"rm sobre una ruta en variable ({o!r}): no se puede comprobar, prohibido.")
        if os.path.isabs(o):
            r = os.path.realpath(o)
            if r in RAICES_PROTEGIDAS or (os.path.realpath(RAIZ) + os.sep).startswith(r + os.sep):
                bloquear(
                    f"rm sobre la raíz de una zona escribible o sobre un directorio que contiene "
                    f"el proyecto ({o!r}): prohibido."
                )
        if os.path.isabs(o) and not ruta_escribible(o):
            bloquear(f"rm fuera del repositorio y de /tmp ({o!r}): prohibido.")
        if o == ".git" or o.startswith(".git/"):
            bloquear("rm dentro de .git: prohibido.")
    if recursivo and not objetivos:
        bloquear("rm recursivo sin objetivo claro: prohibido.")


# ── Base de datos LOCAL (ADR 0024) ───────────────────────────────────────────────────────
# Los clientes siguen prohibidos salvo estos dos, y solo para leer o para una prueba que se
# deshace sola. Cada cliente que se abre es superficie nueva: `psql`, `sqlite3`, `redis-cli`,
# `mongo`, `mongosh`, `sqlcmd` y `mysqldump` se quedan fuera.
CLIENTES_BD_LOCAL = {"mysql", "mariadb"}
HOSTS_LOCALES = {"localhost", "127.0.0.1", "::1"}
# Lo único que puede aparecer en el molde de LECTURA.
SENTENCIAS_DE_LECTURA = {"SELECT", "SHOW", "DESCRIBE", "DESC", "EXPLAIN", "USE"}
# Y lo único que puede aparecer en el molde REVERSIBLE. Lista blanca a propósito: una lista de
# prohibidas nunca acaba, porque el SQL puede viajar DENTRO de una cadena —`EXECUTE IMMEDIATE`,
# `PREPARE`/`EXECUTE`, `CALL`— y ahí el análisis por primera palabra no llega (#501).
SENTENCIAS_REVERSIBLES = SENTENCIAS_DE_LECTURA | {
    "BEGIN", "START", "INSERT", "UPDATE", "DELETE", "REPLACE", "SAVEPOINT", "ROLLBACK", "RELEASE",
}
# Escritura que PERSISTE (ADR 0025): el PO autorizó escribir en la base local con confianza, por ser
# su entorno de desarrollo. Sigue siendo lista blanca (LEY 35).
SENTENCIAS_DE_ESCRITURA = SENTENCIAS_DE_LECTURA | {"INSERT", "UPDATE", "DELETE", "REPLACE"}
# Y la estructura, más acotada todavía: lo que hace falta para una migración y nada más. `DROP TABLE`,
# `TRUNCATE`, `RENAME` y `DROP DATABASE` se quedan fuera: destruyen lo que ninguna ronda necesita
# destruir, y para eso están `bin/cli scheme-drop` y `db-restore`, que la instrucción tiene que nombrar.
# `CREATE TABLE … AS SELECT` entra aquí y crea la tabla con datos dentro: crea, no destruye.
ACCIONES_DE_ESTRUCTURA = (
    r"CREATE\s+(UNIQUE\s+)?INDEX\b",
    r"CREATE\s+TABLE\b",
    r"DROP\s+INDEX\b",
)
# Y las cláusulas que puede llevar un ALTER TABLE. **Se comprueban una a una**: `ALTER TABLE` admite
# varias separadas por comas, así que mirar solo el principio deja pasar lo que venga detrás (#511).
# `DROP PRIMARY KEY` no casa con `DROP (COLUMN|INDEX|KEY)`, y `RENAME TO` no casa con
# `RENAME (COLUMN|INDEX|KEY)`: los dos quedan fuera, que es lo que se quiere.
CLAUSULAS_DE_ALTER = (
    r"ADD\b",
    r"DROP\s+(COLUMN|INDEX|KEY)\b",
    r"MODIFY\b",
    r"CHANGE\b",
    r"RENAME\s+(COLUMN|INDEX|KEY)\b",
)
# En MySQL y MariaDB estas CONFIRMAN SOLAS: el ROLLBACK posterior no las deshace, así que una
# prueba que las lleve no es reversible aunque lo parezca.
CONFIRMAN_SOLAS = {
    "CREATE", "ALTER", "DROP", "TRUNCATE", "RENAME", "GRANT", "REVOKE",
    "LOCK", "UNLOCK", "INSTALL", "UNINSTALL", "FLUSH", "SET",
    # Mantenimiento: tampoco se deshacen, y RESET MASTER borra los registros binarios.
    "RESET", "OPTIMIZE", "REPAIR", "ANALYZE", "CACHE", "CHECKSUM", "BACKUP", "RESTORE",
}
# `LOAD DATA [LOCAL] INFILE` lee un archivo del disco y lo vuelca en una tabla: rodea la
# protección de secure-keys/ y de src/app/config/ (40-salvaguardas.md §7). Fuera de los dos moldes.
LEE_O_ESCRIBE_ARCHIVOS = re.compile(r"\b(INTO\s+(OUT|DUMP)FILE|INFILE|LOAD\s+DATA|LOAD_FILE\s*\()", re.IGNORECASE)
# Opciones CORTAS de mysql/mariadb que llevan valor. Hace falta saberlo para leer `-Ne "SELECT 1"`,
# donde `-N` no lleva valor y `-e` sí: sin esto, un uso legítimo se bloquea y un `-pSECRETO`
# agrupado se escapa.
# Aquí solo entra la letra que se ha comprobado que lleva valor. Poner de más es peor que poner
# de menos: una letra que NO lo lleva se come el argumento siguiente, y si ese argumento era un
# `-e`, la guarda deja de verlo (#495). `-C`, `-L`, `-r`, `-i`, `-N` y `-B` son banderas.
CORTAS_CON_VALOR = "uhPeSD"  # usuario, host, puerto, ejecutar, socket, base de datos
# `-p` va aparte: su valor es OPCIONAL y solo cuenta pegado (`-pSECRETO`); `-p` a secas pide la
# contraseña por terminal y NO se come el argumento siguiente.
CORTA_CONTRASENA = "p"
# Lo único que puede declarar el archivo de opciones: conexión y credenciales. Lista blanca a
# propósito, porque ahí dentro cabe cualquier opción del cliente y algunas ejecutan SQL.
OPCIONES_DE_ARCHIVO = {
    "host", "user", "password", "port", "socket", "database", "protocol",
    "default-character-set", "ssl-ca", "ssl-cert", "ssl-key",
}
# Opciones de la ORDEN que meten SQL por otra puerta que `-e`, escriben fuera de la base o leen
# de un sitio que no se puede inspeccionar. Se vigilan por su nombre, así que sus abreviaturas
# entran solas por la regla de prefijos.
OPCIONES_VETADAS = (
    ("init-command", "mete SQL por otra puerta que -e"),
    ("tee", "escribe en un archivo de fuera de la base"),
    ("pager", "entrega la salida a otra orden"),
    ("login-path", "lee un archivo cifrado que no se puede inspeccionar"),
    ("defaults-group-suffix", "amplía qué grupos del archivo se leen"),
    ("defaults-extra-file", "se SUMA a los archivos por omisión; usa --defaults-file, que los sustituye"),
)


def _opciones(args):
    """Desmonta los argumentos de mysql/mariadb en [(nombre, valor)].

    Cubre las formas reales: `-e V`, `-eV`, `-Ne V` (cortas agrupadas), `--execute=V` y
    `--execute V`. `valor` es None cuando la opción no lo lleva o viene al final sin él.
    """
    fuera, saltar = [], False
    for k, a in enumerate(args):
        if saltar:
            saltar = False
            continue
        siguiente = args[k + 1] if k + 1 < len(args) else None
        if a.startswith("--"):
            nombre, _, valor = a[2:].partition("=")
            if _:
                fuera.append((nombre, valor))
            else:
                fuera.append((nombre, siguiente))
            continue
        if a.startswith("-") and len(a) > 1:
            resto = a[1:]
            while resto:
                letra, resto = resto[0], resto[1:]
                if letra == CORTA_CONTRASENA:
                    #Valor opcional y solo pegado: `-p` a secas no se come el argumento siguiente.
                    fuera.append((letra, resto or None))
                    resto = ""
                elif letra in CORTAS_CON_VALOR:
                    if resto:
                        fuera.append((letra, resto))
                    else:
                        fuera.append((letra, siguiente))
                        saltar = siguiente is not None
                    resto = ""
                else:
                    fuera.append((letra, None))
            continue
        fuera.append(("", a))
    return fuera


def _valores(opciones, cortas, *largas):
    """Todos los valores de una opción. La lista dice cuántas veces aparece: mariadb CONCATENA
    varios `-e`, así que quedarse con el primero deja el resto sin ver.

    Un nombre largo cuenta también por su ABREVIATURA: el cliente acepta cualquier prefijo único
    (`--hos=`, `--pas=`), así que comparar el nombre exacto deja la comprobación fuera de juego.
    Un prefijo de un nombre vigilado es ese nombre.
    """
    fuera = []
    for nombre, valor in opciones:
        if nombre in cortas or any(nombre and l.startswith(nombre) for l in largas):
            fuera.append(valor)
    return fuera


def _cuenta_ejecuciones(args):
    """Cuenta las opciones de ejecución por otro camino que el lector de opciones.

    Defensa en profundidad: si el lector se equivoca sobre qué opción lleva valor, puede tragarse
    un `-e` entero y dejar de verlo (#495). Si los dos recuentos no coinciden, la orden no se
    puede comprobar y no pasa. Una letra mal declarada —de más o de menos— hace que discrepen,
    así que equivocarse en esa lista falla CERRADO.

    En un grupo de cortas solo cuenta la `e` que aún podría ser opción: en cuanto aparece una
    letra que se lleva el resto como valor, lo que venga después es texto. Sin eso, una base de
    datos llamada `piecesphp` (`-Dpiecesphp`) se leería como una ejecución (#497).
    """
    n = 0
    for a in args:
        if a.startswith("--"):
            nombre = a[2:].split("=", 1)[0]
            if nombre and "execute".startswith(nombre):
                n += 1
        elif a.startswith("-") and len(a) > 1:
            for letra in a[1:]:
                if letra == "e":
                    n += 1
                    break
                if letra in CORTAS_CON_VALOR or letra == CORTA_CONTRASENA:
                    break
    return n


def _clausulas(texto):
    """Parte por comas de primer nivel: fuera de comillas y fuera de paréntesis.

    Las comas de dentro de un `VARCHAR(10, 2)` o de una lista de columnas no separan cláusulas.
    """
    fuera, actual, comilla, hondo, escapado = [], [], None, 0, False
    for c in texto:
        if escapado:
            actual.append(c)
            escapado = False
            continue
        if c == "\\":
            actual.append(c)
            escapado = True
            continue
        if comilla:
            actual.append(c)
            if c == comilla:
                comilla = None
            continue
        if c in "'\"`":
            comilla = c
        elif c == "(":
            hondo += 1
        elif c == ")":
            hondo = max(0, hondo - 1)
        elif c == "," and hondo == 0:
            fuera.append("".join(actual).strip())
            actual = []
            continue
        actual.append(c)
    ultimo = "".join(actual).strip()
    if ultimo:
        fuera.append(ultimo)
    return [c for c in fuera if c]


def _partir_sentencias(consulta):
    """Parte por `;` FUERA de comillas y devuelve (PRIMERA_PALABRA, sentencia).

    Partir por `;` a secas deja que un `;` dentro de una cadena rompa una sentencia en dos y
    fabrique una primera palabra que no es la de nadie.
    """
    tramos, actual, comilla, escapado = [], [], None, False
    for c in consulta:
        if escapado:
            actual.append(c)
            escapado = False
            continue
        if c == "\\":
            actual.append(c)
            escapado = True
            continue
        if comilla:
            actual.append(c)
            if c == comilla:
                comilla = None
            continue
        if c in "'\"`":
            comilla = c
            actual.append(c)
            continue
        if c == ";":
            tramos.append("".join(actual))
            actual = []
            continue
        actual.append(c)
    tramos.append("".join(actual))
    if comilla:
        bloquear("consulta con una comilla sin cerrar: no se puede comprobar (ADR 0024).")
    fuera = []
    for tramo in tramos:
        limpio = tramo.strip()
        if limpio:
            fuera.append((re.split(r"\s+", limpio)[0].upper(), limpio))
    return fuera


def revisar_cliente_bd(cmd, args):
    """Deja pasar una lectura o una prueba reversible contra la base LOCAL (ADR 0024).

    Bloquea con su motivo en cualquier otro caso: la regla manda sobre la guarda, y lo que no
    se puede comprobar leyendo la orden no se deja pasar.
    """
    opciones = _opciones(args)
    #Una contraseña escrita en la orden queda en el historial de la sesión (40-salvaguardas.md §7);
    #`-p` a secas la pide por terminal, que además no se puede comprobar. La vía es un archivo de
    #opciones con `--defaults-extra-file`, fuera del repositorio.
    if _valores(opciones, ("p",), "password"):
        bloquear(
            f"'{cmd}' con la contraseña en la orden: prohibido (40-salvaguardas.md §7). "
            "Usa --defaults-extra-file con un archivo fuera del repositorio."
        )
    for host in _valores(opciones, ("h",), "host"):
        if (host or "").strip() not in HOSTS_LOCALES:
            bloquear(f"'{cmd}' solo contra la base LOCAL (ADR 0024): '{host}' no lo es.")
    for nombre, motivo in OPCIONES_VETADAS:
        if _valores(opciones, (), nombre):
            bloquear(f"'{cmd}': --{nombre} {motivo} (ADR 0024).")
    #El cliente lee además archivos de opciones que la orden no nombra —los del sistema, los del
    #HOME y el que señale MYSQL_HOME—, y de cualquiera de ellos puede salir SQL o un host (#499).
    #`--defaults-file` SUSTITUYE a todos, así que exigirlo hace que lo que se inspecciona aquí sea
    #exactamente lo que el cliente va a leer. Es la única forma de que esta comprobación prometa
    #algo comprobable.
    rutas = _valores(opciones, (), "defaults-file")
    if len(rutas) != 1:
        bloquear(
            f"'{cmd}' sin --defaults-file: el cliente leería además archivos que la orden no nombra "
            "y que esta guarda no puede ver (ADR 0024)."
        )
    #El host también puede venir del archivo de opciones, y entonces la orden no lo enseña.
    for ruta in rutas:
        if not ruta:
            bloquear(f"'{cmd}' con un archivo de opciones sin ruta (ADR 0024).")
        #La ruta se mira tal como está escrita: aquí no hay intérprete de órdenes que expanda nada.
        if "$" in ruta or "`" in ruta:
            bloquear(f"'{cmd}': la ruta del archivo de opciones no puede llevar variables; escríbela entera (ADR 0024).")
        try:
            contenido = open(os.path.expanduser(ruta), encoding="utf-8", errors="replace").read()
        except OSError:
            bloquear(f"'{cmd}': no se puede leer el archivo de opciones '{ruta}', así que no se puede comprobar su host (ADR 0024).")
        #El archivo es una SEGUNDA vía de entrada: el cliente concatena su `execute` con el de la
        #orden y ejecuta las dos (#497). Por eso aquí manda una lista blanca: lo que no está
        #declarado no entra, y así no hace falta perseguir cada opción que inyecte SQL.
        for linea in contenido.splitlines():
            limpia = linea.split("#", 1)[0].strip()
            if not limpia or limpia.startswith("["):
                continue
            clave = limpia.split("=", 1)[0].strip().lower().replace("_", "-")
            if clave not in OPCIONES_DE_ARCHIVO:
                bloquear(
                    f"'{cmd}': el archivo de opciones declara '{clave}', que no está permitida. "
                    "Solo conexión y credenciales; nada que ejecute SQL (ADR 0024)."
                )
            if clave == "host":
                valor = limpia.split("=", 1)[1].strip().strip("\"'") if "=" in limpia else ""
                if valor not in HOSTS_LOCALES:
                    bloquear(f"'{cmd}': el archivo de opciones declara un host que no es local (ADR 0024).")
    if "<" in args:
        bloquear(f"'{cmd}' alimentado por un archivo: la consulta no se puede comprobar (ADR 0024).")
    consultas = _valores(opciones, ("e",), "execute")
    #mariadb CONCATENA varios `-e` y los ejecuta todos: comprobar solo el primero dejaría el resto
    #sin mirar, que es tanto como no comprobar nada.
    if len(consultas) > 1:
        bloquear(f"'{cmd}' con varios -e: se ejecutan todos y solo se comprueba de uno en uno (ADR 0024).")
    #Si el recuento fino y el de bulto no coinciden, el lector de opciones se ha perdido algo.
    if _cuenta_ejecuciones(args) != len(consultas):
        bloquear(f"'{cmd}': las opciones no se pueden leer con seguridad, así que la consulta no se puede comprobar (ADR 0024).")
    consulta = consultas[0] if consultas else None
    if not (consulta or "").strip():
        bloquear(
            f"'{cmd}' sin -e: una sesión interactiva o por entrada estándar no se puede comprobar "
            "antes de ejecutarse (ADR 0024)."
        )
    #Un comentario puede esconder lo que venga detrás, y aquí no se analiza SQL: se rechaza de más.
    if re.search(r"--|/\*|#", consulta):
        bloquear(f"'{cmd}' con un comentario SQL dentro de la consulta: prohibido (ADR 0024).")
    #Leer o escribir archivos desde SQL no es ni leer ni escribir en la base: no entra en ningún molde.
    if LEE_O_ESCRIBE_ARCHIVOS.search(consulta):
        bloquear(
            f"'{cmd}': la consulta lee o escribe un archivo del disco (INFILE, OUTFILE, LOAD DATA). "
            "Eso rodea la protección de los secretos (40-salvaguardas.md §7)."
        )

    sentencias = _partir_sentencias(consulta)
    if not sentencias:
        bloquear(f"'{cmd}' con una consulta vacía.")
    primeras = [p for p, _ in sentencias]

    # Molde REVERSIBLE: abre, hace lo suyo y deshace. Se mira antes que el de lectura porque una
    # transacción de solo lecturas también es válida aquí.
    if primeras[0] in ("BEGIN", "START"):
        if primeras[0] == "START" and not re.match(r"START\s+TRANSACTION\b", sentencias[0][1], re.IGNORECASE):
            bloquear(f"'{cmd}': 'START' que no abre una transacción (ADR 0024).")
        if primeras[-1] != "ROLLBACK":
            bloquear(f"'{cmd}': una prueba reversible termina en ROLLBACK, y ésta termina en '{primeras[-1]}' (ADR 0024).")
        if "COMMIT" in primeras:
            bloquear(f"'{cmd}': con COMMIT no se deshace nada (ADR 0024).")
        for palabra in primeras:
            if palabra in CONFIRMAN_SOLAS:
                bloquear(
                    f"'{cmd}': '{palabra}' confirma sola en MySQL y MariaDB, así que el ROLLBACK "
                    "no la deshace y la prueba no sería reversible (ADR 0024)."
                )
            if palabra not in SENTENCIAS_REVERSIBLES:
                bloquear(
                    f"'{cmd}': '{palabra}' no está entre lo que puede ir en una prueba reversible "
                    "(ADR 0024). Lo que ejecuta SQL guardado en una cadena no se puede comprobar."
                )
        return

    # Molde ESTRUCTURA: una migración. Se mira antes que los demás porque sus sentencias no caben
    # en ninguno y su lista es la más estrecha.
    if any(p in ("ALTER", "CREATE", "DROP") for p in primeras):
        for palabra, sentencia in sentencias:
            if palabra in SENTENCIAS_DE_LECTURA:
                continue
            cabecera = re.match(r"ALTER\s+TABLE\s+\S+\s+(.*)$", sentencia, re.IGNORECASE | re.DOTALL)
            if cabecera:
                for clausula in _clausulas(cabecera.group(1)):
                    if not any(re.match(c, clausula, re.IGNORECASE) for c in CLAUSULAS_DE_ALTER):
                        bloquear(
                            f"'{cmd}': la cláusula '{clausula[:40]}' no está permitida en un ALTER TABLE (ADR 0025). "
                            "Un ALTER admite varias separadas por comas y se comprueban todas."
                        )
                continue
            if not any(re.match(a, sentencia, re.IGNORECASE) for a in ACCIONES_DE_ESTRUCTURA):
                bloquear(
                    f"'{cmd}': '{palabra}' no es una de las acciones de estructura permitidas (ADR 0025). "
                    "Para vaciar o borrar una tabla están bin/cli scheme-drop y db-restore, que la instrucción nombra."
                )
        return

    # Molde ESCRITURA: lo que persiste. Solo datos.
    if any(p in ("INSERT", "UPDATE", "DELETE", "REPLACE") for p in primeras):
        for palabra, sentencia in sentencias:
            if palabra not in SENTENCIAS_DE_ESCRITURA:
                bloquear(f"'{cmd}': '{palabra}' no cabe en una escritura de datos (ADR 0025).")
            if re.search(r"\bINTO\s+(OUT|DUMP)FILE\b", sentencia, re.IGNORECASE):
                bloquear(f"'{cmd}': INTO OUTFILE escribe fuera de la base (ADR 0024).")
            #Un UPDATE o un DELETE sin WHERE alcanza la tabla entera, y casi nunca es lo que se quiere.
            #Si de verdad hace falta vaciarla, se dice envolviéndolo en BEGIN … ROLLBACK o con bin/cli.
            if palabra in ("UPDATE", "DELETE") and not re.search(r"\bWHERE\b", sentencia, re.IGNORECASE):
                bloquear(f"'{cmd}': '{palabra}' sin WHERE alcanza la tabla entera (ADR 0025).")
        return

    # Molde LECTURA.
    for palabra, sentencia in sentencias:
        if palabra not in SENTENCIAS_DE_LECTURA:
            bloquear(f"'{cmd}': '{palabra}' no es una lectura (ADR 0024). Para escribir, envuélvelo en BEGIN … ROLLBACK.")
        if re.search(r"\bINTO\b", sentencia, re.IGNORECASE):
            bloquear(f"'{cmd}': INTO saca el resultado fuera de la consulta: prohibido (ADR 0024).")


def revisar_bash(comando):
    if re.search(r"(curl|wget)\b[^|]*\|\s*(sudo\s+)?(ba|z|da)?sh\b", comando):
        bloquear("descargar y ejecutar un script remoto está prohibido.")
    if REDIRECCION_AL_SISTEMA.search(comando):
        bloquear("escribir en rutas del sistema está prohibido (40-salvaguardas.md §3).")
    # Un mensaje de commit puede llegar por heredoc o $(cat ...), fuera del
    # alcance del análisis por argumentos: se examina el comando entero, pero
    # solo ante una invocación real de "git [opciones globales] commit".
    if re.search(r"\bgit(\s+(-[cC]\s+\S+|--\S+))*\s+commit\b", comando):
        hallazgos = buscar(comando)
        if hallazgos:
            bloquear(f"el commit atribuye el trabajo a una IA ({hallazgos[0][1]!r}): prohibido (40-salvaguardas.md §5).")
    # Sustitución de comandos dentro de comillas dobles: shlex la deja como un
    # solo token y no llegaría a analizarse como comando.
    if re.search(r"\$\(\s*(sudo|su|ssh|scp|sftp|sshpass|rsync|mysql|mariadb|psql|gh)\b", comando):
        bloquear("sustitución de comandos con un comando prohibido.")

    # Directorio desde el que se resuelven las rutas relativas; los `cd` de la misma orden lo mueven.
    base = os.getcwd() if dentro_de(os.path.realpath(os.getcwd()), RAIZ) else RAIZ
    for seg in segmentos(comando):
        partes = tokens(seg)
        if not partes:
            continue
        cmd = os.path.basename(partes[0])
        args = partes[1:]
        # `php8.5 /usr/bin/composer update` es composer: sin esto, la regla de dependencias no lo ve.
        if cmd.startswith("php"):
            for k, a in enumerate(args):
                if os.path.basename(a) in ("composer", "composer.phar"):
                    cmd, args = "composer", args[k + 1:]
                    break

        if cmd == "cd":
            base = os.path.normpath(os.path.join(base, os.path.expanduser(args[0]))) if args else HOME
        if cmd not in ("echo", "printf") and any(_toca_secretos(a, base) for a in [partes[0]] + args):
            bloquear("secure-keys/ guarda claves del producto: un agente no la lee ni la lista (40-salvaguardas.md §7).")
        if cmd in CLIENTES_BD_LOCAL:
            revisar_cliente_bd(cmd, args)
            continue
        if cmd in PROHIBIDOS or cmd.startswith("mkfs"):
            #El segmento va en el mensaje: sin él, quien escribe una frase que EMPIEZA por una
            #palabra prohibida no sabe cuál es ni dónde. Costó dos intentos el 2026-09-26 (#610).
            donde = " ".join(partes)
            if len(donde) > 120:
                donde = donde[:117] + "..."
            bloquear(
                f"'{cmd}' está prohibido para agentes (40-salvaguardas.md). Pídeselo al PO.\n"
                f"  Lo disparó este trozo de la orden: {donde!r}"
            )
        if cmd == "rsync" and any(re.match(r"^[^/\s]+:", a) for a in args):
            bloquear("rsync a un host remoto: prohibido.")
        if cmd in ("pip", "pip3") and args[:1] in (["install"], ["uninstall"]):
            bloquear("instalar dependencias requiere permiso del PO (00-core.md).")
        if cmd.startswith("python") and "-m" in args:
            modulo = args[args.index("-m") + 1:][:1]
            orden = [a for a in sin_redirecciones(args[args.index("-m") + 2:]) if not a.startswith("-")][:1]
            # `python -m pip list` solo lee; instalar o desinstalar, no.
            if modulo == ["ensurepip"] or (modulo == ["pip"] and orden in (["install"], ["uninstall"])):
                bloquear("instalar dependencias requiere permiso del PO (00-core.md).")
        if cmd in ("npm", "pnpm", "yarn", "bun") and (
            any(a in ("-g", "--global", "global") for a in args) or args[:1] in (["install"], ["i"], ["add"], ["update"], ["ci"])
        ):
            bloquear("instalar o actualizar dependencias requiere permiso del PO (00-core.md).")
        if cmd == "composer" and args[:1] in (["global"], ["require"], ["install"], ["update"], ["remove"], ["upgrade"]):
            # Excepción (ADR 0007): actualizar las herramientas de análisis, nombradas una a una.
            # `phpstan/phpstan:2.2.12` fija la versión: cuenta el nombre. Una redirección
            # (`> ruta`, `2>&1`) no es un paquete.
            paquetes = [a.split(":", 1)[0] for a in sin_redirecciones(args[1:]) if not a.startswith("-")]
            permitido = args[:1] == ["update"] and paquetes and all(
                p in HERRAMIENTAS_DE_ANALISIS or p in PAQUETES_HERMANOS_COMPOSER for p in paquetes
            )
            # ADR 0008: `piecesphp/*` solo dentro de un paquete hermano, cuyo lock no se versiona.
            # En este repositorio `src/composer.lock` SÍ se versiona: sería una dependencia del producto.
            if permitido and any(p in PAQUETES_HERMANOS_COMPOSER for p in paquetes):
                dirs = [a.split("=", 1)[1] for a in args if a.startswith("--working-dir=")]
                destino = os.path.realpath(os.path.join(RAIZ, os.path.expanduser(dirs[-1]))) if dirs else None
                en_hermano = destino is not None and any(destino == os.path.realpath(h) for h in HERMANOS)
                # ADR 0017: en el framework, solo `piecesphp/*` y sin arrastrar dependencias de terceros.
                en_framework = (
                    destino == os.path.realpath(os.path.join(RAIZ, "src"))
                    and all(p in PAQUETES_HERMANOS_COMPOSER for p in paquetes)
                    and not any(a in ("-w", "-W", "--with-dependencies", "--with-all-dependencies") for a in args)
                )
                permitido = en_hermano or en_framework
            if not permitido:
                bloquear("instalar o actualizar dependencias requiere permiso del PO (00-core.md; excepción de análisis: ADR 0007).")
        if cmd == "chmod" and any(RUTAS_DEL_SISTEMA.search(" " + a) for a in args):
            bloquear("cambiar permisos en rutas del sistema está prohibido.")
        if cmd == "git":
            revisar_git(args)
        if cmd == "rm":
            revisar_rm(args)
        if cmd in ("mv", "cp", "ln", "install", "tee", "truncate", "touch", "mkdir"):
            destinos = destinos_de_escritura(args)
            if destinos and os.path.isabs(destinos[-1]) and not ruta_escribible(destinos[-1]):
                bloquear(f"{cmd} hacia fuera del repositorio y de /tmp ({destinos[-1]!r}): prohibido.")
        if cmd not in ("echo", "printf") and any(CONFIG_DE_GIT.search(a) for a in args):
            bloquear(".git/config lleva las credenciales de los remotos: no se lee ni se copia (18 T4).")
        if GUIONES_VETADOS.search(partes[0]) or (
            cmd in ("bash", "sh", "dash", "zsh", "source", ".") and "-n" not in args and any(GUIONES_VETADOS.search(a) for a in args)
        ):
            bloquear("bin/push-all y permissions-and-property.sh no los ejecuta un agente (40-salvaguardas.md).")


def revisar_escritura(entrada):
    ruta = entrada.get("file_path") or entrada.get("notebook_path") or ""
    if not ruta:
        return
    if not ruta_escribible(ruta):
        bloquear(f"escritura fuera del repositorio, de /tmp y de los repositorios hermanos ({ruta}): prohibido.")
    absoluta = os.path.realpath(ruta)
    if not dentro_de(absoluta, RAIZ):
        return
    relativa = os.path.relpath(absoluta, RAIZ)
    if relativa == ".git" or relativa.startswith(".git" + os.sep):
        bloquear("el directorio .git no se escribe a mano.")
    if not es_entregable(relativa):
        return
    textos = [entrada.get("content") or "", entrada.get("new_string") or "", entrada.get("new_source") or ""]
    textos += [e.get("new_string", "") for e in entrada.get("edits") or []]
    hallazgos = buscar("\n".join(textos))
    if hallazgos:
        bloquear(f"{relativa} es entregable y el texto atribuye el trabajo a una IA ({hallazgos[0][1]!r}): prohibido (40-salvaguardas.md §5).")


def main():
    try:
        datos = json.load(sys.stdin)
    except (json.JSONDecodeError, ValueError):
        print("guardia: entrada ilegible, se bloquea por prudencia.", file=sys.stderr)
        return 2
    herramienta = datos.get("tool_name", "")
    entrada = datos.get("tool_input") or {}
    try:
        if herramienta == "Bash":
            revisar_bash(entrada.get("command", ""))
        elif herramienta in ("Write", "Edit", "MultiEdit", "NotebookEdit"):
            revisar_escritura(entrada)
    except Bloqueo as b:
        print(f"Bloqueado por la guarda del proyecto: {b}", file=sys.stderr)
        return 2
    return 0


if __name__ == "__main__":
    sys.exit(main())
