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
				dom: `<'ui stackable grid'<'row'<'eight wide column'l><'right aligned eight wide column'<'custom-search'>>><'row dt-table'<'sixteen wide column'tr>><'row'<'seven wide column'i><'right aligned nine wide column'p>>>`,
				responsive: false,
				autoWidth: false,
				drawCallback: function () {
				},
				initComplete: function (a, b, c, d) {
					const thisDataTable = this.DataTable()
					const container = this.closest('.container-standard-table')
					const templates = $(`<div>${container.find('template').get(0).innerHTML}</div>`)
					const searchFilters = $(templates.find('search-filters').html())
					const customSearchContainer = container.find('.custom-search')
					customSearchContainer.append(searchFilters)
					searchFilters.find('[search-input] input').off('keyup').on('keyup', function (e) {
						thisDataTable.search($(e.currentTarget).val()).draw()
					})
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


})
