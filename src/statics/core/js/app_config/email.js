/// <reference path="../../js/helpers.js" />
showGenericLoader('email')
window.addEventListener('load', () => {

	const onInvalidHandler = function (event) {

		let element = event.target
		let validationMessage = element.validationMessage
		let jElement = $(element)
		let field = jElement.closest('.field')
		let nameOnLabel = field.find('label').html()

		errorMessage(`${nameOnLabel}: ${validationMessage}`)

		event.preventDefault()

	}
	/**
	 * @param {FormData} formData 
	 */
	const onSetFormData = function (formData) {
		formData.set('auto_tls', form.find(`[name="auto_tls"]`).parent().checkbox('is checked') ? 1 : 0)
		formData.set('auth', form.find(`[name="auth"]`).parent().checkbox('is checked') ? 1 : 0)
		return formData
	}
	let form = genericFormHandler(`form.email`, {
		onSetFormData: (formData) => {
			return onSetFormData(formData)
		},
		onInvalidEvent: onInvalidHandler,
	})

	//Mostrar/ocultar contraseña
	form.find('[show-hide-password-event] .icon').on('click', function (e) {
		let that = $(e.target)
		let parent = that.parent()
		let input = parent.find('input')

		if (input.attr('type') == 'text') {
			that.removeClass('eye slash')
			that.addClass('eye')
			input.attr('type', 'password')
		} else {
			that.removeClass('eye')
			that.addClass('eye slash')
			input.attr('type', 'text')
		}
	})

	form.find('.ui.checkbox').checkbox()

	//Los campos del servidor de pruebas solo pintan cuando el modo no está apagado (P52).
	const testModeSelect = form.find('[name="test_mode"]')
	const testTargets = form.find('[data-mail-test-target]')
	const refreshTestFields = function () {
		let off = testModeSelect.val() === 'off'
		testTargets.each(function () {
			let field = $(this)
			field.css('display', off ? 'none' : '')
			field.find('input').prop('required', !off)
		})
	}
	testModeSelect.on('change', refreshTestFields)
	refreshTestFields()

	removeGenericLoader('email')

})
