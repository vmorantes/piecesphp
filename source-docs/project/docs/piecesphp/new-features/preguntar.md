# Preguntar al usuario: `confirmAction()`

En el panel **solo hay una forma de preguntar**: `confirmAction()`, la confirmación de Fomantic (un aviso con sus botones,
arriba en el centro), la misma que usa `genericFormHandler` antes de enviar un formulario. Ni `window.confirm`, ni
`alert`, ni `iziToast.question`: `bin/cli verify-integrity` los rechaza en todo `src/app` y `src/statics`.

Vive en `src/statics/core/js/helpers-lib/confirmAction.js` y entra en el JavaScript común del panel, así que está en
cualquier pantalla del panel sin cargar nada.

## Uso

```js
const ok = await confirmAction({
    title: 'Eliminar',
    message: '¿Seguro que quieres eliminar este documento?',
    confirmText: 'Sí, eliminar',
    cancelText: 'No',
    confirmClass: 'red',
})
if (ok) {
    // la acción
}
```

- Devuelve una `Promise` que resuelve `true` si se confirma y `false` si se cancela.
- `cancelText: null` deja un solo botón: sirve para **avisar** sin preguntar.
- `confirmClass` es la clase de Fomantic del botón de confirmar (`blue` por omisión; `red` para borrar).
- **Si en la página no está Fomantic**, no pregunta: escribe el mensaje en la consola y resuelve `false`, así nunca se
  ejecuta una acción sin que nadie la haya confirmado.

Para avisar de un error o de un resultado, sin preguntar, siguen `errorMessage()` y `successMessage()` de `helpers.js`.

Los textos por omisión («Confirmación», «¿Está seguro de realizar esta acción?», «Sí», «No», «Aceptar») salen del grupo
de traducción `confirmAction` (`src/statics/core/js/translations/es.js` y `src/statics/core/js/translations/en.js`): la pregunta sale en el idioma de la
pantalla. Lo que pases tú, tradúcelo en tu módulo.

Se ve funcionando, con su código, en la **hoja de diseño** del panel, sección de mensajes.
