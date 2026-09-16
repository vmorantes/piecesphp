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
| [0002](0002-donde-viven-estado-mapa-e-historia.md) | Dónde viven el estado, el mapa a la MAJOR y la historia | sí | Aceptada; en parte reemplazada por 0006 |
| [0003](0003-salvaguardas-forzadas-por-maquina.md) | Salvaguardas forzadas por máquina, adaptadas a PiecesPHP | sí | Aceptada |
| [0004](0004-subagentes-generados-desde-personas.md) | Subagentes generados desde personas, con modelo por coste del error | sí | Aceptada |
| [0005](0005-el-coder-commitea-sin-permiso-commit-a-commit.md) | Excepción de este repositorio: el coder commitea sin pedir permiso commit a commit | sí | Aceptada |
| [0006](0006-una-razon-de-ser-por-carpeta.md) | Una razón de ser por carpeta, y fuera el build de la documentación de la API | sí | Aceptada |
| [0007](0007-actualizar-herramientas-de-analisis.md) | Excepción: los agentes actualizan las herramientas de análisis con Composer | sí | Aceptada |
| [0008](0008-sincronizar-entorno-local-de-paquetes.md) | Excepción: los agentes sincronizan el entorno local de los paquetes hermanos | sí | Aceptada |
| [0009](0009-escapestring-cede-al-marcador.md) | `escapeString()` cede al marcador y queda obsoleta; no se toca `sql_mode` | sí | Aceptada |
| [0010](0010-pruebas-contra-la-aplicacion-local.md) | Pruebas contra la aplicación local: navegador simulado y escrituras en la base de prueba | sí | Aceptada |
| [0011](0011-correo-real-a-mailinator.md) | Correo real de prueba, solo a buzones públicos de Mailinator | sí | Aceptada |
| [0012](0012-lf-en-los-cinco-repositorios.md) | Finales de línea LF en los cinco repositorios | sí | Aceptada |
| [0013](0013-gulp-sin-permiso-por-ronda.md) | Excepción: el coder compila con gulp cuando la instrucción lo dice | sí | Aceptada |
| [0014](0014-agents-md-es-la-fuente.md) | `AGENTS.md` es la fuente de las reglas; `CLAUDE.md`, un espejo | sí | Aceptada |
| [0015](0015-mailpit-como-sumidero-smtp.md) | Mailpit como sumidero SMTP local para las pruebas de correo | sí | Aceptada (sin implementar) |
| [0016](0016-guia-del-po-en-su-propio-repositorio.md) | La guía personal del PO vive en su propio repositorio; el arquitecto la escribe y la commitea | sí | Aceptada |
| [0017](0017-el-framework-actualiza-sus-paquetes.md) | Excepción: el framework actualiza sus paquetes `piecesphp/*` con Composer cuando un lote lo pide | sí | Aceptada |
| [0018](0018-recuperacion-de-contrasena.md) | La recuperación de contraseña es una sola: código ligado al usuario, con límite de intentos | sí | Aceptada |
| [0019](0019-versionado-y-etiquetas-del-framework.md) | Arquitecto y coder versionan y etiquetan el framework, salvo las versiones mayores estables | sí | Aceptada |
