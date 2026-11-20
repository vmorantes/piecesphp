window.addEventListener('load', function () {

	let langGroup = 'reportsManage'

	//Todas las cifras del informe, con los separadores del idioma de la página, como la dona («1248» y «49.979.170» en es).
	const numberFormat = new Intl.NumberFormat(document.documentElement.lang || undefined)
	const formatNumber = (value) => numberFormat.format(Number(value) || 0)

	showGenericLoader(langGroup)

	getDataConfig()

	removeGenericLoader(langGroup)

	function getDataConfig() {

		showGenericLoader('getDataConfig')

		const elementsToFill = [
			{
				dataType: 'peopleData',
				fill: function (value) {
					fillCountries(this.dataType, value)
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
			{
				dataType: 'organizationsData',
				fill: function (value) {
					fillCountries(this.dataType, value)
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
					domElement.text(formatNumber(value))
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
					domElement.text(formatNumber(value))
				},
				getValueFromSource: function (source) {
					const value = source[this.dataType]
					return value
				},
			},
		]

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
	 * La dona y su leyenda de una tarjeta por países (chart-card.php, `proportion`): el total en el centro y, por fila, el
	 * nombre (el del JSON), la cifra y su porcentaje. Un país que no esté no se pinta: ni su fila ni su porción.
	 * @param {string} dataType
	 * @param {{total:number,byCountry:{country:number,name:string,qty:number}[],others:number}} value
	 */
	function fillCountries(dataType, value) {
		const card = document.querySelector(`[data-type="${dataType}"]`)
		if (card === null) {
			return
		}
		const total = Number(value.total) || 0
		//Con los separadores del idioma de la página: «12.480 · 71,9 %» en es y «12,480 · 71.9%» en en (en es, 1248 va sin punto).
		const language = document.documentElement.lang || undefined
		const percentFormat = new Intl.NumberFormat(language, { style: 'percent', minimumFractionDigits: 1, maximumFractionDigits: 1 })
		const percent = (qty) => percentFormat.format(total > 0 ? qty / total : 0)
		const items = []
		const fillRow = function (row, name, qty) {
			if (row === null) {
				return
			}
			if (name !== null) {
				row.querySelector('[data-name]').textContent = name
			}
			row.querySelector('[data-value]').textContent = numberFormat.format(qty)
			row.querySelector('[data-percent]').textContent = percent(qty)
			row.style.removeProperty('display')
			items.push({ name: row.querySelector('[data-name]').textContent, value: qty, color: row.dataset.chartColor })
		}
		for (const position of [1, 2]) {
			const row = card.querySelector(`[data-type="${dataType}Country${position}"]`)
			const country = value.byCountry[position - 1]
			if (country === undefined) {
				if (row !== null) {
					//`ui flex` pone display: flex, que gana al atributo hidden.
					row.style.display = 'none'
				}
				continue
			}
			fillRow(row, country.name, Number(country.qty) || 0)
		}
		fillRow(card.querySelector(`[data-type="${dataType}Others"]`), null, Number(value.others) || 0)
		const totalElement = card.querySelector(`[data-type="${dataType}Total"]`)
		if (totalElement !== null) {
			totalElement.textContent = numberFormat.format(total)
		}
		drawStatisticsDonut(card.querySelector('.ui.chart > .canvas'), items)
	}

})
