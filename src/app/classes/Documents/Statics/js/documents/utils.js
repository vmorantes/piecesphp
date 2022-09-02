/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../statics/core/own-plugins/CropperAdapterComponent.js" />
/// <reference path="../../../../../../statics/core/own-plugins/SimpleUploadPlaceholder.js" />
var Documents = {}

Documents.configDocumentForm = function (onSuccess = null, ignoreRedirection = false, ignoreReload = false) {

	showGenericLoader('configDocumentForm')

	let formSelector = `.ui.form[document-form]`
	let langGroup = 'appDocumentsLang'
	let isEdit = false

	configFomanticDropdown('.ui.dropdown') //Debe inciarse antes de genericFormHandler para la validación

	const documentImage = new CropperAdapterComponent({
		containerSelector: '[document-image-main]',
		minWidth: 300,
		outputWidth: 300,
		cropperOptions: {
			aspectRatio: 1 / 1,
		},
	})

	const documentContainer = $(`[document]`)
	const document = new SimpleUploadPlaceholder({
		containerSelector: `[document]`,
		onReady: function () {
		},
		onChangeFile: (files, component, instance, event) => {
			const fileInput = files[0]
			if (isEdit) {
				const previewContainer = $(documentContainer).find('[preview]')
				if (fileInput.type.indexOf('image/') !== -1) {
					const reader = new FileReader()
					reader.readAsDataURL(fileInput)
					reader.onload = function (e) {
						previewContainer.html(`<img src="${e.target.result}"/>`)
					}
				} else {
					previewContainer.html('')
				}
			}
		},
	})

	let form = genericFormHandler(formSelector, {
		onSuccess: typeof onSuccess == 'function' ? onSuccess : () => { },
		onSetFormData: function (formData) {

			if (isEdit) {
				formData.set('documentImage', documentImage.getFile(null, null, null, null, true))
			} else {
				formData.set('documentImage', documentImage.getFile())
			}

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

		},
		ignoreRedirection: ignoreRedirection,
		ignoreReload: ignoreReload,
	})

	isEdit = form.find(`[name="id"]`).length > 0

	form.find('input, select, textarea').attr('autocomplete', 'off')
	$('.tabular.menu .item').tab()

	configLangChange('.ui.dropdown.langs')

	function configLangChange(dropdownSelector) {

		let dropdown = $(dropdownSelector)

		dropdown.dropdown({
			/**
			 * 
			 * @param {Number|String} value 
			 * @param {String} innerText 
			 * @param {$} element 
			 */
			onChange: function (value, innerText, element) {
				showGenericLoader('redirect')
				window.location.href = value
			},
		})

	}

	removeGenericLoader('configDocumentForm')

}
