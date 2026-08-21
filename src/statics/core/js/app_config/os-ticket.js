/// <reference path="../../js/helpers.js" />
showGenericLoader('os-ticket')
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
		return formData
	}
	let form = genericFormHandler(`form.os-ticket`, {
		onSetFormData: (formData) => {
			return onSetFormData(formData)
		},
		onInvalidEvent: onInvalidHandler,
	})

	removeGenericLoader('os-ticket')

})
