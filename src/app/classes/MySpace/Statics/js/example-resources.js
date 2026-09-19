/// <reference path="../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	let langGroup = 'exampleResources'

	showGenericLoader(langGroup)

	//Tabs
	const tabs = $('.tabs-controls [data-tab]').tab({})

	getDataConfig()
	createQRs()
	totp()
	dialogPcs()

	removeGenericLoader(langGroup)

	function configProjectionsDataHorizontal(selector, data) {

		const barColor = "var(--main-brand-color)"
		const markerColor = "#6d6d6d"
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
							y: firstData.progressValue,
							goals: [
								{
									name: projectionTitle,
									value: firstData.projectionValue,
									strokeWidth: markerStrokeWidth,
									strokeHeight: markerStrokeHeight,
									strokeColor: markerColor
								}
							]
						},
						{
							x: secondData.title,
							y: secondData.progressValue,
							goals: [
								{
									name: projectionTitle,
									value: secondData.projectionValue,
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
						return `${value} / ${firstData.totalValue}`
					} else if (elementData.x == secondData.title) {
						return `${value} / ${secondData.totalValue}`
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

	function configDetailedAdvanceLines(selector, seriesConfig, closeData, rangeData) {

		//Tratamiento de datos para conversión de fecha
		for (const index in seriesConfig) {
			const serie = seriesConfig[index]
			const serieData = serie.data
			for (const indexData in serieData) {
				const elementData = serieData[indexData]
				elementData.x = new Date(elementData.x).getTime()
				serieData[indexData] = elementData
			}
			seriesConfig[index] = serie
		}

		//Configuración de la gráfica
		const options = {
			chart: {
				type: 'line',
				height: 350,
			},
			series: seriesConfig,
			xaxis: {
				type: "datetime",
				tickAmount: rangeData.points,
				min: new Date(rangeData.start).getTime(),
				max: new Date(rangeData.end).getTime(),
				labels: {
					formatter: function (value) {
						return new Date(value).toLocaleDateString(pcsphpGlobals.lang)
					}
				},
			},
			colors: ['#D90429', '#AFAFAF'], //Color de línea: Realizadas|Proyectadas
			stroke: {
				width: 5,
				curve: ['smooth', 'straight'] //Suavizado de líne: Realizadas-suave|Proyectadas-lineal
			},
			markers: {
				size: [6, 6], //Puntos: Realizadas|Proyectadas
				colors: ['#D90429', '#AFAFAF'], //Color de puntos: Realizadas|Proyectadas
				strokeColors: '#fff',
				strokeWidth: 2,
			},
			tooltip: {
				enabled: true,
				custom: function ({
					series,
					seriesIndex: serieIndex,
					dataPointIndex,
					w,
				}) {

					const serieProgressIndex = 0
					const serieProjectionIndex = 1
					const serieProgress = seriesConfig[serieProgressIndex]
					const serieProjection = seriesConfig[serieProjectionIndex]

					const serieConfig = serieIndex == serieProgressIndex ? serieProgress : serieProjection
					const anotherSerieConfig = serieIndex == serieProgressIndex ? serieProjection : serieProgress

					const serieData = serieConfig.data
					const anotherSerieData = anotherSerieConfig.data

					const serieDataElement = serieData[dataPointIndex]
					const anotherSerieDataElement = anotherSerieData[dataPointIndex]

					const timestamp = serieDataElement.x
					const value = serieDataElement.y
					const date = new Date()
					const anotherTimestamp = anotherSerieDataElement.x
					const anotherValue = anotherSerieDataElement.y
					const anotherDate = new Date()

					//Punto destacado
					const lang = pcsphpGlobals.lang
					date.setTime(timestamp)
					const day = new Intl.DateTimeFormat(lang, {
						day: 'numeric', // Día (1)
					}).format(date)
					const month = new Intl.DateTimeFormat(lang, {
						month: 'long',  // Mes en formato completo (Enero)
					}).format(date)
					const monthCapitalized = month.charAt(0).toUpperCase() + month.slice(1)
					const year = new Intl.DateTimeFormat(lang, {
						year: 'numeric' // Año (2025)
					}).format(date)
					const formattedDate = `${monthCapitalized} ${day}, ${year}`

					if (serieIndex === 0) {//Avance

						const progressPercent = anotherValue > 0 ? (100 / anotherValue * value).toFixed(2) : 0
						let progressPercentDifferent = 0
						const comparativeTextMore = _i18n(langGroup, '{number}% más que la proyección')
						const comparativeTextLess = _i18n(langGroup, '{number}% menos que la proyección')
						const comparativeTextEqual = _i18n(langGroup, 'Igual a la proyección')
						const comparativeTextBitMore = _i18n(langGroup, 'Ligeramente más que la proyección')
						const comparativeTextBitLess = _i18n(langGroup, 'Ligeramente menos que la proyección')
						let comparativeText = comparativeTextEqual

						if (value > anotherValue) {
							comparativeText = comparativeTextMore
							progressPercentDifferent = parseFloat(progressPercent - 100)
							if (progressPercentDifferent < 0.01) {
								comparativeText = comparativeTextBitMore
							}
						} else if (value < anotherValue) {
							comparativeText = comparativeTextLess
							progressPercentDifferent = parseFloat(100 - progressPercent)
							if (progressPercentDifferent < 0.01) {
								progressPercentDifferent = 0
								comparativeText = comparativeTextBitLess
							}
						}

						return `<div class="mark-point-line-apex">
										<span class="tag">${formattedDate}</span>
										<span class="value">${value} ${_i18n(langGroup, 'Encuestas')}</span>
										<span value="description">${comparativeText.replace('{number}', progressPercentDifferent.toFixed(2))}</span>
									</div>`
					} else if (serieIndex === 1) {//Proyección
						return `<div class="mark-point-line-apex">
										<span class="tag">${formattedDate}</span>
										<span class="value alt">${value} ${_i18n(langGroup, 'Proyectadas')}</span><br>
									</div>`
					}

					return ''
				}
			},
			annotations: {
				xaxis: [
					{
						x: new Date(closeData.date).getTime(),
						strokeDashArray: 4,
						borderColor: '#F4D03F',
						label: {
							style: {
								color: '#fff',
								background: '#F4D03F',
							},
							text: closeData.title,
						}
					}
				],
			},
		}

		const chart = new ApexCharts(document.querySelector(selector), options)
		chart.render()

	}

	function getDataConfig() {

		showGenericLoader('getDataConfig')

		const elementsToFill = [
			{
				dataType: 'dataElement1',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(value)
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
			{
				dataType: 'dataElement2',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(value)
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
			{
				dataType: 'dataElement3',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(value)
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
			{
				dataType: 'dataElement4',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(value)
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
			{
				dataType: 'dataElement5',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.attr('style', `--progress: ${value}`)
				},
				getValueFromSource: function (source) {
					let value = source['dataElement5_6_7_8']
					if (typeof value !== 'undefined') {
						value = value.getPercent()
					} else {
						value = 0
					}
					return value
				},
			},
			{
				dataType: 'dataElement6',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(`${value}%`)
				},
				getValueFromSource: function (source) {
					let value = source['dataElement5_6_7_8']
					if (typeof value !== 'undefined') {
						value = value.getPercent()
					} else {
						value = 0
					}
					return value
				},
			},
			{
				dataType: 'dataElement7',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(`${value}`)
				},
				getValueFromSource: function (source) {
					let value = source['dataElement5_6_7_8']
					if (typeof value !== 'undefined') {
						value = value.a
					} else {
						value = 0
					}
					return value
				},
			},
			{
				dataType: 'dataElement8',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(`${value}`)
				},
				getValueFromSource: function (source) {
					let value = source['dataElement5_6_7_8']
					if (typeof value !== 'undefined') {
						value = value.b
					} else {
						value = 0
					}
					return value
				},
			},
			{
				dataType: 'dataElement9',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(value)
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
			{
				dataType: 'dataElement10',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(value)
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
			{
				dataType: 'dataElement11',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(value)
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
			{
				dataType: 'dataElement12',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.attr('style', `--progress: ${value}`)
				},
				getValueFromSource: function (source) {
					let value = source['dataElement12_13_14_15']
					if (typeof value !== 'undefined') {
						value = value.getPercent()
					} else {
						value = 0
					}
					return value
				},
			},
			{
				dataType: 'dataElement13',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(`${value}%`)
				},
				getValueFromSource: function (source) {
					let value = source['dataElement12_13_14_15']
					if (typeof value !== 'undefined') {
						value = value.getPercent()
					} else {
						value = 0
					}
					return value
				},
			},
			{
				dataType: 'dataElement14',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(`${value}`)
				},
				getValueFromSource: function (source) {
					let value = source['dataElement12_13_14_15']
					if (typeof value !== 'undefined') {
						value = value.a
					} else {
						value = 0
					}
					return value
				},
			},
			{
				dataType: 'dataElement15',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(`${value}`)
				},
				getValueFromSource: function (source) {
					let value = source['dataElement12_13_14_15']
					if (typeof value !== 'undefined') {
						value = value.b
					} else {
						value = 0
					}
					return value
				},
			},
			{
				dataType: 'dataElement16',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(`${value}`)
				},
				getValueFromSource: function (source) {
					let value = source['dataElement16_17']
					if (typeof value !== 'undefined') {
						value = value.a
					} else {
						value = 0
					}
					return value
				},
			},
			{
				dataType: 'dataElement17',
				fill: function (value) {
					const dataType = this.dataType
					const domElement = $(`[data-type="${dataType}"]`)
					domElement.html(`${value}`)
				},
				getValueFromSource: function (source) {
					let value = source['dataElement16_17']
					if (typeof value !== 'undefined') {
						value = value.b
					} else {
						value = 0
					}
					return value
				},
			},
		]
		const getRandomInt = (min, max) => {
			min = Math.ceil(min);
			max = Math.floor(max);
			return Math.floor(Math.random() * (max - min + 1) + min);
		}

		//Configurar tooltip en cards
		$('.card-statistic .toolbar .help:not(.icon)').map(function (index, element) {
			let tooltipText = element.dataset.tooltip
			tooltipText = typeof tooltipText == 'string' && tooltipText.length > 0 ? tooltipText : null
			if (tooltipText == null) {
				element.remove()
			}
		})

		//EJEMPLO DE DATOS ENTRANTES
		const maxForPercents = getRandomInt(0, 1000)
		const secondaryForPercents = getRandomInt(0, maxForPercents)
		const parsedReponse = {
			dataElement1: getRandomInt(0, 9999),
			dataElement2: getRandomInt(0, 9999),
			dataElement3: getRandomInt(0, 9999),
			dataElement4: getRandomInt(0, 9999),
			dataElement5: getRandomInt(0, 9999),
			dataElement5_6_7_8: {
				a: secondaryForPercents,
				b: maxForPercents,
				getPercent: function () {
					const total = this.a + this.b
					const percent = (total > 0 ? 100 / total * this.a : 0).toFixed(2)
					return percent
				},
			},
			dataElement9: getRandomInt(0, 9999),
			dataElement10: getRandomInt(0, 9999),
			dataElement11: getRandomInt(0, 9999),
			dataElement12_13_14_15: {
				a: secondaryForPercents,
				b: maxForPercents,
				getPercent: function () {
					const total = this.a + this.b
					const percent = (total > 0 ? 100 / total * this.a : 0).toFixed(2)
					return percent
				},
			},
			dataElement16_17: {
				a: getRandomInt(0, 9999),
				b: getRandomInt(0, 9999),
			},
		}
		//Generales
		for (const elementToFill of elementsToFill) {
			const value = elementToFill.getValueFromSource(parsedReponse)
			elementToFill.fill(value)
		}
		//Gráficos de barras
		const firstDataTotal = getRandomInt(0, 3500)
		const firstDataProgress = getRandomInt(0, firstDataTotal)
		const firstDataProjection = getRandomInt(0, firstDataProgress)
		const firstGroupData = {
			progressValue: firstDataProgress,
			totalValue: firstDataTotal,
			projectionValue: firstDataProjection,
		}
		const secondDataTotal = getRandomInt(0, 3500)
		const secondDataProgress = getRandomInt(0, secondDataTotal)
		const secondDataProjection = getRandomInt(0, secondDataProgress)
		const secondGroupData = {
			progressValue: secondDataProgress,
			totalValue: secondDataTotal,
			projectionValue: secondDataProjection,
		}
		configProjectionsDataHorizontal("#projection-charts", {
			title: 'Progreso',
			projectionTitle: 'Esperado',
			first: {
				title: 'Datos #1',
				progressValue: firstGroupData.progressValue,
				totalValue: firstGroupData.totalValue,
				projectionValue: firstGroupData.projectionValue,
			},
			second: {
				title: 'Datos #2',
				progressValue: secondGroupData.progressValue,
				totalValue: secondGroupData.totalValue,
				projectionValue: secondGroupData.projectionValue,
			},
		})

		const projectionData = {
			"series": [
				{
					"name": "Encuestas realizadas",
					"data": [
						{
							"x": "2025-01-22",
							"y": 54
						},
						{
							"x": "2025-02-06",
							"y": 100
						},
						{
							"x": "2025-02-21",
							"y": 354
						},
						{
							"x": "2025-03-08",
							"y": 492
						},
						{
							"x": "2025-03-23",
							"y": 545
						},
						{
							"x": "2025-04-07",
							"y": 687
						},
						{
							"x": "2025-04-22",
							"y": 781
						},
						{
							"x": "2025-05-07",
							"y": 984
						},
						{
							"x": "2025-05-22",
							"y": 1095
						},
						{
							"x": "2025-06-06",
							"y": 1111
						},
						{
							"x": "2025-06-21",
							"y": 1307
						},
						{
							"x": "2025-07-06",
							"y": 1397
						},
						{
							"x": "2025-07-21",
							"y": 1397
						},
						{
							"x": "2025-08-05",
							"y": 1465
						},
						{
							"x": "2025-08-20",
							"y": 1662
						},
						{
							"x": "2025-09-04",
							"y": 1725
						},
						{
							"x": "2025-09-19",
							"y": 1872
						},
						{
							"x": "2025-10-04",
							"y": 1930
						},
						{
							"x": "2025-10-19",
							"y": 2067
						},
						{
							"x": "2025-11-03",
							"y": 2278
						},
						{
							"x": "2025-11-18",
							"y": 2359
						},
						{
							"x": "2025-12-03",
							"y": 2395
						},
						{
							"x": "2025-12-16",
							"y": 2534
						},
						{
							"x": "2026-01-01",
							"y": 2550
						}
					]
				},
				{
					"name": "Encuestas proyectadas",
					"data": [
						{
							"x": "2025-01-22",
							"y": 112
						},
						{
							"x": "2025-02-06",
							"y": 224
						},
						{
							"x": "2025-02-21",
							"y": 336
						},
						{
							"x": "2025-03-08",
							"y": 448
						},
						{
							"x": "2025-03-23",
							"y": 560
						},
						{
							"x": "2025-04-07",
							"y": 672
						},
						{
							"x": "2025-04-22",
							"y": 784
						},
						{
							"x": "2025-05-07",
							"y": 896
						},
						{
							"x": "2025-05-22",
							"y": 1008
						},
						{
							"x": "2025-06-06",
							"y": 1120
						},
						{
							"x": "2025-06-21",
							"y": 1232
						},
						{
							"x": "2025-07-06",
							"y": 1344
						},
						{
							"x": "2025-07-21",
							"y": 1456
						},
						{
							"x": "2025-08-05",
							"y": 1568
						},
						{
							"x": "2025-08-20",
							"y": 1680
						},
						{
							"x": "2025-09-04",
							"y": 1792
						},
						{
							"x": "2025-09-19",
							"y": 1904
						},
						{
							"x": "2025-10-04",
							"y": 2016
						},
						{
							"x": "2025-10-19",
							"y": 2128
						},
						{
							"x": "2025-11-03",
							"y": 2240
						},
						{
							"x": "2025-11-18",
							"y": 2352
						},
						{
							"x": "2025-12-03",
							"y": 2464
						},
						{
							"x": "2025-12-16",
							"y": 2567
						},
						{
							"x": "2026-01-01",
							"y": 2567
						}
					]
				}
			],
			"closeData": {
				"title": "Cierre",
				"date": "2025-12-16 00:00:00"
			},
			"rangeData": {
				"start": "2025-01-22 00:00:00",
				"end": "2026-01-31 00:00:00",
				"points": 23
			}
		}
		configDetailedAdvanceLines("#projection-charts-lines", projectionData.series, projectionData.closeData, projectionData.rangeData)

		removeGenericLoader('getDataConfig')

	}

	function createQRs() {
		const qrContainers = Array.from($('[qr-container]'))
		for (const qrContainer of qrContainers) {
			const qrData = qrContainer.dataset.value
			new QRCode(qrContainer, {
				text: qrData,
				width: 180,
				height: 180,
				colorDark: "#000000",
				colorLight: "#FFFFFF",
				correctLevel: QRCode.CorrectLevel.H,
			})
		}
	}

	function totp() {
		genericFormHandler("form[totp]")

		const toptContainer = $('[totp-code]')
		const getTOTPCodeURL = toptContainer.data('url')

		if (typeof getTOTPCodeURL == 'string' && getTOTPCodeURL.trim().length > 0) {
			const getCode = function () {
				fetch(getTOTPCodeURL).then(res => res.json()).then(function (res) {
					const code = res.values.code
					toptContainer.html(code)
				})
			}
			getCode()
			setInterval(getCode, 5000)
		}
	}

	function dialogPcs() {
		const trigger = $('[trigger-add-dialog-pcs]')
		let dialog = new DialogPCS('.dialog-pcs.a', '.module-view-container')
		trigger.on('click', function (e) {
			e.preventDefault()
			if (dialog.isOpen()) {
				dialog.close()
			} else {
				dialog.open()
			}
		})
	}

})
