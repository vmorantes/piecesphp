# AGENTS.md

Punto de entrada para cualquier agente o herramienta que abra este repositorio. Las reglas del
proyecto están en `CLAUDE.md` y valen para cualquier herramienta, no solo para Claude Code.
Claude Code carga además `.claude/CLAUDE.md`.

**Proyecto**: PiecesPHP, framework PHP modular propio sobre Slim 4. Es una plantilla que se
clona: cada despliegue es un consumidor futuro, así que la regla no es «no rompas
producción», sino «no embarques una trampa».

## Lee, en este orden

1. `.agents/estado/AHORA.md` — qué está en curso, qué espera al Product Owner, qué número de
   mensaje toca.
2. `CLAUDE.md` — reglas del proyecto que no se negocian.
3. `.agents/README.md` — mapa de la documentación para agentes y orden de lectura.
4. `.agents/rules/` — **todas**. Si tu herramienta no las carga sola, léelas a mano.

## No se negocia

- Tres roles: el arquitecto decide y documenta; el coder implementa, verifica y commitea
  (`.agents/rules/30-protocolo-coder.md`).
- Ningún cambio de estado de git sin orden. Nunca `git add .`. `git push`, nunca. Ni etiquetas
  ni ramas nuevas en este repositorio sin el PO (en los paquetes, la regla 30).
- Nunca imprimir `.git/config` ni `git remote -v`: los remotos llevan credenciales.
- Ninguna conexión a servidores ni a bases de datos sin permiso. Ninguna dependencia nueva sin
  proponerla con alternativas.
- Cero atribución a IA en commits, código y documentación para personas.
- Nada inventado: lo que no se verificó, se dice.
