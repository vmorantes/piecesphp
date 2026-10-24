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

	removeGenericLoader('email')

	//Las pruebas del correo: el resultado y a dónde fue, siempre como TEXTO (el error del servidor viene tal cual).
	const tests = document.querySelector('[data-mail-tests]')
	if (tests !== null) {
		const result = tests.querySelector('[data-mail-test-result]')
		const paint = function (data) {
			result.hidden = false
			result.classList.toggle('positive', data.success === true)
			result.classList.toggle('negative', data.success !== true)
			result.classList.add('visible')
			tests.querySelector('[data-mail-test-message]').textContent = typeof data.message == 'string' ? data.message : ''
			tests.querySelector('[data-mail-test-route]').textContent = typeof data.route == 'string' ? data.route : ''
		}
		const run = function (button, url, body) {
			button.classList.add('loading', 'disabled')
			fetch(url, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
				.then((response) => response.json())
				.then(paint)
				.catch(() => paint({ success: false, message: _i18n('appConfig', 'No se pudo completar la prueba.') }))
				.finally(() => button.classList.remove('loading', 'disabled'))
		}
		tests.querySelector('[data-mail-probe]').addEventListener('click', function (event) {
			run(event.currentTarget, tests.getAttribute('data-probe-url'), new FormData())
		})
		tests.querySelector('[data-mail-test-send]').addEventListener('click', function (event) {
			const body = new FormData()
			body.set('to', tests.querySelector('[data-mail-test-to]').value)
			run(event.currentTarget, tests.getAttribute('data-test-send-url'), body)
		})
	}

})
