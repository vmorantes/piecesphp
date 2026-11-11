window.addEventListener('load', function () {

	//Intentos por día (attempts.php): área apilada, fallidos abajo y exitosos encima. Los colores salen del tema en el
	//navegador: el acento, la variable; el rojo, el de `ui red` leído con getComputedStyle (el tema no lo da en variable).
	for (const container of document.querySelectorAll('[data-access-days-chart]')) {
		if (typeof echarts === 'undefined') {
			break
		}
		let series = null
		try {
			series = JSON.parse(container.getAttribute('data-access-days-chart'))
		} catch (error) {
			continue
		}
		const legend = container.closest('.card-statistic').querySelector('.chart-data')
		const items = Array.from(legend.querySelectorAll('.item'))
		const probe = document.createElement('span')
		probe.className = 'ui red text'
		document.body.appendChild(probe)
		const red = getComputedStyle(probe).color
		probe.remove()
		const [accent, errorColor, muted, border] = statisticsChartThemeColors(['var(--accent)', red, 'var(--theme-text-muted)', 'var(--theme-border)'])
		//El punto de la leyenda de los fallidos, del mismo rojo que su serie.
		if (items[1] !== undefined) {
			items[1].style.setProperty('--custom-color', errorColor)
		}
		const name = (item) => item.querySelector('.name').textContent.trim()
		const chart = echarts.init(container)
		chart.setOption({
			legend: { show: false },
			tooltip: { trigger: 'axis' },
			grid: { left: 36, right: 12, top: 12, bottom: 28 },
			xAxis: { type: 'category', boundaryGap: false, data: series.labels, axisTick: { show: false }, axisLine: { lineStyle: { color: border } }, axisLabel: { color: muted } },
			yAxis: { type: 'value', minInterval: 1, splitNumber: 2, splitLine: { lineStyle: { color: border } }, axisLabel: { color: muted } },
			series: [
				{ name: name(items[1]), type: 'line', stack: 'attempts', smooth: 0.3, symbol: 'none', data: series.error, color: errorColor, lineStyle: { width: 1.5 }, areaStyle: { opacity: 0.24 } },
				{ name: name(items[0]), type: 'line', stack: 'attempts', smooth: 0.3, symbol: 'none', data: series.success, color: accent, lineStyle: { width: 2 }, areaStyle: { opacity: 0.16 } },
			],
		})
		fitStatisticsChart(chart)
		linkStatisticsChartLegend(chart, legend)
		resizeStatisticsChart(chart)
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
