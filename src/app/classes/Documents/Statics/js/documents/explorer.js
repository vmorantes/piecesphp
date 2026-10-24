/// <reference path="../../../../../../statics/core/js/configurations.js" />
/// <reference path="../../../../../../statics/core/js/helpers.js" />
window.addEventListener('load', function () {

	//Las columnas las decide Fomantic por ancho: cuatro, dos en tableta (doubling) y una en el móvil (stackable).
	dataTablesServerProccesingOnCards('.table-to-cards', 20, {}, {
		containerCardsClass: 'ui four doubling stackable cards',
	})

})
