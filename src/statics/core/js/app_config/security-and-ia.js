/// <reference path="../../js/helpers.js" />
showGenericLoader('security-and-ia')
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
		formData.set('check_aud_on_auth', form.find(`[name="check_aud_on_auth"]`).parent().checkbox('is checked') ? 1 : 0)
		formData.set('translationAIEnable', form.find(`[name="translationAIEnable"]`).parent().checkbox('is checked') ? 1 : 0)
		return formData
	}
	let form = genericFormHandler(`form.security-and-ia`, {
		onSetFormData: (formData) => {
			return onSetFormData(formData)
		},
		onInvalidEvent: onInvalidHandler,
	})

	form.find('.ui.checkbox').checkbox()

	removeGenericLoader('security-and-ia')

})
