/// <reference path="../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../statics/core/own-plugins/RichEditorAdapterComponent.js" />
function MySpaceProfilesTranslationConfig() { }
MySpaceProfilesTranslationConfig.handle = function (event, form) {
	event.preventDefault()
	const translatableFields = {
		experienceName: [
			'es',
			'fr',
		],
		description: [
			'es',
			'fr',
		],
	}
	const fieldsByLang = {}
	const fieldsBase = {}
	const fieldsNeedTranslation = {}
	const globalLangGroup = 'global'

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
			const fieldValue = field.val()
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
				showGenericLoader(loaderName, null, false, {
					textMessage: _i18n(globalLangGroup, 'El tiempo de respuesta depende del servicio de traducción de las plataformas de inteligencia artificial, por lo que su duración puede variar.'),
				})

				const formData = new FormData()
				formData.set('text', base64EncodeUnicode(JSON.stringify(objectToTranslate)))
				formData.set('from', langFrom)
				formData.set('to', langTo)

				postRequest(translationURL.href, formData, {
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
								fieldNeedData.field.val(translation)
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