/// <reference path="../../../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../../../statics/core/js/helpers.js" />
/// <reference path="../AppPresentations.js" />
window.addEventListener('load', function () {

	const langGroup = 'fancy'

	showGenericLoader(langGroup)

	registerDynamicMessages(langGroup)

	let options = {
		arrows: true,
		infobar: true,
		buttons: [
			"zoom",
			"slideShow",
			"fullScreen",
			"thumbs",
			"close",
		],
		thumbs: {
			autoStart: true
		},
		protect: true,
		fullScreen: {
			autoStart: true
		},
		clickSlide: false,
		clickOutside: false,
		lang: "en",
		i18n: {
			en: {
				CLOSE: _i18n(langGroup, 'CLOSE'),
				NEXT: _i18n(langGroup, 'NEXT'),
				PREV: _i18n(langGroup, 'PREV'),
				ERROR: _i18n(langGroup, 'ERROR'),
				PLAY_START: _i18n(langGroup, 'PLAY_START'),
				PLAY_STOP: _i18n(langGroup, 'PLAY_STOP'),
				FULL_SCREEN: _i18n(langGroup, 'FULL_SCREEN'),
				THUMBS: _i18n(langGroup, 'THUMBS'),
				DOWNLOAD: _i18n(langGroup, 'DOWNLOAD'),
				SHARE: _i18n(langGroup, 'SHARE'),
				ZOOM: _i18n(langGroup, 'ZOOM'),
			},
		}
	}

	$('[data-fancybox="images"]').fancybox(options)

	removeGenericLoader(langGroup)

	/**
	 * @param {String} name 
	 * @returns {void}
	 */
	function registerDynamicMessages(name) {

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
			CLOSE: "Cerrar",
			NEXT: "Siguiente",
			PREV: "Anterior",
			ERROR: "El contenido solicitado no puede ser cargado. <br/> Por favor, inténtelo más tarde.",
			PLAY_START: "Iniciar presentación de diapositivas",
			PLAY_STOP: "Pausa de la presentación de diapositivas",
			FULL_SCREEN: "Pantalla completa",
			THUMBS: "Miniaturas",
			DOWNLOAD: "Descargar",
			SHARE: "Comparte",
			ZOOM: "Zoom"
		}
		let en = {
			CLOSE: "Close",
			NEXT: "Next",
			PREV: "Previous",
			ERROR: "The requested content cannot be loaded. <br/> Please try again later.",
			PLAY_START: "Start slideshow",
			PLAY_STOP: "Pause slideshow",
			FULL_SCREEN: "Full screen",
			THUMBS: "Thumbnails",
			DOWNLOAD: "Download",
			SHARE: "Share",
			ZOOM: "Zoom"
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
