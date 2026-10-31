/// <reference path="../../../../../../statics/core/js/configurations.js" />
window.addEventListener('load', () => {

	const table = document.querySelector('[data-routes-table]')
	if (table === null) {
		return
	}
	//La quinta columna (Módulo) no se ve, pero se busca en ella: buscar un módulo filtra sus rutas.
	$(table).DataTable(Object.assign({}, pcsphpGlobals.configDataTables, {
		pageLength: 10,
		responsive: false,
		autoWidth: false,
		order: [[4, 'asc'], [0, 'asc']],
		columns: [
			{ width: '24%' },
			{ width: '22%' },
			{ width: '26%' },
			{ width: '28%' },
			{ visible: false },
		],
		language: Object.assign({}, pcsphpGlobals.configDataTables.language, {
			searchPlaceholder: table.getAttribute('data-search-placeholder') || '',
		}),
	}))

})
