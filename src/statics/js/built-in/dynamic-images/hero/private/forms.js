/// <reference path="../../../../../core/js/configurations.js" />
/// <reference path="../../../../../core/js/helpers.js" />

showGenericLoader('_CARGA_INICIAL_')

window.addEventListener('load', function () {

	window.dispatchEvent(new Event('canDeleteHeroImageConfig'))

	let isEdit = false
	let formSelector = `.ui.form.dynamic-images-hero`

	let cropperAdapter = new CropperAdapterComponent({
		containerSelector: '[cropper-main-image]',
		minWidth: 1400,
		outputWidth: 1400,
		cropperOptions: {
			aspectRatio: 1400 / 700,
		},
	})

	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData) {

			if (isEdit) {
				formData.set('image', cropperAdapter.getFile(null, null, null, null, true))
			} else {
				formData.set('image', cropperAdapter.getFile())
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


