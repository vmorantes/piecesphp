function Avatar() {

	let that = this

	this.image = null
	this.sources = null
	this.saveButton = null

	let saveEventName = 'save'
	let saveEvent = new Event(saveEventName)
	let saveEventer = document.createElement('SAVE')

	let avatarComponentSelector = ".avatar-component"
	let frameSelector = `${avatarComponentSelector} .frame`
	let frameItemsSelector = `${frameSelector} [item]`
	let controlsContainerSelector = `${avatarComponentSelector} .controls-select`
	let controlsRadioInputSelector = `${controlsContainerSelector} input`
	let controlsMoveButtonsSelector = `${controlsContainerSelector} [buttons-move]`
	let colorContainerSelector = `${controlsContainerSelector} [group-color]`
	let saveButtonSelector = `${avatarComponentSelector} [save-button]`

	let groupAttribute = 'group'
	let subGroupAttribute = 'sub-group'
	let buttonMoveAttribute = 'buttons-move'
	let colorAttribute = 'group-color'

	let avatarComponent = $(avatarComponentSelector)

	let frame = $(frameSelector)
	let frameItems = $(frameItemsSelector).toArray()

	let controlsContainer = $(controlsContainerSelector)
	let controlsRadioInput = $(controlsRadioInputSelector)
	let controlsMoveButtons = $(controlsMoveButtonsSelector)
	let colorContainer = $(colorContainerSelector)
	let selectedCategories = controlsRadioInput.filter(':checked').val()

	this.saveButton = $(saveButtonSelector)

	let buttonsConfigurated = []

	controlsRadioInput.parent().checkbox()

	this.config = function (sources) {

		that.sources = sources

		setAvatar()

		controlsRadioInput.change(function () {
			let value = $(this).val()

			if (value != selectedCategories) {
				selectedCategories = value
			}
			setAvatar()
		})

		that.saveButton.click(function () {

			let itemFrameCapture = $(frameSelector)
			itemFrameCapture = itemFrameCapture[0]

			let paths = $(itemFrameCapture).find('svg path').toArray()

			let svgHTML = $(document.createElement('svg'))
			svgHTML.attr('xmlns', 'http://www.w3.org/2000/svg')
			svgHTML.css({
				width: '1000px',
				height: '1000px',
			})

			for (let path of paths) {
				svgHTML.append(path.outerHTML)
			}

			svgHTML = svgHTML[0]

			let canvas = document.createElement('canvas')

			canvg(canvas, svgHTML.outerHTML, {
				renderCallback: () => {

					let bgCanvas = document.createElement("canvas")
					bgCanvas.width = canvas.width
					bgCanvas.height = canvas.height
					bgCtx = bgCanvas.getContext('2d')
					bgCtx.fillStyle = "#FFFFFF"
					bgCtx.fillRect(0, 0, canvas.width, canvas.height)
					bgCtx.drawImage(canvas, 0, 0)

					that.image = bgCanvas.toDataURL('image/jpeg')

					saveEventer.dispatchEvent(saveEvent)
				}
			})

		})

	}

	this.getImage = function () {
		return that.image
	}

	this.on = function (name, callback) {
		if (typeof name == 'string' && typeof callback == 'function') {
			if (name == saveEventName) {
				let listener = function () {
					let preview = avatarComponent.find('.preview')
					callback(that.image, preview.length > 0 ? preview[0] : null)
				}
				saveEventer.removeEventListener(name, listener)
				saveEventer.addEventListener(name, listener)
			}
		}
	}

	function setAvatar(parts = null) {

		let all = !Array.isArray(parts)

		for (let item of frameItems) {

			let i = $(item)

			let source = that.sources.categories[selectedCategories]

			let group = i.attr(groupAttribute)
			let element = source.elements[group]
			if (typeof element == 'undefined') {
				continue
			}
			let currentIndex = element.current
			let hasSubGroup = typeof element.subElements == 'object'

			if (!all) {
				if (parts.indexOf(group) === -1) {
					continue
				}
			}

			let srcsFrame = []

			if (!hasSubGroup) {

				let svg = `${element.files[currentIndex]}`

				srcsFrame = [
					{
						svg: svg,
						element: i,
					}
				]
				i.html('')

			} else {

				let subItems = $(`${frameItemsSelector}[${subGroupAttribute}]`).toArray()

				for (let subItem of subItems) {
					let sub = $(subItem)
					let subGroup = sub.attr(subGroupAttribute)
					let subElement = element.subElements[subGroup]
					if (typeof subElement == 'undefined') {
						continue
					}
					let svg = `${subElement.files[currentIndex]}`

					srcsFrame.push({
						svg: svg,
						element: sub,
					})
					sub.html('')
				}
			}

			configButtons(group)
			configColors(group)

			for (let src of srcsFrame) {
				src.element.html(src.svg)
			}
		}

	}

	function configButtons(group) {

		let groupButtons = controlsMoveButtons.filter(`[${buttonMoveAttribute}='${group}']`)
		let prev = groupButtons.find('[prev]')
		let next = groupButtons.find('[next]')
		let label = groupButtons.find('[label]')
		let labelText = label.attr('label')

		label.attr('disabled',true)

		let source = that.sources.categories[selectedCategories]
		let element = source.elements[group]
		let currentIndex = element.current
		let numItem = currentIndex + 1
		let total = element.total

		if (numItem == 1) {
			prev.attr('disabled', true)
		} else {
			prev.attr('disabled', false)
		}
		if (numItem == total) {
			next.attr('disabled', true)
		} else {
			next.attr('disabled', false)
		}

		if(labelText.trim().length>0){
			label.val(`${labelText} - ${numItem}/${total}`)
		}else{
			label.val(`${numItem}/${total}`)
		}

		if (buttonsConfigurated.indexOf(group) === -1) {

			buttonsConfigurated.push(group)

			prev.click(function () {

				let source = that.sources.categories[selectedCategories]
				let element = source.elements[group]
				let currentIndex = element.current
				let numItem = currentIndex + 1
				let total = element.total

				if (numItem > 1) {

					that.sources.categories[selectedCategories]['elements'][group]['current']--
					numItem -= 1

					if (numItem > 1) {
						prev.attr('disabled', false)
					} else {
						prev.attr('disabled', true)
					}

				} else {
					prev.attr('disabled', false)
				}

				label.attr('data-text', `${numItem}/${total}`)

				next.attr('disabled', false)

				setAvatar([group])
			})
			next.click(function () {

				let source = that.sources.categories[selectedCategories]
				let element = source.elements[group]
				let currentIndex = element.current
				let numItem = currentIndex + 1
				let total = element.total

				if (numItem < total) {

					that.sources.categories[selectedCategories]['elements'][group]['current']++
					numItem += 1

					if (numItem < total) {
						next.attr('disabled', false)
					} else {
						next.attr('disabled', true)
					}

				} else {
					next.attr('disabled', true)
				}

				label.attr('data-text', `${numItem}/${total}`)

				prev.attr('disabled', false)

				setAvatar([group])
			})
		}
	}

	function configColors(group) {

		let colorSelectors = colorContainer.filter(`[${colorAttribute}='${group}']`)
		let colorsContainer = colorSelectors.find(`[container-colors]`)

		let elements = that.sources.categories[selectedCategories]
		let el = elements.elements[group]
		let hasSubGroup = typeof el.subElements == 'object'

		frame.on('DOMNodeInserted', function () {

			colorsContainer.html('')
			colorSelectors.hide()

			if (!hasSubGroup) {

				let hasColors = typeof el.colors == 'object' && Array.isArray(el.colors) && el.colors.length > 0

				if (hasColors) {

					let colors = el.colors

					let dropDownDiv = $(document.createElement('div'))
						.addClass('ui inline dropdown')

					let textDiv = $(document.createElement('div'))
						.addClass('text')

					let menuDiv = $(document.createElement('div'))
						.addClass('menu')

					dropDownDiv.append(textDiv).append(menuDiv)

					let setPreview = false

					for (let color of colors) {

						let itemColorDiv = $(document.createElement('div'))
						itemColorDiv.attr('item-color', '')

						itemColorDiv.css({
							backgroundColor: color,
						})

						itemColorDiv.click(function () {

							if($(this).parent().parent().hasClass('text')){
								return;
							}

							let color = $(this).css('background-color')

							let itemsColorable = $(`${frameItemsSelector}[${groupAttribute}='${group}'] path`)

							itemsColorable.css({
								fill: color
							})

						})

						let item = $(document.createElement('div')).addClass('item')
						item.append(itemColorDiv)

						menuDiv.append(item)
						if (!setPreview) {
							textDiv.append(item)
							setPreview = true
						}

					}

					colorsContainer.append(dropDownDiv)

					dropDownDiv.dropdown()
					colorSelectors.show()
				}

			} else {
				let groupHasColor = typeof el.colors == 'object' && Array.isArray(el.colors) && el.colors.length > 0
				let subEls = el.subElements

				if (!groupHasColor) {

					let number = 0

					for (let name in subEls) {

						let subEl = subEls[name]

						let hasColors = typeof subEl.colors == 'object' && Array.isArray(subEl.colors) && subEl.colors.length > 0

						let itemsColorable = $(`${frameItemsSelector}[${subGroupAttribute}='${name}'] path`)

						let itemSub = $(document.createElement('div'))
						itemSub.attr('item','')

						if (hasColors && itemsColorable.length > 0) {

							number++

							itemSub.append(`<strong>${number}:</strong>`)

							let colors = subEl.colors

							let dropDownDiv = $(document.createElement('div'))
								.addClass('ui inline dropdown')

							let textDiv = $(document.createElement('div'))
								.addClass('text')

							let menuDiv = $(document.createElement('div'))
								.addClass('menu')

							dropDownDiv.append(textDiv).append(menuDiv)

							let setPreview = false

							for (let color of colors) {

								let itemColorDiv = $(document.createElement('div'))
								itemColorDiv.attr('item-color', '')

								itemColorDiv.css({
									backgroundColor: color,
								})

								itemColorDiv.click(function () {
									if($(this).parent().parent().hasClass('text')){
										return;
									}
									let color = $(this).css('background-color')

									itemsColorable.css({
										fill: color
									})

								})

								let item = $(document.createElement('div')).addClass('item')
								item.append(itemColorDiv)

								menuDiv.append(item)
								if (!setPreview) {
									textDiv.append(item)
									setPreview = true
								}

							}

							itemSub.append(dropDownDiv)
							colorsContainer.append(itemSub)

							dropDownDiv.dropdown()
							colorSelectors.show()
						}

					}
				} else {

					let hasColors = typeof el.colors == 'object' && Array.isArray(el.colors) && el.colors.length > 0

					if (hasColors) {

						let colors = el.colors

						let dropDownDiv = $(document.createElement('div'))
							.addClass('ui inline dropdown')

						let textDiv = $(document.createElement('div'))
							.addClass('text')

						let menuDiv = $(document.createElement('div'))
							.addClass('menu')

						dropDownDiv.append(textDiv).append(menuDiv)

						let setPreview = false

						for (let color of colors) {

							let itemColorDiv = $(document.createElement('div'))
							itemColorDiv.attr('item-color', '')

							itemColorDiv.css({
								backgroundColor: color,
							})

							itemColorDiv.click(function () {

								if($(this).parent().parent().hasClass('text')){
									return;
								}

								let color = $(this).css('background-color')

								let itemsColorable = $(`${frameItemsSelector}[${groupAttribute}='${group}'] path`)

								itemsColorable.css({
									fill: color
								})

							})

							let item = $(document.createElement('div')).addClass('item')
							item.append(itemColorDiv)

							menuDiv.append(item)

							if (!setPreview) {
								textDiv.append(item)
								setPreview = true
							}

						}

						colorsContainer.append(dropDownDiv)

						dropDownDiv.dropdown()
						colorSelectors.show()
					}

				}
			}
		})
	}


	return this
}
