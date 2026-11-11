window.addEventListener('load', function () {

	const form = document.querySelector('[data-transfer-import]')
	const report = document.querySelector('[data-transfer-report]')
	if (form === null || report === null) {
		return
	}

	//Todo lo que llega del servidor puede traer valores del archivo: se pinta como texto, nunca como HTML.
	const element = function (tag, className, text) {
		const node = document.createElement(tag)
		if (className) {
			node.className = className
		}
		if (text !== undefined) {
			node.textContent = String(text)
		}
		return node
	}

	const download = function (artifact) {
		const binary = atob(artifact.contentBase64)
		const bytes = new Uint8Array(binary.length)
		for (let i = 0; i < binary.length; i++) {
			bytes[i] = binary.charCodeAt(i)
		}
		const url = URL.createObjectURL(new Blob([bytes], { type: artifact.mimeType }))
		const link = element('a')
		link.href = url
		link.download = artifact.filename
		document.body.appendChild(link)
		link.click()
		link.remove()
		URL.revokeObjectURL(url)
	}

	const t = (text) => _i18n('DataImportExportUtility-lang', text)
	//Una sola pasada sobre la plantilla: lo que se sustituye (el nombre del archivo) no se vuelve a mirar.
	const fill = (text, values) => t(text).replace(/%(\w+)%/g, (marca, key) => Object.prototype.hasOwnProperty.call(values, key) ? String(values[key]) : marca)

	//──── El archivo: un campo de texto con el nombre y «Examinar», que abre el input real ────────────────
	const fileInput = form.querySelector('[data-transfer-file]')
	const fileName = form.querySelector('[data-transfer-file-name]')
	const fileField = form.querySelector('[data-transfer-file-field]')
	const browse = form.querySelector('[data-transfer-file-browse]')
	const chosenName = () => fileInput !== null && fileInput.files.length > 0 ? fileInput.files[0].name : ''

	const clearFileError = function () {
		if (fileField === null) {
			return
		}
		fileField.classList.remove('error')
		fileName.removeAttribute('aria-invalid')
		fileField.querySelectorAll('[data-transfer-file-error]').forEach((node) => node.remove())
		const describedBy = (fileName.getAttribute('aria-describedby') || '').split(/\s+/).filter((id) => id !== '' && id !== 'data-transfer-import-file-error')
		fileName.setAttribute('aria-describedby', describedBy.join(' '))
	}

	const markFileError = function (text) {
		if (fileField === null || fileName === null) {
			return
		}
		fileField.classList.add('error')
		fileName.setAttribute('aria-invalid', 'true')
		fileField.querySelectorAll('[data-transfer-file-error]').forEach((node) => node.remove())
		const label = element('div', 'ui basic red pointing prompt label', text)
		label.setAttribute('data-transfer-file-error', '')
		label.id = 'data-transfer-import-file-error'
		const anchor = fileName.closest('.ui.action.input') || fileName
		anchor.after(label)
		//El error, también para el lector de pantalla: el campo lo anuncia al recibir el foco.
		const describedBy = (fileName.getAttribute('aria-describedby') || '').split(/\s+/).filter((id) => id !== '' && id !== 'data-transfer-import-file-error')
		fileName.setAttribute('aria-describedby', ['data-transfer-import-file-error', ...describedBy].join(' '))
		fileName.focus()
	}

	if (fileInput !== null && fileName !== null && browse !== null) {
		browse.addEventListener('click', () => fileInput.click())
		fileName.addEventListener('click', () => fileInput.click())
		fileName.addEventListener('keydown', function (event) {
			if (event.key === 'Enter' || event.key === ' ') {
				event.preventDefault()
				fileInput.click()
			}
		})
		fileInput.addEventListener('change', function () {
			fileName.value = chosenName()
			clearFileError()
		})

		//Soltar un archivo sobre el campo lo elige, si su extensión es una de las del input real (accept).
		const accepted = (fileInput.getAttribute('accept') || '').split(',').map((e) => e.trim().toLowerCase()).filter((e) => e !== '')
		fileField.addEventListener('dragover', function (event) {
			event.preventDefault()
			event.dataTransfer.dropEffect = 'copy'
		})
		fileField.addEventListener('drop', function (event) {
			event.preventDefault()
			const files = event.dataTransfer.files
			if (files.length === 0) {
				return
			}
			const name = files[0].name.toLowerCase()
			if (accepted.length > 0 && !accepted.some((extension) => name.endsWith(extension))) {
				markFileError(fill('Ese archivo no es una plantilla válida (%extensions%).', { extensions: accepted.map((e) => e.replace(/^\./, '')).join(', ') }))
				return
			}
			const transfer = new DataTransfer()
			transfer.items.add(files[0])
			fileInput.files = transfer.files
			fileInput.dispatchEvent(new Event('change', { bubbles: true }))
		})
	}

	//──── El resultado ─────────────────────────────────────────────────────────────────────────────────────
	const message = function (variant, icon, header, text) {
		const box = element('div', 'ui ' + variant + ' icon message')
		const iconNode = element('i', icon + ' icon')
		iconNode.setAttribute('aria-hidden', 'true')
		const content = element('div', 'content')
		content.appendChild(element('div', 'header', header))
		content.appendChild(element('p', null, text))
		box.appendChild(iconNode)
		box.appendChild(content)
		return box
	}

	const paint = function (data, filename) {
		//Un JSON que no es el informe (sesión caducada, un error del servidor) no se lee como «archivo sin filas».
		if (data === null || typeof data !== 'object' || typeof data.total !== 'number') {
			throw new Error('respuesta sin informe')
		}
		report.replaceChildren()
		const total = Number(data.total) || 0
		const valid = Number(data.valid) || 0
		const rows = Array.isArray(data.rows) ? data.rows : []
		const headerErrors = Array.isArray(data.headerErrors) ? data.headerErrors : []
		const invalid = rows.length

		const segment = element('section', 'ui segment')
		segment.setAttribute('aria-labelledby', 'import-result-title')
		const title = element('h2', 'ui header', t('Resultado de la importación'))
		title.id = 'import-result-title'
		segment.appendChild(title)

		if (total > 0) {
			segment.appendChild(element('p', 'ui muted text', fill('Archivo: %name% · %total% filas leídas', { name: filename, total: total })))
		}

		//headerErrors mezcla causas (títulos, tope de filas, rechazo de la subida, fallo al guardar): no se afirma ninguna.
		if (headerErrors.length > 0) {
			segment.appendChild(message('error', 'times circle', t('No se pudo importar el archivo'), t('El motivo está en la tabla de abajo.')))
		} else if (total === 0) {
			//Las filas vacías se saltan: «sin filas con datos», no «solo los títulos».
			segment.appendChild(message('warning', 'exclamation triangle', t('El archivo no tiene filas con datos'), t('Llena la plantilla debajo de la fila de los títulos y vuelve a subirla.')))
		} else if (data.persisted) {
			segment.appendChild(message('success', 'check circle', fill('Se importaron las %n% filas', { n: total }), t('Todas las filas eran válidas y se guardaron.')))
		} else if (data.dryRun && invalid === 0) {
			segment.appendChild(message('info', 'info circle', fill('Simulacro: las %n% filas son válidas', { n: total }), t('No se guardó nada.')))
		} else if (data.dryRun) {
			segment.appendChild(message('warning', 'exclamation triangle', fill('Simulacro: %x% de %n% filas tienen errores', { x: invalid, n: total }), t('No se guardó nada.')))
		} else {
			segment.appendChild(message('error', 'times circle', t('No se importó ninguna fila'), fill('%x% de %n% filas tienen errores. Si una fila tiene errores, no se guarda ninguna: corrígelas y vuelve a subir el archivo.', { x: invalid, n: total })))
		}

		//Sin filas que contar (errores generales o archivo sin datos), no hay etiquetas.
		if (headerErrors.length === 0 && total > 0) {
			const labels = element('div', 'ui labels')
			labels.appendChild(element('span', 'ui green label', fill('%n% válidas', { n: valid })))
			if (invalid > 0) {
				labels.appendChild(element('span', 'ui red label', fill('%n% con errores', { n: invalid })))
			}
			segment.appendChild(labels)
		}

		//position cuenta las filas de datos desde 1 (ImportRunner::run()); la 1 del archivo es la de los títulos.
		const messages = []
		for (const text of headerErrors) {
			messages.push(['—', text])
		}
		for (const row of rows) {
			for (const error of (Array.isArray(row.errors) ? row.errors : [])) {
				messages.push([Number(row.position) + 1, error])
			}
		}
		if (messages.length > 0) {
			const region = element('div', 'ui basic fitted horizontal scrolling segment')
			region.setAttribute('role', 'region')
			region.setAttribute('tabindex', '0')
			region.setAttribute('aria-label', t('Errores de la importación'))
			const table = element('table', 'ui very basic unstackable table')
			const head = element('thead')
			const headRow = element('tr')
			headRow.appendChild(element('th', null, t('Fila')))
			headRow.appendChild(element('th', null, t('Error')))
			head.appendChild(headRow)
			const body = element('tbody')
			for (const [position, text] of messages) {
				const tr = element('tr')
				tr.appendChild(element('td', null, position))
				tr.appendChild(element('td', null, text))
				body.appendChild(tr)
			}
			table.appendChild(head)
			table.appendChild(body)
			region.appendChild(table)
			segment.appendChild(region)
			if (rows.length > 0) {
				segment.appendChild(element('p', 'ui muted text', t('La fila 1 del archivo es la de los títulos.')))
			}
		}

		report.appendChild(segment)

		if (data.persisted && data.artifact) {
			download(data.artifact)
		}
	}

	form.addEventListener('submit', function (event) {
		event.preventDefault()
		if (fileInput !== null && fileField !== null && fileName !== null && chosenName() === '') {
			markFileError(t('Elige el archivo de la plantilla.'))
			return
		}
		const filename = chosenName()
		const button = form.querySelector('button[type="submit"]')
		button.classList.add('loading', 'disabled')
		fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then(response => response.json())
			.then((data) => paint(data, filename))
			.catch(() => {
				report.replaceChildren(element('p', null, _i18n('DataImportExportUtility-lang', 'No se pudo completar la importación.')))
			})
			.finally(() => button.classList.remove('loading', 'disabled'))
	})
})
