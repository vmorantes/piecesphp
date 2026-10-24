/// <reference path="../../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	const table = $('[process]')
	if (table.length === 0) {
		return
	}

	dataTableServerProccesing(table, table.attr('process'), 10, {
		responsive: false,
		autoWidth: false,
		initComplete: function () {
			configMirrorScrollX('namespace.mirror-scroll-x.all', '.mirror-scroll-x.all')
		},
	})

})
