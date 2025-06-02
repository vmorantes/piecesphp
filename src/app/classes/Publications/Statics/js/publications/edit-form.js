/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../statics/core/own-plugins/CropperAdapterComponent.js" />
/// <reference path="../../../../../../statics/core/own-plugins/RichEditorAdapterComponent.js" />
/// <reference path="../../../../../../statics/core/own-plugins/SimpleUploadPlaceholder.js" />
/// <reference path="../../../../../../statics/core/own-plugins/AttachmentPlaceholder.js" />
showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	const translatableProperties = JSON.parse(atob(getVariableFromHTML('translatableProperties')))
	const translationsLangs = JSON.parse(atob(getVariableFromHTML('translationsLangs')))
	const defaultLang = atob(getVariableFromHTML('defaultLang'))
	const baseLang = atob(getVariableFromHTML('baseLang'))
	const previousTranslationsPromises = []

	const tabsLang = $('.lang-tabs [data-tab]').tab({
		onVisible: function (tabName) {
			//Crear nuevo objeto URL con la URL actual
			const url = new URL(window.location.href)
			//Actualizar o agregar el parámetro lang
			url.searchParams.set('lang', tabName);
			//Actualizar la URL del navegador sin recargar la página
			window.history.pushState({}, '', url)
		}
	})
	const formsByLang = new Map()
	const saveButtons = $('[lang-container] button[type="submit"][save]')

	for (const lang in translationsLangs) {
		configByLang(lang)
	}
	saveButtons.on('click', function (event) {
		const loaderName = 'sendAllForms'
		const formsByLangKeys = Array.from(formsByLang.keys())
		const sendPromises = []

		showGenericLoader(loaderName)

		for (const lang of formsByLangKeys) {
			const wasFormChanged = formsByLang.get(lang).wasFormChanged
			const form = formsByLang.get(lang).form
			if (wasFormChanged || baseLang == lang) {
				const isValid = form.get(0).checkValidity()
				sendPromises.push(new Promise(function (resolve) {
					form.onSuccessFinally = function (formProcess, formData, response) {
						resolve({
							form: form,
							isValid: isValid,
							response: response,
						})
					}
					if (isValid) {
						form.trigger('submit')
					} else {
						resolve({
							form: form,
							isValid: isValid,
							response: null,
						})
					}
				}))
			}
		}

		Promise.all(sendPromises).then(function (resolvedResponses) {

			if (resolvedResponses.every(response => response.isValid)) {

				const resolvedResponse = resolvedResponses.find(response => response.response !== null)
				const toReloadPage = () => {
					setTimeout(function (e) {
						window.location.reload()
					}, 1500)
				}

				if (resolvedResponse !== null) {

					const response = resolvedResponse.response

					if (resolvedResponse !== null) {
						const responseValues = response.values
						const redirectURL = responseValues.redirect_to
						const validRedirection = typeof redirectURL == 'string' && redirectURL.trim().length > 0
						if (validRedirection) {
							setTimeout(function (e) {
								window.location = redirectURL
							}, 1500)
						} else {
							toReloadPage()
						}
					} else {
						toReloadPage()
					}

				}

			}

		})

		removeGenericLoader(loaderName)

	})

	//Lanzar evento de traducción lista para configurar
	const publicationsTranslationsReadyToConfigEvent = 'publicationsTranslationsReadyToConfig'
	showGenericLoader(publicationsTranslationsReadyToConfigEvent)
	Promise.all(previousTranslationsPromises).then(function () {
		window.dispatchEvent(new Event(publicationsTranslationsReadyToConfigEvent))
		removeGenericLoader(publicationsTranslationsReadyToConfigEvent)
	})

	removeGenericLoader('_CARGA_INICIAL_')

	function configByLang(lang) {

		window.dispatchEvent(new Event('canDeletePublication'))
		const triggerChange = function () {
			formsByLang.get(lang).wasFormChanged = true
		}
		//Objeto externo para el formulario
		let formExternal = {
			form: null,
			wasFormChanged: false,
			lang: lang,
		}
		formsByLang.set(lang, formExternal)

		/* Traducciones */
		const langGroup = 'appPublicationsLang'
		registerDynamicLocalizationMessages(langGroup)

		/* Selectores y elementos de interfaz */
		const baseSelector = `[lang-container="${lang}"]`
		const formSelector = `${baseSelector} .ui.form.publications`
		const idMainImage = `main-image-${lang}`
		const idThumbImage = `thumb-image-${lang}`
		const idOpenGraphImage = `og-image-${lang}`

		//Placeholders
		let attachmentMainImage = new AttachmentPlaceholder($(`${baseSelector} .attach-placeholder.${idMainImage}`))
		let attachmentThumbImage = new AttachmentPlaceholder($(`${baseSelector} .attach-placeholder.${idThumbImage}`))
		let attachmentOpenGraphImage = new AttachmentPlaceholder($(`${baseSelector} .attach-placeholder.${idOpenGraphImage}`))

		//Croppers (están afuera de los formularios, no requieren el selector base)
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
		let richEditorAdapter = null
		previousTranslationsPromises.push(new Promise(function (resolve) {
			richEditorAdapter = new RichEditorAdapterComponent({
				containerSelector: `${baseSelector} [rich-editor-adapter-component]`,
				textareaTargetSelector: `${baseSelector} textarea[name='content']`,
				onChange: function () {
					triggerChange()
				}
			})
			richEditorAdapter.on(RichEditorAdapterComponent.events.instanceReady, function () {
				resolve()
			})
		}))

		//Calendar
		configGroupCalendar(`calendar-group-js-lang-${lang}`)

		//Adjuntos
		/**
		 * @type {AttachmentPlaceholder[]}
		 */
		let attachments = []
		dynamicPublicationAttachments(attachments, baseSelector)

		/* Configuraciones iniciales */
		configFomanticDropdown(`${baseSelector} .ui.dropdown:not(.langs)`) //Debe inciarse antes de genericFormHandler para la validación

		formsByLang.get(lang).form = genericFormHandler(formSelector, {
			onSetFormData: function (formData) {
				formData.set('mainImage', cropperMainImage.getFile())
				formData.set('thumbImage', cropperThumbImage.getFile())
				if (cropperOpenGraphImage.wasChange()) {
					formData.set('ogImage', cropperOpenGraphImage.getFile())
				} else {
					if (cropperOpenGraphImage.getFile() !== null) {
						formData.set('ogImage', cropperOpenGraphImage.getFile())
					}
				}

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

				errorMessage(`<small>(${translationsLangs[lang]})</small> ${nameOnLabel}: ${validationMessage}`)

				event.preventDefault()

			},
			toast: false,
			onSuccess: function (formProcess, formData, response) {
				successMessage(`<small>(${translationsLangs[lang]})</small> ${response.name}`, response.message)
			},
			onError: function (formProcess, formData, response) {
				errorMessage(`<small>(${translationsLangs[lang]})</small> ${response.name}`, response.message)
			},
		})
		let form = formsByLang.get(lang).form

		//Inutilizar botón de guardado
		const saveButton = form.find('button[type="submit"][save]')
		saveButton.on('click', function (e) {
			e.preventDefault()
		})

		//Verificar si hubo actividad en el formulario
		form.find('input, select, textarea').on('input', triggerChange)
		form.find('input, select, textarea').on('change', triggerChange)
		form.find('input, select, textarea').on('keyup', triggerChange)

		//Comportamiento de placeholders
		attachmentMainImage.scopeAction(function (instance, elements) { genericAttachmentWithModalCropperBehavior(instance, elements, cropperMainImage, idMainImage) })
		attachmentThumbImage.scopeAction(function (instance, elements) { genericAttachmentWithModalCropperBehavior(instance, elements, cropperThumbImage, idThumbImage) })
		attachmentOpenGraphImage.scopeAction(function (instance, elements) { genericAttachmentWithModalCropperBehavior(instance, elements, cropperOpenGraphImage, idOpenGraphImage) })

		//Tabs
		const tabsContent = $(`${baseSelector} .tabs-controls [data-tab]`).tab({
			context: baseSelector,
			onVisible: function (tabName) {
			}
		})

		//Otros
		form.find('input, select, textarea').attr('autocomplete', 'off')
		form.find('.checkbox').checkbox()
		$('.ui.accordion').accordion()

		configTranslations()

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
				triggerChange()
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
		 * @param {String} baseSelector 
		 */
		function dynamicPublicationAttachments(attachments, baseSelector) {
			const addTriggerID = 'add-trigger'
			//Configuración de presentes
			const baseAttr = 'data-dynamic-attachment'
			const elements = Array.from(document.querySelectorAll(`${baseSelector} [${baseAttr}]`))
			for (const element of elements) {
				const identifier = element.getAttribute(baseAttr)
				if (typeof identifier == 'string' && identifier.trim().length > 0 && identifier != addTriggerID) {
					const attachment = new AttachmentPlaceholder($(`${baseSelector} [${baseAttr}="${identifier}"]`))
					attachment.onSelected(function () {
						triggerChange()
					})
					attachments.push(attachment)
				} else if (identifier != addTriggerID) {
					element.remove()
				}
			}
			//Adición
			let attachsCounter = 0
			const addTrigger = $(`${baseSelector} [${baseAttr}="${addTriggerID}"]`)
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
				const attach = new AttachmentPlaceholder($(`${baseSelector} [${baseAttr}="${uniqueID}"]`))
				attach.onSelected(function () {
					triggerChange()
				})
				attach.setName(title.text())
				attachments.push(attach)
			})
		}

		function configTranslations() {

			const fieldsNotTranstalables = form.find('[translatable="no"]')
			fieldsNotTranstalables.addClass('disabled')

		}
	}

})


