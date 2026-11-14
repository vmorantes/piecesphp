//elFinder 2.1.70 usa la API de botón de jQuery UI 1.11 (icons.primary, text: false): la 1.14 solo la acepta con esto.
if (typeof window.jQuery === 'function') {
	window.jQuery.uiBackCompat = true
	//jQuery UI completo trae su accordion y pisaría el de Fomantic: se guarda aquí y lo repone jquery-ui-fomantic-restore.js.
	window.jQuery.pcsphpFomanticAccordion = window.jQuery.fn.accordion
}
