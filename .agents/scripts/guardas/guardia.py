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
    "mysql", "mariadb", "mysqldump", "psql", "mongo", "mongosh", "redis-cli", "sqlcmd",
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


def segmentos(comando):
    """
    Devuelve una lista de comandos simples, cada uno como lista de tokens.

    Parte por líneas y, dentro de cada una, por los operadores de control
    (; && || | & paréntesis) que estén FUERA de comillas: un '|' dentro del
    patrón de un grep no separa nada. Las líneas de un heredoc se analizan
    como si fueran comandos: da falsos positivos posibles, nunca negativos.
    """
    resultado = []
    for linea in comando.replace("`", "\n").splitlines():
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


# ADR 0019: en este repositorio, arquitecto y coder etiquetan solo pre-versiones; una estable la decide el PO.
PRE_VERSION = re.compile(r"v\d+\.\d+\.\d+-(alpha|beta|rc)\.\d+")
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
        pre_version = en_framework and PRE_VERSION.fullmatch(nombre) is not None
        if (escribe or not listar) and not en_paquete and not pre_version:
            bloquear(
                "en este repositorio solo se etiquetan pre-versiones vX.Y.Z-alpha|beta|rc.N (ADR 0019); una versión "
                "estable la decide el PO. En los cuatro paquetes sí se etiqueta (P19)."
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
        if cmd in PROHIBIDOS or cmd.startswith("mkfs"):
            bloquear(f"'{cmd}' está prohibido para agentes (40-salvaguardas.md). Pídeselo al PO.")
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
            destinos = [a for a in args if not a.startswith("-")]
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
