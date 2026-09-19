/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../statics/core/own-plugins/RichEditorAdapterComponent.js" />
showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	window.dispatchEvent(new Event('canDeleteNews'))

	/* Traducciones */
	const langGroup = 'appNewsLang'
	registerDynamicLocalizationMessages(langGroup)

	/* Selectores y elementos de interfaz */
	const formSelector = `.ui.form.news`

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

	/* Configuraciones iniciales */
	configFomanticDropdown('.ui.dropdown:not(.langs)') //Debe inciarse antes de genericFormHandler para la validación

	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData) {
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

	//Otros
	form.find('input, select, textarea').attr('autocomplete', 'off')
	form.find('.checkbox').checkbox()
	$('.ui.accordion').accordion()

	removeGenericLoader('_CARGA_INICIAL_')

})


