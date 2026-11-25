---
name: adaptar-diseno
description: >
  Procedimiento del arquitecto de un proyecto PiecesPHP cuando llega un diseño o una entrega nueva del diseñador para la
  zona pública: guardar el original, adaptarlo lo antes posible con la skill adapt-design del kit, comprobarlo con un
  censo funcional que hace otro agente, devolverle el adaptado al diseñador y, después, integrarlo en el proyecto.
  Invócala cuando se diga «adapta el diseño de <ruta>», «llegó el diseño», «nueva entrega del diseñador», o cuando haya
  que preparar o integrar el sitio público por marcas.
---

# Adaptar un diseño en el proyecto

El formato y las órdenes están en el manual «El sitio público por marcas» de la documentación del framework, y lo que
muerde, en `.agents/context/16-frontend-arquitectura.md` («El sitio público por marcas»). El trabajo de adaptar lo hace la skill `adapt-design` del kit (`src/app/classes/PiecesPHP/PublicSite/kit/skills/adapt-design/SKILL.md`):
esta skill dice el orden en el proyecto. El manual para personas es «Adaptar una plantilla, de principio a fin» de la
documentación del framework.

## 0. El resultado, y el reloj

UN adaptado y DOS sitios que funcionan: la versión del diseñador (`files/design/adaptado/`, doble clic, datos de ejemplo
que simulan al detalle) y la integrada (servida, datos reales). **La primera entrega es la del diseñador, y manda el
reloj**: mientras se adapta, el diseñador sigue trabajando sobre su versión vieja y lo adaptado se pierde. Del diseño se
toca solo lo imprescindible: las marcas y lo que finge.

## 1. Paso (a): adaptar y devolver, lo primero

1. **El original, en el repositorio**: copia el export tal cual a `files/design/base/` y que el coder lo commitee solo.
   Cada entrega nueva del diseñador es otro commit ahí: así se ve qué cambió.
2. **Las reglas del proyecto**: `files/design/PROJECT.md`, con lo que el diseñador debe respetar, los dominios del
   diseñador (pruebas, staging, Webflow) y dos secciones obligatorias, `## Censo funcional` y `## Pendientes conocidos`.
   Lo que escribas ahí viaja al diseñador sin revisar: no nombres la metodología ni las herramientas del proyecto.
3. **La carpeta de partida**, si no existe: `bin/cli public-site-export site=<nombre> destination=files/design/adaptado`
   (con un sitio sin importar sale del ejemplo del kit).
4. **Adaptar**: un agente sigue `adapt-design` sobre `files/design/base/` hacia `files/design/adaptado/`. Si un recurso
   del sitio todavía no existe en el proyecto, el coder registra su contrato estimado
   (`ResourceContract::estimated()`, con sus campos y al menos tantos ejemplos distintos como el `count` de la lista).
5. **Comprobar**: `files/design/adaptado/` no lleva `CHECK.html`: exporta el paquete a una carpeta temporal fuera del
   proyecto y que un agente abra su `CHECK.html` con un navegador; sin avisos (salvo un servicio externo legítimo,
   anotado en «Pendientes conocidos»). Y capturas a 1440 y a 390 de cada página frente a
   `base/` en lo estático.
6. **El censo funcional, por OTRO agente** (no el que adaptó): cada elemento interactivo y cada recurso externo de cada
   página, con su estado y su plan, en `## Censo funcional`; lo que quede, en `## Pendientes conocidos`. Con un «falso»
   sin decisión del PO, no se entrega.
7. **Devolver**: `bin/cli public-site-export from=files/design/adaptado destination=<carpeta fuera del proyecto> zip=yes`,
   y avisar al diseñador de que siga desde ese paquete. Si el sitio ya está importado con ese nombre, `from=` no vale:
   importa antes el adaptado y exporta con `site=<nombre>`.

## 2. Paso (b): integrar, después

1. Los contratos reales (el módulo registra su lector y sustituye al estimado).
2. `bin/cli public-site-import folder=files/design/adaptado site=<nombre>` y activar el sitio (`$config['public_site']`).
3. El comportamiento que el diseño pide y la marca no da (paginación servida, búsqueda, «cargar más» más allá de `count`)
   lo construye el proyecto en su integración: «es del framework» no es respuesta.
4. **Antes de «listo para producción»**: el censo funcional repetido sobre el sitio SERVIDO, por un agente que no lo
   integró, con cero «falso» sin decisión del PO. «Todas las páginas responden 200» no es una verificación.

## 3. Cuando el diseñador devuelve una entrega

- Va a `files/design/base/` en su propio commit; el diff contra la entrega anterior dice qué cambió.
- Si trabajó sobre el paquete adaptado, su carpeta se compara con `files/design/adaptado/` y lo que cambió se lleva ahí.
- Si trabajó sobre su versión vieja, se avisa al PO: el adaptado se rehace sobre la entrega nueva, y se le recuerda al
  diseñador que parta siempre del último paquete. Llevar los cambios de un export nuevo al adaptado sin rehacerlo está por
  estudiar.

## 4. Lo que no se hace

- Editar a mano `src/app/view/<sitio>/` o `src/statics/<sitio>/`: los genera la importación.
- Dar por terminado algo que nadie usó: lo comprueba otro agente.
- Publicar con «Pendientes conocidos» sin decisión del PO.
