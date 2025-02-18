/// <reference path="../../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../../statics/core/own-plugins/CropperAdapterComponent.js" />
/// <reference path="../../../../../../../statics/core/own-plugins/RichEditorAdapterComponent.js" />
/// <reference path="../../../../../../../statics/core/own-plugins/AttachmentPlaceholder.js" />
showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	window.dispatchEvent(new Event('canDeleteBuiltInBanner'))
	let isEdit = false

	/* Traducciones */
	const langGroup = 'built-in-banner-lang'
	registerDynamicLocalizationMessages(langGroup)

	/* Selectores y elementos de interfaz */
	const formSelector = `.ui.form.built-in-banner`
	const idDesktopImage = 'desktop-image'
	const idMobileImage = 'mobile-image'

	//Placeholders
	let attachmentDesktopImage = new AttachmentPlaceholder($(`.attach-placeholder.${idDesktopImage}`))
	let attachmentMobileImage = new AttachmentPlaceholder($(`.attach-placeholder.${idMobileImage}`))

	//Croppers
	const BuiltInBannerConfiguration = pcsphpGlobals.frontConfigurationsFromBackend.BuiltInBannerConfiguration
	let cropperDesktopImage = new SimpleCropperAdapter(`[${idDesktopImage}]`, {
		aspectRatio: BuiltInBannerConfiguration.desktop.aspectRatio,
		format: 'image/jpeg',
		quality: 0.8,
		fillColor: 'white',
		outputWidth: BuiltInBannerConfiguration.desktop.outputWidth,
	})
	let cropperMobileImage = new SimpleCropperAdapter(`[${idMobileImage}]`, {
		aspectRatio: BuiltInBannerConfiguration.mobile.aspectRatio,
		format: 'image/jpeg',
		quality: 0.8,
		fillColor: 'white',
		outputWidth: BuiltInBannerConfiguration.mobile.outputWidth,
	})

	//Editores de texto
	let richEditorAdapter = new RichEditorAdapterComponent({
		containerSelector: '[rich-editor-adapter-component]',
		textareaTargetSelector: "textarea[name='content']",
	}, {
		items: [
			'undo',
			'redo',
			'|',
			'fontBackgroundColor',
			'fontColor',
			'bold',
			'italic',
			'underline',
			'strikethrough',
			'link',
			'removeFormat',
			'|',
			'superscript',
			'subscript',
		]
	})

	/* Configuraciones iniciales */
	configFomanticDropdown('.ui.dropdown:not(.langs)') //Debe inciarse antes de genericFormHandler para la validación

	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData) {

			if (isEdit) {
				formData.set('desktopImage', cropperDesktopImage.getFile())
				if (cropperMobileImage.wasChange()) {
					formData.set('mobileImage', cropperMobileImage.getFile())
				}
			} else {
				formData.set('desktopImage', cropperDesktopImage.getFile())
				if (cropperMobileImage.wasChange()) {
					formData.set('mobileImage', cropperMobileImage.getFile())
				}
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

	//Comportamiento de placeholders
	attachmentDesktopImage.scopeAction(function (instance, elements) { genericAttachmentWithModalCropperBehavior(instance, elements, cropperDesktopImage, idDesktopImage) })
	attachmentMobileImage.scopeAction(function (instance, elements) { genericAttachmentWithModalCropperBehavior(instance, elements, cropperMobileImage, idMobileImage) })

	//Tabs
	const tabs = $('.tabs-controls [data-tab]').tab({
		onVisible: function (tabName) {
		}
	})

	//Otros
	form.find('input, select, textarea').attr('autocomplete', 'off')
	form.find('.checkbox').checkbox()
	$('.ui.accordion').accordion()

	isEdit = form.find(`[name="id"]`).length > 0

	configLangChange('.ui.dropdown.langs')

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


