/// <reference path=".././CustomNamespace.js" />
/// <reference path="../../core/js/helpers.js" />
window.addEventListener('loadApp', function (e) {

	let formSelector = '[global-contact-form]'

	let form = genericFormHandler(formSelector, {
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
