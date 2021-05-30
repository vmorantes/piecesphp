/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
showGenericLoader('filterView')
window.addEventListener('load', function (e) {

	dataTablesServerProccesingOnCards('.table-to-cards', 20, {
		drawCallbackEnd: function (cards) {
		},
	})
	
	$('.ui.accordion').accordion()

	removeGenericLoader('filterView')
})
