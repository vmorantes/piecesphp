/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	let tableSelector = "table[url]"
	let table = $(tableSelector)
	let tableURLAttr = "url"

	const dataTable = dataTableServerProccesing(table, table.attr(tableURLAttr), 20, {
		responsive: false,
		drawCallback: function () {
			window.dispatchEvent(new Event('canDeleteDocumentType'))
			configMirrorScrollX()
		},
	}).DataTable()

	DocumentTypes.configDocumentTypeForm(function (formProcess) {
		return new Promise(function (resolve) {
			formProcess[0].reset()
			formProcess.find('.ui.dropdown').dropdown('clear')
			formProcess.find('.ui.dropdown').dropdown('refresh')
			dataTable.draw()
			resolve()
		})
	}, true, true)


})
