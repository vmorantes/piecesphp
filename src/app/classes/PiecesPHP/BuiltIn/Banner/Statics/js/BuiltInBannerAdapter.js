/// <reference path="../../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../../statics/core/js/helpers.js" />
/**
 * @param {OptionsConfiguration} options
 * @returns {BuiltInBannerAdapter}
 */
function BuiltInBannerAdapter(options) {
	/**
	 * @typedef OptionsConfiguration
	 * @property {String|URL} requestURL
	 * @property {function(Object, HTMLElement): (HTMLElement|$)} onDraw Recibe el item actual por parámetro, se usa para insertar el elemento en el DOM debe devolver un HTMLElement o un objeto JQuery ($)
	 * @property {function(Object)} onEmpty Recibe el contenedor asignado
	 * @property {Number} [page=1]
	 * @property {Number} [perPage=5]
	 * @property {String} [containerSelector=[built-in-banner-js]]
	 */

	/** @constant {string} */
	const langGroup = 'built-in-banner-lang'

	/** @constant {string} */
	const LOADER_NAME = 'BuiltInBannerAdapter'

	/**  @property {BuiltInBannerAdapter} */
	let instance = this

	/** @property {Number} */
	let page = 1
	/** @property {Number} */
	let perPage = 5
	/** @property {Number} */
	let nextPage = 1
	/** @property {Number} */
	let prevPage = 1
	/** @property {String} */
	let containerSelector = '[built-in-banner-js]'
	/** @property {String|URL} */
	let requestURL = ''

	/** @property {function(Object, HTMLElement): (HTMLElement|$)} */
	let onDraw
	/** @property {function(Object)} */
	let onEmpty
	/** @property {$} */
	let container
	/** @property {Boolean} */
	let firstLoad = false

	init(options)

	/**
	 * @function init
	 * @param {OptionsConfiguration} options
	 */
	function init(options) {

		registerDynamicLocalizationMessages(langGroup)

		try {

			processOptions(options)

		} catch (error) {

			console.error(error)
			errorMessage(_i18n(langGroup, 'Error'), _i18n(langGroup, 'Ha ocurrido un error al cargar los elementos.'))

		}

	}

	/**
	 * @method loadItems
	 * @returns {Promise}
	 */
	this.loadItems = function () {

		return new Promise(function (resolve, reject) {

			requestURL.searchParams.set('paginate', 'yes')
			requestURL.searchParams.set('page', page)
			requestURL.searchParams.set('per_page', perPage)

			showGenericLoader(LOADER_NAME)
			firstLoad = true

			let request = getRequest(requestURL)

			request.done(function (res) {

				let elements = res.elements
				let parsedElements = res.parsedElements
				let totalElements = res.totalElements
				let isFinal = res.isFinal
				page = res.page
				nextPage = res.nextPage
				prevPage = res.prevPage
				perPage = res.perPage

				if (totalElements == 0) {
					onEmpty(container)
				}

				let items = []
				for (let index in parsedElements) {

					let element = elements[index]
					let parsedElement = parsedElements[index]
					let item = onDraw(element, createItem(parsedElement))

					if (item instanceof HTMLElement) {
						item = $(item)
					} else if (!(item instanceof $)) {
						item = createItem(parsedElement)
					}

					item.hide()
					items.push(item)
					container.append(item)

				}

				items.map(e => $(e).show(500))

				resolve(res)

			}).fail(function (error) {

				errorMessage(_i18n(langGroup, 'Error'), _i18n(langGroup, 'Ha ocurrido un error al cargar los elementos.'))
				console.log(error)

			}).always(function () {

				removeGenericLoader(LOADER_NAME)

			})

		})

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

		if (typeof options.requestURL == 'string') {
			requestURL = options.requestURL
		}

		if (typeof options.onDraw == 'function') {
			onDraw = options.onDraw
		} else {
			onDraw = createItem
		}

		if (typeof options.onEmpty == 'function') {
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

	}

	/**
	 * @function createItem
	 * @param {Object} data 
	 * @returns {HTMLElement}
	 */
	function createItem(data) {
		return $(data).get(0)
	}

	return this
}
