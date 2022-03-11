/// <reference path="../../../../../statics/core/js/helpers.js"/>
/// <reference path="../../../../../statics/core/js/configurations.js"/>
/**
 * @param {String} file
 * @returns {ComponentsProvider}
 */
function ComponentsProvider(file) {

	/** @type {HTMLElement} */ let eventer = document.createElement('custom-provider-eventer')
	/** @type {String} */ let eventPrefix = 'custom-provider-event'
	/** @type {String} */ let loadEventName = `${eventPrefix}-load`
	/** @type {Event} */ let loadEvent = new Event(loadEventName)

	/** @type {Map<String, Map<String, HTMLElement>>} */ let components = new Map()
	/** @type {Boolean} */ let isLoad = false

	let requestComponents = $.ajax({
		url: file,
		dataType: 'html',
		error: function (jqXHR, textStatus, errorThrown) {
			console.error(textStatus)
			console.error(errorThrown)
			errorMessage('Error', 'Ha ocurrido un error al cargar los componentes.')
		},
	})

	requestComponents.done((res) => {

		$(res).filter(`[name]`).toArray().map((componentsElement) => {

			let componentGroupNames = componentsElement.getAttribute('name').trim().split(' ')
			let componentsItems = componentsElement.querySelectorAll('component')

			for (let componentGroupName of componentGroupNames) {

				components.set(componentGroupName, new Map())

				Array.from(componentsItems).map((componentItem) => {

					let names = componentItem.getAttribute('name').trim().split(' ')
					let content = $(componentItem.innerHTML)

					for (let name of names) {
						components.get(componentGroupName).set(name, content.get(0))
					}

				})

			}

			return componentsElement
		})

		isLoad = true
		eventer.dispatchEvent(loadEvent)

	})

	/**
	 * @method on
	 * @param {String} eventName
	 * @param {Function} callback
	 * @returns {this}
	 */
	this.on = (eventName, callback) => {

		if (typeof eventName == 'string') {

			let _eventName = `${eventPrefix}-${eventName}`

			if (typeof callback == 'function') {

				eventer.addEventListener(_eventName, function (e) {

					if (_eventName == loadEventName) {
						callback(e, components)
					} else {
						callback(e)
					}

				})

			}

		}

		return this
	}


	/**
	 * @method isLoad
	 * @returns {Boolean}
	 */
	this.isLoad = () => {
		return isLoad
	}

	/**
	 * @method getComponentsGroup
	 * @param {String} name
	 * @returns {Map<String, HTMLElement>}
	 */
	this.getComponentsGroup = (name) => {

		if (components.has(name)) {
			return components.get(name)
		}

		throw new Error('El grupo de componentes no existe')
	}

}
