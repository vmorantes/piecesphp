# 0005 — Excepción de este repositorio: el coder commitea sin pedir permiso commit a commit

- **Estado:** Aceptada
- **Fecha:** 2026-09-14
- **Decide:** Product Owner
- **Estructural:** sí (cómo se trabaja; excepción a una regla general del PO)

## En cristiano

En este repositorio el coder puede preparar y commitear, sin pedir permiso para cada commit, el
trabajo que el PO haya nombrado y el arquitecto haya instruido. Los commits siguen siendo
atómicos, uno por unidad coherente. Subir al remoto y lo demás que el PO se reservó sigue siendo
suyo. Su regla general (`00-core.md`) no se reescribe: lleva una línea que remite aquí.

## Contexto

- `00-core.md`, regla general del PO: *«Nunca hagas `git add`, `git commit`, `git push`, ni
  ninguna otra operación que modifique el estado de git … sin pedírmelo primero
  explícitamente.»*
- Con el PO de mensajero, el recuadro que él transportaba hacía de permiso. Con el canal directo
  (ADR 0001) ya no transporta. El coder lo señaló en `#004`: un mensaje del arquitecto no es un
  permiso del PO, ni para la regla ni para su propia herramienta.
- El PO, el 2026-09-14: *«Bueno, debe corregirse al nuevo flujo. Pero la atomicidad de commits
  debe seguir presente. Pueden hacerlo, solo los push son míos y demás cosas que hemos
  mencionado.»*

## Decisión

El PO da una autorización permanente, escrita en `.agents/estado/AHORA.md`: el coder commitea,
en commits atómicos, el trabajo que el PO nombra y el arquitecto instruye, sin push y sin nada
de lo reservado.

## Qué sigue reservado al PO

- `git push` y cualquier operación contra un remoto.
- Etiquetar `piecesphp`; tocar su `master` o su `last-stable`.
- Crear ramas, salvo `dev` en los cuatro paquetes.
- Reescribir historia: `rebase`, `--amend`, `reset --hard` y parecidas.
- Escribir o destruir datos de una base de datos, salvo lo que la instrucción nombre con su
  autorización.
- Dependencias, builds, servidores y credenciales.

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| Permiso commit a commit | El PO no lo quiere, y con él los tramos autónomos se paran en cada commit |
| Reescribir el texto general de `00-core.md` | Es la regla general del PO y vale igual en sus otros proyectos. La excepción se registra en este repositorio, con una línea en la regla que remite aquí |
| Que commitee el arquitecto | Es juntar decidir e implementar en el mismo actor, que es lo que el modelo evita (ADR 0001) |

## Consecuencias

- Los tramos pueden encadenar rondas con sus commits sin detenerse.
- Hay más commits sin que un humano los revise antes. Lo compensan varias cosas:
  - la guarda de hooks;
  - `verificar.sh` y las puertas del producto;
  - la lectura del código del coder por el arquitecto;
  - la regla de los diez archivos (18 T0bis);
  - que nada sale del equipo sin el push del PO, que revisa antes de subir.
- Un commit local equivocado se corrige con otro commit, nunca reescribiendo: `--amend` y
  `rebase` siguen prohibidos.

## Reversión

1. Quitar de `.agents/estado/AHORA.md` la línea de autorización. Desde ese momento el coder se
   detiene antes de preparar (regla 30, «Commits»).
2. Quitar de `00-core.md` la línea que remite aquí.
3. En `30-protocolo-coder.md`, «Autorización», volver a «permiso del PO commit a commit».

Los commits ya hechos se quedan. Reversión completa y no destructiva.

## Verificación

- `AHORA.md` lleva la línea de autorización con su fecha.
- Cada instrucción con commits la cita.
- La guarda sigue bloqueando `push`, `--amend`, `rebase` y crear ramas
  (`probar_guardia.py`).
