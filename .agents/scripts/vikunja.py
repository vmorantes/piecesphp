#!/usr/bin/env python3
"""
Las tareas y los avisos del PO en su Vikunja (PO, 2026-10-05 y 2026-10-06).

Envoltura de `vikunja-notify`, la herramienta general del PO (`IA_Usage_Utilities/vikunja-notify`, instalada en
~/.local/bin): una sola implementación. La configuración es su perfil `piecesphp` (`.env-piecesphp` junto a la
herramienta); aquí solo vive la foto de `revisar`, en .agents/local/, que git ignora. El token no se imprime nunca.

Uso (las órdenes son las de `vikunja-notify --help`):
  python3 -B .agents/scripts/vikunja.py tareas
  python3 -B .agents/scripts/vikunja.py crear <lote.json>
  python3 -B .agents/scripts/vikunja.py hecha <id> [<id>…]
  python3 -B .agents/scripts/vikunja.py borrar <id> [<id>…]
  python3 -B .agents/scripts/vikunja.py mensaje "Mensajes del arquitecto · AAAA-MM-DD" <archivo>
  python3 -B .agents/scripts/vikunja.py revisar [--sin-guardar]

Para el PO, Vikunja lleva SOLO lo que él tiene que ver o decidir, nombrado por el número que ve allí («#22»), y los
avisos intermedios como comentarios; los reportes finales van por correo con mail-notify (pendientes.md 370). Lo que
ya no necesita al PO se marca hecho. Si Vikunja falla, no se insiste: se apunta el pendiente y se reintenta en otra
ocasión (370.1).
"""
import os
import shutil
import sys

AQUI = os.path.dirname(os.path.abspath(__file__))
LOCAL = os.path.join(AQUI, "..", "local")

herramienta = shutil.which("vikunja-notify")
if herramienta is None:
    sys.exit("Falta `vikunja-notify` en el PATH: instálalo desde IA_Usage_Utilities/vikunja-notify (./install.sh).")
# La configuración (y el token) es el perfil `piecesphp` del PO, junto a la herramienta: un solo sitio que cambiar
# (PO, 2026-10-06, pendientes.md 395). Aquí solo vive la foto de `revisar`.
os.execv(herramienta, [herramienta,
                       "--perfil", "piecesphp",
                       "--estado", os.path.join(LOCAL, "vikunja-estado.json"),
                       *sys.argv[1:]])
