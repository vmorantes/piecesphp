/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../ApplicationCalls/Statics/js/ApplicationCallAdapter.js" />
window.addEventListener('load', function (e) {

	const langGroup = 'content-navigation-hub-lang'

	registerDynamicLocalizationMessages(langGroup)

	const loaderName = generateUniqueID()
	showGenericLoader(loaderName)

	/* Elementos de interfaz */
	/**
	 * @type {ApplicationCallAdapter}
	 */
	let elementsManager = null
	/**
	 * @type {URL}
	 */
	let elementsRequestURL = null
	let formFilterSelector = '.ui.form.section.filters'
	let formFilter = $(formFilterSelector)

	/* Configuraciones básicas de interfaz */
	configListing()
	configFilters()

	removeGenericLoader(loaderName)

	function configListing() {
		elementsRequestURL = $('[data-application-call-url]').attr('data-application-call-url')
		if (typeof elementsRequestURL == 'string' && elementsRequestURL.length > 0) {
			elementsRequestURL = new URL(elementsRequestURL)
			elementsRequestURL.searchParams.set('internal', 'yes')
			elementsManager = new ApplicationCallAdapter({
				scrollToOnLoadMore: true,
				requestURL: elementsRequestURL.href,
				page: 1,
				perPage: 10,
				containerSelector: '[application-calls-js]',
				loadMoreTriggerSelector: '[application-calls-load-more-js]',
				onDraw: (item, parsed, container) => {
					return parsed
				},
				onEmpty: (container) => {
					container.html(`<h2>${_i18n(langGroup, 'No hay resultados para mostrar')}</h2>`)
				},
			})

			elementsManager.loadItems().then(function (response) {
				const container = response.container
				const responseElements = response.response
				handleShareAction()
			})
		}
	}

	function configFilters() {
		const controlSearchInput = formFilter.find(`[control-search]`)
		const controlResearhAreasDropdown = formFilter.find(`[control-research-areas]`).dropdown({
			onAdd: function (addedValue, addedText, $addedChoice) {
				let maxTimes = 30
				let counterTry = 0
				const interval = setInterval(function () {
					const tag = controlResearhAreasDropdown.find(`.ui.label.visible[data-value=${addedValue}]`)
					const color = controlResearhAreasDropdown.find(`select option[value="${addedValue}"]`).data('color')
					tag.addClass('tag-area')
					tag.attr('style', `--tag-color: ${color};`)
					counterTry++
					if (counterTry >= maxTimes || tag.length > 0) {
						clearInterval(interval)
					}
				}, 200)
			}
		})
		const controlOrganizationsDropdown = formFilter.find(`[control-organizations ]`).dropdown()
		const controlContentTypeDropdown = formFilter.find(`[control-content-type]`).dropdown()
		const controlFinancingTypeDropdown = formFilter.find(`[control-financing-type ]`).dropdown()
		const controlStartDateInput = formFilter.find(`[control-start-date]`)
		const controlEndDateInput = formFilter.find(`[control-end-date]`)
		const objectQuery = {}
		const fieldsToObjectQuery = [
			{
				element: controlSearchInput,
				getValue: function () {
					const value = this.element.val()
					return value
				},
			},
			{
				element: controlResearhAreasDropdown,
				getValue: function () {
					const value = this.element.dropdown('get value')
					return value
				},
			},
			{
				element: controlOrganizationsDropdown,
				getValue: function () {
					const value = this.element.dropdown('get value')
					return value
				},
			},
			{
				element: controlContentTypeDropdown,
				getValue: function () {
					const value = this.element.dropdown('get value')
					return value
				},
			},
			{
				element: controlFinancingTypeDropdown,
				getValue: function () {
					const value = this.element.dropdown('get value')
					return value
				},
			},
			{
				element: controlStartDateInput,
				getValue: function () {
					const value = this.element.val()
					return value
				},
			},
			{
				element: controlEndDateInput,
				getValue: function () {
					const value = this.element.val()
					return value
				},
			},
		]
		fieldsToObjectQuery.map((e) => {
			let name = e.element.attr('name')
			if (e.element.hasClass('dropdown')) {
				name = e.element.find('select').attr('name')
			}
			objectQuery[name] = e
		})
		formFilter.on('submit', function (e) {
			e.preventDefault()
			const formData = new FormData(formFilter.get(0))
			const uniqueKeys = Array.from(new Set(formData.keys()))

			if (elementsRequestURL !== null) {
				//Se eliminan los parámetros de la URL
				Object.keys(objectQuery).map(e => elementsRequestURL.searchParams.delete(e))
				//Se asignan los valores del filtro y se rehace la consulta
				for (const key of uniqueKeys) {
					const value = objectQuery[key].getValue()
					const valid = (typeof value == 'string' && value.trim().length > 0) || typeof value !== 'string'
					if (elementsRequestURL !== null && valid) {
						if (Array.isArray(value)) {
							value.map(function (e) {
								elementsRequestURL.searchParams.append(key, e)
							})
						} else {
							elementsRequestURL.searchParams.set(key, value)
						}
					}
				}
				elementsManager.reload(elementsRequestURL).loadItems().then(function (response) {
					handleShareAction()
				})
			}
		})
	}

	function handleShareAction() {
		$('[share-action]').off('click').on('click', function (e) {
			e.preventDefault()
			const element = $(e.currentTarget)
			console.log(element)
			shareLinkContent({
				title: element.data('title'),
				text: element.data('text'),
				url: element.data('url'),
				onCopy: (url) => console.log('Copiado:', url),
				onShare: (shareData) => console.log('Compartido:', shareData)
			})
		})
	}
})
