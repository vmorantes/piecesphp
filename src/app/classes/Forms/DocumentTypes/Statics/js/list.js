/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	let tableSelector = "table[url]"
	let table = $(tableSelector)
	let tableURLAttr = "url"

	//El alta y la edición van en la ventana de la pieza (admin-area/js/main.js, contentModals()).
	dataTableServerProccesing(table, table.attr(tableURLAttr), 20, {
		responsive: false,
		autoWidth: false,
		drawCallback: function () {
			window.dispatchEvent(new Event('canDeleteDocumentType'))
			configMirrorScrollX()
		},
	})

})
