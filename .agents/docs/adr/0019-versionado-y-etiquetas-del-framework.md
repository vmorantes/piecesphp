# 0019 — Arquitecto y coder versionan y etiquetan el framework, salvo las versiones mayores estables

- **Estado:** Aceptada
- **Fecha:** 2026-09-16
- **Decide:** Product Owner (A-039 y A-040, 2026-09-16), con la forma del arquitecto
- **Estructural:** sí (levanta un punto serio y cambia la guarda)

## En cristiano

Hasta hoy, poner número de versión al framework era cosa exclusiva del propietario. Mientras tanto,
`master` recibió todas las rupturas de la campaña y seguía diciendo que era la `v7.1.0` de agosto:
quien lo clonara recibía un framework incompatible con la etiqueta de uno estable. Desde ahora, el
arquitecto y el coder marcan las versiones y crean sus etiquetas, con dos límites: la versión mayor
estable (`v8.0.0` a secas) la sigue decidiendo el propietario, y `last-stable` apunta siempre a una
versión estable.

## Contexto

- **Medido el 2026-09-16:** `master` = `origin/master` = `33251bf6` lleva las rupturas de la campaña,
  su última etiqueta es `v7.1.0` y `APP_VERSION` dice `v7.1.0` (`src/app/core/bootstrap.php:262`).
  `dev` va 38 commits por delante, sin empujar, y `master` es su ancestro.
- **`last-stable` = `b536c9c5`**, que es `v7.0.6` más 24 commits: no es una versión. Es ancestro de
  `v7.1.0`, que va 41 commits por delante sin divergir.
- Hasta hoy, «versionar, etiquetar o publicar `piecesphp`; tocar su `master` o su `last-stable`» era
  un punto serio (regla 30; 20 §2), y la guarda bloqueaba toda etiqueta en este repositorio.
- La forma de las etiquetas la aprobó el PO el 2026-09-02 (`.agents/context/12-convenciones.md`,
  «Etiquetas de versión»): `vX.Y.Z` y `vX.Y.Z-beta.N`.
- El PO preguntó si convenía ir sacando alphas y betas para que quien clone `master` no se confunda, y
  decidió: *«a partir de ahora les doy rienda suelta para el versionamiento y etiquetado, con excepción
  de mayores, y tomando en cuenta que last-stable siempre apunta a versión estable; tienen puerta
  abierta a la 8 en formas explícitamente no estables»* (formalizado).

## Decisión

1. **Arquitecto y coder deciden y crean las versiones y etiquetas de `piecesphp`** sin consultar al PO.
2. **Excepción: una versión MAYOR estable** (`vX.0.0` sin sufijo cuando `X` sube; hoy, `v8.0.0`) la
   decide el PO. De la 8 solo se crean formas explícitamente no estables:
   `v8.0.0-alpha.N`, `v8.0.0-beta.N` y `v8.0.0-rc.N`.
3. **`last-stable` apunta siempre a una versión estable etiquetada.** Solo avanza.
4. **`master` recibe las pre-versiones**, para que quien lo clone vea que no es estable. Solo avanza, y
   solo hasta un commit con etiqueta de pre-versión.
5. **Cómo avanza una rama:** `git update-ref refs/heads/<rama> <nuevo> <anterior>`, después de
   comprobar `git merge-base --is-ancestor <anterior> <nuevo>`. No toca el árbol de trabajo, y la
   comparación con el valor anterior es atómica: si la rama se movió, no hace nada.
6. **La cadencia de la campaña:**
   - `alpha.N`: al cerrar cada lote, o antes si hay algo que quien clone deba recibir;
   - `beta.N`: cuando el alcance de la MAJOR esté completo y solo queden correcciones;
   - `rc.N`: antes de proponer al PO la `v8.0.0`.
7. **Cada pre-versión lleva**, en el mismo commit: `APP_VERSION` y `APP_VERSION_DATE` con su valor, y su
   fila en la tabla de pre-versiones de la sección de la MAYOR en curso del `CHANGELOG` (versión, fecha y
   hasta qué ruptura llega). **Las entradas no se reparten por pre-versión**: el bloque «CAMBIOS
   INCOMPATIBLES» sigue agrupado para la MAYOR, que es como lo lee quien actualiza.
8. **Sigue sin cambios:**
   - el push, que es del PO;
   - mover o borrar una etiqueta ya creada, que no se hace nunca, en ninguno de los cinco repositorios;
   - reescribir historia;
   - las 79 etiquetas históricas, que no se tocan.
9. **La guarda es más estricta que la regla:** en este repositorio solo deja crear etiquetas con la forma
   exacta `vX.Y.Z-(alpha|beta|rc).N`. Una MINOR o PATCH estable, que la regla sí permite, exige ajustar
   antes la guarda con su prueba. Hoy no hay ninguna prevista.

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| Seguir sin etiquetar hasta la `v8.0.0` | Es la confusión que el PO quiere evitar: `master` dice `v7.1.0` con rupturas dentro |
| Dejar de fusionar a `master` hasta la MAJOR | `master` ya está empujado con las rupturas (`33251bf6`); no arregla lo publicado |
| Etiquetar solo en `dev` | Quien clona `master` seguiría sin ver el aviso |
| Avanzar ramas con `git switch` y `git merge --ff-only` | Cambia el árbol de trabajo, que Apache sirve y que el coder puede tener a medias |
| Que la guarda permita también las estables no mayores | Tendría que deducir la mayor vigente leyendo etiquetas; más complejo, y no hay ninguna prevista |

## Consecuencias

- **Lo bueno:**
  - quien clona `master` ve `v8.0.0-alpha.N` y sabe que no es estable;
  - Composer no instala una pre-versión si no se pide expresamente;
  - `last-stable` vuelve a significar lo que dice.
- **Lo malo:**
  - una etiqueta creada en local no se puede corregir: si sale mal, se crea la siguiente (`alpha.N+1`);
  - las etiquetas locales no llegan al remoto hasta que el PO empuja, y los agentes no pueden
    comprobarlo sin consultar el remoto;
  - `verify-integrity` (comprobación 17) ignora las pre-versiones a propósito, así que no vigila que
    `APP_VERSION` coincida con la etiqueta. Lo vigila la instrucción de cada pre-versión.

## Reversión

1. En `guardia.py`, quitar `PRE_VERSION` y la excepción de `tag`, y la regla de `update-ref`; en
   `probar_guardia.py`, sus casos. Correr las pruebas.
2. Devolver a la regla 30 el punto serio «versionar, etiquetar o publicar `piecesphp`; tocar su `master`
   o su `last-stable`», y a la regla 40 §1 «en este repositorio, ninguna etiqueta». Quitar la excepción
   de `00-core.md` y de `AGENTS.md`.
3. Las etiquetas ya creadas **no se revierten**: son publicables y nunca se borran. `master` y
   `last-stable` tampoco se retroceden. La reversión es de la autorización, no de lo hecho con ella.

## Verificación

- `probar_guardia.py` (233/233, medido el 2026-09-16):
  - permite `git tag v8.0.0-alpha.1`, `git tag -a v8.0.0-beta.2 -m …`, `git -C <raíz> tag v8.0.0-rc.1 HEAD`
    y `git update-ref refs/heads/<rama> <nuevo> <anterior>`;
  - bloquea `v8.0.0`, `v8.0.0-alpha` (sin número), `v8.0.0-dev.1`, `v8.0.0-alpha.1-extra`, un `-m` con
    forma de pre-versión delante de un nombre estable, `-f`, una etiqueta en un repositorio ajeno,
    `update-ref` sin valor anterior, con `-d` o `--stdin`, sobre `refs/tags/` o fuera de `refs/heads/`.
- Provocación, en la ronda que lo commitea: sin la excepción de pre-versión caen los casos permitidos;
  sin el `fullmatch`, caen los bloqueos de forma.
