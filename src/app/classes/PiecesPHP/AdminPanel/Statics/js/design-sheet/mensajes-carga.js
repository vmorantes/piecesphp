window.addEventListener('load', function () {
	//Lo normal es encenderlo al pedir y apagarlo al responder (como las tablas, con el evento processing.dt).
	$('[example-zone-show]').on('click', function () {
		showZoneLoader('[example-zone]')
	})
	$('[example-zone-hide]').on('click', function () {
		removeZoneLoader('[example-zone]')
	})
})
