/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../statics/core/own-plugins/ContentImageEditor.js" />
/// <reference path="../../../../../../statics/core/own-plugins/SimpleUploadPlaceholder.js" />
var Documents = {}

Documents.configDocumentForm = function (onSuccess = null, ignoreRedirection = false, ignoreReload = false) {

	showGenericLoader('configDocumentForm')

	let formSelector = `.ui.form[document-form]`
	let langGroup = 'appDocumentsLang'
	let isEdit = false

	configFomanticDropdown('.ui.dropdown') //Debe inciarse antes de genericFormHandler para la validación

	//La tarjeta de imagen: lo aplicado en el editor se envía al guardar.
	let documentImage = null
	new ContentImageEditor().onApply('document-image', (file) => documentImage = file)

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

			if (documentImage !== null) {
				formData.set('documentImage', documentImage)
			}

			return formData
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

	return form
}
