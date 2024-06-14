/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	//Tablas
	const tables = [
		{
			selector: 'table[url].all',
			ajaxURLAttribute: 'url',
			table: null,
			dataTable: null,
			length: 20,
			options: {
				responsive: false,
				autoWidth: false,
				drawCallback: function () {
					window.dispatchEvent(new Event('canDeletePublication'))
				},
				initComplete: function () {
					configMirrorScrollX('namespace.mirror-scroll-x.all', '.mirror-scroll-x.all')
				},
			},
		},
		{
			selector: 'table[url].publicated',
			ajaxURLAttribute: 'url',
			table: null,
			dataTable: null,
			length: 20,
			options: {
				responsive: false,
				autoWidth: false,
				drawCallback: function () {
					window.dispatchEvent(new Event('canDeletePublication'))
				},
				initComplete: function () {
					configMirrorScrollX('namespace.mirror-scroll-x.publicated', '.mirror-scroll-x.publicated')
				},
			},
		},
		{
			selector: 'table[url].scheduled',
			ajaxURLAttribute: 'url',
			table: null,
			dataTable: null,
			length: 20,
			options: {
				responsive: false,
				autoWidth: false,
				drawCallback: function () {
					window.dispatchEvent(new Event('canDeletePublication'))
				},
				initComplete: function () {
					configMirrorScrollX('namespace.mirror-scroll-x.scheduled', '.mirror-scroll-x.scheduled')
				},
			},
		},
		{
			selector: 'table[url].draft',
			ajaxURLAttribute: 'url',
			table: null,
			dataTable: null,
			length: 20,
			options: {
				responsive: false,
				autoWidth: false,
				drawCallback: function () {
					window.dispatchEvent(new Event('canDeletePublication'))
				},
				initComplete: function () {
					configMirrorScrollX('namespace.mirror-scroll-x.draft', '.mirror-scroll-x.draft')
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


})
