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
		experienceForm()
	})

	function profileForm() {

		/* Selectores y elementos de interfaz */
		const formSelector = `.ui.form.my-profile`

		//Formulario
		let form = genericFormHandler(formSelector, {
			onSetFormData: function (formData) {
				return formData
			},
			onInvalidEvent: function (event) {

				let element = event.target
				let validationMessage = element.validationMessage
				let jElement = $(element)
				let field = jElement.closest('.field')
				let nameOnLabel = field.find('label').text().trim()
				if (field.length == 0) {
					field = jElement.closest('.attach-placeholder')
					nameOnLabel = field.find('>label >.text >.header >.title').text()
				}

				errorMessage(`${nameOnLabel}: ${validationMessage}`)

				event.preventDefault()

			}
		})

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

			$('body').addClass('wait-to-action')
			const loaderNameCancel = generateUniqueID('loaderCancel-')
			let title = _i18n(langGroup, 'Confirmación')
			let message = _i18n(langGroup, '¿Está seguro de cancelar la operación?')
			$.toast({
				title: title,
				message: message,
				displayTime: 0,
				class: 'white',
				position: 'top center',
				classActions: 'top attached',
				actions: [{
					text: _i18n(langGroup, 'Sí'),
					class: 'red',
					click: function () {
						showGenericLoader(loaderNameCancel)
						window.location.reload()
						removeGenericLoader(loaderNameCancel)
						$('body').removeClass('wait-to-action')
					}
				}, {
					text: _i18n(langGroup, 'No'),
					class: 'blue',
					click: function () {
						$('body').removeClass('wait-to-action')
						return true
					}
				}]
			})
		}
		cancelButtons.on('click', handleCancel)
		externalCancelButtons.on('click', handleCancel)

	}

	function experienceForm() {

		/* Selectores y elementos de interfaz */
		const formSelector = `.ui.form.my-profile-experiences`

		//Tabla
		const experienceListDataTable = dataTablesServerProccesingOnCards('.table-to-cards', 20, {
			drawCallbackEnd: function (cards) {
				window.dispatchEvent(new Event('canDeletePreviousExperience'))
				$('[data-tooltip]').popup()
			},
		}, {
			containerCardsClass: 'list-cards-container',
			containerCardsSelector: '.list-cards-container',
			cardsSelector: '.experience-card',
		})

		window.addEventListener('wasDeletedPreviousExperience', function () {
			experienceListDataTable.DataTable().draw()
		})

		//Formulario
		let form = genericFormHandler(formSelector, {
			onSetFormData: function (formData) {
				formData.set('startDate', form.find(`[name="startDate"]`).parent().calendar('get date', 'Y-m-d'))
				formData.set('endDate', form.find(`[name="endDate"]`).parent().calendar('get date', 'Y-m-d'))
				return formData
			},
			onInvalidEvent: function (event) {

				let element = event.target
				let validationMessage = element.validationMessage
				let jElement = $(element)
				let field = jElement.closest('.field')
				let nameOnLabel = field.find('label').text().trim()
				if (field.length == 0) {
					field = jElement.closest('.attach-placeholder')
					nameOnLabel = field.find('>label >.text >.header >.title').text()
				}

				errorMessage(`${nameOnLabel}: ${validationMessage}`)

				event.preventDefault()

			},
			onSuccess: function () {
				form.get(0).reset()
				form.find('.ui.dropdown').dropdown('clear').dropdown('refresh')
				experienceListDataTable.DataTable().draw()
			}
		})

		//Botones
		let translateButton = form.find('button[translate]')

		//Comportamiento de botón de traducción
		const handleTranslate = function (event) {
			event.preventDefault()
			const translatableFields = {
				experienceName: [
					'es',
					'fr',
				],
				description: [
					'es',
					'fr',
				],
			}
			const fieldsByLang = {}
			const fieldsBase = {}
			const fieldsNeedTranslation = {}

			for (const fieldName in translatableFields) {
				const availablesLangs = translatableFields[fieldName]
				for (const lang of availablesLangs) {
					fieldsByLang[fieldName] = typeof fieldsByLang[fieldName] == 'object' ? fieldsByLang[fieldName] : {}
					fieldsByLang[fieldName][lang] = form.find(`[name="${fieldName}[${lang}]"]`)
				}
			}

			for (const fieldName in fieldsByLang) {
				const fieldLangs = fieldsByLang[fieldName]
				for (const lang in fieldLangs) {
					const field = fieldLangs[lang]
					const fieldValue = field.val()
					if (fieldValue.trim().length > 0) {
						if (typeof fieldsBase[fieldName] == 'undefined') {
							fieldsBase[fieldName] = {
								lang: lang,
								value: fieldValue,
							}
						}
					} else {
						if (typeof fieldsNeedTranslation[fieldName] == 'undefined') {
							fieldsNeedTranslation[fieldName] = []
						}
						fieldsNeedTranslation[fieldName].push({
							lang: lang,
							field: field,
						})
					}
				}
			}


			const translationURL = new URL('core/api/translations', pcsphpGlobals.baseURL)

			for (const fieldName in fieldsNeedTranslation) {

				const baseData = typeof fieldsBase[fieldName] !== 'undefined' ? fieldsBase[fieldName] : null
				const fieldsNeedData = fieldsNeedTranslation[fieldName]

				for (const fieldNeedData of fieldsNeedData) {

					if (baseData !== null) {

						const langFrom = baseData.lang
						const valueFrom = baseData.value
						const langTo = fieldNeedData.lang

						const objectToTranslate = {}
						objectToTranslate[fieldName] = valueFrom

						const loaderName = generateUniqueID()
						showGenericLoader(loaderName)

						translationURL.searchParams.set('text', base64EncodeUnicode(JSON.stringify(objectToTranslate)))
						translationURL.searchParams.set('from', langFrom)
						translationURL.searchParams.set('to', langTo)

						getRequest(translationURL.href, null, {
							'PCSPHP-Response-Expected-Language': pcsphpGlobals.lang,
						}).done(function (response) {

							const success = response.success
							const message = response.message
							const result = response.result
							const error = response.error

							if (success) {

								const translations = result.translation

								for (const translationKeyText in translations) {
									const translation = translations[translationKeyText]
									const fieldConfig = fieldsNeedTranslation[translationKeyText]
									if (typeof translation == 'string' && translation.trim().length > 0) {
										fieldNeedData.field.val(translation)
									}
								}

							} else {
								errorMessage(message)
							}

							if (error !== null) {
								console.error(error)
							}
						}).always(function () {
							removeGenericLoader(loaderName)
						})

					}
				}

			}

		}
		translateButton.on('click', handleTranslate)

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

			fetch('configurations/mapbox-key')
				.then(response => response.text())
				.then(key => resolve(key))

		}).then(function (key) {
			mapBoxAdapter
				.setKey(key)
				.configurateWhitForm(
					controlsMapBox,
					{
						defaultLongitude: -74.8065913846496,
						defaultLatitude: 11.0021516003209,
						ignoreDefaultCss: true,
					},
					{
						zoom: 10,
					}
				)
		}).finally(function () {
			removeGenericLoader(loaderMap)
		})

	}
})
