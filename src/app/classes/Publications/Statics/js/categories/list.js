/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	const table = $('table[url]')

	dataTableServerProccesing(table, table.attr('url'), 20, {
		responsive: false,
		autoWidth: false,
		drawCallback: function () {
			window.dispatchEvent(new Event('canDeletePublicationCategory'))
			configMirrorScrollX()
		},
	}).DataTable()

})
