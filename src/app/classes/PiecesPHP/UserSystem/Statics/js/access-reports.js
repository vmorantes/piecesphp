window.addEventListener('load', function () {

	//Las donas de chart-card.php (Apache ECharts): nombres, valores y colores salen de su leyenda (chart-legend.php), que
	//pinta el servidor.
	for (const container of document.querySelectorAll('[data-access-chart]')) {
		if (typeof echarts === 'undefined') {
			break
		}
		const legend = container.closest('.card-statistic').querySelector('.chart-data')
		const items = Array.from(legend.querySelectorAll('.item'))
		const chart = echarts.init(container)
		chart.setOption({
			color: items.map((item) => item.style.getPropertyValue('--custom-color').trim()),
			//La leyenda nativa, declarada y oculta: la nuestra la usa para ocultar porciones (statistics-chart-legend.js).
			legend: { show: false },
			tooltip: { trigger: 'item' },
			series: [{
				type: 'pie',
				label: { show: false },
				data: items.map((item) => ({
					name: item.querySelector('.name').textContent.trim(),
					value: parseFloat(item.querySelector('.value').textContent.trim()) || 0,
				})),
			}],
		})
		fitStatisticsChart(chart, 0.65)
		linkStatisticsChartLegend(chart, legend)
		resizeStatisticsChart(chart)
	}

	for (const element of document.querySelectorAll('table[data-access-table]')) {
		const table = $(element)
		dataTableServerProccesing(table, table.attr('data-access-table'), 25, {
			responsive: false,
		})
	}

})
