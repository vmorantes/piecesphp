window.addEventListener('load', function () {

	//Las tarjetas de cada pestaña, cada una con su buscador: dataTablesServerProccesingOnCards busca sus controles dentro de
	//su contenedor. 12 por página (12/24/48) y la línea «Viendo desde X hasta Y de N elementos», como el prototipo.
	for (const status of ['actives-elements', 'inactives', 'all', 'blocked', 'pending', 'rejected']) {
		dataTablesServerProccesingOnCards(`.table-to-cards.${status}`, 12, {}, {
			containerCardsClass: 'ui three stackable doubling cards',
			lengthPaginationSelector: 'select[length-pagination]',
			withInfo: true,
		})
	}

	$('.tabs-controls [data-tab]').tab()

})
