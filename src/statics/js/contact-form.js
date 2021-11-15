/// <reference path="./CustomNamespace.js" />
/// <reference path="../core/js/helpers.js" />
window.addEventListener('load', function (e) {

	let formSelector = '[contact-form]'

	let captchaResponse = {}
	let captchaResult = false
	let captchaMessage = ''
	let captchaAdapter = new GoogleCaptchaV3Adapter({
		key: '6Lc9cTgdAAAAAMVBHJIk3i0XBOnNtyAV0Ijl6ZBv',
	})
	captchaAdapter.on('prepare', function () {
		captchaAdapter.execute(function (response, success, message) {
			captchaResponse = response
			captchaResult = success
			captchaMessage = message
		})
	})

	let form = genericFormHandler(formSelector, {
		validate: function (form) {
			let valid = true
			if (!captchaResult) {
				valid = false
				errorMessage(`<strong>${captchaMessage}</strong>`, '')
			}
			return valid
		},
		onSuccess: function () {
			form[0].reset()
		},
		onInvalidEvent: function (event) {
			/** @type {HTMLElement} */ let element = event.target
			let validationMessage = element.validationMessage
			let label = element.parentElement.querySelector('label')
			let nameOnLabel = label.innerHTML
			errorMessage(`${nameOnLabel}: ${validationMessage}`)
			event.preventDefault()
		}
	})

})
