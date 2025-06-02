/**
 * @function LocationsAdapter
 */
function LocationsAdapter(attributes = {
	selectAutoFilledRegionAttr: 'locations-component-auto-filled-region',
	selectAutoFilledCountryAttr: 'locations-component-auto-filled-country',
	selectAutoFilledStateAttr: 'locations-component-auto-filled-state',
	selectAutoFilledCityAttr: 'locations-component-auto-filled-city',
	selectAutoFilledPointAttr: 'locations-component-auto-filled-point',
}) {

	const langGroup = 'LocationsAdapter'

	LocationsAdapter.registerDynamicMessages(langGroup)

	let instance = this

	let locationsURL = 'locations'

	let regionsURL = `${locationsURL}/regions`
	let countriesURL = `${locationsURL}/countries`
	let statesURL = `${locationsURL}/states`
	let citiesURL = `${locationsURL}/cities`
	let pointsURL = `${locationsURL}/points`

	let {
		selectAutoFilledRegionAttr,
		selectAutoFilledCountryAttr,
		selectAutoFilledStateAttr,
		selectAutoFilledCityAttr,
		selectAutoFilledPointAttr,
	} = attributes

	let regionFirstTime = true
	let countryFirstTime = true
	let stateFirstTime = true
	let cityFirstTime = true
	let pointFirstTime = true

	let lastCountrySelected = []
	let lastStatesSelected = []
	let lastCitiesSelected = []
	let lastPointsSelected = []

	let currentRegionDropdown = null
	let currentCountryDropdown = null
	let currentStateDropdown = null
	let currentCityDropdown = null
	let currentPointDropdown = null

	let onConfigDropdowns = () => { }
	let onChangeRegionDropdown = () => { }
	let onChangeCountryDropdown = () => { }
	let onChangeStateDropdown = () => { }
	let onChangeCityDropdown = () => { }
	let onChangePointDropdown = () => { }

	/**
	 * @method fillSelectWithRegions
	 * @description Rellena un select con las regiones
	 * @returns {bool} true si hay, false si no
	   */
	this.fillSelectWithRegions = () => {

		let regions = this.getRegions()

		let regionsSelect = $(`[${selectAutoFilledRegionAttr}]`)

		let has = true

		if (regionsSelect.length > 0) {

			has = regions.length > 0

			let firstOption = regionsSelect.find(`option[value=""]`)

			let attrValue = regionsSelect.attr(selectAutoFilledRegionAttr)
			let hasDefault = typeof attrValue == 'string' && attrValue.trim().length > 0 && regionFirstTime
			let defaultValue = hasDefault ? attrValue.trim() : null

			regionFirstTime = false

			if (firstOption.length > 0) {
				firstOption = firstOption.get(0).outerHTML
			} else if (regions.length > 0) {
				firstOption = `<option value="">${_i18n(langGroup, 'Seleccione una opción')}</option>`
			} else {
				firstOption = null
			}

			regionsSelect.html('')

			if (firstOption != null) {
				regionsSelect.append(firstOption)
			}

			//Acciones con valores iniciales

			for (let region of regions) {
				let option = document.createElement('option')
				option.value = region.name
				option.innerHTML = region.name
				if (hasDefault) {
					if (region.name == defaultValue) {
						option.setAttribute('selected', true)
					}
				}
				regionsSelect.append(option)
			}

			let selectedOption = regionsSelect.find('option').filter(':selected')
			let selectedValue = selectedOption.length > 0 ? selectedOption.val().trim() : ''

			if (selectedValue.length > 0) {
				if (!instance.fillSelectWithCountries(selectedValue)) {
					infoMessage(_i18n(langGroup, 'Atención'), _i18n(langGroup, 'No hay países registrados.'))
				}
			}

			//Acciones con eventos

			function eventHandler(e) {

				let value = $(e.target).val()
				value = typeof value == 'string' ? value : ''
				const loaderName = generateUniqueID('event')
				showGenericLoader(loaderName)

				if (typeof value == 'string' && value.trim().length > 0) {
					if (!instance.fillSelectWithCountries(value)) {
						infoMessage(_i18n(langGroup, 'Atención'), _i18n(langGroup, 'No hay países registrados.'))
					}
				} else {
					instance.fillSelectWithCountries('NONE')
				}

				removeGenericLoader(loaderName)

			}

			if (regionsSelect.attr('event-attach') !== 'yes') {
				regionsSelect.off('change', eventHandler)
				regionsSelect.on('change', eventHandler)
				regionsSelect.attr('event-attach', 'yes')
			}

			if (typeof regionsSelect.attr('with-dropdown') == 'string') {

				$(`[${selectAutoFilledRegionAttr}]`).addClass('search')
				let dropdown = null
				if (currentRegionDropdown === null) {
					dropdown = configFomanticDropdown(`[${selectAutoFilledRegionAttr}]`, {
						onChange: function (value, text, $element) {
							onChangeRegionDropdown(value, text, $element)
						},
					})[0]
					currentRegionDropdown = dropdown
					onConfigDropdowns()
				} else {
					dropdown = currentRegionDropdown
				}

				if (!hasDefault) {
					instance.fillSelectWithCountries(-1)
					dropdown.dropdown('clear')
				} else {
					dropdown.dropdown('refresh')
				}

			}

		}

		return has
	}

	/**
	 * @method fillSelectWithCountries
	 * @description Rellena un select con los países
	 * @param {string} region El nombre de la región
	 * @returns {bool} true si hay, false si no
	   */
	this.fillSelectWithCountries = (region = null) => {

		let countries = this.getCountries(region)

		let countriesSelect = $(`[${selectAutoFilledCountryAttr}]`)

		let has = true

		if (countriesSelect.length > 0) {

			has = countries.length > 0

			let firstOption = countriesSelect.find(`option[value=""]`)

			let attrValue = countriesSelect.attr(selectAutoFilledCountryAttr)
			let hasDefault = typeof attrValue == 'string' && attrValue.trim().length > 0 && countryFirstTime
			let defaultValue = hasDefault ? attrValue.trim() : null

			countryFirstTime = false

			if (firstOption.length > 0) {
				firstOption = firstOption.get(0).outerHTML
			} else if (countries.length > 0) {
				firstOption = `<option value="">${_i18n(langGroup, 'Seleccione una opción')}</option>`
			} else {
				firstOption = null
			}

			countriesSelect.html('')

			if (firstOption != null) {
				countriesSelect.append(firstOption)
			}

			//Acciones con valores iniciales
			let hasSelected = false
			for (let country of countries) {
				let option = document.createElement('option')
				option.value = country.id
				option.innerHTML = country.name
				if (hasDefault) {
					if (country.id == defaultValue) {
						option.setAttribute('selected', true)
						hasSelected = true
					}
				}
				if (LocationsAdapter.dataToFilter.countriesSelected.includes(parseInt(country.id)) || LocationsAdapter.dataToFilter.countriesSelected.includes(`${country.id}`)) {
					option.setAttribute('selected', true)
					hasSelected = true
				}
				countriesSelect.append(option)
			}

			let selectedOption = countriesSelect.find('option').filter(':selected')
			let selectedValue = selectedOption.length > 0 ? selectedOption.val().trim() : ''

			if (selectedValue.length > 0) {
				if (!instance.fillSelectWithStates(selectedValue)) {
					infoMessage(_i18n(langGroup, 'Atención'), _i18n(langGroup, 'No hay departamentos registrados.'))
				}
			}

			//Acciones con eventos

			function eventHandler(e) {

				let value = $(e.target).val()
				value = typeof value == 'string' ? value : ''
				const loaderName = generateUniqueID('event')
				showGenericLoader(loaderName)

				if (typeof value == 'string' && value.trim().length > 0) {
					if (!instance.fillSelectWithStates(value)) {
						infoMessage(_i18n(langGroup, 'Atención'), _i18n(langGroup, 'No hay departamentos registrados.'))
					}
				} else {
					instance.fillSelectWithStates(-1)
				}

				removeGenericLoader(loaderName)

			}

			if (countriesSelect.attr('event-attach') !== 'yes') {
				countriesSelect.off('change', eventHandler)
				countriesSelect.on('change', eventHandler)
				countriesSelect.attr('event-attach', 'yes')
			}

			if (typeof countriesSelect.attr('with-dropdown') == 'string') {

				$(`[${selectAutoFilledCountryAttr}]`).addClass('search')
				let dropdown = null
				if (currentCountryDropdown === null) {
					dropdown = configFomanticDropdown(`[${selectAutoFilledCountryAttr}]`, {
						onChange: function (value, text, $element) {
							onChangeCountryDropdown(value, text, $element)
						},
					})[0]
					currentCountryDropdown = dropdown
					onConfigDropdowns()
				} else {
					dropdown = currentCountryDropdown
				}

				if (!hasDefault && !hasSelected) {
					instance.fillSelectWithStates(null)
					dropdown.dropdown('clear')
				} else {
					dropdown.dropdown('refresh')
				}

			}

		}

		return has
	}

	/**
	 * @method fillSelectWithStates
	 * @description Rellena un select con los estados del país provisto
	 * @param {number} country El id del país
	 * @returns {bool} true si hay, false si no
	   */
	this.fillSelectWithStates = (country) => {

		let states = []

		let statesSelect = $(`[${selectAutoFilledStateAttr}]`)

		let has = true

		if (statesSelect.length > 0) {

			country = typeof country == 'undefined' ? null : country
			states = country !== null ? this.getStates(country) : []

			has = states.length > 0

			let firstOption = statesSelect.find(`option[value=""]`)

			if (firstOption.length > 0) {
				firstOption = firstOption.get(0).outerHTML
			} else if (states.length > 0) {
				firstOption = `<option value="">${_i18n(langGroup, 'Seleccione una opción')}</option>`
			} else {
				firstOption = null
			}

			statesSelect.html('')

			if (firstOption != null) {
				statesSelect.append(firstOption)
			}

			let hasDefault = false

			if (country !== null) {

				//Acciones cuando comienza con valores definidos

				let attrValue = statesSelect.attr(selectAutoFilledStateAttr)
				let defaultValues = []
				if (typeof attrValue == 'string') {

					if (attrValue.trim().split(', ').length > 0) {

						defaultValues = attrValue.trim().split(', ').filter((i) => {
							return i.trim().length > 0
						}).map(e => {
							return parseInt(e)
						})

						hasDefault = defaultValues.length > 0 && stateFirstTime

					}

				}
				stateFirstTime = false

				for (let state of states) {
					let option = document.createElement('option')
					option.value = state.id
					option.innerHTML = state.name
					if (hasDefault) {
						if (defaultValues.indexOf(state.id) !== -1) {
							option.setAttribute('selected', true)
						}
					} else {
						if (lastStatesSelected.indexOf(state.id) !== -1) {
							option.setAttribute('selected', true)
						}
					}
					statesSelect.append(option)
				}

				lastStatesSelected = Array.isArray(statesSelect.val()) ? statesSelect.val() : [statesSelect.val()]

				if (statesSelect.closest('.ui.dropdown').length > 0) {
					if (currentStateDropdown instanceof $) {
						$(currentStateDropdown.simulatorNode).find('option').removeAttr('selected')
						const options = Array.from(statesSelect.find('option'))
						for (let option of options) {
							option = $(option)
							if (option.is(':selected') && option.attr('value').length > 0) {
								$(currentStateDropdown.simulatorNode).append(`<option value="${option.attr('value')}" selected></option>`)
							}
						}
						currentStateDropdown.dropdown('clear')
						currentStateDropdown.find('.default.text').text($(firstOption).text())
						currentStateDropdown.dropdown('refresh')
						currentStateDropdown.setRequired(true)
					}
				}

				if (hasDefault) {

					if (!instance.fillSelectWithCities(defaultValues)) {
						infoMessage(
							_i18n(langGroup, 'Atención'),
							_i18n(langGroup, `No hay ciudades registradas en el/los departamento(s) seleccionado(s).`)
						)
					}

				}

				//Acciones en eventos

				function eventHandler(e) {

					let that = $(e.currentTarget)
					let value = that.val()
					lastStatesSelected = Array.isArray(value) ? value : [value]
					const loaderName = generateUniqueID('event')
					showGenericLoader(loaderName)

					if (Array.isArray(value)) {

						if (value.length > 0) {

							if (!instance.fillSelectWithCities(value)) {
								infoMessage(
									_i18n(langGroup, 'Atención'),
									_i18n(langGroup, `No hay ciudades registradas en el/los departamento(s) seleccionado(s).`)
								)
							}

						} else {
							instance.fillSelectWithCities(-1)
						}

					} else {

						if (typeof value == 'string' && value.trim().length > 0) {
							if (!instance.fillSelectWithCities(value)) {
								infoMessage(
									_i18n(langGroup, 'Atención'),
									_i18n(langGroup, `No hay ciudades registradas en el/los departamento(s) seleccionado(s).`)
								)
							}
						} else {
							instance.fillSelectWithCities(-1)
						}

					}

					removeGenericLoader(loaderName)

				}

				if (statesSelect.attr('event-attach') !== 'yes') {
					statesSelect.off('change', eventHandler)
					statesSelect.on('change', eventHandler)
					statesSelect.attr('event-attach', 'yes')
				}

			}

			if (typeof statesSelect.attr('with-dropdown') == 'string') {

				$(`[${selectAutoFilledStateAttr}]`).addClass('search')
				let dropdown = null
				if (currentStateDropdown === null) {
					dropdown = configFomanticDropdown(`[${selectAutoFilledStateAttr}]`, {
						onChange: function (value, text, $element) {
							onChangeStateDropdown(value, text, $element)
						},
					})[0]
					currentStateDropdown = dropdown
					onConfigDropdowns()
				} else {
					dropdown = currentStateDropdown
				}

				if (!hasDefault) {

					instance.fillSelectWithCities(null)

					if (lastStatesSelected.length > 0) {
						dropdown.dropdown('refresh')
						instance.fillSelectWithCities(lastStatesSelected)
					} else {
						dropdown.dropdown('clear')
					}

				} else {
					dropdown.dropdown('refresh')
				}

			}

		}

		return has
	}

	/**
	 * @method fillSelectWithCities
	 * @description Rellena un select con las ciudades del estado provisto
	 * @param {number} state El id del estado
	 * @returns {bool} true si hay, false si no
	   */
	this.fillSelectWithCities = (state) => {

		let cities = []

		let citiesSelect = $(`[${selectAutoFilledCityAttr}]`)

		let has = true

		if (citiesSelect.length > 0) {

			if (state !== null || Array.isArray(state)) {

				state = Array.isArray(state) ? state : [state]

				for (let i of state) {
					i = typeof i == 'undefined' ? null : i
					let _cities = this.getCities(i)

					for (let j of _cities) {
						cities.push(j)
					}
				}

			}

			has = cities.length > 0

			let firstOption = citiesSelect.find(`option[value=""]`)

			if (firstOption.length > 0) {
				firstOption = firstOption.get(0).outerHTML
			} else if (cities.length > 0) {
				firstOption = `<option value="">${_i18n(langGroup, 'Seleccione una opción')}</option>`
			} else {
				firstOption = null
			}

			citiesSelect.html('')

			if (firstOption != null) {
				citiesSelect.append(firstOption)
			}

			let hasDefault = false

			if (state !== null) {

				//Acciones cuando comienza con valores definidos

				let attrValue = citiesSelect.attr(selectAutoFilledCityAttr)
				let defaultValues = []
				if (typeof attrValue == 'string') {

					if (attrValue.trim().split(', ').length > 0) {

						defaultValues = attrValue.trim().split(', ').filter((i) => {
							return i.trim().length > 0
						}).map(e => {
							return parseInt(e)
						})

						hasDefault = defaultValues.length > 0 && cityFirstTime

					}

				}

				cityFirstTime = false

				for (let city of cities) {
					let option = document.createElement('option')
					option.value = city.id
					option.innerHTML = city.name
					if (hasDefault) {
						if (defaultValues.indexOf(city.id) !== -1) {
							option.setAttribute('selected', true)
						}
					} else {
						if (lastCitiesSelected.indexOf(city.id) !== -1) {
							option.setAttribute('selected', true)
						}
					}
					citiesSelect.append(option)
				}

				lastCitiesSelected = Array.isArray(citiesSelect.val()) ? citiesSelect.val() : [citiesSelect.val()]

				if (citiesSelect.closest('.ui.dropdown').length > 0) {
					if (currentCityDropdown instanceof $) {
						$(currentCityDropdown.simulatorNode).find('option').removeAttr('selected')
						const options = Array.from(citiesSelect.find('option'))
						for (let option of options) {
							option = $(option)
							if (option.is(':selected') && option.attr('value').length > 0) {
								$(currentCityDropdown.simulatorNode).append(`<option value="${option.attr('value')}" selected></option>`)
							}
						}
						currentCityDropdown.dropdown('clear')
						currentCityDropdown.find('.default.text').text($(firstOption).text())
						currentCityDropdown.dropdown('refresh')
						currentCityDropdown.setRequired(true)
					}
				}

				if (hasDefault) {

					if (!instance.fillSelectWithPoints(defaultValues)) {
						infoMessage(
							_i18n(langGroup, 'Atención'),
							_i18n(langGroup, `No hay locaciones registradas en la(s) ciudad(es) seleccionada(s).`)
						)
					}

				}

				//Acciones en eventos

				function eventHandler(e) {

					let that = $(e.currentTarget)
					let value = that.val()
					lastCitiesSelected = Array.isArray(value) ? value : [value]
					const loaderName = generateUniqueID('event')
					showGenericLoader(loaderName)

					if (Array.isArray(value)) {

						if (value.length > 0) {

							if (!instance.fillSelectWithPoints(value)) {
								infoMessage(
									_i18n(langGroup, 'Atención'),
									_i18n(langGroup, `No hay locaciones registradas en la(s) ciudad(es) seleccionada(s).`)
								)
							}

						} else {
							instance.fillSelectWithPoints(-1)
						}

					} else {

						if (typeof value == 'string' && value.trim().length > 0) {
							if (!instance.fillSelectWithPoints(value)) {
								infoMessage(
									_i18n(langGroup, 'Atención'),
									_i18n(langGroup, `No hay locaciones registradas en la(s) ciudad(es) seleccionada(s).`)
								)
							}
						} else {
							instance.fillSelectWithPoints(-1)
						}

					}

					removeGenericLoader(loaderName)

				}

				if (citiesSelect.attr('event-attach') !== 'yes') {
					citiesSelect.off('change', eventHandler)
					citiesSelect.on('change', eventHandler)
					citiesSelect.attr('event-attach', 'yes')
				}

			}

			if (typeof citiesSelect.attr('with-dropdown') == 'string') {

				$(`[${selectAutoFilledCityAttr}]`).addClass('search')
				let dropdown = null
				if (currentCityDropdown === null) {
					dropdown = configFomanticDropdown(`[${selectAutoFilledCityAttr}]`, {
						onChange: function (value, text, $element) {
							onChangeCityDropdown(value, text, $element)
						},
					})[0]
					currentCityDropdown = dropdown
					onConfigDropdowns()
				} else {
					dropdown = currentCityDropdown
				}

				if (!hasDefault) {

					instance.fillSelectWithPoints(null)

					if (lastCitiesSelected.length > 0 && state != null) {
						dropdown.dropdown('refresh')
						instance.fillSelectWithPoints(lastCitiesSelected)
					} else {
						dropdown.dropdown('clear')
					}

				} else {
					dropdown.dropdown('refresh')
				}

			}

		}

		return has
	}

	/**
	 * @method fillSelectWithPoints
	 * @description Rellena un select con los puntos de la ciudad provista
	 * @param {number} city El id del estado
	 * @returns {bool} true si hay, false si no
	   */
	this.fillSelectWithPoints = (city) => {

		let points = []

		let pointsSelect = $(`[${selectAutoFilledPointAttr}]`)

		let has = true

		if (pointsSelect.length > 0) {

			if (city !== null || Array.isArray(city)) {

				city = Array.isArray(city) ? city : [city]

				for (let i of city) {
					i = typeof i == 'undefined' ? null : i
					let _points = this.getPoints(i)

					for (let j of _points) {
						points.push(j)
					}
				}

			}

			has = points.length > 0

			let firstOption = pointsSelect.find(`option[value=""]`)

			if (firstOption.length > 0) {
				firstOption = firstOption.get(0).outerHTML
			} else if (points.length > 0) {
				firstOption = `<option value="">${_i18n(langGroup, 'Seleccione una opción')}</option>`
			} else {
				firstOption = null
			}

			pointsSelect.html('')

			if (firstOption != null) {
				pointsSelect.append(firstOption)
			}

			let hasDefault = false

			if (city !== null) {

				//Acciones cuando comienza con valores definidos

				let attrValue = pointsSelect.attr(selectAutoFilledPointAttr)
				let defaultValues = []
				if (typeof attrValue == 'string') {

					if (attrValue.trim().split(', ').length > 0) {

						defaultValues = attrValue.trim().split(', ').filter((i) => {
							return i.trim().length > 0
						}).map(e => {
							return parseInt(e)
						})

						hasDefault = defaultValues.length > 0 && pointFirstTime

					}

				}

				pointFirstTime = false

				for (let point of points) {
					let option = document.createElement('option')
					option.value = point.id
					option.innerHTML = point.name
					if (hasDefault) {
						if (defaultValues.indexOf(point.id) !== -1) {
							option.setAttribute('selected', true)
						}
					} else {
						if (lastPointsSelected.indexOf(point.id) !== -1) {
							option.setAttribute('selected', true)
						}
					}
					pointsSelect.append(option)
				}

				lastPointsSelected = Array.isArray(pointsSelect.val()) ? pointsSelect.val() : [pointsSelect.val()]

				if (pointsSelect.closest('.ui.dropdown').length > 0) {
					if (currentPointDropdown instanceof $) {
						$(currentPointDropdown.simulatorNode).find('option').removeAttr('selected')
						const options = Array.from(pointsSelect.find('option'))
						for (let option of options) {
							option = $(option)
							if (option.is(':selected') && option.attr('value').length > 0) {
								$(currentPointDropdown.simulatorNode).append(`<option value="${option.attr('value')}" selected></option>`)
							}
						}
						currentPointDropdown.dropdown('clear')
						currentPointDropdown.find('.default.text').text($(firstOption).text())
						currentPointDropdown.dropdown('refresh')
						currentPointDropdown.setRequired(true)
					}
				}

				//Acciones en eventos

				function eventHandler(e) {

					let that = $(e.currentTarget)
					let value = that.val()
					lastPointsSelected = Array.isArray(value) ? value : [value]
					const loaderName = generateUniqueID('event')
					showGenericLoader(loaderName)
					removeGenericLoader(loaderName)
				}

				if (pointsSelect.attr('event-attach') !== 'yes') {
					pointsSelect.off('change', eventHandler)
					pointsSelect.on('change', eventHandler)
					pointsSelect.attr('event-attach', 'yes')
				}

			}

			if (typeof pointsSelect.attr('with-dropdown') == 'string') {

				$(`[${selectAutoFilledPointAttr}]`).addClass('search')
				let dropdown = null
				if (currentPointDropdown === null) {
					dropdown = configFomanticDropdown(`[${selectAutoFilledPointAttr}]`, {
						onChange: function (value, text, $element) {
							onChangePointDropdown(value, text, $element)
						},
					})[0]
					currentPointDropdown = dropdown
					onConfigDropdowns()
				} else {
					dropdown = currentPointDropdown
				}

				if (!hasDefault) {

					if (lastPointsSelected.length > 0 && city != null) {
						dropdown.dropdown('refresh')
					} else {
						dropdown.dropdown('clear')
					}

				} else {
					dropdown.dropdown('refresh')
				}

			}

		}

		return has
	}

	/**
	 * @method fillSelectWithCountries
	 * @description Rellena un select con los países
	 * @param {string} region El nombre de la región
	 * @returns {bool} true si hay, false si no
	   */
	this.fillSelectWithCountriesToCities = (region = null) => {

		let countries = this.getCountries(region)

		let countriesSelect = $(`[${selectAutoFilledCountryAttr}]`)

		let has = true

		if (countriesSelect.length > 0) {

			has = countries.length > 0

			let firstOption = countriesSelect.find(`option[value=""]`)

			let attrValue = countriesSelect.attr(selectAutoFilledCountryAttr)
			let hasDefault = typeof attrValue == 'string' && attrValue.trim().length > 0 && countryFirstTime
			let defaultValue = hasDefault ? attrValue.trim() : null

			countryFirstTime = false

			if (firstOption.length > 0) {
				firstOption = firstOption.get(0).outerHTML
			} else if (countries.length > 0) {
				firstOption = `<option value="">${_i18n(langGroup, 'Seleccione una opción')}</option>`
			} else {
				firstOption = null
			}

			countriesSelect.html('')

			if (firstOption != null) {
				countriesSelect.append(firstOption)
			}

			//Acciones con valores iniciales
			let hasSelected = false
			for (let country of countries) {
				let option = document.createElement('option')
				option.value = country.id
				option.innerHTML = country.name
				if (hasDefault) {
					if (country.id == defaultValue) {
						option.setAttribute('selected', true)
						hasSelected = true
					}
				}
				if (LocationsAdapter.dataToFilter.countriesSelected.includes(parseInt(country.id)) || LocationsAdapter.dataToFilter.countriesSelected.includes(`${country.id}`)) {
					option.setAttribute('selected', true)
					hasSelected = true
				}
				countriesSelect.append(option)
			}

			let selectedOption = countriesSelect.find('option').filter(':selected')
			let selectedValue = selectedOption.length > 0 ? selectedOption.val().trim() : ''

			if (selectedValue.length > 0) {
				if (!instance.fillSelectWithCitiesFromCountry(selectedValue)) {
					infoMessage(_i18n(langGroup, 'Atención'), _i18n(langGroup, 'No hay ciudades registradas.'))
				}
			}

			//Acciones con eventos

			function eventHandler(e) {

				let value = $(e.target).val()
				value = typeof value == 'string' ? value : ''
				const loaderName = generateUniqueID('event')
				showGenericLoader(loaderName)

				if (typeof value == 'string' && value.trim().length > 0) {
					if (!instance.fillSelectWithCitiesFromCountry(value)) {
						infoMessage(_i18n(langGroup, 'Atención'), _i18n(langGroup, 'No hay ciudades registradas.'))
					}
				} else {
					instance.fillSelectWithCitiesFromCountry(-1)
				}

				removeGenericLoader(loaderName)

			}

			if (countriesSelect.attr('event-attach') !== 'yes') {
				countriesSelect.off('change', eventHandler)
				countriesSelect.on('change', eventHandler)
				countriesSelect.attr('event-attach', 'yes')
			}

			if (typeof countriesSelect.attr('with-dropdown') == 'string') {

				$(`[${selectAutoFilledCountryAttr}]`).addClass('search')
				let dropdown = null
				if (currentCountryDropdown === null) {
					dropdown = configFomanticDropdown(`[${selectAutoFilledCountryAttr}]`, {
						onChange: function (value, text, $element) {
							onChangeCountryDropdown(value, text, $element)
						},
					})[0]
					currentCountryDropdown = dropdown
					onConfigDropdowns()
				} else {
					dropdown = currentCountryDropdown
				}

				if (!hasDefault && !hasSelected) {
					instance.fillSelectWithCitiesFromCountry(null)
					dropdown.dropdown('clear')
				} else {
					dropdown.dropdown('refresh')
				}

			}

		}

		return has
	}

	/**
	 * @method fillSelectWithCities
	 * @description Rellena un select con las ciudades del país provisto
	 * @param {number} country El id del país
	 * @returns {bool} true si hay, false si no
	   */
	this.fillSelectWithCitiesFromCountry = (country) => {

		let cities = []

		let citiesSelect = $(`[${selectAutoFilledCityAttr}]`)

		let has = true

		if (citiesSelect.length > 0) {

			if (country !== null || Array.isArray(country)) {

				country = Array.isArray(country) ? country : [country]

				for (let i of country) {
					i = typeof i == 'undefined' ? null : i
					let _cities = this.getCities(null, i)

					for (let j of _cities) {
						cities.push(j)
					}
				}

			}

			has = cities.length > 0

			let firstOption = citiesSelect.find(`option[value=""]`)

			if (firstOption.length > 0) {
				firstOption = firstOption.get(0).outerHTML
			} else if (cities.length > 0) {
				firstOption = `<option value="">${_i18n(langGroup, 'Seleccione una opción')}</option>`
			} else {
				firstOption = null
			}

			citiesSelect.html('')

			if (firstOption != null) {
				citiesSelect.append(firstOption)
			}

			let hasDefault = false

			if (country !== null) {

				//Acciones cuando comienza con valores definidos

				let attrValue = citiesSelect.attr(selectAutoFilledCityAttr)
				let defaultValues = []
				if (typeof attrValue == 'string') {

					if (attrValue.trim().split(', ').length > 0) {

						defaultValues = attrValue.trim().split(', ').filter((i) => {
							return i.trim().length > 0
						}).map(e => {
							return parseInt(e)
						})

						hasDefault = defaultValues.length > 0 && cityFirstTime

					}

				}

				cityFirstTime = false

				for (let city of cities) {
					let option = document.createElement('option')
					option.value = city.id
					option.innerHTML = city.name
					if (hasDefault) {
						if (defaultValues.indexOf(city.id) !== -1) {
							option.setAttribute('selected', true)
						}
					} else {
						if (lastCitiesSelected.indexOf(city.id) !== -1) {
							option.setAttribute('selected', true)
						}
					}
					citiesSelect.append(option)
				}

				lastCitiesSelected = Array.isArray(citiesSelect.val()) ? citiesSelect.val() : [citiesSelect.val()]

				if (citiesSelect.closest('.ui.dropdown').length > 0) {
					if (currentCityDropdown instanceof $) {
						$(currentCityDropdown.simulatorNode).find('option').removeAttr('selected')
						const options = Array.from(citiesSelect.find('option'))
						for (let option of options) {
							option = $(option)
							if (option.is(':selected') && option.attr('value').length > 0) {
								$(currentCityDropdown.simulatorNode).append(`<option value="${option.attr('value')}" selected></option>`)
							}
						}
						currentCityDropdown.dropdown('clear')
						currentCityDropdown.find('.default.text').text($(firstOption).text())
						currentCityDropdown.dropdown('refresh')
						currentCityDropdown.setRequired(true)
					}
				}

				if (hasDefault) {

					if (!instance.fillSelectWithPoints(defaultValues)) {
						infoMessage(
							_i18n(langGroup, 'Atención'),
							_i18n(langGroup, `No hay locaciones registradas en la(s) ciudad(es) seleccionada(s).`)
						)
					}

				}

				//Acciones en eventos

				function eventHandler(e) {

					let that = $(e.currentTarget)
					let value = that.val()
					lastCitiesSelected = Array.isArray(value) ? value : [value]
					const loaderName = generateUniqueID('event')
					showGenericLoader(loaderName)

					if (Array.isArray(value)) {

						if (value.length > 0) {

							if (!instance.fillSelectWithPoints(value)) {
								infoMessage(
									_i18n(langGroup, 'Atención'),
									_i18n(langGroup, `No hay locaciones registradas en la(s) ciudad(es) seleccionada(s).`)
								)
							}

						} else {
							instance.fillSelectWithPoints(-1)
						}

					} else {

						if (typeof value == 'string' && value.trim().length > 0) {
							if (!instance.fillSelectWithPoints(value)) {
								infoMessage(
									_i18n(langGroup, 'Atención'),
									_i18n(langGroup, `No hay locaciones registradas en la(s) ciudad(es) seleccionada(s).`)
								)
							}
						} else {
							instance.fillSelectWithPoints(-1)
						}

					}

					removeGenericLoader(loaderName)

				}

				if (citiesSelect.attr('event-attach') !== 'yes') {
					citiesSelect.off('change', eventHandler)
					citiesSelect.on('change', eventHandler)
					citiesSelect.attr('event-attach', 'yes')
				}

			}

			if (typeof citiesSelect.attr('with-dropdown') == 'string') {

				$(`[${selectAutoFilledCityAttr}]`).addClass('search')
				let dropdown = null
				if (currentCityDropdown === null) {
					dropdown = configFomanticDropdown(`[${selectAutoFilledCityAttr}]`, {
						onChange: function (value, text, $element) {
							onChangeCityDropdown(value, text, $element)
						},
					})[0]
					currentCityDropdown = dropdown
					onConfigDropdowns()
				} else {
					dropdown = currentCityDropdown
				}

				if (!hasDefault) {

					instance.fillSelectWithPoints(null)

					if (lastCitiesSelected.length > 0 && country != null) {
						dropdown.dropdown('refresh')
						instance.fillSelectWithPoints(lastCitiesSelected)
					} else {
						dropdown.dropdown('clear')
					}

				} else {
					dropdown.dropdown('refresh')
				}

			}

		}

		return has
	}


	/**
	 * @returns {$|null}
	 */
	this.getCurrentRegionDropdown = function () {
		let element = currentRegionDropdown
		return element instanceof $ ? element : null
	}

	/**
	 * @returns {$|null}
	 */
	this.getCurrentCountryDropdown = function () {
		let element = currentCountryDropdown
		return element instanceof $ ? element : null
	}

	/**
	 * @returns {$|null}
	 */
	this.getCurrentStateDropdown = function () {
		let element = currentStateDropdown
		return element instanceof $ ? element : null
	}

	/**
	 * @returns {$|null}
	 */
	this.getCurrentCityDropdown = function () {
		let element = currentCityDropdown
		return element instanceof $ ? element : null
	}

	/**
	 * @returns {$|null}
	 */
	this.getCurrentPointDropdown = function () {
		let element = currentPointDropdown
		return element instanceof $ ? element : null
	}

	/**
	 * @param {Function} callback 
	 * @returns {LocationsAdapter}
	 */
	this.setOnConfigDropdowns = function (callback) {
		onConfigDropdowns = typeof callback == 'function' ? callback : () => { }
		return this
	}

	/**
	 * @param {Function} callback 
	 * @returns {LocationsAdapter}
	 */
	this.setOnChangeRegionDropdown = function (callback) {
		onChangeRegionDropdown = typeof callback == 'function' ? callback : () => { }
		return this
	}

	/**
	 * @param {Function} callback 
	 * @returns {LocationsAdapter}
	 */
	this.setOnChangeCountryDropdown = function (callback) {
		onChangeCountryDropdown = typeof callback == 'function' ? callback : () => { }
		return this
	}

	/**
	 * @param {Function} callback 
	 * @returns {LocationsAdapter}
	 */
	this.setOnChangeStateDropdown = function (callback) {
		onChangeStateDropdown = typeof callback == 'function' ? callback : () => { }
		return this
	}

	/**
	 * @param {Function} callback 
	 * @returns {LocationsAdapter}
	 */
	this.setOnChangeCityDropdown = function (callback) {
		onChangeCityDropdown = typeof callback == 'function' ? callback : () => { }
		return this
	}

	/**
	 * @param {Function} callback 
	 * @returns {LocationsAdapter}
	 */
	this.setOnChangePointDropdown = function (callback) {
		onChangePointDropdown = typeof callback == 'function' ? callback : () => { }
		return this
	}

	/**
	 * @method getRegions
	 * @description Devuelve los países
	 * @returns {array}
	   */
	this.getRegions = () => {
		const loaderName = generateUniqueID()
		showGenericLoader(loaderName)
		let regions = []
		let url = new URL(regionsURL, document.baseURI)
		$.ajax({
			async: false,
			url: url,
			dataType: 'json',
		}).done(function (res) {
			regions = res
		}).always(function () {
			removeGenericLoader(loaderName)
		})
		return regions
	}

	/**
	 * @method getCountries
	 * @description Devuelve los países
	 * @param {string} region
	 * @returns {array}
	   */
	this.getCountries = (region = null) => {
		const loaderName = generateUniqueID()
		showGenericLoader(loaderName)
		let countries = []
		let url = new URL(countriesURL, document.baseURI)
		LocationsAdapter.dataToFilter.onlyCountries.map(function (i) {
			url.searchParams.append('ids[]', i)
		})
		if (region !== null) {
			url.searchParams.append('region', region)
		}
		$.ajax({
			async: false,
			url: url,
			dataType: 'json',
		}).done(function (res) {
			countries = res
		}).always(function () {
			removeGenericLoader(loaderName)
		})
		return countries
	}

	/**
	 * @method getStates
	 * @description Devuelve los estados del país provisto
	 * @param {number} country
	 * @returns {array}
	   */
	this.getStates = (country) => {
		const loaderName = generateUniqueID()
		showGenericLoader(loaderName)
		let states = []
		let url = new URL(statesURL, document.baseURI)
		url.searchParams.set('country', country)
		LocationsAdapter.dataToFilter.onlyStates.map(function (i) {
			url.searchParams.append('ids[]', i)
		})
		$.ajax({
			async: false,
			url: url,
			dataType: 'json',
		}).done(function (res) {
			states = res
		}).always(function () {
			removeGenericLoader(loaderName)
		})
		return states
	}

	/**
	 * @method getCities
	 * @description Devuelve las ciudades del estado/país provisto
	 * @param {number} state
	 * @param {number} country
	 * @returns {array}
	   */
	this.getCities = (state, country) => {
		const loaderName = generateUniqueID()
		showGenericLoader(loaderName)
		let cities = []
		let url = new URL(citiesURL, document.baseURI)
		if (typeof state != 'undefined' && state !== null) {
			url.searchParams.set('state', state)
		}
		if (typeof country != 'undefined' && country !== null) {
			url.searchParams.set('country', country)
		}
		LocationsAdapter.dataToFilter.onlyCities.map(function (i) {
			url.searchParams.append('ids[]', i)
		})
		$.ajax({
			async: false,
			url: url,
			dataType: 'json',
		}).done(function (res) {
			cities = res
		}).always(function () {
			removeGenericLoader(loaderName)
		})
		return cities
	}

	/**
	 * @method getPoints
	 * @description Devuelve los puntos de la ciudad provista
	 * @param {number} city
	 * @returns {array}
	   */
	this.getPoints = (city) => {
		const loaderName = generateUniqueID()
		showGenericLoader(loaderName)
		let points = []
		$.ajax({
			async: false,
			url: `${pointsURL}?city=${city}`,
			dataType: 'json',
		}).done(function (res) {
			points = res
		}).always(function () {
			removeGenericLoader(loaderName)
		})
		return points
	}

}
LocationsAdapter.dataToFilter = {
	onlyCountries: [],
	onlyStates: [],
	onlyCities: [],
	countriesSelected: [],
}
/**
 * @param {String} name 
 * @returns {void}
 */
LocationsAdapter.registerDynamicMessages = function (name) {

	if (typeof pcsphpGlobals != 'object') {
		pcsphpGlobals = {}
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
	}

	let en = {
		'Seleccione una opción': 'Select an option',
		'Atención': 'Attention',
		'No hay departamentos registrados.': 'There are no registered departments.',
		'No hay ciudades registradas en el/los departamento(s) seleccionado(s).': 'There are no cities registered in the selected state/states.',
		'No hay locaciones registradas en la(s) ciudad(es) seleccionada(s).': 'There are no localities registered in the selected city(ies).',
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
