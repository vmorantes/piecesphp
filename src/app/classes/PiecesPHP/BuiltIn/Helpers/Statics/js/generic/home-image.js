/// <reference path="../../../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../../../statics/core/own-plugins/SimpleCropperAdapter.js" />
/// <reference path="../../../../../../../../statics/core/own-plugins/AttachmentPlaceholder.js" />
showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	window.dispatchEvent(new Event('canDeleteOrganization'))

	let isEdit = false
	const formSelector = `.ui.form.generic`
	const baseMainImageAttachmentSelector = '.attach-placeholder.main-image'
	const baseMainImageModalSelector = 'main-image-cropper'
	const attachments = Array.from(document.querySelectorAll(baseMainImageAttachmentSelector))
	const attachmentsElements = []

	for (const attachment of attachments) {
		const lang = attachment.dataset.lang
		const name = attachment.dataset.name
		const modalSelector = `${baseMainImageModalSelector}-${lang}`
		const attachmentElement = new AttachmentPlaceholder($(`${baseMainImageAttachmentSelector}[data-lang="${lang}"]`))
		const cropperElement = new SimpleCropperAdapter(`[${modalSelector}]`, {
			aspectRatio: 1200 / 900,
			format: 'image/jpeg',
			quality: 0.8,
			fillColor: 'white',
			outputWidth: 1200,
		})
		attachmentsElements.push({
			attachment: attachmentElement,
			cropper: cropperElement,
			modalSelector: modalSelector,
			lang: lang,
			name: name,
		})
	}

	//Formulario
	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData) {

			for (const attachmentConfig of attachmentsElements) {
				const nameOnForm = `${attachmentConfig.name}[${attachmentConfig.lang}]`
				if (isEdit) {
					if (attachmentConfig.cropper.wasChange()) {
						formData.set(nameOnForm, attachmentConfig.cropper.getFile(null, null, null, null, true))
					}
				} else {
					if (attachmentConfig.cropper.wasChange()) {
						formData.set(nameOnForm, attachmentConfig.cropper.getFile())
					}
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

	//Adjuntos
	for (const attachmentConfig of attachmentsElements) {

		attachmentConfig.attachment.scopeAction(function (instance, elements) {

			const modal = $(`[modal="${attachmentConfig.modalSelector}"]`)
			let cropperFirstDraw = true

			attachmentConfig.cropper.onCancel(() => modal.modal('hide'))
			attachmentConfig.cropper.onCropped((blobImage, settedImage) => {
				instance.setFile(blobImage)
				modal.modal('hide')
			})

			instance.onClick(function (instance, elements, event) {
				event.preventDefault()
				modal.modal({
					onVisible: function () {
						if (cropperFirstDraw) {
							attachmentConfig.cropper.refresh()
							cropperFirstDraw = false
						}
					},
				}).modal('show')
			})

		})
	}

	removeGenericLoader('_CARGA_INICIAL_')

})


