/**
 * @class DialogPCS
 * @param {String} selectorParam 
 * @param {String} parentSelectorParam 
 * @param {Boolean} resizableParam 
 */
function DialogPCS(selectorParam = '.dialog-pcs', parentSelectorParam = 'body', resizableParam = true) {

	/** @type {DialogPCS} */let instance = this

	/** @type {HTMLElement} */let dragItem = null
	/** @type {HTMLElement} */let container = null
	/** @type {HTMLElement} */let dragArea = null

	/** @type {Number} */ let currentX
	/** @type {Number} */ let currentY
	/** @type {Number} */ let initialX
	/** @type {Number} */ let initialY
	/** @type {Number} */ let xOffset = 0
	/** @type {Number} */ let yOffset = 0

	/** @type {Number} */ let initialW = 0
	/** @type {Number} */ let initialH = 0
	/** @type {Number} */ let changeY = 0
	/** @type {Number} */ let changeX = 0

	/** @type {Boolean} */  let active = false
	/** @type {Boolean} */  let isOpen = false
	/** @type {Boolean} */  let eventsSetted = false
	/** @type {Boolean} */  let isInit = false
	/** @type {Boolean} */  let sized = false

	/** @type {Boolean} */ let resizable
	/** @type {String} */ let selector
	/** @type {String} */ let parentSelector

	init(selectorParam, parentSelectorParam, resizableParam)

	/**
	 * @function init
	 * @param {String} selectorParam 
	 * @param {String} parentSelectorParam 
	 * @param {Boolean} resizableParam 
	 * @returns {void}
	 */
	function init(selectorParam, parentSelectorParam, resizableParam) {

		selector = typeof selectorParam == 'string' ? selectorParam : '.dialog-pcs'
		parentSelector = typeof parentSelectorParam == 'string' ? parentSelectorParam : 'body'
		resizable = typeof resizableParam == 'boolean' ? resizableParam === true : true

		dragItem = $(selectorParam)
		container = $(parentSelector)
		dragArea = $('[drag-area]')
		closeTrigger = $('[close]')
		deleteTrigger = $('[delete]')

		closeTrigger.css('cursor', 'pointer')
		deleteTrigger.css('cursor', 'pointer')

		if (container.length > 0) {

			if (dragItem.length > 0) {

				if (DialogPCS.variables.selectors.indexOf(selectorParam) === -1) {

					$(dragItem).hide()

					dragItem = dragItem.get(0)
					container = container.get(0)

					DialogPCS.variables.selectors.push(selectorParam)
					isInit = true

					dragItem.style.position = 'absolute'
					dragItem.style.top = '0px'
					dragItem.style.left = '0px'
					dragItem.style.zIndex = '1000'

					if (resizable) {
						makeResizableDiv(selectorParam)
					}

					closeTrigger.click((e) => {

						e.preventDefault()
						e.stopPropagation()
						instance.close()

					})

					deleteTrigger.click((e) => {

						e.preventDefault()
						e.stopPropagation()
						instance.delete()

					})

				} else {
					console.error('El elemento ya ha sido usado por otra instancia')
				}

			} else {
				console.error('El elemento no existe')
			}

		} else {
			console.error('El contenedor no existe')
		}

	}

	/**
	 * @method reInit
	 * @param {String} selectorParam 
	 * @param {String} parentSelectorParam 
	 * @param {Boolean} resizableParam 
	 * @returns {void}
	 */
	this.reInit = (dialogSelector, parentSelector, resizableParam) => {
		init(dialogSelector, parentSelector, resizableParam)
	}

	/**
	 * @method open
	 * @returns {void}
	 */
	this.open = () => {

		if (isInit && !eventsSetted && !isOpen) {

			eventsSetted = true

			container.addEventListener("touchstart", dragStart, false)
			container.addEventListener("touchend", dragEnd, false)
			container.addEventListener("touchmove", drag, false)
			container.addEventListener("mousedown", dragStart, false)
			container.addEventListener("mouseup", dragEnd, false)
			container.addEventListener("mousemove", drag, false)

			$(dragItem).show()

			isOpen = true

			if (!sized) {
				initialW = $(dragItem).width()
				initialH = $(dragItem).height()
				sized = true
			}

		}

	}

	/**
	 * @method close
	 * @returns {void}
	 */
	this.close = () => {

		if (isInit && eventsSetted && isOpen) {

			eventsSetted = false

			container.removeEventListener("touchstart", dragStart)
			container.removeEventListener("touchend", dragEnd)
			container.removeEventListener("touchmove", drag)
			container.removeEventListener("mousedown", dragStart)
			container.removeEventListener("mouseup", dragEnd)
			container.removeEventListener("mousemove", drag)

			$(dragItem).hide()

			isOpen = false

		}

	}

	/**
	 * @method delete
	 * @returns {void}
	 */
	this.delete = () => {

		this.close()
		$(dragItem).remove()
		isInit = false
		sized = false
		initialW = 0
		initialH = 0

		let newSelectors = []
		for (let s of DialogPCS.variables.selectors) {
			if (s != selector) {
				newSelectors.push(s)
			}
		}
		DialogPCS.variables.selectors = newSelectors

	}

	/**
	 * @method delete
	 * @returns {Boolean}
	 */
	this.isOpen = () => {
		return isOpen
	}

	/**
	 * @function dragStart
	 * @param {Event} e
	 * @returns {void}
	 */
	function dragStart(e) {
		if (e.type === "touchstart") {
			initialX = e.touches[0].clientX - xOffset
			initialY = e.touches[0].clientY - yOffset
		} else {
			initialX = e.clientX - xOffset
			initialY = e.clientY - yOffset
		}

		if (e.target === dragArea.get(0)) {
			active = true
		}
	}

	/**
	 * @function dragEnd
	 * @param {Event} e
	 * @returns {void}
	 */
	function dragEnd(e) {
		initialX = currentX
		initialY = currentY

		active = false
	}

	/**
	 * @function drag
	 * @param {Event} e
	 * @returns {void}
	 */
	function drag(e) {
		if (active) {

			e.preventDefault()

			if (e.type === "touchmove") {
				currentX = e.touches[0].clientX - initialX
				currentY = e.touches[0].clientY - initialY
			} else {
				currentX = e.clientX - initialX
				currentY = e.clientY - initialY
			}

			xOffset = currentX
			yOffset = currentY

			setTranslate(currentX, currentY, dragItem)
		}
	}

	/**
	 * @function setTranslate
	 * @param {Number} xPos 
	 * @param {Number} yPos 
	 * @param {HTMLElement} el 
	 * @returns {void}
	 */
	function setTranslate(xPos, yPos, el) {
		yPos = yPos + changeY
		xPos = xPos + changeX
		el.style.top = yPos + 'px'
		el.style.left = xPos + 'px'
		//el.style.transform = "translate3d(" + xPos + "px, " + yPos + "px, 0)"

	}

	/**
	 * @function makeResizableDiv
	 * @param {String} elementSelector 
	 * @returns {void}
	 */
	function makeResizableDiv(elementSelector) {

		const element = document.querySelector(elementSelector)
		const minimum_size = 200

		let original_width = 0
		let original_height = 0
		let original_x = 0
		let original_y = 0
		let original_mouse_x = 0
		let original_mouse_y = 0

		let resizerAttr = 'dialog-pcs-resizer-element'

		let type = ''

		createResizers(element)

		let resizers = document.querySelectorAll(elementSelector + ` [${resizerAttr}]`)

		let counter = 0
		let resizeEvents = []
		let stopResizeEvents = []

		for (let indexIterator = 0; indexIterator < resizers.length; indexIterator++) {
			const currentResizer = resizers[indexIterator]
			currentResizer.addEventListener('mousedown', handlerConfigEvents)
		}

		/**
		 * @function handlerConfigEvents
		 * @param {Event} e 
		 * @returns {void}
		 */
		function handlerConfigEvents(e) {

			e.preventDefault()
			original_width = parseFloat(getComputedStyle(element, null).getPropertyValue('width').replace('px', ''))
			original_height = parseFloat(getComputedStyle(element, null).getPropertyValue('height').replace('px', ''))
			original_x = element.getBoundingClientRect().left
			original_y = element.getBoundingClientRect().top
			original_mouse_x = e.pageX
			original_mouse_y = e.pageY

			let that = e.currentTarget
			let thatCounter = counter

			if (!active) {

				resizeEvents[counter] = function (e) {
					resize(e, that, thatCounter)
				}
				stopResizeEvents[counter] = function (e) {
					stopResize(e, that, thatCounter)
				}

				type = ''

				window.addEventListener('mousemove', resizeEvents[counter])
				window.addEventListener('mouseup', stopResizeEvents[counter])

				counter++
			}

		}

		/**
		 * @function resize
		 * @param {Event} e 
		 * @param {HTMLElement} currentResizer 
		 * @param {Number} ecounter
		 * @returns {void}
		 */
		function resize(e, currentResizer, counter) {

			if (currentResizer.classList.contains('bottom-right')) {
				const width = original_width + (e.pageX - original_mouse_x)
				const height = original_height + (e.pageY - original_mouse_y)
				if (width > minimum_size) {
					element.style.width = width + 'px'
				}
				if (height > minimum_size) {
					element.style.height = height + 'px'
				}
			}
			else if (currentResizer.classList.contains('bottom-left')) {
				const height = original_height + (e.pageY - original_mouse_y)
				const width = original_width - (e.pageX - original_mouse_x)
				if (height > minimum_size) {
					element.style.height = height + 'px'
				}
				if (width > minimum_size) {
					element.style.width = width + 'px'
					element.style.left = original_x + (e.pageX - original_mouse_x) + 'px'
				}

				type = 'bl'

			}
			else if (currentResizer.classList.contains('top-right')) {

				const width = original_width + (e.pageX - original_mouse_x)
				const height = original_height - (e.pageY - original_mouse_y)
				if (width > minimum_size) {
					element.style.width = width + 'px'
				}
				if (height > minimum_size) {
					element.style.height = height + 'px'
					element.style.top = original_y + (e.pageY - original_mouse_y) + 'px'
				}

				type = 'tr'

			}
			else {
				const width = original_width - (e.pageX - original_mouse_x)
				const height = original_height - (e.pageY - original_mouse_y)
				if (width > minimum_size) {
					element.style.width = width + 'px'
					element.style.left = original_x + (e.pageX - original_mouse_x) + 'px'
				}
				if (height > minimum_size) {
					element.style.height = height + 'px'
					element.style.top = original_y + (e.pageY - original_mouse_y) + 'px'
				}

				type = 'tl'
			}
		}

		/**
		 * @function stopResize
		 * @param {Event} e 
		 * @param {HTMLElement} currentResizer 
		 * @param {Number} ecounter
		 * @returns {void}
		 */
		function stopResize(e, currentResizer, counter) {

			window.removeEventListener('mousemove', resizeEvents[counter])

			if (type == 'tr' || type == 'tl') {

				let up = true
				let size = 0

				let height = $(element).height()

				if (height > initialH) {
					size = Math.abs(initialH - height)
					up = true
				} else {
					size = Math.abs(height - initialH)
					up = false
				}
				if (up) {
					changeY = size * -1
				} else {
					changeY = size
				}

			}

			if (type == 'bl' || type == 'tl') {

				let up = true
				let size = 0

				let width = $(element).width()

				if (width > initialW) {
					size = Math.abs(initialW - width)
					up = true
				} else {
					size = Math.abs(width - initialW)
					up = false
				}
				if (up) {
					changeX = size * -1
				} else {
					changeX = size
				}

			}

		}

		/**
		 * @function createResizers
		 * @param {HTMLElement} dialog 
		 * @returns {void}
		 */
		function createResizers(dialog) {

			let resizerTL = document.createElement('div')
			let resizerTR = document.createElement('div')
			let resizerBR = document.createElement('div')
			let resizerBL = document.createElement('div')

			resizerTL.setAttribute(resizerAttr, '')
			resizerTR.setAttribute(resizerAttr, '')
			resizerBR.setAttribute(resizerAttr, '')
			resizerBL.setAttribute(resizerAttr, '')

			resizerTL.classList.add('top-left')
			resizerTR.classList.add('top-right')
			resizerBR.classList.add('bottom-right')
			resizerBL.classList.add('bottom-left')

			resizerTL.style.position = 'absolute'
			resizerTL.style.top = '0%'
			resizerTL.style.left = '0%'
			resizerTR.style.position = 'absolute'
			resizerTR.style.top = '0%'
			resizerTR.style.right = '0%'
			resizerBR.style.position = 'absolute'
			resizerBR.style.bottom = '0%'
			resizerBR.style.right = '0%'
			resizerBL.style.position = 'absolute'
			resizerBL.style.bottom = '0%'
			resizerBL.style.left = '0%'

			resizerTL.style.width = '10px'
			resizerTL.style.height = '10px'
			resizerTL.style.backgroundColor = 'rgba(0, 0, 0, 0.1)'
			resizerTL.style.cursor = 'nwse-resize'
			dialog.appendChild(resizerTL)
			resizerTR.style.width = '10px'
			resizerTR.style.height = '10px'
			resizerTR.style.backgroundColor = 'rgba(0, 0, 0, 0.1)'
			resizerTR.style.cursor = 'nesw-resize'
			dialog.appendChild(resizerTR)
			resizerBR.style.width = '10px'
			resizerBR.style.height = '10px'
			resizerBR.style.backgroundColor = 'rgba(0, 0, 0, 0.1)'
			resizerBR.style.cursor = 'nwse-resize'
			dialog.appendChild(resizerBR)
			resizerBL.style.width = '10px'
			resizerBL.style.height = '10px'
			resizerBL.style.backgroundColor = 'rgba(0, 0, 0, 0.1)'
			resizerBL.style.cursor = 'nesw-resize'
			dialog.appendChild(resizerBL)

		}

	}

	return this
}

DialogPCS.variables = {
	/** @type {String[]} */
	selectors: [],
}
