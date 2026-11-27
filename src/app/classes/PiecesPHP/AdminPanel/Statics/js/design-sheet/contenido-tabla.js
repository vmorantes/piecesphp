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
		//El collapsing del <th> no llega a las celdas: DataTables solo les copia el className de columnDefs.
		columnDefs: [{ targets: [1, 2, 3], className: 'collapsing' }],
		//Sin paginación ni búsqueda, DataTables pinta igual sus filas de arriba y abajo, vacías: franjas en blanco.
		layout: { topStart: null, topEnd: null, bottomStart: null, bottomEnd: null },
	})
})
