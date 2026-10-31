/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	let langGroup = 'reportsManage'

	showGenericLoader(langGroup)

	getDataConfig()

	removeGenericLoader(langGroup)

	function getDataConfig() {

		showGenericLoader('getDataConfig')

		//Las series son la paleta de datos del tema (--data-*), no el JSON: ApexCharts no entiende var() ni light-dark(), así
		//que una sonda los resuelve aquí, y el mismo color va a la leyenda de cada torta.
		const seriesColors = resolveColors(['var(--data-1)', 'var(--data-2)', 'var(--statistics-disabled-color, gray)'])

		const elementsToFill = [
			{
				dataType: 'peopleData',
				fill: function (value) {
					fillCountries(this.dataType, value, seriesColors)
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
			{
				dataType: 'organizationsData',
				fill: function (value) {
					fillCountries(this.dataType, value, seriesColors)
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
			{
				dataType: 'totalPendingPublicationsQty',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(formatNumberString(value, '.', ','))
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
			{
				dataType: 'totalRemainingTokens',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(formatNumberString(value, '.', ','))
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
		]

		//Configurar tooltip en cards
		$('.card-statistic .toolbar .help:not(.icon)').map(function (index, element) {
			let tooltipText = element.dataset.tooltip
			tooltipText = typeof tooltipText == 'string' && tooltipText.length > 0 ? tooltipText : null
			if (tooltipText == null) {
				element.remove()
			}
		})

		//Datos entrantes: la URL la da la vista (data-generic-data-url, con routeName()), no se arma aquí.
		const dataURL = document.querySelector('[data-generic-data-url]').dataset.genericDataUrl
		fetch(dataURL).then((response) => response.json()).then(function (response) {
			for (const elementToFill of elementsToFill) {
				const value = elementToFill.getValueFromSource(response)
				elementToFill.fill(value)
			}
		}).finally(function () {
			removeGenericLoader('getDataConfig')
		})

	}

	/**
	 * La leyenda y la torta de una tarjeta por países: los dos primeros con su nombre (el del JSON) y «otros».
	 * Un país que no esté no se pinta: ni su fila ni su porción.
	 * @param {string} dataType
	 * @param {{total:number,byCountry:{country:number,name:string,qty:number}[],others:number,chartData:Object}} value
	 * @param {string[]} seriesColors Primero, segundo y «otros»
	 */
	function fillCountries(dataType, value, seriesColors) {
		const mainDomElement = $(`[data-type="${dataType}"]`)
		const chartElementContainer = mainDomElement.find('.apex-chart')
		for (const position of [1, 2]) {
			const valueElement = mainDomElement.find(`[data-type="${dataType}Country${position}"]`)
			const row = valueElement.closest('.item')
			const country = value.byCountry[position - 1]
			if (country === undefined) {
				row.hide()
				continue
			}
			row.find('.name').text(country.name)
			valueElement.html(formatNumberString(country.qty, '.', ','))
			row.show()
		}
		mainDomElement.find(`[data-type="${dataType}Others"]`).html(formatNumberString(value.others, '.', ','))
		//Los colores de los países que hay, y el de «otros» siempre al final, como su fila de la leyenda.
		const colors = value.byCountry.map((country, index) => seriesColors[index]).concat([seriesColors[2]])
		const chartData = value.chartData
		configDonutChart(getUniqueSelector(chartElementContainer.get(0)), chartData.series, chartData.labels, colors, chartData.style, chartData.background, chartData.unitText)
	}

	/**
	 * @param {string[]} values Colores CSS, también var(--…, reserva)
	 * @returns {string[]} El color que resuelve el navegador para cada uno
	 */
	function resolveColors(values) {
		const probe = document.createElement('span')
		probe.hidden = true
		document.body.appendChild(probe)
		const resolved = values.map((value) => {
			probe.style.color = ''
			probe.style.color = value
			return getComputedStyle(probe).color
		})
		probe.remove()
		return resolved
	}

	function configDonutChart(selector, series, labels, colors, style, background, unitText = '') {
		//Sin datos, una sola porción del color de pista de las piezas de estadísticas.
		const emptyColor = resolveColors(['var(--statistics-track-color, gainsboro)'])[0]
		const chartHeight = 350
		const donutThickness = 65
		const radius = chartHeight / 2
		const sizePercent = 1 - (donutThickness / radius)

		//Verificar si hay datos
		const hasData = series && series.length > 0 && series.some(val => val > 0)

		const options = {
			series: hasData ? series : [100],
			chart: {
				type: 'donut',
				height: chartHeight
			},
			labels: hasData ? labels : [''],
			colors: hasData ? colors : [emptyColor],
			dataLabels: {
				enabled: hasData,
				dropShadow: {
					enabled: false
				},
				style: {
					fontSize: style.fontSize,
					fontFamily: getComputedStyle(document.documentElement).getPropertyValue('--font-body').trim(),
					fontWeight: style.fontWeight,
					colors: hasData ? style.colors : [emptyColor]
				},
				formatter: function (val) {
					return hasData ? val.toFixed(1) + "%" : ""
				}
			},
			fill: {
				colors: hasData ? colors : [emptyColor]
			},
			plotOptions: {
				pie: {
					donut: {
						size: `${(sizePercent * 100).toFixed(1)}%`, // tamaño calculado para grosor fijo
						background: background,
						labels: {
							show: false
						}
					}
				}
			},
			stroke: {
				show: false, // Oculta el borde externo (outline)
				width: 0,
				colors: ['transparent']
			},
			//La leyenda es la de la tarjeta (chart-legend.php), enlazada abajo.
			legend: {
				show: false,
			},
			tooltip: {
				enabled: hasData,
				y: {
					formatter: function (val) {
						return val + (unitText.length > 0 ? ` ${unitText}` : '')
					}
				}
			}
		}
		const container = document.querySelector(selector)
		const chart = new ApexCharts(container, options)
		const card = container !== null ? container.closest('.card-statistic') : null
		const legend = card !== null ? card.querySelector('.chart-data') : null
		//Cada fila de la leyenda, con el color de su serie: el de la vista es var(…) y sin reserva (chart-legend.php).
		if (legend !== null) {
			legend.querySelectorAll(':scope > .item').forEach((item, index) => {
				if (typeof colors[index] == 'string') {
					item.style.setProperty('--custom-color', colors[index])
				}
			})
		}
		//Sin datos el gráfico es una sola porción gris: no hay series que encender ni apagar.
		chart.render().then(() => hasData && linkStatisticsChartLegend(chart, legend))
	}

})
