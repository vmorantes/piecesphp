/// <reference path="../../core/js/helpers.js" />
/// <reference path="../../core/js/user-system/main_system_user.js" />
/// <reference path="../../core/js/user-system/PiecesPHPSystemUserHelper.js" />

window.addEventListener(pcsphpGlobals.events.configurationsAndWindowLoad, function (e) {
	changeImageLogin()
	configLoginForm()
})

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

		bgElement.css({
			'background-image': `url(${urlImage})`,
		})

	}

	bgHandler()

	function randomNumber(max = 5) {
		let number = Math.random() * max
		number = Math.round(number)
		if (number > max) {
			number--
		}
		return number
	}
}

/**
 * Configura el formulario de logueo
 */
function configLoginForm() {

	pcsphp.authenticator.verify(() => window.location.reload())

	const STORAGE_USER_NAME = 'user_name_storage'
	const defaultView = $('[defauld-show]')
	const errorView = $('[show-error]')
	const onErrorTryAgainButton = $('[try-again]')
	const userStorage = localStorage.getItem(STORAGE_USER_NAME)

	let form = $('[login-form-js]')
	let userNameInput = form.find("[name='username']")
	let rememberCheckbox = form.find('.remember-me input[type=checkbox]')
	let twoFactorContainer = form.find(".field[twofa-field]")
	let twoFactorInput = twoFactorContainer.find("[name='twoFactor']")
	const otpTrigger = form.find('[otp-trigger]')

	onErrorTryAgainButton.on('click', () => {
		errorView.hide()
		defaultView.show()
	})

	userNameInput.on('input', function (e) {
		const username = userNameInput.val().trim()
		if (username.length > 0) {
			otpTrigger.removeClass('disabled')
			otpTrigger.attr('disabled', false)
			otpTrigger.removeAttr('disabled')
		} else {
			otpTrigger.addClass('disabled')
			otpTrigger.attr('disabled', true)
		}
	})

	if (userStorage) {
		userNameInput.val(userStorage)
		rememberCheckbox.prop('checked', true)
	}

	userNameInput.trigger('input')
	toggleTwoFactor(false) //Esconder código de autenticación

	form.on('submit', function (e) {

		e.preventDefault()

		const rememberMe = rememberCheckbox.is(':checked')

		if (rememberMe) {
			localStorage.setItem(
				STORAGE_USER_NAME,
				userNameInput.val()
			)
		} else {
			localStorage.removeItem(STORAGE_USER_NAME)
		}

		const loaderVerifyTwoFactorStatus = 'VerifyTwoFactorStatus'
		showGenericLoader(loaderVerifyTwoFactorStatus)
		pcsphp.authenticator.verifyTwoFactorStatus(userNameInput.val(), function (required2FA) {

			const toLogin = function () {
				const LoaderName = 'login'
				showGenericLoader(LoaderName)
				let login = pcsphp.authenticator.authenticateWithUsernamePassword(
					userNameInput.val(),
					form.find("[name='password']").val(),
					twoFactorInput.val()
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
						defaultView.hide()
						errorView.show()
						setMessageError(res.error, res)
						removeGenericLoader(LoaderName)
					}

				}).catch(function (jqXHR) {
					removeGenericLoader(LoaderName)
					console.error(jqXHR)
					errorMessage(_i18n('loginForm', 'Error'), _i18n('loginForm', 'Ha ocurrido un error inesperado, intente más tarde.'))
				})
			}

			if (!required2FA) {
				toLogin()
				toggleTwoFactor(false)
			} else {
				if (!twoFactorContainer.is(':visible')) {
					toggleTwoFactor(true)
				} else {
					toLogin()
				}
			}

			removeGenericLoader(loaderVerifyTwoFactorStatus)

		}).catch(function () {
			removeGenericLoader(loaderVerifyTwoFactorStatus)
		})

		return false
	})

	OTPHandler()

	function OTPHandler() {
		otpTrigger.on('click', function (e) {
			e.preventDefault()
			const username = userNameInput.val().trim()
			const requestURLStr = otpTrigger.data('url')
			if (username.length > 0 && typeof requestURLStr == 'string' && requestURLStr.trim().length > 0) {
				showGenericLoader('OTPHandler')
				const requestURL = new URL(requestURLStr)
				requestURL.searchParams.set('username', username)
				fetch(requestURL).then(res => res.json()).then(function (response) {
					if (response.success) {
						successMessage(response.name, response.message)
					} else {
						if (response.values.error == 'USER_NO_EXISTS') {
							defaultView.hide()
							errorView.show()
							response.user = response.values.user
							setMessageError(response.values.error, response)
						} else {
							errorMessage(response.name, response.message)
						}
					}

				}).finally(function () {
					removeGenericLoader('OTPHandler')
				})
			}
		})
	}

	function toggleTwoFactor(active = true) {
		twoFactorInput.val('')
		if (active) {
			twoFactorContainer.show(500)
			twoFactorInput.attr('required', true)
		} else {
			twoFactorContainer.hide()
			twoFactorInput.attr('required', false)
			twoFactorInput.removeAttr('required')
		}
	}

	function setMessageError(error, data) {

		let titleError = errorView.find('[title]')
		let messageError = errorView.find('[message]')
		let messageBottom = errorView.find('[bottom-message]')
		messageBottom.html(_i18n('loginForm', 'Si continua con problemas para ingresar, por favor utilice la ayuda.'))

		if ('INCORRECT_PASSWORD' == error) {

			titleError.html(_i18n('loginForm', 'CONTRASEÑA_INVÁLIDA'))
			messageError.html(_i18n('loginForm', 'Por favor, verifique los datos de ingreso y vuelva a intentar.'))

		} else if ('BLOCKED_FOR_ATTEMPTS' == error) {

			titleError.html(_i18n('loginForm', 'USUARIO_BLOQUEADO'))
			messageError.html(_i18n('loginForm', 'Por favor, ingrese al siguiente enlace para desbloquear su usuario.'))
			onErrorTryAgainButton.hide()
			messageBottom.hide()

		} else if ('USER_NO_EXISTS' == error) {

			titleError.html(
				formatStr(
					_i18n('loginForm', 'USUARIO_INEXISTENTE'),
					[
						data.user,
					]
				)
			)
			messageError.html(_i18n('loginForm', 'Por favor, verifique los datos de ingreso y vuelva a intentar.'))


		} else if ('MISSING_OR_UNEXPECTED_PARAMS' == error) {

			titleError.html('Error')
			messageError.html(data.message)

		} else if ('GENERIC_ERROR' == error) {

			titleError.html(_i18n('loginForm', 'ERROR_AL_INGRESAR'))
			messageError.html(_i18n('loginForm', 'Se ha presentado un error al momento de ingresar, por favor intente nuevamente.'))

		} else {

			titleError.html('Error')
			messageError.html(data.message)

		}

	}
}
