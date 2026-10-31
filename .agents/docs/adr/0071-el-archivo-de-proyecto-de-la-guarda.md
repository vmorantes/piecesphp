# 0071 — Lo propio del proyecto sale de la guarda, a un archivo validado

- **Estado:** Aceptada
- **Fecha:** 2026-10-30
- **Decide:** el PO (A-1516 y A-1517: «podemos aprender e implementar lo necesario del andamiaje ajeno para que ningún clon lo necesite»; «adelante»), formalizado por el arquitecto
- **Estructural:** sí (dónde vive lo que la guarda permite y prohíbe en cada proyecto)

## En cristiano

La guarda lleva escrito en su código qué repositorios hermanos hay, qué carpetas se pueden escribir o qué guiones no se ejecutan. Eso es de cada proyecto, no del método. Pasa a
`.agents/project-architect-coder.json`, el formato de la plantilla `andamiaje-arquitecto-coder`. Una carpeta nueva es ahora una línea con su motivo, y un clon se configura sin tocar la guarda.

## Contexto

- `guardia.py` son 1.018 líneas y 410 casos. Lo propio del proyecto son unas diez constantes y `patrones.py`.
- La plantilla ya resolvió esto (su ADR 0007): archivo validado, clave desconocida como error y fallo cerrado. Su guarda y la nuestra difieren en unas 1.700 líneas, y la nuestra tiene reglas propias.
- La guarda bloqueó el 2026-10-30 escribir en `~/Documentos/MigracionIfcol/`, una carpeta autorizada por el PO: un permiso no podía declararse sin tocar el código.

## Decisión

1. **Se trae `proyecto.py` tal cual**, con sus pruebas, y una clave propia: `guard.writable_paths`, carpetas personales enteras, cada una con su `reason`.
2. **Las constantes salen de ese archivo.** `can_tag` distingue los paquetes de un repositorio donde solo se escribe, y `creatable_branches` sustituye al `dev` escrito a mano.
3. **Falla cerrada.** Si el archivo falta o no vale, la guarda bloquea todo salvo escribir ese archivo.
4. **Lo que no sabe aplicar, no lo acepta.** `forbidden_reads` solo admite aquí carpetas enteras (`carpeta/*`): otro patrón cierra la guarda en vez de dar una falsa protección.
5. **`budget` se acepta y aún no lo usa nadie:** es del presupuesto de contexto (H140).
6. **`verificar.sh` valida el archivo y corre sus pruebas.** Viaja en la capa A: un clon lo recibe y lo ajusta.

## Alternativas descartadas

- **Copiar la guarda de la plantilla entera.** Perderíamos reglas propias a cambio de nada.
- **Añadir la carpeta en el código.** Es lo que pasó con ADR 0058, y otra vez aquí: cada permiso, un cambio en la guarda.

## Consecuencias

- Una carpeta o un hermano nuevos se escriben con motivo en el archivo de proyecto, y `verificar.sh` lo comprueba.
- Un clon hereda las rutas de este proyecto hasta que las ajuste: las de los hermanos no existen allí y no hacen daño.
- Sigue sin hacer, en H140: el instalador con `--plan`, el presupuesto y `manual-de-uso`.
