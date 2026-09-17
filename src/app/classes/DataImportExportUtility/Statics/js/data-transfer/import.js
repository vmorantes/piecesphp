/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
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

	const paint = function (data) {
		report.replaceChildren()
		const summary = element('p', null,
			data.persisted
				? _i18n('DataImportExportUtility-lang', 'Importación completada.') + ' ' + data.total
				: _i18n('DataImportExportUtility-lang', 'No se guardó ninguna fila.') + ' ' + data.valid + '/' + data.total
		)
		report.appendChild(summary)

		const messages = []
		for (const message of (data.headerErrors || [])) {
			messages.push(['', message])
		}
		for (const row of (data.rows || [])) {
			for (const error of row.errors) {
				messages.push([row.position, error])
			}
		}
		if (messages.length > 0) {
			const table = element('table', 'ui basic table')
			const body = element('tbody')
			for (const [position, text] of messages) {
				const tr = element('tr')
				tr.appendChild(element('td', null, position))
				tr.appendChild(element('td', null, text))
				body.appendChild(tr)
			}
			table.appendChild(body)
			report.appendChild(table)
		}

		if (data.persisted && data.artifact) {
			download(data.artifact)
		}
	}

	form.addEventListener('submit', function (event) {
		event.preventDefault()
		const button = form.querySelector('button[type="submit"]')
		button.classList.add('loading', 'disabled')
		fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then(response => response.json())
			.then(paint)
			.catch(() => {
				report.replaceChildren(element('p', null, _i18n('DataImportExportUtility-lang', 'No se pudo completar la importación.')))
			})
			.finally(() => button.classList.remove('loading', 'disabled'))
	})
})
