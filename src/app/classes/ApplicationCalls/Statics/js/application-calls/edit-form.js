/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../statics/core/own-plugins/CropperAdapterComponent.js" />
/// <reference path="../../../../../../statics/core/own-plugins/RichEditorAdapterComponent.js" />
/// <reference path="../../../../../../statics/core/own-plugins/AttachmentPlaceholder.js" />
showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	window.dispatchEvent(new Event('canDeleteApplicationCall'))

	/* Traducciones */
	const langGroup = 'application-calls-lang'
	registerDynamicLocalizationMessages(langGroup)

	/* Selectores y elementos de interfaz */
	const formSelector = `.ui.form.application-calls`
	const idMainImage = 'main-image'
	const idThumbImage = 'thumb-image'

	//Placeholders
	let attachmentMainImage = new AttachmentPlaceholder($(`.attach-placeholder.${idMainImage}`))
	let attachmentThumbImage = new AttachmentPlaceholder($(`.attach-placeholder.${idThumbImage}`))

	//Croppers
	let cropperMainImage = new SimpleCropperAdapter(`[${idMainImage}]`, {
		aspectRatio: 1200 / 675,
		format: 'image/jpeg',
		quality: 0.8,
		fillColor: 'white',
		outputWidth: 1200,
	})
	let cropperThumbImage = new SimpleCropperAdapter(`[${idThumbImage}]`, {
		aspectRatio: 1 / 1,
		format: 'image/jpeg',
		quality: 0.8,
		fillColor: 'white',
		outputWidth: 640,
	})

	//Editores de texto
	const richEditorBaseAttrSelector = 'rich-editor-adapter-component'
	const richEditors = Array.from(document.querySelectorAll(`[${richEditorBaseAttrSelector}]`))
	const richEditorAdaptersByLang = {}
	for (const richEditor of richEditors) {
		const lang = richEditor.getAttribute(richEditorBaseAttrSelector)
		if (typeof lang == 'string' && lang.trim().length > 0) {
			const richEditorSelector = `[${richEditorBaseAttrSelector}="${lang}"]`
			richEditorAdaptersByLang[lang] = new RichEditorAdapterComponent({
				containerSelector: richEditorSelector,
				textareaTargetSelector: `textarea[name='content[${lang}]']`,
			})
		}
	}


	//Adjuntos
	/**
	 * @type {AttachmentPlaceholder[]}
	 */
	let attachments = []
	dynamicAttachments(attachments)

	/* Configuraciones iniciales */
	configFomanticDropdown('.ui.dropdown:not(.additions)')
	configFomanticDropdown('.ui.dropdown.additions', {
		allowAdditions: true,
	})

	let toFormEditForTranstation = false
	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData) {

			formData.set('mainImage', cropperMainImage.getFile())
			formData.set('thumbImage', cropperThumbImage.getFile())
			formData.set('toTranslation', toFormEditForTranstation ? 'yes' : 'no')

			let attachCounter = 0
			for (const attachment of attachments) {
				const fileSelected = attachment.getSelectedFile()
				const mapperID = attachment.getElements().attachContainer.data('mapper-id')
				let existsAttachment = typeof mapperID !== 'undefined'
				const isNewOrChanged = (existsAttachment && attachment.wasChange()) || !existsAttachment
				if (existsAttachment) {
					attachCounter++
					formData.append(`attachmentsID_${attachCounter}`, mapperID)
				}
				if (isNewOrChanged) {
					if (!existsAttachment) { attachCounter++ }
					formData.append(`attachmentsFile_${attachCounter}`, fileSelected)
				}
				formData.append(`attachmentsName_${attachCounter}`, attachment.getName())
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

	//Botón de traducción
	let translateButton = form.find('button[translate]')
	//Comportamiento de botón de traducción
	const handleTranslate = function (event) {
		event.preventDefault()
		const translatableFields = {
			title: [
				'es',
				'fr',
			],
			content: [
				'es',
				'fr',
			],
		}
		const fieldsByLang = {}
		const fieldsBase = {}
		const fieldsNeedTranslation = {}

		for (const fieldName in translatableFields) {
			const availablesLangs = translatableFields[fieldName]
			for (const lang of availablesLangs) {
				fieldsByLang[fieldName] = typeof fieldsByLang[fieldName] == 'object' ? fieldsByLang[fieldName] : {}
				fieldsByLang[fieldName][lang] = form.find(`[name="${fieldName}[${lang}]"]`)
			}
		}

		for (const fieldName in fieldsByLang) {
			const fieldLangs = fieldsByLang[fieldName]
			for (const lang in fieldLangs) {
				const field = fieldLangs[lang]
				const isTextArea = field.is('textarea')
				const isRichEditor = isTextArea && typeof field.get(0).getRichEditorData !== 'undefined'
				const fieldValue = isRichEditor ? field.get(0).getRichEditorData() : field.val()
				if (fieldValue.trim().length > 0) {
					if (typeof fieldsBase[fieldName] == 'undefined') {
						fieldsBase[fieldName] = {
							lang: lang,
							value: fieldValue,
						}
					}
				} else {
					if (typeof fieldsNeedTranslation[fieldName] == 'undefined') {
						fieldsNeedTranslation[fieldName] = []
					}
					fieldsNeedTranslation[fieldName].push({
						lang: lang,
						field: field,
					})
				}
			}
		}

		const translationURL = new URL('core/api/translations', pcsphpGlobals.baseURL)

		for (const fieldName in fieldsNeedTranslation) {

			const baseData = typeof fieldsBase[fieldName] !== 'undefined' ? fieldsBase[fieldName] : null
			const fieldsNeedData = fieldsNeedTranslation[fieldName]

			for (const fieldNeedData of fieldsNeedData) {

				if (baseData !== null) {

					const langFrom = baseData.lang
					const valueFrom = baseData.value
					const langTo = fieldNeedData.lang

					const objectToTranslate = {}
					objectToTranslate[fieldName] = valueFrom

					const loaderName = generateUniqueID()
					showGenericLoader(loaderName)

					translationURL.searchParams.set('text', base64EncodeUnicode(JSON.stringify(objectToTranslate)))
					translationURL.searchParams.set('from', langFrom)
					translationURL.searchParams.set('to', langTo)

					getRequest(translationURL.href, null, {
						'PCSPHP-Response-Expected-Language': pcsphpGlobals.lang,
					}).done(function (response) {

						const success = response.success
						const message = response.message
						const result = response.result
						const error = response.error

						if (success) {

							const translations = result.translation

							for (const translationKeyText in translations) {
								const translation = translations[translationKeyText]
								const fieldConfig = fieldsNeedTranslation[translationKeyText]
								if (typeof translation == 'string' && translation.trim().length > 0) {
									const isTextArea = fieldNeedData.field.is('textarea')
									const isRichEditor = isTextArea && typeof fieldNeedData.field.get(0).getRichEditorData !== 'undefined'
									isRichEditor ? fieldNeedData.field.get(0).updateRichEditor(translation) : fieldNeedData.field.val(translation)
								}
							}

						} else {
							errorMessage(message)
						}

						if (error !== null) {
							console.error(error)
						}
					}).always(function () {
						removeGenericLoader(loaderName)
					})

				}
			}

		}

	}
	translateButton.on('click', handleTranslate)

	//Comportamiento de placeholders
	attachmentMainImage.scopeAction(function (instance, elements) { genericAttachmentWithModalCropperBehavior(instance, elements, cropperMainImage, idMainImage) })
	attachmentThumbImage.scopeAction(function (instance, elements) { genericAttachmentWithModalCropperBehavior(instance, elements, cropperThumbImage, idThumbImage) })

	//Tabs
	const checkRelatedTabElements = function (tabName) {
		const relatedElementsWithTabs = $(`[data-tab-related]`)
		const relatedElementWithTab = $(`[data-tab-related="${tabName}"]`)
		relatedElementsWithTabs.hide()
		relatedElementWithTab.show()
	}
	const initialTabActions = function (tabs) {
		//Revisar elementos relacionados
		checkRelatedTabElements(tabs.filter('.active').data('tab'))
		//Navegación externa de tabs
		$('[go-to-tab]').off('click').on('click', function (e) {
			e.preventDefault()
			const tabName = $(e.currentTarget).attr('go-to-tab')
			if (typeof tabName == 'string' && tabName.trim().length > 0) {
				tabs.tab('change tab', tabName)
			}
		})
		//Actualizar activo según hash
		const url = new URL(window.location.href)
		const pathAlternatives = [
			url.searchParams.get('currentTab'),
			url.hash,
		]
		for (let path of pathAlternatives) {
			path = typeof path == 'string' ? path.replace('#', '').trim() : ''
			if (path.length > 0 && tabs.tab('is tab', path)) {
				tabs.tab('change tab', path)
				break
			}
		}
	}
	const tabs = $('.tabs-controls [data-tab]').tab({
		onVisible: function (tabName) {
			checkRelatedTabElements(tabName)
			const url = new URL(window.location.href)
			url.searchParams.set('currentTab', tabName)
			window.history.pushState({}, '', url)
		},
	})
	initialTabActions(tabs)

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

	/**
	 * Configura el comportamiento de los adjuntos
	 * @param {AttachmentPlaceholder[]} attachments 
	 */
	function dynamicAttachments(attachments) {
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


