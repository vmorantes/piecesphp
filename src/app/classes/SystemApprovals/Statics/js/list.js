/// <reference path="../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	//Tablas
	const tables = [
		{
			name: 'approvals',
			selector: 'table[url].all',
			ajaxURLAttribute: 'url',
			table: null,
			dataTable: null,
			length: 20,
			options: {
				responsive: false,
				autoWidth: false,
				drawCallback: function () {
				},
				initComplete: function () {
					configMirrorScrollX('namespace.mirror-scroll-x.all', '.mirror-scroll-x.all')
				},
			},
		},
	]

	for (const tableConfig of tables) {
		const selector = tableConfig.selector
		const ajaxURLAttribute = tableConfig.ajaxURLAttribute
		const length = tableConfig.length
		const options = tableConfig.options
		tableConfig.table = $(selector)
		let ajaxURL = tableConfig.table.attr(ajaxURLAttribute)
		tableConfig.dataTable = dataTableServerProccesing(tableConfig.table, ajaxURL, length, options).DataTable()
	}

	//Tabs
	const tabs = $('.tabs-controls [data-tab]').tab({
		onVisible: function (tabName) {
			for (const tableConfig of tables) {
				tableConfig.dataTable.draw()
			}
		}
	})

	//Filtro
	const form = $('.ui.form.filters')
	const contentTypeDropdown = form.find('[name="contentType"]').dropdown()
	const elapsedDaysDropdown = form.find('[name="elapsedDays"]').dropdown()
	const filterButton = form.find('[type="submit"]')
	filterButton.on('click', function (e) {
		e.preventDefault()
		const tableConfig = tables.find(e => e.name == 'approvals')
		const url = new URL(tableConfig.dataTable.ajax.url())
		url.searchParams.set('referenceAlias', contentTypeDropdown.dropdown('get value'))
		url.searchParams.set('elapsedDays', elapsedDaysDropdown.dropdown('get value'))
		tableConfig.dataTable.ajax.url(url.href)
		tableConfig.dataTable.draw()
	})

})
