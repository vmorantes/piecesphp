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

	window.pcsphp.authenticator.verify(() => window.location.reload())

	form.on('submit', function (e) {

		e.preventDefault()

		let login = window.pcsphp.authenticator.authenticateWithUsernamePassword(
			form.find("[name='username']").val(),
			form.find("[name='password']").val()
		)

		let lastURL = form.attr('last-uri')

		login.then(function (res) {

			let auth = res.auth
			let message = res.message
			let isAuth = res.isAuth

			if (auth === true || isAuth === true) {

				if (typeof lastURL == 'string' && lastURL.trim().length > 0) {

					window.location.href = lastURL

				} else {

					window.location.reload()

				}

			} else {
				errorMessage(_i18n('titles', 'error'), message)
			}

		}).catch(function (jqXHR) {
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
