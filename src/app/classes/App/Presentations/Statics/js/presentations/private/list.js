/// <reference path="../../../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	dataTablesServerProccesingOnCards('.table-to-cards', 20, {
		drawCallbackEnd: function (cards) {
			window.dispatchEvent(new Event('canDeletePresentation'))
		},
	})

})
