# IDEA · Guías de estilo por lenguaje, para humanos Y para agentes

**Intención declarada por el PROPIETARIO — 2026-08-30. SIN RESOLVER.**

> *«Basado en mi código y preferencias podemos tener guías de estilo para programadores y agentes
> de js, css, scss, php, etc… Quizás para el final de campaña donde ajustamos docs.»*

## Reparto: esto NO entra en la campaña

Regla del PROPIETARIO: **lo que CORRIGE una trampa entra; lo que EXTIENDE una capacidad, no.**
Una guía de estilo extiende. Va DESPUÉS de la MAJOR.

Lo que sí queda dentro es `16-frontend-arquitectura.md` (E6), que es **descriptivo**: dice qué hay.
La guía es **normativa**: dice qué hacer. Son dos artefactos y el segundo se apoya en el primero.
Confundirlos hace crecer E6 sin fondo, y «toda es toda» ya es una promesa cara.

## La condición que la hace valer algo: SE EXTRAE, NO SE OPINA

Una guía de estilo escrita de memoria es una lista de preferencias, y no sobrevive al primer
desacuerdo. Esta se saca del código con el mismo instrumento que la campaña:

**Cada regla llega con su censo detrás.** No «usa `const`» sino «126 JS: N con `const`, M con
`let`, K con `var`; la forma dominante es X y estas P son la excepción». Igual que se hizo con las
comparaciones a cero, los retornos ignorados, las claves huérfanas y las guardas.

Consecuencia incomoda y hay que aceptarla: **el censo va a señalar sitios donde el propio
PROPIETARIO no se sigue a sí mismo.** Eso no es un reproche, es el material: la regla se escribe
sobre la forma dominante y las excepciones se declaran o se corrigen, una por una.

## La guía para AGENTES no es la misma que para humanos

Un humano lee una guía y la recuerda a medias. Un agente la incumple **en silencio y a escala**, y
en este proyecto eso ya pasó: el desbalance de `<div>` en `generic-report-view.php` no lo produjo
un despiste humano.

Por eso, para agentes:

- **Cada regla que importe termina en un `bin/censo-*` o en una comprobación de
  `verify-integrity`.** Es LEY 11 aplicada al estilo: *una regla que falla deja de ser regla y pasa
  a ser mecanismo.* La prosa es la explicación; la puerta es la regla.
- **La guía dice QUÉ NO ATRAPA cada puerta**, igual que pide la guía personal. Un agente que cree
  que una puerta verde lo cubre todo hace daño con confianza.
- **Las reglas se ordenan por consecuencia**, no por lenguaje. La primera no es la indentación: es
  «una edición en una vista se cierra CONTANDO ETIQUETAS».

## Lo que YA es estilo mecanizado, y no hay que reinventar

Antes de escribir una línea, inventariar lo que ya decide forma sin pedir permiso:

`.editorconfig` y `.gitattributes` (CRLF, y `bin/` en LF) · `bin/normaliza-eol` · el baseline de
PHPStan · `narrative-comments.json` y la anotación `@codigo-comentado` · la comprobación de
balance de etiquetas (178 vistas) · el trinquete de retornos ignorados · el censo de claves
huérfanas · `integrity-signatures.json` · Rector.

**La guía no repite eso: lo apunta y explica cómo se lee.**

## Lo que está sólo en la cabeza del PROPIETARIO y hay que sacarle

Esto es lo caro, y es lo único que un agente no puede deducir del árbol:

- **PHP** — el módulo como patrón: `*Routes` + `*Controller` + `*Mapper` + `Views/` + `Statics/`,
  con `ENABLE`, `staticRoute()`, `injectLang()` y `init()` autoprotegido. Qué es obligatorio y qué
  es costumbre. `_allowedRoute()` como único punto de variación de acceso.
- **JS** — la capa de adaptadores propios (`LocationsAdapter`, `MapBoxAdapter`, `NewsAdapter`…)
  sobre librerías de terceros; el DOM se consulta **por atributo**, no por clase; `helpers.js` y
  `configurations.js` como núcleo; el `Proxy` de `configurations.js:113` como costura de i18n.
  Y la regla que falta y que este mismo bloque destapó: **un selector sin productor puede ser
  residuo, contrato con HTML de fuera, o gancho opcional — y los dos últimos NO se retiran.**
- **SCSS/CSS** — cómo conviven las variables CSS que inyecta `foundHandler` con Sass; por qué
  `src/statics/plugins` está fuera de todo censo; los silencios de Sass (ya en roadmap aparte).
- **Comentarios** — el proyecto tiene comentario NARRATIVO deliberado, y una campaña entera
  discutiendo cuánto comentar. Eso es estilo, y es de los que más se nota en un clon.

## Prueba de aceptación

La misma que se le pide a `DataImportExportUtility`: **¿puede alguien que acaba de clonar escribir
un módulo nuevo que parezca escrito por el PROPIETARIO, sin leerse el núcleo?** Si no, la guía es
decorado.
