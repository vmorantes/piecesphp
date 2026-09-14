# 0003 — Salvaguardas forzadas por máquina, adaptadas a PiecesPHP

- **Estado:** Aceptada
- **Fecha:** 2026-09-14
- **Decide:** Arquitecto
- **Estructural:** sí (configuración de alcance global y cómo se trabaja)

## En cristiano

Antes de cada comando o escritura de un agente de Claude Code corre un guardián que bloquea lo
que las reglas prohíben: subir al remoto, crear etiquetas, enseñar las credenciales de los
remotos, borrar historia, salir del repositorio. El guardián vino de otro proyecto y se ajustó
a este: allí se podía subir y aquí no; allí se prohibía nombrar la IA, y aquí el producto
tiene funciones de IA reales, así que solo se prohíbe firmar el trabajo como hecho por una IA.
Que el guardián deje pasar algo no lo autoriza: la regla sigue siendo la fuente.

## Contexto

- La guarda (`.agents/scripts/guardas/`) y su enganche (`.claude/settings.json`) se copiaron
  del proyecto de plugins de HestiaCP del PO, donde:
  - `git push` estaba **permitido** por una excepción de aquel repositorio. Aquí la regla es
    la contraria: *«Nada de push. Nunca se le pide. El PROPIETARIO empuja cuando quiere.»*
    (20 §3). Y etiquetar o publicar es un «punto serio» (20 §2).
  - El filtro de menciones bloqueaba el **vocabulario** (`IA`, `AI`, `OpenAI`, `Gemini`,
    `inteligencia artificial`). Medido aquí el 2026-09-14 sobre `src/`, `bin/`, `databases/`,
    `source-docs/`, `files/` y los archivos de la raíz: **120 coincidencias, todas
    legítimas**: el producto tiene funciones de IA (`API/Adapters/OpenAIHandlerAdapter.php`,
    `SpeechToTextGroqAdapter.php`, `adminer/adminer-plugins/AdminerSqlGemini.php`, textos de
    interfaz en `config/constants.php:68`).
  - La historia tiene **cero atribuciones a IA**: el filtro viejo marcaba tres commits y los
    tres son falsos positivos (`92e3f92e` nombra el bloque «AI»; `b536c9c5` y `8273fe58` son
    commits del PO sobre la configuración de agentes).
- Riesgos propios de este repositorio:
  - Los remotos de git llevan credenciales en la URL (18 T4; decisión cerrada del PO: se
    quedan). Un `git remote -v` o un `cat .git/config` las imprimiría.
  - `bin/push-all` sube los cinco repositorios.
  - `permissions-and-property.sh` hace `chown`/`chmod`.
  - `secure-keys/` guarda claves de API del producto (ignoradas por git).
  - Los cuatro paquetes (`database`, `datastructures`, `geojson`, `html`) son repositorios
    hermanos en los que la campaña sí trabajó con autorización (`database` v4.1.0, 20 §7).

## Decisión

La guarda bloquea todo `push`, `fetch`, `pull` y `ls-remote`; crear etiquetas en este
repositorio (en los cuatro paquetes sí se etiqueta, P19) y moverlas o borrarlas en cualquiera;
crear ramas (`git branch <nueva>`, `switch -c`, `checkout -b`, `worktree add`), salvo `dev` en
los paquetes: el PO lo decidió el 2026-09-14;
imprimir las URL de los remotos o `.git/config`; `bin/push-all` y
`permissions-and-property.sh`; y la **atribución** a una IA (no su vocabulario) en commits y
entregables. A eso se suma lo genérico de la skill: `sudo`, servidores, clientes de base de
datos, gestores de paquetes, git destructivo y escrituras fuera del repositorio, los
temporales, la memoria nativa y los cuatro repositorios hermanos.

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| Conservar el filtro de vocabulario con una lista blanca de archivos | Son más de cuarenta archivos, y cada función de IA nueva lo haría saltar. La regla del PO es sobre autoría, no sobre el tema |
| Permitir `git push` como en el proyecto de origen | Contradice la regla del PO para este repositorio |
| Bloquear `bin/cli db-restore` y `scheme-drop` | La LEY 12 exige restaurar la base como método. Lo gobierna la regla (solo por instrucción), no la guarda |
| Bloquear escrituras en los repositorios hermanos | La campaña ya trabajó en `database` con autorización; la guarda forzaría rodeos. La regla exige que la instrucción nombre el repositorio |
| Sin guarda, solo reglas | Las reglas ya se incumplieron: el 20 §5 cuenta los casos. Un hook no se olvida |

## Consecuencias

- Falsos positivos posibles: un `grep` del literal `.git/config` se bloquea; si el producto
  integrara algún día un proveedor llamado Claude o Anthropic, el patrón saltaría y habría que
  ajustarlo con su prueba.
- Con `fetch` bloqueado, el coder no puede consultar el remoto: lo hace el PO.
- La guarda solo existe en Claude Code. Otra herramienta queda con las reglas y con el hook
  `commit-msg`, que el PO activa una vez por clon.
- `python3 -B` evita que la guarda deje `__pycache__` en el árbol; lo que ya exista se ignora
  por `.gitignore`.

## Reversión

1. Quitar el bloque `hooks` de `.claude/settings.json`: la guarda deja de correr.
2. Quitar las denegaciones añadidas en `permissions.deny`.
3. Si se activó el hook de git: `git config --unset core.hooksPath` (lo hace el PO).
4. `.agents/scripts/guardas/` puede quedarse; sin enganche no hace nada.

Reversión completa y no destructiva.

## Verificación

- `python3 -B .agents/scripts/guardas/probar_guardia.py`: todos los casos correctos, y cada
  bloqueo con su caso legítimo al lado.
- `python3 -B .agents/scripts/menciones_ia.py`: cero atribuciones en entregables y commits.
- Ambas corren dentro de `bash .agents/scripts/verificar.sh`.
