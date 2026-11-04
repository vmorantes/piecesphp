/// <reference path="../configurations.js" />
/// <reference path="../helpers.js" />
window.addEventListener('load', function () {
	//Apache ECharts sobre el lienzo de chart-card.php. Nombres, valores y colores salen de su leyenda (chart-legend.php).
	const container = document.querySelector('[example-chart]')
	if (container === null || typeof echarts === 'undefined') {
		return
	}
	const legend = container.closest('.card-statistic').querySelector('.chart-data')
	const items = Array.from(legend.querySelectorAll('.item'))
	const chart = echarts.init(container)
	chart.setOption({
		color: items.map((item) => item.style.getPropertyValue('--custom-color').trim()),
		//La leyenda nativa, declarada y oculta; la nuestra, enlazada con statistics-chart-legend.js.
		legend: { show: false },
		tooltip: { trigger: 'item' },
		series: [{
			type: 'pie',
			radius: ['65%', '100%'],
			label: { show: false },
			data: items.map((item) => ({
				name: item.querySelector('.name').textContent.trim(),
				value: parseFloat(item.querySelector('.value').textContent.trim()) || 0,
			})),
		}],
	})
	linkStatisticsChartLegend(chart, legend)
	resizeStatisticsChart(chart)
})
