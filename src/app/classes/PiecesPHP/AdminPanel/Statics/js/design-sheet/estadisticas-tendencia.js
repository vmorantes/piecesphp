window.addEventListener('load', function () {
	//El total, con los separadores del idioma; el área y la variación frente al periodo anterior, con drawStatisticsTrend()
	//(statistics-chart-legend.js). Con `better: 'down'`, subir sería lo malo y la variación saldría en rojo.
	const card = document.querySelector('[example-trend]')
	if (card === null || typeof echarts === 'undefined') {
		return
	}
	const values = [31, 36, 34, 40, 38, 35, 42, 39, 44, 41, 37, 43, 46, 49]
	const points = values.map((value, index) => ({ label: `${String(index + 7).padStart(2, '0')}-11`, value: value }))
	const total = values.reduce((sum, value) => sum + value, 0)
	card.querySelector('[data-type="exampleTrendTotal"]').textContent = new Intl.NumberFormat(document.documentElement.lang || undefined).format(total)
	drawStatisticsTrend(card.querySelector('.ui.chart > .canvas'), points, { name: card.querySelector('.header').textContent, previous: 512, better: 'up' })
})
