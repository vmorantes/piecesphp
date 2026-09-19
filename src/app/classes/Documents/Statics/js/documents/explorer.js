/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	const dataTable = dataTablesServerProccesingOnCards('.table-to-cards', 20, {
		drawCallbackEnd: function (cards) {
		},
	}).DataTable()
	const defaultURL = new URL(dataTable.ajax.url())

	configFomanticDropdown('.ui.dropdown', {
		onChange: function (value) {
			defaultURL.searchParams.set('FIELD_SAMPLE_FILTER_LOAD', value)
			dataTable.ajax.url(defaultURL.href).load()
		},
	})

})
