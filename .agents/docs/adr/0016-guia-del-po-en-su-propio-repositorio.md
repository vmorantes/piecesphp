# 0016 — La guía personal del PO vive en su propio repositorio; el arquitecto la escribe y la commitea

- **Estado:** Aceptada
- **Fecha:** 2026-09-16 (autorizaciones del PO: 2026-09-15 y 2026-09-16)
- **Decide:** Product Owner
- **Estructural:** sí (amplía las zonas donde la guarda deja escribir, y crea una excepción a «el
  arquitecto no commitea»)

## En cristiano

El PO quiere una guía personal para reconocer su propio framework cuando acabe la campaña, y no
quiere que se versione con el framework. Creó un repositorio aparte para ella. La guarda de los
agentes solo dejaba escribir en el framework, en los temporales y en los cuatro paquetes, así
que bloqueaba cualquier escritura en la guía. Esta decisión añade ese repositorio, y solo ese, a
los sitios donde se puede escribir, y deja que el arquitecto lo commitee él mismo, porque es el
único que lo toca.

## Contexto

- **La guía no se versiona con el framework** (PO, 2026-08-29; confirmado el 2026-09-15: «no
  quiero que mi guía personal quede versionada porque es… personal»).
- Sus requisitos están en `roadmap-posterior/Requisitos de la guia personal.md` (2026-08-24):
  organizada por situación, con la ficha fija por instrumento y «qué NO atrapa» como campo
  principal, y con muestras reales fechadas.
- **El 2026-09-15 el PO creó `/var/www/html/vicsen/guia-piecesphp-para-po`** (solo `.git`, rama
  `master` sin ningún commit) y pidió un MkDocs «bonito, explorable: nada pasado por alto, pero
  tampoco una enciclopedia».
- La guarda (`guardia.py`, ADR 0003) bloqueó las nueve escrituras con «escritura fuera del
  repositorio, de /tmp y de los repositorios hermanos: prohibido». **Hizo bien**: ese repositorio
  no estaba en su lista.
- Autorizaciones del PO, formalizadas:
  - 2026-09-15: que se escriba allí («pégalo allá, te lo permito»);
  - 2026-09-16: que el arquitecto haga los commits de ese repositorio como considere, porque es
    el único que lo tocará.
- Requisitos añadidos por el PO la noche del 2026-09-15:
  - un tema más propio de documentación y más amigable;
  - que cubra todo lo relevante desde el inicio de la campaña, el 19 de agosto de 2026;
  - **que le sirva sin IA**, porque si no se le volvería inmantenible y no siempre la usará.

## Decisión

1. `guardia.py` declara `GUIA_PO` (`<padre de este repositorio>/guia-piecesphp-para-po/`):
   - la añade a `ESCRIBIBLES_EXTRA`, así que se escribe dentro;
   - añade su raíz a `RAICES_PROTEGIDAS`, así que dentro se borra pero **la raíz entera no**.
2. `40-salvaguardas.md` §3 lo nombra entre los sitios escribibles.
3. **La escribe y la commitea el arquitecto.** Es la única excepción a «el arquitecto no
   commitea» (`30-protocolo-coder.md`, «Roles»), y vale solo para ese repositorio. Ninguna
   instrucción al coder la nombra.
4. `git push` en ese repositorio sigue siendo del PO, como en todos.

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| Dentro de `piecesphp`, por ejemplo en `.agents/guia/` | Se versionaría con el framework y viajaría con la plantilla que se clona: contra la condición del PO |
| En el scratchpad o en `/tmp` | Se pierde al cerrar la sesión, y ninguna sesión futura la encuentra |
| Escribir por Bash esquivando la guarda | Esquivar una guarda está prohibido: la regla es ajustarla con su prueba (`.claude/CLAUDE.md`, «Guarda de hooks») |
| Abrir todo `/var/www/html/vicsen/` | En esta máquina hay proyectos ajenos: dejaría escribir en ellos |
| Que la commitee el coder, con la autorización del ADR 0005 | Esa autorización cubre `piecesphp`, y el PO decidió que en la guía solo trabaja el arquitecto |

## Consecuencias

- **Lo bueno:** la guía se escribe con la guarda activa y su raíz está protegida contra un
  borrado recursivo.
- **Lo malo:**
  - **la guarda no distingue roles**: también dejaría escribir ahí al coder. Lo impide la regla,
    no la máquina;
  - el repositorio de la guía **no tiene hook `commit-msg`** y `menciones_ia.py` no lo mira, así
    que la ausencia de atribución a IA en sus commits depende del arquitecto;
  - `verificar.sh` no ve la guía: un error suyo no pone nada en rojo.

## Reversión

1. En `guardia.py`, quitar `GUIA_PO` de `ESCRIBIBLES_EXTRA` y de `RAICES_PROTEGIDAS`, y borrar su
   declaración.
2. En `probar_guardia.py`, quitar la constante `GUIA` y sus cuatro casos: el `rm -rf` de su
   raíz, el de `site/`, el `status` y las dos escrituras.
3. Quitar su línea de `40-salvaguardas.md` §3, y la excepción de la regla 30.
4. Correr `python3 -B .agents/scripts/guardas/probar_guardia.py`.

Es completa. El repositorio de la guía **no se toca**: la reversión solo impide escribir en él.

## Verificación

- `python3 -B .agents/scripts/guardas/probar_guardia.py` pasa entera. Tras este cambio y los dos
  falsos positivos arreglados el mismo día: **209/209** (medido por el arquitecto el 2026-09-16).
- **Provocación (LEY 24):** quitando `GUIA_PO` de `ESCRIBIBLES_EXTRA`, la escritura de
  `docs/index.md` de la guía tiene que fallar; quitándolo de `RAICES_PROTEGIDAS`, tiene que fallar
  el `rm -rf` de su raíz. La hace el coder y la reporta.
