/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
var DocumentTypes = {}

DocumentTypes.configDocumentTypeForm = function (onSuccess = null, ignoreRedirection = false, ignoreReload = false) {

	const loaderName = 'configDocumentTypeForm'
	showGenericLoader(loaderName)

	let formSelector = `.ui.form[document-type-form]`
	let langGroup = 'appDocumentTypesLang'
	let isEdit = false

	configFomanticDropdown('.ui.dropdown') //Debe inciarse antes de genericFormHandler para la validación

	let form = genericFormHandler(formSelector, {
		onSuccess: typeof onSuccess == 'function' ? onSuccess : () => { },
		onSetFormData: function (formData) {
			return formData
		},
		onInvalidEvent: function (event) {

			let element = event.target
			let validationMessage = element.validationMessage
			let jElement = $(element)
			let field = jElement.closest('.field')
			let nameOnLabel = field.find('label').html()

			errorMessage(`${nameOnLabel}: ${validationMessage}`)

			event.preventDefault()

		},
		ignoreRedirection: ignoreRedirection,
		ignoreReload: ignoreReload,
	})

	isEdit = form.find(`[name="id"]`).length > 0

	form.find('input, select, textarea').attr('autocomplete', 'off')
	$('.tabular.menu .item').tab()

	configLangChange('.ui.dropdown.langs')

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

	removeGenericLoader(loaderName)

}
