/// <reference path="../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../statics/core/js/helpers.js" />
/**
 * @param {OptionsConfiguration} options
 * @returns {NewsAdapter}
 */
function NewsAdapter(options) {
	/**
	 * @typedef OptionsConfiguration
	 * @property {String|URL} requestURL
	 * @property {function(Object):HTMLElement|$} onDraw Recibe el item actual por parámetro, se usa para insertar el elemento en el DOM debe devolver un HTMLElement o un objeto JQuery ($)
	 * @property {function(Object)} onEmpty Recibe el contenedor asignado
	 * @property {Number} [page=1]
	 * @property {Number} [perPage=5]
	 * @property {String} [containerSelector=[news-js]]
	 * @property {String} [loadMoreTriggerSelector=[news-load-more-js]]
	 */

	/** @constant {string} */
	const langGroup = 'NewsAdapter'

	/** @constant {string} */
	const LOADER_NAME = 'NewsAdapter'

	/**  @property {NewsAdapter} */
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
	let containerSelector = '[news-js]'
	/** @property {String} */
	let loadMoreTriggerSelector = '[news-load-more-js]'
	/** @property {String|URL} */
	let requestURL = ''

	/** @property {function(Object, HTMLElement):HTMLElement|$} */
	let onDraw
	/** @property {function(Object)} */
	let onEmpty
	/** @property {$} */
	let container
	/** @property {$} */
	let loadMoreTrigger
	/** @property {Boolean} */
	let firstLoad = false

	init(options)

	/**
	 * @function init
	 * @param {OptionsConfiguration} options
	 */
	function init(options) {

		registerDynamicMessages(langGroup)

		try {

			processOptions(options)

			//Acciones inciales
			loadMoreTrigger.off('click')
			loadMoreTrigger.on('click', function (e) {
				page = nextPage
				instance.loadItems()
			})

		} catch (error) {

			console.error('NewsAdapter-Error-Start')
			console.error(_i18n(langGroup, 'Error'), _i18n(langGroup, 'Ha ocurrido un error al cargar los elementos.'))
			console.error(error)
			console.error('NewsAdapter-Error-End')

		}

	}

	/**
	 * @method loadItems
	 * @returns {Promise}
	 */
	this.loadItems = function () {

		return new Promise(function (resolve, reject) {

			if (requestURL.length === 0) {
				return Promise.resolve()
			}
			requestURL.searchParams.set('paginate', 'yes')
			requestURL.searchParams.set('page', page)
			requestURL.searchParams.set('per_page', perPage)

			showGenericLoader(LOADER_NAME)
			firstLoad = true
			loadMoreTrigger.attr('disabled', true)

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

				if (isFinal) {
					loadMoreTrigger.hide()
				} else {
					if (!loadMoreTrigger.is(':visible')) {
						loadMoreTrigger.show()
					}
				}

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
				loadMoreTrigger.attr('disabled', false)

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
		loadMoreTrigger = $(loadMoreTriggerSelector)

	}

	/**
	 * @function createItem
	 * @param {Object} data 
	 * @returns {$}
	 */
	function createItem(data) {
		return $(data)
	}

	/**
	 * @param {String} name 
	 * @returns {void}
	 */
	function registerDynamicMessages(name) {

		if (typeof pcsphpGlobals != 'object') {
			window.pcsphpGlobals = {}
		}
		if (typeof pcsphpGlobals.messages != 'object') {
			pcsphpGlobals.messages = {}
		}
		if (typeof pcsphpGlobals.messages.es != 'object') {
			pcsphpGlobals.messages.es = {}
		}
		if (typeof pcsphpGlobals.messages.en != 'object') {
			pcsphpGlobals.messages.en = {}
		}

		let es = {
			'Error': 'Error',
			'Ha ocurrido un error al cargar los elementos.': 'Ha ocurrido un error al cargar los elementos.',
		}

		let en = {
			'Error': 'Error',
			'Ha ocurrido un error al cargar los elementos.': 'An error occurred while loading the items.',
		}

		for (let i in es) {
			if (typeof pcsphpGlobals.messages.es[name] == 'undefined') pcsphpGlobals.messages.es[name] = {}
			pcsphpGlobals.messages.es[name][i] = es[i]
		}

		for (let i in en) {
			if (typeof pcsphpGlobals.messages.en[name] == 'undefined') pcsphpGlobals.messages.en[name] = {}
			pcsphpGlobals.messages.en[name][i] = en[i]
		}

	}

	return this
}
