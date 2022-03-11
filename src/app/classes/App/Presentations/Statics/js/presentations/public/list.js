/// <reference path="../../../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../../../statics/core/js/helpers.js" />
/// <reference path="../AppPresentations.js" />
window.addEventListener('load', function () {

	const langGroup = 'AppPresentations'

	registerDynamicMessages(langGroup)

	let presentation = new AppPresentations({
		requestURL: $('[data-presentation-url]').attr('data-presentation-url'),
		page: 1,
		perPage: 10,
		containerSelector: '[app-presentations-js]',
		loadMoreTriggerSelector: '[app-presentations-load-more-js]',
		onDraw: (item, parsed) => {
			return parsed
		},
		onEmpty: (container) => {
			container.addClass('empty')
			container.html(_i18n(langGroup, 'No hay presentaciones disponibles'))
		},
	})

	presentation.loadItems()

	$('.ui.dropdown').dropdown()

	function registerDynamicMessages(name) {

		if (typeof pcsphpGlobals != 'object') {
			window.pcsphpGlobals = {}
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
			'No hay presentaciones disponibles': 'No hay presentaciones disponibles',
		}

		let en = {
			'No hay presentaciones disponibles': 'No presentations available',
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

})
