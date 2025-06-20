/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	window.dispatchEvent(new Event('canDeleteInterestResearchArea'))

	/* Traducciones */
	const langGroup = 'interest-research-areas-lang'
	registerDynamicLocalizationMessages(langGroup)

	/* Selectores y elementos de interfaz */
	const formSelector = `.ui.form.interest-research-areas`

	/* Configuraciones iniciales */
	configFomanticDropdown('.ui.dropdown:not(.additions)')
	configFomanticDropdown('.ui.dropdown.additions', {
		allowAdditions: true,
	})

	let toFormEditForTranstation = false
	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData) {
			formData.set('toTranslation', toFormEditForTranstation ? 'yes' : 'no')
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

	removeGenericLoader('_CARGA_INICIAL_')

})


