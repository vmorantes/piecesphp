/**
 * Selecciona una imagen de entre varias al azar
 */
function changeImageLogin() {

	let bgElement = $('[bg-js]')
	let backgrounds = atob(bgElement.attr('bg-js'))
	backgrounds = JSON.parse(backgrounds)

	let randomImageLoginIndex = randomNumber(backgrounds.length > 0 ? backgrounds.length - 1 : backgrounds.length)

	let urlImage = backgrounds[randomImageLoginIndex]

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

	function randomNumber(max = 5){
		let number = Math.random() * max
		number = Math.round(number)
		if(number > max){
			number--
		}
		return number
	}
}

/**
 * Configura el formulario de logueo
 */
function configLoginForm() {

	let form = $('[login-form-js]')

	pcsphp.authenticator.verify(() => window.location.reload())

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

		let login = pcsphp.authenticator.authenticateWithUsernamePassword(
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
			errorMessage(_i18n('loginForm', 'Error'), _i18n('loginForm', 'Ha ocurrido un error inesperado, intente más tarde.'))
		})

		return false
	})

	function setMessageError(error, data) {		

		let problemsTitleContainer = problemsTitle.parent()
		problemsTitleContainer.html(`<span class="text"></span> <span class="mark"></span>`)

		problemsTitle = problemsContent.find('.title .text')
		problemsTitleMark = problemsContent.find('.title .mark')

		problemsMessageBottom.html(_i18n('loginForm', 'Si continua con problemas para ingresar, por favor utilice la ayuda.'))

		if ('INCORRECT_PASSWORD' == error) {

			problemsTitleContainer.html(_i18n('loginForm', 'CONTRASEÑA_INVÁLIDA'))
			problemsMessage.html(_i18n('loginForm', 'Por favor, verifique los datos de ingreso y vuelva a intentar.'))

		} else if ('BLOCKED_FOR_ATTEMPTS' == error) {

			problemsTitleContainer.html(_i18n('loginForm', 'USUARIO_BLOQUEADO'))
			problemsMessage.html(_i18n('loginForm', 'Por favor, ingrese al siguiente enlace para desbloquear su usuario.'))
			problemsRetryButton.hide()
			problemsMessageBottom.hide()

		} else if ('USER_NO_EXISTS' == error) {

			problemsTitle
				.parent()
				.html(
					formatStr(
						_i18n('loginForm', 'USUARIO_INEXISTENTE'),
						[
							data.user,
						]
					)
				)
			problemsMessage.html(_i18n('loginForm', 'Por favor, verifique los datos de ingreso y vuelva a intentar.'))
			
			problemsTitle = problemsContent.find('.title .text')
			problemsTitleMark = problemsContent.find('.title .mark')

		} else if ('MISSING_OR_UNEXPECTED_PARAMS' == error) {

			problemsTitle.html('Error')
			problemsTitleMark.html('')
			problemsTitle.html(data.message)

		} else if ('GENERIC_ERROR' == error) {

			problemsTitleContainer.html(_i18n('loginForm', 'ERROR_AL_INGRESAR'))
			problemsMessage.html(_i18n('loginForm', 'Se ha presentado un error al momento de ingresar, por favor intente nuevamente.'))

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
