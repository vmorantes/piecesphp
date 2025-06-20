/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	//Tablas
	const tables = [
		{
			selector: 'table[url].actives',
			ajaxURLAttribute: 'url',
			table: null,
			dataTable: null,
			length: 20,
			options: {
				responsive: false,
				autoWidth: false,
				drawCallback: function () {
					window.dispatchEvent(new Event('canDeleteOrganization'))
				},
				initComplete: function () {
					configMirrorScrollX('namespace.mirror-scroll-x.actives', '.mirror-scroll-x.actives')
				},
			},
		},
		{
			selector: 'table[url].inactives',
			ajaxURLAttribute: 'url',
			table: null,
			dataTable: null,
			length: 20,
			options: {
				responsive: false,
				autoWidth: false,
				drawCallback: function () {
					window.dispatchEvent(new Event('canDeleteOrganization'))
				},
				initComplete: function () {
					configMirrorScrollX('namespace.mirror-scroll-x.inactives', '.mirror-scroll-x.inactives')
				},
			},
		},
		{
			selector: 'table[url].pendings',
			ajaxURLAttribute: 'url',
			table: null,
			dataTable: null,
			length: 20,
			options: {
				responsive: false,
				autoWidth: false,
				drawCallback: function () {
					window.dispatchEvent(new Event('canDeleteOrganization'))
				},
				initComplete: function () {
					configMirrorScrollX('namespace.mirror-scroll-x.pendings', '.mirror-scroll-x.pendings')
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
