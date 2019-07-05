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

	let bgHandler = function (e) {

		if ($(window).width() > 768) {

			bgElement.css({
				'background-image': `url(${urlImage})`,
			})

		} else {

			bgElement.css({
				'background-image': 'none',
			})

		}

	}

	bgHandler()

	$(window).resize(bgHandler)
}

/**
 * Configura el formulario de logueo
 */
function configLoginForm() {

	let form = $('[login-form-js]')

	window.pcsphp.authenticator.verify(() => window.location.reload())

	let problemsContainer = $('.problems-message-container')
	let problemsContent = problemsContainer.find('.content')
	let problemsTitle = problemsContent.find('.title .text')
	let problemsTitleMark = problemsContent.find('.title .mark')
	let problemsMessage = problemsContent.find('.message')
	let problemsRetryButton = problemsContent.find('.ui.button.retry')
	let problemsMessageBottom = problemsContent.find('.message-bottom')
	let problemsProblemButton = problemsContent.find('.ui.button.problem')

	problemsRetryButton.click(function (e) {
		problemsContainer.hide()
	})

	form.on('submit', function (e) {

		e.preventDefault()

		let login = window.pcsphp.authenticator.authenticateWithUsernamePassword(
			form.find("[name='username']").val(),
			form.find("[name='password']").val()
		)

		let lastURL = form.attr('last-uri')

		login.then(function (res) {

			let auth = res.auth
			let isAuth = res.isAuth

			if (auth === true || isAuth === true) {

				if (typeof lastURL == 'string' && lastURL.trim().length > 0) {

					window.location.href = lastURL

				} else {

					window.location.reload()

				}

			} else {
				problemsContainer.show()
				problemsRetryButton.show()
				problemsMessageBottom.show()
				setMessageError(res.error, res)
			}

		}).catch(function (jqXHR) {
			console.error(jqXHR)
			errorMessage(_i18n('titles', 'error'), _i18n('errors', 'unexpected_error_try_later'))
		})

		return false
	})

	function setMessageError(error, data) {

		problemsTitle
			.parent()
			.html(`<span class="text"></span> <span class="mark"></span>`)

		problemsTitle = problemsContent.find('.title .text')
		problemsTitleMark = problemsContent.find('.title .mark')

		problemsMessageBottom.html('Si continua con problemas para ingresar, por favor utilice la ayuda.')

		if ('INCORRECT_PASSWORD' == error) {

			problemsTitle.html('Contraseña')
			problemsTitleMark.html('inválida')
			problemsMessage.html('Por favor, verifique los datos de ingreso y vuelva a intentar.')

		} else if ('BLOCKED_FOR_ATTEMPTS' == error) {

			problemsTitle.html('Usuario')
			problemsTitleMark.html('bloqueado')
			problemsMessage.html('Por favor, ingrese al siguiente enlace para desbloquear su usuario.')
			problemsRetryButton.hide()
			problemsMessageBottom.hide()

		} else if ('USER_NO_EXISTS' == error) {

			problemsTitle
				.parent()
				.html(`<span class="text">El usuario</span> <span class="mark">${data.user}</span> <span class="text">no existe</span>`)
			problemsMessage.html('Por favor, verifique los datos de ingreso y vuelva a intentar.')

			problemsTitle = problemsContent.find('.title .text')
			problemsTitleMark = problemsContent.find('.title .mark')

		} else if ('MISSING_OR_UNEXPECTED_PARAMS' == error) {

			problemsTitle.html('Error')
			problemsTitleMark.html('')
			problemsTitle.html(data.message)

		} else if ('GENERIC_ERROR' == error) {

			problemsTitle.html('Error')
			problemsTitleMark.html('al ingresar')
			problemsMessage.html('Se ha presentado un error al momento de ingresar, por favor intente nuevamente.')

		} else {

			problemsTitle.html('Error')
			problemsTitleMark.html('')
			problemsMessage.html(data.message)

		}

	}
}

$(document).ready(function (e) {
	changeImageLogin()
	configLoginForm()
})
