//Va DETRÁS de jquery-ui.min.js: devuelve a Fomantic el accordion que jQuery UI acaba de pisar (elFinder no lo usa).
if (typeof window.jQuery === 'function' && typeof window.jQuery.pcsphpFomanticAccordion === 'function') {
	window.jQuery.fn.accordion = window.jQuery.pcsphpFomanticAccordion
	delete window.jQuery.pcsphpFomanticAccordion
}
