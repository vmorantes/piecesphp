# 0012 — Finales de línea LF en los cinco repositorios

- **Estado:** Aceptada
- **Fecha:** 2026-09-15
- **Decide:** Product Owner
- **Estructural:** sí (cambia la configuración global de los cinco repositorios)

## En cristiano

Los archivos de PiecesPHP y de sus cuatro paquetes pasan a usar un solo tipo de salto de línea,
el de Linux (LF), en lugar del de Windows (CRLF). El cambio no altera el contenido de ningún
archivo en la historia de git, porque git ya guardaba LF: solo cambia lo que hay en el disco de
quien trabaja. Se hace porque casi todas las herramientas de esta máquina escriben LF, y cada
una que tocaba un archivo en CRLF lo dejaba mezclado. Eso obligaba a arreglarlo a mano y
confundía las comparaciones.

## Contexto

- **La política era CRLF en el disco**:
  - `.gitattributes`: `* text=auto eol=crlf`;
  - `.editorconfig`: `[*] end_of_line = crlf`;
  - lo mismo en los cuatro paquetes, con la regla «si se toca aquí, se toca igual en los
    cuatro».
- **Git ya guardaba LF.** Medido el 2026-09-15 con `git ls-files --eol`, ningún archivo tiene
  CRLF en el índice:

  | Repositorio | `i/lf` | `i/none` | `i/-text` | Sin clasificar |
  | --- | --: | --: | --: | --: |
  | piecesphp | 1.571 | 299 | 183 | 13 |
  | Cada paquete | 74-134 | 1 | — | 7 |

- **En el disco de piecesphp**, 1.429 de los 2.066 archivos versionados estaban en CRLF (medido
  con `file` sobre `git ls-files`).
- **Casi todo lo que escribe en esta máquina pone LF:** Python, `sed`, PHP y las herramientas
  de edición de los agentes. El resultado eran archivos mezclados, y hacían falta herramientas
  para repararlos:
  - `bin/normaliza-eol` y `bin/anexar`;
  - el incidente de 20 archivos citado en el propio `.gitattributes`;
  - las huellas `sha256` de disco que no casan con las de git (`#052`).
- **El `.editorconfig` tenía además dos defectos**, medidos por lectura:
  - `[{yaml,neon}]` solo casa con archivos llamados literalmente `yaml` o `neon`, así que nunca
    se aplicó. Si se hubiera aplicado, habría puesto tabuladores en YAML, que no los admite para
    sangrar. Los `.neon` y `.yml` versionados sangran con espacios.
  - El comentario de `[*]` decía «Unix-style newlines» y declaraba CRLF.
- **El PO, el 2026-09-15**, formalizado: si LF es lo más universal, se usa LF.

## Decisión

Los cinco repositorios declaran LF para todo el texto, en `.gitattributes`
(`* text=auto eol=lf`) y en `.editorconfig` (`end_of_line = lf`). Los archivos del disco se
convierten una vez, con `bin/normaliza-eol --arregla`.

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| Seguir con CRLF y reparar con herramientas | Es la situación que se deja. Cada escritura de una herramienta en LF es un arreglo pendiente, y las comparaciones por huella fallan |
| `eol` sin declarar, que cada sistema decida | En Windows dejaría CRLF y en Linux LF: las huellas de disco dejarían de ser comparables entre máquinas |
| Solo piecesphp, sin los paquetes | Rompe la política común, y `verify-integrity` vigila el instrumental común |

## Consecuencias

- **Lo bueno:**
  - las herramientas y el disco coinciden, y los archivos mezclados dejan de producirse;
  - la huella de un archivo del disco pasa a ser la de git;
  - `bin/normaliza-eol` sigue sirviendo como red.
- **Lo malo:**
  - un editor de Windows que no respete `.editorconfig` verá LF. Los editores actuales lo
    manejan;
  - las respuestas HTML de las vistas cambian sus saltos (`\r\n` → `\n`), así que una foto
    HTTP comparada por bytes, tomada antes, deja de ser comparable. Por eso la conversión se
    hace entre rondas, sin fotos pendientes;
  - hay que corregir la documentación que describe CRLF: `12-convenciones.md:301` y el
    comentario de `bin/anexar`.
- **Queda igual:** las líneas explícitas `eol=lf` de `.gitattributes` (`*.sh`, `bin/*`, los
  hooks, `*.lock`, `PHPStanResult.*`) se conservan. Son redundantes, pero protegen esos
  archivos si la política cambiara otra vez.

## Reversión

1. En los cinco repositorios, devolver `* text=auto eol=crlf` en `.gitattributes` y
   `end_of_line = crlf` en `[*]` y `[*.js]` de `.editorconfig`.
2. Con el árbol limpio, correr `bin/normaliza-eol --arregla` en cada uno: el disco vuelve a
   CRLF.
3. Comprobar que `git status` solo muestra los dos archivos de configuración.

La reversión es completa y no destructiva: la historia no cambia en ningún sentido.

## Verificación

- `bin/normaliza-eol` sin argumentos, en cada repositorio: «0 fuera de forma».
- `git status` tras la conversión: solo los dos archivos de configuración de cada repositorio.
- `git ls-files --eol`: nada con `w/crlf` ni `w/mixed`.
