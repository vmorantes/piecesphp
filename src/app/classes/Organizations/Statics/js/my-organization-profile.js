/// <reference path="../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../statics/core/js/helpers.js" />
/// <reference path="../../../../../statics/core/own-plugins/MapBoxAdapter.js" />
/// <reference path="../../../../../statics/core/own-plugins/MapBoxAdapterCustomControls.js" />
/// <reference path="../../../../../statics/core/own-plugins/LocationsAdapter.js" />
showGenericLoader('my-organization-profile')
window.addEventListener('load', function () {

	removeGenericLoader('my-organization-profile')

	const langGroup = 'global'
	registerDynamicLocalizationMessages(langGroup)

	/* Configuraciones iniciales */

	//Dropdowns
	configFomanticDropdown('.ui.dropdown.auto')
	configFomanticDropdown('.ui.dropdown.auto.additions', {
		allowAdditions: true,
	})

	//Tabs
	const checkRelatedTabElements = function (tabName) {
		const relatedElementsWithTabs = $(`[data-tab-related]`)
		const relatedElementWithTab = $(`[data-tab-related="${tabName}"]`)
		relatedElementsWithTabs.hide()
		relatedElementWithTab.show()
	}
	const initialTabActions = function (tabs) {
		//Revisar elementos relacionados
		checkRelatedTabElements(tabs.filter('.active').data('tab'))
		//Actualizar activo según hash
		const url = new URL(window.location.href)
		const pathAlternatives = [
			url.searchParams.get('currentTab'),
			url.hash,
		]
		for (let path of pathAlternatives) {
			path = typeof path == 'string' ? path.replace('#', '').trim() : ''
			if (path.length > 0 && tabs.tab('is tab', path)) {
				tabs.tab('change tab', path)
				break
			}
		}
	}
	const tabs = $('.tabs-controls [data-tab]').tab({
		onVisible: function (tabName) {
			checkRelatedTabElements(tabName)
			const url = new URL(window.location.href)
			url.searchParams.set('currentTab', tabName)
			window.history.pushState({}, '', url)
		},
	})
	initialTabActions(tabs)

	//Tooltip
	$('[data-tooltip]').popup()

	//Mapa
	configurateMap().then(function () {
		profileForm()
	})

	//La tarjeta de imagen: lo aplicado en el editor se envía al guardar.
	let logo = null
	new ContentImageEditor().onApply('organization-logo', (file) => logo = file)
	function profileForm(onlyForm = false) {

		/* Selectores y elementos de interfaz */
		const formSelector = `.ui.form.my-organization-profile`

		//Formulario
		let form = genericFormHandler(formSelector, {
			onSetFormData: function (formData) {
				if (logo !== null) {
					formData.set('logo', logo)
				}
				return formData
			},
		})

		if (onlyForm) {
			return form
		}

		//Botones de guardado
		let regularSaveButton = form.find('button[type="submit"][save]')
		let otherSaveButtons = form.find('button[type="submit"][other-save-button]')
		let cancelButtons = form.find('button[type="submit"][cancel]')
		let externalSaveButtons = $('button[type="submit"][external-save]')
		let externalCancelButtons = $('button[type="submit"][external-cancel]')

		//Comportamiento de otros botenes de guardado
		const handleSave = function (event) {
			event.preventDefault()
			regularSaveButton.click()
		}
		otherSaveButtons.on('click', handleSave)
		externalSaveButtons.on('click', handleSave)

		//Comportamiento de botenes de cancelar
		const handleCancel = function (event) {

			event.preventDefault()

			confirmAction({
				message: _i18n(langGroup, '¿Está seguro de cancelar la operación?'),
				confirmClass: 'red',
			}).then(function (confirmed) {
				if (confirmed) {
					const loaderNameCancel = generateUniqueID('loaderCancel-')
					showGenericLoader(loaderNameCancel)
					window.location.reload()
					removeGenericLoader(loaderNameCancel)
				}
			})
		}
		cancelButtons.on('click', handleCancel)
		externalCancelButtons.on('click', handleCancel)

		//Cambio de administrador
		let changeAdminModalSelector = '.ui.modal[change-organization-admin-modal]'
		let changeAdminButton = form.find('button[change-organization-admin-trigger]')
		let changeAdminModal = $(changeAdminModalSelector).modal()
		changeAdminButton.on('click', function (e) {
			e.preventDefault()
			changeAdminModal.modal({
				onVisible: function () {

					const formModalSelector = `${changeAdminModalSelector} form.ui.form`
					let form = genericFormHandler(formModalSelector, {
						onSetFormData: function (formData) {
							return formData
						},
						confirmation: {
							title: _i18n(langGroup, 'Confirmación'),
							message: _i18n(langGroup, '¿Está seguro de cancelar la operación?'),
							positive: _i18n(langGroup, 'Sí'),
							negative: _i18n(langGroup, 'No'),
							selector: `${formModalSelector}`,
						},
					})
				}
			}).modal('show')
		})

	}

	function configurateMap() {

		const loaderMap = 'loaderMap-' + generateUniqueID()
		showGenericLoader(loaderMap)
		let locations = new LocationsAdapter()
		let locations2 = new LocationsAdapter({
			selectAutoFilledRegionAttr: 'locations-component-auto-filled-region2',
			selectAutoFilledCountryAttr: 'locations-component-auto-filled-country2',
			selectAutoFilledStateAttr: 'locations-component-auto-filled-state2',
			selectAutoFilledCityAttr: 'locations-component-auto-filled-city2',
			selectAutoFilledPointAttr: 'locations-component-auto-filled-point2',
		})
		let mapBoxAdapter = new MapBoxAdapter()
		let dataElementLocation = $('[element-location-module-data]')

		if (dataElementLocation.length > 0) {
			LocationsAdapter.dataToFilter.onlyCountries = []
			LocationsAdapter.dataToFilter.onlyStates = []
			LocationsAdapter.dataToFilter.onlyCities = []
		}

		locations.fillSelectWithCountriesToCities()
		locations2.fillSelectWithCountriesToCities()

		let controlsMapBox = {
			latitudeInput: $(`[latitude-mapbox-handler]`),
			longitudeInput: $(`[longitude-mapbox-handler]`),
			selectCountry: $(`[locations-component-auto-filled-country]`),
			selectState: $(`[locations-component-auto-filled-state]`),
			selectCity: $(`[locations-component-auto-filled-city]`),
			triggerSatelitalView: $(`[set-satelital-view]`),
			triggerDrawView: $(`[set-draw-view]`),
			triggerCenterView: $(`[set-center-view]`),
		}

		//NOTE: Ignorar arbitrariamente valores que corresponden a "Otros"
		mapBoxAdapter.ignoreSearch = function (countryValue, stateValue, cityValue) {
			let ignore = countryValue == 3 || stateValue == 127 || cityValue == 1574
			//Asignar coordenadas en medio del mar
			if (ignore) {
				const lng = -14.065756740576035
				const lat = -14.416525542824788
				mapBoxAdapter.currentMapElements.marker.setLngLat([lng, lat])
				controlsMapBox.longitudeInput.val(lng)
				controlsMapBox.latitudeInput.val(lat)
			}
			return ignore
		}

		return new Promise(function (resolve) {

			fetch(pcsphpGlobals.mapboxKeyURL)
				.then(response => response.text())
				.then(key => resolve(key))

		}).then(function (key) {
			mapBoxAdapter
				.setKey(key)
				.configurateWhitForm(
					controlsMapBox,
					{
						//El centro por omisión es una opción de Configuración › Sistema; si no llega, el de reserva del adaptador.
						defaultLongitude: pcsphpGlobals.mapDefaultCenter !== null ? pcsphpGlobals.mapDefaultCenter.longitude : null,
						defaultLatitude: pcsphpGlobals.mapDefaultCenter !== null ? pcsphpGlobals.mapDefaultCenter.latitude : null,
						ignoreDefaultCss: true,
					},
					{
						zoom: pcsphpGlobals.mapDefaultCenter !== null ? pcsphpGlobals.mapDefaultCenter.zoom : 10,
					}
				)
		}).finally(function () {
			removeGenericLoader(loaderMap)
		})

	}
})
