/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	let tableSelector = "table[url]"
	let table = $(tableSelector)
	let tableURLAttr = "url"

	dataTableServerProccesing(table, table.attr(tableURLAttr), 10, {
		responsive: false,
		drawCallback: function () {
			configMirrorScrollX()
		},
	})

})
