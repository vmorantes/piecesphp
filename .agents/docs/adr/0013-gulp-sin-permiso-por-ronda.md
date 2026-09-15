# 0013 — Excepción: el coder compila con gulp cuando la instrucción lo dice

- **Estado:** Aceptada
- **Fecha:** 2026-09-15
- **Decide:** Product Owner
- **Estructural:** sí (levanta en este repositorio una salvaguarda de `00-core.md` y
  `40-salvaguardas.md`)

## En cristiano

Los archivos de JavaScript y de estilos que carga el navegador no son los que se editan: son
versiones «compiladas» que genera una herramienta, gulp. Hasta ahora, compilar necesitaba el
permiso del PO cada vez, y eso paró una ronda. El PO ha dicho que no hace falta pedírselo. Desde
ahora, el coder compila cuando la instrucción lo dice, y lo compilado se revisa antes de
guardarlo.

## Contexto

- **`00-core.md`**: «Nunca ejecutes un build después de hacer cambios, salvo que yo lo pida
  explícitamente».
- **`40-salvaguardas.md` §3**: prohíbe `gulp` y `bin/package-css` sin una orden explícita.
- **En `#064`**, el lote 3b se paró porque el navegador carga `configurations.min.js`, que genera
  la tarea `jsTask` de `src/gulpfile.js:88-120`, y no la fuente `configurations.js`.
- **El PO, el 2026-09-15** (A-003), formalizado: la compilación con gulp no necesita permiso.

## Decisión

En este repositorio, el coder ejecuta las tareas de `src/gulpfile.js` cuando la instrucción lo
nombra, **sin pedir permiso por ronda**. El resultado compilado entra en su propio commit, tras
revisar qué fuentes arrastra.

## Condiciones

- **Solo las tareas que nombre la instrucción** (por ejemplo, `jsTask`), no `init-project`
  entero, salvo que la instrucción lo diga.
- **Antes de compilar se mide qué fuentes cambiaron desde la última compilación**, con
  `git log` desde la fecha del compilado. Todas entran en el resultado y se enumeran en el
  reporte.
- **Si faltan dependencias de Node (`node_modules`), se para:** instalarlas sigue siendo del PO
  (`40-salvaguardas.md` §3, dependencias).
- **El compilado no se edita a mano nunca.**

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| Pedir permiso en cada compilación | Es lo que el PO acaba de retirar. Paraba rondas enteras por un paso mecánico |
| Editar el `.min.js` a mano | Es un generado (`00-core.md`, archivos generados): la siguiente compilación lo pisaría |
| Dejar de versionar los compilados | Rompe los clones que no compilan al desplegar. Es otra decisión, del PO |

## Consecuencias

- **Lo bueno:** los cambios de JS y de estilos llegan al navegador en la misma ronda que se
  hacen.
- **Lo malo:**
  - un compilado arrastra todo lo cambiado en sus fuentes desde la última compilación, no solo lo
    de la ronda. Por eso se mide antes;
  - el diff de un `.min.js` no se puede leer: la verificación es de comportamiento (navegador
    simulado), no de lectura.

## Reversión

1. Quitar la línea de la excepción en `40-salvaguardas.md` §3.
2. Volver a exigir la orden del PO en las instrucciones que compilen.

## Verificación

Cada instrucción que compila nombra la tarea. Cada reporte enumera las fuentes arrastradas e
incluye la prueba en navegador de lo compilado.

## Fe de erratas (2026-09-15, `#068`)

La decisión no cambia. Cambian dos hechos que el arquitecto dio por supuestos sin verificar:

- **Los compilados de JS NO se versionan:** `src/statics/core/js/.gitignore` ignora `*.min.js`
  y `*.js.map`, y `configurations.min.js` no tiene ningún commit en ninguna rama. Cada
  instalación compila el suyo. Por eso:
  - la fila «Dejar de versionar los compilados» de la tabla de alternativas describe algo que ya
    es así;
  - la condición «el resultado compilado entra en su propio commit» no aplica al JS: se compila
    en local para probar, y el `CHANGELOG` avisa a los clones cuando hay que recompilar al
    actualizar.
- **La tarea es `js-vendor`** (`src/gulpfile.js:124-128`): `jsTask()` es una función, no una
  tarea.
- **El PO amplió después la excepción** (A-004, 2026-09-15): «es un entorno de pruebas y gulp no
  es destructivo: úsalo como quieras». Las instrucciones siguen nombrando la tarea, para que el
  reporte diga qué se compiló.
