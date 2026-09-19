/// <reference path="../core/js/helpers.js"/>
/// <reference path="../core/js/configurations.js"/>
/**
 * Clase para evitar colisiones
 */
function CustomNamespace() {
}

/**
 * @method loader
 * 
 * @param {String} [name]
 * @param {Boolean} [on=true]
 * @param {String} [classPrefix]
 * @param {Boolean} [withProgress=false]
 * @param {Object} [options={}]
 * @description Cargador
 * @returns {void}
 */
CustomNamespace.loader = function (name = null, on = true, classPrefix = null, withProgress = false, options = {}) {
	name = typeof name == 'string' ? name : 'CustomNamespace'
	if (on === false) {
		removeGenericLoader(name, classPrefix)
	} else {
		showGenericLoader(name, classPrefix, withProgress, options)
	}

}

/**
 * @method smoothScroll
 * 
 * @param {String} [selector]
 * @returns {HTMLElement}
 */
CustomNamespace.smoothScroll = function (selector = '[data-smooth]') {

	selector = validString(selector) ? selector : '[data-smooth]'

	let trigger = $(selector)

	if (trigger.length > 0) {

		trigger.click(function (e) {

			e.preventDefault()

			let targetCode = $(e.currentTarget).data('smooth-to')

			if (validString(targetCode)) {
				let target = $(`[data-smooth-target-id="${targetCode}"]`)
				CustomNamespace.scrollTo(target)
			}


		})
	}

	function validString(str) {
		return typeof str == 'string' && str.length > 0
	}

}

/**
 * @method scrollTo
 * 
 * @param {$} target
 * @param {Number} stopOn
 * @returns {HTMLElement}
 */
CustomNamespace.scrollTo = function (target, stopOn = 0) {

	stopOn = parseFloat(stopOn)

	if (isNaN(stopOn)) {
		stopOn = 0
	}

	if (target instanceof $ && target.length > 0) {

		target = target.get(0)
		let targetOriginalDistanceTop = distanceToTop(target)

		window.scrollBy({ top: targetOriginalDistanceTop - stopOn, left: 0, behavior: 'smooth' })

		const checkIfDone = setInterval(function () {

			const atBottom = window.innerHeight + window.pageYOffset >= document.body.offsetHeight - 2
			let currentDistance = distanceToTop(target)

			if (currentDistance <= stopOn || atBottom) {
				clearInterval(checkIfDone)
			}

		}, 100);

	}

	function distanceToTop(element) {
		return Math.floor(element.getBoundingClientRect().top)
	}
}

/**
 * @method tabs
 * 
 * @param {String} [activeClass]
 * @returns {HTMLElement}
 */
CustomNamespace.tabs = function (activeClass) {

	activeClass = validString(activeClass) ? activeClass : 'primary'

	let menus = $('[data-tab-menu]')

	let isActive = function (item) {
		let value = item.attr('data-tab-active')
		return validString(value) && value == 'yes'
	}

	menus.each((index, menu) => {

		menu = $(menu)
		let name = menu.data('tab-menu')

		let itemsMenu = menu.find('[data-tab-target]')
		let contents = $(`[data-tab-content="${name}"]`)
		let itemsContent = contents.find(`> [data-tab-name]`)
		let hasActive = false

		itemsMenu.each((index, item) => {

			item = $(item)
			let target = item.data('tab-target')
			let content = contents.find(`> [data-tab-name="${target}"]`)
			let active = isActive(item)

			if (!active || hasActive) {
				content.hide()
				item.attr('data-tab-active', 'no')
				item.removeClass(activeClass)
			} else {
				hasActive = true
				item.addClass(activeClass)
			}

			item.click(function (e) {

				e.preventDefault()

				let active = isActive(item)

				if (!active) {
					itemsContent.hide(500)
					itemsMenu.removeClass(activeClass)
					itemsMenu.attr('data-tab-active', 'no')
					content.show(500, function () {
						CustomNamespace.scrollTo(itemsMenu, 50)
					})
					item.addClass(activeClass)
					item.attr('data-tab-active', 'yes')
				}


			})

		})

	})

	function validString(str) {
		return typeof str == 'string' && str.length > 0
	}

}

/**
 * @method slideshow
 * 
 * @param {String} selector
 * @param {Number} delay 0 = No se pasa automáticamente (segundos)
 * @param {function(srcDesktop:String,srcMobile:String, currentItem:HTMLElement)} onChangeImage
 * @returns {HTMLElement}
 */
CustomNamespace.slideshow = function (selector, delay = 5, onChangeImage) {

	selector = validString(selector) ? selector : '.vm-slideshow'
	delay = typeof delay == 'number' ? parseFloat(delay) : null
	delay = delay !== null && delay > 0 ? delay * 1000 : null
	onChangeImage = typeof onChangeImage == 'function' ? onChangeImage : () => { }

	/**
	 * @type {HTMLElement}
	 */
	let slideshow = document.querySelector(selector)

	if (!(slideshow instanceof HTMLElement)) {
		return null
	}

	let items = slideshow.querySelectorAll('.item')
	let navigationDots = slideshow.querySelector('.navigation-dots')
	let prev = slideshow.querySelector('.prev')
	let next = slideshow.querySelector('.next')

	let isDisplay = false

	/**
	 * @type {Number}
	 */
	let interval = null

	/**
	 * @type {NodeList}
	 */
	let dots
	/**
	 * @type {Number[]}
	 */
	let pages = []
	let totalPagesNumber = 0
	/**
	 * @type {HTMLElement}
	 */
	let activeItem = null

	let minorPage = () => pages.reduce((a, b) => (a < b ? a : b))
	let higherPage = () => pages.reduce((a, b) => (a > b ? a : b))

	items.forEach((/** @type {HTMLElement} */item) => {

		totalPagesNumber++

		setItemNumberPage(item, totalPagesNumber)
		addDotItem(item)

		if (activeItem === null) {

			item.querySelector('img').onload = (e) => {
				if (!isDisplay) {
					slideshow.style.opacity = 1
					isDisplay = true
				}
			}

			setPage(totalPagesNumber)
		}

		pages.push(parseInt(totalPagesNumber))
		pages.sort((a, b) => (a < b ? -1 : (a > b ? 1 : 0)))

		//Touch events
		swipedetect(item, function (direction, event) {
			if (direction == 'r') {
				event.preventDefault()
				prevPage()
			} else if (direction == 'l') {
				event.preventDefault()
				nextPage()
			}
		})

	})

	if (!isDisplay) {
		setTimeout(function () {
			slideshow.style.opacity = 1
			isDisplay = true
		}, 800)
	}

	if (items.length < 2) {
		navigationDots.style.display = 'none'
		prev.style.display = 'none'
		next.style.display = 'none'
	} else {
		navigationDots.style.display = 'block'
		prev.style.display = 'block'
		next.style.display = 'block'
	}

	prev.addEventListener('click', function (e) {
		e.preventDefault()
		prevPage()
	})

	next.addEventListener('click', function (e) {
		e.preventDefault()
		nextPage()
	})

	startAutoPagination()

	/**
	 * 
	 * @param {Number} page 
	 */
	function setPage(page) {

		let item = getItemByPage(page)
		let dot = getDotByPage(page)

		if (!isActiveItem(item)) {

			if (activeItem !== null) {
				let activeDot = getDotByItem(activeItem)
				activeItem.classList.remove('active')
				activeDot.classList.remove('active')
			}

			if (item !== null) {
				item.classList.add('active')
				dot.classList.add('active')
				activeItem = item
			}

			let mainImage = activeItem.querySelector('img.desktop')
			mainImage = mainImage !== null ? mainImage : activeItem.querySelector('img:not(.mobile)')
			let mobileImage = activeItem.querySelector('img.mobile')
			mobileImage = mobileImage !== null ? mobileImage : activeItem.querySelector('img:not(.desktop)')
			mobileImage = mobileImage !== null ? mobileImage : mainImage
			onChangeImage(mainImage.src, mobileImage.src, activeItem)

		}


	}

	function prevPage() {

		let currentPage = activeItem !== null ? parseInt(getItemNumberPage(activeItem)) : 0
		let first = minorPage()
		let last = higherPage()

		if (pages.length > 1) {

			if (first == currentPage) {
				setPage(last)
			} else {
				setPage(currentPage - 1)
			}

			startAutoPagination()

		}

	}

	function nextPage() {

		let currentPage = activeItem !== null ? parseInt(getItemNumberPage(activeItem)) : 0
		let first = minorPage()
		let last = higherPage()

		if (pages.length > 1) {

			if (last == currentPage) {
				setPage(first)
			} else {
				setPage(currentPage + 1)
			}

			startAutoPagination()

		}

	}

	function startAutoPagination() {

		if (delay !== null) {

			if (interval !== null) {
				clearInterval(interval)
				interval = null
			}

			interval = setInterval(function () {
				nextPage()
			}, delay)

		}

	}

	//──── Sobre dots ────────────────────────────────────────────────────────────────────────

	/**
	 * 
	 * @param {HTMLElement} item 
	 * @param {Number} page 
	 * @returns {HTMLElement}
	 */
	function addDotItem(item) {

		let dot = document.createElement('span')
		dot.classList.add('dot')

		let page = getItemNumberPage(item)
		dot.dataset.item = page

		dot.addEventListener('click', function (e) {
			e.preventDefault()
			setPage(getDotNumberPage(e.currentTarget))
		})

		navigationDots.appendChild(dot)

		dots = navigationDots.querySelectorAll('.dot')

		return item
	}

	/**
	 * 
	 * @param {Number} page 
	 * @returns {HTMLElement|null}
	 */
	function getDotByPage(page) {
		let filtered = Array.from(dots).filter(function (element) {
			return element.dataset.item == page
		})
		return filtered.length > 0 ? filtered[0] : null
	}

	/**
	 * 
	 * @param {HTMLElement} item 
	 * @returns {HTMLElement|null}
	 */
	function getDotByItem(item) {
		return getDotByPage(getItemNumberPage(item))
	}

	/**
	 * 
	 * @param {HTMLElement} dot 
	 * @returns {Number}
	 */
	function getDotNumberPage(dot) {
		return dot.dataset.item
	}

	//──── Sobre items ───────────────────────────────────────────────────────────────────────

	/**
	 * 
	 * @param {HTMLElement} item 
	 * @param {Number} page 
	 * @returns {HTMLElement}
	 */
	function setItemNumberPage(item, page) {
		item.dataset.index = page
		return item
	}

	/**
	 * 
	 * @param {HTMLElement} item 
	 * @returns {Number}
	 */
	function getItemNumberPage(item) {
		return item.dataset.index
	}

	/**
	 * 
	 * @param {Number} page 
	 * @returns {HTMLElement|null}
	 */
	function getItemByPage(page) {
		let filtered = Array.from(items).filter(function (element) {
			return element.dataset.index == page
		})
		return filtered.length > 0 ? filtered[0] : null
	}

	/**
	 * 
	 * @param {HTMLElement} item 
	 * @returns {Boolean}
	 */
	function isActiveItem(item) {
		return item.classList.contains('active')
	}

	//──── Otras funciones ───────────────────────────────────────────────────────────────────

	/**
	 * 
	 * @param {String} str 
	 * @returns {String}
	 */
	function validString(str) {
		return typeof str == 'string' && str.length > 0
	}

}
