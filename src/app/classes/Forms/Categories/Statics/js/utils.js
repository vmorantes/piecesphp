/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
var Categories = {}

Categories.configCategoryForm = function (onSuccess = null, ignoreRedirection = false, ignoreReload = false) {

	const loaderName = 'configCategoryForm'
	showGenericLoader(loaderName)

	let formSelector = `.ui.form[category-form]`
	let langGroup = 'appCategoriesLang'
	let isEdit = false

	configFomanticDropdown('.ui.dropdown') //Debe inciarse antes de genericFormHandler para la validación

	let form = genericFormHandler(formSelector, {
		onSuccess: typeof onSuccess == 'function' ? onSuccess : () => { },
		onSetFormData: function (formData) {
			return formData
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
