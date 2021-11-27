/**
 * @function LocationsAdapter
 */
function LocationsAdapter() {

	const langGroup = 'LocationsAdapter'

	LocationsAdapter.registerDynamicMessages(langGroup)

	let instance = this

	let locationsURL = 'locations'

	let countriesURL = `${locationsURL}/countries`
	let statesURL = `${locationsURL}/states`
	let citiesURL = `${locationsURL}/cities`
	let pointsURL = `${locationsURL}/points`

	let selectAutoFilledCountryAttr = 'locations-component-auto-filled-country'
	let selectAutoFilledStateAttr = 'locations-component-auto-filled-state'
	let selectAutoFilledCityAttr = 'locations-component-auto-filled-city'
	let selectAutoFilledPointAttr = 'locations-component-auto-filled-point'

	let countryFirstTime = true
	let stateFirstTime = true
	let cityFirstTime = true
	let pointFirstTime = true

	let lastStatesSelected = []
	let lastCitiesSelected = []
	let lastPointsSelected = []

	/**
	 * @method fillSelectWithCountries
	 * @description Rellena un select con los países
	 * @returns {bool} true si hay, false si no
	   */
	this.fillSelectWithCountries = () => {

		let countries = this.getCountries()

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

			for (let country of countries) {
				let option = document.createElement('option')
				option.value = country.id
				option.innerHTML = country.name
				if (hasDefault) {
					if (country.id == defaultValue) {
						option.setAttribute('selected', true)
					}
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

				if (value.trim().length > 0) {
					if (!instance.fillSelectWithStates(value)) {
						infoMessage(_i18n(langGroup, 'Atención'), _i18n(langGroup, 'No hay departamentos registrados.'))
					}
				} else {
					instance.fillSelectWithStates(-1)
				}

			}
			countriesSelect.off('change', eventHandler)
			countriesSelect.on('change', eventHandler)

			if (typeof countriesSelect.attr('with-dropdown') == 'string') {

				countriesSelect.dropdown()
				if (!hasDefault) {
					instance.fillSelectWithStates(null)
					countriesSelect.dropdown('clear')
				} else {
					countriesSelect.dropdown('refresh')
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

				if (statesSelect.parents('.ui.dropdown').length > 0) {
					let field = statesSelect.parents('.field')
					let htmlSelect = statesSelect.get(0).outerHTML
					statesSelect.parents('.ui.dropdown').remove()
					field.append($(htmlSelect).get(0))
					statesSelect = field.find('select')
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

						if (value.trim().length > 0) {
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

				}
				statesSelect.off('change', eventHandler)
				statesSelect.on('change', eventHandler)

			}

			if (typeof statesSelect.attr('with-dropdown') == 'string') {

				statesSelect.dropdown()

				if (!hasDefault) {

					instance.fillSelectWithCities(null)

					if (lastStatesSelected.length > 0) {
						statesSelect.dropdown('refresh')
						instance.fillSelectWithCities(lastStatesSelected)
					} else {
						statesSelect.dropdown('clear')
					}

				} else {
					statesSelect.dropdown('refresh')
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

				if (citiesSelect.parents('.ui.dropdown').length > 0) {
					let field = citiesSelect.parents('.field')
					let htmlSelect = citiesSelect.get(0).outerHTML
					citiesSelect.parents('.ui.dropdown').remove()
					field.append($(htmlSelect).get(0))
					citiesSelect = field.find('select')
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

						if (value.trim().length > 0) {
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

				}
				citiesSelect.off('change', eventHandler)
				citiesSelect.on('change', eventHandler)

			}

			if (typeof citiesSelect.attr('with-dropdown') == 'string') {

				citiesSelect.dropdown()

				if (!hasDefault) {

					instance.fillSelectWithPoints(null)

					if (lastCitiesSelected.length > 0 && state != null) {
						citiesSelect.dropdown('refresh')
						instance.fillSelectWithPoints(lastCitiesSelected)
					} else {
						citiesSelect.dropdown('clear')
					}

				} else {
					citiesSelect.dropdown('refresh')
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

				if (pointsSelect.parents('.ui.dropdown').length > 0) {
					let field = pointsSelect.parents('.field')
					let htmlSelect = pointsSelect.get(0).outerHTML
					pointsSelect.parents('.ui.dropdown').remove()
					field.append($(htmlSelect).get(0))
					pointsSelect = field.find('select')
				}

				//Acciones en eventos

				function eventHandler(e) {

					let that = $(e.currentTarget)
					let value = that.val()
					lastPointsSelected = Array.isArray(value) ? value : [value]

				}
				pointsSelect.off('change', eventHandler)
				pointsSelect.on('change', eventHandler)

			}

			if (typeof pointsSelect.attr('with-dropdown') == 'string') {

				pointsSelect.dropdown()

				if (!hasDefault) {

					if (lastPointsSelected.length > 0 && city != null) {
						pointsSelect.dropdown('refresh')
					} else {
						pointsSelect.dropdown('clear')
					}

				} else {
					pointsSelect.dropdown('refresh')
				}

			}

		}

		return has
	}

	/**
	 * @method getCountries
	 * @description Devuelve los países
	 * @returns {array}
	   */
	this.getCountries = () => {
		let countries = []
		let url = new URL(countriesURL, document.baseURI)
		LocationsAdapter.dataToFilter.onlyCountries.map(function (i) {
			url.searchParams.append('ids[]', i)
		})
		$.ajax({
			async: false,
			url: url,
			dataType: 'json',
		}).done(function (res) {
			countries = res
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
		})
		return states
	}

	/**
	 * @method getCities
	 * @description Devuelve las ciudades del estado provisto
	 * @param {number} state
	 * @returns {array}
	   */
	this.getCities = (state) => {
		let cities = []
		let url = new URL(citiesURL, document.baseURI)
		url.searchParams.set('state', state)
		LocationsAdapter.dataToFilter.onlyCities.map(function (i) {
			url.searchParams.append('ids[]', i)
		})
		$.ajax({
			async: false,
			url: url,
			dataType: 'json',
		}).done(function (res) {
			cities = res
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
		let points = []
		$.ajax({
			async: false,
			url: `${pointsURL}?city=${city}`,
			dataType: 'json',
		}).done(function (res) {
			points = res
		})
		return points
	}

}
LocationsAdapter.dataToFilter = {
	onlyCountries: [],
	onlyStates: [],
	onlyCities: [],
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
