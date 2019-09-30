/**
 * @function BuiltInArticle
 * @param {OptionsConfiguration} options
 */
function BuiltInArticle(options) {
	/**
	 * @typedef DescriptiveDate
	 * @property {Number} day
	 * @property {String} dayName
	 * @property {Number} month
	 * @property {String} monthName
	 * @property {Number} year
	 */
	/**
	 * @typedef OptionsConfiguration
	 * @property {String|URL} requestURL
	 * @property {String} singleURL Intenta remplazar {{friendly_name}}
	 * @property {Function} onDraw Recibe el item actual por parámetro, se usa para insertar el elemento en el DOM debe devolver un HTMLElement o un objeto JQuery ($)
	 * @property {Number} [page=1]
	 * @property {Number} [perPage=5]
	 * @property {String} [containerSelector=[built-in-articles-items-js]]
	 * @property {String} [loadMoreTriggerSelector=[built-in-articles-load-more-js]]
	 */

	/**
	 * @property {BuiltInArticle} instance
	 */
	let instance = this

	/**
	 * @property {Number} perPage
	 */
	let perPage = 5
	/**
	 * @property {Number} page
	 */
	let page = 1
	/**
	 * @property {String} containerSelector
	 */
	let containerSelector = '[built-in-articles-items-js]'
	/**
	 * @property {String} loadMoreTriggerSelector
	 */
	let loadMoreTriggerSelector = '[built-in-articles-load-more-js]'
	/**
	 * @property {String|URL} requestURL
	 */
	let requestURL = ''
	/**
	 * @property {String} singleURL
	 */
	let singleURL = ''

	/**
	 * @property {Function} onDraw
	 */
	let onDraw
	/**
	 * @property {$} container
	 */
	let container
	/**
	 * @property {$} loadMoreTrigger
	 */
	let loadMoreTrigger
	/**
	 * @property {Boolean} firstLoad
	 */
	let firstLoad = false

	init(options)

	/**
	 * @function init
	 * @param {OptionsConfiguration} options
	 */
	function init(options) {

		try {

			processOptions(options)

			//Acciones inciales
			loadMoreTrigger.click(function (e) {

				if (firstLoad) {
					page += 1
				}

				instance.loadItems()

			})

		} catch (error) {

			console.error(error)
			errorMessage('Error', 'Ha ocurrido un error al cargar los artículos.')

		}

	}

	/**
	 * @function validateOptions
	 * @param {OptionsConfiguration} options 
	 */
	function processOptions(options = {}) {

		//Configuración de valores
		if (typeof options != 'object') {
			options = {}
		}

		if (typeof options.perPage == 'number') {
			perPage = Math.round(options.perPage)
		}

		if (typeof options.page == 'number') {
			page = Math.round(options.page)
		}

		if (typeof options.containerSelector == 'string') {
			containerSelector = options.containerSelector
		}

		if (typeof options.loadMoreTriggerSelector == 'string') {
			loadMoreTriggerSelector = options.loadMoreTriggerSelector
		}

		if (typeof options.requestURL == 'string') {
			requestURL = options.requestURL
		}

		if (typeof options.singleURL == 'string') {
			singleURL = options.singleURL
		}

		if (typeof options.onDraw == 'function') {
			onDraw = options.onDraw
		} else {
			onDraw = createItem
		}

		//Asignación de valores

		if (typeof requestURL == 'string') {
			requestURL = new URL(requestURL)
		}

		container = $(containerSelector)
		loadMoreTrigger = $(loadMoreTriggerSelector)

	}

	/**
	 * @function createItem
	 * @param {Object} data 
	 * @returns {$}
	 */
	function createItem(data) {

		let itemHTML = $(document.createElement('div'))

		let date = data.start_date !== null ? data.start_date.date : data.created.date
		date = instance.processDate(new Date(date))

		itemHTML.append(`<div>Título: ${data.title}</div>`)
		itemHTML.append(`<div>Miniatura: <img src="${data.meta.imageThumb}" style="max-width: 500px;"/></div>`)
		itemHTML.append(`<div>Categoría: ${data.category.name}</div>`)
		itemHTML.append(`<div>Mes (número): ${date.month}</div>`)
		itemHTML.append(`<div>Mes (texto): ${date.monthName}</div>`)
		itemHTML.append(`<div>Día (número): ${date.day}</div>`)
		itemHTML.append(`<div>Día (texto): ${date.dayName}</div>`)
		itemHTML.append(`<div>Año: ${date.year}</div>`)
		itemHTML.append(`<div>Autor: ${data.author.username}</div>`)
		itemHTML.append(`<div>Visitas: ${data.meta.visits}</div>`)
		itemHTML.append(`<div><a href="${singleURL.replace('{{friendly_name}}', data.friendly_url)}">URL</a></div>`)

		return $(itemHTML)

	}

	/**
	 * @method loadItems
	 * @returns {Promise}
	 */
	this.loadItems = function () {

		return new Promise(function (resolve, reject) {

			let loaderName = 'BUILT_IN_ARTICLES'

			requestURL.searchParams.set('paginate', 'yes')
			requestURL.searchParams.set('page', page)
			requestURL.searchParams.set('per_page', perPage)

			showGenericLoader(loaderName)
			firstLoad = true
			loadMoreTrigger.attr('disabled', true)

			let request = getRequest(requestURL)

			request.done(function (res) {

				let data = res.data
				let currentPage = res.page
				let totalPages = res.pages
				let totalRecords = res.total

				if (currentPage == totalPages) {
					loadMoreTrigger.remove()
				}

				for (let element of data) {

					let item = onDraw(element)

					if (item instanceof HTMLElement) {
						item = $(item)
					} else if (!(item instanceof $)) {
						item = createItem(element)
					}

					item.hide()
					container.append(item)
					item.show(500)

				}

				resolve(res)

			}).fail(function (erro) {

				errorMessage('Error', 'Ha ocurrido un error al cargar los artículos.')
				console.log(error)

			}).always(function () {

				removeGenericLoader(loaderName)
				loadMoreTrigger.attr('disabled', false)

			})

		})

	}

	/**
	 * @method processDate
	 * @param {Date} date 
	 * @returns {DescriptiveDate} 
	 */
	this.processDate = function (date) {

		/**
		 * @property {DescriptiveDate} returnValue
		 */
		let returnValue = {}

		date = date instanceof Date ? date : new Date()

		let months = [
			'Enero',
			'Febrero',
			'Marzo',
			'Abril',
			'Mayo',
			'Junio',
			'Julio',
			'Agosto',
			'Septiembre',
			'Octubre',
			'Noviembre',
			'Diciembre',
		]

		let days = [
			'Domingo',
			'Lunes',
			'Martes',
			'Miércoles',
			'Jueves',
			'Viernes',
			'Sábado',
		]

		let day = date.getDate()
		let weekDay = days[date.getDay()]
		let month = months[date.getMonth()]
		let year = date.getFullYear()

		returnValue.day = day
		returnValue.dayName = weekDay
		returnValue.month = date.getMonth() + 1
		returnValue.monthName = month
		returnValue.year = year

		return returnValue
	}


	return this
}
