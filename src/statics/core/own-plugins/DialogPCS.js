function DialogPCS(dialogSelector = '.dialog-pcs', parentSelector = 'body') {

	let instance = this

	let dragItem = null
	let container = null
	let dragArea = null

	let currentX
	let currentY
	let initialX
	let initialY
	let xOffset = 0
	let yOffset = 0
	let active = false
	let isOpen = false
	let eventsSetted = false
	let isInit = false

	init(dialogSelector, parentSelector)

	function init(dialogSelector, parentSelector) {

		dragItem = $(dialogSelector)
		container = $(parentSelector)
		dragArea = $('[drag-area]')
		closeTrigger = $('[close]')
		deleteTrigger = $('[delete]')

		closeTrigger.css('cursor', 'pointer')
		deleteTrigger.css('cursor', 'pointer')

		if (container.length > 0) {

			if (dragItem.length > 0) {

				if (DialogPCS.variables.selectors.indexOf(dialogSelector) === -1) {

					$(dragItem).hide()

					dragItem = dragItem.get(0)
					container = container.get(0)

					DialogPCS.variables.selectors.push(dialogSelector)
					isInit = true

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

	this.reInit = (dialogSelector = '.dialog-pcs', parentSelector = 'body') => {
		init(dialogSelector, parentSelector)
	}

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

		}

	}

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

	this.delete = () => {

		this.close()
		$(dragItem).remove()
		isInit = false

	}

	this.isOpen = () => {
		return isOpen
	}

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

	function dragEnd(e) {
		initialX = currentX
		initialY = currentY

		active = false
	}

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

	function setTranslate(xPos, yPos, el) {
		el.style.transform = "translate3d(" + xPos + "px, " + yPos + "px, 0)"
	}

}

DialogPCS.variables = {
	selectors: [],
}
