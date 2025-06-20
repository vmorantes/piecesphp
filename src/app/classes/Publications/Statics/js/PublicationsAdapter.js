/// <reference path="../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../statics/core/js/helpers.js" />
/**
 * @param {OptionsConfiguration} options
 * @returns {PublicationsAdapter}
 */
function PublicationsAdapter(options) {
	/**
	 * @typedef OptionsConfiguration
	 * @property {String|URL} requestURL
	 * @property {function(Object, HTMLElement, $):HTMLElement|$} onDraw Recibe el item actual por parámetro, se usa para insertar el elemento en el DOM debe devolver un HTMLElement o un objeto JQuery ($)
	 * @property {function(Object)} onEmpty Recibe el contenedor asignado
	 * @property {Number} [page=1]
	 * @property {Number} [perPage=5]
	 * @property {String} [containerSelector=[publications-js]]
	 * @property {String} [loadMoreTriggerSelector=[publications-load-more-js]]
	 * @property {Boolean} [scrollToOnLoadMore=[false]]
	 */

	/** @constant {string} */
	const langGroup = 'PublicationsAdapter'

	/** @constant {string} */
	const LOADER_NAME = 'PublicationsAdapter'

	/**  @property {PublicationsAdapter} */
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
	let containerSelector = '[publications-js]'
	/** @property {String} */
	let loadMoreTriggerSelector = '[publications-load-more-js]'
	/** @property {String|URL} */
	let requestURL = ''

	/** @property {function(Object, HTMLElement, $):HTMLElement|$} */
	let onDraw
	/** @property {function(Object)} */
	let onEmpty
	/** @property {$} */
	let container
	/** @property {$} */
	let loadMoreTrigger
	/** @property {Boolean} */
	let firstLoad = false
	/** @property {Number} */
	let maxIndex = -1
	/** @property {Boolean} */
	let scrollToOnLoadMore = false
	/** @property {$[]} */
	let addedItems = []

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
				e.preventDefault()
				page = nextPage
				instance.loadItems(true, scrollToOnLoadMore)
			})

		} catch (error) {

			console.error(error)
			errorMessage(_i18n(langGroup, 'Error'), _i18n(langGroup, 'Ha ocurrido un error al cargar los elementos.'))

		}

	}

	/**
	 * Reinicia la consulta
	 * @param {URL|String} url 
	 * @returns {PublicationsAdapter}
	 */
	this.reload = function (url) {
		requestURL = typeof url == 'string' ? new URL(url) : (
			url instanceof URL ?
				url :
				requestURL
		)
		options.requestURL = requestURL
		addedItems.map((e) => e.remove())
		addedItems = []
		init(options)
		return this
	}

	/**
	 * @method loadItems
	 * @param {Boolean} [append=true] Agrega los elementos al contenedor
	 * @param {Boolean} [scrollToFinal=false]
	 * @returns {Promise}
	 */
	this.loadItems = function (append = true, scrollToFinal = false) {

		return new Promise(function (resolve, reject) {

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
				let loadMoreTriggerIsInsideContainer = loadMoreTrigger.length > 0 && container.find(loadMoreTrigger).length > 0
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
					let item = onDraw(element, createItem(parsedElement), container)

					if (item instanceof HTMLElement) {
						item = $(item)
					} else if (!(item instanceof $)) {
						item = createItem(parsedElement)
					}

					item.css('display', 'none')
					item.css('opacity', 0)
					maxIndex++
					item.attr('data-index', maxIndex)
					if (append) {
						items.push(item)
						if (loadMoreTriggerIsInsideContainer) {
							item.insertBefore(loadMoreTrigger)
						} else {
							container.append(item)
						}
					}

				}

				const showPromises = []
				items.map(e => {
					const $e = $(e)
					const resolveElement = {
						index: $e.data('index'),
						element: $e,
					}
					addedItems.push($e)
					$e.css('display', '')
					const lastPromise = showPromises.length > 0 ? showPromises[showPromises.length - 1] : null
					const showElement = function () {
						showPromises.push(new Promise((showResolve) => {
							$e.animate({ opacity: 1 }, 500, () => {
								showResolve(resolveElement)
							})
						}))
					}

					if (lastPromise == null) {
						showElement()
					} else {
						lastPromise.then(function () {
							showElement()
						})
					}

				})

				Promise.all(showPromises).then(function (elements) {
					const lastElement = elements.find((element) => element.index == maxIndex)
					if (scrollToFinal) {
						getScrollableParent(lastElement.element.get(0)).scrollTo({
							top: lastElement.element.offset().top + 100,
							behavior: 'smooth'
						})
					}
				})

				resolve({
					response: res,
					container: container,
				})

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
	 * @function onDraw
	 * @param {function(Object, HTMLElement, $):HTMLElement|$} callback
	 */
	this.onDraw = function (callback) {
		if (typeof callback == 'function') {
			onDraw = callback
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

		if (typeof options.onEmpty == 'function') {
			onEmpty = options.onEmpty
		} else {
			onEmpty = function () {
			}
		}

		if (typeof options.scrollToOnLoadMore == 'boolean') {
			scrollToOnLoadMore = options.scrollToOnLoadMore
		} else {
			scrollToOnLoadMore = false
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
