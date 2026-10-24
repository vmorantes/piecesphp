/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	//Las tarjetas de cada pestaña, cada una con su buscador: dataTablesServerProccesingOnCards busca sus controles dentro de
	//su contenedor.
	for (const status of ['all', 'actives-elements', 'inactives', 'blocked', 'pending', 'rejected']) {
		dataTablesServerProccesingOnCards(`.table-to-cards.${status}`, 20, {})
	}

	$('.tabs-controls [data-tab]').tab()

})
