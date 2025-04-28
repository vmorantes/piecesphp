/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	const currentAppLang = pcsphpGlobals.lang
	const translatableProperties = JSON.parse(atob(getVariableFromHTML('translatableProperties')))
	const translationsLangs = JSON.parse(atob(getVariableFromHTML('translationsLangs')))
	const defaultLang = atob(getVariableFromHTML('defaultLang'))
	const defaultLangContainerElements = $(`.module-view-container [lang-container="${defaultLang}"]`)
	const containerElements = Array.from($('.module-view-container [lang-container]').not(`[lang-container="${defaultLang}"]`))
	const translationURL = new URL('core/api/translations', pcsphpGlobals.baseURL)
	const textsToTranslate = {}

	const fieldsHandler = {
		title: {
			selector: `input[name="title"]`,
			getValue: function (element) {
				return element.val()
			},
			setValue: function (element, value) {
				return element.val(value)
			},
		},
		content: {
			selector: `textarea[name="content"]`,
			getValue: function (element) {
				return element.val()
			},
			setValue: function (element, value) {
				return element.get(0).updateRichEditor(value)
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
		},
	}

	for (const fieldName in fieldsHandler) {
		const fieldConfig = fieldsHandler[fieldName]
		const fieldElement = defaultLangContainerElements.find(fieldConfig.selector)
		const fieldValue = fieldConfig.getValue(fieldElement)
		textsToTranslate[fieldName] = fieldValue
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
						showGenericLoader(loaderName)
						translationURL.searchParams.set('text', base64EncodeUnicode(JSON.stringify(textsToTranslate)))
						translationURL.searchParams.set('from', fromLangName)
						translationURL.searchParams.set('to', toLangName)
						getRequest(translationURL, null, {
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


