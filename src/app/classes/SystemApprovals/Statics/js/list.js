window.addEventListener('load', function () {

	const table = $('table[url].all')
	if (table.length === 0) {
		return
	}
	const baseURL = table.attr('url')
	const dataTable = dataTableServerProccesing(table, baseURL, 20, {
		responsive: false,
		autoWidth: false,
		initComplete: function () {
			configMirrorScrollX('namespace.mirror-scroll-x.all', '.mirror-scroll-x.all')
		},
	}).DataTable()

	//Filtro: el tipo de contenido y el tiempo transcurrido viajan en la dirección de la tabla.
	const form = $('[data-approvals-filters]')
	const contentTypeDropdown = form.find('[name="contentType"]').dropdown()
	const elapsedDaysDropdown = form.find('[name="elapsedDays"]').dropdown()
	form.on('submit', function (e) {
		e.preventDefault()
		const url = new URL(dataTable.ajax.url())
		url.searchParams.set('referenceAlias', contentTypeDropdown.dropdown('get value'))
		url.searchParams.set('elapsedDays', elapsedDaysDropdown.dropdown('get value'))
		dataTable.ajax.url(url.href).load()
	})
	form.on('reset', function () {
		setTimeout(function () {
			contentTypeDropdown.dropdown('restore defaults')
			elapsedDaysDropdown.dropdown('restore defaults')
			dataTable.ajax.url(baseURL).load()
		}, 0)
	})

})
