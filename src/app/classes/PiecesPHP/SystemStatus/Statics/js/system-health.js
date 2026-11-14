window.addEventListener('load', function () {

	//Salud de la instalación: las pestañas (Resumen y un grupo en cada una). Acotado a esta vista: `.menu .item` sin más
	//tomaría también el menú lateral.
	$('[data-system-health-tabs] .item').tab()

})
