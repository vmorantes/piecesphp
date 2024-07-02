/// <reference path="./MapBoxAdapter.js" />
window.addEventListener(MapBoxAdapter.events.ConfigurationReady, function () {

	console.info('===Ajustes de intefaz de mapa en MapBoxAdapterCustomControls===')

	for (const mapBoxAdapter of MapBoxAdapter.instances) {

		const currentMapElements = mapBoxAdapter.currentMapElements
		const map = currentMapElements.map
		const marker = currentMapElements.marker
		const geolocator = currentMapElements.geolocator
		const $mapHtmlElement = $(map.getContainer())
		const $markerHtmlElment = $(marker._element)
		const $mapControls = $mapHtmlElement.find('.mapboxgl-ctrl.mapboxgl-ctrl-group')

		//──── Personalizaciones ─────────────────────────────────────────────────────────────────

		//Marcador de posición
		$markerHtmlElment.addClass('marker-map')
		$markerHtmlElment.html(`<i class="map marker alternate icon"></i>`)

		//Controles
		const watchButtonControlExistsUtility = function (getButtonCallback, callback) {
			const searchButtonInterval = setInterval(function () {
				let button = typeof getButtonCallback == 'function' ? getButtonCallback() : {}
				if (typeof button == 'object' && typeof button.length == 'number' && button.length > 0) {
					if (typeof callback == 'function') {
						callback(button)
					}
					clearInterval(searchButtonInterval)
				}
			}, 100)
		}
		const controlsElements = {
			zoomIn: {
				getButton: function () {
					return $mapControls.find('.mapboxgl-ctrl-zoom-in')
				},
				onButtonReady: watchButtonControlExistsUtility,
			},
			zoomOut: {
				getButton: function () {
					return $mapControls.find('.mapboxgl-ctrl-zoom-out')
				},
				onButtonReady: watchButtonControlExistsUtility,
			},
			rotate: {
				getButton: function () {
					return $mapControls.find('.mapboxgl-ctrl-compass')
				},
				onButtonReady: watchButtonControlExistsUtility,
			},
			geolocate: {
				getButton: function () {
					return $(geolocator._geolocateButton)
				},
				onButtonReady: watchButtonControlExistsUtility,
			},
			fullscreen: {
				getButton: function () {
					return !mapBoxAdapter.isFullscreen() ? $mapControls.find('.mapboxgl-ctrl-fullscreen') : $mapControls.find('.mapboxgl-ctrl-shrink')
				},
				onButtonReady: watchButtonControlExistsUtility,
			},
		}

		//--Zoom in/out
		controlsElements.zoomIn.onButtonReady(controlsElements.zoomIn.getButton, function (button) {
			button.find('>span').replaceWith(`<i class="plus icon"></i>`)
		})
		controlsElements.zoomOut.onButtonReady(controlsElements.zoomOut.getButton, function (button) {
			button.find('>span').replaceWith(`<i class="minus icon"></i>`)
		})

		//--Rotate
		controlsElements.rotate.onButtonReady(controlsElements.rotate.getButton, function (button) {
			button.find('>span').replaceWith(`<i class="compass outline icon"></i>`)
		})

		//--Geolocate
		controlsElements.geolocate.onButtonReady(controlsElements.geolocate.getButton, function (button) {
			button.find('>span').replaceWith(`<i class="bullseye icon"></i>`)
		})

		//--Fullscreen
		const customizeFullScreenButtonHandler = function () {
			controlsElements.fullscreen.onButtonReady(controlsElements.fullscreen.getButton, function (button) {
				button.parent().css('margin-top', "0px")
				if (mapBoxAdapter.isFullscreen()) {
					button.find('>i.icon').replaceWith(`<i class="compress icon"></i>`)
				} else {
					button.find('>span, >i.icon').replaceWith(`<i class="expand icon"></i>`)
				}
			})
		}
		customizeFullScreenButtonHandler()
		map.getContainer().addEventListener('fullscreenchange', customizeFullScreenButtonHandler)

	}

})
