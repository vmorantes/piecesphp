# Decisiones de arquitectura (ADR)

Un ADR registra **por qué** se decidió algo, qué se descartó y a cambio de qué. No describe
cómo funciona el código: eso se lee del código.

> `git log` te dice qué cambió. Jamás te dice qué descartaste, ni por qué.

Viven aquí, con la documentación de agentes, y no en `source-docs/`, a propósito: quien más
los consulta es un agente que necesita saber qué no rediscutir.

## Regla dura: los ADR son inmutables

**La inmutabilidad empieza cuando el ADR se commitea.** Antes es un borrador y se edita
libremente. Publicado, no se toca.

Si la decisión cambia, se escribe uno nuevo: el nuevo lleva `Reemplaza: NNNN`; del viejo
**solo** se toca la línea de estado, que pasa a `Reemplazado por NNNN`.

## Cuándo escribir uno

Cuando hay una **elección real entre alternativas** con consecuencias que duran. No cuando
hay código nuevo. Se escribe **antes** de implementar.

## Cambios estructurales: dos secciones obligatorias

Un cambio es **estructural** si cambia **dónde vive** algo, un **contrato** entre partes
(rutas, esquema, API pública, formato compartido), **cómo se trabaja**, o **configuración de
alcance global** (`.gitignore`, `.gitattributes`, base de datos, build, despliegue).

Entonces el ADR lleva **En cristiano** (cuatro frases sin jerga) y **Reversión** (paso a
paso, en orden inverso, en términos de estado y no de hashes; si es parcial o destructiva,
se dice).

## Decisiones anteriores a este índice

Del 2026-08-19 al 2026-09-14 las decisiones se registraron en `.agents/context/`: las
entradas T de `18-siguientes-ventanas.md`, las leyes de `19-leyes.md` y el contrato
`20-contrato-de-trabajo.md` («DECIDIDO por el PROPIETARIO», «DECISIÓN DE ARQUITECTO»).
**Siguen vigentes.** No se reescriben en bloque: cuando una tarea toque una de ellas, se
escribe su ADR **retrospectivo** citando la entrada original, y la entrada queda como
procedencia.

## Convención

`NNNN-titulo-en-kebab-case.md`, correlativos, sin reutilizar números. Estados:
`Propuesta` · `Aceptada` · `Aceptada (sin implementar)` · `Reemplazada por NNNN` ·
`Descartada`. Plantilla: `.agents/skills/arquitecto-coder/plantillas/adr-plantilla.md`.

## Índice

| # | Decisión | Estructural | Estado |
| --- | --- | --- | --- |
| [0001](0001-tres-roles-con-canal-directo.md) | Tres roles con canal directo, sobre el registro existente | sí | Aceptada |
| [0002](0002-donde-viven-estado-mapa-e-historia.md) | Dónde viven el estado, el mapa a la MAJOR y la historia | sí | Aceptada |
| [0003](0003-salvaguardas-forzadas-por-maquina.md) | Salvaguardas forzadas por máquina, adaptadas a PiecesPHP | sí | Aceptada |
| [0004](0004-subagentes-generados-desde-personas.md) | Subagentes generados desde personas, con modelo por coste del error | sí | Aceptada |
| [0005](0005-el-coder-commitea-sin-permiso-commit-a-commit.md) | Excepción de este repositorio: el coder commitea sin pedir permiso commit a commit | sí | Aceptada |
