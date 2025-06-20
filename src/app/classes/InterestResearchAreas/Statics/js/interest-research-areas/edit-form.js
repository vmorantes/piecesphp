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


