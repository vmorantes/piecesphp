/// <reference path="../configurations.js" />
/// <reference path="../helpers.js" />
window.addEventListener('load', function () {

	//Ningún formulario de la hoja envía. Se corta en captura, antes que genericFormHandler, y también en la ventana de
	//formulario, que Fomantic saca del contenedor de la hoja al abrirla.
	const inSheet = (element) => element.closest('[design-sheet]') !== null || element.closest('.ui.modal[id^="design-sheet-"]') !== null

	document.addEventListener('submit', function (event) {
		if (inSheet(event.target)) {
			event.preventDefault()
			event.stopImmediatePropagation()
			$(event.target).closest('.ui.modal').modal('hide')
		}
	}, true)

	//El índice, plegado al entrar; solo se ve plegable a menos de 768 px (design-sheet.scss).
	$('.design-sheet-index-accordion').accordion()

	//Los enlaces de muestra (`#`) no mueven la página.
	document.addEventListener('click', function (event) {
		const link = event.target.closest('[design-sheet-examples] a[href="#"]')
		if (link !== null) {
			event.preventDefault()
		}
	})

})
