#!/usr/bin/env python3
"""
Tareas del PO en su Vikunja (PO, 2026-10-05), por su API REST.

Credenciales en .agents/local/vikunja.env, que git ignora: VIKUNJA_URL, VIKUNJA_TOKEN,
VIKUNJA_PROJECT y VIKUNJA_VIEW. El token no se imprime nunca.

Uso:
  python3 -B .agents/scripts/vikunja.py proyecto            # proyecto, vistas y columnas
  python3 -B .agents/scripts/vikunja.py tareas              # tareas del proyecto (id, hecha, título)
  python3 -B .agents/scripts/vikunja.py crear <lote.json>   # crea las tareas del lote
  python3 -B .agents/scripts/vikunja.py borrar <id> [<id>…]  # borra tareas
  python3 -B .agents/scripts/vikunja.py mensaje "<tarea>" <archivo>   # comentario en esa tarea (la crea si falta)
  python3 -B .agents/scripts/vikunja.py revisar [--sin-guardar]   # lo que cambió el PO desde la última revisión

`revisar` compara con la foto de .agents/local/vikunja-estado.json (ignorada por git): tareas nuevas o borradas,
marcadas o desmarcadas como hechas, renombradas o editadas, etiquetas cambiadas y comentarios nuevos. Lo que hace este
guion (crear, borrar, comentar) se apunta en la foto al hacerlo, así que no reaparece como cambio del PO. Sin
cambios, imprime una sola línea: revisar es barato.

Para el PO, Vikunja lleva SOLO lo que él tiene que ver o hacer (PO, 2026-10-06), y los mensajes intermedios del
arquitecto como comentarios de la tarea «Mensajes del arquitecto · AAAA-MM-DD». Los reportes finales van por correo
con mail-notify.

Formato del lote: [{"title": "...", "description": "<p>...</p>", "labels": ["PiecesPHP"],
"priority": 0-5, "bucket": "<nombre de columna, opcional>"}]. Una tarea cuyo título ya existe
en el proyecto no se crea otra vez.
"""
from html import escape, unescape
import json
import os
import re
import sys
import urllib.error
import urllib.request

AQUI = os.path.dirname(os.path.abspath(__file__))
ENV = os.path.join(AQUI, "..", "local", "vikunja.env")
ESTADO = os.path.join(AQUI, "..", "local", "vikunja-estado.json")


def configuracion():
    if not os.path.isfile(ENV):
        sys.exit(f"Falta {os.path.relpath(ENV)}: VIKUNJA_URL, VIKUNJA_TOKEN, VIKUNJA_PROJECT, VIKUNJA_VIEW.")
    datos = {}
    with open(ENV, encoding="utf-8") as f:
        for linea in f:
            linea = linea.strip()
            if linea and not linea.startswith("#") and "=" in linea:
                clave, valor = linea.split("=", 1)
                datos[clave.strip()] = valor.strip()
    faltan = [c for c in ("VIKUNJA_URL", "VIKUNJA_TOKEN", "VIKUNJA_PROJECT") if not datos.get(c)]
    if faltan:
        sys.exit(f"Faltan en {os.path.relpath(ENV)}: {', '.join(faltan)}")
    return datos


def pedir(cfg, metodo, ruta, cuerpo=None, tolerante=False):
    url = cfg["VIKUNJA_URL"].rstrip("/") + "/api/v1" + ruta
    datos = json.dumps(cuerpo).encode() if cuerpo is not None else None
    peticion = urllib.request.Request(url, data=datos, method=metodo)
    peticion.add_header("Authorization", "Bearer " + cfg["VIKUNJA_TOKEN"])
    peticion.add_header("Content-Type", "application/json")
    try:
        with urllib.request.urlopen(peticion, timeout=30) as r:
            return json.loads(r.read() or b"null")
    except urllib.error.HTTPError as e:
        if tolerante:
            return None
        # El cuerpo del error de Vikunja no lleva el token: se puede mostrar.
        sys.exit(f"{metodo} {ruta} → HTTP {e.code}: {e.read().decode(errors='replace')[:300]}")


def todas(cfg, ruta):
    resultado, pagina = [], 1
    while True:
        separador = "&" if "?" in ruta else "?"
        lote = pedir(cfg, "GET", f"{ruta}{separador}page={pagina}&per_page=50") or []
        resultado += lote
        if len(lote) < 50:
            return resultado
        pagina += 1


def leer_estado():
    if not os.path.isfile(ESTADO):
        return {"tareas": {}, "propios": []}
    with open(ESTADO, encoding="utf-8") as f:
        return json.load(f)


def guardar_estado(estado):
    with open(ESTADO, "w", encoding="utf-8") as f:
        json.dump(estado, f, ensure_ascii=False, indent=1)


def foto_tarea(cfg, t):
    comentarios = pedir(cfg, "GET", f"/tasks/{t['id']}/comments", tolerante=True) or []
    return {
        "titulo": t["title"],
        "hecha": bool(t.get("done")),
        "actualizada": t.get("updated"),
        "descripcion": t.get("description") or "",
        "etiquetas": sorted(e["title"] for e in (t.get("labels") or [])),
        "comentarios": {str(c["id"]): c.get("comment", "") for c in comentarios},
    }


def apuntar_propio(tarea_id=None, comentario_id=None):
    """Lo que hace el guion no es un cambio del PO: se apunta en la foto."""
    estado = leer_estado()
    if comentario_id is not None:
        estado["propios"].append(str(comentario_id))
    if tarea_id is not None:
        estado["propios"].append(f"tarea:{tarea_id}")
    guardar_estado(estado)


def texto_plano(html_texto):
    return re.sub(r"\s+", " ", unescape(re.sub(r"<[^>]+>", " ", html_texto))).strip()


def revisar(cfg, guardar=True):
    estado = leer_estado()
    antes, propios = estado["tareas"], set(estado["propios"])
    ahora = {str(t["id"]): foto_tarea(cfg, t) for t in todas(cfg, f"/projects/{cfg['VIKUNJA_PROJECT']}/tasks")}
    cambios = []
    for i, t in ahora.items():
        a = antes.get(i)
        if a is None:
            if f"tarea:{i}" not in propios:
                cambios.append(f"NUEVA {i}: {t['titulo']}")
            a = {"titulo": t["titulo"], "hecha": False, "descripcion": t["descripcion"], "etiquetas": t["etiquetas"],
                 "comentarios": {}}
        if a["hecha"] != t["hecha"]:
            cambios.append(f"{'HECHA' if t['hecha'] else 'REABIERTA'} {i}: {t['titulo']}")
        if a["titulo"] != t["titulo"]:
            cambios.append(f"RENOMBRADA {i}: «{a['titulo']}» → «{t['titulo']}»")
        if a["descripcion"] != t["descripcion"]:
            cambios.append(f"DESCRIPCIÓN EDITADA {i}: {texto_plano(t['descripcion'])[:300]}")
        if a["etiquetas"] != t["etiquetas"]:
            cambios.append(f"ETIQUETAS {i}: {', '.join(a['etiquetas']) or '—'} → {', '.join(t['etiquetas']) or '—'}")
        for c, texto in t["comentarios"].items():
            if c not in a["comentarios"] and c not in propios:
                cambios.append(f"COMENTARIO {i} ({t['titulo']}): {texto_plano(texto)[:500]}")
            elif c in a["comentarios"] and a["comentarios"][c] != texto and c not in propios:
                cambios.append(f"COMENTARIO EDITADO {i}: {texto_plano(texto)[:500]}")
        for c in set(a["comentarios"]) - set(t["comentarios"]):
            cambios.append(f"COMENTARIO BORRADO {i}: {texto_plano(a['comentarios'][c])[:120]}")
    for i in set(antes) - set(ahora):
        cambios.append(f"BORRADA {i}: {antes[i]['titulo']}")
    for linea in cambios:
        print(linea)
    print(f"vikunja: {len(cambios)} cambio(s) desde la última revisión, {len(ahora)} tarea(s)")
    if guardar:
        estado["tareas"] = ahora
        estado["propios"] = [x for x in propios if x.startswith("tarea:") and x[6:] in ahora] + [
            c for t in ahora.values() for c in t["comentarios"] if c in propios]
        guardar_estado(estado)


def proyecto(cfg):
    p = cfg["VIKUNJA_PROJECT"]
    info = pedir(cfg, "GET", f"/projects/{p}")
    print(f"Proyecto {p}: {info.get('title')}")
    for v in pedir(cfg, "GET", f"/projects/{p}/views") or []:
        print(f"  vista {v['id']}: {v['title']} ({v.get('view_kind')})")
        if v.get("view_kind") == "kanban":
            columnas = pedir(cfg, "GET", f"/projects/{p}/views/{v['id']}/buckets", tolerante=True)
            if columnas is None:
                print("    columnas: el token no tiene permiso para leerlas")
            for b in columnas or []:
                print(f"    columna {b['id']}: {b['title']}")


def tareas(cfg):
    for t in todas(cfg, f"/projects/{cfg['VIKUNJA_PROJECT']}/tasks"):
        print(f"{t['id']:>6}  {'[X]' if t.get('done') else '[ ]'}  {t['title']}")


def crear(cfg, archivo):
    with open(archivo, encoding="utf-8") as f:
        lote = json.load(f)
    p = cfg["VIKUNJA_PROJECT"]
    existentes = {t["title"] for t in todas(cfg, f"/projects/{p}/tasks")}
    etiquetas = {e["title"]: e["id"] for e in todas(cfg, "/labels")}
    columnas = {}
    if cfg.get("VIKUNJA_VIEW") and any(i.get("bucket") for i in lote):
        columnas = {b["title"]: b["id"] for b in pedir(cfg, "GET", f"/projects/{p}/views/{cfg['VIKUNJA_VIEW']}/buckets") or []}
    creadas = saltadas = 0
    for item in lote:
        if item["title"] in existentes:
            print(f"YA EXISTE: {item['title']}")
            saltadas += 1
            continue
        cuerpo = {"title": item["title"], "description": item.get("description", ""), "priority": item.get("priority", 0)}
        tarea = pedir(cfg, "PUT", f"/projects/{p}/tasks", cuerpo)
        for nombre in item.get("labels", []):
            if nombre not in etiquetas:
                etiquetas[nombre] = pedir(cfg, "PUT", "/labels", {"title": nombre})["id"]
            pedir(cfg, "PUT", f"/tasks/{tarea['id']}/labels", {"label_id": etiquetas[nombre]})
        if item.get("bucket"):
            if item["bucket"] not in columnas:
                sys.exit(f"La columna «{item['bucket']}» no existe; hay: {', '.join(columnas) or 'ninguna'}")
            pedir(cfg, "POST", f"/projects/{p}/views/{cfg['VIKUNJA_VIEW']}/buckets/{columnas[item['bucket']]}/tasks",
                  {"task_id": tarea["id"]})
        apuntar_propio(tarea_id=tarea["id"])
        print(f"CREADA {tarea['id']}: {item['title']}")
        creadas += 1
    print(f"vikunja: {creadas} creada(s), {saltadas} ya existían, de {len(lote)}")


def borrar(cfg, ids):
    for i in ids:
        pedir(cfg, "DELETE", f"/tasks/{int(i)}")
        estado = leer_estado()
        estado["tareas"].pop(str(int(i)), None)
        guardar_estado(estado)
        print(f"BORRADA {i}")
    print(f"vikunja: {len(ids)} borrada(s)")


def mensaje(cfg, titulo_tarea, archivo):
    """Un mensaje al PO: comentario en la tarea del día; la crea si no existe."""
    with open(archivo, encoding="utf-8") as f:
        texto = f.read()
    p = cfg["VIKUNJA_PROJECT"]
    tarea = next((t for t in todas(cfg, f"/projects/{p}/tasks") if t["title"] == titulo_tarea), None)
    if tarea is None:
        tarea = pedir(cfg, "PUT", f"/projects/{p}/tasks", {"title": titulo_tarea, "priority": 3})
        apuntar_propio(tarea_id=tarea["id"])
        print(f"CREADA {tarea['id']}: {titulo_tarea}")
    html = "".join(f"<p>{escape(linea)}</p>" if linea.strip() else "" for linea in texto.split("\n"))
    comentario = pedir(cfg, "PUT", f"/tasks/{tarea['id']}/comments", {"comment": html})
    apuntar_propio(comentario_id=comentario["id"])
    print(f"vikunja: comentario {comentario['id']} en la tarea {tarea['id']}")


if __name__ == "__main__":
    cfg = configuracion()
    orden = sys.argv[1] if len(sys.argv) > 1 else ""
    if orden == "proyecto":
        proyecto(cfg)
    elif orden == "tareas":
        tareas(cfg)
    elif orden == "crear" and len(sys.argv) == 3:
        crear(cfg, sys.argv[2])
    elif orden == "borrar" and len(sys.argv) > 2:
        borrar(cfg, sys.argv[2:])
    elif orden == "revisar":
        revisar(cfg, guardar="--sin-guardar" not in sys.argv)
    elif orden == "mensaje" and len(sys.argv) == 4:
        mensaje(cfg, sys.argv[2], sys.argv[3])
    else:
        sys.exit(__doc__)
