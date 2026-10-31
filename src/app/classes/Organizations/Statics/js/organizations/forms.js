/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../../statics/core/own-plugins/ContentImageEditor.js" />
/// <reference path="../../../../../../statics/core/own-plugins/SimpleUploadPlaceholder.js" />
/// <reference path="../../../../../../statics/core/own-plugins/LocationsAdapter.js" />
showGenericLoader('_CARGA_INICIAL_')
window.addEventListener('load', function () {

	window.dispatchEvent(new Event('canDeleteOrganization'))

	let isEdit = false
	let formSelector = `.ui.form.organizations`
	let langGroup = 'appOrganizationsLang'
	new SimpleUploadPlaceholder({ containerSelector: '[organization-rut]' })
	//La tarjeta de imagen: lo aplicado en el editor se envía al guardar.
	let logo = null
	new ContentImageEditor().onApply('organization-logo', (file) => logo = file)

	//Dropdowns en general
	configFomanticDropdown('.ui.dropdown:not(.langs):not(.no-auto)') //Debe inciarse antes de genericFormHandler para la validación

	//Líneas de acción
	configFomanticDropdown('.ui.dropdown[name="actionLines[]"]', {
		allowAdditions: true,
	})

	//Ubicación	
	let locations = new LocationsAdapter()
	locations.fillSelectWithCountriesToCities()

	//Formulario
	let form = genericFormHandler(formSelector, {
		onSetFormData: function (formData) {

			if (logo !== null) {
				formData.set('logo', logo)
			}

			return formData
		},
	})

	//Tabs
	const tabs = $('.tabs-controls [data-tab]').tab({
		onVisible: function (tabName) {
		}
	})

	//Otros
	form.find('input, select, textarea').attr('autocomplete', 'off')
	form.find('.checkbox').checkbox()
	//Solo los del formulario: uno global reiniciaba el de la navegación del shell sin su selector.ignore del riel.
	form.find('.ui.accordion').accordion()

	isEdit = form.find(`[name="id"]`).length > 0

	//Idiomas
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

	removeGenericLoader('_CARGA_INICIAL_')

})


