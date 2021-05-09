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

	let langs = $('form[lang]').toArray().map((e) => e.getAttribute('lang'))

	for (let lang of langs) {

		let cropperOpenGraph = instantiateCropper(`form.seo[lang="${lang}"] .cropper-adapter`)
		let form = genericFormHandler(`form.seo[lang="${lang}"]`, {
			onSetFormData: (formData) => {
				return onSetFormData(formData, cropperOpenGraph, `open-graph`)
			},
			onInvalidEvent: onInvalidHandler,
		})

		form.find('.ui.dropdown.keywords').dropdown({
			allowAdditions: true,
		})
	}

	$('.menu .item').tab()

	removeGenericLoader('seo')

})
