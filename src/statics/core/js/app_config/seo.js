/// <reference path="../../js/helpers.js" />
/// <reference path="../../own-plugins/CropperAdapterComponent.js" />
showGenericLoader('seo')
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
	const instantiateCropper = (selector, w = 1200, ow = 1200, ar = 1200 / 630) => {
		return new CropperAdapterComponent({
			containerSelector: selector,
			minWidth: w,
			outputWidth: ow,
			allowResizeCrop: true,
			cropperOptions: {
				viewMode: 1,
				aspectRatio: ar,
				cropBoxResizable: true,
			},
		})
	}
	/**
	 * @param {FormData} formData 
	 * @param {CropperAdapterComponent} cropper 
	 * @param {String} name 
	 */
	const onSetFormData = function (formData, cropper, name) {
		formData.set(name, cropper.getFile(null, null, null, null, true))
		return formData
	}

	let cropperOpenGraph = instantiateCropper(`form.seo .cropper-adapter`)
	let form = genericFormHandler(`form.seo`, {
		onSetFormData: (formData) => {
			return onSetFormData(formData, cropperOpenGraph, `open-graph`)
		},
		onInvalidEvent: onInvalidHandler,
	})

	form.find('.ui.dropdown.keywords').dropdown({
		allowAdditions: true,
	})

	removeGenericLoader('seo')

})
