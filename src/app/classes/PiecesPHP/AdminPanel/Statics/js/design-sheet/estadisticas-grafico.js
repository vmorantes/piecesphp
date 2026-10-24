/// <reference path="../configurations.js" />
/// <reference path="../helpers.js" />
window.addEventListener('load', function () {
	//ApexCharts sobre el contenedor de chart-card.php. Nombres, valores y colores salen de su leyenda (chart-legend.php).
	const container = document.querySelector('[example-chart]')
	if (container === null || typeof ApexCharts === 'undefined') {
		return
	}
	const legend = container.closest('.card-statistic').querySelector('.chart-data')
	const items = Array.from(legend.querySelectorAll('.item'))
	const chart = new ApexCharts(container, {
		chart: { type: 'donut', height: 220 },
		series: items.map((item) => parseFloat(item.querySelector('.value').textContent.trim()) || 0),
		labels: items.map((item) => item.querySelector('.name').textContent.trim()),
		colors: items.map((item) => item.style.getPropertyValue('--custom-color').trim()),
		legend: { show: false },
		dataLabels: { enabled: false },
	})
	//La leyenda nativa, apagada; la nuestra, enlazada con statistics-chart-legend.js.
	chart.render().then(() => linkStatisticsChartLegend(chart, legend))
})
