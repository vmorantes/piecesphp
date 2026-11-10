window.addEventListener('load', function () {
	//El panel pone el contenedor de `table.content-table` (admin-area/js/main.js, contentTablesStackable()): en el móvil la
	//tabla se desplaza. Con la clase `content-table-cards`, cada fila es una tarjeta.
	$('table[example-table]').DataTable({
		paging: false,
		searching: false,
		info: false,
		ordering: false,
		responsive: false,
		autoWidth: false,
		//Sin paginación ni búsqueda, DataTables pinta igual sus filas de arriba y abajo, vacías: franjas en blanco.
		layout: { topStart: null, topEnd: null, bottomStart: null, bottomEnd: null },
	})
})
