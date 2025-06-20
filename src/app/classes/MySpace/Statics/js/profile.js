/// <reference path="../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../statics/core/own-plugins/MapBoxAdapter.js" />
/// <reference path="../../../../../statics/core/own-plugins/MapBoxAdapterCustomControls.js" />
/// <reference path="../../../../../statics/core/own-plugins/LocationsAdapter.js" />
showGenericLoader('my-profile')
window.addEventListener('load', function () {

	removeGenericLoader('my-profile')

	const langGroup = 'global'
	registerDynamicLocalizationMessages(langGroup)

	/* Configuraciones iniciales */
	//Tooltip
	$('[data-tooltip]').popup()

	//Mapa
	configurateMap().then(function () {
	})

	function configurateMap() {

		const loaderMap = 'loaderMap-' + generateUniqueID()
		showGenericLoader(loaderMap)

		let mapBoxAdapterLocation = new MapBoxAdapter()
		let mapBoxAdapterLocationMobile = new MapBoxAdapter()
		const longitudeInput = $(`[longitude-mapbox-handler]`)
		const latitudeInput = $(`[latitude-mapbox-handler]`)

		return new Promise(function (resolve) {

			fetch('configurations/mapbox-key')
				.then(response => response.text())
				.then(key => resolve(key))

		}).then(function (key) {
			mapBoxAdapterLocation.setKey(key).configurateWhitForm(
				{},
				{
					defaultLongitude: longitudeInput.val(),
					defaultLatitude: latitudeInput.val(),
					ignoreDefaultCss: true,
					idMapContainer: 'map',
					draggableMarker: false,
				},
				{
					withMarker: true,
					withGeolocator: false,
					withNav: false,
					withScale: false,
					withGeocoder: false,
					withFullscreen: true,
					zoom: 10,
					dragPan: false,
				},
			)
			mapBoxAdapterLocationMobile.setKey(key).configurateWhitForm(
				{},
				{
					defaultLongitude: longitudeInput.val(),
					defaultLatitude: latitudeInput.val(),
					ignoreDefaultCss: true,
					idMapContainer: 'map-mobile',
					draggableMarker: false,
				},
				{
					withMarker: true,
					withGeolocator: false,
					withNav: false,
					withScale: false,
					withGeocoder: false,
					withFullscreen: true,
					zoom: 10,
					dragPan: false,
				},
			)
		}).finally(function () {
			removeGenericLoader(loaderMap)
		})

	}
})
