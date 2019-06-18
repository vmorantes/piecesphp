/**
 * Selecciona una imagen de entre varias al con el mismo nombre al azar (diferenciadas 
 * por un número al final) para usarla como fondo.
 * 
 * @param {string} nombreImagen Primera parte del nombre de la imagen
 * @param {number} totalImagesLogin Cantidad de imágenes
 */
function changeImageLogin(nombreImagen = 'bg', totalImagesLogin = 5) {

	let bgElement = $('[bg-js]')
	let randomImageLogin = Math.floor((Math.random() * totalImagesLogin) + 1)
	let url = 'statics/login-and-recovery/images/login'
	let imageName = `${nombreImagen}${randomImageLogin}.jpg`
	let urlImage = `${url}/${imageName}`

	bgElement.css({
		'background-image': `url(${urlImage})`,
	})
}

/**
 * Configura el formulario de logueo
 */
function configLoginForm() {

	let form = $('[login-form-js]')

	form.on('submit', function (e) {

		e.preventDefault()

		let login = postRequest('users/login', new FormData(form[0]))

		login.done(function (res) {
			if (res.auth === true) {
				window.location.reload()
			} else {
				errorMessage(_i18n('titles', 'error'), res.message)
			}
		})

		login.fail(function (jqXHR) {
			console.error(jqXHR)
			errorMessage(_i18n('titles', 'error'), _i18n('errors', 'unexpected_error_try_later'))
		})

		return false
	})
}

$(document).ready(function (e) {
	changeImageLogin()
	configLoginForm()
})
