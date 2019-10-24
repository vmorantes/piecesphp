/**
 * @function BuiltInCategory
 * @param {OptionsConfiguration} options
 */
function BuiltInCategory(options) {
	/**
	 * @typedef OptionsConfiguration
	 * @property {String|URL} requestURL
	 * @property {function(Object):HTMLElement|$} onDraw Recibe el item actual por parámetro, se usa para insertar el elemento en el DOM debe devolver un HTMLElement o un objeto JQuery ($)
	 * @property {function(Object)} onEmpty Recibe el contenedor asignado
	 * @property {Number} [page=1]
	 * @property {Number} [perPage=5]
	 * @property {String} [containerSelector=[built-in-categories-items-js]]
	 * @property {String} [loadMoreTriggerSelector=[built-in-categories-load-more-js]]
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
	let containerSelector = '[built-in-categories-items-js]'
	/**
	 * @property {String} loadMoreTriggerSelector
	 */
	let loadMoreTriggerSelector = '[built-in-categories-load-more-js]'
	/**
	 * @property {String|URL} requestURL
	 */
	let requestURL = ''

	/**
	 * @property {function(Object):HTMLElement|$}  onDraw
	 */
	let onDraw
	/**
	 * @property {function(Object)}  onEmpty
	 */
	let onEmpty
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
			errorMessage(_i18n('articles', 'Error'), _i18n('articles', 'Ha ocurrido un error al cargar las categorías.'))

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

		if (typeof options.onDraw == 'function') {
			onDraw = options.onDraw
		} else {
			onDraw = createItem
		}

		if (typeof options.onDraw == 'function') {
			onEmpty = options.onEmpty
		} else {
			onEmpty = function () {
			}
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

		itemHTML.append(`<div>${_i18n('articles', 'Nombre')}: ${data.name}</div>`)
		itemHTML.append(`<div>${_i18n('articles', 'Descripción')}: ${data.description}</div>`)
		itemHTML.append(`<div><a href="${data.link}">${_i18n('articles', 'URL')}</a></div>`)

		return $(itemHTML)

	}

	/**
	 * @method loadItems
	 * @returns {Promise}
	 */
	this.loadItems = function () {

		return new Promise(function (resolve, reject) {

			let loaderName = 'BUILT_IN_CATEGORIES'

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

				if (currentPage == totalPages || totalRecords == 0) {
					loadMoreTrigger.remove()
				}

				if (totalRecords == 0) {
					onEmpty(container)
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

				errorMessage(_i18n('articles', 'Error'), _i18n('articles', 'Ha ocurrido un error al cargar las categorías.'))
				console.log(error)

			}).always(function () {

				removeGenericLoader(loaderName)
				loadMoreTrigger.attr('disabled', false)

			})

		})

	}

	return this
}
