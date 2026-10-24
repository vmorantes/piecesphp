/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	//El menú «⋯» de cada fila (RowActions): DataTables pinta filas nuevas en cada dibujado.
	const rowActionsMenus = () => $('[row-actions-menu]').dropdown({ action: 'hide' })

	let tableSelector = "table[url]"
	let table = $(tableSelector)
	let tableURLAttr = "url"

	dataTableServerProccesing(table, table.attr(tableURLAttr), 20, {
		responsive: false,
		drawCallback: function () {
			rowActionsMenus()
			window.dispatchEvent(new Event('canDeleteDocument'))
			configMirrorScrollX()
		},
	}).DataTable()

})
