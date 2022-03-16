/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../statics/core/own-plugins/LocationsAdapter.js" />
var Persons = {}

Persons.configPersonForm = function (onSuccess = null, ignoreRedirection = false, ignoreReload = false) {

	showGenericLoader('configPersonForm')

	let formSelector = `.ui.form[person-form]`
	let langGroup = 'appPersonsLang'
	let isEdit = false
	const storagesIDToDelete = new Set()

	configFomanticDropdown('.ui.dropdown.auto') //Debe inciarse antes de genericFormHandler para la validación

	let dropdownStorages = configFomanticDropdown('.ui.dropdown.storages', {
		onAdd: function (value, text, $selectedItem) {
			if (storagesIDToDelete.has(value)) {
				storagesIDToDelete.delete(value)
			}
		},
		onRemove: function (value, text, $selectedItem) {
			storagesIDToDelete.add(value)
		},
	})[0]

	let locations = new LocationsAdapter()

	locations.setOnChangeCityDropdown(function (value, text, $selectedItem) {
		value = parseInt(value)
		value = !isNaN(value) && Number.isFinite(value) ? value : -1
		const searchStorageURL = new URL(dropdownStorages.data('search-url'))
		searchStorageURL.searchParams.set('city', value)
		dropdownStorages.recreate(true, searchStorageURL.href)
	})

	locations.fillSelectWithStates(1)

	let form = genericFormHandler(formSelector, {
		onSuccess: typeof onSuccess == 'function' ? onSuccess : () => { },
		onSetFormData: function (formData) {
			formData.set('storagesToDelete[]', Array.from(storagesIDToDelete.values()))
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

	removeGenericLoader('configPersonForm')

}
