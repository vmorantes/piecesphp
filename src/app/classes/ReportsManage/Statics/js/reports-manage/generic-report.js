/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	let langGroup = 'reportsManage'

	showGenericLoader(langGroup)

	getDataConfig()

	removeGenericLoader(langGroup)

	function getDataConfig() {

		showGenericLoader('getDataConfig')

		const elementsToFill = [
			{
				dataType: 'researchersData',
				fill: function (value) {
					const dataType = this.dataType
					const totalResearchersQty = value.totalResearchersQty
					const totalResearchersQtyColombia = value.totalResearchersQtyColombia
					const totalResearchersQtyFrancia = value.totalResearchersQtyFrancia
					const totalResearchersQtyOthers = value.totalResearchersQtyOthers
					const chartData = value.chartData
					const mainDomElement = $(`[data-type="${dataType}"]`)
					const chartElementContainer = mainDomElement.find('.apex-chart')
					mainDomElement.find('[data-type="totalResearchersQtyColombia"]').html(formatNumberString(totalResearchersQtyColombia, '.', ','))
					mainDomElement.find('[data-type="totalResearchersQtyFrancia"]').html(formatNumberString(totalResearchersQtyFrancia, '.', ','))
					mainDomElement.find('[data-type="totalResearchersQtyOthers"]').html(formatNumberString(totalResearchersQtyOthers, '.', ','))

					configDonutChart(getUniqueSelector(chartElementContainer.get(0)), chartData.series, chartData.labels, chartData.colors, chartData.style, chartData.background, chartData.unitText)
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
			{
				dataType: 'organizationsData',
				fill: function (value) {
					const dataType = this.dataType
					const totalOrganizationsQty = value.totalOrganizationsQty
					const totalOrganizationsQtyColombia = value.totalOrganizationsQtyColombia
					const totalOrganizationsQtyFrancia = value.totalOrganizationsQtyFrancia
					const totalOrganizationsQtyOthers = value.totalOrganizationsQtyOthers
					const chartData = value.chartData
					const mainDomElement = $(`[data-type="${dataType}"]`)
					const chartElementContainer = mainDomElement.find('.apex-chart')
					mainDomElement.find('[data-type="totalOrganizationsQtyColombia"]').html(formatNumberString(totalOrganizationsQtyColombia, '.', ','))
					mainDomElement.find('[data-type="totalOrganizationsQtyFrancia"]').html(formatNumberString(totalOrganizationsQtyFrancia, '.', ','))
					mainDomElement.find('[data-type="totalOrganizationsQtyOthers"]').html(formatNumberString(totalOrganizationsQtyOthers, '.', ','))

					configDonutChart(getUniqueSelector(chartElementContainer.get(0)), chartData.series, chartData.labels, chartData.colors, chartData.style, chartData.background, chartData.unitText)
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
			{
				dataType: 'totalApplicationsCallsBilateralProjectQty',
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
				dataType: 'totalApplicationsCallsFundingOpportunityQty',
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
				dataType: 'totalApprovedPublicationsQty',
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

		//Datos entrantes
		fetch(new URL('core/api/reports/get-generic-data', pcsphpGlobals.baseURL)).then((response) => response.json()).then(function (response) {
			for (const elementToFill of elementsToFill) {
				const value = elementToFill.getValueFromSource(response)
				elementToFill.fill(value)
			}
			configProjectionsDataHorizontal("#projection-charts", response.publicationsProjectionData)
		}).finally(function () {
			removeGenericLoader('getDataConfig')
		})

	}

	function configProjectionsDataHorizontal(selector, data) {

		const barColor = "var(--main-brand-color)"
		const markerColor = "#254079"
		const chartHeight = 180
		const markerStrokeWidth = 5
		const markerStrokeHeight = 12
		const legendPosition = 'top'
		const legendHorizontalAlign = 'right'
		const chartType = 'bar'
		const toolbarShow = false

		const title = data.title
		const projectionTitle = data.projectionTitle
		const firstData = data.first
		const secondData = data.second

		const options = {
			series: [
				{
					name: title,
					data: [
						{
							x: firstData.title,
							y: firstData.barValue,
							goals: [
								{
									name: projectionTitle,
									value: firstData.markerValue,
									strokeWidth: markerStrokeWidth,
									strokeHeight: markerStrokeHeight,
									strokeColor: markerColor
								}
							]
						},
						{
							x: secondData.title,
							y: secondData.barValue,
							goals: [
								{
									name: projectionTitle,
									value: secondData.markerValue,
									strokeWidth: markerStrokeWidth,
									strokeHeight: markerStrokeHeight,
									strokeColor: markerColor
								}
							]
						}
					]
				}
			],
			chart: {
				height: chartHeight,
				type: chartType,
				toolbar: {
					show: toolbarShow
				},
			},
			plotOptions: {
				bar: {
					horizontal: true,
				}
			},
			colors: [barColor],
			dataLabels: {
				formatter: function (value, options) {
					const elementData = options.w.config.series[options.seriesIndex].data[options.dataPointIndex]
					if (elementData.x == firstData.title) {
						return `${firstData.progressValue} / ${value}`
					} else if (elementData.x == secondData.title) {
						return `${secondData.progressValue} / ${value}`
					} else {
						return `${value}`
					}
				}
			},
			legend: {
				position: legendPosition,
				horizontalAlign: legendHorizontalAlign,
				show: true,
				showForSingleSeries: true,
				customLegendItems: [title, projectionTitle],
				markers: {
					fillColors: [barColor, markerColor]
				}
			}
		}

		const chart = new ApexCharts(document.querySelector(selector), options)
		chart.render()

	}

	function configDonutChart(selector, series, labels, colors, style, background, unitText = '', emptyColor = '#e0e0e0') {
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
					fontFamily: style.fontFamily,
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
			legend: {
				position: 'bottom',
				fontSize: style.fontSize,
				fontWeight: style.fontWeight,
				show: hasData,
			},
			tooltip: {
				enabled: hasData,
				y: {
					formatter: function (val) {
						console.log(val)
						return val + (unitText.length > 0 ? ` ${unitText}` : '')
					}
				}
			}
		}
		const chart = new ApexCharts(document.querySelector(selector), options)
		chart.render()
	}

})
