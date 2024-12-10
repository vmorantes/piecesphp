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
		]
		const getRandomInt = (min, max) => {
			min = Math.ceil(min);
			max = Math.floor(max);
			return Math.floor(Math.random() * (max - min + 1) + min);
		}

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
