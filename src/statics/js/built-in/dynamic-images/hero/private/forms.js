/// <reference path="../../../../../core/js/configurations.js" />
/// <reference path="../../../../../core/js/helpers.js" />
/// <reference path="../../../../../core/own-plugins/CropperAdapterComponent.js" />
showGenericLoader('_CARGA_INICIAL_')

window.addEventListener('load', function () {

	window.dispatchEvent(new Event('canDeleteHeroImageConfig'))

	let isEdit = false
	let formSelector = `.ui.form.dynamic-images-hero`

	/**
	 * @type {Map<String, CropperAdapterComponent>}
	 */
	let cropperAdaptersByLang = new Map()
	let commonCropperAdapterSelector = '[cropper-adapter]'
	let commonCropperConfig = {
		containerSelector: '',
		minWidth: 1400,
		outputWidth: 1400,
		cropperOptions: {
			aspectRatio: 1400 / 700,
		},
	}

	let adapters = document.querySelectorAll(commonCropperAdapterSelector)

	for (let adapter of adapters) {

		let lang = adapter.dataset.lang
		let configCropper = Object.assign({}, commonCropperConfig)
		configCropper.containerSelector = `${commonCropperAdapterSelector}[data-lang="${lang}"]`
		cropperAdaptersByLang.set(lang, new CropperAdapterComponent(configCropper))
	}

	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData) {

			for (let key of cropperAdaptersByLang.keys()) {

				let adapter = cropperAdaptersByLang.get(key)
				let file = isEdit ? adapter.getFile(null, null, null, null, true) : adapter.getFile()

				formData.append(`image-${key}`, file)

			}

			return formData

		},
		onInvalidEvent: function (event) {

			let element = event.target
			let validationMessage = element.validationMessage
			let jElement = $(element)
			let field = jElement.parents('.field')
			let nameOnLabel = field.find('label').html()

			errorMessage(`${nameOnLabel}: ${validationMessage}`)

			event.preventDefault()

		}
	})

	form.find('input, select, textarea').attr('autocomplete', 'off')
	form.find('.ui.dropdown').dropdown()

	isEdit = form.find(`[name="id"]`).length > 0

	removeGenericLoader('_CARGA_INICIAL_')

})


