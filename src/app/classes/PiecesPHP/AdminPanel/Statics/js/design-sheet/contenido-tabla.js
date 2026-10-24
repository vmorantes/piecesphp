/// <reference path="../configurations.js" />
/// <reference path="../helpers.js" />
window.addEventListener('load', function () {
	//Al iniciarse DataTables, el panel quita `unstackable` a toda `table.content-table` y pone la etiqueta de cada celda
	//(admin-area/js/main.js, contentTablesStackable()): por debajo de 768 px, cada fila es una tarjeta.
	$('table[example-table]').DataTable({
		paging: false,
		searching: false,
		info: false,
		ordering: false,
		responsive: false,
		autoWidth: false,
	})
})
