showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	let formSelector = `.ui.form.generic`

	//Formulario: uno por cada uno, porque el manejador envía el primero de lo que recibe (la página de MapBox tiene dos).
	let form = $(formSelector)
	form.each(function () {
		genericFormHandler($(this), {
			onSetFormData: function (formData) {
				return formData
			},
		})
	})

	//Otros
	form.find('input, select, textarea').attr('autocomplete', 'off')
	form.find('.checkbox').checkbox({
		onChange: function () {
			const nextResultsDateNotAvailableCheck = $('[name="nextResultsDateNotAvailable"]')
			if (nextResultsDateNotAvailableCheck.length > 0) {
				if (nextResultsDateNotAvailableCheck.is(':checked')) {
					$('.field[calendar-js]').addClass('disabled')
					$('.field[calendar-js] input').attr('disabled', true)
				} else {
					$('.field[calendar-js]').removeClass('disabled')
					$('.field[calendar-js] input').attr('disabled', false)
					$('.field[calendar-js] input').removeAttr('disabled')
				}
			}
		}
	})
	//Solo los del formulario: uno global reiniciaba el de la navegación del shell sin su selector.ignore del riel.
	form.find('.ui.accordion').accordion()

	removeGenericLoader('_CARGA_INICIAL_')

})


