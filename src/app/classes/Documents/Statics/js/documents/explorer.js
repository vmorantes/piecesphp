/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	const dataTable = dataTablesServerProccesingOnCards('.table-to-cards', 20, {
		drawCallbackEnd: function (cards) {
		},
	}).DataTable()
})
