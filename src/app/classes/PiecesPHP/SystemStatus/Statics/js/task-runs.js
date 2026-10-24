/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	//La chuleta del cron: plegable de Fomantic; abierta o cerrada la deja el servidor.
	$('[data-task-runs-cron]').accordion()

	//Registros › Tareas programadas: el libro, por servidor, con sus filtros en la dirección de los datos.
	const table = $('table[data-task-runs-table]')
	const form = document.querySelector('[data-task-runs-filters]')
	if (table.length === 0 || form === null) {
		return
	}
	const baseURL = table.attr('url')
	const urlWithFilters = function () {
		const params = new URLSearchParams()
		for (const [name, value] of new FormData(form).entries()) {
			if (typeof value === 'string' && value.trim() !== '') {
				params.set(name, value.trim())
			}
		}
		const query = params.toString()
		return query === '' ? baseURL : baseURL + (baseURL.includes('?') ? '&' : '?') + query
	}
	const dataTable = dataTableServerProccesing(table, baseURL, 20, { responsive: false }).DataTable()
	$(form).find('.ui.dropdown').dropdown()
	form.addEventListener('submit', function (event) {
		event.preventDefault()
		dataTable.ajax.url(urlWithFilters()).load()
	})
	form.addEventListener('reset', function () {
		setTimeout(() => {
			$(form).find('.ui.dropdown').dropdown('clear')
			dataTable.ajax.url(baseURL).load()
		}, 0)
	})

})
