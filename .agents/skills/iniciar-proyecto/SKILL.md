---
name: iniciar-proyecto
description: >
  Ritual de la PRIMERA sesión del arquitecto en un proyecto recién clonado de PiecesPHP por la vía privilegiada
  (bin/clonar-proyecto). Comprueba que el clon nació bien, guía al PO por lo que solo él puede hacer (renombrar las
  sesiones, activar los hooks, el primer commit, montar el entorno), deja la verificación del andamiaje en verde, llena el
  registro vacío con lo que el PO diga del proyecto y arranca la cadena con el coder. Invócala cuando se diga «iniciar
  proyecto», «proyecto nuevo», «acabo de clonar», o cuando AHORA.md tenga los contadores a cero y ningún tramo.
  Después de esta, cada día empieza con abrir-jornada.
---

# Iniciar un proyecto clonado de PiecesPHP

Se usa **una sola vez**, en la primera sesión del arquitecto del proyecto nuevo. A partir del día siguiente manda
`abrir-jornada`. Todo lo que diga el PO del proyecto se escribe en el registro; nada se inventa.

## 1. Comprobar que el clon nació bien (medido, no supuesto)

```bash
git -C . --no-optional-locks log --oneline -3
git -C . --no-optional-locks status --short
git -C . --no-optional-locks tag --list 'v*'
cat .piecesphp-distribution
python3 -c "import json;print(json.load(open('.agents/project-architect-coder.json'))['sessions'])"
```

- El clon nace **sin commits** y con todo preparado en el índice; tras el commit del PO, la historia tiene **uno solo**
  (ADR 0085 del framework). La versión de la que nace está en `.piecesphp-distribution` (etiqueta y commit).
- `git status` enseña todo **preparado sin commitear** (si el PO aún no hizo el commit) o nada.
- `.agents/estado/AHORA.md` tiene los contadores a cero; `tramos/` está vacía; no hay ADR.
- Si algo no cuadra, se dice al PO y se para: un clon mal nacido no se arregla trabajando encima.

## 2. Lo que solo puede hacer el PO (se le da pegable, en este orden)

1. **Los nombres de las sesiones**, los del campo `sessions` del json:
   ```
   /rename <Proyecto>-Arquitecto-Main     ← en esta sesión
   /rename <Proyecto>-Coder-Main          ← en la sesión del coder
   ```
2. **Los hooks y el primer commit** (si no los hizo ya; los imprimió `bin/clonar-proyecto`):
   ```bash
   git -C <ruta del clon> config core.hooksPath .agents/scripts/git-hooks
   git -C <ruta del clon> commit -m "chore: proyecto nuevo desde PiecesPHP <versión> con la metodología de trabajo"
   ```
3. **Montar el entorno**, como cualquier proyecto PiecesPHP: `composer install` en `src/`, la base de datos, el vhost y
   la configuración (`src/app/config/`). La `app_key` se genera sola en el primer arranque, en `secure-keys/`.
4. **La autorización de commits** para el coder (ADR 0005 del framework): sin la frase del PO en el registro, el coder
   no commitea. Se le pregunta y se escribe en `AHORA.md` §8 con sus palabras.

## 3. El andamiaje en verde

```bash
bash .agents/scripts/verificar.sh
```

Tiene que terminar en «ANDAMIAJE OK» (las comprobaciones que dicen «NO APLICA» en un clon son normales). Si cae, se
para y se dice qué cae: el hook de push corre lo mismo.

## 4. Llenar el registro vacío con el PO

Se le pregunta al PO, en un solo mensaje numerado, y se escribe lo que conteste (sus palabras, formalizadas):

1. Qué es el proyecto y para quién.
2. El objetivo de la primera etapa y qué cuenta como terminado.
3. Si lleva zona pública. Si no: `$config['public_area'] = false` en `src/app/config/config.php` (el panel puede ir en otra ruta con
   `$config['admin_path']`; en la raíz, todavía no). Si lleva un sitio público desde un diseño: el manual «Adaptar una plantilla,
   de principio a fin» de la documentación de PiecesPHP y la skill `adapt-design` del kit del sitio público. Adónde lleva
   el acceso: `$config['login_redirect_route']`, por nombre de ruta.
4. Qué módulos del framework se usan y cuáles se apagan.
5. Repositorios hermanos, carpetas escribibles fuera del repositorio o servicios: si los hay, van a
   `.agents/project-architect-coder.json` (la guarda los lee de ahí).

Con eso: `AHORA.md` (secciones 1, 5, 6 y 8), el primer archivo de `tramos/` con su hora de inicio, la línea del
encargo en `abierto.md` y la fila de cada decisión en `decisiones-del-po.md`.

## 5. Lo heredado que no aplica

Las reglas y las skills vienen de PiecesPHP y citan casos de su desarrollo: los paquetes hermanos `database`,
`datastructures`, `geojson` y `html`, la guía personal del PO, carpetas de `~/Documentos/…`, ADR y números de mensaje
que no viajaron. **En este proyecto no aplican salvo que su json o su registro los declaren.** Si una regla obliga a
algo que solo tiene sentido en PiecesPHP, se dice al PO y se anota para adaptarla; no se cumple a ciegas ni se borra.

## 6. Arrancar la cadena con el coder

El saludo de la plantilla `arquitecto-coder/plantillas/saludo-coder.md`, con el contador en `#1`. El coder responde
con su directorio, su rama, su `git status`, su herramienta y su modelo. Después, la primera instrucción del encargo
que nombró el PO.

## 7. Al terminar

El primer `docs(estado)` del proyecto (lo commitea el coder) y, al PO, el resumen de arranque con su `A-1`.
