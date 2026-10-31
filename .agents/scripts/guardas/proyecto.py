#!/usr/bin/env python3
"""Valida la configuración propia del proyecto.

Uso:
  python3 -B .agents/scripts/guardas/proyecto.py [--raiz DIR]
"""
import argparse
import json
import os
import sys

RUTA_CONFIG = ".agents/project-architect-coder.json"
GESTORES = {"pip", "npm", "pnpm", "yarn", "bun", "composer", "cargo", "gem", "bundle", "go"}
RAIZ_CLAVES = {"format", "project", "sessions", "guard", "deliverables", "budget"}
SESSIONS_CLAVES = {"architect", "coder", "mode"}
MODOS = ("dos", "una")
MODO_POR_OMISION = "dos"
CODER_SUBAGENTE = "coder"
GUARD_CLAVES = {"sibling_repositories", "banned_scripts", "extra_banned_commands",
                 "forbidden_reads", "allowed_dependencies", "allowed_commands", "writable_paths"}
SIBLING_CLAVES = {"path", "can_tag", "creatable_branches"}
DEPENDENCIA_CLAVES = {"manager", "command", "packages", "directories", "reason"}
ALLOWED_COMMAND_CLAVES = {"command", "reason"}
WRITABLE_PATH_CLAVES = {"path", "reason"}
DELIVERABLES_CLAVES = {"prefixes", "files", "excluded"}
BUDGET_CLAVES = {"rules_words"}

# Duplicado de guardia.py (PROHIBIDOS y el conjunto de escalada): proyecto.py no puede
# importar de guardia.py, que ya importa de aquí.
COMANDOS_PROHIBIDOS = {
    "sudo", "su", "pkexec", "doas",
    "ssh", "scp", "sftp", "sshpass", "mosh", "telnet", "ftp", "lftp", "ncftp", "gh", "glab",
    "mysql", "mariadb", "mysqldump", "psql", "mongo", "mongosh", "sqlite3", "redis-cli", "sqlcmd",
    "apt", "apt-get", "aptitude", "dpkg", "snap", "flatpak", "yum", "dnf",
    "systemctl", "service", "crontab", "mkfs", "fdisk", "parted", "dd",
    "shutdown", "reboot", "poweroff", "halt", "chown", "useradd", "userdel",
    "usermod", "passwd", "visudo", "iptables", "ufw", "nft",
}
COMANDOS_ESCALADA = {"sudo", "su", "pkexec", "doas", "visudo", "passwd",
                      "useradd", "userdel", "usermod"}


def cargar(raiz):
    ruta = os.path.join(raiz, ".agents", "project-architect-coder.json")

    if not os.path.lexists(ruta):
        return None, [_err("no existe")]

    try:
        with open(ruta, "r", encoding="utf-8") as f:
            datos = json.loads(f.read())
    except (OSError, ValueError):
        return None, [_err("JSON inválido")]

    formato = datos.get("format") if isinstance(datos, dict) else None
    if not isinstance(datos, dict) or type(formato) is not int or formato != 1:
        return None, [_err("formato")]

    errores = _validar_raiz(datos)
    if errores:
        return None, errores
    return datos, []


def _err(mensaje, r=None):
    if r is None:
        return "%s: %s" % (RUTA_CONFIG, mensaje)
    return "%s: %s %s" % (RUTA_CONFIG, mensaje, r)


def _ruta(padre, hijo):
    return "%s.%s" % (padre, hijo) if padre else hijo


def _indice(base, i):
    return "%s[%d]" % (base, i)


def _objeto_extra(obj, claves_validas, r):
    extra = set(obj.keys()) - claves_validas
    return [_err("clave desconocida", _ruta(r, k)) for k in sorted(extra)]


def _validar_raiz(datos):
    errores = _objeto_extra(datos, RAIZ_CLAVES, "")

    if "project" not in datos:
        errores.append(_err("falta el campo", "project"))
    else:
        errores += _validar_texto(datos["project"], "project")

    if "sessions" not in datos:
        errores.append(_err("falta el campo", "sessions"))
    else:
        errores += _validar_sessions(datos["sessions"], "sessions")

    if "guard" not in datos:
        errores.append(_err("falta el campo", "guard"))
    else:
        errores += _validar_guard(datos["guard"], "guard")

    if "deliverables" not in datos:
        errores.append(_err("falta el campo", "deliverables"))
    else:
        errores += _validar_deliverables(datos["deliverables"], "deliverables")

    if "budget" in datos:
        errores += _validar_budget(datos["budget"], "budget")

    return errores


def _validar_texto(valor, r):
    if not isinstance(valor, str):
        return [_err("tipo inválido", r)]
    if not valor.strip():
        return [_err("vacío", r)]
    return []


def modo(config):
    """El modo del protocolo de una configuración ya validada: «dos» o «una».

    Lo consultan el instalador, el presupuesto, la guarda y el generador: la
    lógica vive aquí sola para que no haya dos respuestas a la misma pregunta.
    """
    if not isinstance(config, dict):
        return MODO_POR_OMISION
    sessions = config.get("sessions")
    if not isinstance(sessions, dict):
        return MODO_POR_OMISION
    valor = sessions.get("mode")
    return valor if valor in MODOS else MODO_POR_OMISION


def _validar_sessions(obj, r):
    if not isinstance(obj, dict):
        return [_err("tipo inválido", r)]
    errores = _objeto_extra(obj, SESSIONS_CLAVES, r)
    for clave in ("architect", "coder"):
        if clave not in obj:
            errores.append(_err("falta el campo", _ruta(r, clave)))
        else:
            errores += _validar_texto(obj[clave], _ruta(r, clave))

    # «mode» es opcional: si falta, el modo es «dos», así que los proyectos ya
    # instalados siguen valiendo sin tocarlos.
    modo_valido = True
    if "mode" in obj:
        if not isinstance(obj["mode"], str):
            errores.append(_err("tipo inválido", _ruta(r, "mode")))
            modo_valido = False
        elif obj["mode"] not in MODOS:
            errores.append(_err("modo desconocido", _ruta(r, "mode")))
            modo_valido = False

    # En modo «una» el coder no es una sesión: es el subagente «coder», y su
    # nombre tiene que ser exactamente ese para que la guarda lo reconozca.
    if modo_valido and obj.get("mode") == "una" and obj.get("coder") != CODER_SUBAGENTE:
        errores.append(_err(
            'en modo una tiene que ser "%s"' % CODER_SUBAGENTE, _ruta(r, "coder")))

    return errores


def _validar_guard(obj, r):
    if not isinstance(obj, dict):
        return [_err("tipo inválido", r)]
    errores = _objeto_extra(obj, GUARD_CLAVES, r)

    campo = "sibling_repositories"
    if campo not in obj:
        errores.append(_err("falta el campo", _ruta(r, campo)))
    else:
        errores += _validar_siblings(obj[campo], _ruta(r, campo))

    campo = "banned_scripts"
    if campo not in obj:
        errores.append(_err("falta el campo", _ruta(r, campo)))
    else:
        errores += _validar_lista_rutas(obj[campo], _ruta(r, campo))

    campo = "extra_banned_commands"
    if campo not in obj:
        errores.append(_err("falta el campo", _ruta(r, campo)))
    else:
        errores += _validar_lista_ordenes(obj[campo], _ruta(r, campo))

    campo = "forbidden_reads"
    if campo not in obj:
        errores.append(_err("falta el campo", _ruta(r, campo)))
    else:
        errores += _validar_forbidden_reads(obj[campo], _ruta(r, campo))

    campo = "allowed_dependencies"
    if campo not in obj:
        errores.append(_err("falta el campo", _ruta(r, campo)))
    else:
        errores += _validar_dependencias(obj[campo], _ruta(r, campo))

    campo = "allowed_commands"
    if campo in obj:
        errores += _validar_allowed_commands(obj[campo], _ruta(r, campo))

    campo = "writable_paths"
    if campo in obj:
        errores += _validar_writable_paths(obj[campo], _ruta(r, campo))

    if (isinstance(obj.get("extra_banned_commands"), list)
            and isinstance(obj.get("allowed_commands"), list)):
        prohibidas = {c for c in obj["extra_banned_commands"] if isinstance(c, str)}
        permitidas = {a["command"] for a in obj["allowed_commands"]
                      if isinstance(a, dict) and isinstance(a.get("command"), str)}
        for nombre in sorted(prohibidas & permitidas):
            errores.append(_err(
                "a la vez en extra_banned_commands y en allowed_commands", nombre))

    return errores


def _validar_siblings(lista, r):
    if not isinstance(lista, list):
        return [_err("tipo inválido", r)]
    errores = []
    vistos = set()
    for i, item in enumerate(lista):
        ri = _indice(r, i)
        if not isinstance(item, dict):
            errores.append(_err("tipo inválido", ri))
            continue
        errores += _objeto_extra(item, SIBLING_CLAVES, ri)

        if "path" not in item:
            errores.append(_err("falta el campo", _ruta(ri, "path")))
        else:
            valor = item["path"]
            if not isinstance(valor, str):
                errores.append(_err("tipo inválido", _ruta(ri, "path")))
            elif not _ruta_hermana_valida(valor):
                errores.append(_err("ruta inválida", _ruta(ri, "path")))
            elif valor in vistos:
                errores.append(_err("duplicado", _ruta(ri, "path")))
            else:
                vistos.add(valor)

        if "can_tag" not in item:
            errores.append(_err("falta el campo", _ruta(ri, "can_tag")))
        elif type(item["can_tag"]) is not bool:
            errores.append(_err("tipo inválido", _ruta(ri, "can_tag")))

        campo = "creatable_branches"
        if campo not in item:
            errores.append(_err("falta el campo", _ruta(ri, campo)))
        else:
            errores += _validar_lista_textos(item[campo], _ruta(ri, campo))
    return errores


def _validar_lista_textos(lista, r):
    if not isinstance(lista, list):
        return [_err("tipo inválido", r)]
    errores = []
    for i, item in enumerate(lista):
        errores += _validar_texto(item, _indice(r, i))
    return errores


def _validar_lista_rutas(lista, r, terminar_en_barra=None):
    if not isinstance(lista, list):
        return [_err("tipo inválido", r)]
    errores = []
    for i, item in enumerate(lista):
        ri = _indice(r, i)
        if not isinstance(item, str):
            errores.append(_err("tipo inválido", ri))
            continue
        valido = _ruta_interna_valida(item)
        if valido and terminar_en_barra is True and not item.endswith("/"):
            valido = False
        if valido and terminar_en_barra is False and item.endswith("/"):
            valido = False
        if not valido:
            errores.append(_err("ruta inválida", ri))
    return errores


def _forbidden_read_valida(ruta):
    if not isinstance(ruta, str):
        return False
    if ruta.startswith("~/"):
        return _ruta_interna_valida(ruta[2:])
    if ruta.startswith("~"):
        return False
    return _ruta_interna_valida(ruta)


def _validar_forbidden_reads(lista, r):
    if not isinstance(lista, list):
        return [_err("tipo inválido", r)]
    errores = []
    for i, item in enumerate(lista):
        ri = _indice(r, i)
        if not isinstance(item, str):
            errores.append(_err("tipo inválido", ri))
            continue
        if not _forbidden_read_valida(item):
            errores.append(_err("ruta inválida", ri))
    return errores


def _validar_lista_directorios(lista, r):
    if not isinstance(lista, list):
        return [_err("tipo inválido", r)]
    errores = []
    for i, item in enumerate(lista):
        ri = _indice(r, i)
        if not isinstance(item, str):
            errores.append(_err("tipo inválido", ri))
            continue
        if not (_ruta_interna_valida(item) or _ruta_hermana_valida(item)):
            errores.append(_err("ruta inválida", ri))
    return errores


def _validar_lista_ordenes(lista, r):
    if not isinstance(lista, list):
        return [_err("tipo inválido", r)]
    errores = []
    for i, item in enumerate(lista):
        errores += _validar_orden(item, _indice(r, i))
    return errores


def _validar_orden(valor, r):
    if not isinstance(valor, str):
        return [_err("tipo inválido", r)]
    if not valor.strip():
        return [_err("vacío", r)]
    if any(c.isspace() for c in valor) or "/" in valor:
        return [_err("nombre de orden inválido", r)]
    return []


def _validar_manager(valor, r):
    if not isinstance(valor, str):
        return [_err("tipo inválido", r)]
    if not valor.strip():
        return [_err("vacío", r)]
    if valor not in GESTORES:
        return [_err("gestor desconocido", r)]
    return []


def _validar_packages(lista, r):
    if not isinstance(lista, list):
        return [_err("tipo inválido", r)]
    if not lista:
        return [_err("vacío", r)]
    errores = []
    for i, item in enumerate(lista):
        errores += _validar_texto(item, _indice(r, i))
    return errores


def _validar_dependencias(lista, r):
    if not isinstance(lista, list):
        return [_err("tipo inválido", r)]
    errores = []
    for i, item in enumerate(lista):
        ri = _indice(r, i)
        if not isinstance(item, dict):
            errores.append(_err("tipo inválido", ri))
            continue
        errores += _objeto_extra(item, DEPENDENCIA_CLAVES, ri)

        campo = "manager"
        if campo not in item:
            errores.append(_err("falta el campo", _ruta(ri, campo)))
        else:
            errores += _validar_manager(item[campo], _ruta(ri, campo))

        campo = "command"
        if campo not in item:
            errores.append(_err("falta el campo", _ruta(ri, campo)))
        else:
            errores += _validar_orden(item[campo], _ruta(ri, campo))

        campo = "packages"
        if campo not in item:
            errores.append(_err("falta el campo", _ruta(ri, campo)))
        else:
            errores += _validar_packages(item[campo], _ruta(ri, campo))

        campo = "directories"
        if campo not in item:
            errores.append(_err("falta el campo", _ruta(ri, campo)))
        else:
            errores += _validar_lista_directorios(item[campo], _ruta(ri, campo))

        campo = "reason"
        if campo not in item:
            errores.append(_err("falta el campo", _ruta(ri, campo)))
        else:
            errores += _validar_texto(item[campo], _ruta(ri, campo))
    return errores


def _writable_path_valida(ruta):
    """Solo carpetas de la carpeta personal, enteras y sin comodines: ni `~` a secas ni rutas del sistema."""
    if not isinstance(ruta, str) or not ruta.startswith("~/") or any(c in ruta for c in "*?["):
        return False
    return _ruta_interna_valida(ruta[2:])


def _validar_writable_paths(lista, r):
    if not isinstance(lista, list):
        return [_err("tipo inválido", r)]
    errores = []
    for i, item in enumerate(lista):
        ri = _indice(r, i)
        if not isinstance(item, dict):
            errores.append(_err("tipo inválido", ri))
            continue
        errores += _objeto_extra(item, WRITABLE_PATH_CLAVES, ri)
        for campo in ("path", "reason"):
            if campo not in item:
                errores.append(_err("falta el campo", _ruta(ri, campo)))
        if "path" in item and not _writable_path_valida(item["path"]):
            errores.append(_err("ruta inválida", _ruta(ri, "path")))
        if "reason" in item:
            errores += _validar_texto(item["reason"], _ruta(ri, "reason"))
    return errores


def _validar_allowed_commands(lista, r):
    if not isinstance(lista, list):
        return [_err("tipo inválido", r)]
    errores = []
    for i, item in enumerate(lista):
        ri = _indice(r, i)
        if not isinstance(item, dict):
            errores.append(_err("tipo inválido", ri))
            continue
        errores += _objeto_extra(item, ALLOWED_COMMAND_CLAVES, ri)

        campo = "command"
        if campo not in item:
            errores.append(_err("falta el campo", _ruta(ri, campo)))
        else:
            valor = item[campo]
            if not isinstance(valor, str):
                errores.append(_err("tipo inválido", _ruta(ri, campo)))
            elif not valor.strip():
                errores.append(_err("vacío", _ruta(ri, campo)))
            elif valor in COMANDOS_ESCALADA:
                errores.append(_err("orden de escalada: no se puede abrir", _ruta(ri, campo)))
            elif valor not in COMANDOS_PROHIBIDOS:
                errores.append(_err("orden no prohibida", _ruta(ri, campo)))

        campo = "reason"
        if campo not in item:
            errores.append(_err("falta el campo", _ruta(ri, campo)))
        else:
            errores += _validar_texto(item[campo], _ruta(ri, campo))
    return errores


def _validar_deliverables(obj, r):
    if not isinstance(obj, dict):
        return [_err("tipo inválido", r)]
    errores = _objeto_extra(obj, DELIVERABLES_CLAVES, r)

    campo = "prefixes"
    if campo not in obj:
        errores.append(_err("falta el campo", _ruta(r, campo)))
    else:
        errores += _validar_lista_rutas(obj[campo], _ruta(r, campo), terminar_en_barra=True)

    campo = "files"
    if campo not in obj:
        errores.append(_err("falta el campo", _ruta(r, campo)))
    else:
        errores += _validar_lista_rutas(obj[campo], _ruta(r, campo), terminar_en_barra=False)

    campo = "excluded"
    if campo not in obj:
        errores.append(_err("falta el campo", _ruta(r, campo)))
    else:
        errores += _validar_lista_rutas(obj[campo], _ruta(r, campo))

    return errores


def _validar_budget(obj, r):
    if not isinstance(obj, dict):
        return [_err("tipo inválido", r)]
    errores = _objeto_extra(obj, BUDGET_CLAVES, r)

    campo = "rules_words"
    if campo not in obj:
        errores.append(_err("falta el campo", _ruta(r, campo)))
    else:
        valor = obj[campo]
        if type(valor) is not int or valor < 1:
            errores.append(_err("tipo inválido", _ruta(r, campo)))

    return errores


def _ruta_interna_valida(ruta):
    if not isinstance(ruta, str) or ruta == "":
        return False
    if ruta.startswith("/") or "\\" in ruta:
        return False
    segmentos = ruta.split("/")
    if segmentos and segmentos[-1] == "":
        segmentos = segmentos[:-1]
    if not segmentos:
        return False
    for segmento in segmentos:
        if segmento in ("", ".", ".."):
            return False
    return True


def _ruta_hermana_valida(ruta):
    if not isinstance(ruta, str) or not ruta.startswith("../"):
        return False
    if "\\" in ruta or ruta.endswith("/"):
        return False
    resto = ruta[3:]
    if resto == "":
        return False
    for segmento in resto.split("/"):
        if segmento in ("", ".", ".."):
            return False
    return True


def main():
    aqui = os.path.dirname(os.path.abspath(__file__))
    raiz_por_omision = os.path.dirname(os.path.dirname(os.path.dirname(aqui)))

    analizador = argparse.ArgumentParser(
        description="Valida la configuración propia del proyecto.")
    analizador.add_argument("--raiz", default=raiz_por_omision,
                             help="raíz del proyecto (por omisión, la de esta plantilla)")
    args = analizador.parse_args()

    config, errores = cargar(args.raiz)

    if errores:
        for error in errores:
            print("ERROR %s" % error, file=sys.stderr)
        palabra = "error" if len(errores) == 1 else "errores"
        print("PROYECTO ROTO: %d %s" % (len(errores), palabra), file=sys.stderr)
        sys.exit(1)

    print("PROYECTO OK: %s" % config["project"])
    sys.exit(0)


if __name__ == "__main__":
    main()
