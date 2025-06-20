/// <reference path="../../../../../../../..//statics/core/js/configurations.js" />
/// <reference path="../../../../../../../..//statics/core/js/helpers.js" />
/// <reference path="../../../../../../../..//statics/core/own-plugins/RichEditorAdapterComponent.js" />
showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	let formSelector = `.ui.form.generic`

	//Formulario
	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData) {
			return formData
		},
		onInvalidEvent: function (event) {

			let element = event.target
			let validationMessage = element.validationMessage
			let jElement = $(element)
			let field = jElement.closest('.field')
			let nameOnLabel = field.find('label').html()

			errorMessage(`${nameOnLabel}: ${validationMessage}`)

			event.preventDefault()

		}
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
	$('.ui.accordion').accordion()

	removeGenericLoader('_CARGA_INICIAL_')

})


