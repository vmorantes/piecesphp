/// <reference path="../../js/helpers.js" />
showGenericLoader('ai')
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
		formData.set('translationAIEnable', form.find(`[name="translationAIEnable"]`).parent().checkbox('is checked') ? 1 : 0)
		return formData
	}
	let form = genericFormHandler(`form.ai`, {
		onSetFormData: (formData) => {
			return onSetFormData(formData)
		},
		onInvalidEvent: onInvalidHandler,
	})

	form.find('.ui.checkbox').checkbox()

	removeGenericLoader('ai')

})
