/// <reference path="../../../js/helpers.js" />
/// <reference path="./jqcron/jqCron.js" />
showGenericLoader('crontab')
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
	let form = genericFormHandler(`form.crontab`, {
		onSetFormData: (formData) => {
			return onSetFormData(formData)
		},
		onInvalidEvent: onInvalidHandler,
	})

	let crontabsSelectors = $('[crontab]')
	crontabsSelectors.toArray().map(function (e) {
		let element = $(e)
		let inputID = element.attr('crontab')
		let crontabInput = $(`[crontab-input="${inputID}"]`)
		element.jqCron({
			default_value: '* 3 * * *', //Cada día a las tres de la mañana
			bind_to: crontabInput,
		})
	})

	removeGenericLoader('crontab')

})
