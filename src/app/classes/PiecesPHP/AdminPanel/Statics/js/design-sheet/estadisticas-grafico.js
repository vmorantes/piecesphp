window.addEventListener('load', function () {
	//Cifras, porcentajes y total por el `data-type` de cada fila, con los separadores del idioma; la dona, con
	//drawStatisticsDonut() (statistics-chart-legend.js), que toma el color de cada fila y enlaza la leyenda.
	const card = document.querySelector('[example-chart]')
	if (card === null || typeof echarts === 'undefined') {
		return
	}
	const values = { exampleColombia: 60, exampleFrance: 25, exampleOthers: 15 }
	const total = Object.values(values).reduce((sum, value) => sum + value, 0)
	const language = document.documentElement.lang || undefined
	const numberFormat = new Intl.NumberFormat(language)
	const percentFormat = new Intl.NumberFormat(language, { style: 'percent', minimumFractionDigits: 1, maximumFractionDigits: 1 })
	const items = []
	for (const [dataType, value] of Object.entries(values)) {
		const row = card.querySelector(`[data-type="${dataType}"]`)
		row.querySelector('[data-value]').textContent = numberFormat.format(value)
		row.querySelector('[data-percent]').textContent = percentFormat.format(value / total)
		items.push({ name: row.querySelector('[data-name]').textContent, value: value, color: row.dataset.chartColor })
	}
	card.querySelector('[data-type="exampleTotal"]').textContent = numberFormat.format(total)
	drawStatisticsDonut(card.querySelector('.ui.chart > .canvas'), items)
})
