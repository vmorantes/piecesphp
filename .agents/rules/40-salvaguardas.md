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
- **En este repositorio, ninguna etiqueta** se crea, mueve ni borra: versionar y publicar
  `piecesphp` es un punto serio (20 §2). En los cuatro paquetes se etiqueta con soltura (P19);
  mover o borrar una etiqueta, en ninguno.
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
- La base **local de desarrollo** la usan las tareas de `bin/cli` que la instrucción nombre.
  Las que escriben o destruyen datos (`db-restore`, `scheme-drop`, `scheme-create`,
  `clean-all`) solo con orden explícita en la instrucción, y la instrucción solo las ordena con
  la autorización del PO para esa acción concreta.

## 3. El sistema local

- Nada de `sudo`, `su`, `pkexec`, gestores de paquetes, `systemctl`, `crontab`, `chown`.
- **Nunca ejecutar** `permissions-and-property.sh` (cambia propietarios y permisos) ni builds
  (`gulp`, `bin/package-css`) sin orden explícita.
- Dependencias (`composer install|update|require`, `npm install`, `pip install`): el PO, con
  la propuesta y sus alternativas delante. **Única excepción (ADR 0007)**: `composer update` de
  las herramientas de análisis (`phpstan/phpstan`, `phpstan/phpstan-deprecation-rules`,
  `rector/rector`), nombradas una a una, porque el PO delegó la instrumentación en el
  arquitecto. **Y la del ADR 0008**: en los cuatro paquetes hermanos, y solo con
  `--working-dir` apuntando a uno de ellos, también `piecesphp/*`, porque allí el
  `composer.lock` no se versiona y lo que cambia es el entorno local.
- Solo se escribe dentro del repositorio, en el scratchpad de la sesión, en `/tmp` y en la
  memoria nativa de la herramienta. Los cuatro paquetes hermanos
  (`/var/www/html/vicsen/{database,datastructures,geojson,html}`) solo cuando la instrucción
  nombra ese repositorio. `src/vendor/` no se edita nunca.
- Borrados recursivos solo dentro del repositorio o de un temporal propio, y nunca sobre algo
  no versionado sin haberlo leído antes: lo no versionado no se recupera. Mejor moverlo a un
  temporal.

## 4. Git

Lo de `00-core.md`, más: nada de `reset --hard`, `clean -f`, `checkout -- .`, `restore` sobre
el árbol, `rebase`, `commit --amend`, `branch -D`, `filter-branch`/`filter-repo`, `reflog
expire`, `stash drop|clear`. `git config` no se toca: es configuración del entorno del PO.
Nunca `git add .` ni `-A`.

**Ramas** (PO, 2026-09-14): ninguna se crea sin su permiso, salvo `dev` en los cuatro paquetes.
En este repositorio, `master` es la estable sin versionar y `last-stable` la estable con etiqueta
de versión; el resto son de trabajo. En los paquetes, `master` es su estable y se trabaja en
`dev`. Detalle en `30-protocolo-coder.md`,
«Ramas».

## 5. Cero atribución a IA

El PO firma este código. Nada entregable dice ni sugiere que lo escribió una IA:

- **Prohibido** en código, commits, ramas, PR, `README.md`, `CHANGELOG.md`, `source-docs/` y
  `files/`: `Co-Authored-By` de un agente, «Generated with/by …», «generado por/con IA», el
  nombre del asistente o de su fabricante como autor, emojis de robot.
- **Permitido**: el vocabulario del producto. PiecesPHP tiene funciones de IA reales
  (adaptadores de OpenAI y Groq, traducción automática, un plugin de Gemini para adminer):
  nombrarlas no es firmar nada.
- **Permitido** hablar de agentes donde el tema son los agentes: `.agents/`, `.claude/`,
  `AGENTS.md`, `CLAUDE.md`.

Comprobación: `python3 -B .agents/scripts/menciones_ia.py` (lo corre `verificar.sh`). El hook
`commit-msg` lo aplica a cualquier herramienta una vez que el PO lo activa en su clon:
`git config core.hooksPath .agents/scripts/git-hooks`.

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
