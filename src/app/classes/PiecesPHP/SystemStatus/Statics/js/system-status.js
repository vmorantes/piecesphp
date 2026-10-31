/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	const post = function (url, body) {
		return fetch(url, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (response) {
				return response.json().then(function (data) {
					if (!response.ok) {
						throw new Error(typeof data.error === 'string' ? data.error : String(response.status))
					}
					return data
				})
			})
	}

	//Avisos: ocultar o mostrar un aviso ocultable. El botón dice lo que hará (data-hidden es el estado de ahora) y solo
	//cambia cuando el servidor lo confirma.
	const alerts = document.querySelector('[data-system-alerts]')
	if (alerts !== null) {
		const toggleURL = alerts.getAttribute('data-toggle-url')
		alerts.querySelectorAll('[data-system-alert-toggle]').forEach(function (button) {
			button.addEventListener('click', function (event) {
				event.preventDefault()
				if (button.classList.contains('disabled')) {
					return
				}
				const hidden = button.getAttribute('data-hidden') !== 'yes'
				const body = new FormData()
				body.set('key', button.getAttribute('data-key'))
				body.set('hidden', hidden ? 'yes' : 'no')
				button.classList.add('loading', 'disabled')
				button.setAttribute('aria-disabled', 'true')
				post(toggleURL, body)
					.then(function () {
						const text = hidden ? _i18n('system-status', 'Mostrar') : _i18n('system-status', 'Ocultar')
						button.setAttribute('data-hidden', hidden ? 'yes' : 'no')
						button.setAttribute('data-tooltip', text)
						button.setAttribute('aria-label', text)
						const icon = button.querySelector('.icon')
						if (icon !== null) {
							icon.className = hidden ? 'eye icon' : 'eye slash icon'
						}
						const state = button.closest('tr').querySelector('[data-system-alert-state]')
						if (state !== null) {
							state.textContent = hidden ? _i18n('system-status', 'Oculto') : _i18n('system-status', 'Activo')
							state.className = hidden ? 'ui label' : 'ui green basic circular label'
						}
					})
					.catch(function (error) {
						errorMessage(_i18n('system-status', 'Error'), error.message)
					})
					.finally(function () {
						button.classList.remove('loading', 'disabled')
						button.removeAttribute('aria-disabled')
					})
			})
		})
	}

	//Mantenimiento: cada acción pide confirmación y enseña su resultado como texto.
	const maintenance = document.querySelector('[data-system-maintenance]')
	if (maintenance !== null) {
		const result = maintenance.querySelector('[data-system-maintenance-result]')
		const show = function (lines, negative) {
			result.replaceChildren()
			const box = document.createElement('div')
			box.className = negative ? 'ui negative message' : 'ui positive message'
			const list = document.createElement('ul')
			list.className = 'list'
			for (const line of lines) {
				const item = document.createElement('li')
				item.textContent = String(line)
				list.appendChild(item)
			}
			box.appendChild(list)
			result.appendChild(box)
		}
		//El mismo formato que la vista (maintenance.php): MB con un decimal a partir de 1048576, si no KB.
		const tamano = function (bytes) {
			const n = Number(bytes) || 0
			return n >= 1048576 ? (n / 1048576).toFixed(1) + ' MB' : (n / 1024).toFixed(1) + ' KB'
		}
		//Una fila por limpieza registrada (CleanTargetRegistry) y la fija de los accesos rotos: su unidad y sus claves vienen
		//en la celda, desde la vista. Con dos claves, «A de B».
		const pintarEstado = function (status) {
			maintenance.querySelectorAll('[data-system-maintenance-row]').forEach(function (nodo) {
				const claves = (nodo.getAttribute('data-keys') || '').split(',')
				const unidad = nodo.getAttribute('data-unit')
				if (unidad === 'bytes') {
					nodo.textContent = tamano(status[claves[0]])
				} else if (unidad === 'number' && claves.length > 1) {
					nodo.textContent = _i18n('system-status', '%s de %s').replace('%s', String(status[claves[0]])).replace('%s', String(status[claves[1]]))
				} else {
					nodo.textContent = String(status[claves[0]])
				}
			})
		}
		maintenance.querySelectorAll('[data-system-maintenance-action]').forEach(function (button) {
			button.addEventListener('click', async function () {
				if (!(await confirmAction({ message: button.getAttribute('data-confirm') || '' }))) {
					return
				}
				button.classList.add('loading', 'disabled')
				const cuerpo = new FormData()
				const destino = button.getAttribute('data-system-maintenance-target')
				//Los dos botones de siempre no traen destino, así que su petición es idéntica a la de antes.
				if (destino !== null) {
					cuerpo.append('target', destino)
				}
				post(button.getAttribute('data-system-maintenance-action'), cuerpo)
					.then(function (data) {
						if (data.status) {
							pintarEstado(data.status)
						}
						if (typeof data.target === 'string') {
							const lineas = [typeof data.message === 'string' ? data.message : _i18n('system-status', 'Hecho')]
							if (typeof data.freed === 'number' && data.freed > 0) {
								lineas.push(_i18n('system-status', 'Espacio liberado') + ': ' + tamano(data.freed))
							}
							show(lineas, data.success === false)
						} else if (Array.isArray(data.deleted)) {
							//Solo los recuentos: la lista de rutas es para quien audita, y va al registro de acciones.
							const lines = [
								_i18n('system-status', 'Accesos rotos borrados') + ': ' + data.deleted.length,
								_i18n('system-status', 'Carpetas vacías borradas') + ': ' + (data.emptiedDirectories || []).length,
							]
							if (typeof data.failed === 'number' && data.failed > 0) {
								lines.push(_i18n('system-status', 'Sin permiso para borrar') + ': ' + data.failed)
							}
							show(lines, false)
						} else {
							show([typeof data.message === 'string' ? data.message : _i18n('system-status', 'Hecho')], data.success === false)
						}
					})
					.catch(function (error) {
						show([error.message], true)
					})
					.finally(function () {
						button.classList.remove('loading', 'disabled')
					})
			})
		})
	}
})
