/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../statics/core/own-plugins/ContentImageEditor.js" />
/// <reference path="../../../../../../statics/core/own-plugins/RichEditorAdapterComponent.js" />
/// <reference path="../../../../../../statics/core/own-plugins/AttachmentPlaceholder.js" />
showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	window.dispatchEvent(new Event('canDeletePublication'))

	/* Traducciones */
	const langGroup = 'appPublicationsLang'
	registerDynamicLocalizationMessages(langGroup)

	/* Selectores y elementos de interfaz */
	const formSelector = `.ui.form.publications`

	//Tarjetas de imagen: lo aplicado en el editor se envía al guardar, con el nombre de su campo.
	const editedImages = {}
	const imageEditor = new ContentImageEditor()
	imageEditor.onApply('main-image', (file) => editedImages.mainImage = file)
	imageEditor.onApply('thumb-image', (file) => editedImages.thumbImage = file)
	imageEditor.onApply('og-image', (file) => editedImages.ogImage = file)

	//Editores de texto
	let richEditorAdapter = new RichEditorAdapterComponent({
		containerSelector: '[rich-editor-adapter-component]',
		textareaTargetSelector: "textarea[name='content']",
	})

	//Adjuntos
	/**
	 * @type {AttachmentPlaceholder[]}
	 */
	let attachments = []
	dynamicPublicationAttachments(attachments)

	/* Configuraciones iniciales */
	configFomanticDropdown('.ui.dropdown:not(.langs)') //Debe inciarse antes de genericFormHandler para la validación

	let toFormEditForTranstation = false
	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData) {

			for (const field in editedImages) {
				formData.set(field, editedImages[field])
			}
			formData.set('toTranslation', toFormEditForTranstation ? 'yes' : 'no')

			let attachCounter = 0
			for (const attachment of attachments) {
				const fileSelected = attachment.getSelectedFile()
				if (fileSelected !== null) {
					attachCounter++
					formData.append(`attachmentsID_${attachCounter}`, null)
					formData.append(`attachmentsFile_${attachCounter}`, fileSelected)
					formData.append(`attachmentsName_${attachCounter}`, attachment.getName())
				}
			}
			return formData
		},
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

	//Tabs
	const tabs = $('.tabs-controls [data-tab]').tab({
		onVisible: function (tabName) {
		}
	})

	//Otros
	form.find('input, select, textarea').attr('autocomplete', 'off')
	form.find('.checkbox').checkbox()
	//Solo los del formulario: uno global reiniciaba el de la navegación del shell sin su selector.ignore del riel.
	form.find('.ui.accordion').accordion()

	/**
	 * Configura el comportamiento de los adjuntos
	 * @param {AttachmentPlaceholder[]} attachments 
	 */
	function dynamicPublicationAttachments(attachments) {
		const addTriggerID = 'add-trigger'
		//Configuración de presentes
		const baseAttr = 'data-dynamic-attachment'
		const elements = Array.from(document.querySelectorAll(`[${baseAttr}]`))
		for (const element of elements) {
			const identifier = element.getAttribute(baseAttr)
			if (typeof identifier == 'string' && identifier.trim().length > 0 && identifier != addTriggerID) {
				attachments.push(new AttachmentPlaceholder($(`[${baseAttr}="${identifier}"]`)))
			} else if (identifier != addTriggerID) {
				element.remove()
			}
		}
		//Adición
		let attachsCounter = 0
		const addTrigger = $(`[${baseAttr}="${addTriggerID}"]`)
		const container = addTrigger.parent()
		const template = document.querySelector('template[attach]').innerHTML
		addTrigger.on('click', function (e) {
			e.preventDefault()
			attachsCounter++
			const uniqueID = generateUniqueID()
			const templateElement = $(template
				.replace(/\{NUMBER\}/gim, attachsCounter)
				.replace(/\{ID\}/gim, uniqueID)
			)
			const title = templateElement.find('.header > .title')
			container.append(templateElement)
			const attach = new AttachmentPlaceholder($(`[${baseAttr}="${uniqueID}"]`))
			attach.setName(title.text())
			attachments.push(attach)
		})
	}

	removeGenericLoader('_CARGA_INICIAL_')

})


