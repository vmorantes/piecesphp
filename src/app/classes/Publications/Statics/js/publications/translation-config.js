/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../statics/core/own-plugins/RichEditorAdapterComponent.js" />
showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('publicationsTranslationsReadyToConfig', function () {

	const currentAppLang = pcsphpGlobals.lang
	const translatableProperties = JSON.parse(atob(getVariableFromHTML('translatableProperties')))
	const translationsLangs = JSON.parse(atob(getVariableFromHTML('translationsLangs')))
	const defaultLang = atob(getVariableFromHTML('defaultLang'))
	const baseLang = atob(getVariableFromHTML('baseLang'))
	const baseLangContainerElements = $(`.module-view-container [lang-container="${baseLang}"]`)
	const containerElements = Array.from($('.module-view-container [lang-container]').not(`[lang-container="${baseLang}"]`))
	const translationURL = new URL('core/api/translations', pcsphpGlobals.baseURL)
	const textsToTranslate = {}
	const asHTMLProperties = []
	const globalLangGroup = 'global'

	const fieldsHandler = {
		title: {
			selector: `input[name="title"]`,
			getValue: function (element) {
				return element.val()
			},
			setValue: function (element, value) {
				return element.val(value)
			},
			onChange: function (element, callback) {
				let that = this
				element.on('input', function () {
					const value = that.getValue(element)
					callback(value, {})
				})
			},
		},
		content: {
			selector: `textarea[name="content"]`,
			getValue: function (element) {
				return element.get(0).getRichEditorData()
			},
			setValue: function (element, value) {
				return element.get(0).updateRichEditor(value)
			},
			onChange: function (element, callback) {
				element.get(0).onChangeRichEditor(function (instance, value) {
					callback(value, {
						instance: instance,
					})
				})
			},
		},
		seoDescription: {
			selector: `textarea[name="seoDescription"]`,
			getValue: function (element) {
				return element.val()
			},
			setValue: function (element, value) {
				return element.val(value)
			},
			onChange: function (element, callback) {
				let that = this
				element.on('input', function () {
					const value = that.getValue(element)
					callback(value, {})
				})
			},
		},
	}

	for (const fieldName in fieldsHandler) {
		const fieldConfig = fieldsHandler[fieldName]
		const fieldElement = baseLangContainerElements.find(fieldConfig.selector)
		const isTextArea = fieldElement.is('textarea')
		const isRichEditor = isTextArea && typeof fieldElement.get(0).getRichEditorData !== 'undefined'
		const fieldValue = fieldConfig.getValue(fieldElement)
		const asHTML = isRichEditor
		if (asHTML) {
			asHTMLProperties.push(fieldName)
		}
		textsToTranslate[fieldName] = fieldValue
		fieldConfig.onChange(fieldElement, function (value) {
			if (typeof value == 'string' && value.trim().length > 0) {
				textsToTranslate[fieldName] = value
			} else {
				delete textsToTranslate[fieldName]
			}
		})
	}

	for (const keyText in textsToTranslate) {
		const text = textsToTranslate[keyText]
		let valid = typeof text == 'string' && text.trim().length > 0
		if (!valid) {
			delete textsToTranslate[keyText]
		}
	}

	configureTranslationAction()

	function configureTranslationAction() {
		for (const containerElement of containerElements) {

			const translateTrigger = $(containerElement).find('[do-translation]')
			const fromLangCode = translateTrigger.attr('from-lang')
			const toLangCode = translateTrigger.attr('to-lang')

			if (typeof fromLangCode == 'string' && typeof toLangCode == 'string') {
				const fromLangName = typeof translationsLangs[fromLangCode] == 'string' ? translationsLangs[fromLangCode] : null
				const toLangName = typeof translationsLangs[toLangCode] == 'string' ? translationsLangs[toLangCode] : null
				if (fromLangName !== null && toLangName !== null) {
					translateTrigger.off('click')
					translateTrigger.on('click', function (e) {
						e.preventDefault()
						const loaderName = generateUniqueID()
						showGenericLoader(loaderName, null, false, {
							textMessage: _i18n(globalLangGroup, 'El tiempo de respuesta depende del servicio de traducción de las plataformas de inteligencia artificial, por lo que su duración puede variar.'),
						})
						const formData = new FormData()
						formData.set('text', base64EncodeUnicode(JSON.stringify(textsToTranslate)))
						formData.set('from', fromLangName)
						formData.set('to', toLangName)
						asHTMLProperties.forEach(function (asHTMLProperty) {
							formData.append('asHTMLProperties[]', asHTMLProperty)
						})

						postRequest(translationURL.href, formData, {
							'PCSPHP-Response-Expected-Language': currentAppLang,
						}).done(function (response) {

							const success = response.success
							const message = response.message
							const result = response.result
							const error = response.error

							if (success) {

								const translations = result.translation

								for (const translationKeyText in translations) {
									if (typeof fieldsHandler[translationKeyText] !== 'undefined') {
										const translation = translations[translationKeyText]
										const fieldConfig = fieldsHandler[translationKeyText]
										const fieldElement = $(containerElement).find(fieldConfig.selector)
										if (typeof translation == 'string' && translation.trim().length > 0) {
											fieldConfig.setValue(fieldElement, translation)
										}
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
					})
				}
			}

		}
	}

})


