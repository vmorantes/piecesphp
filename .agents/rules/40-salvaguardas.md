# Salvaguardas del proyecto

Complementa `00-core.md`. Aquí lo específico de PiecesPHP: un framework que es **una plantilla
que se clona**, con cinco repositorios (este y los paquetes `database`, `datastructures`,
`geojson` y `html`), remotos con credenciales en la URL y funciones de IA dentro del producto.
Aplica a toda sesión, rol y proveedor.

Parte de esto está **forzado por máquina** en Claude Code (`.agents/scripts/guardas/guardia.py`,
enganchada en `.claude/settings.json`; ADR 0003). Que la guarda no bloquee algo **no** lo
autoriza: la regla es esta, la guarda es una red.

## 1. Remotos y servidores

- **`git push`, nunca.** El PO sube cuando quiere (20 §3). Tampoco `fetch`, `pull`,
  `ls-remote` ni `bin/push-all`: los remotos no se tocan.
- **En este repositorio, arquitecto y coder versionan y etiquetan (ADR 0019, PO 2026-09-16)**, salvo una
  versión MAYOR estable, que decide el PO: de la 8 solo `v8.0.0-alpha|beta|rc.N`. `last-stable` apunta
  siempre a una versión estable, y `master` y `last-stable` solo avanzan. **La guarda deja crear versiones
  `vX.Y.Z` y pre-versiones `vX.Y.Z-alpha|beta|rc.N`, y nada más** (ajustada el 2026-10-02 con la autorización
  expresa del PO: estaba escrita contra el ADR 0019 y bloqueaba la estable que el 0031 nos delegó). En los cuatro paquetes se etiqueta con soltura (P19). **Mover o borrar una etiqueta, en
  ninguno de los cinco.**
- **Los remotos llevan credenciales en la URL** (18 T4; decisión cerrada del PO: se quedan y no
  se vuelve a levantar). Por eso **nunca** se imprime `.git/config`, ni `git remote -v`, ni
  `git config --list`, ni una URL de remoto.
- Ningún agente se conecta a un servidor: nada de `ssh`, `scp`, `sftp`, `rsync` remoto. Si hace
  falta un dato de un servidor, se pide al PO el comando exacto de solo lectura y se espera su
  salida.

## 2. Bases de datos

- Lo de `00-core.md`: ninguna conexión sin permiso, y conectarse no es escribir.
- Ningún cliente de base de datos (`mysql`, `mariadb`, `psql`…): la guarda los bloquea.
- **Excepción del ADR 0010 (PO, 2026-09-15):** contra la instalación LOCAL de esta máquina, que
  es de prueba y desechable, el coder puede hacer peticiones HTTP como un navegador, entrar con
  las credenciales de prueba locales y crear, editar o borrar registros por la aplicación o por
  las pruebas. Condiciones: los registros llevan un prefijo reconocible, se guarda el estado antes
  (`bin/cli db-backup`) y todo se enumera en el reporte. Nunca contra un servidor remoto ni con
  otras credenciales.
- **Excepción del ADR 0023 (PO, 2026-09-19):** el coder crea sus propios usuarios de prueba en la
  base LOCAL (prefijo `zz-agente-`, por la aplicación o la terminal) y guarda sus contraseñas solo
  en `.claude/settings.local.json`, que git ignora. Nunca las imprime ni las escribe en otro sitio.
- **Excepción del ADR 0024 (PO, 2026-09-23):** contra la base LOCAL, el coder **lee** directamente (solo sentencias
  de lectura) y hace **pruebas que se deshacen solas** (`BEGIN … ROLLBACK`, sin `COMMIT` y sin sentencias que confirman
  solas). **Escribe de verdad solo si la ronda lo nombra**, con `bin/cli db-backup` antes. Nunca pide ni imprime
  columnas de contraseña, token o clave. **Hasta que la guarda lo implemente, los clientes siguen bloqueados.**
- **Excepción del ADR 0011 (PO, 2026-09-15):** el correo real de una prueba solo puede ir a
  direcciones `zz-prueba-…@mailinator.com`, que son buzones públicos. El reporte lista cada
  dirección, qué lo envió y cuándo, para que el PO lo revise. Ningún otro destinatario; nada
  sensible en el correo; sin tocar la configuración SMTP; sin envíos en masa.
- La base **local de desarrollo** la usan las tareas de `bin/cli` que la instrucción nombre.
  Las que escriben o destruyen datos (`db-restore`, `scheme-drop`, `scheme-create`,
  `clean-all`) solo con orden explícita en la instrucción, y la instrucción solo las ordena con
  la autorización del PO para esa acción concreta.

## 3. El sistema local

- Nada de `sudo`, `su`, `pkexec`, gestores de paquetes, `systemctl`, `crontab`, `chown`.
- **Nunca ejecutar** `permissions-and-property.sh` (cambia propietarios y permisos) ni builds
  (`gulp`, `bin/package-css`) sin orden explícita.
  - **Excepción de los ADR 0013 y 0035** (PO, 2026-09-15 y 2026-09-30): **cualquier tarea de
    `src/gulpfile.js`, cuando haga falta**, sin que la instrucción la nombre. Menos las de
    observación (`*:watch`), que no terminan. Desde el bloque CG compilar **ya no** ejecuta
    `bin/cli clean-cache` (solo lo hace la tarea `clean-cache`); el reporte dice igual qué tareas
    corrieron.
  - Antes se enumeran las fuentes que arrastra el compilado.
  - Si faltan `node_modules`, se para: instalarlos sigue siendo del PO.
- Dependencias (`composer install|update|require`, `npm install`, `pip install`): el PO, con
  la propuesta y sus alternativas delante. **Única excepción (ADR 0007)**: `composer update` de
  las herramientas de análisis (`phpstan/phpstan`, `phpstan/phpstan-deprecation-rules`,
  `rector/rector`), nombradas una a una, porque el PO delegó la instrumentación en el
  arquitecto. **Y la del ADR 0008**: en los cuatro paquetes hermanos, y solo con
  `--working-dir` apuntando a uno de ellos, también `piecesphp/*`, porque allí el
  `composer.lock` no se versiona y lo que cambia es el entorno local. **Y la del ADR 0017**: en el
  framework, `composer update` de `piecesphp/*` y nada más, con `--working-dir=src`, sin `-w` ni
  `-W`, cuando un lote nombrado por el PO lo pida y después de que el PO haya empujado la versión.
- Solo se escribe dentro del repositorio, en el scratchpad de la sesión, en `/tmp` y en la
  memoria nativa de la herramienta. Los cuatro paquetes hermanos
  (`/var/www/html/vicsen/{database,datastructures,geojson,html}`) solo cuando la instrucción
  nombra ese repositorio. El de la guía personal del PO
  (`/var/www/html/vicsen/guia-piecesphp-para-po`), solo el arquitecto y solo para la guía
  (ADR 0016). `src/vendor/` no se edita nunca.
- Borrados recursivos solo dentro del repositorio o de un temporal propio, y nunca sobre algo
  no versionado sin haberlo leído antes: lo no versionado no se recupera. Mejor moverlo a un
  temporal.

## 4. Git

Lo de `00-core.md`, más: nada de `reset --hard`, `clean -f`, `checkout -- .`, `restore` sobre
el árbol, `rebase`, `commit --amend`, `branch -D`, `filter-branch`/`filter-repo`, `reflog
expire`, `stash drop|clear`. `git config` no se toca: es configuración del entorno del PO.
Nunca `git add .` ni `-A`.

**Ramas** (PO, 2026-09-14): ninguna se crea sin su permiso, salvo `dev` en los cuatro paquetes.
En este repositorio, `master` recibe las pre-versiones y `last-stable` apunta a la última versión
estable etiquetada; las dos solo avanzan (ADR 0019). El resto son de trabajo. En los paquetes, `master` es su estable y se trabaja en
`dev`. Detalle en `30-protocolo-coder.md`,
«Ramas».

## 5. Cero atribución a IA

El PO firma este código. Nada entregable dice ni sugiere que lo escribió una IA:

- **Prohibido** en código, commits, ramas, PR, `README.md`, `CHANGELOG.md`, `source-docs/` y
  `files/`: `Co-Authored-By` de un agente, «Generated with/by …», «generado por/con IA», el
  nombre del asistente o de su fabricante como autor, emojis de robot.
- **Permitido**: el vocabulario del producto. PiecesPHP tiene funciones de IA reales
  (adaptadores de OpenAI, Mistral y Groq, traducción automática):
  nombrarlas no es firmar nada.
- **Permitido** hablar de agentes donde el tema son los agentes: `.agents/`, `.claude/`,
  `AGENTS.md`, `CLAUDE.md`.

Comprobación: `python3 -B .agents/scripts/menciones_ia.py` (lo corre `verificar.sh`). **Tres hooks** lo aplican a
cualquier herramienta —y también a una persona— una vez que el PO los activa en su clon con
`git config core.hooksPath .agents/scripts/git-hooks`:

| Hook | Qué frena | Cuánto cuesta |
| :-- | :-- | :-- |
| `commit-msg` | Un mensaje de commit con atribución a una IA | inapreciable |
| `pre-commit` | Que lo **preparado** —no el árbol— lleve atribución en un entregable | **0,09 s** medido |
| `pre-push` | Subir con el andamiaje roto: corre `verificar.sh` | **12 s** medido el 2026-09-24 |

- **El `pre-commit` mira el ÍNDICE**, con `menciones_ia.py --indice`: un archivo sucio del árbol que no entra en el
  commit no puede frenarlo. Y solo mira **entregables**: un commit de puro andamiaje da «universo del índice: 0», así
  que **el hook no protege `.agents/`**; para eso está `verificar.sh`.
- **El `pre-push` es el único control que se le ejecuta al PO sin que lo pida.** Por eso avisa antes de empezar, dice
  cuánto tarda, **enseña la salida entera** si falla, ofrece `git push --no-verify` en su propia línea, y **deja subir
  si no encuentra el verificador**: un control que rompe el push por su propio fallo es peor que no tenerlo.

## 6. Datos verídicos

- Nada inventado. Lo que no se verificó se escribe «sin verificar», no se redondea a hecho.
- Toda cifra que sobrevive a la sesión lleva su método y su unidad (LEY 5).
- Una afirmación sobre un consumidor no se deduce de su productor (LEY 19): se mide.

## 7. Secretos

Lo de `00-core.md`. Además:

- `secure-keys/` guarda claves de API del producto (ignoradas por git): no se lee.
- Las credenciales de `src/app/config/` y de `.git/config` no se imprimen ni se copian.
- Datos de prueba sintéticos. Una contraseña de prueba **tampoco** se escribe en documentación
  versionada: el repositorio se publica.
