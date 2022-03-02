/// <reference path="../../js/helpers.js" />
/// <reference path="../../own-plugins/CropperAdapterComponent.js" />
showGenericLoader('backgrounds')
window.addEventListener('load', () => {

	let backgroundQty = 5
	/**
	 * @type {CropperAdapterComponent[]} croppers
	 */
	let croppers = []
	let forms = []

	for (let i = 1; i <= backgroundQty; i++) {

		croppers[i] = new CropperAdapterComponent({
			containerSelector: `form[bg="${i}"] .cropper-adapter`,
			minWidth: 1920,
			outputWidth: 1920,
			cropperOptions: {
				aspectRatio: 16 / 9,
			},
			outputFormat: 'image/jpeg',
		})

		forms[i] = genericFormHandler(`form[bg="${i}"]`, {
			onSetFormData: function (formData) {
				formData.set(`background-${i}`, croppers[i].getFile(null, 0.2, null, null, true))
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

	}

	removeGenericLoader('backgrounds')

})
