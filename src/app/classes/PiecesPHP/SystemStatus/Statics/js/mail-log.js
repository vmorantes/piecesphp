/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	//Sin la tabla en la base la vista no pinta el <table>: entonces no hay nada que inicializar.
	let table = $('table[data-mail-log-table][url]')
	if (table.length === 0) {
		return
	}

	const baseURL = table.attr('url')
	const form = document.querySelector('[data-mail-log-filters]')
	$(form).find('select.ui.dropdown').dropdown()

	//Los filtros van en la dirección de los datos: el servidor los aplica con marcadores.
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

	const dataTable = dataTableServerProccesing(table, baseURL, 10, {
		responsive: false,
		drawCallback: function () {
			configMirrorScrollX()
		},
	}).DataTable()

	form.addEventListener('submit', function (event) {
		event.preventDefault()
		dataTable.ajax.url(urlWithFilters()).load()
	})
	form.addEventListener('reset', function () {
		setTimeout(function () {
			$(form).find('select.ui.dropdown').dropdown('clear')
			dataTable.ajax.url(baseURL).load()
		}, 0)
	})

	//El visor solo existe si quien mira tiene el permiso del cuerpo.
	const viewer = document.querySelector('[data-mail-log-body-viewer]')
	if (viewer === null) {
		return
	}
	const frame = viewer.querySelector('[data-mail-log-body-frame]')
	//Delante del cuerpo: sin red. Una imagen remota avisaría al remitente de que root abrió ese correo, y
	//desde dónde; el sandbox no lo impide. Solo imágenes incrustadas (data:, cid:) y estilos en línea.
	const isolation = '<meta http-equiv="Content-Security-Policy" content="default-src \'none\'; img-src data: cid:; style-src \'unsafe-inline\'">'
		+ '<meta name="referrer" content="no-referrer">'
	const message = viewer.querySelector('[data-mail-log-body-message]')

	const show = function (text) {
		//Como TEXTO, no como marcado: el mensaje también viene del servidor.
		message.textContent = text
		message.hidden = text === ''
		viewer.hidden = false
		viewer.scrollIntoView({ behavior: 'smooth', block: 'start' })
	}

	viewer.querySelector('[data-mail-log-body-close]').addEventListener('click', function () {
		frame.srcdoc = ''
		viewer.hidden = true
	})

	//Delegado: DataTables vuelve a pintar las filas en cada página.
	table.get(0).addEventListener('click', function (event) {
		const button = event.target.closest('[data-mail-log-body-url]')
		if (button === null) {
			return
		}
		fetch(button.getAttribute('data-mail-log-body-url'), {
			credentials: 'same-origin',
			headers: { 'X-Requested-With': 'XMLHttpRequest' },
		})
			.then(function (response) {
				return response.json()
			})
			.then(function (data) {
				if (data.status === 'ok' && typeof data.body === 'string') {
					//EL CUERPO ENTRA SOLO POR `srcdoc`, en un iframe con `sandbox` vacío: sin scripts y sin
					//el origen del panel. Pintarlo como marcado del propio panel sería un XSS contra root.
					frame.srcdoc = isolation + data.body
					show('')
					return
				}
				frame.srcdoc = ''
				show(typeof data.message === 'string' ? data.message : (typeof data.error === 'string' ? data.error : ''))
			})
			.catch(function () {
				frame.srcdoc = ''
				show(viewer.getAttribute('data-error-text') || '')
			})
	})

})
