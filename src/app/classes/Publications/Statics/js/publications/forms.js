/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../statics/core/own-plugins/CropperAdapterComponent.js" />
/// <reference path="../../../../../../statics/core/own-plugins/RichEditorAdapterComponent.js" />
showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	window.dispatchEvent(new Event('canDeletePublication'))

	let isEdit = false
	let formSelector = `.ui.form.publications`
	let langGroup = 'appPublicationsLang'

	let imagenMain = new CropperAdapterComponent({
		containerSelector: '[cropper-image-main]',
		minWidth: 800,
		outputWidth: 800,
		cropperOptions: {
			aspectRatio: 4 / 3,
		},
	})

	let imageThumb = new CropperAdapterComponent({
		containerSelector: '[cropper-image-thumb]',
		minWidth: 400,
		outputWidth: 400,
		cropperOptions: {
			aspectRatio: 4 / 3,
		},
	})

	let imageOpenGraph = new CropperAdapterComponent({
		containerSelector: '[cropper-image-og]',
		minWidth: 800,
		outputWidth: 1200,
		cropperOptions: {
			aspectRatio: 2 / 1,
		},
	})

	let richEditorAdapter = new RichEditorAdapterComponent({
		containerSelector: '[rich-editor-adapter-component]',
		textareaTargetSelector: "textarea[name='content']",
	})

	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData) {

			if (isEdit) {

				formData.set('mainImage', imagenMain.getFile(null, null, null, null, true))
				formData.set('thumbImage', imageThumb.getFile(null, null, null, null, true))
				if (imageOpenGraph.wasChanged()) {
					formData.set('ogImage', imageOpenGraph.getFile(null, null, null, null, true))
				}

			} else {
				formData.set('mainImage', imagenMain.getFile())
				formData.set('thumbImage', imageThumb.getFile())
				if (imageOpenGraph.wasChanged()) {
					formData.set('ogImage', imageOpenGraph.getFile())
				}
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
	$('.tabular.menu .item').tab()

	isEdit = form.find(`[name="id"]`).length > 0

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

	removeGenericLoader('_CARGA_INICIAL_')

})


