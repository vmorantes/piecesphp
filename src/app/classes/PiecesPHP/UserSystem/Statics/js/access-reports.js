window.addEventListener('load', function () {

	//Exitosos y fallidos por día (attempts.php): una tendencia del tema por resultado, con drawStatisticsTrend()
	//(statistics-chart-legend.js), que pone la variación contra el mismo resultado del periodo anterior.
	const numberFormat = new Intl.NumberFormat(document.documentElement.lang || undefined)
	for (const canvas of document.querySelectorAll('[data-access-trend]')) {
		let trend = null
		try {
			trend = JSON.parse(canvas.getAttribute('data-access-trend'))
		} catch (error) {
			continue
		}
		const card = canvas.closest('.card')
		const points = Array.isArray(trend.points) ? trend.points : []
		const total = card !== null ? card.querySelector('.ui.statistic > .value') : null
		if (total !== null) {
			total.textContent = numberFormat.format(points.reduce((sum, point) => sum + (Number(point.value) || 0), 0))
		}
		const title = card !== null ? card.querySelector('.header') : null
		drawStatisticsTrend(canvas, points, { name: title !== null ? title.textContent : '', previous: trend.previous, better: trend.better, color: trend.color })
	}

	for (const element of document.querySelectorAll('table[data-access-table]')) {
		const table = $(element)
		const baseURL = table.attr('data-access-table')
		const dataTable = dataTableServerProccesing(table, baseURL, 25, {
			responsive: false,
			//La fila de un intento fallido, teñida con la de error del tema.
			createdRow: function (row) {
				if (row.querySelector('[data-attempt-result="error"]') !== null) {
					row.classList.add('negative')
				}
			},
		}).DataTable()

		//«Filtrar por resultado»: cambia `result` en la petición de la tabla y en Exportar; la URL de la página no cambia.
		const view = element.closest('[data-access-attempts]')
		const chips = view !== null ? view.querySelector('[data-access-results]') : null
		const exportLink = view !== null ? view.querySelector('[data-access-export]') : null
		if (chips === null) {
			continue
		}
		const withResult = (url, result) => result === '' ? url : url + (url.includes('?') ? '&' : '?') + 'result=' + encodeURIComponent(result)
		const choose = function (chip) {
			for (const other of chips.querySelectorAll('[data-access-result]')) {
				const active = other === chip
				other.classList.toggle('active', active)
				other.setAttribute('aria-checked', active ? 'true' : 'false')
				other.setAttribute('tabindex', active ? '0' : '-1')
			}
			const result = chip.getAttribute('data-access-result') || ''
			dataTable.ajax.url(withResult(baseURL, result)).load()
			if (exportLink !== null) {
				exportLink.setAttribute('href', withResult(exportLink.getAttribute('data-access-export'), result))
			}
		}
		chips.addEventListener('click', function (event) {
			const chip = event.target.closest('[data-access-result]')
			if (chip !== null) {
				event.preventDefault()
				choose(chip)
			}
		})
		chips.addEventListener('keydown', function (event) {
			const chip = event.target.closest('[data-access-result]')
			if (chip === null) {
				return
			}
			const all = Array.from(chips.querySelectorAll('[data-access-result]'))
			const index = all.indexOf(chip)
			let next = null
			if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
				next = all[(index + 1) % all.length]
			} else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
				next = all[(index - 1 + all.length) % all.length]
			} else if (event.key === ' ' || event.key === 'Enter') {
				event.preventDefault()
				choose(chip)
				return
			}
			if (next !== null) {
				event.preventDefault()
				next.focus()
				choose(next)
			}
		})
	}

})
