window.addEventListener('load', function () {
	//Las cuatro de helpers.js (título, mensaje, alCerrar, opciones): el toast de Fomantic (sin él, confirmAction). genericFormHandler ya las usa.
	const toasts = {
		success: successMessage,
		info: infoMessage,
		warning: warningMessage,
		error: errorMessage,
	}
	$('[example-toast]').on('click', function () {
		const button = $(this)
		toasts[button.attr('example-toast')](button.data('title'), button.data('message'))
	})
})
