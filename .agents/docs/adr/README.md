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
| [0020](0020-phpstan-mide-solo-php-85.md) | PHPStan mide solo PHP 8.5 en los cinco repositorios | sí | Aceptada |
| [0021](0021-claves-recaptcha-de-prueba-en-el-repositorio.md) | Excepción: las claves de reCAPTCHA v3 de prueba del propietario se versionan | sí | Aceptada |
| [0022](0022-importacion-y-exportacion-en-el-nucleo.md) | Importación y exportación: motor en el núcleo, un solo módulo de panel | sí | Aceptada |
| [0023](0023-usuario-de-prueba-del-agente.md) | El coder crea y usa su propio usuario de prueba local | sí | Aceptada |
| [0024](0024-lectura-y-escritura-directa-en-la-base-local.md) | El coder lee la base local directamente, y escribe en ella en las rondas que lo autorizan | sí | Aceptada |
| [0025](0025-escritura-en-la-base-local.md) | El coder escribe en la base local sin pedirlo ronda a ronda | sí | Aceptada |
| [0026](0026-revocacion-de-sesiones.md) | Cómo se revoca una sesión: marcas primero, almacén después | sí | Aceptada |
| [0027](0027-cuando-se-actualizan-las-dependencias.md) | Cuándo se actualizan las dependencias, y con qué criterio se acepta la actualización | sí | Aceptada |
| [0028](0028-taxonomia-de-la-configuracion-del-panel.md) | La taxonomía de la configuración del panel, y por qué el menú y la URL no se obligan a coincidir | sí | Aceptada |
| [0029](0029-herencia-tope-y-recados.md) | La herencia entre sesiones del arquitecto, el tope de `AHORA.md` y los recados | sí | Aceptada, sin implementar |
| [0030](0030-seguridad-e-ia-se-separan.md) | «Seguridad e IA» se separa en dos pantallas, antes de la MAJOR | sí | Aceptada |
| [0031](0031-la-mayor-estable-cuando-sea-verdadera.md) | La `v8.0.0` la etiquetan arquitecto y coder, cuando sea verdadera | sí | Aceptada |
| [0032](0032-identidad-seo-scripts-y-archivos-de-raiz.md) | Identidad y SEO: scripts por zona, metadatos completos y los archivos de raíz por ruta | sí | Aceptada; su §1 lo corrige el 0033 |
| [0033](0033-scripts-solo-en-los-cinco-layouts.md) | Los scripts inyectados solo en los cinco layouts; el acceso queda fuera (reemplaza parte del 0032) | sí | Aceptada |
| [0035](0035-cualquier-tarea-de-gulp.md) | Cualquier tarea de gulp, cuando haga falta (amplía el 0013) | sí | Aceptada |
| [0036](0036-metadatos-completos-y-datos-estructurados.md) | Los metadatos completos y los datos estructurados (desarrolla el §2 del 0032) | sí | Aceptada; la canónica la precisa el 0037 |
| [0037](0037-canonica-con-idioma-en-la-consulta.md) | La canónica lleva el idioma cuando el idioma viaja en la consulta; dónde manda el título para compartir | sí | Aceptada |
| [0034](0034-version-por-archivo-de-los-estaticos.md) | Una versión por archivo para los estáticos, y caché larga solo para lo versionado | sí | Aceptada |
| [0038](0038-politica-de-respaldos.md) | Política de respaldos: intervalo, conservación por niveles y tablas sin filas, configurables por root | sí | Aceptada |
| [0039](0039-sesiones-caducadas-sin-token.md) | Una sesión caducada deja una línea, no un archivo con el token (encargo del PO del 2026-10-02) | sí | Aceptada |
| [0040](0040-mautic-local-del-po.md) | La instalación local de Mautic del PO: qué se puede tocar para probar el envío masivo | sí | Aceptada |
| [0041](0041-sumidero-http-local.md) | Un sumidero HTTP local, lo que Mailpit es para el correo (el PO, P93) | sí | Aceptada |
| [0042](0042-la-credencial-de-mautic-es-la-del-po.md) | La credencial de Mautic es la del PO: su CSRF no se fuerza (reemplaza parte del 0040) | sí | Aceptada |
| [0043](0043-el-correo-no-se-escapa-ni-se-traga.md) | El correo no se escapa en local ni se traga en producción (el PO, antes de la estable) | sí | Aceptada |
| [0044](0044-servicios-de-desarrollo-locales.md) | Los servicios de desarrollo LOCALES se pueden usar; la red y su entorno, no (generaliza 0010, 0015, 0024, 0040-0042) | sí | Aceptada |
| [0045](0045-el-sink-se-cumple-en-el-envio.md) | «Retenido» se cumple en el envío, no solo desviando el destino (amplía el 0043) | sí | Aceptada |
| [0046](0046-la-sonda-smtp-no-sale-sola.md) | La sonda SMTP de `mail-doctor` no sale sola a la red (amplía el 0043 §6) | sí | Aceptada |
| [0047](0047-la-verificacion-empieza-limpia.md) | La verificación empieza limpia, y si no, lo dice (la última condición para la `v8.0.0`) | sí | Aceptada |
| [0048](0048-el-cuerpo-del-correo-se-captura-ahora.md) | El cuerpo del correo se captura ahora, cifrado; la vista centralizada (encargo 133), en la 8.1 | sí | Aceptada |
| [0049](0049-que-viaja-al-repositorio-publico.md) | Qué viaja al repositorio público y qué se queda en el de desarrollo (resuelve la contradicción capas/exclusión) | sí | Aceptada |
| [0050](0050-secure-keys-viaja-con-su-proteccion.md) | `secure-keys/` viaja al repositorio público con sus tres archivos de protección (reemplaza el 0049 §4) | sí | Aceptada |
| [0051](0051-el-cuerpo-se-cifra-con-clave-derivada.md) | El cuerpo del correo se cifra con una clave derivada de la de la aplicación (precisa el 0048 §2; su §2 lo reemplaza el 0052) | sí | Aceptada |
| [0052](0052-la-clave-de-relleno-solo-impide-fuera-de-local.md) | Con la clave de relleno, el cuerpo del correo no se guarda solo fuera de una instalación local (reemplaza el 0051 §2) | sí | Aceptada |
| [0053](0053-la-candidata-de-la-estable-ya-dice-v8.md) | La candidata de la estable ya dice `v8.0.0` por dentro, y las dos etiquetas van al mismo commit (precisa el 0031) | no | Aceptada |
