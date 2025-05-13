/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../statics/core/own-plugins/CropperAdapterComponent.js" />
/// <reference path="../../../../../../statics/core/own-plugins/RichEditorAdapterComponent.js" />
/// <reference path="../../../../../../statics/core/own-plugins/SimpleUploadPlaceholder.js" />
/// <reference path="../../../../../../statics/core/own-plugins/AttachmentPlaceholder.js" />
showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	window.dispatchEvent(new Event('canDeletePublication'))

	/* Traducciones */
	const langGroup = 'appPublicationsLang'
	registerDynamicLocalizationMessages(langGroup)

	/* Selectores y elementos de interfaz */
	const formSelector = `.ui.form.publications`
	const idMainImage = 'main-image'
	const idThumbImage = 'thumb-image'
	const idOpenGraphImage = 'og-image'

	//Placeholders
	let attachmentMainImage = new AttachmentPlaceholder($(`.attach-placeholder.${idMainImage}`))
	let attachmentThumbImage = new AttachmentPlaceholder($(`.attach-placeholder.${idThumbImage}`))
	let attachmentOpenGraphImage = new AttachmentPlaceholder($(`.attach-placeholder.${idOpenGraphImage}`))

	//Croppers
	let cropperMainImage = new SimpleCropperAdapter(`[${idMainImage}]`, {
		aspectRatio: 4 / 3,
		format: 'image/jpeg',
		quality: 0.8,
		fillColor: 'white',
		outputWidth: 800,
	})
	let cropperThumbImage = new SimpleCropperAdapter(`[${idThumbImage}]`, {
		aspectRatio: 4 / 3,
		format: 'image/jpeg',
		quality: 0.8,
		fillColor: 'white',
		outputWidth: 400,
	})
	let cropperOpenGraphImage = new SimpleCropperAdapter(`[${idOpenGraphImage}]`, {
		aspectRatio: 2 / 1,
		format: 'image/jpeg',
		quality: 0.8,
		fillColor: 'white',
		outputWidth: 1200,
	})

	//Editores de texto
	let richEditorAdapter = new RichEditorAdapterComponent({
		containerSelector: '[rich-editor-adapter-component]',
		textareaTargetSelector: "textarea[name='content']",
	})

	//Adjuntos
	const attachments = {}
	let indexAttachment = 1
	for (const attachmentElement of Array.from(document.querySelectorAll('[attachment-element]'))) {
		attachmentElement.setAttribute(`attachment-${indexAttachment}`, '')
		attachments[indexAttachment] = new SimpleUploadPlaceholder({
			containerSelector: `[attachment-element][attachment-${indexAttachment}]`,
			onReady: function () {
			},
			onChangeFile: (files, component, instance, event) => {
				const fileInput = files[0]
			},
		})
		indexAttachment++
	}

	/* Configuraciones iniciales */
	configFomanticDropdown('.ui.dropdown:not(.langs)') //Debe inciarse antes de genericFormHandler para la validación

	let toFormEditForTranstation = false
	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData) {

			formData.set('mainImage', cropperMainImage.getFile())
			formData.set('thumbImage', cropperThumbImage.getFile())
			formData.set('toTranslation', toFormEditForTranstation ? 'yes' : 'no')
			if (cropperOpenGraphImage.wasChange()) {
				formData.set('ogImage', cropperOpenGraphImage.getFile())
			}

			return formData
		},
		onInvalidEvent: function (event) {

			let element = event.target
			let validationMessage = element.validationMessage
			let jElement = $(element)
			let field = jElement.closest('.field')
			let nameOnLabel = field.find('label').html()
			if (field.length == 0) {
				field = jElement.closest('.attach-placeholder')
				nameOnLabel = field.find('>label >.text >.header >.title').text()
			}

			errorMessage(`${nameOnLabel}: ${validationMessage}`)

			event.preventDefault()

		}
	})

	//Botones de guardado
	let regularSaveButton = form.find('button[type="submit"][save]')
	let toTranslationButtonSubmit = form.find('button[type="submit"][add-translation]')

	//Comportamiento del botón de agregar traducción
	toTranslationButtonSubmit.on('click', function (event) {
		event.preventDefault()
		toFormEditForTranstation = true
		regularSaveButton.click()
	})

	//Comportamiento de placeholders
	attachmentMainImage.scopeAction(function (instance, elements) { genericAttachmentWithModalCropperBehavior(instance, elements, cropperMainImage, idMainImage) })
	attachmentThumbImage.scopeAction(function (instance, elements) { genericAttachmentWithModalCropperBehavior(instance, elements, cropperThumbImage, idThumbImage) })
	attachmentOpenGraphImage.scopeAction(function (instance, elements) { genericAttachmentWithModalCropperBehavior(instance, elements, cropperOpenGraphImage, idOpenGraphImage) })

	//Tabs
	const tabs = $('.tabs-controls [data-tab]').tab({
		onVisible: function (tabName) {
		}
	})

	//Otros
	form.find('input, select, textarea').attr('autocomplete', 'off')
	form.find('.checkbox').checkbox()
	$('.ui.accordion').accordion()

	/** 
	 * @param {AttachmentPlaceholder} instance
	 * @param {AttachmentPlaceholderElements} elements
	 * @param {SimpleCropperAdapter} cropper
	 * @param {String} selector
	*/
	function genericAttachmentWithModalCropperBehavior(instance, elements, cropper, selector) {

		const modal = $(`[modal="${selector}"]`)
		let firstDraw = true
		cropper.onCancel(() => modal.modal('hide'))
		cropper.onCropped((blobImage, settedImage) => {
			instance.setFile(blobImage)
			modal.modal('hide')
		})

		instance.onClick(function (instance, elements, event) {
			event.preventDefault()
			modal.modal({
				onVisible: function () {
					if (firstDraw) {
						cropper.refresh()
						firstDraw = false
					}
				},
			}).modal('show')
		})

	}

	removeGenericLoader('_CARGA_INICIAL_')

})


