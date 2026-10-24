/// <reference path="../configurations.js" />
/// <reference path="../helpers.js" />
window.addEventListener('load', function () {
	//En un formulario con genericFormHandler, el desplegable se inicia ANTES que él, para que valide (publicaciones, add-form.js).
	$('select[example-dropdown]').dropdown()
})
