/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	//Los donuts de chart-card.php: nombres, valores y colores salen de su leyenda (chart-legend.php), que pinta el servidor.
	for (const container of document.querySelectorAll('[data-access-chart]')) {
		if (typeof ApexCharts === 'undefined') {
			break
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
		//La leyenda es la nuestra (chart-legend.php), enlazada: un clic oculta o muestra su porción.
		chart.render().then(() => linkStatisticsChartLegend(chart, legend))
	}

	for (const element of document.querySelectorAll('table[data-access-table]')) {
		const table = $(element)
		dataTableServerProccesing(table, table.attr('data-access-table'), 25, {
			responsive: false,
		})
	}

})
